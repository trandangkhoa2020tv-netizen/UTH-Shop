<?php
require_once __DIR__ . '/database_connection.php';

$conn = connectDatabase([
    'host' => '',
    'port' => '',
    'dbname' => '',
    'username' => '',
    'password' => '',
], __DIR__ . '/database_hosting.local.php');
