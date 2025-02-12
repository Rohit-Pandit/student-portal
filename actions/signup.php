<?php
require_once '../database/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role'];
    $subjects = $_POST['subjects'] ?? []; // Array of selected subject IDs

    if (empty($full_name) || empty($email) || empty($password) || empty($role)) {
        die("Error: Full Name, Email, Password, and Role are required fields.");
    }

    // Insert into appropriate table
    if ($role === 'student') {
        $stmt = $conn->prepare("INSERT INTO students (full_name, email, password) VALUES (?, ?, ?)");
    } elseif ($role === 'teacher') {
        $stmt = $conn->prepare("INSERT INTO teachers (full_name, email, password) VALUES (?, ?, ?)");
    } else {
        die("Error: Invalid role.");
    }

    $stmt->bind_param("sss", $full_name, $email, $password);

    if ($stmt->execute()) {
        $user_id = $stmt->insert_id; // Get the ID of the inserted user

        // Insert selected subjects into enrollments table
        if (!empty($subjects)) {
            $enrollment_stmt = $conn->prepare("INSERT INTO enrollments (user_id, subject_id, role) VALUES (?, ?, ?)");
            foreach ($subjects as $subject_id) {
                $enrollment_stmt->bind_param("iis", $user_id, $subject_id, $role);
                $enrollment_stmt->execute();
            }
            $enrollment_stmt->close();
        }

        echo "Signup successful! You can now <a href='../views/login.html'>Login</a>";
    } else {
        die("Error: " . $stmt->error);
    }

    $stmt->close();
    $conn->close();
}
?>
