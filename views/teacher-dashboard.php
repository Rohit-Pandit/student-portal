<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../views/login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch teacher details
$teacher_query = $conn->prepare("SELECT full_name, email FROM teachers WHERE id = ?");
$teacher_query->bind_param("i", $user_id);
$teacher_query->execute();
$teacher_result = $teacher_query->get_result();
$teacher = $teacher_result->fetch_assoc();

// Fetch assigned subjects
$subjects_query = $conn->prepare("
    SELECT subjects.subject_name 
    FROM enrollments 
    JOIN subjects ON enrollments.subject_id = subjects.id 
    WHERE enrollments.user_id = ? AND enrollments.role = 'teacher'
");
$subjects_query->bind_param("i", $user_id);
$subjects_query->execute();
$subjects_result = $subjects_query->get_result();

// Fetch assignments to be reviewed
$assignments_query = $conn->prepare("
    SELECT 
        assignments.id AS assignment_id, 
        subjects.subject_name, 
        students.full_name AS student_name, 
        assignments.file_path, 
        assignments.created_at, 
        assignments.grade 
    FROM assignments 
    JOIN subjects ON assignments.subject_id = subjects.id 
    JOIN students ON assignments.student_id = students.id 
    WHERE assignments.subject_id IN (
        SELECT subject_id 
        FROM enrollments 
        WHERE user_id = ? AND role = 'teacher'
    ) AND (assignments.grade IS NULL OR assignments.grade = '')
");
$assignments_query->bind_param("i", $user_id);
$assignments_query->execute();
$assignments_result = $assignments_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <link rel="stylesheet" href="">
</head>
<body>
    <div class="dashboard-container">
        <h1>Welcome, <?php echo htmlspecialchars($teacher['full_name']); ?></h1>
        <p>Email: <?php echo htmlspecialchars($teacher['email']); ?></p>

        <!-- Assigned Subjects Section -->
        <h2>Your Assigned Subjects</h2>
        <?php if ($subjects_result->num_rows > 0): ?>
            <ul>
                <?php while ($subject = $subjects_result->fetch_assoc()): ?>
                    <li><?php echo htmlspecialchars($subject['subject_name']); ?></li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p>You are not assigned to any subjects.</p>
        <?php endif; ?>

        <!-- Assignments to Review Section -->
        <h2>Assignments to Review</h2>
        <?php if ($assignments_result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Subject</th>
                        <th>File</th>
                        <th>Upload Date</th>
                        <th>Grade</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($assignment = $assignments_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($assignment['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($assignment['subject_name']); ?></td>
                            <td>
                                <a href="../uploads/<?php echo htmlspecialchars($assignment['file_path']); ?>" target="_blank">
                                    <?php echo htmlspecialchars($assignment['file_path']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($assignment['created_at']); ?></td>
                            <td>
                                <?php echo $assignment['grade'] ? htmlspecialchars($assignment['grade']) : "Not Graded"; ?>
                            </td>
                            <td>
                                <form method="POST" action="../actions/grade-assignment.php">
                                    <input type="hidden" name="assignment_id" value="<?php echo $assignment['assignment_id']; ?>">
                                    <select name="grade" required>
                                        <option value="">Select Grade</option>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                        <option value="D">D</option>
                                        <option value="F">F</option>
                                    </select>
                                    <button type="submit">Submit</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No assignments to review.</p>
        <?php endif; ?>

        <a href="../actions/logout.php">Logout</a>
    </div>
</body>
</html>
