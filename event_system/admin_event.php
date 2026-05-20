<?php
require_once 'config.php';
requireAdmin();

$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$id]);
$event = $stmt->fetch();
if (!$event) { header('Location: admin.php'); exit; }

// กรองตามแผนกและห้อง
$filterDept  = sanitize($_GET['dept'] ?? '');
$filterClass = sanitize($_GET['class'] ?? '');

// ดึงแผนกทั้งหมดที่มีในกิจกรรมนี้
$depts = $pdo->prepare("SELECT DISTINCT department FROM registrations WHERE event_id=? ORDER BY department");
$depts->execute([$id]);
$departments = $depts->fetchAll(PDO::FETCH_COLUMN);

// ดึงห้องเรียนทั้งหมด
$classes = $pdo->prepare("SELECT DISTINCT classroom FROM registrations WHERE event_id=? ORDER BY classroom");
$classes->execute([$id]);
$classrooms = $classes->fetchAll(PDO::FETCH_COLUMN);

// ดึงข้อมูลผู้ลงทะเบียน
$sql = "SELECT * FROM registrations WHERE event_id=?";
$params = [$id];
if ($filterDept) { $sql .= " AND department=?"; $params[] = $filterDept; }
if ($filterClass) { $sql .= " AND classroom=?"; $params[] = $filterClass; }
$sql .= " ORDER BY department, classroom, lastname";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$registrations = $stmt->fetchAll();

// สรุปตามแผนก+ห้อง (สำหรับ grid overview)
$summary = $pdo->prepare("
    SELECT department, classroom, COUNT(*) as cnt
    FROM registrations WHERE event_id=?
    GROUP BY department, classroom
    ORDER BY department, classroom
");
$summary->execute([$id]);
$summaryData = $summary->fetchAll();

// จัดกลุ่ม summary
$grouped = [];
foreach ($summaryData as $row) {
    $grouped[$row['department']][$row['classroom']] = $row['cnt'];
}

$totalRegs = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE event_id=?");
$totalRegs->execute([$id]);
$total = $totalRegs->fetchColumn();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= sanitize($event['title']) ?> — ข้อมูลผู้ลงทะเบียน</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap');
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Sarabun',sans-serif;background:#0f1117;color:#e6edf3;min-height:100vh}
.sidebar{position:fixed;left:0;top:0;bottom:0;width:240px;background:#161b28;border-right:1px solid rgba(56,139,253,.15);padding:24px 0;z-index:100}
.sidebar-logo{padding:0 20px 24px;border-bottom:1px solid rgba(255,255,255,.06)}
.sidebar-logo .icon{font-size:28px;margin-bottom:8px}
.sidebar-logo h2{font-size:15px;font-weight:700;color:#e6edf3}
.sidebar-logo p{font-size:12px;color:#7d8590;margin-top:2px}
.nav-section{padding:16px 12px 8px;color:#4a515a;font-size:11px;text-transform:uppercase;letter-spacing:.8px;font-weight:600}
.nav-item{display:flex;align-items:center;gap:10px;padding:10px 20px;color:#7d8590;text-decoration:none;font-size:14px;transition:all .2s}
.nav-item:hover,.nav-item.active{color:#e6edf3;background:rgba(56,139,253,.1)}
.nav-item.active{border-left:2px solid #388bfd}
.sidebar-footer{position:absolute;bottom:0;left:0;right:0;padding:16px 20px;border-top:1px solid rgba(255,255,255,.06)}
.logout-btn{color:#7d8590;text-decoration:none;font-size:12px}
.logout-btn:hover{color:#f85149}
.main{margin-left:240px;padding:32px;min-height:100vh}
.breadcrumb{display:flex;align-items:center;gap:8px;color:#7d8590;font-size:13px;margin-bottom:20px}
.breadcrumb a{color:#388bfd;text-decoration:none}
.breadcrumb a:hover{text-decoration:underline}
.event-header{background:#161b28;border:1px solid rgba(255,255,255,.06);border-radius:12px;padding:24px;margin-bottom:24px}
.event-header h1{font-size:22px;font-weight:700;margin-bottom:8px}
.event-meta{display:flex;gap:20px;flex-wrap:wrap}
.meta-item{display:flex;align-items:center;gap:6px;color:#7d8590;font-size:13px}
.badge{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:20px;font-size:12px;font-weight:600}
.badge-active{background:rgba(63,185,80,.1);color:#3fb950;border:1px solid rgba(63,185,80,.2)}
/* Overview Grid */
.section-title{font-size:16px;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.dept-block{background:#161b28;border:1px solid rgba(255,255,255,.06);border-radius:12px;margin-bottom:20px;overflow:hidden}
.dept-header{padding:16px 20px;background:rgba(56,139,253,.08);border-bottom:1px solid rgba(255,255,255,.06);display:flex;align-items:center;justify-content:space-between}
.dept-name{font-weight:700;font-size:15px;color:#388bfd}
.dept-total{font-size:13px;color:#7d8590}
.class-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;padding:16px 20px}
.class-btn{background:rgba(13,17,23,.6);border:1px solid rgba(48,54,61,.8);border-radius:10px;padding:14px 16px;text-decoration:none;color:#e6edf3;transition:.2s;text-align:center;cursor:pointer;display:block}
.class-btn:hover{border-color:#388bfd;background:rgba(56,139,253,.08);transform:translateY(-2px)}
.class-btn.selected{border-color:#388bfd;background:rgba(56,139,253,.12)}
.class-btn .room-name{font-size:13px;font-weight:600;margin-bottom:4px}
.class-btn .room-count{font-size:22px;font-weight:700;color:#388bfd}
.class-btn .room-label{font-size:11px;color:#7d8590}
/* Filter Bar */
.filter-bar{background:#161b28;border:1px solid rgba(255,255,255,.06);border-radius:12px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.filter-bar select{background:rgba(13,17,23,.8);border:1px solid rgba(48,54,61,.8);border-radius:8px;padding:8px 12px;color:#e6edf3;font-family:'Sarabun',sans-serif;font-size:14px}
.filter-bar select:focus{outline:none;border-color:#388bfd}
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-family:'Sarabun',sans-serif;font-size:14px;font-weight:600;cursor:pointer;border:none;transition:.2s;text-decoration:none}
.btn-primary{background:linear-gradient(135deg,#388bfd,#1f6feb);color:#fff}
.btn-primary:hover{opacity:.9}
.btn-outline{background:none;border:1px solid rgba(48,54,61,.8);color:#7d8590}
.btn-outline:hover{border-color:#388bfd;color:#388bfd}
/* Table */
.table-card{background:#161b28;border:1px solid rgba(255,255,255,.06);border-radius:12px;overflow:hidden}
.table-card-header{padding:16px 20px;border-bottom:1px solid rgba(255,255,255,.06);display:flex;align-items:center;justify-content:space-between}
.table-card-header h3{font-size:15px;font-weight:700}
table{width:100%;border-collapse:collapse}
thead th{padding:10px 16px;text-align:left;font-size:11px;color:#7d8590;text-transform:uppercase;letter-spacing:.5px;background:rgba(0,0,0,.2);font-weight:600}
tbody td{padding:12px 16px;border-top:1px solid rgba(255,255,255,.04);font-size:14px}
tbody tr:hover{background:rgba(56,139,253,.03)}
.no-data{text-align:center;padding:40px;color:#4a515a}
.highlight-dept{background:rgba(56,139,253,.05)}
.num{font-family:monospace;color:#7d8590;font-size:12px}
</style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-logo">
        <div class="icon">🎓</div>
        <h2><?= SITE_NAME ?></h2>
        <p>ระบบจัดการสำหรับแอดมิน</p>
    </div>
    <div class="nav-section">เมนูหลัก</div>
    <a class="nav-item" href="admin.php"><span>📋</span> จัดการกิจกรรม</a>
    <a class="nav-item active" href="#"><span>📊</span> ข้อมูลผู้ลงทะเบียน</a>
    <div class="sidebar-footer">
        <a href="admin_logout.php" class="logout-btn">🚪 ออกจากระบบ</a>
    </div>
</div>

<div class="main">
    <div class="breadcrumb">
        <a href="admin.php">📋 จัดการกิจกรรม</a>
        <span>›</span>
        <span><?= sanitize($event['title']) ?></span>
    </div>

    <div class="event-header">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap">
            <div>
                <h1><?= sanitize($event['title']) ?></h1>
                <div class="event-meta" style="margin-top:10px">
                    <div class="meta-item">📅 <?= formatThaiDate($event['event_date']) ?><?= $event['event_time'] ? ' เวลา ' . date('H:i', strtotime($event['event_time'])) . ' น.' : '' ?></div>
                    <?php if ($event['location']): ?>
                    <div class="meta-item">📍 <?= sanitize($event['location']) ?></div>
                    <?php endif; ?>
                    <div class="meta-item">👥 ผู้ลงทะเบียน <?= $total ?> คน</div>
                </div>
            </div>
            <div style="display:flex;gap:8px">
                <a href="admin_qr.php?id=<?= $id ?>" class="btn" style="background:rgba(227,179,65,.1);border:1px solid rgba(227,179,65,.3);color:#e3b341">📱 QR Code</a>
                <a href="admin_export.php?id=<?= $id ?>&dept=<?= urlencode($filterDept) ?>&class=<?= urlencode($filterClass) ?>" class="btn btn-primary">📥 Export Excel</a>
            </div>
        </div>
    </div>

    <?php if (!empty($grouped)): ?>
    <!-- Overview by Dept/Class -->
    <div style="margin-bottom:28px">
        <div class="section-title">🗂️ ภาพรวมตามแผนก / ห้องเรียน <span style="color:#7d8590;font-weight:400;font-size:13px">— กดเพื่อกรองดูข้อมูล</span></div>

        <?php foreach ($grouped as $dept => $rooms): ?>
        <div class="dept-block">
            <div class="dept-header">
                <div class="dept-name">📌 <?= sanitize($dept) ?></div>
                <div class="dept-total">รวม <?= array_sum($rooms) ?> คน</div>
            </div>
            <div class="class-grid">
                <?php foreach ($rooms as $room => $cnt): ?>
                <?php
                $isSelected = ($filterDept === $dept && $filterClass === $room);
                $url = "admin_event.php?id=$id&dept=" . urlencode($dept) . "&class=" . urlencode($room);
                ?>
                <a href="<?= $url ?>" class="class-btn <?= $isSelected ? 'selected' : '' ?>">
                    <div class="room-name">🏫 <?= sanitize($room) ?></div>
                    <div class="room-count"><?= $cnt ?></div>
                    <div class="room-label">คน</div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Filter Bar -->
    <form method="GET" action="">
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="filter-bar">
            <span style="font-size:14px;color:#7d8590;font-weight:600">🔍 กรองข้อมูล:</span>
            <select name="dept">
                <option value="">— แผนกทั้งหมด —</option>
                <?php foreach ($departments as $d): ?>
                <option value="<?= sanitize($d) ?>" <?= $filterDept === $d ? 'selected' : '' ?>><?= sanitize($d) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="class">
                <option value="">— ห้องเรียนทั้งหมด —</option>
                <?php foreach ($classrooms as $c): ?>
                <option value="<?= sanitize($c) ?>" <?= $filterClass === $c ? 'selected' : '' ?>><?= sanitize($c) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">ค้นหา</button>
            <a href="admin_event.php?id=<?= $id ?>" class="btn btn-outline">รีเซ็ต</a>
        </div>
    </form>

    <!-- Registrations Table -->
    <div class="table-card">
        <div class="table-card-header">
            <h3>
                รายชื่อผู้ลงทะเบียน
                <?php if ($filterDept || $filterClass): ?>
                — <span style="color:#388bfd"><?= sanitize($filterDept ?: 'ทุกแผนก') ?> <?= sanitize($filterClass ? "/ $filterClass" : '') ?></span>
                <?php endif; ?>
                (<?= count($registrations) ?> คน)
            </h3>
        </div>

        <?php if (empty($registrations)): ?>
        <div class="no-data">📭 ยังไม่มีผู้ลงทะเบียน</div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>ชื่อ-นามสกุล</th>
                    <th>รหัสนักศึกษา</th>
                    <th>ห้องเรียน</th>
                    <th>แผนก/สาขา</th>
                    <th>เบอร์โทร</th>
                    <th>เวลาลงทะเบียน</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $prevDept = null;
            $no = 1;
            foreach ($registrations as $reg):
                $isDeptChange = ($reg['department'] !== $prevDept);
                $prevDept = $reg['department'];
            ?>
                <?php if ($isDeptChange && !$filterDept): ?>
                <tr>
                    <td colspan="7" style="background:rgba(56,139,253,.08);padding:8px 16px;font-weight:700;color:#388bfd;font-size:13px">
                        📌 <?= sanitize($reg['department']) ?>
                    </td>
                </tr>
                <?php $no = 1; ?>
                <?php endif; ?>
                <tr>
                    <td class="num"><?= $no++ ?></td>
                    <td><strong><?= sanitize($reg['firstname']) ?> <?= sanitize($reg['lastname']) ?></strong></td>
                    <td><code style="background:rgba(56,139,253,.1);color:#388bfd;padding:2px 8px;border-radius:4px;font-size:13px"><?= sanitize($reg['student_id']) ?></code></td>
                    <td><?= sanitize($reg['classroom']) ?></td>
                    <td><span style="background:rgba(63,185,80,.08);color:#3fb950;padding:2px 8px;border-radius:4px;font-size:12px"><?= sanitize($reg['department']) ?></span></td>
                    <td style="color:#7d8590"><?= sanitize($reg['phone'] ?: '-') ?></td>
                    <td style="color:#7d8590;font-size:12px"><?= date('d/m/Y H:i', strtotime($reg['registered_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
