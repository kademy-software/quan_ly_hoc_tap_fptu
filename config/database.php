<?php
// Cau hinh ket noi CSDL - QLHT
// Doi cac gia tri ben duoi cho phu hop voi moi truong cua ban (XAMPP/Laragon...)

define('DB_HOST', 'localhost');
define('DB_PORT', '3307'); // Cong MySQL cua ban - doi lai neu XAMPP dung cong khac
define('DB_NAME', 'qlht');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die('Loi ket noi co so du lieu: ' . htmlspecialchars($e->getMessage()));
        }
    }
    return $pdo;
}
