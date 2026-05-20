<?php
require_once 'config.php';

// รับ token จาก QR Code
$token = sanitize($_GET['event'] ?? '');
$event = null;

if ($token) {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE qr_token = ? AND status = 'active'");
    $stmt->execute([$token]);
    $event = $stmt->fetch();
}

$success = false;
$error   = '';
$alreadyReg = false;

// บันทึกข้อมูล
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $event) {
    $firstname  = sanitize($_POST['firstname'] ?? '');
    $lastname   = sanitize($_POST['lastname'] ?? '');
    $student_id = sanitize($_POST['student_id'] ?? '');
    $classroom  = sanitize($_POST['classroom'] ?? '');
    $department = sanitize($_POST['department'] ?? '');
    $phone      = sanitize($_POST['phone'] ?? '');

    if (!$firstname || !$lastname || !$student_id || !$classroom || !$department) {
        $error = 'กรุณากรอกข้อมูลให้ครบทุกช่องที่มี *';
    } elseif (!preg_match('/^\d{10,13}$/', $student_id)) {
        $error = 'รหัสนักศึกษาต้องเป็นตัวเลข 10-13 หลัก';
    } else {
        // ตรวจสอบซ้ำ
        $check = $pdo->prepare("SELECT id FROM registrations WHERE event_id=? AND student_id=?");
        $check->execute([$event['id'], $student_id]);
        if ($check->fetch()) {
            $alreadyReg = true;
            $error = 'รหัสนักศึกษา ' . $student_id . ' ได้ลงทะเบียนกิจกรรมนี้แล้ว';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO registrations (event_id, firstname, lastname, student_id, classroom, department, phone, ip_address) VALUES (?,?,?,?,?,?,?,?)");
                $stmt->execute([
                    $event['id'], $firstname, $lastname, $student_id,
                    $classroom, $department, $phone,
                    $_SERVER['REMOTE_ADDR'] ?? ''
                ]);
                $success = true;
            } catch (PDOException $e) {
                $error = 'เกิดข้อผิดพลาด กรุณาลองใหม่';
            }
        }
    }
}

// รายการแผนก/สาขา (ปรับให้ตรงกับสถาบัน)
$departments = [
    'แผนกคอมพิวเตอร์ธุรกิจ',
    'แผนกเทคโนโลยีสารสนเทศ',
    'แผนกการบัญชี',
    'แผนกการตลาด',
    'แผนกการจัดการ',
    'แผนกการโรงแรม',
    'แผนกการท่องเที่ยว',
    'แผนกช่างยนต์',
    'แผนกช่างไฟฟ้า',
    'แผนกช่างอิเล็กทรอนิกส์',
    'แผนกช่างกลโรงงาน',
    'แผนกการก่อสร้าง',
    'อื่นๆ',
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
<title><?= $event ? sanitize($event['title']) : 'ลงทะเบียนกิจกรรม' ?> — <?= SITE_NAME ?></title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap');
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Sarabun',sans-serif;background:#f0f4f8;min-height:100vh}
.hero{background:linear-gradient(135deg,#1a3a5c 0%,#0d2137 50%,#161b28 100%);padding:32px 20px 80px;text-align:center;position:relative;overflow:hidden}
.hero::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse at 50% 0%,rgba(56,139,253,.2) 0%,transparent 70%)}
.hero-icon{font-size:48px;margin-bottom:12px;position:relative}
.hero h1{color:#fff;font-size:22px;font-weight:700;margin-bottom:6px;position:relative}
.hero p{color:rgba(255,255,255,.6);font-size:14px;position:relative}
.hero-meta{display:flex;justify-content:center;gap:16px;margin-top:16px;flex-wrap:wrap;position:relative}
.hero-badge{background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);border-radius:20px;padding:5px 14px;font-size:12px;color:rgba(255,255,255,.8);display:flex;align-items:center;gap:5px}
/* Card floating over hero */
.container{max-width:480px;margin:0 auto;padding:0 16px 40px}
.form-card{background:#fff;border-radius:16px;box-shadow:0 4px 40px rgba(0,0,0,.12);margin-top:-48px;position:relative;overflow:hidden}
.form-card-header{background:linear-gradient(135deg,#388bfd,#1f6feb);padding:16px 24px;text-align:center}
.form-card-header h2{color:#fff;font-size:16px;font-weight:700}
.form-card-body{padding:24px}
.form-group{margin-bottom:18px}
label{display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:6px}
label .req{color:#ef4444}
input,select{width:100%;background:#f9fafb;border:1.5px solid #e5e7eb;border-radius:10px;padding:12px 14px;font-family:'Sarabun',sans-serif;font-size:15px;color:#111827;transition:.2s;-webkit-appearance:none}
input:focus,select:focus{outline:none;border-color:#388bfd;background:#fff;box-shadow:0 0 0 3px rgba(56,139,253,.1)}
input::placeholder{color:#9ca3af}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.hint{font-size:11px;color:#9ca3af;margin-top:4px}
.alert{padding:14px 16px;border-radius:10px;font-size:14px;margin-bottom:16px;display:flex;align-items:flex-start;gap:8px}
.alert-error{background:#fef2f2;border:1px solid #fecaca;color:#dc2626}
.alert-warning{background:#fffbeb;border:1px solid #fde68a;color:#92400e}
.btn-submit{width:100%;background:linear-gradient(135deg,#388bfd,#1f6feb);color:#fff;border:none;border-radius:12px;padding:16px;font-family:'Sarabun',sans-serif;font-size:17px;font-weight:700;cursor:pointer;transition:.2s;margin-top:8px;display:flex;align-items:center;justify-content:center;gap:8px}
.btn-submit:hover{opacity:.92;transform:translateY(-1px);box-shadow:0 8px 25px rgba(56,139,253,.4)}
.btn-submit:active{transform:translateY(0)}
.btn-submit:disabled{opacity:.5;cursor:not-allowed;transform:none}
/* Success */
.success-screen{text-align:center;padding:40px 24px}
.success-icon{font-size:64px;margin-bottom:16px;animation:pop .4s ease}
@keyframes pop{0%{transform:scale(0.5);opacity:0}80%{transform:scale(1.15)}100%{transform:scale(1);opacity:1}}
.success-screen h2{font-size:22px;font-weight:700;color:#111827;margin-bottom:8px}
.success-screen p{color:#6b7280;font-size:14px;margin-bottom:20px}
.info-summary{background:#f0f9ff;border:1px solid #bae6fd;border-radius:12px;padding:16px;text-align:left;margin-bottom:20px}
.info-row{display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid #e0f2fe}
.info-row:last-child{border-bottom:none}
.info-label{font-size:12px;color:#6b7280}
.info-value{font-size:14px;font-weight:600;color:#1e40af}
/* Error screen */
.error-screen{text-align:center;padding:40px 24px}
.no-events-screen{text-align:center;padding:40px 24px}
/* Footer */
.page-footer{text-align:center;padding:20px;color:#9ca3af;font-size:12px}
</style>
</head>
<body>

<?php if (!$event && !$token): ?>
<!-- ไม่มี token — แสดงรายการกิจกรรมที่เปิดอยู่ -->
<?php
$activeEvents = $pdo->query("SELECT * FROM events WHERE status='active' ORDER BY event_date ASC")->fetchAll();
?>
<div class="hero">
    <div class="hero-icon">🎓</div>
    <h1><?= SITE_NAME ?></h1>
    <p>เลือกกิจกรรมที่ต้องการลงทะเบียน</p>
</div>
<div class="container">
    <?php if (empty($activeEvents)): ?>
    <div class="form-card">
        <div class="no-events-screen">
            <div style="font-size:48px;margin-bottom:16px">📭</div>
            <h2 style="color:#111827;margin-bottom:8px">ไม่มีกิจกรรมที่เปิดรับ</h2>
            <p style="color:#6b7280;font-size:14px">ขณะนี้ยังไม่มีกิจกรรมที่เปิดรับลงทะเบียน<br>กรุณาติดต่อผู้ดูแลระบบ</p>
        </div>
    </div>
    <?php else: ?>
    <div class="form-card" style="margin-top:-20px">
        <div class="form-card-header"><h2>📋 รายการกิจกรรม</h2></div>
        <div style="padding:16px">
        <?php foreach ($activeEvents as $ev): ?>
            <a href="register.php?event=<?= $ev['qr_token'] ?>" style="display:block;background:#f9fafb;border:1.5px solid #e5e7eb;border-radius:12px;padding:16px;margin-bottom:12px;text-decoration:none;transition:.2s" onmouseover="this.style.borderColor='#388bfd'" onmouseout="this.style.borderColor='#e5e7eb'">
                <div style="font-weight:700;color:#111827;font-size:15px;margin-bottom:4px"><?= sanitize($ev['title']) ?></div>
                <div style="font-size:12px;color:#6b7280;display:flex;gap:12px;flex-wrap:wrap">
                    <span>📅 <?= formatThaiDate($ev['event_date']) ?></span>
                    <?php if ($ev['location']): ?><span>📍 <?= sanitize($ev['location']) ?></span><?php endif; ?>
                </div>
                <?php if ($ev['description']): ?>
                <div style="font-size:13px;color:#374151;margin-top:6px"><?= mb_substr(sanitize($ev['description']),0,80) ?>...</div>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php elseif (!$event && $token): ?>
<!-- Token ไม่ถูกต้องหรือกิจกรรมปิด -->
<div class="hero">
    <div class="hero-icon">⚠️</div>
    <h1>ไม่พบกิจกรรม</h1>
    <p>QR Code นี้ไม่ถูกต้องหรือกิจกรรมถูกปิดแล้ว</p>
</div>
<div class="container">
    <div class="form-card">
        <div class="error-screen">
            <p style="color:#6b7280;font-size:14px">กรุณาติดต่อผู้ดูแลระบบ หรือ <a href="register.php" style="color:#388bfd">ดูกิจกรรมทั้งหมด</a></p>
        </div>
    </div>
</div>

<?php elseif ($success): ?>
<!-- สำเร็จ -->
<div class="hero">
    <div class="hero-icon">🎉</div>
    <h1><?= sanitize($event['title']) ?></h1>
    <p>ลงทะเบียนเสร็จสมบูรณ์</p>
</div>
<div class="container">
    <div class="form-card">
        <div class="success-screen">
            <div class="success-icon">✅</div>
            <h2>ลงทะเบียนสำเร็จ!</h2>
            <p>ข้อมูลของคุณถูกบันทึกเรียบร้อยแล้ว</p>
            <div class="info-summary">
                <div class="info-row">
                    <span class="info-label">ชื่อ-นามสกุล</span>
                    <span class="info-value"><?= sanitize($_POST['firstname']) ?> <?= sanitize($_POST['lastname']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">รหัสนักศึกษา</span>
                    <span class="info-value"><?= sanitize($_POST['student_id']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">ห้องเรียน</span>
                    <span class="info-value"><?= sanitize($_POST['classroom']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">แผนก/สาขา</span>
                    <span class="info-value"><?= sanitize($_POST['department']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">กิจกรรม</span>
                    <span class="info-value"><?= sanitize($event['title']) ?></span>
                </div>
            </div>
            <p style="font-size:12px;color:#9ca3af">กรุณาถ่ายรูปหน้าจอนี้ไว้เป็นหลักฐาน</p>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ฟอร์มลงทะเบียน -->
<div class="hero">
    <div class="hero-icon">📝</div>
    <h1><?= sanitize($event['title']) ?></h1>
    <p>กรอกข้อมูลเพื่อลงทะเบียนเข้าร่วมกิจกรรม</p>
    <div class="hero-meta">
        <div class="hero-badge">📅 <?= formatThaiDate($event['event_date']) ?><?= $event['event_time'] ? ' ' . date('H:i', strtotime($event['event_time'])) . ' น.' : '' ?></div>
        <?php if ($event['location']): ?>
        <div class="hero-badge">📍 <?= sanitize($event['location']) ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="container">
    <div class="form-card">
        <div class="form-card-header"><h2>📋 แบบฟอร์มลงทะเบียน</h2></div>
        <div class="form-card-body">

            <?php if ($error): ?>
            <div class="alert <?= $alreadyReg ? 'alert-warning' : 'alert-error' ?>">
                <span><?= $alreadyReg ? '⚠️' : '❌' ?></span>
                <div><?= $error ?></div>
            </div>
            <?php endif; ?>

            <form method="POST" id="regForm">
                <!-- ชื่อ - นามสกุล -->
                <div class="form-row">
                    <div class="form-group">
                        <label>ชื่อ <span class="req">*</span></label>
                        <input type="text" name="firstname" placeholder="ชื่อจริง" value="<?= sanitize($_POST['firstname'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>นามสกุล <span class="req">*</span></label>
                        <input type="text" name="lastname" placeholder="นามสกุล" value="<?= sanitize($_POST['lastname'] ?? '') ?>" required>
                    </div>
                </div>

                <!-- รหัสนักศึกษา -->
                <div class="form-group">
                    <label>รหัสนักศึกษา <span class="req">*</span></label>
                    <input type="text" name="student_id" placeholder="เช่น 6832041056" value="<?= sanitize($_POST['student_id'] ?? '') ?>" maxlength="13" pattern="\d{10,13}" inputmode="numeric" required>
                    <div class="hint">กรอกตัวเลขเท่านั้น 10-13 หลัก</div>
                </div>

                <!-- ห้องเรียน -->
                <div class="form-group">
                    <label>ห้องเรียน <span class="req">*</span></label>
                    <input type="text" name="classroom" placeholder="เช่น สทธ68-22, คธ67-11" value="<?= sanitize($_POST['classroom'] ?? '') ?>" required>
                    <div class="hint">รหัสห้องเรียนหรือกลุ่มเรียนของคุณ</div>
                </div>

                <!-- แผนก/สาขา -->
                <div class="form-group">
                    <label>แผนก / สาขาที่เรียน <span class="req">*</span></label>
                    <select name="department" required>
                        <option value="">— เลือกแผนก/สาขา —</option>
                        <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept ?>" <?= (($_POST['department'] ?? '') === $dept) ? 'selected' : '' ?>><?= $dept ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- เบอร์โทร (optional) -->
                <div class="form-group">
                    <label>เบอร์โทรศัพท์ <span style="color:#9ca3af;font-weight:400">(ไม่บังคับ)</span></label>
                    <input type="tel" name="phone" placeholder="เช่น 081-234-5678" value="<?= sanitize($_POST['phone'] ?? '') ?>" inputmode="tel">
                </div>

                <button type="submit" class="btn-submit" id="submitBtn">
                    <span>✅</span> ยืนยันการลงทะเบียน
                </button>
            </form>
        </div>
    </div>
</div>

<div class="page-footer"><?= SITE_NAME ?></div>

<script>
// ป้องกัน double submit
document.getElementById('regForm').addEventListener('submit', function() {
    var btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '⏳ กำลังบันทึก...';
});

// กรองเฉพาะตัวเลขในรหัสนักศึกษา
document.querySelector('[name="student_id"]').addEventListener('input', function() {
    this.value = this.value.replace(/\D/g, '');
});
</script>
<?php endif; ?>

</body>
</html>
