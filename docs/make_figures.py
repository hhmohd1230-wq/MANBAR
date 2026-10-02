#!/usr/bin/env python3
"""Generates the additional report figures (SVG) into docs/figures/. Run: python3 docs/make_figures.py
PNG versions are rendered by docs/render_png.js (Playwright)."""
import os, math, html

OUT = os.path.join(os.path.dirname(__file__), 'figures')
os.makedirs(OUT, exist_ok=True)

G = dict(g50='#f2fbf5', g100='#e1f6e8', g200='#c3ecd2', g300='#94dcb1', g500='#34b06b', g600='#249658', g700='#1d7a48', g800='#1a613c', g900='#124a2d',
         ink='#11291d', muted='#5f7568', line='#cfe3d7', amber='#f2b032', blue='#3b8fe0', violet='#8b6fe0', rose='#e0587b', teal='#1fa5a0', orange='#ef8a3d')
FONT = "Inter, 'Segoe UI', Arial, sans-serif"
e = html.escape


def svg(w, h, body, title=''):
    return (f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {w} {h}" width="{w}" height="{h}" font-family="{FONT}">'
            '<defs><linearGradient id="hd" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#43c783"/><stop offset="1" stop-color="#1f9a5c"/></linearGradient>'
            '<marker id="ar" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="8" markerHeight="8" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" fill="#1d7a48"/></marker>'
            '<marker id="ar2" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" fill="#8da498"/></marker>'
            '<filter id="sh" x="-10%" y="-10%" width="120%" height="130%"><feDropShadow dx="0" dy="3" stdDeviation="4" flood-color="#124a2d" flood-opacity=".14"/></filter></defs>'
            f'<rect width="{w}" height="{h}" fill="#ffffff"/>' + (f'<text x="{w/2}" y="40" text-anchor="middle" font-size="24" font-weight="800" fill="{G["g900"]}">{e(title)}</text>' if title else '') + body + '</svg>')


def text(x, y, s, size=14, weight=500, fill=None, anchor='middle', lh=None):
    fill = fill or G['ink']
    lines = s.split('\n')
    lh = lh or size * 1.3
    y0 = y - (len(lines) - 1) * lh / 2 + size * 0.35
    t = ''.join(f'<tspan x="{x}" dy="{0 if i == 0 else lh}">{e(l)}</tspan>' for i, l in enumerate(lines))
    return f'<text x="{x}" y="{y0}" text-anchor="{anchor}" font-size="{size}" font-weight="{weight}" fill="{fill}">{t}</text>'


def box(x, y, w, h, label='', fill='#fff', stroke=None, r=14, size=14, weight=600, color=None, shadow=True, sub=None):
    stroke = stroke or G['g300']
    s = f'<rect x="{x}" y="{y}" width="{w}" height="{h}" rx="{r}" fill="{fill}" stroke="{stroke}" stroke-width="1.6"' + (' filter="url(#sh)"' if shadow else '') + '/>'
    if label:
        if sub:
            s += text(x + w / 2, y + h / 2 - 9, label, size, weight, color) + text(x + w / 2, y + h / 2 + 12, sub, size - 3, 500, G['muted'])
        else:
            s += text(x + w / 2, y + h / 2, label, size, weight, color)
    return s


def head_box(x, y, w, h, title, lines, tone=None):
    tone = tone or G['g600']
    s = f'<rect x="{x}" y="{y}" width="{w}" height="{h}" rx="14" fill="#fff" stroke="{G["g300"]}" stroke-width="1.6" filter="url(#sh)"/>'
    s += f'<path d="M{x},{y+14} a14,14 0 0 1 14,-14 h{w-28} a14,14 0 0 1 14,14 v22 h-{w} z" fill="{tone}"/>'
    s += text(x + w / 2, y + 17, title, 14, 800, '#fff')
    for i, l in enumerate(lines):
        s += text(x + 14, y + 52 + i * 19, l, 12, 500, G['ink'], anchor='start')
    return s


def arrow(x1, y1, x2, y2, label='', dashed=False, color=None, lab_dy=-8, both=False):
    color = color or G['g700']
    d = ' stroke-dasharray="7 6"' if dashed else ''
    m = ' marker-end="url(#ar)"' + (' marker-start="url(#ar)"' if both else '')
    s = f'<line x1="{x1}" y1="{y1}" x2="{x2}" y2="{y2}" stroke="{color}" stroke-width="2"{d}{m}/>'
    if label:
        mx, my = (x1 + x2) / 2, (y1 + y2) / 2
        s += f'<rect x="{mx - len(label) * 3.6 - 6}" y="{my + lab_dy - 11}" width="{len(label) * 7.2 + 12}" height="20" rx="6" fill="#fff" opacity=".95"/>' + text(mx, my + lab_dy, label, 12, 600, G['g800'])
    return s


def poly(points, label='', dashed=False, color=None):
    color = color or G['g700']
    d = ' stroke-dasharray="7 6"' if dashed else ''
    pts = ' '.join(f'{x},{y}' for x, y in points)
    return f'<polyline points="{pts}" fill="none" stroke="{color}" stroke-width="2"{d} marker-end="url(#ar)"/>'


def save(name, w, h, body, title=''):
    with open(os.path.join(OUT, name + '.svg'), 'w', encoding='utf-8') as f:
        f.write(svg(w, h, body, title))
    print('wrote', name)


def clip(cx, cy, w, h, tx, ty):
    """point on rectangle border (centre cx,cy size w,h) towards (tx,ty)"""
    dx, dy = tx - cx, ty - cy
    if dx == 0 and dy == 0:
        return cx, cy
    sx = (w / 2) / abs(dx) if dx else 1e9
    sy = (h / 2) / abs(dy) if dy else 1e9
    s = min(sx, sy)
    return cx + dx * s, cy + dy * s


# ------------------------------------------------------------------ 18 architecture
def fig_architecture():
    b = ''
    # tiers
    b += f'<rect x="40" y="80" width="300" height="470" rx="22" fill="{G["g50"]}" stroke="{G["g200"]}"/>' + text(190, 106, 'PRESENTATION TIER', 13, 800, G['g700'])
    b += f'<rect x="400" y="80" width="560" height="470" rx="22" fill="{G["g50"]}" stroke="{G["g200"]}"/>' + text(680, 106, 'APPLICATION TIER  (PHP 8)', 13, 800, G['g700'])
    b += f'<rect x="1020" y="80" width="300" height="470" rx="22" fill="{G["g50"]}" stroke="{G["g200"]}"/>' + text(1170, 106, 'DATA TIER', 13, 800, G['g700'])
    b += box(70, 140, 240, 70, 'Web browser', G['g100'], sub='Desktop · Tablet · Phone')
    b += box(70, 235, 240, 70, 'HTML + CSS design system', '#fff', sub='Light-green theme, SVG graphics')
    b += box(70, 330, 240, 70, 'Vanilla JavaScript', '#fff', sub='AJAX · reactions · AI assist')
    b += box(70, 425, 240, 90, 'Google Sign-In button', '#fff', sub='Google Identity Services')
    b += box(430, 140, 500, 62, 'Front controller  public/index.php  ·  Router  ·  CSRF  ·  Sessions', G['g100'], size=13)
    ctrl = ['Auth &\nOnboarding', 'Feed &\nPosts', 'Profiles &\nPeople', 'Projects', 'Marketplace', 'Learning\ncenter', 'Mentorship', 'Admin\nconsole']
    for i, c in enumerate(ctrl):
        b += box(430 + (i % 4) * 128, 225 + (i // 4) * 80, 118, 68, c, '#fff', size=12)
    b += box(430, 392, 240, 70, 'Services', G['g100'], sub='Points · Badges · Notifications', size=13)
    b += box(690, 392, 240, 70, 'AI Assistant', '#e9f9ef', stroke=G['g500'], sub='Spelling fix · Post routing', size=13)
    b += box(430, 478, 240, 52, 'PDO layer + PHP views', G['g100'], size=12)
    b += box(1050, 140, 240, 100, 'MySQL / MariaDB', G['g100'], sub='XAMPP now · hosted server later')
    b += box(1050, 265, 240, 90, '32 tables', '#fff', sub='users · posts · projects · services …')
    b += box(1050, 380, 240, 70, 'File storage', '#fff', sub='public/uploads (images)')
    b += box(1050, 470, 240, 60, 'Student roster (CSV import)', '#fff', size=12)
    # externals
    b += box(430, 600, 230, 70, 'Google OAuth', '#fff', stroke=G['blue'], sub='ID-token verification')
    b += box(700, 600, 230, 70, 'Claude API (optional)', '#fff', stroke=G['violet'], sub='Smarter assistant')
    b += text(1170, 635, 'External services (dashed)', 12, 700, G['muted'])
    b += arrow(310, 175, 430, 175, 'HTTPS', both=True)
    b += arrow(930, 171, 1050, 190, 'SQL', both=True)
    b += arrow(545, 600, 545, 530, '', dashed=True)
    b += arrow(815, 600, 815, 462, '', dashed=True)
    b += arrow(190, 515, 190, 590, '', dashed=True)
    b += poly([(190, 590), (190, 635), (430, 635)], dashed=True)
    save('figure-18-architecture', 1360, 710, b, 'Figure 18 — MANBAR system architecture')


# ------------------------------------------------------------------ 19 modules
def fig_modules():
    b = box(560, 330, 240, 90, 'MANBAR', 'url(#hd)', stroke='none', size=28, weight=800, color='#fff', sub=None)
    b += text(680, 372, 'منبر · Student platform', 13, 600, '#e6fff0')
    mods = [
        ('Idea feed', 'posts · reactions · comments · share · save', G['amber'], 60, 90),
        ('Project collaboration', 'teams · applications · task board · chat', G['g500'], 60, 330),
        ('People directory', 'search by skill · follow · messages', G['blue'], 60, 570),
        ('Student marketplace', 'services · requests · ratings & reviews', G['orange'], 1050, 90),
        ('Learning center', 'courses · lessons · progress · enroll', G['teal'], 1050, 330),
        ('Mentorship', 'requests · sessions · feedback', G['violet'], 1050, 570),
        ('Achievements', 'points · badges · levels · leaderboard', G['rose'], 555, 70),
        ('AI assistant', 'auto-correct · route ideas to the right page', G['g700'], 555, 590),
    ]
    for name, sub, col, x, y in mods:
        b += f'<rect x="{x}" y="{y}" width="290" height="104" rx="18" fill="#fff" stroke="{col}" stroke-width="2.4" filter="url(#sh)"/><rect x="{x}" y="{y}" width="10" height="104" rx="5" fill="{col}"/>'
        b += text(x + 30, y + 38, name, 18, 800, G['g900'], anchor='start') + text(x + 30, y + 68, sub, 12, 500, G['muted'], anchor='start')
        cx, cy = x + 145, y + 52
        x1, y1 = clip(cx, cy, 290, 104, 680, 375)
        x2, y2 = clip(680, 375, 240, 90, cx, cy)
        b += f'<line x1="{x1}" y1="{y1}" x2="{x2}" y2="{y2}" stroke="{col}" stroke-width="2.4" stroke-dasharray="2 7" stroke-linecap="round"/>'
    b += box(60, 800 - 150, 1280, 110, '', G['g50'], stroke=G['g200'], shadow=False)
    b += text(700, 680, 'Cross-cutting: Google sign-in (@aau.ac.ae) · Roles (student / teacher / admin) · Notifications · Direct messages · Moderation · Admin dashboard · Multi-university ready', 14, 700, G['g800'])
    b += text(700, 712, 'Security: CSRF tokens · prepared statements · output escaping · role-based access control · upload validation', 13, 500, G['muted'])
    save('figure-19-modules', 1400, 800, b, 'Figure 19 — Functional modules of MANBAR')


# ------------------------------------------------------------------ 20 context DFD
def fig_context():
    b = ''
    ents = [('Student', 90, 130), ('Teacher / Mentor', 90, 390), ('Administrator', 90, 650), ('Google\nAccounts', 1010, 130), ('Claude API\n(optional)', 1010, 390), ('University\nroster (CSV)', 1010, 650)]
    for n, x, y in ents:
        b += box(x, y, 200, 90, n, G['g100'], stroke=G['g500'], r=8, size=15)
    b += f'<circle cx="650" cy="470" r="150" fill="url(#hd)" filter="url(#sh)"/>' + text(650, 450, 'MANBAR', 28, 800, '#fff') + text(650, 485, 'Student platform\n(0)', 14, 600, '#e6fff0')
    flows = [
        (290, 160, 500, 400, 'ideas, comments, applications', False), (500, 430, 290, 190, 'feeds, profiles, notifications', False),
        (290, 420, 500, 460, 'courses, mentor profile, offers', False), (510, 500, 290, 460, 'requests, enrolments', False),
        (290, 680, 520, 560, 'moderation, settings', False), (520, 590, 290, 710, 'reports, analytics', False),
        (1010, 160, 780, 400, 'verified e-mail + name', False), (790, 430, 1010, 190, 'sign-in request', False),
        (1010, 420, 790, 470, 'corrected text, best page', False), (790, 500, 1010, 450, 'draft text', False),
        (1010, 680, 780, 540, 'student no. → name, major', False),
    ]
    for x1, y1, x2, y2, l, d in flows:
        b += arrow(x1, y1, x2, y2, l, dashed=d, lab_dy=-6)
    save('figure-20-context-diagram', 1300, 820, b, 'Figure 20 — Context diagram (Level 0 data-flow)')


# ------------------------------------------------------------------ 21 ER
def fig_er():
    T = {
        'universities': (1, 0, ['PK id', 'name', 'domain (unique)', 'active']),
        'roster': (0, 0, ['PK id', 'FK university_id', 'student_id', 'full_name', 'major · faculty · year']),
        'users': (2, 1, ['PK id', 'FK university_id', 'email (unique)', 'student_id', 'full_name', 'role · status · verified', 'points · profile fields']),
        'user_skills': (3, 0, ['PK id', 'FK user_id', 'name', 'kind (skill / interest)']),
        'follows': (1, 1, ['FK follower_id', 'FK followed_id']),
        'posts': (0, 1, ['PK id', 'FK user_id', 'type', 'title · body · tags', 'image · status · pinned', 'share_of · project_id']),
        'comments': (1, 2, ['PK id', 'FK post_id', 'FK user_id', 'parent_id', 'body']),
        'reactions': (0, 2, ['PK id', 'FK user_id', 'target_type · target_id', 'kind']),
        'bookmarks': (0, 3, ['FK user_id', 'FK post_id']),
        'notifications': (3, 1, ['PK id', 'FK user_id', 'type · text · link', 'is_read']),
        'messages': (4, 1, ['PK id', 'FK sender_id', 'FK receiver_id', 'body · is_read']),
        'badges': (5, 0, ['PK id', 'code · name', 'description · icon']),
        'user_badges': (5, 1, ['FK user_id', 'FK badge_id']),
        'projects': (2, 2, ['PK id', 'FK owner_id', 'title · description', 'needed_skills', 'status · max_members']),
        'project_members': (1, 3, ['FK project_id', 'FK user_id', 'role']),
        'project_applications': (2, 3, ['PK id', 'FK project_id', 'FK user_id', 'message · status']),
        'project_tasks': (3, 3, ['PK id', 'FK project_id', 'assignee_id', 'title · status · due']),
        'services': (4, 2, ['PK id', 'FK user_id', 'title · category', 'price · delivery_days', 'status']),
        'service_requests': (4, 3, ['PK id', 'FK service_id', 'FK buyer_id', 'status']),
        'service_reviews': (5, 3, ['PK id', 'FK service_id', 'FK reviewer_id', 'rating · comment']),
        'courses': (3, 2, ['PK id', 'FK author_id', 'title · category', 'level · status']),
        'lessons': (3, 4, ['PK id', 'FK course_id', 'position · title', 'content · video_url']),
        'enrollments': (4, 4, ['FK course_id', 'FK user_id', 'completed_at']),
        'mentor_profiles': (5, 2, ['PK/FK user_id', 'expertise · about', 'availability · active']),
        'mentorship_requests': (5, 4, ['PK id', 'FK mentor_id', 'FK student_id', 'topic · status', 'session_at · feedback']),
        'reports': (2, 0, ['PK id', 'FK reporter_id', 'target_type · target_id', 'reason · status']),
    }
    W, H, GX, GY, X0, Y0 = 250, 168, 70, 50, 40, 70
    pos = {k: (X0 + c * (W + GX), Y0 + r * (H + GY)) for k, (c, r, _) in T.items()}
    rel = [('roster', 'universities'), ('users', 'universities'), ('user_skills', 'users'), ('follows', 'users'), ('posts', 'users'), ('comments', 'posts'), ('comments', 'users'), ('reactions', 'users'),
           ('bookmarks', 'posts'), ('notifications', 'users'), ('messages', 'users'), ('user_badges', 'users'), ('user_badges', 'badges'), ('projects', 'users'), ('project_members', 'projects'),
           ('project_applications', 'projects'), ('project_tasks', 'projects'), ('services', 'users'), ('service_requests', 'services'), ('service_reviews', 'services'), ('courses', 'users'),
           ('lessons', 'courses'), ('enrollments', 'courses'), ('mentor_profiles', 'users'), ('mentorship_requests', 'mentor_profiles'), ('reports', 'users')]
    b = ''
    for a, c in rel:
        ax, ay = pos[a]; cx_, cy_ = pos[c]
        ca = (ax + W / 2, ay + H / 2); cb = (cx_ + W / 2, cy_ + H / 2)
        p1 = clip(ca[0], ca[1], W, H, cb[0], cb[1]); p2 = clip(cb[0], cb[1], W, H, ca[0], ca[1])
        b += f'<line x1="{p1[0]:.1f}" y1="{p1[1]:.1f}" x2="{p2[0]:.1f}" y2="{p2[1]:.1f}" stroke="#8da498" stroke-width="1.8"/>'
        b += f'<circle cx="{p2[0]:.1f}" cy="{p2[1]:.1f}" r="5" fill="{G["g600"]}"/><text x="{p1[0] + (p2[0] - p1[0]) * .12:.1f}" y="{p1[1] + (p2[1] - p1[1]) * .12 - 4:.1f}" font-size="11" font-weight="700" fill="{G["g800"]}">N</text>'
        b += f'<text x="{p1[0] + (p2[0] - p1[0]) * .88:.1f}" y="{p1[1] + (p2[1] - p1[1]) * .88 - 4:.1f}" font-size="11" font-weight="700" fill="{G["g800"]}">1</text>'
    tones = {'users': G['g700'], 'universities': G['g700'], 'roster': G['g700']}
    for k, (c, r, cols) in T.items():
        x, y = pos[k]
        tone = tones.get(k) or (G['amber'] if k in ('posts', 'comments', 'reactions', 'bookmarks') else G['g500'] if k.startswith('project') else G['orange'] if k.startswith('service') else G['teal'] if k in ('courses', 'lessons', 'enrollments') else G['violet'] if k.startswith('mentor') else G['rose'] if k in ('badges', 'user_badges') else G['blue'])
        b += head_box(x, y, W, H, k, cols, tone)
    save('figure-21-er-diagram', X0 * 2 + 6 * W + 5 * GX, Y0 + 5 * (H + GY) + 10, b, 'Figure 21 — Entity-relationship diagram (26 core entities of the 32-table schema)')


# ------------------------------------------------------------------ 22 sequence
def fig_sequence():
    names = ['Student', 'Browser', 'MANBAR (PHP)', 'Google', 'MySQL']
    xs = [110, 340, 590, 840, 1070]
    b = ''
    for n, x in zip(names, xs):
        b += box(x - 80, 70, 160, 46, n, 'url(#hd)', stroke='none', color='#fff', r=10)
        b += f'<line x1="{x}" y1="116" x2="{x}" y2="850" stroke="#b8d8c6" stroke-width="2" stroke-dasharray="6 6"/>'
    steps = [
        (0, 1, 'Open MANBAR → "Sign in with Google"'), (1, 3, 'Google account chooser (@aau.ac.ae)'), (3, 1, 'ID token (signed JWT)'),
        (1, 2, 'POST /auth/google  (token + CSRF cookie)'), (2, 3, 'Verify token (aud, iss, exp, e-mail verified)'), (3, 2, 'Verified e-mail + name + photo'),
        (2, 2, 'Check domain is an active university'), (2, 4, 'Find user by e-mail'), (4, 2, 'Not found → first login'),
        (2, 4, 'Look up student no. 202020280 in roster'), (4, 2, 'Full name, major, faculty, year'), (2, 4, 'INSERT user (role from e-mail pattern)'),
        (2, 1, 'Start session → redirect /onboarding'), (1, 0, 'Pre-filled profile form'), (0, 1, 'Add skills, bio → submit'), (1, 2, 'Save profile (+20 points, badge)'), (2, 1, 'Redirect to /feed'),
    ]
    y = 160
    for a, c, label in steps:
        if a == c:
            b += f'<rect x="{xs[a]}" y="{y-14}" width="36" height="28" rx="6" fill="{G["g100"]}" stroke="{G["g300"]}"/>' + text(xs[a] + 50, y, label, 12.5, 600, G['g800'], anchor='start')
        else:
            dashed = c < a
            b += arrow(xs[a], y, xs[c], y, '', dashed=dashed) + text((xs[a] + xs[c]) / 2, y - 12, label, 12.5, 600, G['ink'])
        y += 40
    save('figure-22-google-login-sequence', 1180, 880, b, 'Figure 22 — Sign-in with Google and roster-based profile filling')


# ------------------------------------------------------------------ 23 AI flow
def fig_ai():
    b = box(430, 70, 280, 56, 'Student writes a post / describes an idea', G['g100'], stroke=G['g500'], r=28, size=14)
    b += arrow(570, 126, 570, 170)
    b += box(430, 170, 280, 58, 'Click “AI assist” or ask the Guide', '#fff')
    b += arrow(570, 228, 570, 272)
    b += f'<polygon points="570,272 700,330 570,388 440,330" fill="#fff" stroke="{G["blue"]}" stroke-width="2" filter="url(#sh)"/>' + text(570, 330, 'Claude API key\nconfigured?', 13, 700)
    b += arrow(700, 330, 820, 330, 'yes') + arrow(440, 330, 320, 330, 'no')
    b += box(820, 296, 260, 68, 'Claude: fix text + choose best page', '#f1ecff', stroke=G['violet'], size=13)
    b += box(60, 296, 260, 68, 'Rule engine', G['g100'], stroke=G['g500'], size=14, sub='dictionary · casing · punctuation')
    b += arrow(190, 364, 190, 440) + arrow(950, 364, 950, 440)
    b += box(60, 440, 260, 66, '1  Writing fixer', '#fff', sub='typos · "i"→"I" · spacing · full stop')
    b += box(60, 540, 260, 66, '2  Page router', '#fff', sub='keyword scoring → best target')
    b += arrow(190, 506, 190, 540)
    b += box(820, 440, 260, 66, 'JSON: corrected · target · tags', '#fff', size=13)
    b += arrow(320, 573, 430, 640) + arrow(950, 506, 710, 640)
    b += box(430, 640, 280, 62, 'Suggestion panel', G['g100'], stroke=G['g500'], sub='diff + best place + tags')
    b += arrow(570, 702, 570, 746)
    b += f'<polygon points="570,746 700,800 570,854 440,800" fill="#fff" stroke="{G["g500"]}" stroke-width="2" filter="url(#sh)"/>' + text(570, 800, 'Student\ndecision', 13, 700)
    b += arrow(440, 800, 300, 800, 'apply fixes') + arrow(700, 800, 840, 800, 'go to page')
    b += box(60, 770, 240, 60, 'Text updated in composer', '#fff', size=13) + box(840, 770, 260, 60, 'Open page with draft pre-filled', '#fff', size=13, sub='Marketplace · Projects · Courses · Mentors')
    b += arrow(570, 854, 570, 900, 'publish') + box(430, 900, 280, 50, 'Post published (+10 pts)', G['g100'], stroke=G['g500'], r=25)
    save('figure-23-ai-assistant-flow', 1160, 990, b, 'Figure 23 — AI assistant: auto-correct and guide to the right page')


# ------------------------------------------------------------------ 24 sitemap
def fig_sitemap():
    b = box(560, 70, 180, 54, 'Landing page', G['g100'], stroke=G['g500'])
    b += arrow(650, 124, 650, 170) + box(560, 170, 180, 54, 'Sign in with Google', '#fff') + arrow(650, 224, 650, 270) + box(560, 270, 180, 54, 'Onboarding (first time)', '#fff') + arrow(650, 324, 650, 370)
    b += box(540, 370, 220, 60, 'Home feed', 'url(#hd)', stroke='none', color='#fff', size=16)
    cols = [
        ('Feed', G['amber'], ['Compose + AI assist', 'Post detail · comments', 'Saved posts', 'Search']),
        ('Projects', G['g500'], ['Browse & filter', 'Start a project', 'Project: team · tasks · chat', 'Applications']),
        ('Marketplace', G['orange'], ['Browse services', 'Offer a service', 'Service · reviews', 'My orders']),
        ('Learning', G['teal'], ['Course catalogue', 'Course · lessons', 'Create course (teacher)', 'Progress']),
        ('Mentors', G['violet'], ['Mentor directory', 'Mentor profile', 'My mentorship', 'Sessions & feedback']),
        ('You', G['blue'], ['My profile · edit', 'People directory', 'Achievements', 'Messages · Notifications']),
        ('Admin', G['rose'], ['Dashboard', 'Users · Roster', 'Moderation · Content', 'Universities · Settings']),
    ]
    for i, (n, col, items) in enumerate(cols):
        x = 40 + i * 180
        b += poly([(650, 430), (650, 470), (x + 80, 470), (x + 80, 500)])
        b += box(x, 500, 160, 52, n, col, stroke='none', color='#fff', size=15, weight=800)
        for j, it in enumerate(items):
            b += box(x + 8, 585 + j * 70, 144, 54, it, '#fff', stroke=col, size=12, r=10)
    save('figure-24-sitemap', 1320, 900, b, 'Figure 24 — Site map and navigation structure')


# ------------------------------------------------------------------ use cases
def build_usecase(name, title, actor, cases, extends=(), w=1120):
    row = 54
    n_main = len([c for c in cases if c[1] == 0])
    H = max(300, n_main * row + 150)
    boundary = f'<rect x="330" y="64" width="{w-360}" height="{H-84}" rx="14" fill="{G["g50"]}" stroke="{G["g500"]}" stroke-width="2"/>' + text(330 + (w - 360) / 2, 90, 'MANBAR System', 14, 800, G['g800'])
    ay = 64 + (H - 84) / 2
    actor_svg = (f'<circle cx="150" cy="{ay-50}" r="20" fill="#fff" stroke="{G["g800"]}" stroke-width="2.4"/>'
                 f'<line x1="150" y1="{ay-30}" x2="150" y2="{ay+25}" stroke="{G["g800"]}" stroke-width="2.4"/><line x1="105" y1="{ay-12}" x2="195" y2="{ay-12}" stroke="{G["g800"]}" stroke-width="2.4"/>'
                 f'<line x1="150" y1="{ay+25}" x2="112" y2="{ay+80}" stroke="{G["g800"]}" stroke-width="2.4"/><line x1="150" y1="{ay+25}" x2="188" y2="{ay+80}" stroke="{G["g800"]}" stroke-width="2.4"/>' + text(150, ay + 112, actor, 15, 800, G['g900']))
    pos = {}
    main = [c for c in cases if c[1] == 0]
    step = (H - 150) / max(1, len(main))
    for i, (label, _) in enumerate(main):
        pos[label] = (545, 110 + step * i + step / 2 + 10)
    used = {}
    for label, col in cases:
        if col == 1:
            tgt = [b for a, b in extends if a == label][0]
            k = used.get(tgt, 0); used[tgt] = k + 1
            pos[label] = (930, pos[tgt][1] + (k - 0.0) * 46 - (0 if k == 0 else 0))
    lines = ''
    for label, col in cases:
        if col == 0:
            x, y = pos[label]
            lines += f'<line x1="190" y1="{ay-10}" x2="{x-122}" y2="{y}" stroke="#8da498" stroke-width="1.5"/>'
    for a, c in extends:
        (x1, y1), (x2, y2) = pos[a], pos[c]
        lines += f'<line x1="{x1-104}" y1="{y1}" x2="{x2+122}" y2="{y2}" stroke="#8da498" stroke-width="1.6" stroke-dasharray="6 5" marker-end="url(#ar2)"/>' + text((x1 + x2) / 2 + 2, (y1 + y2) / 2 - 8, '«extend»', 10.5, 600, G['muted'])
    shapes = ''
    for label, col in cases:
        x, y = pos[label]
        shapes += f'<ellipse cx="{x}" cy="{y}" rx="{122 if col == 0 else 104}" ry="23" fill="{"#fff8e6" if col else "#fff"}" stroke="{G["amber"] if col else G["g500"]}" stroke-width="1.8"/>' + text(x, y, label, 11.5, 600)
    save(name, w, H + 10, boundary + lines + actor_svg + shapes, title)


def fig_usecase_all():
    build_usecase('figure-25-usecase-student', 'Figure 25 — Student use cases (updated)', 'Student', [
        ('Sign in with Google (@aau.ac.ae)', 0), ('Complete profile (auto-filled from roster)', 0), ('Post idea / question / resource', 0), ('React · comment · share · save', 0),
        ('Follow students and teachers', 0), ('Search people directory (skills)', 0), ('Apply to join a project', 0), ('Offer / request a service', 0), ('Enroll in a course', 0),
        ('Request mentorship', 0), ('Message other users', 0), ('View achievements & leaderboard', 0), ('Use AI assistant', 0),
        ('Auto-correct text', 1), ('Suggest best page', 1), ('Leave rating & review', 1), ('Send application message', 1), ('Track lesson progress', 1),
    ], [('Auto-correct text', 'Use AI assistant'), ('Suggest best page', 'Use AI assistant'), ('Leave rating & review', 'Offer / request a service'), ('Send application message', 'Apply to join a project'), ('Track lesson progress', 'Enroll in a course')])
    build_usecase('figure-26-usecase-teacher', 'Figure 26 — Teacher / Mentor use cases (new: marketplace for teaching)', 'Teacher / Mentor', [
        ('Sign in with university e-mail', 0), ('Create mentor profile & availability', 0), ('Publish announcements & events', 0), ('Post teaching offers (tutoring / office hours)', 0),
        ('List services in the marketplace', 0), ('Create courses and lessons', 0), ('Review mentorship requests', 0), ('Schedule sessions', 0), ('Give feedback on student projects', 0),
        ('Message students', 0), ('Accept request', 1), ('Reject request', 1), ('Provide feedback', 1),
    ], [('Accept request', 'Review mentorship requests'), ('Reject request', 'Review mentorship requests'), ('Provide feedback', 'Give feedback on student projects')])
    build_usecase('figure-27-usecase-admin', 'Figure 27 — Administrator use cases (updated)', 'Administrator', [
        ('Secure login', 0), ('View dashboard & analytics', 0), ('Manage users (role · suspend · delete)', 0), ('Verify university accounts', 0), ('Manage student roster (CSV import)', 0),
        ('Moderate posts and comments', 0), ('Moderate projects, services, courses', 0), ('Review reports', 0), ('Manage universities (enable domains)', 0), ('Platform settings & notice', 0), ('Audit log', 0),
        ('Hide / restore content', 1), ('Export users (CSV)', 1), ('Pin posts', 1),
    ], [('Hide / restore content', 'Moderate posts and comments'), ('Export users (CSV)', 'Manage users (role · suspend · delete)'), ('Pin posts', 'Moderate posts and comments')])


# ------------------------------------------------------------------ 28 deployment
def fig_deploy():
    b = f'<rect x="40" y="80" width="520" height="420" rx="22" fill="{G["g50"]}" stroke="{G["g200"]}"/>' + text(300, 106, 'PHASE 1 — Local (XAMPP + VS Code)', 14, 800, G['g700'])
    b += f'<rect x="640" y="80" width="560" height="420" rx="22" fill="{G["g50"]}" stroke="{G["g200"]}"/>' + text(920, 106, 'PHASE 2 — Public server', 14, 800, G['g700'])
    b += box(70, 140, 220, 80, 'Developer PC', G['g100'], sub='VS Code · Git')
    b += box(70, 260, 220, 80, 'XAMPP Apache + PHP', '#fff', sub='htdocs/manbar')
    b += box(70, 380, 220, 80, 'XAMPP MySQL', '#fff', sub='phpMyAdmin · manbar.sql')
    b += box(320, 260, 210, 80, 'Browser', '#fff', sub='http://localhost/manbar/public')
    b += arrow(180, 220, 180, 260) + arrow(180, 340, 180, 380, '', both=True) + arrow(320, 300, 290, 300, '', both=True)
    b += box(670, 140, 240, 80, 'GitHub repository', G['g100'], sub='oblithub/obliterate')
    b += box(950, 140, 220, 80, 'CI / deploy', '#fff', sub='git pull · install.php')
    b += box(670, 260, 240, 80, 'Web server', '#fff', sub='Apache / Nginx + PHP 8.3 · HTTPS')
    b += box(950, 260, 220, 80, 'Managed MySQL', '#fff', sub='backups · utf8mb4')
    b += box(670, 380, 240, 80, 'Users', G['g100'], sub='AAU → ADU → UAEU → public')
    b += box(950, 380, 220, 80, 'Google OAuth', '#fff', stroke=G['blue'], sub='authorised origin + HTTPS')
    b += arrow(910, 180, 950, 180) + arrow(1060, 220, 1060, 260) + arrow(790, 220, 790, 260) + arrow(910, 300, 950, 300, '', both=True) + arrow(790, 340, 790, 380, '', both=True) + arrow(910, 420, 950, 420, '', both=True)
    b += arrow(530, 190, 670, 175, 'git push', color=G['g500'])
    save('figure-28-deployment', 1240, 560, b, 'Figure 28 — Deployment: from XAMPP to a public server')


# ------------------------------------------------------------------ 29 gantt
def fig_gantt():
    import datetime as dt
    D = dt.date
    tasks = [
        ('Project: MANBAR', D(2026, 2, 19), D(2026, 2, 19), 'cap1'), ('Capstone 1', D(2026, 2, 19), D(2026, 2, 19), 'cap1'),
        ('Domain study', D(2026, 2, 20), D(2026, 3, 9), 'cap1'), ('Requirements documentation & diagrams', D(2026, 3, 10), D(2026, 3, 23), 'cap1'),
        ('Final report to advisor', D(2026, 3, 24), D(2026, 3, 26), 'cap1'), ('Final report to examiners', D(2026, 3, 27), D(2026, 4, 2), 'cap1'),
        ('Final presentation — Capstone 1', D(2026, 4, 3), D(2026, 4, 15), 'cap1'),
        ('Capstone 2', D(2026, 9, 7), D(2026, 9, 7), 'cap2'), ('Design and prototype', D(2026, 9, 8), D(2026, 9, 29), 'cap2'), ('Implementation', D(2026, 9, 30), D(2026, 10, 23), 'cap2'),
        ('Testing', D(2026, 10, 26), D(2026, 11, 5), 'cap2'), ('Full system', D(2026, 11, 6), D(2026, 11, 13), 'cap2'), ('Final report to advisor', D(2026, 11, 16), D(2026, 11, 20), 'cap2'),
        ('Final report to examiners', D(2026, 11, 23), D(2026, 11, 26), 'cap2'), ('Final presentation — Capstone 2', D(2026, 11, 27), D(2026, 12, 1), 'cap2'),
    ]
    # compress the Apr–Sep gap
    def X(d):
        base = D(2026, 2, 15)
        if d <= D(2026, 4, 20):
            return 330 + (d - base).days * 7.2
        gap = 330 + (D(2026, 4, 20) - base).days * 7.2
        return gap + 60 + (d - D(2026, 9, 1)).days * 7.2
    b = ''
    x_end = X(D(2026, 12, 5))
    # month headers
    months = [(D(2026, m, 1), n) for m, n in [(2, 'Feb'), (3, 'Mar'), (4, 'Apr'), (9, 'Sep'), (10, 'Oct'), (11, 'Nov'), (12, 'Dec')]]
    for d, n in months:
        x = X(d) if d >= D(2026, 2, 15) else X(D(2026, 2, 15))
        b += f'<line x1="{x}" y1="90" x2="{x}" y2="{110 + len(tasks) * 40 + 10}" stroke="#e1f6e8" stroke-width="2"/>' + text(x + 24, 80, n + " '26", 12, 700, G['muted'])
    gx = X(D(2026, 4, 20))
    b += f'<rect x="{gx}" y="90" width="60" height="{len(tasks) * 40 + 30}" fill="#f4f7f5"/>' + text(gx + 30, 90 + len(tasks) * 20, 'summer\nbreak', 11, 600, G['muted'])
    for i, (n, s, e_, ph) in enumerate(tasks):
        y = 120 + i * 40
        col = G['g500'] if ph == 'cap1' else G['blue']
        b += text(20, y + 12, n, 13, 600, G['ink'], anchor='start')
        x1, x2 = X(s), X(e_ + dt.timedelta(days=1))
        if (e_ - s).days == 0:
            b += f'<polygon points="{x1},{y+12} {x1+10},{y+2} {x1+20},{y+12} {x1+10},{y+22}" fill="{G["g900"]}"/>'
        else:
            b += f'<rect x="{x1}" y="{y}" width="{max(8, x2-x1)}" height="24" rx="8" fill="{col}"/>' + text(x1 + (x2 - x1) / 2, y + 12, f'{(e_-s).days+1}d', 11, 700, '#fff')
    t = X(D(2026, 10, 2))
    b += f'<line x1="{t}" y1="96" x2="{t}" y2="{110 + len(tasks) * 40 + 8}" stroke="{G["rose"]}" stroke-width="2.5" stroke-dasharray="6 5"/><rect x="{t-36}" y="{110 + len(tasks) * 40 + 10}" width="72" height="22" rx="8" fill="{G["rose"]}"/>' + text(t, 110 + len(tasks) * 40 + 21, 'Today', 12, 800, '#fff')
    b += f'<rect x="20" y="{130 + len(tasks) * 40 + 30}" width="16" height="16" rx="4" fill="{G["g500"]}"/>' + text(44, 130 + len(tasks) * 40 + 38, 'Capstone 1 (complete)', 12, 600, G['ink'], anchor='start')
    b += f'<rect x="230" y="{130 + len(tasks) * 40 + 30}" width="16" height="16" rx="4" fill="{G["blue"]}"/>' + text(254, 130 + len(tasks) * 40 + 38, 'Capstone 2 (implementation)', 12, 600, G['ink'], anchor='start')
    save('figure-29-gantt', int(x_end) + 40, 130 + len(tasks) * 40 + 80, b, 'Figure 29 — Project plan (Gantt chart, updated for Capstone 2)')


# ------------------------------------------------------------------ 30 gamification
def fig_gamify():
    b = ''
    levels = [('Newcomer', 0), ('Explorer', 50), ('Contributor', 150), ('Innovator', 300), ('Pioneer', 600), ('Legend', 1000)]
    for i, (n, p) in enumerate(levels):
        x = 60 + i * 190
        h = 60 + i * 38
        b += f'<rect x="{x}" y="{400-h}" width="160" height="{h}" rx="12" fill="{[G["g200"], G["g300"], "#6fd09a", G["g500"], G["g600"], G["g800"]][i]}"/>' + text(x + 80, 400 - h + 24, f'Level {i+1}', 12, 700, '#fff' if i > 1 else G['g900']) + text(x + 80, 400 - h + 46, n, 16, 800, '#fff' if i > 1 else G['g900']) + text(x + 80, 420, f'{p}+ pts', 12, 600, G['muted'])
    b += '<path d="M60,372 L1100,70" stroke="#8da498" stroke-width="2" stroke-dasharray="6 6" fill="none" marker-end="url(#ar2)"/>'
    pts = [('Complete profile', '+20'), ('Publish a post', '+10'), ('Start a project', '+15'), ('Join a project', '+10'), ('Complete a project', '+30'), ('Comment', '+3'), ('Reaction received', '+1'), ('List a service', '+10'), ('Finish a course', '+25'), ('Mentoring session', '+5/+20')]
    for i, (a, p) in enumerate(pts):
        x = 60 + (i % 5) * 220
        y = 480 + (i // 5) * 62
        b += box(x, y, 200, 48, '', '#fff', r=12) + text(x + 14, y + 24, a, 12.5, 600, G['ink'], anchor='start') + text(x + 186, y + 24, p, 14, 800, G['g600'], anchor='end')
    save('figure-30-points-and-levels', 1200, 640, b, 'Figure 30 — Achievement system: points and levels')


if __name__ == '__main__':
    fig_architecture(); fig_modules(); fig_context(); fig_er(); fig_sequence(); fig_ai(); fig_sitemap(); fig_usecase_all(); fig_deploy(); fig_gantt(); fig_gamify()
