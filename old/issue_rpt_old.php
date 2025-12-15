<?php
// Database connection
$host = 'localhost';
$dbname = 'aebas';
$username = 'root';
$password = 'mysql';

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
        SELECT i.device_slno, i.Loc_ID, i.issu_date, e.emp_name 
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
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .report-container { max-width: 1000px; margin: 0 auto; }
        .report-header { text-align: center; margin-bottom: 20px; }
        .report-title { font-size: 24px; font-weight: bold; margin-bottom: 10px; }
        .employee-info { margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .no-data { text-align: center; padding: 20px; font-style: italic; color: #666; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        select { padding: 8px; width: 300px; }
        button { padding: 8px 15px; background-color: #4CAF50; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #45a049; }
        .print-btn { margin-top: 20px; background-color: #2196F3; }
        .print-btn:hover { background-color: #0b7dda; }
        @media print {
            button { display: none; }
            .report-title { color: #000; }
        }
    </style>
</head>
<body>
    <div class="report-container">
        <form method="get" action="">
            <div class="form-group">
                <label for="emp_id">Select Employee:</label>
                <select name="emp_id" id="emp_id" required>
                    <option value="">-- Select Employee --</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= htmlspecialchars($emp['Att_ID']) ?>" 
                            <?= (isset($_GET['emp_id']) && $_GET['emp_id'] == $emp['Att_ID']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($emp['emp_name']) ?> (<?= htmlspecialchars($emp['Att_ID']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit">Generate Report</button>
        </form>

        <?php if (isset($_GET['emp_id'])): ?>
            <div class="report-header">
                <div class="report-title">Device Issuance Report</div>
                <div class="employee-info">
                    <strong>Employee:</strong> <?= htmlspecialchars($employee['emp_name']) ?> (ID: <?= htmlspecialchars($emp_id) ?>)
                </div>
                <div class="report-date">
                    <strong>Report Generated:</strong> <?= date('Y-m-d H:i:s') ?>
                </div>
            </div>

            <?php if (!empty($issuedDevices)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Device Serial No</th>
                            <th>Location ID</th>
                            <th>Issue Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($issuedDevices as $index => $device): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($device['device_slno']) ?></td>
                                <td><?= htmlspecialchars($device['Loc_ID']) ?></td>
                                <td><?= htmlspecialchars($device['issu_date']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="summary">
                    <p><strong>Total Devices Issued:</strong> <?= count($issuedDevices) ?></p>
                </div>
            <?php else: ?>
                <div class="no-data">No devices issued to this employee.</div>
            <?php endif; ?>

            <button class="print-btn" onclick="window.print()">Print Report</button>
        <?php endif; ?>
    </div>
</body>
</html>