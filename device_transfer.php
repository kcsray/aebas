<?php
// Include database configuration
session_start();
// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    $_SESSION["pfile"] = htmlspecialchars($_SERVER["PHP_SELF"]);
    header("location: login.php");
    exit;
}
require_once 'config.php';

// Initialize variables
$sourceEmployee = '';
$device = '';
$destinationEmployee = '';
$transferDate = date('Y-m-d');
$message = '';
$error = '';

// Fetch active employees (status = 1)
$employees = [];
try {
    $sql = "SELECT Att_ID, emp_Name FROM emp WHERE status = 1 ORDER BY emp_Name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $employees = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error fetching employees: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sourceEmployee = $_POST['source_employee'] ?? '';
    $device = $_POST['device'] ?? '';
    $destinationEmployee = $_POST['destination_employee'] ?? '';
    $transferDate = $_POST['transfer_date'] ?? date('Y-m-d');
    
    // Validate inputs
    if (empty($sourceEmployee) || empty($device) || empty($destinationEmployee) || empty($transferDate)) {
        $error = "All fields are required!";
    } elseif ($sourceEmployee === $destinationEmployee) {
        $error = "Source and destination employees cannot be the same!";
    } else {
        try {
            // Begin transaction
            $pdo->beginTransaction();
            
            // 1. Get the issued device details including location
            $sql = "SELECT i.emp_cd, i.device_slno, i.loc_ID, i.issu_date, 
                           e.emp_Name as source_name, d.type as device_type
                    FROM issued i
                    JOIN emp e ON i.emp_cd = e.Att_ID
                    JOIN device d ON i.device_slno = d.SLNO
                    WHERE i.emp_cd = ? AND i.device_slno = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$sourceEmployee, $device]);
            $deviceDetails = $stmt->fetch();
            
            if (!$deviceDetails) {
                throw new Exception("Device not found or not issued to selected employee!");
            }
            
            $locationId = $deviceDetails['loc_ID'];
            $oldIssueDate = $deviceDetails['issu_date'];
            
            // 2. Insert record into returned table
            $sql = "INSERT INTO returned (emp_cd, device_slno, loc_ID, issu_date, return_date) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$sourceEmployee, $device, $locationId, $oldIssueDate, $transferDate]);
            
            // 3. Delete record from issued table
            $sql = "DELETE FROM issued WHERE emp_cd = ? AND device_slno = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$sourceEmployee, $device]);
            
            // 4. Insert new record into issued table for destination employee
            $sql = "INSERT INTO issued (emp_cd, device_slno, loc_ID, issu_date) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$destinationEmployee, $device, $locationId, $transferDate]);
            
            // 5. Update device status if needed (assuming dev_status needs update)
            $sql = "UPDATE device SET Isissued = 1 WHERE SLNO = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$device]);
            
            // Commit transaction
            $pdo->commit();
            
            // Get employee names for success message
            $sql = "SELECT emp_Name FROM emp WHERE Att_ID = ?";
            $stmt = $pdo->prepare($sql);
            
            $stmt->execute([$sourceEmployee]);
            $sourceName = $stmt->fetchColumn();
            
            $stmt->execute([$destinationEmployee]);
            $destName = $stmt->fetchColumn();
            
            $message = "Device successfully transferred from {$sourceName} to {$destName} on {$transferDate}";
            
            // Clear form
            $sourceEmployee = '';
            $device = '';
            $destinationEmployee = '';
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $error = "Transfer failed: " . $e->getMessage();
        }
    }
}

// Fetch devices for selected source employee (AJAX)
if (isset($_GET['action']) && $_GET['action'] === 'get_devices' && isset($_GET['emp_id'])) {
    header('Content-Type: application/json');
    
    try {
        $sql = "SELECT i.device_slno, d.type, d.dev_status 
                FROM issued i 
                JOIN device d ON i.device_slno = d.SLNO 
                WHERE i.emp_cd = ? 
                ORDER BY d.type";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_GET['emp_id']]);
        $devices = $stmt->fetchAll();
        
        echo json_encode($devices);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Device Transfer System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .container {
            max-width: 800px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 30px;
            margin-top: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 15px;
        }
        .header h2 {
            color: #007bff;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .alert {
            border-radius: 8px;
        }
        .card {
            margin-bottom: 20px;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }
        .required {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>📱 Device Transfer System</h2>
            <p class="text-muted">Transfer devices between employees efficiently</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                ✅ <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                ❌ <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                Device Transfer Form
            </div>
            <div class="card-body">
                <form method="POST" id="transferForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="source_employee" class="form-label">
                                    Source Employee <span class="required">*</span>
                                </label>
                                <select class="form-select" id="source_employee" name="source_employee" required>
                                    <option value="">Select Employee</option>
                                    <?php foreach ($employees as $emp): ?>
                                        <option value="<?php echo $emp['Att_ID']; ?>" 
                                            <?php echo ($emp['Att_ID'] == $sourceEmployee) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($emp['emp_Name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">Employee returning the device</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="device" class="form-label">
                                    Device to Transfer <span class="required">*</span>
                                </label>
                                <select class="form-select" id="device" name="device" required>
                                    <option value="">Select Source Employee First</option>
                                </select>
                                <small class="form-text text-muted">Device currently issued to source employee</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="destination_employee" class="form-label">
                                    Destination Employee <span class="required">*</span>
                                </label>
                                <select class="form-select" id="destination_employee" name="destination_employee" required>
                                    <option value="">Select Employee</option>
                                    <?php foreach ($employees as $emp): ?>
                                        <option value="<?php echo $emp['Att_ID']; ?>" 
                                            <?php echo ($emp['Att_ID'] == $destinationEmployee) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($emp['emp_Name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">Employee receiving the device</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="transfer_date" class="form-label">
                                    Transfer Date <span class="required">*</span>
                                </label>
                                <input type="date" class="form-control" id="transfer_date" 
                                       name="transfer_date" value="<?php echo htmlspecialchars($transferDate); ?>" 
                                       max="<?php echo date('Y-m-d'); ?>" required>
                                <small class="form-text text-muted">Date of device transfer</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group text-center mt-4">
                        <button type="submit" class="btn btn-primary btn-lg px-5">
                            <i class="bi bi-arrow-left-right"></i> Transfer Device
                        </button>
                        <button type="reset" class="btn btn-outline-secondary btn-lg px-5 ms-2">
                            <i class="bi bi-x-circle"></i> Reset
                        </button>
                      <div class="form-group text-center mt-4">
                        <a href="index.php" class="btn btn-secondary btn-custom btn-action">
                            <i class="fas fa-exchange-alt me-2"></i> Home
                        </a>
                     </div>  
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                Transfer Summary
            </div>
            <div class="card-body">
                <p>When you submit this form:</p>
                <ol>
                    <li>A record will be added to the <strong>returned</strong> table for the source employee</li>
                    <li>The record will be removed from the <strong>issued</strong> table for the source employee</li>
                    <li>A new record will be added to the <strong>issued</strong> table for the destination employee</li>
                    <li>The device status will be updated accordingly</li>
                    <li><strong>Office location remains unchanged</strong> during the transfer</li>
                </ol>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sourceEmployeeSelect = document.getElementById('source_employee');
            const deviceSelect = document.getElementById('device');
            
            sourceEmployeeSelect.addEventListener('change', function() {
                const empId = this.value;
                deviceSelect.innerHTML = '<option value="">Loading devices...</option>';
                
                if (empId) {
                    fetch(`device_transfer.php?action=get_devices&emp_id=${empId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.error) {
                                deviceSelect.innerHTML = '<option value="">Error loading devices</option>';
                                console.error(data.error);
                                return;
                            }
                            
                            if (data.length === 0) {
                                deviceSelect.innerHTML = '<option value="">No devices issued to this employee</option>';
                                return;
                            }
                            
                            deviceSelect.innerHTML = '<option value="">Select Device</option>';
                            data.forEach(device => {
                                const option = document.createElement('option');
                                option.value = device.device_slno;
                                option.textContent = `${device.type} (SN: ${device.device_slno}) - ${device.dev_status}`;
                                deviceSelect.appendChild(option);
                            });
                        })
                        .catch(error => {
                            deviceSelect.innerHTML = '<option value="">Error loading devices</option>';
                            console.error('Error:', error);
                        });
                } else {
                    deviceSelect.innerHTML = '<option value="">Select Source Employee First</option>';
                }
            });
            
            // Set max date to today
            document.getElementById('transfer_date').max = new Date().toISOString().split('T')[0];
        });
    </script>
</body>
</html>