<?php
// Database connection using PDO
/*
$host = "localhost";
$username = "root";
$password = "mysql";
$dbname = "aebas";
*/
require_once "config.php";

try {
    $conn = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Execute the query
   /* 
    $sql = "SELECT emp_cd, emp_name, device_slno, issue_date, return_date, Office_location 
            FROM ledger_view, emp 
            WHERE emp.Att_ID=ledger_view.emp_cd order by emp_name, issue_date";
*/
    $sql =  "SELECT 
        `actions`.`emp_cd` AS `emp_cd`,
        `actions`.`device_slno` AS `device_slno`,
        `actions`.`issue_date` AS `issue_date`,
        `actions`.`return_date` AS `return_date`,
        `actions`.`Office_location` AS `Office_location`
    FROM
        (SELECT 
            `i`.`emp_cd` AS `emp_cd`,
                `i`.`device_slno` AS `device_slno`,
                `i`.`issu_date` AS `issue_date`,
                '---' AS `return_date`,
                `o`.`Office_Location` AS `Office_location`
        FROM
            (`issued` `i`
        JOIN `office_loc` `o` ON ((`i`.`Loc_ID` = `o`.`Location_CD`))) UNION ALL SELECT 
            `r`.`emp_cd` AS `emp_cd`,
                `r`.`device_slno` AS `device_slno`,
                `r`.`issu_date` AS `issue_date`,
                `r`.`return_date` AS `return_date`,
                `o`.`Office_Location` AS `Office_location`
        FROM
            (`returned` `r`
        JOIN `office_loc` `o` ON ((`r`.`Loc_ID` = `o`.`Location_CD`)))) `actions`
    ORDER BY `actions`.`emp_cd` , `actions`.`issue_date`"

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    // Set the resulting array to associative
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Ledger Report</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 20px;
                font-size: 12px;
            }
            .table {
                font-size: 12px;
            }
            .report-header {
                margin-bottom: 20px;
            }
        }
        .report-header {
            border-bottom: 2px solid #333;
            margin-bottom: 30px;
            padding-bottom: 15px;
        }
        .company-logo {
            max-height: 60px;
        }
        .table th {
            background-color: #343a40;
            color: white;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4 mb-5">
        <!-- Report Header -->
        <div class="row report-header">
            <div class="col-md-2">
              <a href="index.php" class="btn btn-primary   pull-right">  <img src="image/aebas_logo.png" alt="AEBAS Logo" class="company-logo"> </a>
            </div>

            <div class="col-md-8 text-center">
                <h2>Employee Asset Ledger Report</h2>
                <p class="mb-0">Generated on: <?php echo date('d-m-Y H:i:s'); ?></p>
            </div>
            <div class="col-md-2 text-end">
                <button onclick="window.print()" class="btn btn-sm btn-primary no-print">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>
        </div>

        <!-- Report Data -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Ledger Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Employee Code</th>
                                        <th>Employee Name</th>
                                        <th>Device Serial No</th>
                                        <th>Issue Date</th>
                                        <th>Return Date</th>
                                        <th>Office Location</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (count($result) > 0) {
                                        $counter = 1;
                                        foreach($result as $row) {
                                            // Format issue date to dd-mm-yyyy
                                            $issue_date = $row['issue_date'] ? date('d-m-Y', strtotime($row['issue_date'])) : 'N/A';
                                            
                                            // Handle return date - keep '---' as is, otherwise format
                                            $return_date = $row['return_date'];
                                            if ($return_date !== '---' && !empty($return_date)) {
                                                $return_date = date('d-m-Y', strtotime($return_date));
                                            }
                                            
                                            echo "<tr>";
                                            echo "<td>" . $counter . "</td>";
                                            echo "<td>" . htmlspecialchars($row['emp_cd']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['emp_name']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['device_slno']) . "</td>";
                                            echo "<td>" . $issue_date . "</td>";
                                            echo "<td>" . htmlspecialchars($return_date) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['Office_location']) . "</td>";
                                            echo "</tr>";
                                            $counter++;
                                        }
                                    } else {
                                        echo "<tr><td colspan='7' class='text-center'>No records found</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer text-muted no-print">
                        Total Records: <?php echo count($result); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Close connection (PDO doesn't need explicit close but we can null it)
$conn = null;
?>