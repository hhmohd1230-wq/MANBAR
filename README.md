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
| **Marketplace** | List services (AED or free) · requests · accept / deliver · ratings & reviews · teachers can market tutoring |
| **Learning center** | Teachers publish courses & lessons (YouTube supported); students enroll and track progress |
| **Mentorship** | Mentor directory · requests · scheduling · feedback |
| **Achievements** | Reputation XP, 24 badges, 8 ranks, verified recommendations, leaderboard |
| **Messages & notifications** | Direct messages, live badges |
| **MANBAR Assistant** | Drafts and improves writing across feed posts, projects, marketplace, learning, mentorship, profiles, chat and comments; previews every important before → after change; then recommends direct links to matching community posts, open projects, people, mentors, courses and services based on the student’s intent and skills (works locally; optional Gemini, Groq, OpenAI or Claude language review) |
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
   git clone https://github.com/oblithub/obliterate.git manbar
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

## 4 · Optional: enhanced language AI (Gemini, Groq, OpenAI or Claude)

The assistant’s writing checks, drafting, idea routing and MANBAR catalogue search all work without any key. Search is performed by MANBAR’s PHP backend against visible records; database credentials are never sent to a model. For strong contextual grammar, spelling, vocabulary and form drafting, set `GEMINI_API_KEY` and optionally `GEMINI_MODEL` (the default is `gemini-3.5-flash-lite`). You may also configure the free-tier Groq fallback with `GROQ_API_KEY` / `GROQ_MODEL`, or configure `OPENAI_API_KEY` / `OPENAI_MODEL` and `ANTHROPIC_API_KEY` / `ANTHROPIC_MODEL`. MANBAR tries Gemini, Groq, OpenAI, Claude, and finally its local engine. Explicit writing actions support natural, professional, academic, friendly and concise tones and display a consistent review card containing the polished text, important before → after corrections, short reasons, the engine used, and Apply / Keep controls. After the writing review, the same card can show live, ranked destinations with direct links to relevant posts, projects, people, mentors, courses and services. Skill statements such as “I’m good at frontend development,” “I can help,” and “I want to contribute” are treated as discovery intent even without the word “search.” This review is available across feed posts, project and marketplace forms, courses and lessons, mentorship, profiles, direct messages, comments and replies. Recommendations include transparent relative-fit labels rather than fake probability percentages. Automatic checks while typing stay local; only text submitted with an explicit AI action is sent to a configured provider. API keys must remain server-side and must never be committed or included in browser JavaScript.

## 5 · Publish on GitHub / move to a real server

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
