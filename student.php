<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') { header('Location: login.php'); exit; }
$conn = mysqli_connect('localhost', 'root', '1', 'gradegate2') or die('Connection failed');
$sid = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

if (isset($_GET['view']) && $_GET['view'] == 'grades') {
    $grades = []; $avg = [];
    $res = mysqli_query($conn, "SELECT s.name AS sub, a.file_name, a.upload_date, a.grade, a.comment FROM assignments a JOIN subjects s ON a.subject_id=s.id WHERE a.student_id=$sid ORDER BY s.name, a.upload_date DESC");
    while ($r = mysqli_fetch_assoc($res)) $grades[] = $r;
    $res2 = mysqli_query($conn, "SELECT s.name, AVG(a.grade) AS av FROM assignments a JOIN subjects s ON a.subject_id=s.id WHERE a.student_id=$sid AND a.grade IS NOT NULL GROUP BY s.name");
    while ($r = mysqli_fetch_assoc($res2)) $avg[$r['name']] = round($r['av'],2);
    $avg_html = ''; foreach ($avg as $k=>$v) $avg_html .= "<li><strong>$k:</strong> $v%</li>";
    $grades_table = '<table><tr><th>Subject</th><th>File</th><th>Uploaded</th><th>Grade</th><th>Comment</th></tr>';
    foreach ($grades as $g) {
        $gr = $g['grade'] ?? 'Pending'; $com = $g['comment'] ?? '';
        $grades_table .= "<tr><td>{$g['sub']}</td><td>{$g['file_name']}</td><td>{$g['upload_date']}</td><td>$gr</td><td>$com</td></tr>";
    }
    $grades_table .= '</table>';
    if (empty($grades)) $grades_table = '<p>No grades yet.</p>';
    if (empty($avg)) $avg_html = '';
    else $avg_html = '<h3>Subject Averages</h3><ul>'.$avg_html.'</ul>';
    mysqli_close($conn);
    include 'student_grades.html'; exit;
}

$enroll_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll_self'])) {
    $sub_id = (int)$_POST['subject_id'];
    $check = mysqli_query($conn, "SELECT * FROM enrollments WHERE student_id=$sid AND subject_id=$sub_id");
    if (mysqli_num_rows($check) > 0) $enroll_msg = 'Already enrolled.';
    else { mysqli_query($conn, "INSERT INTO enrollments (student_id, subject_id) VALUES ($sid, $sub_id)"); $enroll_msg = 'Enrolled!'; }
}
$subjects_list = '';
$enrolled = mysqli_query($conn, "SELECT sub.id, sub.name FROM subjects sub JOIN enrollments e ON sub.id=e.subject_id WHERE e.student_id=$sid");
while ($r = mysqli_fetch_assoc($enrolled)) $subjects_list .= '<li>'.$r['name'].'</li>';
$all = mysqli_query($conn, "SELECT id, name FROM subjects");
$available_form = '';
while ($r = mysqli_fetch_assoc($all)) {
    $found = false;
    mysqli_data_seek($enrolled,0);
    while ($e = mysqli_fetch_assoc($enrolled)) if ($e['id']==$r['id']) $found = true;
    if (!$found) $available_form .= '<option value="'.$r['id'].'">'.$r['name'].'</option>';
}
mysqli_close($conn);
include 'student_dashboard.html';