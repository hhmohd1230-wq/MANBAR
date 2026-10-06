<?php
/**
 * MANBAR Assistant.
 *  1) fix_writing(): corrects spelling, common grammar, casing and punctuation.
 *  2) route_idea(): reads what the student describes and suggests the right place.
 *  3) ai_search_catalog(): searches MANBAR's own projects, people, mentors,
 *     courses and services and ranks them against the request and user profile.
 * Works offline with built-in rules. If configured, OpenAI is used first, then Claude, with the rules as the fallback.
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
    'universty' => 'university', 'studen' => 'student', 'studnet' => 'student', 'budy' => 'buddy', 'maxth' => 'math', 'proekt' => 'project',
    'probject' => 'project', 'projet' => 'project', 'projcet' => 'project', 'ancasonte' => 'a capstone', 'casonte' => 'capstone', 'developper' => 'developer',
    'programing' => 'programming', 'langauge' => 'language', 'aplication' => 'application', 'applicaton' => 'application', 'websit' => 'website', 'wensite' => 'website',
    'desing' => 'design', 'colaborate' => 'collaborate', 'colaboration' => 'collaboration', 'collabration' => 'collaboration', 'opertunity' => 'opportunity',
    'oppurtunity' => 'opportunity', 'oportunity' => 'opportunity', 'abilty' => 'ability', 'availble' => 'available', 'avaliable' => 'available', 'teem' => 'team',
    'mentorr' => 'mentor', 'asap' => 'ASAP', 'wanna' => 'want to', 'gonna' => 'going to', 'u' => 'you', 'ur' => 'your', 'pls' => 'please', 'plz' => 'please',
    'shoud' => 'should', 'woud' => 'would', 'coud' => 'could', 'thx' => 'thanks', 'tnx' => 'thanks', 'bcz' => 'because', 'cuz' => 'because', 'abt' => 'about', 'idk' => "I don't know", 'rn' => 'right now', 'tho' => 'though',
    'dont' => "don't", 'doesnt' => "doesn't", 'didnt' => "didn't", 'cant' => "can't", 'wont' => "won't", 'isnt' => "isn't", 'arent' => "aren't", 'wasnt' => "wasn't",
    'couldnt' => "couldn't", 'shouldnt' => "shouldn't", 'wouldnt' => "wouldn't", 'im' => "I'm", 'ive' => "I've", 'thats' => "that's", 'whats' => "what's",
    'theres' => "there's", 'youre' => "you're", 'theyre' => "they're", 'lets' => "let's", 'havent' => "haven't", 'hasnt' => "hasn't",
    'acheive' => 'achieve', 'acheived' => 'achieved', 'accomodate' => 'accommodate', 'acrosss' => 'across', 'addres' => 'address',
    'arguement' => 'argument', 'assigment' => 'assignment', 'attachement' => 'attachment', 'buisness' => 'business', 'capston' => 'capstone',
    'comunity' => 'community', 'contributer' => 'contributor', 'creat' => 'create', 'decription' => 'description', 'develope' => 'develop',
    'developement' => 'development', 'diffrent' => 'different', 'documant' => 'document', 'educaton' => 'education', 'employement' => 'employment',
    'explaination' => 'explanation', 'feauture' => 'feature', 'framwork' => 'framework', 'fucntion' => 'function', 'intrested' => 'interested',
    'knowlegeable' => 'knowledgeable', 'managment' => 'management', 'messege' => 'message', 'oppurtunities' => 'opportunities', 'peaple' => 'people',
    'preferrably' => 'preferably', 'proffesional' => 'professional', 'requirment' => 'requirement', 'requirments' => 'requirements',
    'reserach' => 'research', 'responsability' => 'responsibility', 'shedule' => 'schedule', 'similiar' => 'similar', 'skils' => 'skills', 'grammer' => 'grammar',
    'impove' => 'improve', 'imrpove' => 'improve', 'lke' => 'like', 'vocabulity' => 'vocabulary', 'vocabluary' => 'vocabulary',
    'softwere' => 'software', 'specfic' => 'specific', 'studnets' => 'students', 'suport' => 'support', 'teammatees' => 'teammates', 'usefull' => 'useful',
];

/** Conservative grammar fixes that do not invent or materially rewrite content. */
const AI_GRAMMAR_PATTERNS = [
    '/\bi am agree\b/i' => 'I agree',
    '/\bi am interesting in\b/i' => 'I am interested in',
    '/\bi[\'’]m interesting in\b/i' => "I'm interested in",
    '/\binterested on\b/i' => 'interested in',
    '/\bdiscuss about\b/i' => 'discuss',
    '/\bcan able to\b/i' => 'can',
    '/\bmore better\b/i' => 'better',
    '/\bmore easier\b/i' => 'easier',
    '/\breturn back\b/i' => 'return',
    '/\bwe is\b/i' => 'we are',
    '/\bthey is\b/i' => 'they are',
    '/\byou is\b/i' => 'you are',
    '/\bi is\b/i' => 'I am',
    '/\bhe have\b/i' => 'he has',
    '/\bshe have\b/i' => 'she has',
    '/\bit have\b/i' => 'it has',
    '/\b(teammates|students|people|members) who knows\b/i' => '$1 who know',
    '/\bthere is (many|several)\b/i' => 'there are $1',
    '/\bone of the (student|project|course|mentor|service)\b/i' => 'one of the $1s',
    '/\bdoesn\'t works\b/i' => "doesn't work",
    '/\bdidn\'t went\b/i' => "didn't go",
    '/\bwant build\b/i' => 'want to build',
    '/\bwant create\b/i' => 'want to create',
    '/\bwant (?:send|sent)\b/i' => 'want to send',
    '/\bwant join\b/i' => 'want to join',
    '/\bwant learn\b/i' => 'want to learn',
    '/\bwant find\b/i' => 'want to find',
    '/\bwant (?:to be able to make )?people (?:to )?work with me\b/i' => 'want other people to collaborate with me',
    '/\bwant people to be able to help me (?:in|with) it\b/i' => 'want people to help me with it',
    '/\bhelp me in it\b/i' => 'help me with it',
    '/\bmake an? math project\b/i' => 'create a math project',
    '/\bmake an? project\b/i' => 'create a project',
    '/\b(project|course|service|idea) and I want\b/i' => '$1, and I want',
    '/\bcapstone thing\b/i' => 'capstone project',
    '/\band I (?:would )?like,?\s+to make it way better\b/i' => 'and make it significantly better',
    '/\blooking (?:a|an) teammate\b/i' => 'looking for a teammate',
    '/\blooking teammates\b/i' => 'looking for teammates',
    '/\blooking (?:a|an) mentor\b/i' => 'looking for a mentor',
    '/\bneed teammates? who\b/i' => 'need teammates who',
    '/\ba ([aeiou][a-z]+)\b/i' => 'an $1',
    '/\ban (university|user|useful|unique|one|ui|url|european)\b/i' => 'a $1',
];

/** High-confidence wording upgrades. These stay conservative so the user's meaning is preserved. */
const AI_VOCAB_PATTERNS = [
    '/\bvery good\b/i' => 'excellent',
    '/\bvery important\b/i' => 'essential',
    '/\ba lot of\b/i' => 'many',
    '/\bmake (?:it|this) better\b/i' => 'improve it',
    '/\bmake better\b/i' => 'improve',
    '/\bdo research\b/i' => 'conduct research',
    '/\bget experience\b/i' => 'gain experience',
    '/\bwork together\b/i' => 'collaborate',
    '/\beasy to use\b/i' => 'user-friendly',
    '/\bmain goal\b/i' => 'primary goal',
];

const AI_STOP_WORDS = [
    'a','an','and','are','as','at','be','best','but','by','can','do','for','from','get','give','has','have','help','i','in','is','it','me','my','of','on','or','our','please','show','some','that','the','their','them','this','to','want','we','what','where','which','who','with','you','your',
    'find','search','looking','recommend','recommendation','need','inside','manbar','open','match','project','projects',
];

/* ============ 1) Writing assistant ============ */
function fix_writing(string $text, string $style = 'body'): array
{
    $orig = $text;
    $changes = [];
    $style = in_array($style, ['title', 'body', 'message'], true) ? $style : 'body';
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

    // High-confidence phrase-level grammar fixes.
    foreach (AI_GRAMMAR_PATTERNS as $pattern => $replacement) {
        $before = $text;
        $text = preg_replace($pattern, $replacement, $text);
        if ($text !== $before) $changes[] = ['from' => '…', 'to' => '…', 'why' => 'Improved grammar'];
    }

    // Prefer clearer vocabulary only when the replacement is safe and meaning-preserving.
    foreach (AI_VOCAB_PATTERNS as $pattern => $replacement) {
        $before = $text;
        $text = preg_replace($pattern, $replacement, $text);
        if ($text !== $before) $changes[] = ['from' => '…', 'to' => '…', 'why' => 'Clearer vocabulary'];
    }

    // Standalone "i" => "I"
    $text = preg_replace_callback('/(?<![\w\'’])i(?=[\s,.!?;:]|$)/u', function ($m) use (&$changes) { $changes[] = ['from' => 'i', 'to' => 'I', 'why' => 'Capitalise “I”']; return 'I'; }, $text);

    // Spacing and punctuation.
    $before = $text;
    $text = preg_replace('/\s+([.!?])(?=[A-Za-z])/', '$1 ', $text);
    $text = preg_replace('/[ \t]{2,}/', ' ', $text);
    $text = preg_replace('/[ \t]+([,.!?;:])/', '$1', $text);
    $text = preg_replace('/([,;:])(?=[^\s\d"\')\]])/', '$1 ', $text);
    $text = preg_replace('/([.!?])(?=[A-Z][a-z])/', '$1 ', $text);
    $text = preg_replace('/([\p{L}\p{N})])\n(?=[A-Z])/u', "$1.\n", $text);
    $text = preg_replace('/([!?]){3,}/', '$1', $text);
    $text = preg_replace('/\.{4,}/', '...', $text);
    $text = preg_replace('/\n{3,}/', "\n\n", $text);
    $text = trim($text);
    if ($text !== $before) $changes[] = ['from' => '…', 'to' => '…', 'why' => 'Fixed spacing and punctuation'];

    // Product and technology names users commonly type in lowercase.
    $proper = [
        'manbar' => 'MANBAR', 'aau' => 'AAU', 'ai' => 'AI', 'api' => 'API', 'ui' => 'UI', 'ux' => 'UX',
        'php' => 'PHP', 'sql' => 'SQL', 'html' => 'HTML', 'css' => 'CSS', 'javascript' => 'JavaScript',
        'python' => 'Python', 'flutter' => 'Flutter', 'firebase' => 'Firebase', 'linkedin' => 'LinkedIn',
        'github' => 'GitHub', 'whatsapp' => 'WhatsApp', 'discord' => 'Discord',
    ];
    $text = preg_replace_callback('/\b(manbar|aau|ai|api|ui|ux|php|sql|html|css|javascript|python|flutter|firebase|linkedin|github|whatsapp|discord)\b/iu', function ($m) use (&$changes, $proper) {
        $to = $proper[mb_strtolower($m[1])];
        if ($m[1] !== $to) $changes[] = ['from' => $m[1], 'to' => $to, 'why' => 'Correct name / acronym'];
        return $to;
    }, $text);

    // Capitalise sentence starts.
    $text = preg_replace_callback('/(^|[.!?]\s+|\n)([a-z])/u', function ($m) use (&$changes) {
        $changes[] = ['from' => $m[2], 'to' => strtoupper($m[2]), 'why' => 'Capitalise sentence start'];
        return $m[1] . strtoupper($m[2]);
    }, $text);

    // Titles stay clean; descriptions receive closing punctuation when appropriate.
    if ($style === 'title') {
        $text = preg_replace('/\.+$/', '', $text);
        $text = preg_replace('/\s*\n\s*/', ' ', $text);
    } elseif (preg_match('/[\p{L}\p{N})]$/u', $text) && preg_match_all('/\p{L}+/u', $text) >= 6 && !str_contains($text, "\n")) {
        $text .= '.';
        $changes[] = ['from' => '', 'to' => '.', 'why' => 'Added closing full stop'];
    }

    // Remove duplicate generic notices while preserving concrete word changes.
    $seen = [];
    $changes = array_values(array_filter($changes, function ($c) use (&$seen) {
        $key = implode('|', $c);
        if (isset($seen[$key])) return false;
        return $seen[$key] = true;
    }));
    return ['corrected' => $text, 'changes' => array_slice($changes, 0, 25), 'engine' => 'rules'];
}

/**
 * Build a useful first draft for creation forms without requiring an external API.
 * Existing descriptions are polished, never expanded or silently overwritten.
 */
function ai_form_draft(string $context, string $title, string $body = '', array $meta = []): array
{
    $context = in_array($context, ['project', 'service', 'course'], true) ? $context : 'project';
    $title = trim(mb_substr($title, 0, 200));
    $body = trim(mb_substr($body, 0, 4000));

    if ($title === '' && $body !== '') {
        $firstLine = preg_split('/[.!?\n]/u', $body, 2)[0] ?? $body;
        $words = preg_split('/\s+/u', trim($firstLine), -1, PREG_SPLIT_NO_EMPTY);
        $title = implode(' ', array_slice($words, 0, 12));
    }

    $cleanTitle = fix_writing($title, 'title')['corrected'];
    if ($cleanTitle === '') $cleanTitle = $context === 'course' ? 'A practical campus course' : ($context === 'service' ? 'A campus service' : 'A campus project');

    // If the user already wrote a description, respect it and only polish the language.
    if ($body !== '') {
        $cleanBody = fix_writing($body, 'body')['corrected'];
        return ['title' => $cleanTitle, 'body' => $cleanBody, 'engine' => 'smart rules', 'generated' => false];
    }

    if ($context === 'service') {
        if (preg_match('/^I\s+(?:will|can|offer to)\s+(.+)$/iu', $cleanTitle, $match)) {
            $action = rtrim($match[1], '.');
            $draft = "I will {$action}. We will begin by confirming your goals, requirements, preferred style, and deadline. I will complete the agreed work and deliver it in a clear, ready-to-use format, with straightforward communication throughout the process. Please include any useful examples or references when you send your request.";
        } else {
            $draft = "This service provides {$cleanTitle} for students and campus projects. We will begin by confirming your goals, requirements, preferred style, and deadline. You will receive the agreed work in a clear, ready-to-use format, with straightforward communication throughout the process. Please include any useful examples or references when you send your request.";
        }
    } elseif ($context === 'course') {
        $topic = preg_replace('/^(?:an?\s+)?(?:introduction|intro)\s+to\s+/iu', '', $cleanTitle);
        $topic = $topic !== '' ? $topic : $cleanTitle;
        $level = strtolower((string) ($meta['level'] ?? 'beginner'));
        if (!in_array($level, ['beginner', 'intermediate', 'advanced'], true)) $level = 'beginner';
        $category = trim((string) ($meta['category'] ?? 'the subject')) ?: 'the subject';
        $draft = "This {$level} course introduces students to {$topic} through clear explanations, practical examples, and guided activities. Learners will build a strong foundation, practise the core concepts, and apply what they learn in a practical task. By the end of the course, students will be ready to continue developing their {$category} skills independently.";
    } else {
        $draft = "{$cleanTitle} is a student-led project designed to solve a clear campus need. We will begin by understanding the problem and the students affected, then design and build a focused first version. The team will test the solution with users, use their feedback to improve it, and document the final outcome. We are looking for teammates who can contribute relevant skills and collaborate from planning through delivery.";
    }

    return ['title' => $cleanTitle, 'body' => fix_writing($draft, 'body')['corrected'], 'engine' => 'smart rules', 'generated' => true];
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

/* ============ 3) Search and recommendations inside MANBAR ============ */
function ai_terms(string $text): array
{
    $text = mb_strtolower($text);
    $parts = preg_split('/[^\p{L}\p{N}+#.-]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
    $terms = [];
    foreach ($parts as $word) {
        $word = trim($word, '#.-');
        if (mb_strlen($word) < 2 || in_array($word, AI_STOP_WORDS, true)) continue;
        $terms[$word] = true;
    }
    $expansions = [
        'app' => ['mobile', 'android', 'ios', 'flutter'], 'mobile' => ['app', 'flutter'],
        'website' => ['web', 'frontend', 'backend'], 'web' => ['website', 'html', 'css', 'javascript'],
        'coding' => ['programming', 'software', 'developer'], 'developer' => ['programming', 'software'],
        'design' => ['ui', 'ux', 'graphic'], 'designer' => ['design', 'ui', 'ux'],
        'data' => ['analytics', 'database', 'sql', 'python'], 'ai' => ['machine learning', 'ml', 'data'],
        'business' => ['marketing', 'startup', 'entrepreneurship'], 'career' => ['cv', 'resume', 'internship'],
        'teammate' => ['team', 'member', 'collaborator'], 'teammates' => ['team', 'members', 'collaborators'],
    ];
    foreach (array_keys($terms) as $term) foreach ($expansions[$term] ?? [] as $extra) $terms[$extra] = true;
    return array_slice(array_keys($terms), 0, 24);
}

function ai_profile_terms(array $user): array
{
    $text = implode(' ', array_filter([(string) ($user['major'] ?? ''), (string) ($user['faculty'] ?? ''), (string) ($user['headline'] ?? '')]));
    try {
        $skills = qall('SELECT name FROM user_skills WHERE user_id = ? LIMIT 20', [(int) $user['id']]);
        $text .= ' ' . implode(' ', array_column($skills, 'name'));
    } catch (Throwable) { /* A new/partial database can still use the assistant. */ }
    return ai_terms($text);
}

/** @return array{score:float,matches:array} */
function ai_match_score(array $terms, array $fields, array $weights): array
{
    $score = 0.0;
    $matches = [];
    foreach ($terms as $term) {
        foreach ($fields as $i => $field) {
            if ($field !== '' && mb_stripos($field, $term) !== false) {
                $score += $weights[$i] ?? 1;
                $matches[$term] = true;
                break;
            }
        }
    }
    return ['score' => $score, 'matches' => array_slice(array_keys($matches), 0, 4)];
}

function ai_search_requested(string $text): bool
{
    return (bool) preg_match('/\b(find|search|show|recommend|suggest|match|looking for|available|open|join|best|need a|need an|need someone|who can|where can)\b/iu', $text);
}

function ai_requested_catalogs(string $text): array
{
    $t = mb_strtolower($text);
    $catalogs = [];
    $tests = [
        'projects' => '/\b(project|projects|app|website|platform|capstone|startup|prototype|build|join|team)\b/u',
        'people' => '/\b(people|person|student|students|classmate|developer|designer|teammate|teammates|collaborator|partner)\b/u',
        'mentors' => '/\b(mentor|mentors|guidance|career advice|supervisor|expert|coach)\b/u',
        'courses' => '/\b(course|courses|learn|learning|study|lesson|lessons|tutorial|training)\b/u',
        'services' => '/\b(service|services|hire|logo|poster|translation|photography|video editing|freelance)\b/u',
    ];
    foreach ($tests as $catalog => $pattern) if (preg_match($pattern, $t)) $catalogs[] = $catalog;
    return $catalogs ?: ['projects', 'people', 'mentors', 'courses', 'services'];
}

function ai_result_reason(array $matches, bool $personal): string
{
    if ($matches) return 'Matches ' . implode(', ', array_slice($matches, 0, 3)) . ($personal ? ' and your profile' : '');
    return $personal ? 'Recommended from your profile and current availability' : 'Available now on MANBAR';
}

/**
 * Search only approved, visible MANBAR records. The language model never sees
 * database credentials and never writes or executes SQL.
 */
function ai_search_catalog(string $text, array $user, int $limit = 6): array
{
    if (!ai_search_requested($text)) return [];
    $catalogs = ai_requested_catalogs($text);
    $terms = ai_terms($text);
    $personal = (bool) preg_match('/\b(for me|match me|my skills|my major|suitable|recommend|best for me)\b/iu', $text);
    $profileTerms = $personal ? ai_profile_terms($user) : [];
    $all = [];

    if (in_array('projects', $catalogs, true)) {
        $rows = qall("SELECT p.id,p.title,p.description,p.needed_skills,p.status,p.max_members,u.full_name AS owner_name,
            (SELECT COUNT(*) FROM project_members pm WHERE pm.project_id=p.id) AS members
            FROM projects p JOIN users u ON u.id=p.owner_id WHERE p.hidden=0 AND p.status IN ('open','in_progress') ORDER BY p.id DESC LIMIT 80");
        foreach ($rows as $r) {
            $m = ai_match_score($terms, [(string) $r['title'], (string) $r['needed_skills'], (string) $r['description']], [6, 5, 2]);
            $pm = ai_match_score($profileTerms, [(string) $r['needed_skills'], (string) $r['title'], (string) $r['description']], [3, 2, 1]);
            $space = (int) $r['members'] < (int) $r['max_members'];
            $score = 1 + $m['score'] + $pm['score'] + ($r['status'] === 'open' ? 2 : 0) + ($space ? 2 : 0);
            $all[] = ['type' => 'project', 'title' => $r['title'], 'description' => excerpt((string) $r['description'], 105),
                'meta' => ucfirst(str_replace('_', ' ', $r['status'])) . ' · ' . (int) $r['members'] . '/' . (int) $r['max_members'] . ' members' . ($r['needed_skills'] ? ' · ' . implode(', ', csv_list((string) $r['needed_skills'])) : ''),
                'reason' => ai_result_reason(array_unique([...$m['matches'], ...$pm['matches']]), $personal), 'url' => url('projects/' . $r['id']), 'score' => $score];
        }
    }

    if (in_array('people', $catalogs, true)) {
        $rows = qall("SELECT u.id,u.full_name,u.headline,u.major,u.faculty,u.avatar_url,
            (SELECT " . (db_driver() === 'sqlite' ? "GROUP_CONCAT(name, ', ')" : "GROUP_CONCAT(name SEPARATOR ', ')") . " FROM user_skills s WHERE s.user_id=u.id) AS skills
            FROM users u WHERE u.status='active' AND u.id<>? ORDER BY u.points DESC LIMIT 80", [(int) $user['id']]);
        foreach ($rows as $r) {
            $m = ai_match_score($terms, [(string) $r['full_name'], (string) $r['skills'], (string) $r['major'], (string) $r['headline']], [5, 5, 3, 2]);
            if ($m['score'] <= 0 && !$personal) continue;
            $pm = ai_match_score($profileTerms, [(string) $r['skills'], (string) $r['major'], (string) $r['headline']], [3, 2, 1]);
            $all[] = ['type' => 'person', 'title' => $r['full_name'], 'description' => (string) ($r['headline'] ?: $r['major'] ?: 'MANBAR community member'),
                'meta' => implode(' · ', array_filter([(string) $r['major'], (string) $r['skills']])),
                'reason' => ai_result_reason(array_unique([...$m['matches'], ...$pm['matches']]), $personal), 'url' => url('profile/' . $r['id']), 'score' => 1 + $m['score'] + $pm['score']];
        }
    }

    if (in_array('mentors', $catalogs, true)) {
        $rows = qall("SELECT u.id,u.full_name,u.headline,u.major,m.expertise,m.about,m.availability
            FROM mentor_profiles m JOIN users u ON u.id=m.user_id WHERE m.active=1 AND u.status='active' ORDER BY u.points DESC LIMIT 60");
        foreach ($rows as $r) {
            $m = ai_match_score($terms, [(string) $r['expertise'], (string) $r['full_name'], (string) $r['about'], (string) $r['major']], [6, 4, 2, 3]);
            $pm = ai_match_score($profileTerms, [(string) $r['expertise'], (string) $r['about'], (string) $r['major']], [3, 1, 2]);
            $all[] = ['type' => 'mentor', 'title' => $r['full_name'], 'description' => excerpt((string) ($r['about'] ?: $r['headline'] ?: 'Available to guide MANBAR students'), 105),
                'meta' => implode(' · ', array_filter([(string) $r['expertise'], (string) $r['availability']])),
                'reason' => ai_result_reason(array_unique([...$m['matches'], ...$pm['matches']]), $personal), 'url' => url('profile/' . $r['id']), 'score' => 2 + $m['score'] + $pm['score']];
        }
    }

    if (in_array('courses', $catalogs, true)) {
        $rows = qall("SELECT c.id,c.title,c.description,c.category,c.level,u.full_name AS author_name FROM courses c JOIN users u ON u.id=c.author_id WHERE c.status='published' ORDER BY c.id DESC LIMIT 80");
        foreach ($rows as $r) {
            $m = ai_match_score($terms, [(string) $r['title'], (string) $r['category'], (string) $r['description']], [6, 5, 2]);
            $pm = ai_match_score($profileTerms, [(string) $r['category'], (string) $r['title'], (string) $r['description']], [3, 2, 1]);
            $all[] = ['type' => 'course', 'title' => $r['title'], 'description' => excerpt((string) $r['description'], 105),
                'meta' => ucfirst($r['level']) . ' · ' . ucfirst($r['category']) . ' · ' . $r['author_name'],
                'reason' => ai_result_reason(array_unique([...$m['matches'], ...$pm['matches']]), $personal), 'url' => url('learn/' . $r['id']), 'score' => 1 + $m['score'] + $pm['score']];
        }
    }

    if (in_array('services', $catalogs, true)) {
        $rows = qall("SELECT s.id,s.title,s.description,s.category,s.price,s.delivery_days,u.full_name AS seller_name FROM services s JOIN users u ON u.id=s.user_id WHERE s.status='active' ORDER BY s.id DESC LIMIT 80");
        foreach ($rows as $r) {
            $m = ai_match_score($terms, [(string) $r['title'], (string) $r['category'], (string) $r['description']], [6, 5, 2]);
            if ($m['score'] <= 0 && !$personal) continue;
            $pm = ai_match_score($profileTerms, [(string) $r['category'], (string) $r['title'], (string) $r['description']], [3, 2, 1]);
            $all[] = ['type' => 'service', 'title' => $r['title'], 'description' => excerpt((string) $r['description'], 105),
                'meta' => ((int) $r['price'] ? (int) $r['price'] . ' AED' : 'Free / skill swap') . ' · ' . (int) $r['delivery_days'] . ' days · ' . $r['seller_name'],
                'reason' => ai_result_reason(array_unique([...$m['matches'], ...$pm['matches']]), $personal), 'url' => url('marketplace/' . $r['id']), 'score' => 1 + $m['score'] + $pm['score']];
        }
    }

    usort($all, fn($a, $b) => $b['score'] <=> $a['score']);
    $all = array_slice($all, 0, max(1, min(10, $limit)));
    return array_map(function ($r) { unset($r['score']); return $r; }, $all);
}

function ai_guide_suggestions(string $text, array $results): array
{
    if ($results) return ['Recommend the best match for me', 'Find people with matching skills', 'Show me open projects'];
    if (preg_match('/\b(hello|hi|hey|help)\b/i', $text)) return ['Find an open mobile project', 'Recommend a mentor for me', 'Improve my writing'];
    return ['Find projects that match my skills', 'Find a mentor', 'Show learning courses'];
}

/* ============ Optional language-model providers ============ */
function ai_openai(string $text, string $mode, ?array $user, string $style = 'body'): ?array
{
    $key = cfg('openai_api_key');
    if (!$key || !function_exists('curl_init')) return null;

    $targets = [];
    foreach (AI_TARGETS as $k => $v) $targets[] = "$k = {$v['label']}: {$v['hint']}";
    $styleRule = $style === 'title'
        ? 'The text is a title: keep it concise and do not add a final period.'
        : 'Keep the result concise, natural and suitable for a university community.';
    $instructions = "You are MANBAR's expert English writing assistant. Return only the requested structured result. "
        . "Rewrite the user's text as fluent, natural writing in the same language. Infer obvious misspellings from context, fix grammar and punctuation, remove awkward repetition, and choose clearer vocabulary. "
        . "Preserve the user's intended meaning and tone. Never invent facts, promises, prices, deadlines, qualifications or project details. {$styleRule}\n"
        . "Choose the most suitable MANBAR target and suggest up to five short lower-case tags. Targets:\n" . implode("\n", $targets);
    $schema = [
        'type' => 'object',
        'properties' => [
            'corrected' => ['type' => 'string', 'description' => 'The polished text only.'],
            'target' => ['type' => 'string', 'enum' => array_keys(AI_TARGETS)],
            'reason' => ['type' => 'string', 'description' => 'One short, friendly routing reason.'],
            'tags' => ['type' => 'array', 'items' => ['type' => 'string']],
        ],
        'required' => ['corrected', 'target', 'reason', 'tags'],
        'additionalProperties' => false,
    ];
    $payload = [
        'model' => cfg('openai_model'),
        'instructions' => $instructions,
        'input' => $text,
        'max_output_tokens' => 900,
        'store' => false,
        'text' => ['format' => ['type' => 'json_schema', 'name' => 'manbar_writing_review', 'strict' => true, 'schema' => $schema]],
    ];
    $ch = curl_init('https://api.openai.com/v1/responses');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25, CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['content-type: application/json', 'authorization: Bearer ' . $key],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ]);
    $res = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (!$res || $status < 200 || $status >= 300) return null;

    $json = json_decode($res, true);
    $outputText = (string) ($json['output_text'] ?? '');
    if ($outputText === '') {
        foreach (($json['output'] ?? []) as $item) {
            foreach (($item['content'] ?? []) as $content) {
                if (($content['type'] ?? '') === 'output_text' && isset($content['text'])) $outputText .= (string) $content['text'];
            }
        }
    }
    $out = $outputText !== '' ? json_decode($outputText, true) : null;
    if (!is_array($out) || trim((string) ($out['corrected'] ?? '')) === '') return null;
    $out['_engine'] = 'openai';
    return $out;
}

function ai_llm(string $text, string $mode, ?array $user, string $style = 'body'): ?array
{
    $key = cfg('anthropic_api_key');
    if (!$key || !function_exists('curl_init')) return null;
    $targets = [];
    foreach (AI_TARGETS as $k => $v) $targets[] = "$k = {$v['label']}: {$v['hint']}";
    $styleRule = $style === 'title' ? 'The text is a title: keep it concise and do not add a final period. ' : '';
    $system = "You are the MANBAR assistant for a university student platform. Reply with ONLY compact JSON. "
        . "Keys: corrected (rewrite the user's text as fluent, natural writing in the same language; fix misspellings, grammar, punctuation, awkward wording and weak vocabulary while preserving the meaning and not adding factual claims), "
        . "target (best key from the list), reason (one friendly sentence), tags (up to 5 lower-case tags). Targets:\n" . implode("\n", $targets);
    $system .= ' ' . $styleRule;
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
    if (!is_array($out) || empty($out['corrected'])) return null;
    $out['_engine'] = 'claude';
    return $out;
}

function ai_assist(string $text, ?array $user, string $mode = 'both', string $style = 'body', bool $allowLlm = true): array
{
    $text = mb_substr($text, 0, 4000);
    $fix = fix_writing($text, $style);
    $route = route_idea($text, $user);
    $llm = $allowLlm ? (ai_openai($text, $mode, $user, $style) ?? ai_llm($text, $mode, $user, $style)) : null;
    if ($llm) {
        $fix = ['corrected' => (string) $llm['corrected'], 'changes' => [['from' => '…', 'to' => '…', 'why' => 'Improved by AI']], 'engine' => (string) ($llm['_engine'] ?? 'ai')];
        $k = $llm['target'] ?? '';
        if (isset(AI_TARGETS[$k])) {
            $route['primary'] = array_merge(['key' => $k], AI_TARGETS[$k], ['reason' => (string) ($llm['reason'] ?? ''), 'url' => url(AI_TARGETS[$k]['url'])]);
            $route['confidence'] = 0.9;
        }
        if (!empty($llm['tags']) && is_array($llm['tags'])) $route['tags'] = array_slice(array_map('strval', $llm['tags']), 0, 5);
    }
    return ['fix' => $fix, 'route' => $route];
}

/** Remove a natural-language editing command so the correction card contains only the user's draft. */
function ai_writing_draft(string $text): string
{
    $patterns = [
        '/^\s*(?:please\s+)?(?:correct|fix|check|improve|polish|rewrite|rephrase)(?:\s+(?:the\s+)?(?:grammar|grammer|spelling|writing))?(?:\s+(?:in|for|of)\s*)?[:\-–—]\s*(.+)$/isu',
        '/^\s*(?:please\s+)?(?:correct|fix|check)(?:\s+(?:the\s+)?(?:grammar|grammer|spelling))(?:\s+(?:in|for|of))\s+(.+)$/isu',
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $text, $match) && trim((string) $match[1]) !== '') return trim((string) $match[1]);
    }
    return trim($text);
}

function ai_guide_assist(string $text, array $user, bool $allowLlm = true): array
{
    $grammarRequest = (bool) preg_match('/\b(grammar|grammer|spelling|correct|rewrite|rephrase|polish|improve (?:this|my|the) writing)\b/iu', $text);
    $writingDraft = $grammarRequest ? ai_writing_draft($text) : $text;
    $assist = ai_assist($writingDraft, $user, 'guide', 'message', $allowLlm);
    $results = ai_search_catalog($text, $user);
    $corrected = $assist['fix']['corrected'];
    $route = $assist['route'];
    $greeting = (bool) preg_match('/^\s*(hi|hello|hey|help|what can you do)[!?.\s]*$/iu', $text);

    if ($results) {
        $intent = 'search';
        $types = array_values(array_unique(array_column($results, 'type')));
        $labels = array_map(fn($t) => $t === 'person' ? 'people' : $t . 's', $types);
        $reply = 'I searched MANBAR and found ' . count($results) . ' strong ' . (count($results) === 1 ? 'match' : 'matches') . ' in ' . implode(', ', $labels) . '. I ranked visible results by your request' . (preg_match('/\b(for me|my skills|my major|suitable|best for me|recommend)\b/iu', $text) ? ' and profile.' : '.');
    } elseif ($grammarRequest) {
        $intent = 'writing';
        $reply = $corrected !== $writingDraft
            ? 'I cleaned up the grammar without changing your meaning. Review the polished version below.'
            : 'Your writing already looks clear. I did not find a safe correction to make.';
    } elseif ($greeting) {
        $intent = 'welcome';
        $reply = 'I can improve a title or description, find real projects and teammates, recommend mentors or courses, and take you to the right MANBAR page. Try one of the examples below.';
    } elseif (ai_search_requested($text)) {
        $intent = 'search_empty';
        $reply = 'I could not find a close visible match yet. Try adding a skill, subject, major, or technology such as Flutter, AI, design, or business.';
    } else {
        $intent = 'route';
        $p = $route['primary'];
        $reply = "The best place for this is **{$p['label']}**. {$p['reason']}";
    }

    return $assist + [
        'reply' => $reply,
        'intent' => $intent,
        'results' => $results,
        'suggestions' => ai_guide_suggestions($text, $results),
        'capabilities' => ['writing', 'projects', 'people', 'mentors', 'courses', 'services', 'navigation'],
    ];
}
