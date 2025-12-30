<?php
// Database connection
require_once 'config.php';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get all employees for dropdown
$employees = $conn->query("SELECT Att_ID, emp_name FROM emp ORDER BY emp_name")->fetchAll(PDO::FETCH_ASSOC);

// Get selected employee's issued devices
$issuedDevices = [];
if (isset($_GET['emp_id'])) {
    $emp_id = $_GET['emp_id'];
    $stmt = $conn->prepare("
        SELECT 
            i.device_slno, 
            i.Loc_ID as issued_location_id,
            ol_issued.Office_Location as issued_location_name,
            e.Loc_cd as employee_location_id,
            ol_emp.Office_Location as employee_location_name,
             DATE_FORMAT(i.issu_date, '%d-%m-%Y') as issu_date, 
            e.emp_name 
        FROM issued i
        JOIN emp e ON i.emp_cd = e.Att_ID
        LEFT JOIN office_loc ol_issued ON i.Loc_ID = ol_issued.Location_CD
        LEFT JOIN office_loc ol_emp ON e.Loc_cd = ol_emp.Location_CD
        WHERE i.emp_cd = ?
        ORDER BY i.issu_date DESC
    ");
    $stmt->execute([$emp_id]);
    $issuedDevices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get employee name and location for the report header
    $stmt = $conn->prepare("
        SELECT e.emp_name, e.Loc_cd, ol.Office_Location 
        FROM emp e
        LEFT JOIN office_loc ol ON e.Loc_cd = ol.Location_CD
        WHERE e.Att_ID = ?
    ");
    $stmt->execute([$emp_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Device Issuance Report</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .report-container { 
            max-width: 1400px; 
            margin: 20px auto; 
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .report-header { 
            text-align: center; 
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }
        .report-title { 
            font-size: 28px; 
            font-weight: bold; 
            margin-bottom: 10px;
            color: #2c3e50;
        }
        .employee-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .employee-list table {
            margin-bottom: 0;
        }
        .employee-list tr:hover {
            background-color: #f5f5f5;
            cursor: pointer;
        }
        .employee-list tr.selected {
            background-color: #d4edff !important; /* Light blue for selected row */
            font-weight: bold;
            border-left: 3px solid #0d6efd;
        }
        .employee-list tr.active-selection {
            background-color: #b8daff !important; /* Darker blue when actively clicked */
        }
        .no-data { 
            text-align: center; 
            padding: 40px; 
            font-style: italic; 
            color: #666;
            border: 1px dashed #ddd;
            border-radius: 4px;
        }
        .summary {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        .search-box {
            margin-bottom: 10px;
        }
        .employee-row {
            display: table-row;
        }
        .employee-row.hidden {
            display: none;
        }
        .location-info {
            background-color: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .location-badge {
            font-size: 0.85em;
            padding: 4px 8px;
        }
        @media print {
            .no-print { display: none; }
            .report-title { color: #000; }
            .report-container {
                box-shadow: none;
                padding: 0;
            }
            .table {
                font-size: 12px;
            }
        }
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .status-active {
            background-color: #28a745;
        }
        .status-inactive {
            background-color: #dc3545;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-3 no-print">
        <div class="row">
            <div class="col">
                <a href="index.php" class="btn btn-outline-primary">
                    <i class="bi bi-house-door"></i> Home
                </a>
            </div>
        </div>
    </div>

    <div class="report-container">
        <div class="report-header">
            <div class="report-title">Device Issue Report</div>
            <p class="text-muted">Comprehensive device issuance tracking with location details</p>
        </div>

        <form method="get" action="" class="mb-4">
            <div class="mb-3">
                <label for="emp_id" class="form-label fw-bold">Select Employee:</label>
                <div class="search-box">
                    <input type="text" id="employeeSearch" class="form-control" placeholder="Type at least 3 letters to search..." autocomplete="off">
                </div>
                <div class="employee-list">
                    <table class="table table-hover">
                        <tbody id="employeeTableBody">
                            <?php foreach ($employees as $emp): ?>
                                <tr class="employee-row <?= (isset($_GET['emp_id']) && $_GET['emp_id'] == $emp['Att_ID']) ? 'selected' : '' ?>"
                                    data-name="<?= htmlspecialchars(strtolower($emp['emp_name'])) ?>"
                                    onclick="selectEmployee(this, '<?= htmlspecialchars($emp['Att_ID']) ?>')">
                                    <td>
                                        <input type="radio" name="emp_id" id="emp_id_<?= htmlspecialchars($emp['Att_ID']) ?>" 
                                            value="<?= htmlspecialchars($emp['Att_ID']) ?>" 
                                            <?= (isset($_GET['emp_id']) && $_GET['emp_id'] == $emp['Att_ID']) ? 'checked' : '' ?>
                                            style="display: none;">
                                        <?= htmlspecialchars($emp['emp_name']) ?> (<?= htmlspecialchars($emp['Att_ID']) ?>)
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-file-earmark-text"></i> Generate Report
            </button>
        </form>

        <?php if (isset($_GET['emp_id'])): ?>
            <div class="report-header">
                <div class="employee-info mb-4">
                    <h4 class="mb-3">Employee Details</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Personal Information</h5>
                                    <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($employee['emp_name']) ?></p>
                                    <p class="mb-1"><strong>Employee ID:</strong> <span class="badge bg-primary"><?= htmlspecialchars($emp_id) ?></span></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Location Information</h5>
                                    <?php if ($employee['Office_Location']): ?>
                                        <p class="mb-1"><strong>Assigned Location:</strong> <?= htmlspecialchars($employee['Office_Location']) ?></p>
                                        <p class="mb-0"><small class="text-muted">Location Code: <?= htmlspecialchars($employee['Loc_cd']) ?></small></p>
                                    <?php else: ?>
                                        <p class="mb-0 text-warning"><i class="bi bi-exclamation-triangle"></i> No location assigned</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p class="text-muted"><strong>Report Generated:</strong> <?= date('F j, Y, g:i a') ?></p>
                </div>
            </div>

            <?php if (!empty($issuedDevices)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Device Serial No</th>
                                <th>Issue Date</th>
                                <th>Issued From Location</th>
                                <th>Employee's Assigned Location</th>
                                <th>Location Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($issuedDevices as $index => $device): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($device['device_slno']) ?></strong>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars(date('M d, Y', strtotime($device['issu_date']))) ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($device['issu_date']) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($device['issued_location_name']): ?>
                                            <?= htmlspecialchars($device['issued_location_name']) ?>
                                            <br><small class="text-muted">Code: <?= htmlspecialchars($device['issued_location_id']) ?></small>
                                        <?php else: ?>
                                            <span class="text-warning">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($device['employee_location_name']): ?>
                                            <?= htmlspecialchars($device['employee_location_name']) ?>
                                            <br><small class="text-muted">Code: <?= htmlspecialchars($device['employee_location_id']) ?></small>
                                        <?php else: ?>
                                            <span class="text-warning">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $locationsMatch = ($device['issued_location_id'] == $device['employee_location_id']);
                                        if ($device['issued_location_name'] && $device['employee_location_name']):
                                        ?>
                                            <?php if ($locationsMatch): ?>
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check-circle"></i> Match
                                                </span>
                                                <br><small>Same location</small>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">
                                                    <i class="bi bi-exclamation-triangle"></i> Different
                                                </span>
                                                <br><small>Location mismatch</small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Incomplete Data</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="summary">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Summary</h5>
                            <p class="mb-1"><strong>Total Devices Issued:</strong> <?= count($issuedDevices) ?></p>
                            <?php 
                            // Calculate location matches
                            $matches = 0;
                            foreach ($issuedDevices as $device) {
                                if ($device['issued_location_id'] == $device['employee_location_id'] && 
                                    $device['issued_location_name'] && $device['employee_location_name']) {
                                    $matches++;
                                }
                            }
                            ?>
                            <p class="mb-0">
                                <strong>Location Matches:</strong> 
                                <?= $matches ?> of <?= count($issuedDevices) ?> 
                                (<?= round(($matches/count($issuedDevices))*100, 1) ?>%)
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h5>Legend</h5>
                            <p class="mb-1">
                                <span class="badge bg-success">Match</span> = Device issued from employee's assigned location
                            </p>
                            <p class="mb-0">
                                <span class="badge bg-warning text-dark">Different</span> = Device issued from different location
                            </p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="bi bi-inbox" style="font-size: 48px; color: #ccc; display: block; margin-bottom: 15px;"></i>
                    <h4>No Devices Issued</h4>
                    <p>This employee does not have any devices currently issued to them.</p>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between mt-4 no-print">
                <button class="btn btn-outline-secondary" onclick="window.history.back()">
                    <i class="bi bi-arrow-left"></i> Back
                </button>
                <div>
                    <button class="btn btn-success" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print Report
                    </button>
                    <button class="btn btn-info ms-2" onclick="exportToCSV()">
                        <i class="bi bi-download"></i> Export CSV
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to handle employee selection
        function selectEmployee(row, empId) {
            // Remove all selection classes first
            document.querySelectorAll('.employee-list tr').forEach(function(r) {
                r.classList.remove('selected', 'active-selection');
            });
            
            // Add active-selection class for click effect
            row.classList.add('active-selection');
            
            // Set the radio button as checked
            document.getElementById('emp_id_' + empId).checked = true;
            
            // After a short delay, switch to the selected class
            setTimeout(function() {
                row.classList.remove('active-selection');
                row.classList.add('selected');
            }, 200);
        }

        // Highlight initially selected row on page load
        document.addEventListener('DOMContentLoaded', function() {
            const selectedRadio = document.querySelector('input[name="emp_id"]:checked');
            if (selectedRadio) {
                const selectedRow = selectedRadio.closest('tr');
                if (selectedRow) {
                    selectedRow.classList.add('selected');
                }
            }

            // Search functionality
            const searchInput = document.getElementById('employeeSearch');
            const employeeRows = document.querySelectorAll('.employee-row');
            
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                
                if (searchTerm.length >= 3) {
                    employeeRows.forEach(row => {
                        const employeeName = row.getAttribute('data-name');
                        if (employeeName.includes(searchTerm)) {
                            row.classList.remove('hidden');
                        } else {
                            row.classList.add('hidden');
                        }
                    });
                } else {
                    // Show all rows if search term is less than 3 characters
                    employeeRows.forEach(row => row.classList.remove('hidden'));
                }
            });
        });

        // Function to export report to CSV
        function exportToCSV() {
            <?php if (isset($_GET['emp_id']) && !empty($issuedDevices)): ?>
                let csv = [];
                // Add headers
                csv.push(['Device Serial No', 'Issue Date', 'Issued Location', 'Issued Location Code', 'Employee Location', 'Employee Location Code', 'Location Status']);
                
                // Add data rows
                <?php foreach ($issuedDevices as $device): ?>
                    let status = '<?= ($device['issued_location_id'] == $device['employee_location_id']) ? "Match" : "Different" ?>';
                    csv.push([
                        '<?= addslashes($device['device_slno']) ?>',
                        '<?= addslashes($device['issu_date']) ?>',
                        '<?= addslashes($device['issued_location_name'] ?? 'N/A') ?>',
                        '<?= addslashes($device['issued_location_id'] ?? 'N/A') ?>',
                        '<?= addslashes($device['employee_location_name'] ?? 'N/A') ?>',
                        '<?= addslashes($device['employee_location_id'] ?? 'N/A') ?>',
                        status
                    ]);
                <?php endforeach; ?>
                
                // Convert to CSV string
                let csvContent = "data:text/csv;charset=utf-8,";
                csv.forEach(row => {
                    csvContent += row.map(cell => `"${cell}"`).join(",") + "\r\n";
                });
                
                // Create download link
                let encodedUri = encodeURI(csvContent);
                let link = document.createElement("a");
                link.setAttribute("href", encodedUri);
                link.setAttribute("download", "device_report_<?= isset($_GET['emp_id']) ? $_GET['emp_id'] : 'employee' ?>_<?= date('Y-m-d') ?>.csv");
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            <?php endif; ?>
        }
    </script>
</body>
</html>