# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users
Verified university students at Al Ain University (AAU), plus teachers and admins. Students arrive wanting to share an idea, find teammates for a project, sell or buy a skill-based service, learn, or find a mentor. Teachers publish courses, announcements and mentoring offers. Admins manage the roster, moderation and which universities can sign in.

## Product Purpose
MANBAR (منبر, "the stage / pulpit") is a campus-only platform where an idea becomes a project, a team, a service or a portfolio entry. Success: students post ideas, form teams, ship projects and build a verifiable portfolio inside their university community.

## Positioning
It brings together the pieces that LinkedIn, Fiverr, Discord and Moodle each cover partly (idea sharing, project teams, a student marketplace, mentorship, courses, achievements) behind university-only Google sign-in (@aau.ac.ae), with the student number matched to the official roster.

## Operating Context
Students sign in with their university Google account; the student number pre-fills name, major and year from the roster. The platform starts at AAU and is designed to add Abu Dhabi University and UAE University later, then go public. Capstone project, College of Engineering, AAU (Yaman AlNasri, Tamim Alzein, Ghaith Alsalim, Muhammad Toufeeq, Rami Albaini).

## Capabilities and Constraints
- Modules: idea feed (5 reactions, threaded comments, hashtags, photos), profiles and portfolios, projects (apply/accept, task board, team chat, updates), marketplace (AED or free services, requests, ratings), learning center (courses and lessons, YouTube), mentorship (directory, requests, scheduling), achievements (points, 13 badges, 6 levels, leaderboard), messages and notifications, MANBAR Assistant (visible project/service/course draft generation, local spelling, grammar and vocabulary suggestions across writing fields, idea routing, and profile-aware search across visible projects, people, mentors, courses and services; optional Claude language enhancement), admin dashboard.
- Roles: student, teacher, admin.
- Stack: PHP 8.1+ with no framework, MySQL/MariaDB, served by Apache on XAMPP at http://localhost/manbar/public/. No build step; assets are plain CSS/JS/SVG under public/assets. The user chose to keep this stack for the cinematic landing redesign (no Next.js).
- The landing page shows live counts from the database (students and teachers, posts, projects, courses).

## Brand Commitments
- Name: MANBAR / منبر. Logo (Oct 2026): green rounded tile, white M with rounded arches, two-leaf sprout above the M's V, a microphone under it, a wide stage bar. Files: public/assets/img/logo.svg, favicon.svg, logo-full.svg.
- Bilingual identity: English UI with Arabic name shown alongside.
- Landing + sign-in redesign brief (from the user): opens deep black with vibrant lime; scrolling morphs into MANBAR's green campus world. It must read as a real, professionally designed product, not a generic AI page, and feel lively ("hyped").
  - Intro (every landing load): the logo draws itself, modelled on the user's brand film: outline trace as the load counter, M/mic/stage strokes, leaves pop, gradient fill with glass sheen, sound waves and an orbiting light. It lands on the hero logo.
  - Hero: MANBAR logo + wordmark, with "The stage for every student idea." smaller underneath.
  - Background: drifting green leaves and alphabet letters (Latin and Arabic) for an eLearning feel; the sign-in page adds math equations moving left to right as motion lines.
  - Glass laptop and phone mockups tilt with the mouse and show realistic MANBAR content. Demo UI (journey card, idea-feed tile) plays like a screen recording with typing and a demo cursor (visual only). Compare-table dots animate.
  - Hero text and devices start out of focus and settle sharp like a camera lens.

## Evidence on Hand
- Real platform features (see README.md) and live database counts.
- Demo content in database/demo.php (sample students, posts, projects, services, courses).
- Report figures and screenshots in docs/figures/.
- No real testimonials, press, partner logos, user numbers beyond the live counts, or pricing. Do not invent them.

## Product Principles
1. Campus trust first: only verified university members, and that is the core promise.
2. From idea to outcome: every surface should move an idea toward a team, project or portfolio.
3. Honest proof: show real platform content and real counts, never made-up social proof.
4. Built by students, for students: confident and modern, never corporate.
