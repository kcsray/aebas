<?php
// Database connection
$password = 'mysql';
$host = 'sql100.infinityfree.com';
$dbname = 'if0_40578902_nicaebas';
$username = 'if0_40578902';
$password = 'Github123AXN';

try {
    
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch device statistics
    $totalDevices = $conn->query("SELECT COUNT(*) FROM device")->fetchColumn();
    $availableDevices = $conn->query("SELECT COUNT(*) FROM device WHERE Isissued = 0")->fetchColumn();
    $issuedDevices = $conn->query("SELECT COUNT(*) FROM device WHERE Isissued = 1")->fetchColumn();
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AEBAS | Device Management System</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: var(--dark-color);
        }
        
        .navbar-brand {
            font-weight: 700;
            letter-spacing: 1px;
        }
        
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            border: none;
            margin-bottom: 20px;
            height: 100%;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            font-weight: 600;
        }
        
        .btn-custom {
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-login {
            background-color: #f39c12;
            border-color: #f39c12;
        }
        
        .btn-logout {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        
        .btn-action {
            min-width: 180px;
            margin: 5px;
        }
        
        .action-section {
            background-color: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .welcome-message {
            font-size: 1.1rem;
            color: #7f8c8d;
            margin-bottom: 2rem;
        }
        
        .footer {
            margin-top: 3rem;
            padding: 1.5rem 0;
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-laptop me-2"></i>AEBAS Device Management
            </a>
            <div class="d-flex">
                <a href="login.php" class="btn btn-login btn-custom me-2">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <a href="logout.php" class="btn btn-logout btn-custom">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-container">
        <div class="text-center mb-5">
            <h1 class="display-5 fw-bold text-dark mb-3">
                <i class="fas fa-exchange-alt text-primary me-2"></i>Device Management Portal
            </h1>
            <p class="welcome-message">
                Manage device issuance, returns, and generate comprehensive reports
            </p>
        </div>

        <!-- Action Cards -->
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="action-section">
                    <h3 class="text-center mb-4 text-primary">Quick Actions</h3>
                    <div class="d-flex flex-wrap justify-content-center">
                        <a href="addIssue.php" class="btn btn-success btn-custom btn-action">
                            <i class="fas fa-laptop-medical me-2"></i> Issue Device
                        </a>
                        <a href="return.php" class="btn btn-primary btn-custom btn-action">
                            <i class="fas fa-undo me-2"></i> Return Device
                        </a>
                        <a href="issue_rpt.php" class="btn btn-info btn-custom btn-action">
                            <i class="fas fa-file-alt me-2"></i> Issue Report
                        </a>
                        <a href="aebas_ledger.php" class="btn btn-warning btn-custom btn-action">
                            <i class="fas fa-chart-bar me-2"></i> Ledger Report
                        </a>
                    </div>
                    <div class="d-flex flex-wrap justify-content-center">
                        <a href="add_employee.php" class="btn btn-primary btn-custom btn-action">
                            <i class="fas fa-user-plus me-2"></i> Add Employee
                        </a>
                        <a href="edit_employee.php" class="btn btn-success btn-custom btn-action">
                            <i class="fas fa-user-edit me-2"></i> Edit Employee
                        </a>
                        <a href="isuelistRpt.php" class="btn btn-warning btn-custom btn-action">
                            <i class="fas fa-chart-bar me-2"></i> Emp Issue List
                        </a>
                        <a href="notyetIsuue_rpt.php" class="btn btn-warning btn-custom btn-action">
                            <i class="fas fa-chart-bar me-2"></i> Emp no Device
                        </a>                        
                    </div>
                </div>
            </div>
        </div>

        <!-- Real-time Stats Section -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-laptop me-2"></i> Total Devices
                    </div>
                    <div class="card-body text-center">
                        <a href="total_device_rpt.php" class="btn btn-login btn-custom me-2"> <div class="stat-number"><?php echo $totalDevices; ?></div></a>
                        <p class="text-muted mb-0">All devices in inventory</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-check-circle me-2"></i> Available
                    </div>
                    <div class="card-body text-center">
                         <a href="device_rpt.php" class="btn btn-login btn-custom me-2"><div class="stat-number text-success"><?php echo $availableDevices; ?></div></a>
                        <p class="text-muted mb-0">Available</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-users me-2"></i> Issued
                    </div>
                    <div class="card-body text-center">
                       <a href="total_issued_rpt.php" class="btn btn-login btn-custom me-2"><div class="stat-number text-primary"><?php echo $issuedDevices; ?></div></a>
                        <p class="text-muted mb-0">Currently with employees</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer text-center">
        <div class="container">
            <p class="mb-0">
                &copy; <?php echo date('Y'); ?> AEBAS Device Management System, NIC OSU Bhubaneswar. All rights reserved.
            </p>
            <small>v2.1.0</small>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Auto-refresh every 60 seconds to update stats -->
    <script>
        setTimeout(function(){
            window.location.reload(1);
        }, 60000);
    </script>
</body>
</html>