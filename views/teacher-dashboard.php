<?php
// Start the session
session_start();

// Include the database connection
require_once '../database/db.php';

// Check if the user is logged in and has the role of 'teacher'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit();
}

// Fetch the logged-in teacher's data
$user_id = $_SESSION['user_id'];
$query = $conn->prepare("SELECT full_name, email FROM teachers WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows > 0) {
    $teacher = $result->fetch_assoc();
} else {
    echo "Error: Teacher not found.";
    exit();
}

// Fetch the subjects assigned to the teacher from the enrollments table
$subjects_query = $conn->prepare("
    SELECT s.subject_name 
    FROM enrollments e
    JOIN subjects s ON e.subject_id = s.id 
    WHERE e.user_id = ? AND e.role = 'teacher'
");
$subjects_query->bind_param("i", $user_id);
$subjects_query->execute();
$subjects_result = $subjects_query->get_result();

$assigned_subjects = [];
while ($row = $subjects_result->fetch_assoc()) {
    $assigned_subjects[] = $row['subject_name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <link rel="stylesheet" href="../styles/style.css">
</head>
<body>
    <div class="dashboard-container">
        <h1>Welcome, <?php echo htmlspecialchars($teacher['full_name']); ?>!</h1>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($teacher['email']); ?></p>
        <p><strong>Assigned Subjects:</strong></p>
        <ul>
            <?php
            if (empty($assigned_subjects)) {
                echo "<li>No subjects assigned yet.</li>";
            } else {
                foreach ($assigned_subjects as $subject) {
                    echo "<li>" . htmlspecialchars($subject) . "</li>";
                }
            }
            ?>
        </ul>
        <a href="../actions/logout.php" class="btn">Logout</a>
    </div>
</body>
</html>
