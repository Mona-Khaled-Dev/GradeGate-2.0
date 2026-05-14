<?php
session_start();
$conn = mysqli_connect('localhost', 'root', '1', 'gradegate2') or die('Connection failed');

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}
if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['role'] == 'student' ? 'student.php' : 'teacher.php'));
    exit;
}

$error = '';
$success = '';
$mode = $_POST['action'] ?? $_GET['mode'] ?? 'home';
$page = $_GET['page'] ?? '';

// ---- register ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode == 'register') {
    $u = mysqli_real_escape_string($conn, $_POST['username']);
    $f = mysqli_real_escape_string($conn, $_POST['full_name']);
    $p = $_POST['password'];
    $r = $_POST['role'];

    if ($u == '' || $f == '' || $p == '') {
        $error = 'All fields required.';
    } else {
        $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$u'");
        if (mysqli_num_rows($check) > 0) {
            $error = 'Username already taken.';
        } else {
            mysqli_query($conn, "INSERT INTO users (username, password, role, full_name) VALUES ('$u','$p','$r','$f')");
            if ($r == 'student') {
                $newId = mysqli_insert_id($conn);
                $all = mysqli_query($conn, "SELECT id FROM subjects");
                while ($sub = mysqli_fetch_assoc($all)) {
                    mysqli_query($conn, "INSERT INTO enrollments (student_id, subject_id) VALUES ($newId, {$sub['id']})");
                }
            }
            $success = 'Account created! Please log in.';
            $mode = 'login';
        }
    }
}

// ---- login ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode == 'login') {
    $u = mysqli_real_escape_string($conn, $_POST['username']);
    $p = $_POST['password'];
    $res = mysqli_query($conn, "SELECT * FROM users WHERE username='$u' AND password='$p'");
    if ($res && mysqli_num_rows($res) > 0) {
        $user = mysqli_fetch_assoc($res);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        mysqli_close($conn);
        header('Location: ' . ($user['role'] == 'student' ? 'student.php' : 'teacher.php'));
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}

mysqli_close($conn);
include 'login.html';