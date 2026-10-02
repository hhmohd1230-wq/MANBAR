<?php
/** Schema rendering + seed data shared by install.php and build_sql.php */

function schema_statements(string $driver): array
{
    $sql = file_get_contents(__DIR__ . '/schema.tpl.sql');
    $sql = preg_replace('/--.*$/m', '', $sql);
    $pk = $driver === 'sqlite' ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';
    $engine = $driver === 'sqlite' ? '' : 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    $sql = str_replace(['{{PK}}', '{{ENGINE}}'], [$pk, $engine], $sql);
    return array_values(array_filter(array_map('trim', explode(';', $sql))));
}

function q_(?string $v): string { return $v === null ? 'NULL' : "'" . str_replace("'", "''", $v) . "'"; }

function seed_statements(): array
{
    $now = date('Y-m-d H:i:s');
    $out = [];

    // Universities — only Al Ain University is open for now; others can be switched on from the admin dashboard.
    foreach ([
        ['Al Ain University', 'AAU', 'aau.ac.ae', 1],
        ['Abu Dhabi University', 'ADU', 'adu.ac.ae', 0],
        ['United Arab Emirates University', 'UAEU', 'uaeu.ac.ae', 0],
    ] as [$n, $s, $d, $a]) {
        $out[] = "INSERT INTO universities (name, short_name, domain, active, created_at) VALUES (" . q_($n) . ',' . q_($s) . ',' . q_($d) . ",$a," . q_($now) . ')';
    }

    // Official roster: student number (from the e-mail) -> name & details. Admin can import more via CSV.
    foreach ([
        ['202020280', 'Yaman Mhd Laith AlNasri', 'Software Engineering', 4],
        ['202211424', 'Tamim Ahmed Alzein', 'Software Engineering', 4],
        ['202210908', 'Ghaith Shujaa Alsalim', 'Software Engineering', 4],
        ['202212000', 'Muhammad Toufeeq', 'Software Engineering', 4],
        ['202210821', 'Rami Loay Albaini', 'Software Engineering', 4],
    ] as [$id, $n, $m, $y]) {
        $out[] = "INSERT INTO roster (university_id, student_id, full_name, major, faculty, year_level, created_at) VALUES (1," . q_($id) . ',' . q_($n) . ',' . q_($m) . ",'College of Engineering',$y," . q_($now) . ')';
    }

    foreach ([
        ['profile_pro', 'Profile Pro', 'Completed your profile', 'user', 'green'],
        ['first_post', 'First Voice', 'Published your first post', 'chat', 'blue'],
        ['idea_machine', 'Idea Machine', 'Shared 5 ideas', 'bulb', 'amber'],
        ['conversationalist', 'Conversationalist', 'Wrote 10 comments', 'chat', 'violet'],
        ['team_player', 'Team Player', 'Joined a project team', 'users', 'teal'],
        ['project_leader', 'Project Leader', 'Started a project', 'flag', 'rose'],
        ['service_pro', 'Service Pro', 'Listed a service in the marketplace', 'briefcase', 'orange'],
        ['five_star', 'Five Star', 'Received a 5-star review', 'star', 'amber'],
        ['learner', 'Curious Mind', 'Enrolled in a course', 'book', 'blue'],
        ['graduate', 'Graduate', 'Completed a course', 'cap', 'green'],
        ['mentee', 'Mentee', 'Got accepted by a mentor', 'heart', 'rose'],
        ['popular', 'Rising Star', 'Gained 5 followers', 'fire', 'orange'],
        ['centurion', 'Centurion', 'Reached 100 points', 'trophy', 'amber'],
    ] as [$c, $n, $d, $i, $t]) {
        $out[] = "INSERT INTO badges (code, name, description, icon, tone) VALUES (" . q_($c) . ',' . q_($n) . ',' . q_($d) . ',' . q_($i) . ',' . q_($t) . ')';
    }

    foreach ([['registration_open', '1'], ['site_notice', '']] as [$k, $v]) {
        $out[] = "INSERT INTO settings (k, v) VALUES (" . q_($k) . ',' . q_($v) . ')';
    }
    return $out;
}
