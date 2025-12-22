<?php
// Initialize the session
session_start();
// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    $_SESSION["pfile"] = htmlspecialchars($_SERVER["PHP_SELF"]);
    header("location: login.php");
    exit;
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $input_active_emp = trim($_POST["active_emp"]);
    $input_dev_id = trim($_POST["dev_id"]);
    $input_off_loc = trim($_POST["off_loc"]);
    $input_issue_dt = trim($_POST["issue_dt"]);

    if( empty($input_active_emp)  || empty($input_dev_id) || empty($input_off_loc ) || empty($input_issue_dt)  ){
        // Destroy the session.
        session_destroy();
        header("location: verror.php");
        exit();
    }else{
        require_once "config.php";
        
        try {
            // Start transaction to ensure both operations succeed or fail together
            $pdo->beginTransaction();
            
            // First, check if the device is already issued
            $checkSql = "SELECT Isissued FROM device WHERE SLNO = :dev_id FOR UPDATE";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->bindParam(":dev_id", $input_dev_id, PDO::PARAM_STR);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                $device = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                // Check if device is already issued
                if ($device['Isissued'] == 1) {
                    // Device is already issued, rollback and show error
                    $pdo->rollBack();
                    echo "<div class='alert alert-danger'>Error: This device is already issued to another employee.</div>";
                } else {
                    // Insert into 'issued' table
                    $insertSql = "INSERT INTO issued (emp_cd, device_slno, Loc_ID, issu_date) 
                                  VALUES (:active_emp, :dev_id, :off_loc, :issue_date)";
                    
                    $insertStmt = $pdo->prepare($insertSql);
                    $insertStmt->bindParam(":active_emp", $input_active_emp, PDO::PARAM_STR);
                    $insertStmt->bindParam(":dev_id", $input_dev_id, PDO::PARAM_STR);
                    $insertStmt->bindParam(":off_loc", $input_off_loc, PDO::PARAM_STR);
                    $insertStmt->bindParam(":issue_date", $input_issue_dt, PDO::PARAM_STR);
                    
                    if ($insertStmt->execute()) {
                        // Update status in 'device' table to 1 (issued)
                        $updateSql = "UPDATE device SET Isissued = 1 WHERE SLNO = :dev_id";
                        $updateStmt = $pdo->prepare($updateSql);
                        $updateStmt->bindParam(":dev_id", $input_dev_id, PDO::PARAM_STR);
                        
                        if ($updateStmt->execute()) {
                            // Commit the transaction
                            $pdo->commit();
                            
                            // Records created successfully. Redirect to landing page
                            $_SESSION["SLNO"] = $input_dev_id;
                            header("location: welcome_Vpass.php");
                            exit();
                        } else {
                            $pdo->rollBack();
                            echo "<div class='alert alert-danger'>Error updating device status.</div>";
                        }
                    } else {
                        $pdo->rollBack();
                        echo "<div class='alert alert-danger'>Error inserting issue record.</div>";
                    }
                }
            } else {
                $pdo->rollBack();
                echo "<div class='alert alert-danger'>Error: Device not found.</div>";
            }
            
            // Close statements
            unset($checkStmt);
            unset($insertStmt);
            if (isset($updateStmt)) {
                unset($updateStmt);
            }
            
        } catch (PDOException $e) {
            // Rollback on any exception
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo "<div class='alert alert-danger'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <style type="text/css">
        .wrapper{
            width: 776px;
            margin: 0 auto;
        }
        .history-table {
            margin-top: 20px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
        .history-table th {
            background-color: #f8f9fa;
            position: sticky;
            top: 0;
        }
        .history-container {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 20px;
        }
        .table-responsive {
            display: block;
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .alert {
            margin-top: 20px;
        }
    </style>
    <title>Issue BAS Device</title>
</head>
<body>
    <div class="wrapper">
        <div class="page-header">
            <h2 class="text-danger text-center">Issue Device</h2>
        </div>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="needs-validation" novalidate>
            <div class="form-group">
                <label for="active_emp">Active Employee:</label>
                <select class="form-control" id="active_emp" name="active_emp" required>
                </select>
            </div>

            <div class="form-group">
                <label for="dev_id">BAS Device:</label>
                <select class="form-control" id="dev_id" name="dev_id" required>
                </select>
            </div>

            <div class="form-group">
                <label for="off_loc">Office Location:</label>
                <select class="form-control" id="off_loc" name="off_loc" required>
                </select>
            </div>

            <div class="form-group">
                <label for="issue_dt">Issue Date:</label>
                <input id="issue_dt" name="issue_dt" type="date" data-date="" data-date-format="DD MMMM YYYY" value="<?php echo date('Y-m-d'); ?>" required>
                <div class="invalid-feedback">Please fill out this field with valid Data</div>
            </div>

            <button type="submit" class="btn btn-primary">Submit</button>
            <a href="addIssue.php" class="btn btn-warning btn-outline-danger pull-right">Cancel</a>
        </form>
        <br>
        <p align="center">
            <a href="logout.php" class="btn btn-danger pull-right">Sign Out</a>
            <a href="index.php" class="btn btn-primary   pull-right">Home</a>
        </p>
        
        <!-- Employee Device History Section -->
        <div class="card history-container">
            <div class="card-header bg-light">
                <h6 class="mb-0">Employee Device History</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0 history-table" id="empHistory">
<thead>
    <tr>
        <th>Employee Code</th>
        <th>Device Serial</th>
        <th>Date</th>
        <th>Office Location</th>
        <th>Action Type</th>
    </tr>
</thead>
                        <tbody>
                            <!-- History data will be loaded here via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
    <script type="text/javascript">
        // Disable form submissions if there are invalid fields
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();

        // Load employee dropdown
        $(document).ready(function(){
            var emp_code='emp_code';
            
            $.ajax({
                url:"getdata_BAS.php",
                type:'POST',
                data:{emp_code:emp_code},
                success:function(data,status){
                    $('#active_emp').html(data);
                }
            });
        });

        // Load device dropdown
        $(document).ready(function(){
            var dev_code='dev_code';
            
            $.ajax({
                url:"getdata_BAS.php",
                type:'POST',
                data:{dev_code:dev_code},
                success:function(data,status){
                    $('#dev_id').html(data);
                }
            });
        });    

        // Load office location dropdown
        $(document).ready(function(){
            var off_code='off_code';
            
            $.ajax({
                url:"getdata_BAS.php",
                type:'POST',
                data:{off_code:off_code},
                success:function(data,status){
                    $('#off_loc').html(data);
                }
            });
        });

        // Load employee device history when employee is selected
        $(document).ready(function(){
            $('#active_emp').change(function(){
                var emp_cd = $(this).val();
                if(emp_cd) {
                    $.ajax({
                        url: "getEmpHistory.php",
                        type: 'POST',
                        data: {emp_cd: emp_cd},
                        success: function(data, status) {
                            $('#empHistory tbody').html(data);
                        }
                    });
                } else {
                    $('#empHistory tbody').empty();
                }
            });
        });

        // Function to update device dropdown to show only unissued devices
        $(document).ready(function(){
            // Initially load all devices
            var dev_code='dev_code';
            
            $.ajax({
                url:"getdata_BAS.php",
                type:'POST',
                data:{dev_code:dev_code},
                success:function(data,status){
                    $('#dev_id').html(data);
                }
            });
            
            // Optionally filter to show only available devices
            // This would require modifying getdata_BAS.php to accept a parameter
            // or creating a new endpoint that returns only unissued devices
        });

        function pulsar(e,obj) {
            tecla = (document.all) ? e.keyCode : e.which;
            if (tecla!="8" && tecla!="0"){
                obj.value += String.fromCharCode(tecla).toUpperCase();
                return false;
            }else{
                return true;
            }
        }

        // Set default date to today
        document.getElementById('issue_dt').valueAsDate = new Date();
    </script>
</body>
</html>