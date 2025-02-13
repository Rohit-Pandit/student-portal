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

    // Validate subject_id
    $subject_check = $conn->prepare("SELECT id FROM subjects WHERE id = ? AND id IN (
        SELECT subject_id FROM enrollments WHERE user_id = ?
    )");
    $subject_check->bind_param("ii", $subject_id, $student_id);
    $subject_check->execute();
    $subject_check_result = $subject_check->get_result();

    if ($subject_check_result->num_rows === 0) {
        die("Error: Invalid or unauthorized subject selected.");
    }

    // Validate file type (allow only PDFs)
    $allowed_file_types = ['application/pdf'];
    if (!in_array($file['type'], $allowed_file_types)) {
        die("Error: Only PDF files are allowed.");
    }

    // Check file size (limit to 5 MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        die("Error: File size exceeds the 5MB limit.");
    }

    // Check for file upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        die("Error: File upload failed with error code " . $file['error']);
    }

    // Create upload directory if it doesn't exist
    $upload_dir = '../uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Generate unique file name and move file to uploads directory
    $file_name = uniqid() . '-' . basename($file['name']);
    $file_path = $upload_dir . $file_name;

    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        die("Error: Failed to upload file.");
    }

    // Save assignment details to the database
    $query = $conn->prepare("
        INSERT INTO assignments (student_id, subject_id, file_path, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    $query->bind_param("iis", $student_id, $subject_id, $file_name);

    if ($query->execute()) {
        header("Location: ../views/student-dashboard.php?upload=success");
        exit();
    } else {
        die("Error: " . $conn->error);
    }
} else {
    echo "Error: Invalid request.";
}
?>
