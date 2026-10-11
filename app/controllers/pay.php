<?php
/*
 * Payments: one checkout for paid courses and marketplace orders, plus the seller wallet.
 *
 * Courses: pay to view. Payment unlocks the course straight away.
 * Marketplace (person to person): the buyer pays according to the terms the seller offered
 * (before / 50% deposit / after / cash). Online payments are held by MANBAR and released to the
 * seller when the buyer confirms delivery; sellers then cash out from their wallet.
 *
 * Card payments for courses go to Stripe when a Stripe key is configured. Everything else runs in
 * demo mode until real provider keys are added: the UI is real, no money moves, and it says so.
 */

const PAY_METHODS = [
    'card' => ['Card', 'Visa, Mastercard, American Express'],
    'applepay' => ['Apple Pay', 'Pay with Face ID or Touch ID'],
    'paypal' => ['PayPal', 'Pay with your PayPal balance or card'],
    'tabby' => ['tabby', 'Split in 4 payments, no interest'],
];
const PAY_TERMS = [
    'before' => ['Pay before work starts', 'The full amount is held safely by MANBAR and released to the seller when you confirm delivery.'],
    'split' => ['50% now, 50% on delivery', 'Pay a deposit to start. The rest is paid when you confirm delivery.'],
    'after' => ['Pay after delivery', 'Nothing to pay now. You pay when you confirm the work is done.'],
    'cash' => ['Cash in person', 'You pay the seller in cash when you meet. Confirm it here so it counts.'],
    'free' => ['Free / skill swap', 'No payment for this order.'],
];

function ensure_pay_schema(): void
{
    static $done = false;
    if ($done) return;
    $done = true;
    ensure_market_schema();
    $sqlite = db_driver() === 'sqlite';
    $pk = $sqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';
    $t = $sqlite ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    $rc = table_columns('service_requests');
    foreach (['agreed_price' => 'INT NULL', 'agreed_days' => 'INT NULL', 'pay_terms' => 'VARCHAR(10) NULL', 'offer_note' => 'VARCHAR(500) NULL', 'delivered_at' => 'DATETIME NULL'] as $col => $def)
        if (!in_array($col, $rc, true)) qexec("ALTER TABLE service_requests ADD COLUMN $col $def");
    qexec("CREATE TABLE IF NOT EXISTS market_payments (id $pk, request_id INT NOT NULL, payer_id INT NOT NULL, payee_id INT NOT NULL, amount INT NOT NULL,
        method VARCHAR(12) NOT NULL, stage VARCHAR(10) NOT NULL, status VARCHAR(10) NOT NULL, ref VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, released_at DATETIME NULL,
        FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE)$t");
    qexec("CREATE TABLE IF NOT EXISTS market_payouts (id $pk, user_id INT NOT NULL, amount INT NOT NULL, method VARCHAR(10) NOT NULL, account_hint VARCHAR(40) NOT NULL,
        status VARCHAR(10) NOT NULL DEFAULT 'sent', created_at DATETIME NOT NULL, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE)$t");
}

/** Is this payment method connected to a real provider? (Only card payments for courses, through Stripe, today.) */
function pay_live(string $method, string $type): bool { return $method === 'card' && $type === 'course' && stripe_ready(); }
function pay_ref(): string { return 'MB-' . strtoupper(bin2hex(random_bytes(4))); }

/** What the buyer owes right now on a marketplace order: [amount, stage] or [0, null]. */
function order_due(array $r): array
{
    $price = (int) ($r['agreed_price'] ?? 0);
    $paid = (int) qval("SELECT COALESCE(SUM(amount), 0) FROM market_payments WHERE request_id = ? AND status IN ('held','released')", [$r['id']]);
    if ($price <= 0 || in_array($r['pay_terms'], ['free', 'cash'], true)) return [0, null];
    if ($r['status'] === 'offered') {
        if ($r['pay_terms'] === 'before') return [$price, 'full'];
        if ($r['pay_terms'] === 'split') return [(int) ceil($price / 2), 'deposit'];
        return [0, null];
    }
    if ($r['status'] === 'delivered' && $paid < $price) return [$price - $paid, $paid ? 'balance' : 'full'];
    return [0, null];
}

/** Seller wallet: money released to them, money still held, and what they already cashed out. */
function wallet(int $uid): array
{
    ensure_pay_schema();
    $released = (int) qval("SELECT COALESCE(SUM(amount), 0) FROM market_payments WHERE payee_id = ? AND status = 'released'", [$uid]);
    $held = (int) qval("SELECT COALESCE(SUM(amount), 0) FROM market_payments WHERE payee_id = ? AND status = 'held'", [$uid]);
    $cash = (int) qval("SELECT COALESCE(SUM(amount), 0) FROM market_payments WHERE payee_id = ? AND status = 'cash'", [$uid]);
    $out = (int) qval('SELECT COALESCE(SUM(amount), 0) FROM market_payouts WHERE user_id = ?', [$uid]);
    return ['available' => max(0, $released - $out), 'held' => $held, 'earned' => $released + $cash, 'cash' => $cash, 'paid_out' => $out];
}

/** Post an automatic line in the buyer–seller chat so the deal history lives next to the conversation. */
function order_chat(int $from, int $to, string $text): void
{
    ensure_message_schema();
    insert('messages', ['sender_id' => $from, 'receiver_id' => $to, 'body' => $text]);
}

/* ---------- checkout ---------- */

/** Load what is being paid for. type = course | order. Returns the view data or redirects with a reason. */
function checkout_subject(string $type, int $id, array $u): array
{
    if ($type === 'course') {
        $c = course_load($id);
        if ($c['status'] !== 'published' && !course_can_manage($c, $u)) abort(404);
        if (!course_is_paid($c)) redirect("learn/$id");
        $preview = course_can_manage($c, $u);   // the host or an admin trying the flow: nothing is charged or recorded
        if (!$preview && course_is_enrolled($id, (int) $u['id'])) { flash('You already have this course.'); redirect("learn/$id"); }
        return ['type' => 'course', 'id' => $id, 'amount' => (int) ceil((float) $c['price_aed']), 'title' => $c['title'], 'who' => $c['full_name'], 'whoLabel' => 'Hosted by',
            'cover' => course_cover_css($c), 'line' => 'Lifetime access to every lesson, summary, quiz and file', 'stage' => 'full', 'back' => "learn/$id", 'preview' => $preview, 'terms' => null, 'person' => $c];
    }
    ensure_pay_schema();
    $r = qrow('SELECT r.*, s.title, s.user_id AS seller_id, s.cover_image, s.category, s.id AS sid FROM service_requests r JOIN services s ON s.id = r.service_id WHERE r.id = ?', [$id]) ?? abort(404);
    if ((int) $r['buyer_id'] !== (int) $u['id']) abort(403);
    [$amount, $stage] = order_due($r);
    if (!$amount) redirect('marketplace/' . $r['sid']);
    $seller = qrow('SELECT id, full_name, avatar_url, email, verified FROM users WHERE id = ?', [$r['seller_id']]);
    $stageLine = ['full' => 'Full payment', 'deposit' => '50% deposit to start the work', 'balance' => 'Remaining balance after delivery'][$stage];
    return ['type' => 'order', 'id' => $id, 'amount' => $amount, 'title' => $r['title'], 'who' => $seller['full_name'], 'whoLabel' => 'Seller', 'cover' => service_cover_css($r),
        'line' => $stageLine . ' · agreed price AED ' . (int) $r['agreed_price'] . ' · ' . (int) $r['agreed_days'] . '-day delivery', 'stage' => $stage, 'back' => 'marketplace/' . $r['sid'],
        'preview' => false, 'terms' => $r['pay_terms'], 'person' => $seller, 'request' => $r];
}

function page_checkout(string $type, int $id): void
{
    $u = require_login();
    if (!in_array($type, ['course', 'order'], true)) abort(404);
    $x = checkout_subject($type, $id, $u);
    render('checkout', compact('u', 'x'));
}

function checkout_pay(string $type, int $id): void
{
    $u = require_login();
    if (!in_array($type, ['course', 'order'], true)) abort(404);
    $x = checkout_subject($type, $id, $u);
    $method = isset(PAY_METHODS[input('method')]) ? input('method') : 'card';
    if (preg_match('/^saved-(\d+)$/', input('method'), $sm) && function_exists('billing_methods')) {   // a method saved in Settings → Billing
        $sv = qrow('SELECT * FROM billing_methods WHERE id = ? AND user_id = ?', [(int) $sm[1], $u['id']]);
        if ($sv) $method = $sv['kind'];
    }
    if ($x['preview']) {   // host / admin walking through the student flow
        flash('Preview: this is where the student’s payment would go through. Nothing was charged or saved.');
        redirect($x['back']);
    }
    if (pay_live($method, $type)) course_buy($id);   // real card payment through Stripe Checkout
    $ref = pay_ref();
    if ($type === 'course') {
        insert('course_payments', ['course_id' => $id, 'user_id' => $u['id'], 'provider' => 'demo-' . $method, 'provider_ref' => $ref, 'amount' => $x['amount'], 'currency' => 'aed', 'status' => 'paid', 'paid_at' => now()]);
        enroll_user($id, (int) $u['id']);
        flash('Payment successful (' . PAY_METHODS[$method][0] . ', receipt ' . $ref . '). Welcome to the course! 🎉');
        redirect("learn/$id");
    }
    $r = $x['request'];
    $sellerId = (int) $r['seller_id'];
    if ($x['stage'] === 'balance' || $r['status'] === 'delivered') {
        // paying on delivery: everything goes straight to the seller and the order is complete
        insert('market_payments', ['request_id' => $id, 'payer_id' => $u['id'], 'payee_id' => $sellerId, 'amount' => $x['amount'], 'method' => $method, 'stage' => $x['stage'], 'status' => 'released', 'ref' => $ref, 'released_at' => now()]);
        order_complete($r, $u);
        flash('Paid AED ' . $x['amount'] . ' (receipt ' . $ref . '). The order is complete and the seller has been paid.');
    } else {
        insert('market_payments', ['request_id' => $id, 'payer_id' => $u['id'], 'payee_id' => $sellerId, 'amount' => $x['amount'], 'method' => $method, 'stage' => $x['stage'], 'status' => 'held', 'ref' => $ref]);
        update('service_requests', ['status' => 'accepted', 'decided_at' => $r['decided_at'] ?: now()], 'id = ?', [$id]);
        order_chat((int) $u['id'], $sellerId, '✅ I accepted your offer for “' . excerpt($r['title'], 60) . '” and paid AED ' . $x['amount'] . ' (held by MANBAR until delivery). Let’s start!');
        notify($sellerId, 'service_accepted', $u['full_name'] . ' accepted your offer and paid AED ' . $x['amount'] . '. You can start the work.', 'marketplace/' . $r['sid']);
        flash('Paid AED ' . $x['amount'] . ' (receipt ' . $ref . '). MANBAR holds it safely until you confirm delivery.');
    }
    redirect('marketplace/' . $r['sid']);
}

/** Buyer confirmed delivery: release held money, mark the order complete. */
function order_complete(array $r, array $buyer): void
{
    qexec("UPDATE market_payments SET status = 'released', released_at = ? WHERE request_id = ? AND status = 'held'", [now(), $r['id']]);
    update('service_requests', ['status' => 'completed', 'completed_at' => now()], 'id = ?', [$r['id']]);
    $sellerId = (int) $r['seller_id'];
    award_points($sellerId, 10, 'Delivered a service');
    refresh_reputation_score($sellerId);
    order_chat((int) $buyer['id'], $sellerId, '🎉 I confirmed delivery for “' . excerpt($r['title'], 60) . '”. Thank you!');
    notify($sellerId, 'service_completed', $buyer['full_name'] . ' confirmed delivery. Your payment is now in your wallet.', 'marketplace/earnings');
}

/* ---------- wallet ---------- */

function page_earnings(): void
{
    $u = require_login();
    ensure_pay_schema();
    $w = wallet((int) $u['id']);
    $tx = qall('SELECT p.*, s.title, s.id AS sid, b.full_name AS buyer FROM market_payments p JOIN service_requests r ON r.id = p.request_id JOIN services s ON s.id = r.service_id JOIN users b ON b.id = p.payer_id WHERE p.payee_id = ? ORDER BY p.id DESC LIMIT 50', [$u['id']]);
    $spent = qall('SELECT p.*, s.title, s.id AS sid, x.full_name AS seller FROM market_payments p JOIN service_requests r ON r.id = p.request_id JOIN services s ON s.id = r.service_id JOIN users x ON x.id = p.payee_id WHERE p.payer_id = ? ORDER BY p.id DESC LIMIT 50', [$u['id']]);
    $payouts = qall('SELECT * FROM market_payouts WHERE user_id = ? ORDER BY id DESC', [$u['id']]);
    render('earnings', compact('u', 'w', 'tx', 'spent', 'payouts'));
}

function earnings_payout(): void
{
    $u = require_login();
    $w = wallet((int) $u['id']);
    $amount = input_int('amount');
    $method = input('method') === 'paypal' ? 'paypal' : 'bank';
    $acct = preg_replace('/\s+/', '', input('account'));
    if ($amount < 10 || $amount > $w['available']) { flash('Choose an amount between AED 10 and your available balance (AED ' . $w['available'] . ').', 'error'); redirect('marketplace/earnings'); }
    if ($method === 'bank' && !preg_match('/^AE\d{21}$/i', $acct)) { flash('Enter a UAE IBAN: AE followed by 21 digits.', 'error'); redirect('marketplace/earnings'); }
    if ($method === 'paypal' && !filter_var($acct, FILTER_VALIDATE_EMAIL)) { flash('Enter the email of your PayPal account.', 'error'); redirect('marketplace/earnings'); }
    // only a masked hint is kept, never the full account number
    $hint = $method === 'bank' ? 'IBAN •••• ' . substr($acct, -4) : preg_replace('/^(.).*(@.*)$/', '$1•••$2', $acct);
    insert('market_payouts', ['user_id' => $u['id'], 'amount' => $amount, 'method' => $method, 'account_hint' => $hint, 'status' => 'sent']);
    flash('Cash-out of AED ' . $amount . ' sent to ' . $hint . '. Demo mode: no real transfer was made.');
    redirect('marketplace/earnings');
}

/** Small payment brand marks for checkout buttons (simple drawn badges, no external images). */
function pay_logo(string $name): string
{
    return match ($name) {
        'visa' => '<svg class="pl" viewBox="0 0 48 30" aria-label="Visa"><rect width="48" height="30" rx="5" fill="#1a1f71"/><text x="24" y="20" text-anchor="middle" font-family="Arial, sans-serif" font-size="13" font-weight="900" font-style="italic" fill="#fff">VISA</text></svg>',
        'mastercard' => '<svg class="pl" viewBox="0 0 48 30" aria-label="Mastercard"><rect width="48" height="30" rx="5" fill="#111"/><circle cx="19" cy="15" r="9" fill="#eb001b"/><circle cx="29" cy="15" r="9" fill="#f79e1b" fill-opacity=".9"/></svg>',
        'amex' => '<svg class="pl" viewBox="0 0 48 30" aria-label="American Express"><rect width="48" height="30" rx="5" fill="#2e77bc"/><text x="24" y="19" text-anchor="middle" font-family="Arial, sans-serif" font-size="10" font-weight="900" fill="#fff">AMEX</text></svg>',
        'applepay' => '<svg class="pl" viewBox="0 0 48 30" aria-label="Apple Pay"><rect width="48" height="30" rx="5" fill="#000"/><path d="M14 9.5c.7-.9 1.2-2 1-3.1-1 .1-2.1.7-2.8 1.5-.6.7-1.1 1.8-1 2.9 1.1.1 2.1-.5 2.8-1.3zm1 1.6c-1.5-.1-2.8.9-3.5.9s-1.8-.8-3-.8c-1.5 0-3 .9-3.8 2.3-1.6 2.8-.4 7 1.2 9.3.8 1.1 1.7 2.4 2.9 2.3 1.2 0 1.6-.7 3-.7s1.8.7 3 .7c1.3 0 2.1-1.1 2.8-2.3.9-1.3 1.3-2.5 1.3-2.6 0 0-2.5-1-2.5-3.8 0-2.4 2-3.5 2-3.6-1.1-1.6-2.8-1.8-3.4-1.8z" fill="#fff" transform="translate(2 1) scale(.82)"/><text x="31" y="20" text-anchor="middle" font-family="Arial, sans-serif" font-size="11" font-weight="700" fill="#fff">Pay</text></svg>',
        'paypal' => '<svg class="pl" viewBox="0 0 64 30" aria-label="PayPal"><rect width="64" height="30" rx="5" fill="#fff" stroke="#d6dbe3"/><text x="32" y="20" text-anchor="middle" font-family="Arial, sans-serif" font-size="13" font-weight="900" font-style="italic"><tspan fill="#003087">Pay</tspan><tspan fill="#009cde">Pal</tspan></text></svg>',
        'tabby' => '<svg class="pl" viewBox="0 0 56 30" aria-label="tabby"><rect width="56" height="30" rx="5" fill="#3effc1"/><text x="28" y="20" text-anchor="middle" font-family="Arial, sans-serif" font-size="14" font-weight="900" fill="#111">tabby</text></svg>',
        'cash' => '<svg class="pl" viewBox="0 0 48 30" aria-label="Cash"><rect width="48" height="30" rx="5" fill="#2f9e5f"/><rect x="8" y="8" width="32" height="14" rx="3" fill="none" stroke="#fff" stroke-width="2"/><circle cx="24" cy="15" r="3.5" fill="#fff"/></svg>',
        default => '',
    };
}
function pay_logos_row(): string { return '<span class="pl-row">' . pay_logo('visa') . pay_logo('mastercard') . pay_logo('applepay') . pay_logo('paypal') . pay_logo('tabby') . '</span>'; }

/** "Dr. Layla Hassan" → "Layla": the first name, skipping titles. */
function first_name(string $full): string
{
    $parts = preg_split('/\s+/', trim($full)) ?: [''];
    while (count($parts) > 1 && preg_match('/^(dr|prof|mr|mrs|ms|eng|sheikh)\.?$/i', $parts[0])) array_shift($parts);
    return $parts[0];
}
