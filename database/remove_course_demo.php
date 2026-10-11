<?php
/**
 * Removes the demo classmates from a course: their enrollments, lesson progress, quiz attempts/answers and visits.
 * Demo classmates are the seeded demo students (database/demo.php). Real users are never touched.
 *
 *   php database/remove_course_demo.php 4
 */
chdir(dirname(__DIR__));
require 'app/bootstrap.php';
ensure_learning_schema();

$cid = (int) ($argv[1] ?? 0);
if (!$cid || !qrow('SELECT id FROM courses WHERE id = ?', [$cid])) exit("Usage: php database/remove_course_demo.php <course id>\n");

$demo = ['202310101', '202310202', '202310303', '202310404', '202310505', '202310606', '202310707', '202310808', '202310909', '202311010'];
$ids = array_map('intval', array_column(qall('SELECT id FROM users WHERE email IN (' . implode(',', array_fill(0, count($demo), '?')) . ')', array_map(fn($s) => "$s@aau.ac.ae", $demo)), 'id'));
if (!$ids) exit("No demo students found.\n");
$in = implode(',', $ids);

$attempts = array_map('intval', array_column(qall("SELECT a.id FROM quiz_attempts a JOIN quizzes z ON z.id = a.quiz_id WHERE z.course_id = ? AND a.user_id IN ($in)", [$cid]), 'id'));
if ($attempts) {
    qexec('DELETE FROM quiz_answers WHERE attempt_id IN (' . implode(',', $attempts) . ')');
    qexec('DELETE FROM quiz_attempts WHERE id IN (' . implode(',', $attempts) . ')');
}
qexec("DELETE FROM lesson_progress WHERE user_id IN ($in) AND lesson_id IN (SELECT id FROM lessons WHERE course_id = ?)", [$cid]);
qexec("DELETE FROM course_visits WHERE course_id = ? AND user_id IN ($in)", [$cid]);
$n = qexec("DELETE FROM enrollments WHERE course_id = ? AND user_id IN ($in)", [$cid]);
echo "Removed $n demo classmates and " . count($attempts) . " quiz attempts from course $cid.\n";
