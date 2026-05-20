<?php
require_once 'config.php';
requireAdmin();

$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$id]);
$event = $stmt->fetch();
if (!$event) { header('Location: admin.php'); exit; }

// URL ที่นักศึกษาจะเข้าผ่าน QR
$registerUrl = SITE_URL . '/register.php?event=' . $event['qr_token'];
// QR API ฟรีจาก Google Chart
$qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($registerUrl);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>QR Code — <?= sanitize($event['title']) ?></title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap');
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Sarabun',sans-serif;background:#0f1117;color:#e6edf3;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:32px}
.card{background:#161b28;border:1px solid rgba(255,255,255,.06);border-radius:16px;padding:40px;max-width:500px;width:100%;text-align:center}
.back-link{display:inline-flex;align-items:center;gap:6px;color:#388bfd;text-decoration:none;font-size:14px;margin-bottom:24px}
.back-link:hover{text-decoration:underline}
h1{font-size:20px;font-weight:700;margin-bottom:8px}
.subtitle{color:#7d8590;font-size:14px;margin-bottom:28px}
.qr-wrapper{background:#fff;padding:20px;border-radius:12px;display:inline-block;margin-bottom:24px;box-shadow:0 0 40px rgba(56,139,253,.2)}
.qr-wrapper img{display:block;width:260px;height:260px}
.url-box{background:rgba(13,17,23,.8);border:1px solid rgba(48,54,61,.8);border-radius:8px;padding:12px 16px;font-size:12px;color:#7d8590;word-break:break-all;margin-bottom:20px;text-align:left}
.actions{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 20px;border-radius:8px;font-family:'Sarabun',sans-serif;font-size:14px;font-weight:600;cursor:pointer;border:none;transition:.2s;text-decoration:none}
.btn-primary{background:linear-gradient(135deg,#388bfd,#1f6feb);color:#fff}
.btn-outline{background:none;border:1px solid rgba(56,139,253,.3);color:#388bfd}
.btn:hover{opacity:.85;transform:translateY(-1px)}
.info-row{display:flex;gap:16px;justify-content:center;margin-top:20px;flex-wrap:wrap}
.info-item{background:rgba(56,139,253,.08);border:1px solid rgba(56,139,253,.15);border-radius:8px;padding:10px 16px;font-size:13px}
.info-label{color:#7d8590;font-size:11px}
.info-value{font-weight:600;margin-top:2px}
</style>
</head>
<body>
<div class="card">
    <a href="admin_event.php?id=<?= $id ?>" class="back-link">← กลับไปดูข้อมูล</a>

    <h1>📱 QR Code ลงทะเบียน</h1>
    <p class="subtitle"><?= sanitize($event['title']) ?></p>

    <div class="qr-wrapper">
        <img src="<?= $qrUrl ?>" alt="QR Code" id="qrImg">
    </div>

    <div class="url-box">
        🔗 <?= htmlspecialchars($registerUrl) ?>
    </div>

    <div class="actions">
        <a href="<?= $qrUrl ?>" download="qrcode_event_<?= $id ?>.png" class="btn btn-primary">⬇️ ดาวน์โหลด QR</a>
        <button class="btn btn-outline" onclick="copyUrl()">📋 คัดลอก URL</button>
        <button class="btn btn-outline" onclick="window.print()">🖨️ พิมพ์</button>
    </div>

    <div class="info-row">
        <div class="info-item">
            <div class="info-label">วันที่จัดกิจกรรม</div>
            <div class="info-value"><?= formatThaiDate($event['event_date']) ?></div>
        </div>
        <?php if ($event['location']): ?>
        <div class="info-item">
            <div class="info-label">สถานที่</div>
            <div class="info-value"><?= sanitize($event['location']) ?></div>
        </div>
        <?php endif; ?>
        <div class="info-item">
            <div class="info-label">สถานะ</div>
            <div class="info-value" style="color:<?= $event['status']==='active'?'#3fb950':'#f85149' ?>">
                <?= $event['status']==='active'?'🟢 เปิดรับ':'🔴 ปิด' ?>
            </div>
        </div>
    </div>
</div>

<script>
function copyUrl() {
    navigator.clipboard.writeText('<?= addslashes($registerUrl) ?>').then(() => {
        alert('คัดลอก URL สำเร็จ!');
    });
}
</script>
</body>
</html>
