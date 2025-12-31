<?php
session_start();
require '../db.php';

// Only admin can access
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $complaint_id = $_POST['complaint_id'];
    $new_status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE complaints SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $complaint_id);
    
    if ($stmt->execute()) {
        $message = "Status updated successfully!";
        $message_type = "success";
    } else {
        $message = "Error updating status: " . $conn->error;
        $message_type = "error";
    }
}

// First, let's check what columns exist in your tables
// Fetch complaints with basic user ID information
$result = $conn->query("
    SELECT c.id, c.user_type, c.user_id, c.complaint, c.status, c.created_at
    FROM complaints c
    ORDER BY c.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaints Admin Dashboard</title>
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #7209b7;
            --success: #4cc9f0;
            --warning: #f8961e;
            --danger: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            animation: fadeIn 0.8s ease-out;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .header h1 {
            color: var(--primary);
            font-weight: 600;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .logout-btn {
            background: var(--danger);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-block;
        }

        .logout-btn:hover {
            background: #d1144a;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            text-align: center;
            transition: var(--transition);
            animation: slideUp 0.5s ease-out;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .stat-card h3 {
            font-size: 14px;
            color: var(--gray);
            margin-bottom: 10px;
        }

        .stat-card .count {
            font-size: 32px;
            font-weight: 700;
        }

        .pending { color: var(--warning); }
        .resolved { color: var(--success); }

        .alert {
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            animation: slideDown 0.5s ease-out;
        }

        .alert-success {
            background-color: rgba(76, 201, 240, 0.2);
            color: #0c5460;
            border-left: 4px solid var(--success);
        }

        .alert-error {
            background-color: rgba(247, 37, 133, 0.2);
            color: #721c24;
            border-left: 4px solid var(--danger);
        }

        .alert-icon {
            margin-right: 10px;
            font-size: 20px;
        }

        .table-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            animation: fadeIn 1s ease-out;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        tr {
            transition: var(--transition);
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background: rgba(248, 150, 30, 0.2);
            color: var(--warning);
        }

        .status-resolved {
            background: rgba(76, 201, 240, 0.2);
            color: var(--success);
        }

        .user-type {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .user-student {
            background: rgba(67, 97, 238, 0.2);
            color: var(--primary);
        }

        .user-teacher {
            background: rgba(114, 9, 183, 0.2);
            color: var(--secondary);
        }

        .action-form {
            display: flex;
            gap: 10px;
        }

        .status-select {
            padding: 5px 10px;
            border-radius: var(--border-radius);
            border: 1px solid #ddd;
            font-size: 14px;
        }

        .update-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            font-size: 12px;
        }

        .update-btn:hover {
            background: var(--primary-dark);
        }

        .complaint-text {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .complaint-text.expanded {
            white-space: normal;
            max-width: none;
        }

        .toggle-text {
            color: var(--primary);
            cursor: pointer;
            font-size: 12px;
            margin-top: 5px;
            display: inline-block;
        }

        .user-id {
            font-family: monospace;
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .stats-cards {
                grid-template-columns: 1fr;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
            
            th, td {
                padding: 10px;
            }
            
            .action-form {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Complaints Management Dashboard</h1>
            <div class="admin-info">
                <div class="admin-avatar">A</div>
                <span>Admin Panel</span>
                <a href="admin_logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <?php if (isset($message)): ?>
            <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
                <span class="alert-icon"><?php echo $message_type === 'success' ? '✓' : '⚠'; ?></span>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-cards">
            <?php
            // Reset pointer to count rows
            $result->data_seek(0);
            $total = $result->num_rows;
            
            $result->data_seek(0);
            $pending = 0;
            $resolved = 0;
            
            while($row = $result->fetch_assoc()) {
                if ($row['status'] == 'pending') $pending++;
                if ($row['status'] == 'resolved') $resolved++;
            }
            
            // Reset pointer again for main display
            $result->data_seek(0);
            ?>
            
            <div class="stat-card">
                <h3>Total Complaints</h3>
                <div class="count"><?php echo $total; ?></div>
            </div>
            <div class="stat-card">
                <h3>Pending</h3>
                <div class="count pending"><?php echo $pending; ?></div>
            </div>
            <div class="stat-card">
                <h3>Resolved</h3>
                <div class="count resolved"><?php echo $resolved; ?></div>
            </div>
        </div>

        <!-- Complaints Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User ID</th>
                        <th>Type</th>
                        <th>Complaint</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()) { 
                        $status_class = 'status-' . $row['status'];
                        $user_type_class = 'user-' . $row['user_type'];
                    ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td>
                            <span class="user-id"><?= $row['user_id'] ?></span>
                            <br>
                            <small>(<?= ucfirst($row['user_type']) ?> ID)</small>
                        </td>
                        <td><span class="user-type <?= $user_type_class ?>"><?= ucfirst($row['user_type']) ?></span></td>
                        <td>
                            <div class="complaint-text" id="complaint-<?= $row['id'] ?>">
                                <?= htmlspecialchars($row['complaint']) ?>
                            </div>
                            <?php if (strlen($row['complaint']) > 100): ?>
                                <span class="toggle-text" onclick="toggleText(<?= $row['id'] ?>)">Show more</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="status-badge <?= $status_class ?>"><?= ucfirst($row['status']) ?></span></td>
                        <td><?= date('M j, Y g:i A', strtotime($row['created_at'])) ?></td>
                        <td>
                            <form method="POST" class="action-form">
                                <input type="hidden" name="complaint_id" value="<?= $row['id'] ?>">
                                <select name="status" class="status-select">
                                    <option value="pending" <?= $row['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="resolved" <?= $row['status'] == 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                </select>
                                <button type="submit" name="update_status" class="update-btn">Update</button>
                            </form>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Toggle complaint text expansion
        function toggleText(id) {
            const textElement = document.getElementById(`complaint-${id}`);
            const toggleButton = textElement.nextElementSibling;
            
            if (textElement.classList.contains('expanded')) {
                textElement.classList.remove('expanded');
                toggleButton.textContent = 'Show more';
            } else {
                textElement.classList.add('expanded');
                toggleButton.textContent = 'Show less';
            }
        }
        
        // Auto-hide success message after 5 seconds
        const successAlert = document.querySelector('.alert-success');
        if (successAlert) {
            setTimeout(() => {
                successAlert.style.opacity = '0';
                successAlert.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    successAlert.style.display = 'none';
                }, 500);
            }, 5000);
        }
        
        // Add animation to table rows on load
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                row.style.animationDelay = `${index * 0.1}s`;
                row.style.animation = 'slideUp 0.5s ease-out forwards';
                row.style.opacity = '0';
            });
        });
    </script>
</body>
</html>