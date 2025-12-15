<?php
// Initialize the session
session_start();
 
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.css">
    <style type="text/css">
        body{ font: 14px sans-serif; text-align: center; }
    </style>
</head>
<body>
    <div class="page-header">
        <h1>The Device No.: <b><?php echo htmlspecialchars($_SESSION["SLNO"]); ?></b> is being Issued</h1>
    </div>
    <p>
        <a href="addissue.php" class="btn btn-warning">Issue more Device </a>
        <a href="returned.php?vslno=<?php echo $_SESSION["SLNO"];?>" class="btn btn-danger">Return this Device: <?php echo htmlspecialchars($_SESSION["SLNO"]); ?></a>
        
    </p>
</body>
</html>