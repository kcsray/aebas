<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "mysql";
$dbname = "aebas";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL query to join the three tables
$sql = "SELECT 
            e.emp_Name AS 'Name of employee',
            i.emp_cd AS 'Employee Code',
            i.device_slno AS 'Device Serial No',
            DATE_FORMAT(i.issu_date, '%d-%m-%Y') AS 'Issue Date',
            o.office_Location AS 'Office Location'
        FROM 
            issued i
        JOIN 
            emp e ON i.emp_cd = e.Att_ID
        JOIN 
            office_loc o ON i.loc_ID = o.Location_CD
        ORDER BY 
            e.emp_Name ASC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Device Issuance Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .report-container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .report-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .report-title {
            color: #007bff;
            margin: 0;
        }
        .report-date {
            color: #666;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .no-data {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        .print-button, .home-button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 20px 5px;
            cursor: pointer;
            border-radius: 4px;
        }
        .home-button {
            background-color: #6c757d;
        }
        .print-button:hover {
            background-color: #0056b3;
        }
        .home-button:hover {
            background-color: #5a6268;
        }
        .button-container {
            text-align: center;
        }
        @media print {
            .print-button, .home-button {
                display: none;
            }
            .report-container {
                box-shadow: none;
                padding: 0;
            }
            body {
                padding: 0;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="report-container">
        <div class="report-header">
            <h1 class="report-title">Employee Device Issuance Report</h1>
            <div class="report-date">Generated on: <?php echo date("F j, Y, g:i a"); ?></div>
        </div>

        <div class="button-container">
            <button class="print-button" onclick="window.print()">Print Report</button>
            <a href="index.php" class="home-button">Return to Home</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Sl No</th>
                    <th>Name of Employee</th>
                    <th>Employee Code</th>
                    <th>Device Serial No</th>
                    <th>Issue Date</th>
                    <th>Office Location</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    $counter = 1;
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $counter++ . "</td>";
                        echo "<td>" . htmlspecialchars($row["Name of employee"]) . "</td>";
                        echo "<td>" . htmlspecialchars($row["Employee Code"]) . "</td>";
                        echo "<td>" . htmlspecialchars($row["Device Serial No"]) . "</td>";
                        echo "<td>" . htmlspecialchars($row["Issue Date"]) . "</td>";
                        echo "<td>" . htmlspecialchars($row["Office Location"]) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo '<tr><td colspan="6" class="no-data">No device issuance records found</td></tr>';
                }
                $conn->close();
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>