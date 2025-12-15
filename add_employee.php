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
    
    // Fetch office locations for dropdown
    $locations = $conn->query("SELECT Location_CD, Office_Location FROM office_loc ORDER BY Office_Location")->fetchAll(PDO::FETCH_ASSOC);
    
    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $att_id = $_POST['att_id'];
        $emp_name = $_POST['emp_name'];
        $mobile = $_POST['mobile'];
        $designation = $_POST['designation'];
        $loc_cd = $_POST['loc_cd'];
        $loc_name = $_POST['loc_name'];
        
        try {
            // Insert into emp table
            $stmt = $conn->prepare("INSERT INTO emp (Att_ID, Emp_Name, Mobile, Designation, Loc_cd, Loc_name) 
                                   VALUES (:att_id, :emp_name, :mobile, :designation, :loc_cd, :loc_name)");
            
            $stmt->bindParam(':att_id', $att_id);
            $stmt->bindParam(':emp_name', $emp_name);
            $stmt->bindParam(':mobile', $mobile);
            $stmt->bindParam(':designation', $designation);
            $stmt->bindParam(':loc_cd', $loc_cd);
            $stmt->bindParam(':loc_name', $loc_name);
            
            if ($stmt->execute()) {
                $success = "Employee record added successfully!";
            }
        } catch(PDOException $e) {
            // Check for duplicate entry error (SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry)
            if($e->getCode() == '23000') {
                $error = "Error: Employee with ID $att_id already exists!";
            } else {
                $error = "Error adding employee record: " . $e->getMessage();
            }
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
    <title>Add New Employee | AEBAS</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .form-container {
            max-width: 800px;
            margin: 2rem auto;
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-header {
            color: var(--primary-color);
            border-bottom: 2px solid var(--secondary-color);
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        
        .form-label {
            font-weight: 500;
        }
        
        .btn-submit {
            background-color: var(--secondary-color);
            border: none;
            padding: 10px 20px;
        }
        
        .btn-submit:hover {
            background-color: #2980b9;
        }
        
        .required-field::after {
            content: " *";
            color: red;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="form-header">
                <h2><i class="fas fa-user-plus me-2"></i>Add New Employee</h2>
                <p class="text-muted">Fill in the employee details below</p>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="att_id" class="form-label required-field">Employee ID</label>
                        <input type="text" class="form-control" id="att_id" name="att_id" required
                               value="<?php echo isset($_POST['att_id']) ? htmlspecialchars($_POST['att_id']) : ''; ?>">
                        <?php if (isset($error) && strpos($error, 'already exists') !== false): ?>
                            <div class="text-danger small mt-1">This Employee ID is already in use.</div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label for="emp_name" class="form-label required-field">Employee Name</label>
                        <input type="text" class="form-control" id="emp_name" name="emp_name" required
                               value="<?php echo isset($_POST['emp_name']) ? htmlspecialchars($_POST['emp_name']) : ''; ?>">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="mobile" class="form-label required-field">Mobile Number</label>
                        <input type="tel" class="form-control" id="mobile" name="mobile" required
                               value="<?php echo isset($_POST['mobile']) ? htmlspecialchars($_POST['mobile']) : ''; ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="designation" class="form-label required-field">Designation</label>
                        <input type="text" class="form-control" id="designation" name="designation" required
                               value="<?php echo isset($_POST['designation']) ? htmlspecialchars($_POST['designation']) : ''; ?>">
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="office_location" class="form-label required-field">Office Location</label>
                        <select class="form-select" id="office_location" name="office_location" required 
                                onchange="document.getElementById('loc_cd').value=this.options[this.selectedIndex].getAttribute('data-cd');
                                         document.getElementById('loc_name').value=this.options[this.selectedIndex].text;">
                            <option value="">-- Select Office Location --</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?php echo htmlspecialchars($location['Location_CD']); ?>" 
                                        data-cd="<?php echo htmlspecialchars($location['Location_CD']); ?>"
                                        <?php echo (isset($_POST['loc_cd']) && $_POST['loc_cd'] == $location['Location_CD']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($location['Office_Location']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" id="loc_cd" name="loc_cd" value="<?php echo isset($_POST['loc_cd']) ? htmlspecialchars($_POST['loc_cd']) : ''; ?>">
                        <input type="hidden" id="loc_name" name="loc_name" value="<?php echo isset($_POST['loc_name']) ? htmlspecialchars($_POST['loc_name']) : ''; ?>">
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Home
                    </a>
                    <button type="submit" class="btn btn-submit">
                        <i class="fas fa-save me-2"></i>Save Employee
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Validate form before submission
        document.querySelector('form').addEventListener('submit', function(e) {
            let isValid = true;
            
            // Check required fields
            document.querySelectorAll('[required]').forEach(function(field) {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    </script>
</body>
</html>