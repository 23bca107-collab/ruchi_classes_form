<?php
session_start();
require '../db.php'; // db.php student folder ke bahar hai

// ✅ Check login
if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'student') {
    header("Location: login.php"); // same folder me login.php hai
    exit();
}

$user_id = $_SESSION['user_id'];
$student_name = $_SESSION['student_name'] ?? 'Student';

// ✅ Fetch complaints from database
$stmt = $conn->prepare("SELECT * FROM complaints WHERE user_type = 'student' AND user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Complaint History</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #eef2f7;
      padding: 30px;
    }
    .container {
      max-width: 850px;
      margin: auto;
      background: #fff;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    h2 {
      text-align: center;
      color: #333;
      margin-bottom: 20px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th, td {
      padding: 12px;
      border-bottom: 1px solid #ddd;
      text-align: left;
    }
    th {
      background: #007bff;
      color: white;
    }
    tr:nth-child(even) {
      background: #f9f9f9;
    }
    .status {
      font-weight: bold;
      padding: 5px 8px;
      border-radius: 5px;
    }
    .status-pending { background: #fff3cd; color: #856404; }
    .status-resolved { background: #d4edda; color: #155724; }
    .status-rejected { background: #f8d7da; color: #721c24; }
    .back-link {
      display: inline-block;
      margin-top: 20px;
      text-decoration: none;
      color: #007bff;
      font-weight: bold;
    }
    .back-link:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Complaint History - <?php echo htmlspecialchars($student_name); ?></h2>

    <?php if ($result->num_rows > 0): ?>
      <table>
        <tr>
          <th>ID</th>
          <th>Complaint</th>
          <th>Date</th>
          <th>Status</th>
        </tr>
        <?php while($row = $result->fetch_assoc()): ?>
          <?php 
            $status = $row['status'] ?? 'Pending';
            $class = 'status-pending';
            if ($status === 'Resolved') $class = 'status-resolved';
            elseif ($status === 'Rejected') $class = 'status-rejected';
          ?>
          <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['complaint']); ?></td>
            <td><?php echo date("d M Y, h:i A", strtotime($row['created_at'])); ?></td>
            <td><span class="status <?php echo $class; ?>"><?php echo $status; ?></span></td>
          </tr>
        <?php endwhile; ?>
      </table>
    <?php else: ?>
      <p>No complaints submitted yet.</p>
    <?php endif; ?>

    <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
  </div>
</body>
</html>
