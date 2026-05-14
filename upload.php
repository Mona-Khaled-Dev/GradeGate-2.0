<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') { header('Location: login.php'); exit; }
$conn = mysqli_connect('localhost', 'root', '1', 'gradegate2') or die('Connection failed');
$sid = $_SESSION['user_id'];

// build options for dropdown
$subject_options = '';
$res = mysqli_query($conn, "SELECT sub.id, sub.name FROM subjects sub JOIN enrollments e ON sub.id=e.subject_id WHERE e.student_id=$sid");
while ($r = mysqli_fetch_assoc($res)) $subject_options .= '<option value="'.$r['id'].'">'.$r['name'].'</option>';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mysqli_close($conn);
    include 'upload_form.html'; exit;
}

$sub_id = (int)$_POST['subject_id'];
if (!isset($_FILES['assignment']) || $_FILES['assignment']['error'] === 4) {
    $message = 'Please select a file.';
} elseif ($_FILES['assignment']['error'] !== 0) {
    $message = 'Upload error. Code: '.$_FILES['assignment']['error'];
} else {
    $file = $_FILES['assignment']['name'];
    if ($file == '') { $message = 'Please select a file.'; }
    else {
        $safe = mysqli_real_escape_string($conn, $file);
        $sql = "INSERT INTO assignments (student_id, subject_id, file_name, upload_date) VALUES ($sid, $sub_id, '$safe', NOW())";
        if (mysqli_query($conn, $sql)) $message = 'Assignment uploaded!';
        else $message = 'Database error: '.mysqli_error($conn);
    }
}
mysqli_close($conn);
include 'upload_result.html';