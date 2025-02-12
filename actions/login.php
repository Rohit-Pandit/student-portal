<?php
require_once '../database/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (empty($email) || empty($password) || empty($role)) {
        die("Error: All fields are required.");
    }

    if ($role === 'student') {
        $stmt = $conn->prepare("SELECT id, full_name, password FROM students WHERE email = ?");
    } elseif ($role === 'teacher') {
        $stmt = $conn->prepare("SELECT id, full_name, password FROM teachers WHERE email = ?");
    } else {
        die("Error: Invalid role.");
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $role;
            $_SESSION['name'] = $user['full_name'];

            if ($role === 'student') {
                header("Location: ../views/student-dashboard.php");
            } elseif ($role === 'teacher') {
                header("Location: ../views/teacher-dashboard.php");
            }
            exit;
        } else {
            echo "Invalid password.";
        }
    } else {
        echo "No user found with the provided email.";
    }

    $stmt->close();
    $conn->close();
}
?>
