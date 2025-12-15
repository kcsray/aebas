<?php
session_start();
// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    $_SESSION["pfile"] = htmlspecialchars($_SERVER["PHP_SELF"]);
    header("location: login.php");
    exit;
}

// Database connection using PDO
/*
$host = "fdb30.awardspace.net";
$username = '3716551_kiran'; 
$password = 'tulu123@AXN';
$dbname = '3716551_kiran';
$port = '3306';
*/
require_once "config.php";
try {
    $conn = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Initialize variables
    $empData = null;
    $officeLocations = array();
    $selectedEmp = isset($_POST['emp_name']) ? $_POST['emp_name'] : '';

    // Fetch all office locations for dropdown
    $sql = "SELECT Location_CD, Office_Location FROM office_loc ORDER BY Office_Location";
    $stmt = $conn->query($sql);
    $officeLocations = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // If form is submitted for update
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
        $att_id = $_POST['att_id'];
        $emp_name = $_POST['emp_name_edit'];
        $mobile = $_POST['mobile'];
        $designation = $_POST['designation'];
        $loc_cd = $_POST['office_location'];
        $loc_name = $officeLocations[$loc_cd];
        $status = isset($_POST['status']) ? 1 : 0;
        
        $updateSql = "UPDATE emp SET 
                     Emp_Name = ?,
                     Mobile = ?,
                     Designation = ?,
                     Loc_cd = ?,
                     Loc_name = ?,
                     status = ?
                     WHERE Att_ID = ?";
        
        $stmt = $conn->prepare($updateSql);
        if ($stmt->execute([$emp_name, $mobile, $designation, $loc_cd, $loc_name, $status, $att_id])) {
            $successMsg = "Employee record updated successfully!";
        } else {
            $errorMsg = "Error updating record";
        }
    }

    // Fetch employee data when selected
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['emp_name'])) {
        $empName = $_POST['emp_name'];
        $sql = "SELECT * FROM emp WHERE Emp_Name = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$empName]);
        $empData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$empData) {
            $errorMsg = "No employee found with that name";
        }
    }

} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee Data | AEBAS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --light-gray: #f8f9fa;
            --dark-gray: #343a40;
        }
        
        body {
            background-color: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 1.25rem;
        }
        
        .form-control, .form-select {
            border-radius: 5px;
            padding: 10px 15px;
            border: 1px solid #ced4da;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 10px 20px;
            border-radius: 5px;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-outline-secondary {
            padding: 10px 20px;
            border-radius: 5px;
        }
        
        .alert {
            border-radius: 5px;
        }
        
        .form-label {
            font-weight: 500;
            color: var(--dark-gray);
        }
        
        .status-toggle {
            display: flex;
            align-items: center;
        }
        
        .status-toggle .form-check-input {
            width: 50px;
            height: 25px;
            margin-right: 10px;
        }
        
        .employee-selector {
            margin-bottom: 30px;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .search-box {
            margin-bottom: 10px;
        }
        
        #employeeSearch {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="bi bi-person-lines-fill me-2"></i>Edit Employee Data</h4>
                        <a href="index.php" class="btn btn-light btn-sm">
                            <i class="bi bi-house-door me-1"></i>Home
                        </a>
                    </div>
                    
                    <div class="card-body">
                        <?php if (isset($successMsg)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i><?php echo $successMsg; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($errorMsg)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $errorMsg; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <div class="employee-selector">
                            <form method="post">
                                <div class="mb-3">
                                    <label for="emp_name" class="form-label">Select Employee</label>
                                    <input type="text" id="employeeSearch" class="form-control" placeholder="Type at least 3 letters to search..." autocomplete="off">
                                    <select class="form-select" name="emp_name" id="emp_name" required onchange="this.form.submit()">
                                        <option value="">-- Select an employee --</option>
                                        <?php
                                        $sql = "SELECT Emp_Name FROM emp ORDER BY Emp_Name";
                                        $stmt = $conn->query($sql);
                                        $employees = $stmt->fetchAll(PDO::FETCH_COLUMN);
                                        foreach ($employees as $employee) {
                                            $selected = ($employee == $selectedEmp) ? 'selected' : '';
                                            echo "<option value='".htmlspecialchars($employee)."' $selected data-search='".strtolower($employee)."'>".htmlspecialchars($employee)."</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </form>
                        </div>
                        
                        <?php if ($empData): ?>
                        <hr class="my-4">
                        <form method="post">
                            <input type="hidden" name="att_id" value="<?php echo htmlspecialchars($empData['Att_ID']); ?>">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="emp_name_edit" class="form-label">Employee Name</label>
                                        <input type="text" class="form-control" name="emp_name_edit" id="emp_name_edit" value="<?php echo htmlspecialchars($empData['Emp_Name']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="mobile" class="form-label">Mobile Number</label>
                                        <input type="text" class="form-control" name="mobile" id="mobile" value="<?php echo htmlspecialchars($empData['Mobile']); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="designation" class="form-label">Designation</label>
                                        <input type="text" class="form-control" name="designation" id="designation" value="<?php echo htmlspecialchars($empData['Designation']); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="office_location" class="form-label">Office Location</label>
                                        <select class="form-select" name="office_location" id="office_location" required>
                                            <?php foreach ($officeLocations as $code => $location): ?>
                                                <option value="<?php echo htmlspecialchars($code); ?>" <?php echo ($code == $empData['Loc_cd']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($location); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <div class="mb-3 status-toggle">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="status" id="status" value="1" <?php echo ($empData['status'] == 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="status">Employee Status</label>
                                        </div>
                                        <span class="ms-2 text-muted">(Toggle for Active/ Blocked or Inactive)</span>
                                    </div>
                                </div>
                                
                                <div class="col-12 mt-4 action-buttons">
                                    <button type="submit" name="update" class="btn btn-primary">
                                        <i class="bi bi-save-fill me-2"></i>Update Employee
                                    </button>
                                    <a href="index.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left me-2"></i>Return to Home
                                    </a>
                                </div>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('employeeSearch');
            const empSelect = document.getElementById('emp_name');
            const options = empSelect.options;
            
            // Store all options except the first one
            const allOptions = Array.from(options).slice(1);
            
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                
                // Clear all options except the first one
                while (empSelect.options.length > 1) {
                    empSelect.remove(1);
                }
                
                // If search term has at least 3 characters, filter options
                if (searchTerm.length >= 3) {
                    const filteredOptions = allOptions.filter(option => 
                        option.getAttribute('data-search').includes(searchTerm)
                    );
                    
                    // Add filtered options back to select
                    filteredOptions.forEach(option => {
                        empSelect.add(option);
                    });
                } else {
                    // Add all options back if search term is too short
                    allOptions.forEach(option => {
                        empSelect.add(option);
                    });
                }
            });
        });
    </script>
</body>
</html>

<?php
// Close connection (PDO doesn't need explicit close but we can null it)
$conn = null;
?>