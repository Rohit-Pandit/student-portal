<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../views/login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assignment_id = $_POST['assignment_id'];
    $grade = $_POST['grade'];

    // Update assignment grade
    $query = $conn->prepare("UPDATE assignments SET grade = ? WHERE id = ?");
    $query->bind_param("si", $grade, $assignment_id);

    if ($query->execute()) {
        echo "Grade submitted successfully!";
    } else {
        echo "Error: " . $query->error;
    }
}
?>
