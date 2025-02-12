<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../views/login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_SESSION['user_id'];
    $subject_id = $_POST['subject_id'];
    $file = $_FILES['assignment_file'];

    // Validate file type
    if ($file['type'] !== 'application/pdf') {
        die("Error: Only PDF files are allowed.");
    }

    // Generate unique file name and move file to uploads directory
    $upload_dir = '../uploads/';
    $file_name = uniqid() . '-' . basename($file['name']);
    $file_path = $upload_dir . $file_name;

    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        die("Error: Failed to upload file.");
    }

    // Save assignment details to the database
    $query = $conn->prepare("
        INSERT INTO assignments (student_id, subject_id, file_path) 
        VALUES (?, ?, ?)
    ");
    $query->bind_param("iis", $student_id, $subject_id, $file_path);

    if ($query->execute()) {
        echo "Assignment uploaded successfully!";
    } else {
        echo "Error: " . $query->error;
    }
}
?>
