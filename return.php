<?php
// Initialize the session
session_start();
// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    $_SESSION["pfile"] = htmlspecialchars($_SERVER["PHP_SELF"]);
    header("location: login.php");
    exit;
}
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
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Process return request if any
if (isset($_POST['return_slno'])) {
    $slno = $_POST['return_slno'];
    $emp_cd = $_POST['emp_cd'];
    $return_date = $_POST['return_date'];
    
    try {
        // Modified stored procedure call with return date parameter
        $stmt = $conn->prepare("CALL process_device_return_with_date(?, ?, ?)");
        $stmt->execute([$slno, $emp_cd, $return_date]);
        
        // Redirect back to the same page with success message
        header("Location: return.php?emp_cd=" . urlencode($emp_cd) . "&success=1");
        exit();
    } catch(PDOException $e) {
        $error = "Error processing return: " . $e->getMessage();
    }
}

// Get all employees for dropdown
$employees = $conn->query("SELECT Att_ID as emp_cd, emp_name FROM emp ORDER BY emp_name")->fetchAll(PDO::FETCH_ASSOC);

// Get selected employee's issued devices
$issuedDevices = [];
if (isset($_GET['emp_cd'])) {
    $emp_cd = $_GET['emp_cd'];
    $stmt = $conn->prepare("
        SELECT i.emp_cd, i.device_slno, o.Office_location as location, i.issu_date, e.emp_name 
        FROM issued i
        JOIN emp e ON i.emp_cd = e.Att_ID
        JOIN office_loc o ON i.Loc_ID = o.location_cd
        WHERE i.emp_cd = ?
    ");
    $stmt->execute([$emp_cd]);
    $issuedDevices = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Device</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .employee-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .employee-list tr:hover {
            background-color: #f5f5f5;
            cursor: pointer;
        }
        .employee-list tr.selected {
            background-color: #d4edff !important;
            font-weight: bold;
            border-left: 3px solid #0d6efd;
        }
        .employee-list tr.active-selection {
            background-color: #b8daff !important;
        }
        .table-responsive {
            margin-top: 20px;
        }
        .modal-content {
            padding: 20px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-3">
        <div class="row">
            <div class="col">
                <a href="/" class="btn btn-outline-primary">
                    <i class="bi bi-house-door"></i> Home
                </a>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h2 class="mb-0">Employee Device Management</h2>
            </div>
            <div class="card-body">
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">Device successfully returned and status updated!</div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="get" action="return.php" id="employeeForm">
                    <div class="mb-3">
                        <label for="emp_cd" class="form-label fw-bold">Select Employee:</label>
                        <div class="employee-list">
                            <table class="table table-hover">
                                <tbody>
                                    <?php foreach ($employees as $emp): ?>
                                        <tr onclick="selectEmployee(this, '<?php echo htmlspecialchars($emp['emp_cd']); ?>')"
                                            class="<?php echo (isset($_GET['emp_cd']) && $_GET['emp_cd'] == $emp['emp_cd']) ? 'selected' : ''; ?>">
                                            <td>
                                                <input type="radio" name="emp_cd" id="emp_<?php echo htmlspecialchars($emp['emp_cd']); ?>" 
                                                    value="<?php echo htmlspecialchars($emp['emp_cd']); ?>" 
                                                    <?php echo (isset($_GET['emp_cd']) && $_GET['emp_cd'] == $emp['emp_cd']) ? 'checked' : ''; ?>
                                                    style="display: none;">
                                                <?php echo htmlspecialchars($emp['emp_name']); ?> (<?php echo htmlspecialchars($emp['emp_cd']); ?>)
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Show Devices</button>
                </form>

                <?php if (!empty($issuedDevices)): ?>
                    <div class="mt-4">
                        <h4>Issued Devices for <?php echo htmlspecialchars($issuedDevices[0]['emp_name']); ?> (<?php echo htmlspecialchars($emp_cd); ?>)</h4>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Device Serial No</th>
                                        <th>Office Location</th>
                                        <th>Issue Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($issuedDevices as $index => $device): 
                                        // Convert issue date to dd-mm-yyyy format
                                        $issueDate = date('d-m-Y', strtotime($device['issu_date']));
                                    ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($device['device_slno']); ?></td>
                                            <td><?php echo htmlspecialchars($device['location']); ?></td>
                                            <td><?php echo $issueDate; ?></td>
                                            <td>
                                                <button onclick="openReturnModal('<?php echo $device['device_slno']; ?>', '<?php echo $emp_cd; ?>')" 
                                                    class="btn btn-sm btn-outline-primary">
                                                    Return Device
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php elseif (isset($_GET['emp_cd'])): ?>
                    <div class="alert alert-info mt-3">No devices currently issued to this employee.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Return Modal -->
    <div class="modal fade" id="returnModal" tabindex="-1" aria-labelledby="returnModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="returnModalLabel">Return Device</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="return.php">
                        <input type="hidden" id="modal_emp_cd" name="emp_cd">
                        <input type="hidden" id="modal_device_slno" name="return_slno">
                        <div class="mb-3">
                            <label for="return_date" class="form-label">Return Date:</label>
                            <input type="date" class="form-control" id="return_date" name="return_date" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Confirm Return</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Function to handle employee selection
        function selectEmployee(row, empCd) {
            // Remove all selection classes first
            document.querySelectorAll('.employee-list tr').forEach(function(r) {
                r.classList.remove('selected', 'active-selection');
            });
            
            // Add active-selection class for click effect
            row.classList.add('active-selection');
            
            // Set the radio button as checked
            document.getElementById('emp_' + empCd).checked = true;
            
            // After a short delay, switch to the selected class
            setTimeout(function() {
                row.classList.remove('active-selection');
                row.classList.add('selected');
            }, 200);
            
            // Submit the form automatically
            document.getElementById('employeeForm').submit();
        }

        // Highlight initially selected row on page load
        document.addEventListener('DOMContentLoaded', function() {
            const selectedRadio = document.querySelector('input[name="emp_cd"]:checked');
            if (selectedRadio) {
                const selectedRow = selectedRadio.closest('tr');
                if (selectedRow) {
                    selectedRow.classList.add('selected');
                }
            }
        });

        // Function to open modal and set values
        function openReturnModal(deviceSlno, empCd) {
            document.getElementById("modal_device_slno").value = deviceSlno;
            document.getElementById("modal_emp_cd").value = empCd;
            document.getElementById("return_date").valueAsDate = new Date();
            
            // Show the modal using Bootstrap's modal
            var modal = new bootstrap.Modal(document.getElementById('returnModal'));
            modal.show();
        }
    </script>
</body>
</html>