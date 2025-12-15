 
<?php
// Initialize the session
session_start();
$_SESSION = array();
 
// Destroy the session.
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Error Page</title>
	    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.css">
    <style type="text/css">
        body{ font: 14px sans-serif; text-align: center; }
    </style>
</head>
<body>
	    <p>
        <a href="addIssue.php" class="btn btn-warning">Back to Main Page</a>
        
    </p>
</body>
</html>