<?php
require_once "config.php";

if(isset($_POST['emp_cd'])) {
    $emp_cd = trim($_POST['emp_cd']);
    
    // Query to get both issued and returned devices with location names
    $sql = "
        (SELECT 
            i.emp_cd, 
            i.device_slno, 
            i.issu_date as action_date, 
            o.Office_location,
            'Issued' as action_type
        FROM issued i 
        JOIN office_loc o ON i.loc_id = o.Location_CD 
        WHERE i.emp_cd = :emp_cd)
        
        UNION ALL
        
        (SELECT 
            r.emp_cd, 
            r.device_slno, 
            r.return_date as action_date, 
            o.Office_location,
            'Returned' as action_type
        FROM returned r 
        JOIN office_loc o ON r.loc_id = o.Location_CD 
        WHERE r.emp_cd = :emp_cd)
        
        ORDER BY action_date DESC
    ";
    
    if($stmt = $pdo->prepare($sql)) {
        $stmt->bindParam(":emp_cd", $emp_cd, PDO::PARAM_STR);
        
        if($stmt->execute()) {
            if($stmt->rowCount() > 0) {
                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['emp_cd']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['device_slno']) . "</td>";
                    echo "<td>" . htmlspecialchars(date('d-m-Y', strtotime($row['action_date']))) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Office_location']) . "</td>";
                    echo "<td class='" . ($row['action_type'] == 'Issued' ? 'text-success' : 'text-danger') . "'>";
                    echo htmlspecialchars($row['action_type']);
                    echo "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='5' class='text-center'>No device history found for this employee</td></tr>";
            }
        }
    }
    unset($stmt);
}
unset($pdo);
?>