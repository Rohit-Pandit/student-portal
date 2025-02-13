<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../views/login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch student details
$student_query = $conn->prepare("SELECT full_name, email FROM students WHERE id = ?");
$student_query->bind_param("i", $user_id);
$student_query->execute();
$student_result = $student_query->get_result();
$student = $student_result->fetch_assoc();

// Fetch enrolled subjects
$subjects_query = $conn->prepare("
    SELECT subjects.subject_name 
    FROM enrollments 
    JOIN subjects ON enrollments.subject_id = subjects.id 
    WHERE enrollments.user_id = ? 
");
$subjects_query->bind_param("i", $user_id);
$subjects_query->execute();
$subjects_result = $subjects_query->get_result();

// Fetch uploaded assignments along with grades
$assignment_query = $conn->prepare("
    SELECT 
        assignments.id AS assignment_id, 
        subjects.subject_name, 
        assignments.file_path, 
        assignments.created_at, 
        assignments.grade 
    FROM assignments 
    JOIN subjects ON assignments.subject_id = subjects.id 
    WHERE assignments.student_id = ?
");
$assignment_query->bind_param("i", $user_id);
$assignment_query->execute();
$assignment_result = $assignment_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="">
</head>
<body>
    <div class="dashboard-container">
        <h1>Welcome, <?php echo htmlspecialchars($student['full_name']); ?></h1>
        <p>Email: <?php echo htmlspecialchars($student['email']); ?></p>

        <!-- Enrolled Subjects Section -->
        <h2>Your Enrolled Subjects</h2>
        <?php if ($subjects_result->num_rows > 0): ?>
            <ul>
                <?php while ($subject = $subjects_result->fetch_assoc()): ?>
                    <li><?php echo htmlspecialchars($subject['subject_name']); ?></li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p>You are not enrolled in any subjects.</p>
        <?php endif; ?>

        <!-- Uploaded Assignments Section -->
        <h2>Your Uploaded Assignments</h2>
        <?php if ($assignment_result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>File Path</th>
                        <th>Upload Date</th>
                        <th>Grade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($assignment = $assignment_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($assignment['subject_name']); ?></td>
                            <td>
                                <a href="../uploads/<?php echo htmlspecialchars($assignment['file_path']); ?>" target="_blank">
                                    <?php echo htmlspecialchars($assignment['file_path']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($assignment['created_at']); ?></td>
                            <td>
                                <?php 
                                echo $assignment['grade'] 
                                    ? htmlspecialchars($assignment['grade']) 
                                    : "Under Review"; 
                                ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No assignments uploaded yet.</p>

        <?php endif; ?>

        <h2>Upload a New Assignment</h2>
        <form action="../actions/upload-assignment.php" method="post" enctype="multipart/form-data">
            <label for="subject">Select Subject:</label>
            <select name="subject_id" id="subject" required>
                <?php
                $subjects_query = $conn->prepare("
                    SELECT subjects.id, subjects.subject_name 
                    FROM enrollments 
                    JOIN subjects ON enrollments.subject_id = subjects.id 
                    WHERE enrollments.user_id = ?
                ");
                $subjects_query->bind_param("i", $user_id);
                $subjects_query->execute();
                $subjects_result = $subjects_query->get_result();

                while ($subject = $subjects_result->fetch_assoc()): ?>
                    <option value="<?php echo $subject['id']; ?>">
                        <?php echo htmlspecialchars($subject['subject_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="assignment_file">Upload Assignment File:</label>
            <input type="file" name="assignment_file" id="assignment_file" required>
            <button type="submit">Upload</button>
        </form>


        <a href="../actions/logout.php">Logout</a>
    </div>
</body>
</html>
