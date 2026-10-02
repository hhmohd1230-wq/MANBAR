<?php
/**
 * MANBAR Assistant.
 *  1) fix_writing(): auto-corrects spelling / casing / punctuation before a post is published.
 *  2) route_idea():  reads what the student describes and suggests the right place to post it.
 * Works offline with built-in rules. If ANTHROPIC_API_KEY is configured, Claude is used first and the rules are the fallback.
 */

const AI_TARGETS = [
    'idea'         => ['label' => 'Idea',          'url' => 'feed?compose=idea',         'hint' => 'Share a concept and get feedback.'],
    'team'         => ['label' => 'Team request',  'url' => 'feed?compose=team',         'hint' => 'Find teammates with the right skills.'],
    'question'     => ['label' => 'Question',      'url' => 'feed?compose=question',     'hint' => 'Ask the community for help.'],
    'resource'     => ['label' => 'Resource',      'url' => 'feed?compose=resource',     'hint' => 'Share notes, links and tutorials.'],
    'achievement'  => ['label' => 'Showcase',      'url' => 'feed?compose=achievement',  'hint' => 'Show your work and wins.'],
    'event'        => ['label' => 'Event',         'url' => 'feed?compose=event',        'hint' => 'Announce a workshop, hackathon or meetup.'],
    'announcement' => ['label' => 'Announcement',  'url' => 'feed?compose=announcement', 'hint' => 'Official news (teachers).'],
    'teaching'     => ['label' => 'Teaching offer','url' => 'feed?compose=teaching',     'hint' => 'Offer tutoring or help with a subject (teachers).'],
    'project'      => ['label' => 'New project',   'url' => 'projects/new',              'hint' => 'Turn your idea into a project with a team and tasks.'],
    'service'      => ['label' => 'Marketplace service', 'url' => 'marketplace/new',     'hint' => 'Offer a paid or free service to other students.'],
    'course'       => ['label' => 'New course',    'url' => 'learn/new',                 'hint' => 'Publish lessons in the Learning Center (teachers).'],
    'mentor'       => ['label' => 'Find a mentor', 'url' => 'mentors',                   'hint' => 'Request guidance from a teacher or expert.'],
];

const AI_KEYWORDS = [
    'team'        => ['looking for' => 3, 'need a' => 2, 'need someone' => 3, 'teammate' => 4, 'team members' => 4, 'join my' => 3, 'join us' => 3, 'partner' => 2, 'co-founder' => 4, 'cofounder' => 4, 'developer' => 1, 'designer' => 1, 'who wants to' => 3, 'anyone want to' => 3, 'collaborate' => 2],
    'question'    => ['how do' => 3, 'how can' => 3, 'how to' => 3, 'why ' => 1, 'what is' => 2, 'does anyone' => 3, 'anyone know' => 3, 'help me' => 3, 'can someone' => 3, 'i dont understand' => 3, 'error' => 2, 'stuck' => 2, '?' => 2],
    'service'     => ['i can design' => 4, 'i will' => 2, 'i offer' => 4, 'for hire' => 4, 'i can build' => 3, 'aed' => 3, 'price' => 3, 'per hour' => 3, 'freelance' => 3, 'logo' => 2, 'translation' => 2, 'proofreading' => 2, 'selling' => 3, 'i can help you with' => 3, 'commission' => 2],
    'resource'    => ['tutorial' => 3, 'cheat sheet' => 4, 'notes' => 2, 'slides' => 2, 'pdf' => 2, 'link to' => 2, 'useful' => 1, 'free resource' => 4, 'recommend' => 1, 'book' => 1, 'roadmap' => 2],
    'event'       => ['workshop' => 4, 'hackathon' => 5, 'meetup' => 4, 'seminar' => 4, 'webinar' => 4, 'competition' => 3, 'join us on' => 3, 'register' => 2, 'tomorrow at' => 2, 'event' => 3, 'conference' => 3],
    'achievement' => ['i built' => 3, 'i made' => 2, 'i finished' => 3, 'i won' => 4, 'we won' => 4, 'proud' => 3, 'completed my' => 3, 'launched' => 3, 'my portfolio' => 3, 'certificate' => 2, 'graduated' => 2, 'internship' => 2],
    'mentor'      => ['mentor' => 5, 'guidance' => 3, 'career advice' => 4, 'advice on my career' => 4, 'guide me' => 3, 'supervisor' => 2, 'coach' => 2, 'career path' => 3],
    'course'      => ['course' => 3, 'curriculum' => 4, 'lessons' => 3, 'syllabus' => 4, 'module' => 2, 'online class' => 3],
    'teaching'    => ['office hours' => 4, 'tutoring' => 4, 'i teach' => 4, 'revision session' => 4, 'help students' => 3, 'extra class' => 3, 'i can teach' => 4],
    'announcement'=> ['announcement' => 5, 'important notice' => 5, 'deadline' => 2, 'reminder' => 2, 'attention students' => 4, 'notice' => 2],
    'project'     => ['build an app' => 4, 'build a website' => 4, 'start a project' => 5, 'capstone' => 3, 'prototype' => 3, 'startup' => 3, 'mvp' => 3, 'we are building' => 4, 'project' => 2, 'open source' => 2],
    'idea'        => ['what if' => 4, 'idea' => 4, 'concept' => 3, 'imagine' => 3, 'i think we should' => 3, 'proposal' => 3, 'suggest' => 1, 'it would be great' => 3, 'wouldnt it be' => 3],
];

const AI_TYPOS = [
    'teh' => 'the', 'recieve' => 'receive', 'recieved' => 'received', 'definately' => 'definitely', 'definatly' => 'definitely', 'seperate' => 'separate',
    'occured' => 'occurred', 'occurence' => 'occurrence', 'untill' => 'until', 'wich' => 'which', 'becuase' => 'because', 'becouse' => 'because', 'beacuse' => 'because',
    'thier' => 'their', 'freind' => 'friend', 'freinds' => 'friends', 'goverment' => 'government', 'enviroment' => 'environment', 'tomorow' => 'tomorrow',
    'tommorow' => 'tomorrow', 'tommorrow' => 'tomorrow', 'adress' => 'address', 'begining' => 'beginning', 'beleive' => 'believe', 'calender' => 'calendar',
    'collegue' => 'colleague', 'comming' => 'coming', 'completly' => 'completely', 'concious' => 'conscious', 'dissapoint' => 'disappoint', 'embarass' => 'embarrass',
    'exellent' => 'excellent', 'experiance' => 'experience', 'finaly' => 'finally', 'foward' => 'forward', 'futher' => 'further', 'gaurd' => 'guard', 'happend' => 'happened',
    'immediatly' => 'immediately', 'independant' => 'independent', 'intresting' => 'interesting', 'knowlege' => 'knowledge', 'libary' => 'library', 'liason' => 'liaison',
    'maintainance' => 'maintenance', 'neccessary' => 'necessary', 'necesary' => 'necessary', 'noticable' => 'noticeable', 'occassion' => 'occasion', 'persue' => 'pursue',
    'posible' => 'possible', 'prefered' => 'preferred', 'priviledge' => 'privilege', 'probaly' => 'probably', 'publically' => 'publicly', 'realy' => 'really',
    'reccomend' => 'recommend', 'recomend' => 'recommend', 'refered' => 'referred', 'relevent' => 'relevant', 'sucess' => 'success', 'succesful' => 'successful',
    'suprise' => 'surprise', 'tecnology' => 'technology', 'techonology' => 'technology', 'thru' => 'through', 'truely' => 'truly', 'unfortunatly' => 'unfortunately',
    'wierd' => 'weird', 'writting' => 'writing', 'alot' => 'a lot', 'proffesor' => 'professor', 'profesor' => 'professor', 'univercity' => 'university',
    'universty' => 'university', 'studen' => 'student', 'studnet' => 'student', 'projet' => 'project', 'projcet' => 'project', 'developper' => 'developer',
    'programing' => 'programming', 'langauge' => 'language', 'aplication' => 'application', 'applicaton' => 'application', 'websit' => 'website', 'wensite' => 'website',
    'desing' => 'design', 'colaborate' => 'collaborate', 'colaboration' => 'collaboration', 'collabration' => 'collaboration', 'opertunity' => 'opportunity',
    'oppurtunity' => 'opportunity', 'oportunity' => 'opportunity', 'abilty' => 'ability', 'availble' => 'available', 'avaliable' => 'available', 'teem' => 'team',
    'mentorr' => 'mentor', 'asap' => 'ASAP', 'wanna' => 'want to', 'gonna' => 'going to', 'u' => 'you', 'ur' => 'your', 'pls' => 'please', 'plz' => 'please',
    'shoud' => 'should', 'woud' => 'would', 'coud' => 'could', 'thx' => 'thanks', 'tnx' => 'thanks', 'bcz' => 'because', 'cuz' => 'because', 'abt' => 'about', 'idk' => "I don't know", 'rn' => 'right now', 'tho' => 'though',
    'dont' => "don't", 'doesnt' => "doesn't", 'didnt' => "didn't", 'cant' => "can't", 'wont' => "won't", 'isnt' => "isn't", 'arent' => "aren't", 'wasnt' => "wasn't",
    'couldnt' => "couldn't", 'shouldnt' => "shouldn't", 'wouldnt' => "wouldn't", 'im' => "I'm", 'ive' => "I've", 'thats' => "that's", 'whats' => "what's",
    'theres' => "there's", 'youre' => "you're", 'theyre' => "they're", 'lets' => "let's", 'havent' => "haven't", 'hasnt' => "hasn't",
];

/* ============ 1) Writing assistant ============ */
function fix_writing(string $text): array
{
    $orig = $text;
    $changes = [];
    $text = str_replace("\r", '', $text);

    // Mostly Arabic text: only tidy whitespace/punctuation spacing.
    $letters = preg_match_all('/\p{L}/u', $text);
    $arabic = preg_match_all('/\p{Arabic}/u', $text);
    if ($letters && $arabic / $letters > 0.5) {
        $text = trim(preg_replace(['/[ \t]{2,}/u', '/\n{3,}/'], [' ', "\n\n"], $text));
        return ['corrected' => $text, 'changes' => $text === $orig ? [] : [['from' => '…', 'to' => '…', 'why' => 'Tidied spacing']], 'engine' => 'rules'];
    }

    // Word-level fixes (keeps the capital of the first letter).
    $text = preg_replace_callback('/(?<![\w\'’@#\/.])([A-Za-z]+)(?![\w\'’@\/])/u', function ($m) use (&$changes) {
        $w = $m[1];
        $lw = strtolower($w);
        if (!isset(AI_TYPOS[$lw]) || ($lw === 'asap' && $w === 'ASAP')) return $w;
        if ($w === strtoupper($w) && strlen($w) > 1 && $lw !== 'asap') return $w;   // ALL CAPS words left alone
        $to = AI_TYPOS[$lw];
        if (ctype_upper($w[0]) && $to[0] !== 'I' && $lw !== 'asap') $to = ucfirst($to);
        if ($to === $w) return $w;
        $changes[] = ['from' => $w, 'to' => $to, 'why' => 'Spelling / shorthand'];
        return $to;
    }, $text);

    // Standalone "i" => "I"
    $text = preg_replace_callback('/(?<![\w\'’])i(?=[\s,.!?;:]|$)/u', function ($m) use (&$changes) { $changes[] = ['from' => 'i', 'to' => 'I', 'why' => 'Capitalise “I”']; return 'I'; }, $text);

    // Spacing and punctuation.
    $before = $text;
    $text = preg_replace('/\s+([.!?])(?=[A-Za-z])/', '$1 ', $text);
    $text = preg_replace('/[ \t]{2,}/', ' ', $text);
    $text = preg_replace('/[ \t]+([,.!?;:])/', '$1', $text);
    $text = preg_replace('/([,;:])(?=[^\s\d"\')\]])/', '$1 ', $text);
    $text = preg_replace('/([.!?])(?=[A-Z][a-z])/', '$1 ', $text);
    $text = preg_replace('/([!?]){3,}/', '$1', $text);
    $text = preg_replace('/\.{4,}/', '...', $text);
    $text = preg_replace('/\n{3,}/', "\n\n", $text);
    $text = trim($text);
    if ($text !== $before) $changes[] = ['from' => '…', 'to' => '…', 'why' => 'Fixed spacing and punctuation'];

    // Capitalise sentence starts.
    $text = preg_replace_callback('/(^|[.!?]\s+|\n)([a-z])/u', function ($m) use (&$changes) {
        $changes[] = ['from' => $m[2], 'to' => strtoupper($m[2]), 'why' => 'Capitalise sentence start'];
        return $m[1] . strtoupper($m[2]);
    }, $text);

    // Final full stop on longer plain sentences.
    if (preg_match('/[A-Za-z0-9)]$/', $text) && str_word_count($text) >= 6 && !str_contains($text, "\n")) {
        $text .= '.';
        $changes[] = ['from' => '', 'to' => '.', 'why' => 'Added closing full stop'];
    }

    return ['corrected' => $text, 'changes' => array_slice($changes, 0, 25), 'engine' => 'rules'];
}

/* ============ 2) Where should I post this? ============ */
function route_idea(string $text, ?array $user = null): array
{
    $t = ' ' . mb_strtolower(preg_replace('/\s+/', ' ', $text)) . ' ';
    $t = str_replace(["don't", "wouldn't", "doesn't"], ['dont', 'wouldnt', 'doesnt'], $t);
    $scores = [];
    foreach (AI_KEYWORDS as $target => $words) {
        foreach ($words as $w => $pts) {
            if (str_contains($t, $w)) $scores[$target] = ($scores[$target] ?? 0) + $pts;
        }
    }
    $isStaff = $user && in_array($user['role'], ['teacher', 'admin'], true);
    if (!$isStaff) { unset($scores['course'], $scores['teaching'], $scores['announcement']); }   // staff-only targets
    arsort($scores);
    $top = array_key_first($scores) ?? 'idea';
    $why = [
        'idea' => 'This reads like an idea or proposal — the community can react and give feedback.',
        'team' => 'You are looking for people to work with — a Team request reaches students with the right skills.',
        'question' => 'You are asking for help — a Question gets answers from classmates and mentors.',
        'resource' => 'You are sharing material — a Resource post keeps it easy to find and save.',
        'achievement' => 'You are showing something you did — post it as a Showcase for your portfolio.',
        'event' => 'This sounds like an event — an Event post lets people plan to attend.',
        'announcement' => 'This is an official notice — post it as an Announcement.',
        'teaching' => 'You are offering teaching help — a Teaching offer is visible to every student.',
        'project' => 'This sounds like something you will build — create a Project so people can apply and you can manage tasks.',
        'service' => 'You are offering a skill to others — list it in the Marketplace with a price (or free).',
        'course' => 'This is structured learning content — publish it as a Course in the Learning Center.',
        'mentor' => 'You want guidance — request a Mentor who can advise you.',
    ];
    $alts = array_slice(array_diff(array_keys($scores), [$top]), 0, 2);
    return [
        'primary' => array_merge(['key' => $top], AI_TARGETS[$top], ['reason' => $why[$top], 'url' => url(AI_TARGETS[$top]['url'])]),
        'alternatives' => array_map(fn($k) => array_merge(['key' => $k], AI_TARGETS[$k], ['url' => url(AI_TARGETS[$k]['url'])]), $alts),
        'tags' => suggest_tags($text),
        'confidence' => $scores ? min(0.95, 0.45 + 0.08 * ($scores[$top] ?? 0)) : 0.35,
    ];
}

function suggest_tags(string $text): array
{
    static $known = ['ai', 'web', 'mobile', 'design', 'python', 'java', 'php', 'javascript', 'data', 'robotics', 'iot', 'security', 'cloud', 'startup', 'business', 'marketing',
        'engineering', 'medicine', 'law', 'finance', 'research', 'arabic', 'english', 'math', 'physics', 'game', 'ux', 'ui', 'sql', 'database', 'networking', 'hackathon',
        'capstone', 'internship', 'career', 'photography', 'video', 'writing', 'translation', 'tutoring', 'sustainability', 'health'];
    $words = preg_split('/[^\p{L}\p{N}+#]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY);
    $out = [];
    foreach ($words as $w) {
        $w = ltrim($w, '#');
        if (in_array($w, $known, true)) $out[$w] = 1;
        if ($w === 'machine' || $w === 'chatgpt' || $w === 'llm') $out['ai'] = 1;
        if ($w === 'website' || $w === 'html' || $w === 'css') $out['web'] = 1;
        if ($w === 'app' || $w === 'android' || $w === 'ios') $out['mobile'] = 1;
    }
    return array_slice(array_keys($out), 0, 5);
}

/* ============ Optional: Claude ============ */
function ai_llm(string $text, string $mode, ?array $user): ?array
{
    $key = cfg('anthropic_api_key');
    if (!$key || !function_exists('curl_init')) return null;
    $targets = [];
    foreach (AI_TARGETS as $k => $v) $targets[] = "$k = {$v['label']}: {$v['hint']}";
    $system = "You are the MANBAR assistant for a university student platform. Reply with ONLY compact JSON. "
        . "Keys: corrected (the user's text with spelling, grammar and punctuation fixed, same language, same meaning, do not add content), "
        . "target (best key from the list), reason (one friendly sentence), tags (up to 5 lower-case tags). Targets:\n" . implode("\n", $targets);
    $payload = ['model' => cfg('anthropic_model'), 'max_tokens' => 900, 'system' => $system, 'messages' => [['role' => 'user', 'content' => $text]]];
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['content-type: application/json', 'x-api-key: ' . $key, 'anthropic-version: 2023-06-01'],
        CURLOPT_POSTFIELDS => json_encode($payload),
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    $j = $res ? json_decode($res, true) : null;
    $txt = $j['content'][0]['text'] ?? '';
    if (!preg_match('/\{.*\}/s', $txt, $m)) return null;
    $out = json_decode($m[0], true);
    return is_array($out) && !empty($out['corrected']) ? $out : null;
}

function ai_assist(string $text, ?array $user, string $mode = 'both'): array
{
    $text = mb_substr($text, 0, 4000);
    $fix = fix_writing($text);
    $route = route_idea($text, $user);
    if ($llm = ai_llm($text, $mode, $user)) {
        $fix = ['corrected' => (string) $llm['corrected'], 'changes' => [['from' => '…', 'to' => '…', 'why' => 'Improved by AI']], 'engine' => 'claude'];
        $k = $llm['target'] ?? '';
        if (isset(AI_TARGETS[$k])) {
            $route['primary'] = array_merge(['key' => $k], AI_TARGETS[$k], ['reason' => (string) ($llm['reason'] ?? ''), 'url' => url(AI_TARGETS[$k]['url'])]);
            $route['confidence'] = 0.9;
        }
        if (!empty($llm['tags']) && is_array($llm['tags'])) $route['tags'] = array_slice(array_map('strval', $llm['tags']), 0, 5);
    }
    return ['fix' => $fix, 'route' => $route];
}
