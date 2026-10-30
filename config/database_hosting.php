<?php
$host = 'sql107.infinityfree.com';
$dbname = 'if0_41746355_chun';
$username = 'if0_41746355';
$password = 'raUvjxxKGu';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die('Kết nối thất bại: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
?>
