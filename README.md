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
| **Achievements** | Points, 13 badges, 6 levels, leaderboard |
| **Messages & notifications** | Direct messages, live badges |
| **AI assistant** | Auto-corrects spelling/grammar before posting and **routes your idea to the right page** (works offline; optional Claude API) |
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

1. Go to <https://console.cloud.google.com> → create a project → *APIs & Services* → *OAuth consent screen* (Internal if you have a Google Workspace, otherwise External).
2. *Credentials* → *Create credentials* → **OAuth client ID** → *Web application*.
3. **Authorised JavaScript origins:** `http://localhost` (and your real domain later, with `https://`).
   **Authorised redirect URIs:** `http://localhost/manbar/public/auth/google`
4. Put the Client ID in `config/config.local.php` (`'google_client_id' => '…'`) or set the `GOOGLE_CLIENT_ID` environment variable.
5. Set `'dev_login' => false` before going live.

MANBAR verifies the Google token on the server, requires a verified e-mail, and only accepts domains of **active universities**.

## 3 · Adding more universities, then going public

- Admin → **Universities**: add *Abu Dhabi University* (`adu.ac.ae`), *UAE University* (`uaeu.ac.ae`)… and press **Enable sign-in**. The roster is per university.
- Admin → **Student roster**: import each university's list as CSV (`student_id, full_name, major, faculty, year_level`).
- Going fully public later = allow any verified Google account; the code path is `university_for_email()` in `app/auth.php`.

## 4 · Optional: real AI (Claude)

The assistant works without any key. To let Claude do the correcting and routing, set `ANTHROPIC_API_KEY` (and optionally `ANTHROPIC_MODEL`) — if the call fails it falls back to the built-in rules automatically.

## 5 · Publish on GitHub / move to a real server

```bash
git add -A && git commit -m "..." && git push
```
On a server: PHP 8.1+, MySQL/MariaDB, Apache (document root = `public/`, `mod_rewrite` on) or Nginx (`try_files $uri /index.php?$query_string;`). Then `php database/install.php`, set `MANBAR_ENV=production`, `dev_login=false`, DB credentials and the Google client ID in `config/config.local.php` / environment, and serve over **HTTPS**.

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
