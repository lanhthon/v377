<?php
// File: db_config.php

function get_db_connection() {
    $host = 'localhost';
    // --- SỬA LẠI CÁC DÒNG DƯỚI ĐÂY ---
    $db   = 'eedsyydkhosting_v279'; // ✅ Đây là tên DB đúng
    $user = 'eedsyydkhosting_3igreen';        // ✅ Đây là tên user đúng
    $pass = '3igreen@Pass11082025';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        // ... (phần còn lại của mã)
    }
}
?>