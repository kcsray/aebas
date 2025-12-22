<?php
// Database connection
/*
$host = 'sql100.infinityfree.com';
$dbname = 'if0_40578902_nicaebas';
$username = 'if0_40578902';
$password = 'Github123AXN';
*/
require_once "config.php";
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
        SELECT i.device_slno, i.Loc_ID, e.loc_name, i.issu_date, e.emp_name 
        FROM issued i
        JOIN emp e ON i.emp_cd = e.Att_ID
        WHERE i.emp_cd = ?
        ORDER BY i.issu_date DESC
    ");
    $stmt->execute([$emp_id]);
    $issuedDevices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get employee name for the report header
    $stmt = $conn->prepare("SELECT emp_name FROM emp WHERE Att_ID = ?");
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
            max-width: 1200px; 
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
        @media print {
            .no-print { display: none; }
            .report-title { color: #000; }
            .report-container {
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-3 no-print">
        <div class="row">
            <div class="col">
                <a href="/" class="btn btn-outline-primary">
                    <i class="bi bi-house-door"></i> Home
                </a>
            </div>
        </div>
    </div>

    <div class="report-container">
        <div class="report-header">
            <div class="report-title">Device Issue Report</div>
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
            <button type="submit" class="btn btn-primary">Generate Report</button>
        </form>

        <?php if (isset($_GET['emp_id'])): ?>
            <div class="report-header">
                <div class="employee-info mb-3">
                    <h4 class="mb-3">Employee Details</h4>
                    <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($employee['emp_name']) ?></p>
                    <p class="mb-1"><strong>Employee ID:</strong> <?= htmlspecialchars($emp_id) ?></p>
                    <p class="mb-1"><strong>Report Date:</strong> <?= date('Y-m-d H:i:s') ?></p>
                </div>
            </div>

            <?php if (!empty($issuedDevices)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Device Serial No</th>
                                <th>Location ID</th>
                                <th>Location Name</th>
                                <th>Issue Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($issuedDevices as $index => $device): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($device['device_slno']) ?></td>
                                    <td><?= htmlspecialchars($device['Loc_ID']) ?></td>
                                    <td><?= htmlspecialchars($device['loc_name']) ?></td>
                                    <td><?= htmlspecialchars($device['issu_date']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="summary">
                    <p class="fw-bold">Total Devices Issued: <?= count($issuedDevices) ?></p>
                </div>
            <?php else: ?>
                <div class="no-data">No devices issued to this employee.</div>
            <?php endif; ?>

            <div class="d-flex justify-content-end mt-4 no-print">
                <button class="btn btn-success" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print Report
                </button>
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
    </script>
</body>
</html>