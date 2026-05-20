<?php
require_once 'config.php';

if (isAdminLoggedIn()) {
    header('Location: admin.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        // password_verify หรือเช็คตรงๆ (default: admin1234)
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            header('Location: admin.php');
            exit;
        } else {
            $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
        }
    } else {
        $error = 'กรุณากรอกข้อมูลให้ครบ';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>เข้าสู่ระบบแอดมิน — <?= SITE_NAME ?></title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap');
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Sarabun',sans-serif;background:#0f1117;min-height:100vh;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse at 20% 50%,#1a2a4a 0%,transparent 60%),radial-gradient(ellipse at 80% 20%,#0d2137 0%,transparent 50%);z-index:0}
.grid-bg{position:fixed;inset:0;background-image:linear-gradient(rgba(56,139,253,.05) 1px,transparent 1px),linear-gradient(90deg,rgba(56,139,253,.05) 1px,transparent 1px);background-size:40px 40px;z-index:0}
.card{position:relative;z-index:1;background:rgba(22,27,40,.9);border:1px solid rgba(56,139,253,.2);border-radius:16px;padding:48px 40px;width:100%;max-width:420px;box-shadow:0 0 60px rgba(56,139,253,.1),0 20px 60px rgba(0,0,0,.5)}
.logo{text-align:center;margin-bottom:36px}
.logo-icon{width:64px;height:64px;background:linear-gradient(135deg,#388bfd,#1f6feb);border-radius:14px;display:inline-flex;align-items:center;justify-content:center;font-size:28px;margin-bottom:16px;box-shadow:0 0 30px rgba(56,139,253,.4)}
.logo h1{color:#e6edf3;font-size:22px;font-weight:700}
.logo p{color:#7d8590;font-size:14px;margin-top:4px}
.badge-admin{display:inline-block;background:rgba(56,139,253,.15);border:1px solid rgba(56,139,253,.3);color:#388bfd;font-size:11px;padding:3px 10px;border-radius:20px;margin-top:8px;letter-spacing:.5px}
label{display:block;color:#7d8590;font-size:13px;margin-bottom:6px;margin-top:20px;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
input{width:100%;background:rgba(13,17,23,.8);border:1px solid rgba(48,54,61,.8);border-radius:8px;padding:12px 16px;color:#e6edf3;font-family:'Sarabun',sans-serif;font-size:15px;transition:border-color .2s,box-shadow .2s}
input:focus{outline:none;border-color:#388bfd;box-shadow:0 0 0 3px rgba(56,139,253,.15)}
.btn{width:100%;background:linear-gradient(135deg,#388bfd,#1f6feb);border:none;border-radius:8px;padding:14px;color:#fff;font-family:'Sarabun',sans-serif;font-size:16px;font-weight:700;cursor:pointer;margin-top:28px;transition:opacity .2s,transform .2s,box-shadow .2s;letter-spacing:.3px}
.btn:hover{opacity:.9;transform:translateY(-1px);box-shadow:0 8px 25px rgba(56,139,253,.4)}
.btn:active{transform:translateY(0)}
.error{background:rgba(248,81,73,.1);border:1px solid rgba(248,81,73,.3);color:#f85149;border-radius:8px;padding:12px 16px;font-size:14px;margin-top:20px;text-align:center}
.hint{text-align:center;margin-top:20px;color:#4a515a;font-size:12px}
.hint a{color:#388bfd;text-decoration:none}
</style>
</head>
<body>
<div class="grid-bg"></div>
<div class="card">
    <div class="logo">
        <div class="logo-icon">🎓</div>
        <h1><?= SITE_NAME ?></h1>
        <p>ระบบจัดการกิจกรรมสำหรับสถานศึกษา</p>
        <span class="badge-admin">ADMIN PORTAL</span>
    </div>

    <?php if ($error): ?>
    <div class="error">⚠️ <?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>ชื่อผู้ใช้</label>
        <input type="text" name="username" placeholder="กรอกชื่อผู้ใช้" required autocomplete="username">

        <label>รหัสผ่าน</label>
        <input type="password" name="password" placeholder="กรอกรหัสผ่าน" required autocomplete="current-password">

        <button type="submit" class="btn">🔐 เข้าสู่ระบบ</button>
    </form>

    <p class="hint">รหัสผ่านเริ่มต้น: admin / admin1234 — <a href="#">เปลี่ยนรหัสผ่าน</a></p>
</div>
</body>
</html>
