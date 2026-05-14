<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header('Location: login.php'); exit;
}
$conn = mysqli_connect('localhost', 'root', '1', 'gradegate2') or die('Connection failed');
$tid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assignment_id'])) {
    $aid = (int)$_POST['assignment_id'];
    $g = $_POST['grade']; if ($g === '') $g = 'NULL';
    $c = mysqli_real_escape_string($conn, $_POST['comment']);
    mysqli_query($conn, "UPDATE assignments SET grade=$g, comment='$c' WHERE id=$aid");
    $message = 'Grade saved.';
    mysqli_close($conn);
    include 'grade_result.html'; exit;
}

$pending_table = '';
$res = mysqli_query($conn, "SELECT a.id, a.file_name, a.upload_date, u.full_name AS sn, s.name AS subj
                            FROM assignments a JOIN users u ON a.student_id=u.id JOIN subjects s ON a.subject_id=s.id
                            WHERE s.teacher_id=$tid AND a.grade IS NULL ORDER BY a.upload_date DESC");
while ($r = mysqli_fetch_assoc($res)) {
    $pending_table .= "<tr>
        <td>{$r['sn']}</td><td>{$r['subj']}</td><td>{$r['file_name']}</td><td>{$r['upload_date']}</td>
        <form method='post' action='grade.php'>
            <input type='hidden' name='assignment_id' value='{$r['id']}'>
            <td><input type='number' step='0.01' name='grade'></td>
            <td><input type='text' name='comment'></td>
            <td><button type='submit'>Save</button></td>
        </form>
    </tr>";
}
if (empty($pending_table)) $pending_table = '<p>No ungraded work.</p>';
else $pending_table = '<table><tr><th>Student</th><th>Subject</th><th>File</th><th>Uploaded</th><th>Grade</th><th>Comment</th><th>Action</th></tr>'.$pending_table.'</table>';
mysqli_close($conn);
include 'grade_form.html';