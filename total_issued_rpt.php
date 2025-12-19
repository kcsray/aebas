<?php
// Database connection


require_once "config.php";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Query to get issued devices
    $sql = "SELECT emp_cd, emp_name, device_slno, issue_date, Office_location 
            FROM ledger_view, emp 
            WHERE emp.Att_ID=ledger_view.emp_cd AND return_date ='---' 
            ORDER BY emp_name, issue_date";
    $stmt = $pdo->query($sql);
    $issuedDevices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count total issued devices
    $totalIssued = count($issuedDevices);
    
} catch(PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Device Issued Report</title>
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
        .no-records {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            font-style: italic;
        }
        .summary-card {
            background-color: #dc3545;
            color: white;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 25px;
        }
        .badge-issued {
            background-color: #dc3545;
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
            .summary-card {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
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
                        <h2><i class="fas fa-laptop"></i> Device Issued Report</h2>
                        <p class="text-muted">List of all currently issued devices with employee details - Generated on <?php echo date('F j, Y'); ?></p>
                    </div>
                    
                    <div class="summary-card">
                        <h4><i class="fas fa-laptop"></i> Total Issued Devices</h4>
                        <h3><?php echo $totalIssued; ?></h3>
                    </div>
                    
                    <?php if ($totalIssued > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Employee Code</th>
                                        <th>Employee Name</th>
                                        <th>Device Serial No</th>
                                        <th>Issue Date</th>
                                        <th>Office Location</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($issuedDevices as $index => $device): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($device['emp_cd']); ?></td>
                                            <td><?php echo htmlspecialchars($device['emp_name']); ?></td>
                                            <td><?php echo htmlspecialchars($device['device_slno']); ?></td>
                                            <td><?php echo date('d-M-Y', strtotime($device['issue_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($device['Office_location']); ?></td>
                                            <td>
                                                <span class="badge badge-issued">
                                                    <i class="fas fa-laptop"></i> Issued
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="no-records">
                            <i class="fas fa-laptop fa-3x mb-3"></i>
                            <h4>No issued devices found</h4>
                            <p>All devices are currently available in inventory.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>