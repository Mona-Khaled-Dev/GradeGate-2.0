<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') { header('Location: login.php'); exit; }
$conn = mysqli_connect('localhost', 'root', '1', 'gradegate2') or die('Connection failed');
$tid = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$action = $_GET['action'] ?? '';

if ($action == 'manage_students') {
    $msg = ''; $enrollment_msg = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
        $u = mysqli_real_escape_string($conn, $_POST['username']);
        $f = mysqli_real_escape_string($conn, $_POST['full_name']);
        $p = $_POST['password'];
        if ($u==''||$f==''||$p=='') $msg = 'All fields required.';
        else {
            $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$u'");
            if (mysqli_num_rows($check)>0) $msg = 'Username taken.';
            else { mysqli_query($conn, "INSERT INTO users (username, password, role, full_name) VALUES ('$u','$p','student','$f')"); $msg = 'Student added.'; }
        }
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll_student'])) {
        $sid = (int)$_POST['student_id']; $subid = (int)$_POST['subject_id'];
        $check = mysqli_query($conn, "SELECT * FROM enrollments WHERE student_id=$sid AND subject_id=$subid");
        if (mysqli_num_rows($check)>0) $enrollment_msg = 'Already enrolled.';
        else { mysqli_query($conn, "INSERT INTO enrollments (student_id, subject_id) VALUES ($sid, $subid)"); $enrollment_msg = 'Enrolled.'; }
    }
    $students_list = ''; $res = mysqli_query($conn, "SELECT id, username, full_name FROM users WHERE role='student'");
    while ($r = mysqli_fetch_assoc($res)) $students_list .= '<li>'.$r['full_name'].' ('.$r['username'].')</li>';
    $my_subs = []; $res = mysqli_query($conn, "SELECT id, name FROM subjects WHERE teacher_id=$tid");
    while ($r = mysqli_fetch_assoc($res)) $my_subs[] = $r;
    $student_opts = ''; $subject_opts = ''; $enroll_form = false;
    if (!empty($my_subs)) {
        mysqli_data_seek($res,0);
        $res = mysqli_query($conn, "SELECT id, full_name FROM users WHERE role='student'");
        while ($r = mysqli_fetch_assoc($res)) $student_opts .= '<option value="'.$r['id'].'">'.$r['full_name'].'</option>';
        foreach ($my_subs as $sub) $subject_opts .= '<option value="'.$sub['id'].'">'.$sub['name'].'</option>';
        $enroll_form = true;
    }
    mysqli_close($conn);
    include 'manage_students.html'; exit;
}

if ($action == 'view_submissions') {
    $submissions_table = '';
    $res = mysqli_query($conn, "SELECT a.file_name, a.upload_date, a.grade, u.full_name AS sn, s.name AS subj FROM assignments a JOIN users u ON a.student_id=u.id JOIN subjects s ON a.subject_id=s.id WHERE s.teacher_id=$tid ORDER BY a.upload_date DESC");
    while ($r = mysqli_fetch_assoc($res)) {
        $g = $r['grade'] ?? 'Not graded';
        $submissions_table .= "<tr><td>{$r['sn']}</td><td>{$r['subj']}</td><td>{$r['file_name']}</td><td>{$r['upload_date']}</td><td>$g</td></tr>";
    }
    if (empty($submissions_table)) $submissions_table = '<p>No submissions yet.</p>';
    else $submissions_table = '<table><tr><th>Student</th><th>Subject</th><th>File</th><th>Uploaded</th><th>Grade</th></tr>'.$submissions_table.'</table>';
    mysqli_close($conn);
    include 'view_submissions.html'; exit;
}

// dashboard
$subject_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_subject'])) {
    $name = mysqli_real_escape_string($conn, $_POST['subject_name']);
    if ($name != '') { mysqli_query($conn, "INSERT INTO subjects (name, teacher_id) VALUES ('$name', $tid)"); $subject_msg = 'Subject created.'; }
    else $subject_msg = 'Please enter a name.';
}
$subjects_list = '';
$res = mysqli_query($conn, "SELECT * FROM subjects WHERE teacher_id=$tid");
while ($r = mysqli_fetch_assoc($res)) $subjects_list .= '<li>'.$r['name'].'</li>';
mysqli_close($conn);
include 'teacher_dashboard.html';