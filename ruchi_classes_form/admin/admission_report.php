<?php
require '../db.php'; // Make sure your database connection is included

// Fetch English medium counts
$englishData = $conn->query("
    SELECT class, COUNT(*) as total 
    FROM student_english 
    WHERE class IN ('8', '9', '10', '11', '12')
    GROUP BY class
");

// Store English data
$englishCounts = [];
while ($row = $englishData->fetch_assoc()) {
    $englishCounts[$row['class']] = $row['total'];
}

// Fetch Hindi medium counts
$hindiData = $conn->query("
    SELECT class, COUNT(*) as total 
    FROM student_hindi 
    WHERE class IN ('8', '9', '10', '11', '12')
    GROUP BY class
");

// Store Hindi data
$hindiCounts = [];
while ($row = $hindiData->fetch_assoc()) {
    $hindiCounts[$row['class']] = $row['total'];
}

// Calculate totals
$totalEnglish = array_sum($englishCounts);
$totalHindi = array_sum($hindiCounts);
$overallTotal = $totalEnglish + $totalHindi;

// Prepare data for all classes 8-12
$classes = ['8', '9', '10', '11', '12'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission Analytics Dashboard | Ruchi Classes</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4a6cf7;
            --primary-dark: #3b5be3;
            --secondary: #6c757d;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
            --light: #f8f9fa;
            --dark: #212529;
            --sidebar-bg: #1e2a4a;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fb;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
        }
        
        header h1 {
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        header p {
            color: var(--secondary);
        }
        
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--card-shadow);
            text-align: center;
            transition: var(--transition);
            border-top: 4px solid var(--primary);
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        .card h3 {
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--primary);
        }
        
        .card p {
            color: var(--secondary);
            font-size: 1.1rem;
        }
        
        .card i {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        .data-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 900px) {
            .data-section {
                grid-template-columns: 1fr;
            }
        }
        
        .table-container, .chart-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--card-shadow);
        }
        
        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .section-header h2 {
            color: var(--dark);
            font-weight: 600;
            margin-left: 15px;
        }
        
        .section-header i {
            background: rgba(74, 108, 247, 0.1);
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background-color: #f8fafc;
            font-weight: 600;
            color: var(--dark);
        }
        
        tr:hover {
            background-color: #f8fafc;
        }
        
        .total-row {
            font-weight: 600;
            background-color: #f1f5f9;
        }
        
        .chart-controls {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .chart-controls select {
            padding: 10px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
        }
        
        .chart-wrapper {
            position: relative;
            height: 300px;
        }
        
        .medium-toggle {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .medium-toggle button {
            padding: 8px 15px;
            background: #f1f5f9;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            transition: var(--transition);
        }
        
        .medium-toggle button.active {
            background: var(--primary);
            color: white;
        }
        
        .no-data-message {
            text-align: center;
            padding: 40px;
            color: var(--secondary);
            font-style: italic;
        }
        
        footer {
            text-align: center;
            margin-top: 40px;
            padding: 20px;
            color: var(--secondary);
            font-size: 0.9rem;
        }
        
        .error-message {
            background: #fee;
            border: 1px solid #fcc;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            color: #c33;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Admission Analytics Dashboard</h1>
            <p>Tracking enrollment patterns for Classes 8-12</p>
        </header>
        
        <?php if ($conn->error): ?>
        <div class="error-message">
            <strong>Database Error:</strong> <?php echo $conn->error; ?>
        </div>
        <?php endif; ?>
        
        <div class="dashboard-cards">
            <div class="card">
                <i class="fas fa-users"></i>
                <h3><?php echo $overallTotal; ?></h3>
                <p>Total Admissions</p>
            </div>
            
            <div class="card">
                <i class="fas fa-globe"></i>
                <h3><?php echo $totalEnglish; ?></h3>
                <p>English Medium</p>
            </div>
            
            <div class="card">
                <i class="fas fa-language"></i>
                <h3><?php echo $totalHindi; ?></h3>
                <p>Hindi Medium</p>
            </div>
            
            <div class="card">
                <i class="fas fa-chart-line"></i>
                <h3>8-12</h3>
                <p>Classes Covered</p>
            </div>
        </div>
        
        <div class="data-section">
            <div class="table-container">
                <div class="section-header">
                    <i class="fas fa-table"></i>
                    <h2>Current Admissions</h2>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Class</th>
                            <th>English Medium</th>
                            <th>Hindi Medium</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classes as $class): ?>
                        <tr>
                            <td>Class <?php echo $class; ?></td>
                            <td><?php echo $englishCounts[$class] ?? 0; ?></td>
                            <td><?php echo $hindiCounts[$class] ?? 0; ?></td>
                            <td><strong><?php echo ($englishCounts[$class] ?? 0) + ($hindiCounts[$class] ?? 0); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td><strong>Total</strong></td>
                            <td><strong><?php echo $totalEnglish; ?></strong></td>
                            <td><strong><?php echo $totalHindi; ?></strong></td>
                            <td><strong><?php echo $overallTotal; ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="chart-container">
                <div class="section-header">
                    <i class="fas fa-chart-bar"></i>
                    <h2>Current Enrollment</h2>
                </div>
                
                <div class="chart-wrapper">
                    <canvas id="enrollmentChart"></canvas>
                </div>
            </div>
        </div>
        
        <footer>
            <p>© <?php echo date('Y'); ?> Ruchi Classes - Admission Analytics Dashboard</p>
        </footer>
    </div>

    <script>
        // Prepare chart data from PHP
        const classes = <?php echo json_encode($classes); ?>;
        const englishData = <?php echo json_encode($englishCounts); ?>;
        const hindiData = <?php echo json_encode($hindiCounts); ?>;
        
        // Initialize chart
        const ctx = document.getElementById('enrollmentChart').getContext('2d');
        
        // Prepare data for the chart
        const labels = classes.map(cls => 'Class ' + cls);
        const englishValues = classes.map(cls => englishData[cls] || 0);
        const hindiValues = classes.map(cls => hindiData[cls] || 0);
        
        // Create chart
        const enrollmentChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'English Medium',
                        data: englishValues,
                        backgroundColor: 'rgba(74, 108, 247, 0.7)',
                        borderColor: 'rgba(74, 108, 247, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Hindi Medium',
                        data: hindiValues,
                        backgroundColor: 'rgba(255, 193, 7, 0.7)',
                        borderColor: 'rgba(255, 193, 7, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Students'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Class'
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Current Enrollment by Class and Medium'
                    },
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
        
        // Debug information in console
        console.log('English Data:', englishData);
        console.log('Hindi Data:', hindiData);
        console.log('English Values:', englishValues);
        console.log('Hindi Values:', hindiValues);
    </script>
</body>
</html>