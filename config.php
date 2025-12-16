<?php

/* Database credentials. Assuming you are running MySQL
server with default setting (user 'root' with no password) */
define('DB_SERVER', 'sql100.infinityfree.com:3306');
define('DB_USERNAME', 'if0_40578902');
define('DB_PASSWORD', 'Github123AXN');
define('DB_NAME', 'if0_40578902_nicaebas');

$host = "sql100.infinityfree.com";
$dbname = 'if0_40578902_nicaebas';
$username = 'if0_40578902'; // Replace with your database username
$password = 'Github123AXN'; // Replace with your database password
$port = '3306'; // Separate port specification

 
/* Attempt to connect to MySQL database */
try{
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e){
    die("ERROR: Could not connect. " . $e->getMessage());
}

?>