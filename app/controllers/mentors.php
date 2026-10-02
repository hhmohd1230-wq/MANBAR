<?php
function page_mentors(): void
{
    $u = require_login();
    $q = input('q');
    $where = ["u.status = 'active'", 'm.active = 1'];
    $params = [];
    if ($q !== '') { $where[] = '(u.full_name LIKE ? OR m.expertise LIKE ? OR m.about LIKE ?)'; array_push($params, "%$q%", "%$q%", "%$q%"); }
    $mentors = qall('SELECT m.*, u.id AS uid, u.full_name, u.avatar_url, u.email, u.verified, u.headline, u.department, u.role,
        (SELECT COUNT(*) FROM mentorship_requests r WHERE r.mentor_id = u.id AND r.status IN (\'accepted\',\'completed\')) AS mentees
        FROM mentor_profiles m JOIN users u ON u.id = m.user_id WHERE ' . implode(' AND ', $where) . ' ORDER BY mentees DESC, u.full_name', $params);
    $pending = (int) qval("SELECT COUNT(*) FROM mentorship_requests WHERE mentor_id = ? AND status = 'pending'", [$u['id']]);
    render('mentors', compact('u', 'mentors', 'q', 'pending'));
}

function page_mentor(int $id): void
{
    $u = require_login();
    $m = qrow('SELECT m.*, u.id AS uid, u.full_name, u.avatar_url, u.email, u.verified, u.headline, u.department, u.faculty FROM mentor_profiles m JOIN users u ON u.id = m.user_id WHERE m.user_id = ?', [$id]) ?? abort(404, 'Mentor not found.');
    $mentees = (int) qval("SELECT COUNT(*) FROM mentorship_requests WHERE mentor_id = ? AND status IN ('accepted','completed')", [$id]);
    $open = qrow("SELECT * FROM mentorship_requests WHERE mentor_id = ? AND student_id = ? AND status IN ('pending','accepted') ORDER BY id DESC", [$id, $u['id']]);
    render('mentor', compact('u', 'm', 'mentees', 'open'));
}

function mentor_request(int $id): void
{
    $u = require_login();
    $m = qrow('SELECT * FROM mentor_profiles WHERE user_id = ? AND active = 1', [$id]) ?? abort(404);
    if ($id === (int) $u['id']) { flash('You cannot mentor yourself 😉', 'error'); redirect("mentors/$id"); }
    if (qval("SELECT COUNT(*) FROM mentorship_requests WHERE mentor_id = ? AND student_id = ? AND status IN ('pending','accepted')", [$id, $u['id']])) { flash('You already have an open request with this mentor.', 'error'); redirect("mentors/$id"); }
    $topic = mb_substr(input('topic'), 0, 200);
    if (mb_strlen($topic) < 4) { flash('Tell the mentor what you want help with.', 'error'); redirect("mentors/$id"); }
    insert('mentorship_requests', ['mentor_id' => $id, 'student_id' => $u['id'], 'topic' => $topic, 'message' => mb_substr(input('message'), 0, 1500)]);
    notify($id, 'mentor_request', $u['full_name'] . ' asked for mentorship: ' . excerpt($topic, 60), 'mentorship');
    flash('Request sent to the mentor.');
    redirect('mentorship');
}

function page_mentorship(): void
{
    $u = require_login();
    $incoming = qall('SELECT r.*, u.full_name, u.avatar_url, u.email, u.major FROM mentorship_requests r JOIN users u ON u.id = r.student_id WHERE r.mentor_id = ? ORDER BY (r.status = \'pending\') DESC, r.id DESC', [$u['id']]);
    $outgoing = qall('SELECT r.*, u.full_name, u.avatar_url, u.email FROM mentorship_requests r JOIN users u ON u.id = r.mentor_id WHERE r.student_id = ? ORDER BY r.id DESC', [$u['id']]);
    render('mentorship', compact('u', 'incoming', 'outgoing'));
}

function mentor_decide(int $rid): void
{
    $u = require_login();
    $r = qrow('SELECT * FROM mentorship_requests WHERE id = ? AND mentor_id = ?', [$rid, $u['id']]) ?? abort(404);
    $d = input('decision');
    if ($d === 'accept') {
        $when = input('session_at');
        $ts = $when ? strtotime($when) : false;
        update('mentorship_requests', ['status' => 'accepted', 'session_at' => $ts ? date('Y-m-d H:i:s', $ts) : null], 'id = ?', [$rid]);
        notify((int) $r['student_id'], 'mentor_accept', $u['full_name'] . ' accepted your mentorship request' . ($ts ? ' — session on ' . date('M j, H:i', $ts) : ''), 'mentorship');
        check_badges((int) $r['student_id']);
    } elseif ($d === 'decline') {
        update('mentorship_requests', ['status' => 'declined', 'feedback' => mb_substr(input('feedback'), 0, 1000) ?: null], 'id = ?', [$rid]);
        notify((int) $r['student_id'], 'mentor_decline', $u['full_name'] . ' is unable to take your request right now.', 'mentorship');
    } elseif ($d === 'complete') {
        update('mentorship_requests', ['status' => 'completed', 'feedback' => mb_substr(input('feedback'), 0, 1500) ?: null], 'id = ?', [$rid]);
        award_points((int) $u['id'], 20, 'Completed a mentoring session');
        award_points((int) $r['student_id'], 5, 'Completed a mentoring session');
        notify((int) $r['student_id'], 'mentor_feedback', $u['full_name'] . ' shared feedback on your session', 'mentorship');
    } elseif ($d === 'schedule') {
        $ts = strtotime(input('session_at'));
        if ($ts) { update('mentorship_requests', ['session_at' => date('Y-m-d H:i:s', $ts)], 'id = ?', [$rid]); notify((int) $r['student_id'], 'mentor_schedule', 'Session scheduled for ' . date('M j, H:i', $ts) . ' with ' . $u['full_name'], 'mentorship'); }
    }
    flash('Updated.');
    redirect('mentorship');
}
