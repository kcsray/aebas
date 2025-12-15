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
        SELECT i.emp_cd, i.device_slno, i.Loc_ID, i.issu_date, e.emp_name 
        FROM issued i
        JOIN emp e ON i.emp_cd = e.Att_ID
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
    <title> Return Device </title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .return-btn { color: blue; text-decoration: none; }
        .return-btn:hover { text-decoration: underline; }
        .success { color: green; margin: 10px 0; }
        .error { color: red; margin: 10px 0; }
        .modal { display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
        .modal-content { background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 30%; }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; }
        .close:hover { color: black; text-decoration: none; cursor: pointer; }
    </style>
</head>
<body>
    <h2>Employee Device Management</h2>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="success">Device successfully returned and status updated!</div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="get" action="return.php">
        <label for="emp_cd">Select Employee:</label>
        <select name="emp_cd" id="emp_cd" onchange="this.form.submit()">
            <option value="">-- Select Employee --</option>
            <?php foreach ($employees as $emp): ?>
                <option value="<?php echo htmlspecialchars($emp['emp_cd']); ?>" 
                    <?php echo (isset($_GET['emp_cd']) && $_GET['emp_cd'] == $emp['emp_cd']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($emp['emp_name']); ?> (<?php echo htmlspecialchars($emp['emp_cd']); ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if (!empty($issuedDevices)): ?>
        <h3>Issued Devices for <?php echo htmlspecialchars($issuedDevices[0]['emp_name']); ?> (<?php echo htmlspecialchars($emp_cd); ?>)</h3>
        <table>
            <thead>
                <tr>
                    <th>Employee Code</th>
                    <th>Device Serial No</th>
                    <th>Location ID</th>
                    <th>Issue Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($issuedDevices as $device): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($device['emp_cd']); ?></td>
                        <td><?php echo htmlspecialchars($device['device_slno']); ?></td>
                        <td><?php echo htmlspecialchars($device['Loc_ID']); ?></td>
                        <td><?php echo htmlspecialchars($device['issu_date']); ?></td>
                        <td>
                            <button onclick="openReturnModal('<?php echo $device['device_slno']; ?>', '<?php echo $emp_cd; ?>')" class="return-btn">
                                Return Device
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
   

    <?php elseif (isset($_GET['emp_cd'])): ?>
        <p>No devices currently issued to this employee.</p>
    <?php endif; ?>

    <!-- Return Modal -->
    <div id="returnModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Return Device</h3>
            <form method="post" action="return.php">
                <input type="hidden" id="modal_emp_cd" name="emp_cd">
                <input type="hidden" id="modal_device_slno" name="return_slno">
                <label for="return_date">Return Date:</label>
                <input type="date" id="return_date" name="return_date" required>
                <button type="submit">Confirm Return</button>
            </form>
        </div>
    </div>

    <script>
        // Get the modal
        var modal = document.getElementById("returnModal");

        // Function to open modal and set values
        function openReturnModal(deviceSlno, empCd) {
            document.getElementById("modal_device_slno").value = deviceSlno;
            document.getElementById("modal_emp_cd").value = empCd;
            document.getElementById("return_date").valueAsDate = new Date();
            modal.style.display = "block";
        }

        // Function to close modal
        function closeModal() {
            modal.style.display = "none";
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>

</html>