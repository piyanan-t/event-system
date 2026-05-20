<?php
session_start();
date_default_timezone_set('Asia/Bangkok');

// =============================================
// ตั้งค่าฐานข้อมูล (แก้ไขตามที่ได้รับจาก FreeSQLDatabase)
// =============================================
define('DB_HOST', 'sql.freesqldatabase.com');   // เปลี่ยนตรงนี้
define('DB_NAME', 'sql123456');               // เปลี่ยนตรงนี้
define('DB_USER', 'sql123456');               // เปลี่ยนตรงนี้
define('DB_PASS', ' bSPZzX1nRZ');             // เปลี่ยนตรงนี้

// =============================================
// ตั้งค่าเว็บไซต์
// =============================================
define('SITE_NAME', 'ระบบลงทะเบียนกิจกรรม');
define('SITE_URL', 'https://your-site.rf.gd'); // เปลี่ยนเป็น URL จริง

// =============================================
// เชื่อมต่อฐานข้อมูล
// =============================================
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Production: ซ่อน error จริง แสดงแค่ข้อความทั่วไป
    die('<div style="font-family:sans-serif;text-align:center;padding:50px;color:#c0392b;">
        <h2>⚠️ ไม่สามารถเชื่อมต่อฐานข้อมูลได้</h2>
        <p>กรุณาตรวจสอบการตั้งค่าใน config.php</p>
    </div>');
}

// =============================================
// ฟังก์ชันทั่วไป
// =============================================

function sanitize($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header('Location: admin_login.php');
        exit;
    }
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function formatThaiDate($date) {
    if (!$date) return '-';
    $ts = strtotime($date);
    $months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
               'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    $d = date('j', $ts);
    $m = $months[(int)date('n', $ts)];
    $y = date('Y', $ts) + 543;
    return "$d $m $y";
}
?>
