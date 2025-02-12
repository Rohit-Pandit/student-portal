<?php
// Start the session
session_start();

// Include the database connection
require_once '../database/db.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit();
}

// Fetch the logged-in student's data
$user_id = $_SESSION['user_id'];
$query = $conn->prepare("SELECT full_name, email FROM students WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows > 0) {
    $student = $result->fetch_assoc();
} else {
    echo "Error: Student not found.";
    exit();
}

// Fetch the enrolled subjects from the enrollments table
$subjects_query = $conn->prepare("
    SELECT s.subject_name 
    FROM enrollments e
    JOIN subjects s ON e.subject_id = s.id 
    WHERE e.user_id = ? AND e.role = 'student'
");
$subjects_query->bind_param("i", $user_id);
$subjects_query->execute();
$subjects_result = $subjects_query->get_result();

$enrolled_subjects = [];
while ($row = $subjects_result->fetch_assoc()) {
    $enrolled_subjects[] = $row['subject_name'];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="../styles/style.css">
</head>
<body>
    <div class="dashboard-container">
        <h1>Welcome, <?php echo htmlspecialchars($student['full_name']); ?>!</h1>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
        <p><strong>Enrolled Subjects:</strong></p>
        <ul>
            <?php
            foreach ($enrolled_subjects as $subject) {
                echo "<li>" . htmlspecialchars($subject) . "</li>";
            }
            ?>
        </ul>

        <a href="../actions/logout.php" class="btn">Logout</a>
    </div>
</body>
</html>
