<?php
require_once 'config.php';
requireAdmin();

$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$id]);
$event = $stmt->fetch();
if (!$event) { header('Location: admin.php'); exit; }

$filterDept  = $_GET['dept'] ?? '';
$filterClass = $_GET['class'] ?? '';

$sql = "SELECT * FROM registrations WHERE event_id=?";
$params = [$id];
if ($filterDept)  { $sql .= " AND department=?"; $params[] = $filterDept; }
if ($filterClass) { $sql .= " AND classroom=?";  $params[] = $filterClass; }
$sql .= " ORDER BY department, classroom, lastname";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$filename = 'รายชื่อ_' . $event['title'];
if ($filterDept)  $filename .= '_' . $filterDept;
if ($filterClass) $filename .= '_' . $filterClass;
$filename .= '_' . date('Ymd') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
header('Pragma: no-cache');

// BOM สำหรับ Excel อ่านภาษาไทยได้
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');
fputcsv($out, ['ลำดับ','ชื่อ','นามสกุล','รหัสนักศึกษา','ห้องเรียน','แผนก/สาขา','เบอร์โทร','วันที่ลงทะเบียน']);

$i = 1;
foreach ($rows as $r) {
    fputcsv($out, [
        $i++,
        $r['firstname'],
        $r['lastname'],
        $r['student_id'],
        $r['classroom'],
        $r['department'],
        $r['phone'] ?: '',
        date('d/m/Y H:i', strtotime($r['registered_at'])),
    ]);
}
fclose($out);
exit;
