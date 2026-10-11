# MANBAR · منبر — the student platform

A campus platform where students share ideas, build projects, offer services, learn and find mentors — starting with **Al Ain University (AAU)**, designed to grow to other universities and then go public.
Capstone project, College of Engineering, Al Ain University (Yaman AlNasri, Tamim Alzein, Ghaith Alsalim, Muhammad Toufeeq, Rami Albaini).

![status](https://img.shields.io/badge/PHP-8.1%2B-777bb4) ![db](https://img.shields.io/badge/MySQL%20%2F%20MariaDB-XAMPP-4479a1)

## What is inside

| Module (from the report) | What students can do |
|---|---|
| **Idea feed** | Post ideas, questions, resources, events, showcases · react (5 reactions) · threaded comments · share · save · hashtags · photos |
| **Profiles** | Portfolio profile, skills, interests, links, badges, level; followers/following |
| **Google sign-in (@aau.ac.ae)** | Only university accounts. A student number such as `202020280@aau.ac.ae` is matched with the **roster** and pre-fills name, major and year |
| **Projects** | Start from an idea · apply / accept · team · task board (To do / Doing / Done) · team chat · public updates · outcome |
| **Marketplace** | Services (one price or Basic / Standard / Premium packages) and “Looking for people” posts with a number of spots · cover pictures · portfolio of files, whole folders and links, with “past work” proof · request / apply → accept → deliver → review · trust score from real orders, on-time delivery, response time, reviews, repeat clients and teammate vouches. Portfolio files live in `storage/service_files/` and are shown only to signed-in members |
| **Learning center** | Teachers publish courses & lessons (YouTube supported); students enroll and track progress |
| **Mentorship** | Mentor directory · requests · scheduling · feedback |
| **Achievements** | Reputation XP, 24 badges, 8 ranks, verified recommendations, leaderboard |
| **Messages & notifications** | Direct messages, live badges |
| **MANBAR Assistant** | Drafts and improves writing across feed posts, projects, marketplace, learning, mentorship, profiles, chat and comments; previews every important before → after change; then recommends direct links to matching community posts, open projects, people, mentors, courses and services based on the student’s intent and skills (works locally; optional Gemini, Groq, OpenAI or Anthropic language review) |
| **Admin dashboard** | Analytics charts, users (roles, suspend, verify, CSV export), **roster CSV import**, moderation & reports, content control, **universities** (switch ADU / UAEU on later), settings, audit log |

Roles: `student`, `teacher` (extra tools: announcements, teaching offers, courses, mentor profile) and `admin`.
Design: light-green theme, SVG logo/backgrounds, responsive (desktop / tablet / phone).

All report figures (original + new diagrams + screenshots) are in [`docs/figures/index.html`](docs/figures/index.html) — see [`docs/REPORT_FIGURES.md`](docs/REPORT_FIGURES.md).

---

## 1 · Run it on your PC with XAMPP + VS Code

1. Install **XAMPP** (PHP 8.1+; 8.3 recommended) and **VS Code** (+ the *PHP Intelephense* extension).
2. Clone the repo into XAMPP's web folder:
   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/hhmohd1230-wq/MANBAR.git manbar
   code manbar
   ```
3. Start **Apache** and **MySQL** in the XAMPP Control Panel.
4. Create the database — pick one:
   - **phpMyAdmin:** open `http://localhost/phpmyadmin` → *Import* → choose `database/manbar.sql`.
   - **Command line:** `C:\xampp\php\php.exe database\install.php --demo` (add `--fresh` to wipe and rebuild).
   `--demo` adds sample students, teachers, posts, projects, courses… so the site looks alive. Skip it for a clean database.
5. Open **http://localhost/manbar/public/**
   (or without Apache: run the VS Code task *MANBAR: start dev server* → http://localhost:8000).
6. Sign in. Until Google is configured, the login page shows a **demo login** — type `202020280` (you'll be matched with the roster and become admin), or click a demo account.

> If your MySQL root has a password, copy `config/config.local.sample.php` to `config/config.local.php` and edit it (that file is git-ignored).

## 2 · Turn on Google sign-in

1. Go to <https://console.cloud.google.com> → create a project → *Google Auth Platform* / *OAuth consent screen* → **External** (use **Internal** only if you administer the university's Google Workspace). Scopes: just `openid`, `email`, `profile` (no Google review needed). While the app is in *Testing*, add the accounts that may sign in under *Test users*, or press *Publish app*.
2. *Clients* / *Credentials* → **Create OAuth client ID** → *Web application*.
3. **Authorised JavaScript origins:** `http://localhost` (and your real domain later, with `https://`).
   **Authorised redirect URIs:** `http://localhost/manbar/public/auth/google/callback`
4. Paste the Client ID into `config/config.local.php` (`'google_client_id' => '…'`) or set the `GOOGLE_CLIENT_ID` environment variable. No client secret is needed.
5. Set `'dev_login' => false` before going live.

How sign-in works:
- **Continue with Google**, or Chrome's own *Sign in with Google* bubble (One Tap), uses the Google accounts already signed in to the browser.
- **Type your university email**: type a student number (`202020280`; `@aau.ac.ae` is added for you) or a full address (`saqib.iqbal@aau.ac.ae`). MANBAR emails a **6-digit code** (valid 10 minutes, 5 tries, one new code per minute). After the first code sign-in, people can **set a password**; from then on, typing their email asks for the password instead, and *Forgot password?* sends a code to reset it.
- **Email setup** (needed for codes): fill the `mail` block in `config/config.local.php`. With Gmail: turn on 2-Step Verification, create an *App password* at <https://myaccount.google.com/apppasswords>, then use host `smtp.gmail.com`, port `587`, secure `tls`, your Gmail address as `user` and `from`, and the 16-letter app password as `pass`. Until it is filled in (and while `dev_login` is on), codes are shown on screen and saved to `storage/mail.log` for testing.
- First sign-in creates the account. A **student number** address becomes a *student*, and the roster fills in name, major, faculty and year. A **name** address (`name.surname@…`) becomes a *teacher* with a mentor profile. Admins can change roles in *Admin → Users*.

**Admin console login:** set `admin_login` in `config/config.local.php` to a `username` plus a `password_hash` (make one with `C:\xampp\php\php.exe -r "echo password_hash('your password', PASSWORD_DEFAULT);"`). Typing that username on the sign-in page asks for the admin password and opens the dashboard. The account uses an internal address, so it can't be reached with an email code, and 5 wrong passwords lock it for 15 minutes. The old demo login is off (`dev_login => false`); keep it that way.

MANBAR verifies the Google ID token on the server (audience, issuer, expiry, verified e-mail, plus a one-time `state` and `nonce` against replay), and only accepts domains of **active universities**.

## 3 · Adding more universities, then going public

- Admin → **Universities**: add *Abu Dhabi University* (`adu.ac.ae`), *UAE University* (`uaeu.ac.ae`)… and press **Enable sign-in**. The roster is per university.
- Admin → **Student roster**: import each university's list as CSV (`student_id, full_name, major, faculty, year_level`).
- Going fully public later = allow any verified Google account; the code path is `university_for_email()` in `app/auth.php`.

## 4 · Optional: enhanced language AI (Gemini, Groq, OpenAI or Anthropic)

The assistant’s writing checks, drafting, idea routing and MANBAR catalogue search all work without any key. Search is performed by MANBAR’s PHP backend against visible records; database credentials are never sent to a model. For strong contextual grammar, spelling, vocabulary and form drafting, set `GEMINI_API_KEY` and optionally `GEMINI_MODEL` (the default is `gemini-3.5-flash-lite`). You may also configure the free-tier Groq fallback with `GROQ_API_KEY` / `GROQ_MODEL`, or configure `OPENAI_API_KEY` / `OPENAI_MODEL` and `ANTHROPIC_API_KEY` / `ANTHROPIC_MODEL`. MANBAR tries Gemini, Groq, OpenAI, Anthropic, and finally its local engine. Explicit writing actions support natural, professional, academic, friendly and concise tones and display a consistent review card containing the polished text, important before → after corrections, short reasons, the engine used, and Apply / Keep controls. After the writing review, the same card can show live, ranked destinations with direct links to relevant posts, projects, people, mentors, courses and services. Skill statements such as “I’m good at frontend development,” “I can help,” and “I want to contribute” are treated as discovery intent even without the word “search.” This review is available across feed posts, project and marketplace forms, courses and lessons, mentorship, profiles, direct messages, comments and replies. Recommendations include transparent relative-fit labels rather than fake probability percentages. Automatic checks while typing stay local; only text submitted with an explicit AI action is sent to a configured provider. API keys must remain server-side and must never be committed or included in browser JavaScript.

## 5 · Learning center: courses, quizzes, live quizzes, paid courses

**Who can create courses:** teachers and admins, plus members who reached the **Gold rank** (reputation 900+).

**Course content:** lessons with a YouTube, Vimeo or any video link, and **summaries** (short key-point pages). Files (PDF, Word, PowerPoint, Excel, text, images, ZIP; up to 25 MB each, several at once) and online links can be added to the whole course, a lesson or a summary. Images show as a gallery with a full-screen viewer, PDFs preview inside the page, YouTube/Vimeo links play on the page. Files are stored in `storage/course_files/` (outside the public folder) and served only to the host and enrolled students. Hosts can add a **cover picture** (*Host tools → Cover picture*), shown on the course card and at the top of the course.

**Course path:** the host drags lessons, summaries and quizzes into order. Students press *Start course* and go step by step (each step opens when the one before is done), then can *Start again* as often as they like. Classmates (other students of the course) are shown on the course page.

**Demo classmates:** to remove the seeded demo students from a course (enrollments, progress, quiz attempts, visits): `php database/remove_course_demo.php <course id>`.

**Quizzes** (host tools → *New quiz*):
- Multiple-choice and essay questions, points per question, explanations, time limit, pass mark, attempts and shuffle.
- **Add many at once:** paste text or upload a `.docx` / `.txt` file, check the preview, then save. The format:
  ```text
  1. What does HTML stand for?
  A) Hyper Trainer Marking Language
  B) Hyper Text Markup Language *
  2. Which tag makes the biggest heading? (2 pts)
  a) <h6>
  b) <h1>
  Answer: B
  Essay: Explain margin vs padding. (5 marks)
  Keywords: margin, padding, border
  ```
  Mark the right option with `*` or `(correct)`, or add an `Answer:` line. A question with no options becomes an essay. Word files with automatic numbering work too.
- **Self-paced:** one question per screen, answers autosave, and a countdown submits automatically at zero. Multiple choice is marked instantly. Essays with keywords are auto-marked (the host can change the mark); other essays wait for the host, who marks them with feedback, and the student is notified.
- **Live** (*Start live quiz*): the host shows a 6-digit code and students join at `/live`. Everyone answers the same question at once; correct answers score 500 plus up to 500 for speed. The host sees the live leaderboard and the final podium.

**Host dashboard** (`/learn/{id}/dashboard`): students, visits (14 days), lesson progress, quiz averages, pass rates, the hardest questions, essays waiting for marking, live-session history, payments, settings, *Give access* and CSV export.

**Paid courses** (price in AED): students pay by card through **Stripe Checkout**.
1. Create a Stripe account and copy the **secret key** (start with test mode: `sk_test_…`).
2. Put it in `config/config.local.php`: `'stripe' => ['secret_key' => 'sk_test_…', 'webhook_secret' => ''],`
3. Test with card `4242 4242 4242 4242`, any future date, any CVC.
4. Optional, on a public server: add a webhook to `https://your-site/stripe/webhook` for `checkout.session.completed` and paste its `whsec_…` secret as `webhook_secret`.

**Checkout (courses and marketplace):** `/checkout/course/{id}` and `/checkout/order/{id}` offer card (Visa, Mastercard, Amex), Apple Pay, PayPal and tabby (pay in 4). Card payments for courses use Stripe when a key is set; every other method runs in clearly labelled **demo mode** (nothing is charged, card details never leave the browser) until its provider is connected. Course hosts and admins previewing a paid course see the full checkout, but nothing is charged or saved.

**Marketplace orders (person to person):** request → chat in Messages → the seller sends an offer (price, delivery, and when to pay: before, 50/50, after delivery, or cash) → the buyer accepts and pays if due (online payments are held by MANBAR) → the seller marks it delivered → the buyer confirms (paying the rest if due) → the money is released to the seller’s **wallet** (`/marketplace/earnings`), where they can cash out to an IBAN or PayPal (demo; only the last 4 digits are kept). Admins browse the marketplace in student view by default and switch on *Admin tools* to manage any listing.

### Notifications and Settings

- **Bell pop-up:** the bell in the top bar opens the latest notifications (All / New, grouped by day, *Mark all read*). *View all notifications* opens `/notifications`, which filters by category (Social, Projects, Marketplace, Learning, Badges…).
- **Settings** (`/settings`): Account, Notifications (turn categories on or off; `notify()` skips muted ones), Billing & payments and Appearance.
- **Billing:** saved cards, PayPal and tabby appear as one-tap options at checkout. Only the brand, last 4 digits, expiry and a masked hint are stored (`billing_methods`), never full card numbers or CVCs.

### Admin tools

- **User control panel** (`/admin/users/{id}`, the *Manage* button in Users):
  - ban for 24 hours, 3, 7 or 30 days, or permanently, with a reason the person sees when signing in (timed bans lift themselves);
  - send warnings;
  - add, remove or set Rank XP (logged in `point_log` as "Admin: …");
  - give or take badges;
  - hide or delete posts, with an optional reason sent to the author.
  - Every action goes to a per-user history (`mod_actions`) and the audit log.
- **Scam reports:**
  - Members use *Report a scam* on listings and profiles, or pick "Scam or fraud" in the normal report box.
  - Each report opens a case at `/admin/cases/{id}` showing the order, the held money and the seller's history.
  - From the case you can dismiss it, warn the seller, hide their listings, ban them, and refund money still on hold.
- **Moderation** has tabs for Scam cases, Content reports and All posts (search, hide, delete).

## 6 · Publish on GitHub / move to a real server

```bash
git add -A && git commit -m "..." && git push
```
On a server: PHP 8.1+, MySQL/MariaDB, Apache (`mod_rewrite` on) or Nginx. Prefer setting the document root to `public/`. For shared hosting with a fixed `public_html` root, deploy the whole repository into `public_html`; the root `index.php` and `.htaccess` safely expose only the public assets and front controller.

Then import `database/manbar.sql` (or run `php database/install.php`), create a server-only `config/config.local.php`, set `env` to `production`, keep `dev_login` and `show_codes_on_screen` false, add the production database/mail/Google settings, and serve over **HTTPS**. Make `public/uploads/` and `storage/` writable by PHP. Never commit `config/config.local.php`.

## Project layout

```
app/            PHP application (routes.php, controllers/, views/, auth, AI, gamification)
public/         web root: index.php (front controller), assets/ (css, js, svg), uploads/
database/       schema.tpl.sql, install.php, manbar.sql (phpMyAdmin), demo.php
config/         config.php (+ config.local.php, git-ignored)
docs/           report figures (SVG/PNG/gallery) and the generator scripts
```

## Security notes
CSRF tokens on every POST · prepared statements everywhere · output escaping · role checks on every admin/teacher route · image uploads validated by MIME type and stored without script execution · sessions are HttpOnly / SameSite · Google tokens are verified server-side.
