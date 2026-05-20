<?php
require_once 'config.php';
requireAdmin();

$message = '';
$msgType = '';

// =============================================
// เพิ่มกิจกรรม
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_event'])) {
    $title       = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $location    = sanitize($_POST['location'] ?? '');
    $event_date  = $_POST['event_date'] ?? '';
    $event_time  = $_POST['event_time'] ?? '';

    if ($title && $event_date) {
        $token = generateToken(16);
        $stmt  = $pdo->prepare("INSERT INTO events (title, description, location, event_date, event_time, qr_token) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$title, $description, $location, $event_date, $event_time, $token]);
        $message = 'เพิ่มกิจกรรม "' . $title . '" สำเร็จ!';
        $msgType = 'success';
    } else {
        $message = 'กรุณากรอกชื่อกิจกรรมและวันที่';
        $msgType = 'error';
    }
}

// ลบกิจกรรม
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $message = 'ลบกิจกรรมสำเร็จ';
    $msgType = 'success';
    header('Location: admin.php?msg=deleted');
    exit;
}

// Toggle status
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $stmt = $pdo->prepare("UPDATE events SET status = IF(status='active','inactive','active') WHERE id=?");
    $stmt->execute([$_GET['toggle']]);
    header('Location: admin.php');
    exit;
}

// ดึงกิจกรรมทั้งหมด
$events = $pdo->query("
    SELECT e.*, COUNT(r.id) as reg_count
    FROM events e
    LEFT JOIN registrations r ON r.event_id = e.id
    GROUP BY e.id
    ORDER BY e.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>จัดการกิจกรรม — <?= SITE_NAME ?></title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap');
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Sarabun',sans-serif;background:#0f1117;color:#e6edf3;min-height:100vh}
/* Sidebar */
.sidebar{position:fixed;left:0;top:0;bottom:0;width:240px;background:#161b28;border-right:1px solid rgba(56,139,253,.15);padding:24px 0;z-index:100}
.sidebar-logo{padding:0 20px 24px;border-bottom:1px solid rgba(255,255,255,.06)}
.sidebar-logo .icon{font-size:28px;margin-bottom:8px}
.sidebar-logo h2{font-size:15px;font-weight:700;color:#e6edf3}
.sidebar-logo p{font-size:12px;color:#7d8590;margin-top:2px}
.nav-section{padding:16px 12px 8px;color:#4a515a;font-size:11px;text-transform:uppercase;letter-spacing:.8px;font-weight:600}
.nav-item{display:flex;align-items:center;gap:10px;padding:10px 20px;color:#7d8590;text-decoration:none;font-size:14px;transition:all .2s;cursor:pointer;border:none;background:none;width:100%;text-align:left}
.nav-item:hover,.nav-item.active{color:#e6edf3;background:rgba(56,139,253,.1)}
.nav-item.active{border-left:2px solid #388bfd}
.nav-item .ico{width:18px;text-align:center}
.sidebar-footer{position:absolute;bottom:0;left:0;right:0;padding:16px 20px;border-top:1px solid rgba(255,255,255,.06)}
.admin-chip{display:flex;align-items:center;gap:10px}
.admin-avatar{width:32px;height:32px;background:linear-gradient(135deg,#388bfd,#1f6feb);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px}
.admin-info{flex:1}
.admin-info .name{font-size:13px;font-weight:600}
.admin-info .role{font-size:11px;color:#7d8590}
.logout-btn{color:#7d8590;text-decoration:none;font-size:12px;padding:4px 8px;border-radius:4px;transition:.2s}
.logout-btn:hover{color:#f85149}
/* Main */
.main{margin-left:240px;padding:32px;min-height:100vh}
.topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:32px}
.topbar h1{font-size:24px;font-weight:700}
.topbar p{color:#7d8590;font-size:14px;margin-top:2px}
/* Stats */
.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:32px}
.stat-card{background:#161b28;border:1px solid rgba(255,255,255,.06);border-radius:12px;padding:20px}
.stat-label{font-size:12px;color:#7d8590;text-transform:uppercase;letter-spacing:.5px}
.stat-value{font-size:32px;font-weight:700;margin-top:6px}
.stat-value.blue{color:#388bfd}
.stat-value.green{color:#3fb950}
.stat-value.orange{color:#e3b341}
/* Form Card */
.form-card{background:#161b28;border:1px solid rgba(255,255,255,.06);border-radius:12px;padding:28px;margin-bottom:32px}
.form-card h3{font-size:16px;font-weight:700;margin-bottom:20px;display:flex;align-items:center;gap:8px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-grid.full{grid-template-columns:1fr}
.form-group{display:flex;flex-direction:column;gap:6px}
label{font-size:12px;color:#7d8590;font-weight:600;text-transform:uppercase;letter-spacing:.4px}
input,textarea,select{background:rgba(13,17,23,.8);border:1px solid rgba(48,54,61,.8);border-radius:8px;padding:10px 14px;color:#e6edf3;font-family:'Sarabun',sans-serif;font-size:14px;transition:.2s;width:100%}
input:focus,textarea:focus,select:focus{outline:none;border-color:#388bfd;box-shadow:0 0 0 3px rgba(56,139,253,.1)}
textarea{resize:vertical;min-height:80px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 20px;border-radius:8px;font-family:'Sarabun',sans-serif;font-size:14px;font-weight:600;cursor:pointer;border:none;transition:.2s;text-decoration:none}
.btn-primary{background:linear-gradient(135deg,#388bfd,#1f6feb);color:#fff}
.btn-primary:hover{opacity:.9;transform:translateY(-1px)}
.btn-danger{background:rgba(248,81,73,.1);border:1px solid rgba(248,81,73,.3);color:#f85149}
.btn-danger:hover{background:rgba(248,81,73,.2)}
.btn-sm{padding:6px 14px;font-size:13px}
/* Events Table */
.events-card{background:#161b28;border:1px solid rgba(255,255,255,.06);border-radius:12px;overflow:hidden}
.events-card h3{font-size:16px;font-weight:700;padding:20px 24px;border-bottom:1px solid rgba(255,255,255,.06);display:flex;align-items:center;gap:8px}
table{width:100%;border-collapse:collapse}
thead th{padding:12px 20px;text-align:left;font-size:11px;color:#7d8590;text-transform:uppercase;letter-spacing:.5px;background:rgba(0,0,0,.2);font-weight:600}
tbody td{padding:14px 20px;border-top:1px solid rgba(255,255,255,.04);font-size:14px;vertical-align:middle}
tbody tr:hover{background:rgba(56,139,253,.03)}
.badge{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:20px;font-size:12px;font-weight:600}
.badge-active{background:rgba(63,185,80,.1);color:#3fb950;border:1px solid rgba(63,185,80,.2)}
.badge-inactive{background:rgba(125,133,144,.1);color:#7d8590;border:1px solid rgba(125,133,144,.2)}
.badge-count{background:rgba(56,139,253,.1);color:#388bfd;border:1px solid rgba(56,139,253,.2)}
.actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.btn-link{background:none;border:none;color:#388bfd;cursor:pointer;font-family:'Sarabun',sans-serif;font-size:13px;padding:4px;text-decoration:none}
.btn-link:hover{text-decoration:underline}
.alert{padding:12px 16px;border-radius:8px;font-size:14px;margin-bottom:20px;display:flex;align-items:center;gap:8px}
.alert-success{background:rgba(63,185,80,.1);border:1px solid rgba(63,185,80,.2);color:#3fb950}
.alert-error{background:rgba(248,81,73,.1);border:1px solid rgba(248,81,73,.2);color:#f85149}
.empty{text-align:center;padding:60px 20px;color:#4a515a}
.empty .ico{font-size:48px;margin-bottom:12px}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-logo">
        <div class="icon">🎓</div>
        <h2><?= SITE_NAME ?></h2>
        <p>ระบบจัดการสำหรับแอดมิน</p>
    </div>

    <div class="nav-section">เมนูหลัก</div>
    <a class="nav-item active" href="admin.php"><span class="ico">📋</span> จัดการกิจกรรม</a>

    <div class="sidebar-footer">
        <div class="admin-chip">
            <div class="admin-avatar">👤</div>
            <div class="admin-info">
                <div class="name"><?= sanitize($_SESSION['admin_name']) ?></div>
                <div class="role">Administrator</div>
            </div>
        </div>
        <div style="margin-top:12px">
            <a href="admin_logout.php" class="logout-btn">🚪 ออกจากระบบ</a>
        </div>
    </div>
</div>

<!-- Main -->
<div class="main">
    <div class="topbar">
        <div>
            <h1>📋 จัดการกิจกรรม</h1>
            <p>สร้างและจัดการกิจกรรมทั้งหมดในระบบ</p>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $msgType ?>">
        <?= $msgType === 'success' ? '✅' : '⚠️' ?> <?= $message ?>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <?php
    $totalEvents  = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
    $activeEvents = $pdo->query("SELECT COUNT(*) FROM events WHERE status='active'")->fetchColumn();
    $totalRegs    = $pdo->query("SELECT COUNT(*) FROM registrations")->fetchColumn();
    ?>
    <div class="stats">
        <div class="stat-card">
            <div class="stat-label">กิจกรรมทั้งหมด</div>
            <div class="stat-value blue"><?= $totalEvents ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">กิจกรรมที่เปิดรับ</div>
            <div class="stat-value green"><?= $activeEvents ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">ผู้ลงทะเบียนทั้งหมด</div>
            <div class="stat-value orange"><?= $totalRegs ?></div>
        </div>
    </div>

    <!-- Add Event Form -->
    <div class="form-card">
        <h3>➕ เพิ่มกิจกรรมใหม่</h3>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group" style="grid-column:1/-1">
                    <label>ชื่อกิจกรรม *</label>
                    <input type="text" name="title" placeholder="เช่น ปฐมนิเทศนักศึกษาใหม่ ปีการศึกษา 2568" required>
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label>รายละเอียด</label>
                    <textarea name="description" placeholder="อธิบายกิจกรรมเพิ่มเติม..."></textarea>
                </div>
                <div class="form-group">
                    <label>สถานที่จัดกิจกรรม</label>
                    <input type="text" name="location" placeholder="เช่น หอประชุมใหญ่">
                </div>
                <div class="form-group">
                    <label>วันที่จัดกิจกรรม *</label>
                    <input type="date" name="event_date" required>
                </div>
                <div class="form-group">
                    <label>เวลา</label>
                    <input type="time" name="event_time">
                </div>
            </div>
            <div style="margin-top:20px">
                <button type="submit" name="add_event" class="btn btn-primary">➕ เพิ่มกิจกรรม</button>
            </div>
        </form>
    </div>

    <!-- Events List -->
    <div class="events-card">
        <h3>📅 รายการกิจกรรมทั้งหมด (<?= count($events) ?> กิจกรรม)</h3>
        <?php if (empty($events)): ?>
        <div class="empty">
            <div class="ico">📭</div>
            <p>ยังไม่มีกิจกรรม กรุณาเพิ่มกิจกรรมใหม่</p>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>กิจกรรม</th>
                    <th>วันที่</th>
                    <th>สถานที่</th>
                    <th>ผู้ลงทะเบียน</th>
                    <th>สถานะ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($events as $ev): ?>
                <tr>
                    <td>
                        <div style="font-weight:600"><?= sanitize($ev['title']) ?></div>
                        <?php if ($ev['description']): ?>
                        <div style="color:#7d8590;font-size:12px;margin-top:2px"><?= mb_substr(sanitize($ev['description']), 0, 50) ?>...</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div><?= formatThaiDate($ev['event_date']) ?></div>
                        <?php if ($ev['event_time']): ?>
                        <div style="color:#7d8590;font-size:12px"><?= date('H:i', strtotime($ev['event_time'])) ?> น.</div>
                        <?php endif; ?>
                    </td>
                    <td style="color:#7d8590"><?= sanitize($ev['location'] ?: '-') ?></td>
                    <td>
                        <span class="badge badge-count">👥 <?= $ev['reg_count'] ?> คน</span>
                    </td>
                    <td>
                        <span class="badge <?= $ev['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>">
                            <?= $ev['status'] === 'active' ? '🟢 เปิดรับ' : '⭕ ปิด' ?>
                        </span>
                    </td>
                    <td>
                        <div class="actions">
                            <a href="admin_event.php?id=<?= $ev['id'] ?>" class="btn btn-sm btn-primary">📊 ดูข้อมูล</a>
                            <a href="admin_qr.php?id=<?= $ev['id'] ?>" class="btn btn-sm" style="background:rgba(227,179,65,.1);border:1px solid rgba(227,179,65,.3);color:#e3b341">📱 QR Code</a>
                            <a href="admin.php?toggle=<?= $ev['id'] ?>" class="btn-link" title="เปลี่ยนสถานะ"><?= $ev['status'] === 'active' ? '🔒 ปิด' : '🔓 เปิด' ?></a>
                            <a href="admin.php?delete=<?= $ev['id'] ?>" class="btn-link" style="color:#f85149" onclick="return confirm('ยืนยันการลบกิจกรรมนี้?')">🗑️ ลบ</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
