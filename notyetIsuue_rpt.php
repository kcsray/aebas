<?php
// Database connection
/*
$host = 'localhost';
$dbname = 'aebas';
$username = 'root';
$password = 'mysql';
*/
require_once "config.php";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch employees without issued devices
    $stmt = $conn->prepare("
        SELECT Att_ID, Emp_Name, Mobile, Designation, Loc_name 
        FROM emp
        WHERE NOT EXISTS (SELECT * FROM issued WHERE issued.emp_cd = emp.Att_ID) and emp.status=1
        ORDER BY Emp_Name 
    ");
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees Without Devices | AEBAS</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .report-header {
            background-color: var(--primary-color);
            color: white;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-radius: 5px;
        }
        
        .report-title {
            font-weight: 600;
            margin-bottom: 0;
        }
        
        .table-responsive {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .table thead {
            background-color: var(--primary-color);
            color: white;
        }
        
        .table th {
            font-weight: 500;
        }
        
        .btn-home {
            background-color: var(--secondary-color);
            color: white;
            margin-bottom: 1rem;
        }
        
        .empty-message {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
            font-style: italic;
        }
        
        .print-section {
            margin-top: 2rem;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Report Header -->
        <div class="report-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="report-title">
                        <i class="fas fa-user-check me-2"></i>Employees Without Issued Devices
                    </h1>
                    <p class="mb-0">List of employees who haven't been assigned any devices</p>
                </div>
                <div>
                    <a href="index.php" class="btn btn-home">
                        <i class="fas fa-home me-2"></i>Return to Dashboard
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Report Date -->
        <div class="mb-3 text-end">
            <small class="text-muted">Report generated: <?php echo date('Y-m-d H:i:s'); ?></small>
        </div>
        
        <!-- Report Content -->
        <div class="table-responsive">
            <?php if (count($employees) > 0): ?>
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Employee ID</th>
                            <th>Name</th>
                            <th>Mobile</th>
                            <th>Designation</th>
                            <th>Location</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees as $index => $employee): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($employee['Att_ID']); ?></td>
                                <td><?php echo htmlspecialchars($employee['Emp_Name']); ?></td>
                                <td><?php echo htmlspecialchars($employee['Mobile']); ?></td>
                                <td><?php echo htmlspecialchars($employee['Designation']); ?></td>
                                <td><?php echo htmlspecialchars($employee['Loc_name']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Summary -->
                <div class="p-3 bg-light">
                    <strong>Total Employees Without Devices:</strong> <?php echo count($employees); ?>
                </div>
            <?php else: ?>
                <div class="empty-message">
                    <i class="fas fa-info-circle fa-2x mb-2"></i>
                    <h4>All employees have been issued devices</h4>
                    <p>No records found matching the criteria</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Print Button -->
        <div class="print-section">
            <button onclick="window.print()" class="btn btn-outline-primary">
                <i class="fas fa-print me-2"></i>Print Report
            </button>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>