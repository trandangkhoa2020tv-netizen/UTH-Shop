<?php
require_once __DIR__ . '/database_connection.php';

$conn = connectDatabase([
    'host' => 'localhost',
    'port' => '3307',
    'dbname' => '',
    'username' => '',
    'password' => '',
], __DIR__ . '/database.local.php');
