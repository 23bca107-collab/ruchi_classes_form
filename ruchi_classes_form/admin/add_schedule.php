<?php
// add_schedule.php
session_start();
include('../db.php');

// Only admin access
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $class = $_POST['class'];
    $subject = $_POST['subject'];
    $teacher = $_POST['teacher'];
    $day = $_POST['day'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $medium = $_POST['medium'];

    $sql = "INSERT INTO schedule (class, subject, teacher, day, start_time, end_time, medium)
            VALUES ('$class', '$subject', '$teacher', '$day', '$start_time', '$end_time', '$medium')";

    if ($conn->query($sql) === TRUE) {
        $success_message = "Schedule added successfully!";
    } else {
        $error_message = "Error: " . $conn->error;
    }
}

// Fetch existing schedules for editing
$schedules = [];
$sql = "SELECT * FROM schedule ORDER BY day, start_time";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }
}

// Handle schedule deletion
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $delete_sql = "DELETE FROM schedule WHERE id = $delete_id";
    if ($conn->query($delete_sql) === TRUE) {
        $success_message = "Schedule deleted successfully!";
        // Refresh the page to show updated list
        header("Location: add_schedule.php?success=1");
        exit;
    } else {
        $error_message = "Error deleting schedule: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Schedule Management - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
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
            color: var(--dark);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            margin-bottom: 30px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo i {
            font-size: 2.5rem;
            color: var(--primary);
        }
        
        .logo h1 {
            font-size: 1.8rem;
            color: var(--primary);
        }
        
        .admin-info {
            display: flex;
            align-items: center;
            gap: 10px;
            background: white;
            padding: 10px 15px;
            border-radius: 50px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .admin-info i {
            color: var(--primary);
        }
        
        .tabs {
            display: flex;
            margin-bottom: 30px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .tab {
            padding: 15px 30px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            flex: 1;
            font-weight: 600;
        }
        
        .tab.active {
            background: var(--primary);
            color: white;
        }
        
        .tab:hover:not(.active) {
            background: var(--light-gray);
        }
        
        .tab-content {
            display: none;
            animation: fadeIn 0.5s ease;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        
        .card h2 {
            margin-bottom: 20px;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card h2 i {
            font-size: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }
        
        input, select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }
        
        .form-row {
            display: flex;
            gap: 20px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background: #e1156d;
            transform: translateY(-2px);
        }
        
        .btn-edit {
            background: var(--warning);
            color: white;
        }
        
        .btn-edit:hover {
            background: #e0861b;
            transform: translateY(-2px);
        }
        
        .schedule-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .schedule-item {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border-left: 5px solid var(--primary);
        }
        
        .schedule-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .schedule-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .schedule-day {
            font-weight: 700;
            color: var(--primary);
            font-size: 1.1rem;
        }
        
        .schedule-time {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .schedule-class {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .schedule-subject {
            color: var(--gray);
            margin-bottom: 5px;
        }
        
        .schedule-teacher {
            display: flex;
            align-items: center;
            gap: 5px;
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .schedule-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .timetable {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .timetable th, .timetable td {
            padding: 15px;
            text-align: center;
            border: 1px solid var(--light-gray);
        }
        
        .timetable th {
            background: var(--primary);
            color: white;
            font-weight: 600;
        }
        
        .timetable tr:nth-child(even) {
            background: var(--light);
        }
        
        .timetable tr:hover {
            background: rgba(67, 97, 238, 0.05);
        }
        
        .time-slot {
            font-weight: 600;
            color: var(--primary);
        }
        
        .empty-slot {
            color: var(--gray);
            font-style: italic;
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .schedule-list {
                grid-template-columns: 1fr;
            }
            
            .timetable {
                font-size: 0.8rem;
            }
            
            .timetable th, .timetable td {
                padding: 8px 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <i class="fas fa-calendar-alt"></i>
                <h1>Class Schedule Manager</h1>
            </div>
            <div class="admin-info">
                <i class="fas fa-user-shield"></i>
                <span>Admin Panel</span>
            </div>
        </header>
        
        <div class="tabs">
            <div class="tab active" data-tab="add">Add Schedule</div>
            <div class="tab" data-tab="edit">Edit Schedule</div>
            <div class="tab" data-tab="timetable">Permanent Timetable</div>
        </div>
        
        <!-- Add Schedule Tab -->
        <div class="tab-content active" id="add-tab">
            <div class="card">
                <h2><i class="fas fa-plus-circle"></i> Add New Class Schedule</h2>
                <form method="POST" action="" id="schedule-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="class">Class</label>
                            <input type="text" id="class" name="class" required>
                        </div>
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="teacher">Teacher</label>
                            <input type="text" id="teacher" name="teacher">
                        </div>
                        <div class="form-group">
                            <label for="day">Day</label>
                            <select id="day" name="day" required>
                                <option value="">Select Day</option>
                                <option>Monday</option>
                                <option>Tuesday</option>
                                <option>Wednesday</option>
                                <option>Thursday</option>
                                <option>Friday</option>
                                <option>Saturday</option>
                                <option>Sunday</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_time">Start Time</label>
                            <input type="time" id="start_time" name="start_time" required>
                        </div>
                        <div class="form-group">
                            <label for="end_time">End Time</label>
                            <input type="time" id="end_time" name="end_time" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="medium">Medium</label>
                        <select id="medium" name="medium" required>
                            <option value="English">English</option>
                            <option value="Hindi">Hindi</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Schedule
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Edit Schedule Tab -->
        <div class="tab-content" id="edit-tab">
            <div class="card">
                <h2><i class="fas fa-edit"></i> Manage Schedules</h2>
                <?php if (empty($schedules)): ?>
                    <p>No schedules found. Add some schedules first.</p>
                <?php else: ?>
                    <div class="schedule-list">
                        <?php foreach ($schedules as $schedule): ?>
                            <div class="schedule-item">
                                <div class="schedule-header">
                                    <div class="schedule-day"><?php echo $schedule['day']; ?></div>
                                    <div class="schedule-time"><?php echo date('h:i A', strtotime($schedule['start_time'])); ?> - <?php echo date('h:i A', strtotime($schedule['end_time'])); ?></div>
                                </div>
                                <div class="schedule-class"><?php echo $schedule['class']; ?></div>
                                <div class="schedule-subject"><?php echo $schedule['subject']; ?></div>
                                <div class="schedule-teacher">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                    <?php echo $schedule['teacher'] ? $schedule['teacher'] : 'Not assigned'; ?>
                                </div>
                                <div class="schedule-actions">
                                    <button class="btn btn-edit edit-schedule" data-id="<?php echo $schedule['id']; ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <a href="?delete_id=<?php echo $schedule['id']; ?>" class="btn btn-danger delete-schedule">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Permanent Timetable Tab -->
        <div class="tab-content" id="timetable-tab">
            <div class="card">
                <h2><i class="fas fa-table"></i> Permanent Timetable</h2>
                <?php if (empty($schedules)): ?>
                    <p>No schedules found. Add some schedules first.</p>
                <?php else: ?>
                    <table class="timetable">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Monday</th>
                                <th>Tuesday</th>
                                <th>Wednesday</th>
                                <th>Thursday</th>
                                <th>Friday</th>
                                <th>Saturday</th>
                                <th>Sunday</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Generate time slots from 8 AM to 5 PM
                            $time_slots = [];
                            for ($hour = 8; $hour <= 17; $hour++) {
                                $time_slots[] = sprintf("%02d:00", $hour);
                                if ($hour < 17) {
                                    $time_slots[] = sprintf("%02d:30", $hour);
                                }
                            }
                            
                            foreach ($time_slots as $time_slot):
                                $time_display = date('h:i A', strtotime($time_slot));
                            ?>
                            <tr>
                                <td class="time-slot"><?php echo $time_display; ?></td>
                                <?php
                                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                foreach ($days as $day):
                                    $class_info = "";
                                    foreach ($schedules as $schedule) {
                                        if ($schedule['day'] == $day) {
                                            $start_time = date('H:i', strtotime($schedule['start_time']));
                                            $end_time = date('H:i', strtotime($schedule['end_time']));
                                            
                                            if ($time_slot >= $start_time && $time_slot < $end_time) {
                                                $class_info = $schedule['class'] . " - " . $schedule['subject'];
                                                if ($schedule['teacher']) {
                                                    $class_info .= " (" . $schedule['teacher'] . ")";
                                                }
                                                break;
                                            }
                                        }
                                    }
                                ?>
                                <td><?php echo $class_info ? $class_info : '<span class="empty-slot">Free</span>'; ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Tab switching functionality
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', () => {
                // Remove active class from all tabs and contents
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked tab and corresponding content
                tab.classList.add('active');
                document.getElementById(tab.dataset.tab + '-tab').classList.add('active');
            });
        });
        
        // SweetAlert for success/error messages
        <?php if (isset($success_message)): ?>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '<?php echo $success_message; ?>',
                timer: 3000,
                showConfirmButton: false
            });
        <?php elseif (isset($error_message)): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: '<?php echo $error_message; ?>',
                timer: 5000,
                showConfirmButton: true
            });
        <?php endif; ?>
        
        // Delete confirmation
        document.querySelectorAll('.delete-schedule').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const url = this.getAttribute('href');
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url;
                    }
                });
            });
        });
        
        // Edit functionality (placeholder - would need a more complex implementation)
        document.querySelectorAll('.edit-schedule').forEach(button => {
            button.addEventListener('click', function() {
                const scheduleId = this.getAttribute('data-id');
                Swal.fire({
                    title: 'Edit Schedule',
                    text: 'Edit functionality would be implemented here with a form to update schedule details.',
                    icon: 'info',
                    confirmButtonText: 'OK'
                });
            });
        });
        
        // Form validation
        document.getElementById('schedule-form').addEventListener('submit', function(e) {
            const startTime = document.getElementById('start_time').value;
            const endTime = document.getElementById('end_time').value;
            
            if (startTime >= endTime) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Time',
                    text: 'End time must be after start time.',
                    timer: 3000,
                    showConfirmButton: true
                });
            }
        });
    </script>
</body>
</html>