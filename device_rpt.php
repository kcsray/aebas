<?php
// Database connection
$host = 'localhost';
$dbname = 'aebas';
$username = 'root'; // Replace with your database username
$password = 'mysql'; // Replace with your database password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Query to get available devices
    $sql = "SELECT SLNO as 'Device SLNO' FROM device WHERE Isissued = 0";
    $stmt = $pdo->query($sql);
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Devices Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .report-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-top: 50px;
        }
        .report-header {
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .table-responsive {
            margin-top: 20px;
        }
        .table thead {
            background-color: #343a40;
            color: white;
        }
        .action-buttons {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
        }
        .no-devices {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            font-style: italic;
        }
        
        /* Print-specific styles */
        @media print {
            body {
                background-color: white;
                padding: 0;
                margin: 0;
            }
            .report-container {
                box-shadow: none;
                padding: 0;
                margin: 0;
                border-radius: 0;
            }
            .action-buttons, .home-btn, .print-btn {
                display: none !important;
            }
            .report-header {
                text-align: center;
                border-bottom: 2px solid #000;
                margin-bottom: 15px;
                padding-bottom: 10px;
            }
            .table thead {
                background-color: #000 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .table {
                width: 100%;
                border-collapse: collapse;
            }
            .table th, .table td {
                border: 1px solid #ddd;
                padding: 8px;
            }
            .badge {
                border: 1px solid #000;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="action-buttons">
                    <a href="index.php" class="btn btn-primary home-btn">
                        <i class="fas fa-home"></i> Return Home
                    </a>
                    <button onclick="window.print()" class="btn btn-success print-btn">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>
                
                <div class="report-container">
                    <div class="report-header">
                        <h2><i class="fas fa-laptop"></i> Available Devices Report</h2>
                        <p class="text-muted">List of all currently available devices - Generated on <?php echo date('F j, Y'); ?></p>
                    </div>
                    
                    <?php if (count($devices) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Device Serial Number</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($devices as $index => $device): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($device['Device SLNO']); ?></td>
                                            <td>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check-circle"></i> Available
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="no-devices">
                            <i class="fas fa-box-open fa-3x mb-3"></i>
                            <h4>No available devices found</h4>
                            <p>All devices are currently issued or none are registered in the system.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Print button functionality
        document.addEventListener('DOMContentLoaded', function() {
            // You could add additional print-related JavaScript here if needed
            console.log('Print button ready');
        });
    </script>
</body>
</html>