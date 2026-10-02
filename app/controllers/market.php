<?php
function page_marketplace(): void
{
    $u = require_login();
    $cat = input('cat');
    $q = input('q');
    $sort = input('sort', 'new');
    $where = ["s.status = 'active'"];
    $params = [];
    if (isset(SERVICE_CATEGORIES[$cat])) { $where[] = 's.category = ?'; $params[] = $cat; }
    if ($q !== '') { $where[] = '(s.title LIKE ? OR s.description LIKE ?)'; array_push($params, "%$q%", "%$q%"); }
    $order = ['new' => 's.id DESC', 'cheap' => 's.price ASC, s.id DESC', 'top' => 'rating DESC, reviews DESC, s.id DESC'][$sort] ?? 's.id DESC';
    $services = qall('SELECT s.*, u.full_name, u.avatar_url, u.email, u.verified, u.role,
        (SELECT COALESCE(AVG(r.rating), 0) FROM service_reviews r WHERE r.service_id = s.id) AS rating,
        (SELECT COUNT(*) FROM service_reviews r WHERE r.service_id = s.id) AS reviews
        FROM services s JOIN users u ON u.id = s.user_id WHERE ' . implode(' AND ', $where) . " ORDER BY $order LIMIT 60", $params);
    $counts = array_column(qall("SELECT category, COUNT(*) AS n FROM services WHERE status = 'active' GROUP BY category"), 'n', 'category');
    render('marketplace', compact('u', 'services', 'cat', 'q', 'sort', 'counts'));
}

function page_service_new(): void { render('service_new', ['u' => require_login()]); }

function service_create(): void
{
    $u = require_login();
    $title = mb_substr(input('title'), 0, 200);
    $desc = mb_substr(trim((string) ($_POST['description'] ?? '')), 0, 4000);
    $cat = isset(SERVICE_CATEGORIES[input('category')]) ? input('category') : 'other';
    if (mb_strlen($title) < 5 || mb_strlen($desc) < 20) { flash('Add a clear title and a description (20+ characters).', 'error'); redirect('marketplace/new'); }
    $id = insert('services', ['user_id' => $u['id'], 'title' => $title, 'description' => $desc, 'category' => $cat, 'price' => max(0, min(100000, input_int('price'))), 'delivery_days' => max(1, min(90, input_int('delivery_days', 3)))]);
    award_points((int) $u['id'], 10, 'Listed a service');
    flash('Your service is live in the marketplace. +10 points');
    redirect("marketplace/$id");
}

function page_service(int $id): void
{
    $u = require_login();
    $s = qrow('SELECT s.*, u.full_name, u.avatar_url, u.email, u.verified, u.role, u.headline, u.major FROM services s JOIN users u ON u.id = s.user_id WHERE s.id = ?', [$id]) ?? abort(404, 'Service not found.');
    if ($s['status'] === 'hidden' && !is_admin() && (int) $s['user_id'] !== (int) $u['id']) abort(404);
    $reviews = qall('SELECT r.*, u.full_name, u.avatar_url, u.email FROM service_reviews r JOIN users u ON u.id = r.reviewer_id WHERE r.service_id = ? ORDER BY r.id DESC', [$id]);
    $avg = $reviews ? array_sum(array_column($reviews, 'rating')) / count($reviews) : 0;
    $mine = (int) $s['user_id'] === (int) $u['id'];
    $myReq = qrow('SELECT * FROM service_requests WHERE service_id = ? AND buyer_id = ? ORDER BY id DESC', [$id, $u['id']]);
    $canReview = $myReq && $myReq['status'] === 'completed' && !qval('SELECT COUNT(*) FROM service_reviews WHERE service_id = ? AND reviewer_id = ?', [$id, $u['id']]);
    $incoming = $mine ? qall('SELECT r.*, u.full_name, u.avatar_url, u.email FROM service_requests r JOIN users u ON u.id = r.buyer_id WHERE r.service_id = ? ORDER BY r.id DESC', [$id]) : [];
    render('service', compact('u', 's', 'reviews', 'avg', 'mine', 'myReq', 'canReview', 'incoming'));
}

function service_request(int $id): void
{
    $u = require_login();
    $s = qrow("SELECT * FROM services WHERE id = ? AND status = 'active'", [$id]) ?? abort(404);
    if ((int) $s['user_id'] === (int) $u['id']) { flash('You cannot request your own service.', 'error'); redirect("marketplace/$id"); }
    if (qval("SELECT COUNT(*) FROM service_requests WHERE service_id = ? AND buyer_id = ? AND status IN ('pending','accepted')", [$id, $u['id']])) { flash('You already have an open request for this service.', 'error'); redirect("marketplace/$id"); }
    insert('service_requests', ['service_id' => $id, 'buyer_id' => $u['id'], 'message' => mb_substr(input('message'), 0, 1500)]);
    notify((int) $s['user_id'], 'service_request', $u['full_name'] . ' requested “' . excerpt($s['title'], 50) . '”', "marketplace/$id");
    flash('Request sent. The provider will respond soon.');
    redirect("marketplace/$id");
}

function service_request_decide(int $id, int $rid): void
{
    $u = require_login();
    $s = qrow('SELECT * FROM services WHERE id = ?', [$id]) ?? abort(404);
    if ((int) $s['user_id'] !== (int) $u['id']) abort(403);
    $r = qrow('SELECT * FROM service_requests WHERE id = ? AND service_id = ?', [$rid, $id]) ?? abort(404);
    $to = ['accept' => 'accepted', 'decline' => 'declined', 'complete' => 'completed'][input('decision')] ?? abort(422);
    update('service_requests', ['status' => $to], 'id = ?', [$rid]);
    $txt = ['accepted' => 'accepted your request for', 'declined' => 'declined your request for', 'completed' => 'marked as completed:'][$to];
    notify((int) $r['buyer_id'], 'service_' . $to, $u['full_name'] . " $txt “" . excerpt($s['title'], 50) . '”', "marketplace/$id");
    if ($to === 'completed') award_points((int) $u['id'], 10, 'Delivered a service');
    flash('Request ' . $to . '.');
    redirect("marketplace/$id");
}

function service_review(int $id): void
{
    $u = require_login();
    $s = qrow('SELECT * FROM services WHERE id = ?', [$id]) ?? abort(404);
    $ok = qval("SELECT COUNT(*) FROM service_requests WHERE service_id = ? AND buyer_id = ? AND status = 'completed'", [$id, $u['id']]);
    if (!$ok || qval('SELECT COUNT(*) FROM service_reviews WHERE service_id = ? AND reviewer_id = ?', [$id, $u['id']])) { flash('You can only review a completed order once.', 'error'); redirect("marketplace/$id"); }
    $rating = max(1, min(5, input_int('rating', 5)));
    insert('service_reviews', ['service_id' => $id, 'reviewer_id' => $u['id'], 'rating' => $rating, 'comment' => mb_substr(input('comment'), 0, 1000)]);
    notify((int) $s['user_id'], 'review', $u['full_name'] . ' left you a ' . $rating . '★ review', "marketplace/$id");
    if ($rating >= 4) award_points((int) $s['user_id'], $rating === 5 ? 10 : 5, 'Received a ' . $rating . '-star review'); else check_badges((int) $s['user_id']);
    flash('Thanks for your review!');
    redirect("marketplace/$id");
}

function service_toggle(int $id): void
{
    $u = require_login();
    $s = qrow('SELECT * FROM services WHERE id = ?', [$id]) ?? abort(404);
    if ((int) $s['user_id'] !== (int) $u['id'] && !is_admin()) abort(403);
    if (input('delete')) { qexec('DELETE FROM services WHERE id = ?', [$id]); flash('Service deleted.'); redirect('marketplace'); }
    update('services', ['status' => $s['status'] === 'active' ? 'paused' : 'active'], 'id = ?', [$id]);
    redirect("marketplace/$id");
}

function page_orders(): void
{
    $u = require_login();
    $bought = qall('SELECT r.*, s.title, s.price, s.id AS sid, u.full_name AS provider FROM service_requests r JOIN services s ON s.id = r.service_id JOIN users u ON u.id = s.user_id WHERE r.buyer_id = ? ORDER BY r.id DESC', [$u['id']]);
    $sold = qall('SELECT r.*, s.title, s.price, s.id AS sid, u.full_name AS buyer, u.avatar_url, u.email FROM service_requests r JOIN services s ON s.id = r.service_id JOIN users u ON u.id = r.buyer_id WHERE s.user_id = ? ORDER BY r.id DESC', [$u['id']]);
    $mine = qall('SELECT s.*, (SELECT COUNT(*) FROM service_requests r WHERE r.service_id = s.id) AS orders FROM services s WHERE s.user_id = ? ORDER BY s.id DESC', [$u['id']]);
    render('orders', compact('u', 'bought', 'sold', 'mine'));
}
