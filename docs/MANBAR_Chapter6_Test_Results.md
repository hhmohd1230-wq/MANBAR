# Chapter 6: System Testing — MANBAR

> Evidence-based test report for the MANBAR Capstone 2 project at Al Ain University. A **Pass** means the stated expected behavior was observed in the isolated environment. It does not mean that the entire module is defect-free.

## 6.1 Test objective and scope

The purpose of this test cycle was to verify MANBAR's functional behavior, boundary handling, authorization controls, integration between modules, file validation, limited local performance, and browser rendering. The assessment used the source code, routes, controllers, validation logic and live isolated database behavior. No result was marked Pass from assumption alone.

The executed scope covered university authentication and verification codes; feed posts; projects, applications, tasks, milestones and vouches; marketplace services, orders and demonstration payments; learning, enrollment, courses, quizzes and live quizzes; mentors; messages and attachments; notifications; profiles and settings; AI fallback; administrator controls; roster import; moderation; uploads; source integrity; routing; database structure; browser smoke tests; and original-database isolation.

## 6.2 Safe isolated test environment

| Item | Value |
| --- | --- |
| Original source | `C:\xampp\htdocs\manbar` |
| Isolated source copy | `C:\xampp\htdocs\manbar_ch6_test_20261011` |
| Original database | `manbar` (read-only during test execution) |
| Test database | `manbar_ch6_test_20261011` |
| Test server | `http://127.0.0.1:8765` using PHP 8.1.25 built-in server |
| Fictional students | `202699001@aau.ac.ae`, `202699002@aau.ac.ae`, `202699003@aau.ac.ae` |
| External services | Google, SMTP, Stripe and remote AI provider credentials blank; no external calls |
| Payment mode | Internal demonstration flow only; no real payment |
| Pre-test backup | `C:\xampp\htdocs\manbar\storage\chapter6-evidence-20261011\original-manbar-before-ch6-tests.sql` — SHA-256 `216398E43A1A22BF1EF9AECDAD5FB3C49F1EF887B1B2609BEBFF1117D386A476` |
| Post-test verification | Normalized dumps matched SHA-256 `9E046559FBC34498BDAB2E93C8A4B0985F1286070595D2959DB46A494A3A106F`; original database unchanged |

The source sandbox was configured with its own database and session directory. Test email codes were written only to the sandbox `storage/mail.log`; no message was sent to a real or fictional university mailbox. Randomized test uploads were written only under the sandbox.

## 6.3 Methods

- **Equivalence Class Partitioning (ECP):** valid/invalid domains, known/unknown post types, allowed/disallowed upload types, authorized/unauthorized roles, and valid/duplicate operations.
- **Boundary Value Analysis (BVA):** values immediately below, at, and above limits for titles, descriptions, team sizes, price, upload size, code length and quiz settings/attempts.
- **Functional and integration testing:** real HTTP requests plus database assertions across connected workflows.
- **Security testing:** CSRF, authorization, output encoding, MIME verification, rate limiting, administrative separation and unauthenticated attachment access.
- **Performance testing:** 30 safe sequential authenticated GET requests; descriptive timing only.
- **Browser compatibility:** public login smoke rendering in installed Chrome and Edge, with screenshots.
- **Static verification:** PHP/JavaScript syntax, route-to-handler mapping and schema constraints.

## 6.4 Source-derived validation boundaries

| Field | Implemented rule | Source evidence | Executed cases |
| --- | --- | --- | --- |
| Verification code | Exactly 6 digits; maximum 5 wrong tries; code expires after 10 minutes | `app/auth.php:179-190` | AUTH-003 to AUTH-006 |
| Post title/body/tags | Title minimum 3 and maximum stored 200; body minimum 10 and maximum stored 5,000; maximum 6 tags | `app/controllers/feed.php:165-179` | FEED-001 to FEED-003 |
| Image upload | Maximum 4 MB; detected MIME must be JPEG/PNG/WebP/GIF | `config/config.php:71; app/controllers/feed.php:142-155` | FILE-001, FILE-001A, FILE-001B |
| Project | Title 3-200; description 15-5,000; team size clamped to 2-30; maximum 10 skills | `app/controllers/projects.php:123-142` | PROJ-001 to PROJ-003 |
| Vouch | Completed project; no self-vouch; body minimum 30; rating clamped 1-5; maximum 3 traits | `app/controllers/projects.php:266-291` | VOUCH-001, VOUCH-002 |
| Marketplace service | Title minimum 5; description minimum 20; price 0-100,000; delivery 1-90; slots 1-50; revisions 0-20 | `app/controllers/market.php:237-260, 287-305` | MKT-001, MKT-002 |
| Course | Title minimum 5; description minimum 20; price 0-9,999; positive price below AED 2 raised to AED 2 | `app/controllers/learn.php:211-235` | LEARN-001, LEARN-002 |
| Quiz settings | Time 0-300 min; live 5-120 sec; pass 0-100%; attempts 0-20; question points 1-100 | `app/controllers/quiz.php:34-48, 55-79` | QUIZ-001 to QUIZ-006 |
| Message attachment | 1 byte through 12 MB; extension/MIME allowlist; images, audio, PDF, Office, text/CSV, ZIP | `app/controllers/social.php:48-93` | FILE-002 to FILE-005, SEC-007 |
| Course/service material | Maximum 25 MB, extension and MIME rules, stored outside public web root | `app/learning.php:317-335` | Static inspection; proposed full boundary execution |

## 6.5 Results summary

| Result group | Executed | Passed | Failed | Pass rate |
| --- | ---: | ---: | ---: | ---: |
| Functional HTTP/database suite | 67 | 67 | 0 | 100.00% |
| Independent static/browser/security checks | 7 | 6 | 1 | 85.71% |
| **Total** | **74** | **73** | **1** | **98.65%** |

The single failed test is a confirmed authorization/privacy defect in private-message attachment delivery. The 67-case functional suite itself completed with 67 passes after the test correctly followed MANBAR's enforced course-step sequence.

## 6.6 Executed test cases

| Test Case ID | Module | Input Values | Expected Output | Actual Output | Result | Evidence | Method |
| --- | --- | --- | --- | --- | --- | --- | --- |
| AUTH-001 | University authentication | GET /login | HTTP 200 and AAU sign-in page | HTTP 200; AAU domain visible=True | Pass | HTTP 200 http://127.0.0.1:8765/login; body: Sign in · MANBAR MANBAR منبر The stage for every student idea. One trusted place for ideas, teams, services, courses and mentors at your university. Ideas Projects Marketplace Courses Mentors Al Ain University · Capstone project Sign in or join Use your univer | Automated HTTP + database verification |
| AUTH-002 | University authentication | outsider@gmail.com | Reject non-university address; no user created | HTTP 200; validation shown; users 0->0 | Pass | redirects 302:/login; HTTP 200 http://127.0.0.1:8765/login; body: Sign in · MANBAR MANBAR منبر The stage for every student idea. One trusted place for ideas, teams, services, courses and mentors at your university. Ideas Projects Marketplace Courses Mentors Al Ain University · Capstone project Sign in or join Use your univer | Automated HTTP + database verification |
| SEC-001 | Security / CSRF | POST /auth/dev without _csrf | HTTP 419; no authentication | HTTP 419; {"ok":false,"error":"Session expired. Refresh the page."} | Pass | HTTP 419 http://127.0.0.1:8765/auth/dev; body: {"ok":false,"error":"Session expired. Refresh the page."} | Automated HTTP + database verification |
| AUTH-003 | University authentication | 202699003@aau.ac.ae | Create one locally logged six-digit code; no email sent | HTTP 200; local code length=6; login_codes=1; SMTP disabled | Pass | redirects 302:/auth/email/verify; HTTP 200 http://127.0.0.1:8765/auth/email/verify; body: Check your email · MANBAR MANBAR منبر The stage for every student idea. One trusted place for ideas, teams, services, courses and mentors at your university. Ideas Projects Marketplace Courses Mentors Al Ain University · Capstone project Check your email We se | Automated HTTP + database verification |
| AUTH-004 | University authentication | 5-digit code 12345 | Reject; attempts becomes 1 | HTTP 200; attempts=1; rejection shown | Pass | redirects 302:/auth/email/verify; HTTP 200 http://127.0.0.1:8765/auth/email/verify; body: Check your email · MANBAR MANBAR منبر The stage for every student idea. One trusted place for ideas, teams, services, courses and mentors at your university. Ideas Projects Marketplace Courses Mentors Al Ain University · Capstone project Check your email We se | Automated HTTP + database verification |
| AUTH-005 | University authentication | 7-digit code 1234567 | Reject; attempts becomes 2 | HTTP 200; attempts=2; rejection shown | Pass | redirects 302:/auth/email/verify; HTTP 200 http://127.0.0.1:8765/auth/email/verify; body: Check your email · MANBAR MANBAR منبر The stage for every student idea. One trusted place for ideas, teams, services, courses and mentors at your university. Ideas Projects Marketplace Courses Mentors Al Ain University · Capstone project Check your email We se | Automated HTTP + database verification |
| AUTH-006 | University authentication | Correct locally logged 6-digit code | Sign in, create roster-matched verified user, mark code used | HTTP 200; user=['Mariam Test Student', '1', '1']; used_codes=1 | Pass | redirects 302:/feed; HTTP 200 http://127.0.0.1:8765/feed; body: Home · MANBAR MAN BAR منبر · AAU students Home feed Projects Marketplace Learning center Mentors People You My profile Achievements Saved Messages Settings Rank 1 View Bronze ★ ★ ★ Star 1 of 3 25 XP 125 XP to Bronze ★★ New post 1 Notifications Your most recent | Automated HTTP + database verification |
| FEED-001 | Community Feed | title length 2; body length 9 | Reject; no row inserted | HTTP 200; posts 0->0; validation shown | Pass | redirects 302:/feed?compose=idea; HTTP 200 http://127.0.0.1:8765/feed?compose=idea; body: Home · MANBAR MAN BAR منبر · AAU students Home feed Projects Marketplace Learning center Mentors People You My profile Achievements Saved Messages Settings Rank 1 View Bronze ★ ★ ★ Star 1 of 3 25 XP 125 XP to Bronze ★★ New post 1 Notifications Your most recent | Automated HTTP + database verification |
| FEED-002 | Community Feed | title length 3; body length 10 | Create visible post at minimum boundary | HTTP 302; post_id=1; lengths/tags/user=['3', '10', 'testing,boundary', '2'] | Pass | HTTP 302 http://127.0.0.1:8765/post/create; body: | Automated HTTP + database verification |
| FEED-003 | Community Feed | title 201; body 5001; 7 tags | Create after truncating to 200/5000 and six tags | HTTP 302; post_id=2; stored title/body lengths=['200', '5000']; tags=one,two,three,four,five,six | Pass | HTTP 302 http://127.0.0.1:8765/post/create; body: | Automated HTTP + database verification |
| FEED-004 | Community Feed | type=malicious | HTTP 422 Unknown post type | HTTP 422; Error · MANBAR MAN BAR منبر · AAU students Home feed Projects Marketplace Learning center Mentors People You My profile Achievements Saved Messages Settings Rank 1 View Bronze ★ ★ ★ Star 1 of 3 45 XP 105 XP to Bronze ★★ New post 1 Notifications Your most recen | Pass | HTTP 422 http://127.0.0.1:8765/post/create; body: Error · MANBAR MAN BAR منبر · AAU students Home feed Projects Marketplace Learning center Mentors People You My profile Achievements Saved Messages Settings Rank 1 View Bronze ★ ★ ★ Star 1 of 3 45 XP 105 XP to Bronze ★★ New post 1 Notifications Your most recen | Automated HTTP + database verification |
| FEED-005 | Community Feed | student type=announcement | HTTP 403 | HTTP 403; No access · MANBAR MAN BAR منبر · AAU students Home feed Projects Marketplace Learning center Mentors People You My profile Achievements Saved Messages Settings Rank 1 View Bronze ★ ★ ★ Star 1 of 3 45 XP 105 XP to Bronze ★★ New post 1 Notifications Your most r | Pass | HTTP 403 http://127.0.0.1:8765/post/create; body: No access · MANBAR MAN BAR منبر · AAU students Home feed Projects Marketplace Learning center Mentors People You My profile Achievements Saved Messages Settings Rank 1 View Bronze ★ ★ ★ Star 1 of 3 45 XP 105 XP to Bronze ★★ New post 1 Notifications Your most r | Automated HTTP + database verification |
| SEC-002 | Feed output encoding | title contains <script>alert(1)</script> | Render escaped text; do not execute raw script | HTTP 200; raw_script=False; escaped_script=True | Pass | HTTP 200 http://127.0.0.1:8765/post/3; body: XSS <script>alert(1)</script> · MANBAR MAN BAR منبر · AAU students Home feed Projects Marketplace Learning center Mentors People You My profile Achievements Saved Messages Settings Rank 1 View Bronze ★ ★ ★ Star 1 of 3 55 XP 95 XP to Bronze ★★ New post 1 Notifi | Automated HTTP + database verification |
| FILE-001 | File upload validation | fake.jpg containing plain text | Reject MIME/extension mismatch; no post | HTTP 200; posts 3->3; MIME validation shown | Pass | redirects 302:/feed?compose=idea; HTTP 200 http://127.0.0.1:8765/feed?compose=idea; body: Home · MANBAR MAN BAR منبر · AAU students Home feed Projects Marketplace Learning center Mentors People You My profile Achievements Saved Messages Settings Rank 1 View Bronze ★ ★ ★ Star 1 of 3 55 XP 95 XP to Bronze ★★ New post 1 Notifications Your most recent | Automated HTTP + database verification |
| FILE-001A | File upload boundary | valid PNG exactly 4,194,304 bytes | Accept image and store exactly 4 MB | HTTP 302; posts 3->4; stored_size=4194304; path=uploads/posts/5ae52a490d2e28d1758a.png | Pass | HTTP 302 http://127.0.0.1:8765/post/create; body: | Automated HTTP + database verification |
| FILE-001B | File upload boundary | valid PNG 4,194,305 bytes | Reject image at 4 MB + 1 byte and insert no post | HTTP 200; posts 4->4; payload_bytes=4194305; size_message=True | Pass | redirects 302:/feed?compose=idea; HTTP 200 http://127.0.0.1:8765/feed?compose=idea; body: Home · MANBAR MAN BAR منبر · AAU students Home feed Projects Marketplace Learning center Mentors People You My profile Achievements Saved Messages Settings Rank 1 View Bronze ★ ★ ★ Star 1 of 3 65 XP 85 XP to Bronze ★★ New post 1 Notifications Your most recent | Automated HTTP + database verification |
| PROJ-001 | Project creation | title 2; description 14 | Reject and insert no project | HTTP 200; projects 0->0 | Pass | redirects 302:/projects/new; HTTP 200 http://127.0.0.1:8765/projects/new; body: Start a project · MANBAR MAN BAR منبر · AAU students Home feed Projects Marketplace Learning center Mentors People You My profile Achievements Saved Messages Settings Rank 1 View Bronze ★ ★ ★ Star 1 of 3 65 XP 85 XP to Bronze ★★ New post 1 Notifications Your m | Automated HTTP + database verification |
| PROJ-002 | Project creation | title 3; description 15; team size 1 | Create project; clamp team size to 2; add owner | HTTP 302; project_id=1; values=['3', '15', '2', '2']; owner_membership=1 | Pass | HTTP 302 http://127.0.0.1:8765/projects/new; body: | Automated HTTP + database verification |
| PROJ-003 | Project creation | title 201; description 5001; team size 31 | Truncate 200/5000 and clamp team size to 30 | HTTP 302; project_id=2; stored=['200', '5000', '30', '3'] | Pass | HTTP 302 http://127.0.0.1:8765/projects/new; body: | Automated HTTP + database verification |
| PROJ-004 | Project applications | student 202699002 applies once | Create one pending application and notify owner | HTTP 200; applications 0->1; id=1 | Pass | redirects 302:/projects/1; HTTP 200 http://127.0.0.1:8765/projects/1; body: ABC · MANBAR MAN BAR منبر · AAU students Home feed Projects Marketplace Learning center Mentors People You My profile Achievements Saved Messages Settings Rank 1 View Bronze ★ ★ ★ Star 1 of 3 25 XP 125 XP to Bronze ★★ New post 1 Notifications Your most recent | Automated HTTP + database verification |
| PROJ-005 | Project applications | duplicate application | Reject duplicate; retain one row | HTTP 200; matching rows 1->1 | Pass | redirects 302:/projects/1; HTTP 200 http://127.0.0.1:8765/projects/1; body: ABC · MANBAR MAN BAR منبر · AAU students Home feed Projects Marketplace Learning center Mentors People You My profile Achievements Saved Messages Settings Rank 1 View Bronze ★ ★ ★ Star 1 of 3 25 XP 125 XP to Bronze ★★ New post 1 Notifications Your most recent | Automated HTTP + database verification |
| SEC-003 | Project authorization | applicant attempts to accept own application | HTTP 403; application stays pending | HTTP 403; status=pending | Pass | HTTP 403 http://127.0.0.1:8765/projects/1/application/1; body: No access · MANBAR MAN BAR منبر · AAU students Home feed Projects Marketplace Learning center Mentors People You My profile Achievements Saved Messages Settings Rank 1 View Bronze ★ ★ ★ Star 1 of 3 25 XP 125 XP to Bronze ★★ New post 1 Notifications Your most r | Automated HTTP + database verification |
| PROJ-006 | Project applications | owner accepts application | Status accepted; applicant added as member | HTTP 302; status/member=['accepted', '1'] | Pass | HTTP 302 http://127.0.0.1:8765/projects/1/application/1; body: | Automated HTTP + database verification |
| PROJ-007 | Project tasks | member adds assigned task then moves todo->done | Task stored and status becomes done | add HTTP 302; move HTTP 302; task=['Implement test endpoint', 'done', '3', '2026-10-20'] | Pass | HTTP 302 http://127.0.0.1:8765/projects/1/tasks/1; body: | Automated HTTP + database verification |
| PROJ-008 | Project milestones | owner creates; member marks complete | Milestone done=1 and update recorded | create HTTP 302; member toggle HTTP 302; done=1 | Pass | HTTP 302 http://127.0.0.1:8765/projects/1/milestones; body: | Automated HTTP + database verification |
| VOUCH-001 | Project vouching and achievements | valid 30-character vouch before completion | Reject until project completed | HTTP 200; vouches 0->0 | Pass | redirects 302:/projects/1; HTTP 200 http://127.0.0.1:8765/projects/1; body: ABC · MANBAR MAN BAR منبر · AAU students Home feed Projects Marketplace Learning center Mentors People You My profile Achievements Saved Messages Settings Rank 1 View Bronze ★ ★ ★ Star 1 of 3 35 XP 115 XP to Bronze ★★ New post 3 Notifications Your most recent | Automated HTTP + database verification |
| VOUCH-002 | Project vouching and achievements | completed project; 5 stars; 3 approved traits; body 64 chars | Store vouch, calculate positive XP, refresh reputation | complete HTTP 302; vouch HTTP 302; vouch=['5', 'Collaborative,Reliable,Strong communicator', '150']; reputation_score=305; points_before_vouch=125 | Pass | HTTP 302 http://127.0.0.1:8765/projects/1/vouch/2; body: | Automated HTTP + database verification |
| MKT-001 | Marketplace service creation | title length 4; description length 19 | Reject; no listing | HTTP 200; services 0->0 | Pass | redirects 302:/marketplace/new; HTTP 200 http://127.0.0.1:8765/marketplace/new; body: New listing · MANBAR MAN BAR منبر · AAU students Home feed Projects Marketplace Learning center Mentors People You My profile Achievements Saved Messages Settings Rank 1 View Bronze ★ ★ ★ Star 3 of 3 305 XP 145 XP to Silver ★ New post 4 Notifications Your most | Automated HTTP + database verification |
| MKT-002 | Marketplace service creation | title 5; description 20; price 100001; days 0; slots 0; revisions 25 | Create; clamp to 100000/1/1/20 | HTTP 302; service_id=1; stored=['5', '20', '100000', '1', '1', '20', '2'] | Pass | HTTP 302 http://127.0.0.1:8765/marketplace/new; body: | Automated HTTP + database verification |
| MKT-003 | Marketplace orders | seller requests own listing | Reject; no request row | HTTP 200; requests 0->0 | Pass | redirects 302:/marketplace/1; HTTP 200 http://127.0.0.1:8765/marketplace/1; body: ABCDE · MANBAR MAN BAR منبر · AAU students Home feed Projects Marketplace Learning center Mentors People You My profile Achievements Saved Messages Settings Rank 1 View Bronze ★ ★ ★ Star 3 of 3 315 XP 135 XP to Silver ★ New post 4 Notifications Your most recen | Automated HTTP + database verification |
| MKT-004 | Marketplace orders | buyer requests listing | Create pending request, message, and notification | HTTP 302; request_id=1; conversation_messages=1 | Pass | HTTP 302 http://127.0.0.1:8765/marketplace/1/request; body: | Automated HTTP + database verification |
| PAY-001 | Marketplace demonstration payments | AED 250; PayPal demo; pay before | No real payment; insert held demo payment and accept order | offer HTTP 302 ['offered', '250', '7', 'before']; accept HTTP 302 Location=/checkout/order/1; demo pay HTTP 302; payment=['250', 'paypal', 'full', 'held', 'MB-']; request_status=accepted | Pass | HTTP 302 http://127.0.0.1:8765/checkout/order/1; body: | Automated HTTP + database verification |
| MKT-005 | Marketplace orders and reviews | deliver; confirm; review rating 6 | Complete order, release demo hold, clamp review to 5 | deliver/confirm/review HTTP 302/302/302; request=completed; payment=released; review=['5', '47'] | Pass | HTTP 302 http://127.0.0.1:8765/marketplace/1/review; body: | Automated HTTP + database verification |
| SEC-004 | Learning Center authorization | student attempts course creation | HTTP 403 | HTTP 403; No access · MANBAR MAN BAR منبر · AAU students Home feed Projects Marketplace Learning center Mentors People You My profile Achievements Saved Messages 3 Settings Rank 2 View Silver ★ ★ ★ Star 1 of 3 455 XP 145 XP to Silver ★★ New post 3 9 Notifications Your m | Pass | HTTP 403 http://127.0.0.1:8765/learn/new; body: No access · MANBAR MAN BAR منبر · AAU students Home feed Projects Marketplace Learning center Mentors People You My profile Achievements Saved Messages 3 Settings Rank 2 View Silver ★ ★ ★ Star 1 of 3 455 XP 145 XP to Silver ★★ New post 3 9 Notifications Your m | Automated HTTP + database verification |
| LEARN-001 | Course creation | title 4; description 19 | Reject and insert no course | HTTP 200; courses 0->0 | Pass | redirects 302:/learn/new; HTTP 200 http://127.0.0.1:8765/learn/new; body: Create a course · MANBAR MAN BAR منبر · AAU students Home feed Projects Marketplace Learning center Mentors People You My profile Achievements Saved Messages Settings Rank 1 View Bronze ★ ★ ★ Star 1 of 3 25 XP 125 XP to Bronze ★★ New post 1 Notifications Your | Automated HTTP + database verification |
| LEARN-002 | Course creation | title 5; description 20; price AED 1 | Create and raise positive sub-minimum price to AED 2 | HTTP 302; course_id=1; stored=['5', '20', '2.00', 'published', '4'] | Pass | HTTP 302 http://127.0.0.1:8765/learn/new; body: | Automated HTTP + database verification |
| LEARN-003 | Learning Center enrollment | teacher adds lesson; changes isolated course price to AED 0; student enrolls | Store lesson and one enrollment | lesson HTTP 302, id=1; free-settings HTTP 302; price=0.00; enroll HTTP 302; enrolled=1 | Pass | HTTP 302 http://127.0.0.1:8765/learn/1/enroll; body: | Automated HTTP + database verification |
| QUIZ-001 | Quiz creation | publish empty quiz; time 301; live 4; pass 101; attempts 21 | Remain draft; clamp to 300/5/100/20 | create HTTP 302; settings HTTP 200; quiz=['draft', '300', '5', '100', '20'] | Pass | redirects 302:/learn/1/quiz/1/edit; HTTP 200 http://127.0.0.1:8765/learn/1/quiz/1/edit; body: Edit quiz · Boundary Quiz · MANBAR MAN BAR منبر · AAU students Home feed Projects Marketplace Learning center Mentors People You My profile Achievements Saved Messages Settings Rank 1 View Bronze ★ ★ ★ Star 1 of 3 40 XP 110 XP to Bronze ★★ New post 2 Notificat | Automated HTTP + database verification |
| QUIZ-002 | Quiz question validation | MCQ with one option | Reject; no question | HTTP 200; questions 0->0 | Pass | redirects 302:/learn/1/quiz/1/edit#add; HTTP 200 http://127.0.0.1:8765/learn/1/quiz/1/edit#add; body: Edit quiz · Boundary Quiz · MANBAR MAN BAR منبر · AAU students Home feed Projects Marketplace Learning center Mentors People You My profile Achievements Saved Messages Settings Rank 1 View Bronze ★ ★ ★ Star 1 of 3 40 XP 110 XP to Bronze ★★ New post 2 Notificat | Automated HTTP + database verification |
| QUIZ-003 | Quiz creation | valid 3-option MCQ; points 101; max attempts 1 | Clamp points to 100 and publish | question HTTP 302; publish HTTP 302; stored=['100', 'published', '1'] | Pass | HTTP 302 http://127.0.0.1:8765/learn/1/quiz/1/settings; body: | Automated HTTP + database verification |
| QUIZ-004 | Course/quiz integration | student starts quiz before preceding lesson is complete, then completes lesson | Redirect to the lesson, create no attempt, then advance enrollment to quiz | blocked HTTP 302 Location=/learn/1/lesson/1; attempts 0->0; lesson-complete HTTP 302; current_step=q1 | Pass | HTTP 302 http://127.0.0.1:8765/learn/1/quiz/1/start; body: | Automated HTTP + database verification |
| QUIZ-005 | Quiz submission and automatic grading | autosave choice 99 then submit correct choice 1 | Invalid choice becomes NULL; correct answer grades 100/100 | start/save/submit HTTP 302/200/302; invalid choice stored=NULL; attempt=['graded', '100', '100']; answer=['1', '1', '100'] | Pass | HTTP 302 http://127.0.0.1:8765/quiz/attempt/1/submit; body: | Automated HTTP + database verification |
| QUIZ-006 | Quiz attempt limits | second start when max_attempts=1 | Reject; keep one attempt | HTTP 200; attempts 1->1 | Pass | redirects 302:/learn/1/quiz/1; HTTP 200 http://127.0.0.1:8765/learn/1/quiz/1; body: Boundary Quiz · MANBAR MAN BAR منبر · AAU students Home feed Projects Marketplace Learning center Mentors People You My profile Achievements Saved Messages 2 Settings Rank 1 View Bronze ★ ★ ★ Star 1 of 3 113 XP 37 XP to Bronze ★★ New post 2 7 Notifications You | Automated HTTP + database verification |
| LIVE-001 | Live quiz sessions | host starts; student joins; correct answer; duplicate answer | Six-digit session, one player/answer, duplicate HTTP 409 | start=302; code_length=6; join=200/player=1; open=200; answer=200; duplicate=409; db_answer=['1', '995'] | Pass | HTTP 409 http://127.0.0.1:8765/live/454993/answer; body: {"ok":false,"error":"This question is closed."} | Automated HTTP + database verification |
| PROFILE-001 | Profile management | theme 9; javascript URL; invalid style/effect; valid mentor fields | Clamp theme; reject URL; default styles; activate mentor | HTTP 302; user=['5', 'NULL', 'classic', 'none', '+971 50 000 0000']; mentor=['1', 'Software testing'] | Pass | HTTP 302 http://127.0.0.1:8765/profile/edit; body: | Automated HTTP + database verification |
| PROFILE-002 | Profile management | roster student submits Changed Name and example.com | Preserve roster name; normalize website to HTTPS | HTTP 302; name/website=['Alya Test Student', 'https://example.com'] | Pass | HTTP 302 http://127.0.0.1:8765/profile/edit; body: | Automated HTTP + database verification |
| MENTOR-001 | Mentorship requests | topic length 3 | Reject and insert no request | HTTP 200; requests 0->0 | Pass | redirects 302:/mentors/4; HTTP 200 http://127.0.0.1:8765/mentors/4; body: Dr. Test Mentor · Mentor · MANBAR MAN BAR منبر · AAU students Home feed Projects Marketplace Learning center Mentors People You My profile Achievements Saved Messages 3 Settings Rank 2 View Silver ★ ★ ★ Star 1 of 3 455 XP 145 XP to Silver ★★ New post 3 9 Notif | Automated HTTP + database verification |
| MENTOR-002 | Mentorship requests | valid request then mentor accepts with date | Accepted status and scheduled date | request HTTP 302; accept HTTP 302; stored=['accepted', '2026-10-20 10:00:00'] | Pass | HTTP 302 http://127.0.0.1:8765/mentorship/1; body: | Automated HTTP + database verification |
| MSG-001 | Private messaging | empty body and no attachment | HTTP 422; no message | HTTP 422; messages 0->0; {"ok":false,"error":"Write a message, attach a file, or record a voice message."} | Pass | HTTP 422 http://127.0.0.1:8765/messages/1; body: {"ok":false,"error":"Write a message, attach a file, or record a voice message."} | Automated HTTP + database verification |
| MSG-002 | Private messaging | typing signal and text message | Immediate JSON send and receiver polling without refresh | typing/send/poll HTTP 200/200/200; message_id=6; polled=1 | Pass | HTTP 200 http://127.0.0.1:8765/api/messages/2?after=0; body: {"ok":true,"messages":[{"id":6,"sender_id":2,"receiver_id":1,"mine":false,"body":"Fast fictional test message","created_at":"2026-10-11 22:04:10","time":"22:04","attachment":null}],"typing":true} | Automated HTTP + database verification |
| FILE-002 | Message attachment validation | malware.exe | HTTP 422; extension rejected; no message | HTTP 422; messages 1->1; {"ok":false,"error":"Use an image, voice recording, PDF, Office document, text/CSV file, or ZIP archive."} | Pass | HTTP 422 http://127.0.0.1:8765/messages/1; body: {"ok":false,"error":"Use an image, voice recording, PDF, Office document, text/CSV file, or ZIP archive."} | Automated HTTP + database verification |
| FILE-003 | Message attachment validation | fake.pdf containing plain text | HTTP 422 MIME/extension mismatch | HTTP 422; {"ok":false,"error":"This file type does not match its extension."} | Pass | HTTP 422 http://127.0.0.1:8765/messages/1; body: {"ok":false,"error":"This file type does not match its extension."} | Automated HTTP + database verification |
| FILE-004 | Message attachment validation | valid text/plain evidence.txt | Store randomized file and attachment metadata | HTTP 200; message_id=7; attachment=['evidence.txt', 'text/plain', '40', '/uploads/messages/20261011-783b9b6608b97051c19ee752.txt']; stored_file_exists=True | Pass | HTTP 200 http://127.0.0.1:8765/messages/1; body: {"ok":true,"message":{"id":7,"sender_id":2,"receiver_id":1,"mine":true,"body":"Attached test evidence","created_at":"2026-10-11 22:04:11","time":"22:04","attachment":{"url":"/uploads/messages/20261011-783b9b6608b97051c19ee752.txt","name":"evidence.txt","type": | Automated HTTP + database verification |
| FILE-005 | Message attachment validation | text file of 12 MB + 1 byte | HTTP 422; 12 MB application boundary enforced | HTTP 422; payload_bytes=12582913; {"ok":false,"error":"Attachments can be up to 12 MB."} | Pass | HTTP 422 http://127.0.0.1:8765/messages/1; body: {"ok":false,"error":"Attachments can be up to 12 MB."} | Automated HTTP + database verification |
| NOTIF-001 | Notifications | peek then mark all read | API lists unread; POST makes unread count zero | peek HTTP 200; unread_before=3; API_unread=3; mark HTTP 302; unread_after=0 | Pass | HTTP 302 http://127.0.0.1:8765/notifications/read-all; body: | Automated HTTP + database verification |
| SET-001 | Profile settings | enable only message notification group | Persist per-category preferences | HTTP 302; preference_rows=7; messages=1; other_groups_off=6 | Pass | HTTP 302 http://127.0.0.1:8765/settings/notifications; body: | Automated HTTP + database verification |
| AI-001 | AI Assistant validation | text length 3; local_only=1 | HTTP 422 | HTTP 422; {"ok":false,"error":"Write a few words first."} | Pass | HTTP 422 http://127.0.0.1:8765/api/ai/assist; body: {"ok":false,"error":"Write a few words first."} | Automated HTTP + database verification |
| AI-002 | AI Assistant fallback | malformed sentence; local_only=1; all provider keys blank | HTTP 200 using the local rules engine with corrected text | HTTP 200; engine=rules; corrected=I want to improve my capstone project and I like to improve it. | Pass | HTTP 200 http://127.0.0.1:8765/api/ai/assist; body: {"ok":true,"fix":{"corrected":"I want to improve my capstone project and I like to improve it.","changes":[{"from":"impove","to":"improve","why":"Spelling / shorthand"},{"from":"lke","to":"like","why":"Spelling / shorthand"},{"from":"…","to":"…","why":"Improve | Automated HTTP + database verification |
| SEC-005 | AI Assistant rate limiting | 31 requests within 60 seconds | 31st request HTTP 429 | 31 requests in <60 s; statuses=30x200,1x429; final=429 | Pass | Automated loop; final request returned rate-limit JSON. | Automated HTTP + database verification |
| ADMIN-001 | Administrator permissions | student GET /admin | HTTP 403 | HTTP 403; No access · MANBAR MAN BAR منبر · AAU students Home feed Projects Marketplace Learning center Mentors People You My profile Achievements Saved Messages 3 Settings Rank 2 View Silver ★ ★ ★ Star 1 of 3 455 XP 145 XP to Silver ★★ New post 3 11 Notifications Your | Pass | HTTP 403 http://127.0.0.1:8765/admin; body: No access · MANBAR MAN BAR منبر · AAU students Home feed Projects Marketplace Learning center Mentors People You My profile Achievements Saved Messages 3 Settings Rank 2 View Silver ★ ★ ★ Star 1 of 3 455 XP 145 XP to Silver ★★ New post 3 11 Notifications Your | Automated HTTP + database verification |
| ADMIN-002 | Administrator permissions | configured test admin login | HTTP 200 and admin role | HTTP 200; role=admin; dashboard_visible=False | Pass | HTTP 200 http://127.0.0.1:8765/admin; body: Dashboard · MANBAR Admin MAN BAR Admin console Overview Dashboard People Users Student roster Content Moderation Projects, services, courses Platform Universities Settings & audit Back Open the community Dashboard Sunday, 11 October 2026 MA MANBAR Admin chapte | Automated HTTP + database verification |
| ROSTER-001 | Student roster management | valid fictional 202699010 record | Insert one roster row | HTTP 302; roster rows 0->1 | Pass | HTTP 302 http://127.0.0.1:8765/admin/roster/add; body: | Automated HTTP + database verification |
| ROSTER-002 | Student roster management | CSV: header + 1 valid + 2 invalid rows | Import one and skip two | HTTP 200; valid_rows=1; invalid_rows=0; import_summary_visible=True | Pass | redirects 302:/admin/roster; HTTP 200 http://127.0.0.1:8765/admin/roster; body: Student roster · MANBAR Admin MAN BAR Admin console Overview Dashboard People Users Student roster Content Moderation Projects, services, courses Platform Universities Settings & audit Back Open the community Student roster Sunday, 11 October 2026 MA MANBAR Ad | Automated HTTP + database verification |
| MOD-001 | Content moderation | student reports post; admin hides with reason | Post hidden and report resolved | report HTTP 200; moderate HTTP 302; post/report=['hidden', 'resolved'] | Pass | HTTP 302 http://127.0.0.1:8765/admin/moderate; body: | Automated HTTP + database verification |
| SEC-006 | Content moderation authorization | student POST /admin/moderate | HTTP 403 | HTTP 403 | Pass | HTTP 403 http://127.0.0.1:8765/admin/moderate; body: No access · MANBAR MAN BAR منبر · AAU students Home feed Projects Marketplace Learning center Mentors People You My profile Achievements Saved Messages 2 Settings Rank 1 View Bronze ★ ★ ★ Star 1 of 3 113 XP 37 XP to Bronze ★★ New post 2 8 Notifications Your mo | Automated HTTP + database verification |
| PERF-001 | Safe performance measurement | 30 sequential authenticated GET /feed requests on localhost | All HTTP 200; record latency distribution | {"requests": 30, "status_200": 30, "min_ms": 108.0, "avg_ms": 129.89, "p95_ms": 155.45, "max_ms": 170.02} | Pass | Sequential loopback GET measurements; not a production load test. | Automated local sequential timing |
| ISO-001 | Environment isolation | compare schemas after all tests | Original remains 55 tables; runtime tables appear only in test DB | original_tables=55; test_tables=55; original users snapshot=17	17	2026-10-03 09:05:35; test DB=manbar_ch6_test_20261011 | Pass | Read-only information_schema queries and isolated database name. | Automated read-only database inspection |
| STATIC-001 | PHP source integrity | All 123 PHP files discovered with rg --files -g '*.php' | Every file passes php -l | 123 files checked; 0 lint failures | Pass | storage/chapter6-evidence-20261011/php-lint.log | Automated static syntax execution |
| STATIC-002 | JavaScript source integrity | All 6 JavaScript files under public | Every file passes node --check | 6 files checked; 0 syntax failures | Pass | storage/chapter6-evidence-20261011/js-syntax.log | Automated static syntax execution |
| STATIC-003 | Routing integration | Parse app/routes.php and controller function declarations | Every declared route resolves to a controller handler | 177 routes, 175 unique handlers, 0 missing handlers | Pass | storage/chapter6-evidence-20261011/route-handler-scan.log | Automated static code inspection |
| DB-001 | Database structure | Isolated schema after all lazy schema initializers ran | Schema installed with relational constraints and fictional users only | 55 tables; 68 foreign keys; 5 users; 3 fictional 20269900x users | Pass | storage/chapter6-evidence-20261011/schema-verification.log | Automated information_schema inspection |
| BROWSER-001 | Browser compatibility | Chrome headless, GET /login, 1440x1000 viewport | Login page renders and a non-empty screenshot is produced | Screenshot generated; 445865 bytes; SHA-256 578732C64258B81BD6117ADE6D54A6DFA699421297BF48D0C691BAB43EBE6ECC | Pass | storage/chapter6-evidence-20261011/chrome-login.png | Automated browser smoke execution plus visual artifact |
| BROWSER-002 | Browser compatibility | Microsoft Edge headless, GET /login, 1440x1000 viewport | Login page renders and a non-empty screenshot is produced | Screenshot generated; 404738 bytes; SHA-256 B7050ECAB72A77AD5B67AC9EABB5A353F94AA00FB60285C74E51C65E7486D4D5 | Pass | storage/chapter6-evidence-20261011/edge-login.png | Automated browser smoke execution plus visual artifact |
| SEC-007 | Private messaging attachment authorization | Unauthenticated GET of the randomized URL stored in messages.attachment_path | HTTP 401/403 or authenticated ownership check; private attachment content must not be returned | HTTP 200, Content-Type text/plain; fictional attachment body returned | Fail | storage/chapter6-evidence-20261011/unauthenticated-message-attachment.log | Automated unauthenticated HTTP security test |

## 6.7 Failed test analysis and security findings

### VULN-001 — Private message attachments are accessible without authentication (High)

**Status:** Confirmed by execution

**Evidence:** `PATH=/uploads/messages/20261011-783b9b6608b97051c19ee752.txt`, `HTTP_STATUS=200`, `CONTENT_TYPE=text/plain; charset=UTF-8`, and the fictional attachment body was returned.

**Impact:** Anyone who learns or receives the randomized URL can download a file from a supposedly private conversation. Random names reduce guessing but are not authorization.

**Recommendation:** Store message files outside public/, expose a download controller that requires login and verifies sender/receiver ownership, set Content-Disposition and nosniff, and migrate existing attachments.

### VULN-002 — No Content-Security-Policy header is set (Medium)

**Status:** Static inspection

**Evidence:** public/index.php:5-6 sets nosniff and SAMEORIGIN, but no Content-Security-Policy header was found.

**Impact:** Existing output escaping passed, but a future injection defect would have fewer browser-side containment controls.

**Recommendation:** Deploy a nonce- or hash-based CSP after inventorying inline scripts/styles; start with Report-Only.

### VULN-003 — HSTS is not configured in application code (Deployment limitation)

**Status:** Static inspection

**Evidence:** No Strict-Transport-Security header was found. Local tests used HTTP by design.

**Impact:** A production deployment must rely on the reverse proxy/web server for HTTPS enforcement and HSTS.

**Recommendation:** At the production proxy, redirect HTTP to HTTPS and enable HSTS only after HTTPS is correct for all subdomains.

## 6.8 Implementation strengths observed

- All normal POST routes tested enforce CSRF; a missing token returned HTTP 419.
- Database access uses prepared queries through the project database helpers, and the tested XSS payload rendered escaped rather than executable.
- Role and ownership checks blocked student course creation, self-approval of a project application, unauthorized task/moderation actions, and invalid vouch timing.
- Upload checks combine size, extension and detected MIME. Course/service materials are designed to be stored outside the public web root.
- Quiz sequencing, attempt limits, option validation, autosave and automatic grading worked together correctly.
- Authentication uses six-digit hashed codes, expiry, try limits, rate limits and session regeneration on login.
- The original database remained byte-equivalent after normalization, demonstrating that state-changing work stayed in the test database.

## 6.9 Limitations

- The HTTP tests ran on PHP 8.1.25's built-in development server and a local MySQL database; PERF-001 is a safe response-time sample, not a production capacity or concurrent load test.
- Google OAuth, SMTP delivery, Stripe test mode, Firefox, mobile viewports, and controlled concurrency were not executed. They remain in the proposed table.
- All AI provider credentials were blank. AI-002 tested MANBAR's deterministic local rules fallback, not Gemini/Groq/OpenAI quality or availability.
- PAY-001 used MANBAR's internal demonstration-payment flow. No payment network was contacted and no real money moved.
- Chrome and Edge checks verified public login rendering and retained screenshots; authenticated cross-browser workflows were not automated.
- A 25 MB course-material upload boundary was verified by code inspection but was not uploaded during this run to avoid unnecessary disk consumption; it is explicitly proposed below.

## 6.10 Proposed test cases not executed

These cases must not be presented as executed results. They require external sandbox credentials, a broader browser harness, controlled concurrency, or a deliberately larger binary fixture.

| Test Case ID | Module | Input Values | Expected Output | Verification Procedure | Reason Not Executed |
| --- | --- | --- | --- | --- | --- |
| PROP-GOOGLE-001 | Google authentication | Sandbox Google OAuth client and fictional managed-domain account | Valid ID token with matching audience, issuer, nonce and verified AAU email creates/signs in user; altered token is rejected | Configure a dedicated OAuth test client on a non-production callback, use an authorized fictional account, capture HTTP status/JSON, and confirm only the test DB changes. | Google client ID was intentionally blank to prevent external authentication and no managed fictional Google account was supplied. |
| PROP-STRIPE-001 | Stripe course payment | Stripe test key, AED 2 course, Stripe test card 4242 4242 4242 4242 | Stripe test Checkout session created; signed test webhook marks payment paid and enrolls fictional student; invalid signature rejected | Use Stripe CLI/test mode only, point webhook to isolated server, verify course_payments and enrollments, then discard test records. | All payment credentials were blank; only MANBAR's internal demonstration payment path was executed. |
| PROP-SMTP-001 | SMTP delivery | Sandbox mailbox and fictional recipient controlled by tester | Message accepted by sandbox SMTP and no real university address contacted | Use Mailpit/MailHog locally, configure sandbox host/from, request code, inspect captured message and database event. | SMTP was intentionally disabled; the code was written to the sandbox mail.log instead. |
| PROP-BROWSER-001 | Browser compatibility | Latest Chrome, Edge and Firefox at 1440x900 and 390x844 | Login, feed, project, marketplace, course, quiz and messages render without clipping and core actions remain usable | Run Playwright against the isolated server using seeded sessions; retain full-page screenshots and accessibility snapshots for each viewport/browser. | Cross-browser authenticated automation is not configured in the repository; public-page Chromium/Edge smoke checks are handled separately if installed. |
| PROP-CONC-001 | Concurrency | Two owners accept final project slot simultaneously; two live-answer submissions simultaneously | Only capacity-safe membership/one live answer persists | Use parallel HTTP clients and transaction-aware assertions on a disposable clone of the isolated database. | A controlled concurrency harness was not available; sequential duplicate protections were tested instead. |
| PROP-FILE-001 | Course/service material upload | Valid allowlisted file at exactly 26,214,400 bytes and another at 26,214,401 bytes; mismatched extension/MIME pair | Exact 25 MB file is stored outside public root; 25 MB + 1 and MIME mismatch are rejected with no database row/file residue | On a disposable clone, POST multipart files to the authenticated course/material and service portfolio endpoints; inspect HTTP messages, material/service_files rows, and storage/course_files or storage/service_files. | The 25 MB rule was confirmed statically; large binary boundary execution was omitted to keep the evidence run lightweight. |
| PROP-VOICE-001 | Voice messages | Browser microphone permission granted; 3-second WebM/Opus recording and denied-permission scenario | Recorded audio posts once, recipient can play it without refresh, and denied permission shows a usable error without creating a message | Use authenticated Playwright contexts with a synthetic media stream and permissions; verify messages row, MIME/size, polling response and audio playback controls. | Headless public-page smoke testing did not include authenticated microphone/media permission automation. |

## 6.11 Exact verification commands

1. `C:\xampp\mysql\bin\mysqldump.exe -uroot --single-transaction --routines --triggers --events --databases manbar --result-file=C:\xampp\htdocs\manbar\storage\chapter6-evidence-20261011\original-manbar-before-ch6-tests.sql`

2. `C:\xampp\php\php.exe database\install.php --fresh  (working directory: C:\xampp\htdocs\manbar_ch6_test_20261011)`

3. `C:\xampp\php\php.exe -d upload_max_filesize=30M -d post_max_size=40M -d session.save_path=C:\xampp\htdocs\manbar_ch6_test_20261011\storage\sessions -S 127.0.0.1:8765 -t public public/index.php`

4. `python chapter6_test_runner.py 2>&1 | Tee-Object -FilePath C:\xampp\htdocs\manbar\storage\chapter6-evidence-20261011\test-run.stdout.log`

5. `rg --files -g '*.php'  then for each file: C:\xampp\php\php.exe -l <file>`

6. `rg --files public -g '*.js'  then for each file: node --check <file>`

7. `PowerShell route scan: parse app\routes.php handlers and compare them with function declarations in app\controllers\*.php`

8. `C:\xampp\mysql\bin\mysql.exe -uroot -N -B manbar_ch6_test_20261011 -e <information_schema table and foreign-key queries>`

9. `C:\Program Files\Google\Chrome\Application\chrome.exe --headless=new --disable-gpu --no-first-run --window-size=1440,1000 --virtual-time-budget=3000 --screenshot=<evidence> http://127.0.0.1:8765/login`

10. `C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe --headless --disable-gpu --no-first-run --no-default-browser-check --window-size=1440,1000 --virtual-time-budget=3000 --screenshot=<evidence> http://127.0.0.1:8765/login`

11. `Invoke-WebRequest -Uri http://127.0.0.1:8765/uploads/messages/<random-test-file>.txt -UseBasicParsing -SkipHttpErrorCheck  (no session/cookies)`

12. `C:\xampp\mysql\bin\mysqldump.exe -uroot --single-transaction --routines --triggers --events --databases manbar --result-file=C:\xampp\htdocs\manbar\storage\chapter6-evidence-20261011\original-manbar-after-ch6-tests.sql`

13. `Normalize both original-database dumps by removing only '-- Dump completed on ...', calculate SHA-256, and compare`

## 6.12 Evidence inventory

Evidence directory: `C:\xampp\htdocs\manbar\storage\chapter6-evidence-20261011`

- `raw-test-results.json` — raw 67-case automated HTTP/database results.
- `test-run.stdout.log` — concise runner output with every case and observed response/database state.
- `original-manbar-before-ch6-tests.sql` and `original-manbar-after-ch6-tests.sql` — original database protection evidence.
- `php-lint.log`, `js-syntax.log`, `route-handler-scan.log`, `schema-verification.log` — static and schema checks.
- `chrome-login.png` and `edge-login.png` — browser smoke screenshots.
- `unauthenticated-message-attachment.log` — confirmed failed privacy test.

## 6.13 Conclusion

MANBAR achieved **73 passes from 74 executed tests (98.65%)** in the isolated environment. The principal workflows and validation boundaries behaved as implemented, including multi-module project, marketplace, learning and live-quiz scenarios. One high-priority privacy defect must be corrected before production: private message files require authenticated, participant-only download authorization. External-provider and full cross-browser/concurrency tests remain explicitly proposed rather than being represented as completed.
