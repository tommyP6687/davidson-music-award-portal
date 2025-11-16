<?php
// Quiet notices (optional in production)
// error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
// ini_set('display_errors', '0');

// DB credentials
$db_host = "localhost";
$db_name = "tphamdcr_awardsdb";
$db_user = "tphamdcr_awardsuser";
$db_pass = "Tommyhouston24082006!";

$dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
$pdo = new PDO($dsn, $db_user, $db_pass, $options);