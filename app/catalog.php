<?php
/** Static catalogues used across the UI. */

const POST_TYPES = [
    'idea'         => ['label' => 'Idea',         'icon' => 'bulb',     'tone' => 'amber',  'staff' => false, 'ph' => 'Describe your idea — what problem does it solve, who is it for?'],
    'team'         => ['label' => 'Team request', 'icon' => 'users',    'tone' => 'blue',   'staff' => false, 'ph' => 'Who are you looking for? Mention skills and what you are building.'],
    'question'     => ['label' => 'Question',     'icon' => 'help',     'tone' => 'violet', 'staff' => false, 'ph' => 'What do you need help with? The more detail, the better the answers.'],
    'resource'     => ['label' => 'Resource',     'icon' => 'book',     'tone' => 'teal',   'staff' => false, 'ph' => 'Share notes, links or tutorials that helped you.'],
    'achievement'  => ['label' => 'Showcase',     'icon' => 'trophy',   'tone' => 'rose',   'staff' => false, 'ph' => 'Show what you built or achieved.'],
    'event'        => ['label' => 'Event',        'icon' => 'calendar', 'tone' => 'orange', 'staff' => false, 'ph' => 'What, when and where? Include date and time.'],
    'teaching'     => ['label' => 'Teaching offer','icon' => 'cap',     'tone' => 'green',  'staff' => true,  'ph' => 'Which subject can you help with? When are you available?'],
    'announcement' => ['label' => 'Announcement', 'icon' => 'megaphone','tone' => 'red',    'staff' => true,  'ph' => 'Official notice for students.'],
];

const REACTIONS = [
    'like'      => ['emoji' => '👍', 'label' => 'Like'],
    'love'      => ['emoji' => '💚', 'label' => 'Love'],
    'insight'   => ['emoji' => '💡', 'label' => 'Insightful'],
    'celebrate' => ['emoji' => '🎉', 'label' => 'Celebrate'],
    'support'   => ['emoji' => '🤝', 'label' => 'Support'],
];

const SERVICE_CATEGORIES = [
    'design' => 'Design & Creative', 'programming' => 'Programming & Tech', 'writing' => 'Writing & Translation', 'tutoring' => 'Tutoring & Study help',
    'media' => 'Video, Photo & Audio', 'business' => 'Business & Marketing', 'other' => 'Other',
];

const COURSE_CATEGORIES = [
    'programming' => 'Programming', 'design' => 'Design', 'business' => 'Business & Startups', 'engineering' => 'Engineering', 'languages' => 'Languages',
    'career' => 'Career skills', 'general' => 'General',
];

const FACULTIES = [
    'College of Engineering', 'College of Business', 'College of Education, Humanities & Social Sciences', 'College of Law', 'College of Pharmacy', 'College of Media & Mass Communication', 'Other',
];

/** Cover gradients (index stored in users.theme / courses.theme). */
const THEMES = [
    ['#34b36d', '#a7e8c1'], ['#1f9d8a', '#9be7d7'], ['#5fbf4a', '#d4f2a8'], ['#2a9d6f', '#7fd6b0'], ['#3fb58a', '#c0f0dd'], ['#6bcf8a', '#e2f9b5'],
];

/** Project-specific cover presets. The stored index maps to the CSS classes below. */
const PROJECT_COVER_THEMES = [
    'Campus green', 'Innovation blue', 'Creative violet', 'Desert sunrise',
];
function theme_css(int $i): string
{
    [$a, $b] = THEMES[$i % count(THEMES)];
    return "background: linear-gradient(135deg, $a, $b);";
}

function post_type(string $t): array { return POST_TYPES[$t] ?? POST_TYPES['idea']; }
