<?php
// db_connect.php
// Update credentials if your MySQL is different
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = ''; // default for WAMP is empty
$DB_NAME = 'ocrs_db';

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($mysqli->connect_errno) {
    die('Database connection failed: (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');
$conn = $mysqli;

?>
