<?php
/** Demo data (students, teachers, posts, projects, services, courses...). Run: php database/install.php --fresh --demo */

function seed_demo(): void
{
    $ago = fn(string $s) => date('Y-m-d H:i:s', strtotime("-$s"));
    $uni = 1;

    // ---- users -------------------------------------------------------------------------------
    $people = [
        // id, name, role, major/department, headline, skills, interests, theme, points
        ['admin', 'MANBAR Admin', 'admin', 'IT Services', 'Platform administrator', ['Moderation', 'Operations'], ['Community'], 0, 0],
        ['layla.hassan', 'Dr. Layla Hassan', 'teacher', 'Computer Science', 'Assistant Professor · AI & Data Science', ['Machine Learning', 'Python', 'Research'], ['EdTech', 'Mentoring'], 1, 0],
        ['ahmed.mansour', 'Prof. Ahmed Mansour', 'teacher', 'Software Engineering', 'Lecturer · Software architecture & web', ['Software Architecture', 'JavaScript', 'Databases'], ['Startups', 'Open source'], 3, 0],
        ['rania.saleh', 'Dr. Rania Saleh', 'teacher', 'Business Administration', 'Entrepreneurship & innovation lecturer', ['Business Plans', 'Marketing', 'Pitching'], ['Startups', 'Design thinking'], 2, 0],
        ['202310101', 'Mariam Al Mansoori', 'student', 'Software Engineering', 'UI/UX enthusiast · Future product designer', ['Figma', 'UI Design', 'HTML/CSS'], ['Design', 'Photography'], 4, 0],
        ['202310202', 'Omar Al Nuaimi', 'student', 'Computer Science', 'Backend developer in the making', ['Python', 'SQL', 'APIs'], ['AI', 'Gaming'], 0, 0],
        ['202310303', 'Fatima Al Kaabi', 'student', 'Business Administration', 'Marketing student · Content creator', ['Marketing', 'Copywriting', 'Canva'], ['Social media', 'Startups'], 5, 0],
        ['202310404', 'Khalid Al Suwaidi', 'student', 'Software Engineering', 'Mobile apps · Flutter', ['Flutter', 'Dart', 'Firebase'], ['Mobile', 'Hackathons'], 1, 0],
        ['202310505', 'Noura Al Hammadi', 'student', 'Pharmacy', 'Pharmacy student · Health tech curious', ['Research', 'Data analysis'], ['Health', 'Wellness'], 2, 0],
        ['202310606', 'Saeed Al Dhaheri', 'student', 'Civil Engineering', 'Sustainable design & 3D modelling', ['AutoCAD', 'SketchUp', 'Project planning'], ['Sustainability'], 3, 0],
        ['202310707', 'Aisha Rahman', 'student', 'Media & Communication', 'Video editor · Storyteller', ['Video editing', 'Premiere', 'Storytelling'], ['Film', 'Journalism'], 4, 0],
        ['202310808', 'Hamdan Al Ketbi', 'student', 'Computer Science', 'Cybersecurity & networks', ['Linux', 'Networking', 'Python'], ['CTF', 'Security'], 0, 0],
        ['202310909', 'Lina Haddad', 'student', 'English Literature', 'Writer · Translator (AR/EN)', ['Translation', 'Proofreading', 'Creative writing'], ['Books', 'Poetry'], 5, 0],
        ['202311010', 'Yousef Khan', 'student', 'Software Engineering', 'Full-stack developer · Open-source fan', ['JavaScript', 'React', 'Node.js'], ['Open source', 'Startups'], 1, 0],
    ];
    $faculty = ['Computer Science' => 'College of Engineering', 'Software Engineering' => 'College of Engineering', 'Civil Engineering' => 'College of Engineering', 'Business Administration' => 'College of Business', 'Pharmacy' => 'College of Pharmacy', 'Media & Communication' => 'College of Media & Mass Communication', 'English Literature' => 'College of Education, Humanities & Social Sciences', 'IT Services' => 'Other'];
    $U = [];
    foreach ($people as $i => [$id, $name, $role, $major, $headline, $skills, $interests, $theme]) {
        $email = $id . '@aau.ac.ae';
        $isStu = $role === 'student';
        if ($isStu) {
            insert('roster', ['university_id' => $uni, 'student_id' => $id, 'full_name' => $name, 'major' => $major, 'faculty' => $faculty[$major] ?? null, 'year_level' => random_int(1, 4), 'created_at' => $ago('60 days')]);
        }
        $uid = insert('users', [
            'university_id' => $uni, 'email' => $email, 'student_id' => $isStu ? $id : null, 'full_name' => $name, 'role' => $role, 'verified' => 1,
            'theme' => $theme, 'headline' => $headline, 'bio' => $isStu ? "Hi! I'm $name. I'm studying $major at Al Ain University and I love building things with other students." : "$headline at Al Ain University. Happy to help students with ideas, projects and career questions.",
            'major' => $isStu ? $major : null, 'department' => $isStu ? null : $major, 'faculty' => $faculty[$major] ?? null, 'year_level' => $isStu ? random_int(1, 4) : null,
            'profile_complete' => 1, 'created_at' => $ago(($i + 3) * 2 . ' days'), 'last_login' => $ago('1 hour'),
        ]);
        $U[$id] = $uid;
        foreach ($skills as $s) insert('user_skills', ['user_id' => $uid, 'name' => $s, 'kind' => 'skill']);
        foreach ($interests as $s) insert('user_skills', ['user_id' => $uid, 'name' => $s, 'kind' => 'interest']);
        if (!$isStu && $role === 'teacher') {
            insert('mentor_profiles', ['user_id' => $uid, 'expertise' => implode(', ', $skills), 'about' => "I enjoy guiding students through capstone topics, research methods and early-career decisions. Come with a question and a goal — we'll figure out the next step together.", 'availability' => ['Sun & Tue 2–4 pm', 'Mon & Wed 11 am–1 pm', 'Thu 10 am–12 pm'][$i % 3], 'active' => 1]);
        }
    }
    $u = fn(string $k) => $U[$k];

    // ---- follows -----------------------------------------------------------------------------
    $ids = array_values($U);
    foreach ($ids as $a) foreach ($ids as $b) if ($a !== $b && random_int(0, 100) < 38) insert('follows', ['follower_id' => $a, 'followed_id' => $b, 'created_at' => $ago(random_int(1, 20) . ' days')]);

    // ---- posts -------------------------------------------------------------------------------
    $posts = [
        ['202310101', 'idea', 'A study-buddy matcher for finals week 📚', "What if students could be matched with study partners based on their courses, schedule and learning style? Imagine opening MANBAR the night before an exam and instantly finding 2–3 people revising the same chapter.\n\nI have a rough UI in Figma already. Looking for a backend dev and someone who can think about the matching logic. #edtech #web", 'edtech,web,design', '3 hours'],
        ['202310404', 'team', 'Need 2 teammates for the UAE hackathon 🚀', "We are 2 mobile developers building a campus-event discovery app (Flutter + Firebase) for the upcoming national hackathon.\n\nLooking for: a UI/UX designer and someone comfortable pitching in front of judges. Deadline is in 3 weeks. Reply here or message me!", 'hackathon,mobile,flutter', '5 hours'],
        ['202310202', 'question', 'Best way to learn SQL joins properly?', "I keep mixing up LEFT and INNER joins in my database course and I fail the lab questions. Does anyone have a good visual explanation or practice site? Thanks in advance!", 'sql,database', '9 hours'],
        ['layla.hassan', 'announcement', 'AI Club kick-off & research opportunities', "Attention students: the AI & Data Science club is opening its first semester meeting next Wednesday at 1 pm in Lab B-204. We will present three research mini-projects that need student assistants. Bring your laptop and your curiosity.\n\nNo prior experience needed — we will teach you. #ai #research", 'ai,research,club', '1 day'],
        ['202310707', 'achievement', 'Our short film just got selected for the campus film festival! 🎬', "After 6 weeks of writing, shooting and editing with an amazing team from Media and Engineering, our 7-minute short “Echoes of Al Ain” has been selected for the campus film festival.\n\nHuge thanks to everyone who helped with sound and lighting. Screening is on the 14th!", 'film,video,festival', '1 day'],
        ['202310303', 'resource', 'Free Canva templates for student posters & pitch decks', "I made a pack of 12 clean Canva templates (posters, pitch decks, social posts) with our university colours. Free for any student project. Comment “templates” and I will share the link, or find it in my marketplace listing. #design #marketing", 'design,marketing,canva', '2 days'],
        ['ahmed.mansour', 'teaching', 'Free weekly web development clinic (HTML, CSS, JavaScript)', "Every Monday 3–5 pm in the Software Lab I will hold an open clinic for anyone stuck with web development — bring your code, your questions or your capstone. First-years especially welcome. #web #javascript", 'web,javascript,tutoring', '2 days'],
        ['202311010', 'idea', 'Open-source campus map with indoor navigation 🗺️', "New students always get lost finding classrooms and labs. What if we built an open-source indoor map for all AAU buildings with search (“Where is B-204?”) and walking directions?\n\nI can handle the frontend (React + Leaflet). We would need volunteers to collect floor plans and room data.", 'opensource,web,maps', '3 days'],
        ['202310808', 'event', 'Capture The Flag workshop — beginner friendly 🏴', "Cybersecurity club is running a beginner CTF workshop this Thursday at 4 pm. We will solve a few web and crypto challenges together. No experience required, just bring a laptop and install a Linux VM beforehand. #security #ctf", 'security,ctf,workshop', '3 days'],
        ['202310909', 'idea', 'A bilingual (Arabic / English) peer proofreading circle', "Many of us write reports in a second language and feel unsure about the quality. What about a peer-proofreading circle where students swap short documents and give feedback? I can help with English and Arabic editing. #writing", 'writing,languages', '4 days'],
        ['202310606', 'team', 'Looking for a data person — campus energy usage project', "I am working on a sustainability study measuring electricity use in our buildings. I have access to monthly meter data (anonymised) but need someone who can clean it in Python and make nice charts. Great for a portfolio!", 'sustainability,data,python', '5 days'],
        ['rania.saleh', 'resource', 'Startup pitch deck structure (10 slides) — used by winning teams', "Here is the 10-slide structure I teach in the Entrepreneurship course: Problem, Solution, Market, Product, Business model, Traction, Competition, Team, Financials, Ask. Keep each slide to one message. Happy to review decks during office hours. #startup #pitch", 'startup,pitch,business', '6 days'],
        ['202310505', 'question', 'Anyone doing a health-tech capstone? Need advice on data privacy', "I am planning a capstone about medication reminders for elderly patients. What should I be careful about regarding patient data and privacy in the UAE? Would love guidance from engineers or teachers who have worked on something similar. #health", 'health,privacy,capstone', '6 days'],
    ];
    $P = [];
    foreach ($posts as $i => [$by, $type, $title, $body, $tags, $when]) {
        $P[] = insert('posts', ['user_id' => $u($by), 'type' => $type, 'title' => $title, 'body' => $body, 'tags' => $tags, 'created_at' => $ago($when), 'pinned' => $i === 3 ? 1 : 0]);
        award_points($u($by), 10, 'Published a post');
    }

    // ---- reactions & comments ----------------------------------------------------------------
    $kinds = array_keys(REACTIONS);
    foreach ($P as $pid) {
        $author = (int) qval('SELECT user_id FROM posts WHERE id = ?', [$pid]);
        foreach ($ids as $uid) if ($uid !== $author && random_int(0, 100) < 55) {
            insert('reactions', ['user_id' => $uid, 'target_type' => 'post', 'target_id' => $pid, 'kind' => $kinds[array_rand($kinds)], 'created_at' => $ago(random_int(1, 40) . ' hours')]);
            qexec('UPDATE users SET points = points + 1 WHERE id = ?', [$author]);
        }
    }
    $talk = [
        [0, '202310202', 'Love this! A matching score based on shared courses + free time slots could work really well. Happy to help with the backend API.'],
        [0, 'layla.hassan', 'Nice concept. Consider privacy from day one: let students choose what is visible. Also a good candidate for a capstone.'],
        [0, '202310101', 'Thanks both! @Omar let’s sync this week 🙌'],
        [1, '202310101', 'I would love to help with UI! DM me — I have experience with Figma prototypes.'],
        [1, '202310303', 'I can help with the pitch and the marketing side 🎤'],
        [2, 'ahmed.mansour', 'Draw each join as a Venn diagram — INNER is the overlap, LEFT is the whole left circle plus the overlap. Come to the Monday clinic and we will go through examples.'],
        [2, '202311010', 'sqlbolt.com is great for interactive practice.'],
        [4, '202310101', 'Congratulations!! Can’t wait to watch it 🎉'],
        [4, 'rania.saleh', 'Proud of you all. Inspiring work.'],
        [7, '202310101', 'I can design the map UI and the room icons.'],
        [7, '202310404', 'Count me in for the mobile version later.'],
        [8, '202310202', 'Great idea, I can volunteer to write the matching script for pairing documents.'],
        [11, 'layla.hassan', 'Happy to talk about this in office hours — it is a good topic. Look at anonymisation and consent first.'],
    ];
    foreach ($talk as [$pi, $by, $body]) {
        $cid = insert('comments', ['post_id' => $P[$pi], 'user_id' => $u($by), 'body' => $body, 'created_at' => $ago(random_int(1, 30) . ' hours')]);
        award_points($u($by), 3, 'Commented');
        if (random_int(0, 1)) insert('reactions', ['user_id' => $ids[array_rand($ids)], 'target_type' => 'comment', 'target_id' => $cid, 'kind' => 'like']);
    }
    // a threaded reply
    $first = (int) qval('SELECT id FROM comments WHERE post_id = ? ORDER BY id LIMIT 1', [$P[0]]);
    insert('comments', ['post_id' => $P[0], 'user_id' => $u('202310101'), 'parent_id' => $first, 'body' => 'Yes please! I will share the Figma file with you.', 'created_at' => $ago('2 hours')]);
    insert('bookmarks', ['user_id' => $u('202310101'), 'post_id' => $P[11]]);

    // ---- projects ----------------------------------------------------------------------------
    $projects = [
        ['202310404', 'Campus Events App', "A Flutter app that shows every club event, workshop and exam-week activity on campus in one place, with reminders and RSVP. Targeting the national hackathon.", 'Flutter,UI Design,Firebase,Pitching', 'open', 5, ['202310101']],
        ['202311010', 'Open-source AAU Indoor Map', "Indoor navigation for all university buildings: search any room, see the route on the floor plan. Built with React and Leaflet, data collected by students.", 'React,Leaflet,Data collection,UI Design', 'open', 6, ['202310606']],
        ['202310101', 'Study-Buddy Matcher', "A web platform that matches students for exam revision based on courses, availability and study style. MVP in 6 weeks.", 'Python,SQL,APIs,UI Design', 'in_progress', 4, ['202310202', '202310303']],
        ['202310707', 'Echoes of Al Ain (short film)', "A 7-minute documentary-style short about the city, made by a cross-faculty team. Selected for the campus film festival.", 'Video editing,Sound,Storytelling', 'completed', 6, ['202310101', '202310606']],
    ];
    foreach ($projects as $i => [$owner, $title, $desc, $skills, $status, $max, $members]) {
        $pid = insert('projects', ['owner_id' => $u($owner), 'title' => $title, 'description' => $desc, 'needed_skills' => $skills, 'status' => $status, 'max_members' => $max, 'created_at' => $ago(($i + 1) * 4 . ' days'), 'outcome' => $status === 'completed' ? 'Screened at the campus film festival with 200+ attendees. Winner of the audience award runner-up.' : null]);
        insert('project_members', ['project_id' => $pid, 'user_id' => $u($owner), 'role' => 'Project owner', 'joined_at' => $ago(($i + 1) * 4 . ' days')]);
        qexec('UPDATE users SET points = points + 15 WHERE id = ?', [$u($owner)]);
        foreach ($members as $m) { insert('project_members', ['project_id' => $pid, 'user_id' => $u($m), 'role' => 'Member', 'joined_at' => $ago(($i + 1) * 3 . ' days')]); qexec('UPDATE users SET points = points + 10 WHERE id = ?', [$u($m)]); }
        $tasks = [['Define the MVP scope', 'done'], ['Design the main screens', 'done'], ['Set up the repository and CI', 'doing'], ['Build the first prototype', 'doing'], ['Write the pitch', 'todo'], ['User testing with 10 students', 'todo']];
        foreach (array_slice($tasks, 0, $status === 'open' ? 4 : 6) as $j => [$t, $st]) insert('project_tasks', ['project_id' => $pid, 'title' => $t, 'status' => $status === 'completed' ? 'done' : $st, 'assignee_id' => $u($members[$j % count($members)] ?? $owner), 'due_date' => date('Y-m-d', strtotime('+' . ($j + 2) . ' days'))]);
        insert('project_messages', ['project_id' => $pid, 'user_id' => $u($owner), 'body' => 'Welcome to the team! Our first goal is a clickable prototype by next Friday.', 'is_update' => 0, 'created_at' => $ago(($i + 1) * 3 . ' days')]);
        insert('project_messages', ['project_id' => $pid, 'user_id' => $u($owner), 'body' => 'Progress update: scope is locked and the design is ready for review. Thank you all!', 'is_update' => 1, 'created_at' => $ago(($i + 1) . ' days')]);
        if ($i === 0) insert('project_applications', ['project_id' => $pid, 'user_id' => $u('202310909'), 'message' => 'I can write the copy and handle translations for the Arabic version of the app.', 'created_at' => $ago('6 hours')]);
    }

    // ---- marketplace -------------------------------------------------------------------------
    $services = [
        ['202310101', 'I will design a modern logo & brand kit for your project', 'design', 60, 3, "Get a clean logo, colour palette and typography pairing for your student club, project or startup. Includes 2 revision rounds and files in PNG/SVG.", 5],
        ['202310303', 'Social-media content pack for your club or small business', 'business', 80, 4, "10 ready-to-post designs and captions (English/Arabic) tailored to your brand, plus a simple 2-week posting plan.", 4],
        ['202310202', 'Python & SQL help for your assignments (1-on-1 tutoring)', 'tutoring', 40, 1, "I will explain the concepts, debug your code with you and prepare you for lab exams. Not doing the work for you — teaching you to do it.", 5],
        ['202310909', 'Proofreading & translation (Arabic ⇄ English)', 'writing', 25, 2, "Reports, CVs, personal statements and presentations. Fast turnaround and tracked changes so you can learn from the edits.", 5],
        ['202310707', 'Video editing for presentations, reels and events', 'media', 120, 5, "Cuts, subtitles, colour and sound clean-up. Perfect for graduation projects and club promos.", 4],
        ['202311010', 'Landing page / portfolio website (HTML, CSS, JS)', 'programming', 150, 7, "A responsive one-page website for your portfolio or small project, deployed for free on GitHub Pages.", 5],
        ['ahmed.mansour', 'Capstone consulting: scope, architecture & review', 'tutoring', 0, 3, "Free 30-minute capstone clinics for students: we review your scope, risks and architecture so you start on solid ground.", 5],
        ['202310404', 'Flutter mobile app prototype', 'programming', 200, 10, "A working Flutter prototype of your idea with 3–5 screens and a Firebase backend.", 4],
    ];
    $S = [];
    foreach ($services as $i => [$by, $title, $cat, $price, $days, $desc, $rating]) {
        $sid = insert('services', ['user_id' => $u($by), 'title' => $title, 'category' => $cat, 'price' => $price, 'delivery_days' => $days, 'description' => $desc, 'created_at' => $ago(($i + 1) . ' days')]);
        $S[] = [$sid, $by, $rating];
    }
    $buyers = ['202310505', '202310606', '202310808', '202311010', '202310101'];
    foreach ($S as $k => [$sid, $by, $rating]) {
        $b = $buyers[$k % count($buyers)];
        if ($u($b) === $u($by)) $b = '202310505';
        $rid = insert('service_requests', ['service_id' => $sid, 'buyer_id' => $u($b), 'message' => 'Hi! I would love to use this service. Are you available this week?', 'status' => 'completed', 'created_at' => $ago(($k + 2) . ' days')]);
        insert('service_reviews', ['service_id' => $sid, 'reviewer_id' => $u($b), 'rating' => $rating, 'comment' => ['Excellent work and super fast!', 'Very professional and friendly. Highly recommended.', 'Exactly what I needed — thank you!'][$k % 3], 'created_at' => $ago(($k + 1) . ' days')]);
    }
    insert('service_requests', ['service_id' => $S[0][0], 'buyer_id' => $u('202310808'), 'message' => 'Could you do a logo for our cybersecurity club? We love dark green.', 'status' => 'pending', 'created_at' => $ago('4 hours')]);

    // ---- courses -----------------------------------------------------------------------------
    $courses = [
        ['ahmed.mansour', 'Web Development Fundamentals', 'programming', 'beginner', 0, "Build your first website from scratch. You will learn HTML structure, CSS layout and a little JavaScript, and finish with a portfolio page you can publish.", [
            ['How the web works', "The web is a conversation between a browser (client) and a server.\n\nWhen you open a page, the browser sends a request, the server replies with HTML, and the browser also loads CSS and JavaScript.\n\nIn this lesson try to open the developer tools (F12) on any website and look at the Network tab.", 'https://www.youtube.com/watch?v=hJHvdBlSxug'],
            ['HTML: the structure of a page', "HTML describes what is on the page: headings, paragraphs, images, links and lists.\n\nTry it: create index.html with a heading, two paragraphs and a link to your favourite site."],
            ['CSS: making it beautiful', "CSS controls colour, spacing, fonts and layout. Learn selectors, the box model and Flexbox.\n\nChallenge: style your page with a green theme, centred card and a button that changes colour on hover."],
            ['JavaScript: bringing it to life', "JavaScript adds behaviour. Start with variables, functions and events, then change something on the page when a button is clicked."],
        ]],
        ['layla.hassan', 'Intro to Machine Learning', 'programming', 'intermediate', 1, "A friendly introduction to machine learning: what it is, how models learn, and how to train and evaluate your first model in Python.", [
            ['What is machine learning?', "Machine learning lets computers find patterns in data instead of following hand-written rules.\n\nWe will look at supervised vs unsupervised learning and real campus examples (attendance prediction, course recommendations)."],
            ['Data and features', "Good models start with good data. Learn about cleaning, features, and splitting data into train and test sets."],
            ['Your first model', "Train a simple classifier with scikit-learn, measure accuracy and understand what the numbers mean."],
        ]],
        ['rania.saleh', 'Entrepreneurship 101: From Idea to Pitch', 'business', 'beginner', 2, "Learn how to test an idea, understand customers and pitch it clearly. Ideal before hackathons and startup competitions.", [
            ['Finding a real problem', "Great startups solve a real problem for a specific group of people. Talk to 5 potential users this week and write down their exact words."],
            ['Validating your idea cheaply', "You do not need code to test demand: use a landing page, a survey or even a conversation. Learn the “mom test”."],
            ['The 10-slide pitch', "Problem, Solution, Market, Product, Business model, Traction, Competition, Team, Financials, Ask. Practise in under 3 minutes."],
        ]],
    ];
    $C = [];
    foreach ($courses as $i => [$by, $title, $cat, $lvl, $theme, $desc, $lessons]) {
        $cid = insert('courses', ['author_id' => $u($by), 'title' => $title, 'category' => $cat, 'level' => $lvl, 'theme' => $theme, 'description' => $desc, 'created_at' => $ago(($i + 2) * 3 . ' days')]);
        $C[] = $cid;
        foreach ($lessons as $j => $l) insert('lessons', ['course_id' => $cid, 'position' => $j + 1, 'title' => $l[0], 'content' => $l[1], 'video_url' => $l[2] ?? null]);
    }
    foreach (['202310101', '202310202', '202310303', '202310404', '202311010'] as $k => $stu) {
        $cid = $C[$k % 3];
        insert('enrollments', ['course_id' => $cid, 'user_id' => $u($stu), 'created_at' => $ago('5 days')]);
        $lessons = qall('SELECT id FROM lessons WHERE course_id = ? ORDER BY position', [$cid]);
        foreach (array_slice($lessons, 0, 1 + $k % 3) as $l) insert('lesson_progress', ['lesson_id' => $l['id'], 'user_id' => $u($stu)]);
    }

    // ---- mentorship, messages, notifications --------------------------------------------------
    insert('mentorship_requests', ['mentor_id' => $u('layla.hassan'), 'student_id' => $u('202310202'), 'topic' => 'Choosing a capstone topic in AI', 'message' => 'I am interested in recommendation systems but not sure about scope.', 'status' => 'accepted', 'session_at' => date('Y-m-d 14:00:00', strtotime('+3 days')), 'created_at' => $ago('2 days')]);
    insert('mentorship_requests', ['mentor_id' => $u('layla.hassan'), 'student_id' => $u('202310505'), 'topic' => 'Data privacy for a health-tech capstone', 'message' => 'How do I handle patient data responsibly?', 'status' => 'pending', 'created_at' => $ago('5 hours')]);
    insert('mentorship_requests', ['mentor_id' => $u('rania.saleh'), 'student_id' => $u('202310303'), 'topic' => 'Pitching my content studio idea', 'message' => '', 'status' => 'completed', 'feedback' => 'Great energy. Focus the pitch on one customer segment and show two pieces of traction.', 'session_at' => $ago('6 days'), 'created_at' => $ago('9 days')]);
    insert('messages', ['sender_id' => $u('202310101'), 'receiver_id' => $u('202310202'), 'body' => 'Hey Omar! Are you free to talk about the study-buddy backend tomorrow?', 'created_at' => $ago('3 hours'), 'is_read' => 1]);
    insert('messages', ['sender_id' => $u('202310202'), 'receiver_id' => $u('202310101'), 'body' => 'Yes! After the lab around 3 pm works for me.', 'created_at' => $ago('2 hours'), 'is_read' => 1]);
    insert('messages', ['sender_id' => $u('202310404'), 'receiver_id' => $u('202310101'), 'body' => 'Saw your comment on the hackathon post — would love to have you in the team 🙌', 'created_at' => $ago('1 hour')]);
    insert('reports', ['reporter_id' => $u('202310606'), 'target_type' => 'post', 'target_id' => $P[5], 'reason' => 'Spam or advertising', 'created_at' => $ago('2 hours')]);

    // badges & levels
    foreach ($ids as $uid) check_badges($uid);
    unset($_SESSION['toast_badge']);
    qexec('UPDATE users SET points = points + 40 WHERE id IN (' . implode(',', [$u('202310101'), $u('202310404')]) . ')');
    notify($u('202310101'), 'comment', 'Omar commented on “A study-buddy matcher for finals week”', 'post/' . $P[0]);
}
