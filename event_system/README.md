# ระบบลงทะเบียนกิจกรรม — คู่มือติดตั้ง

## โครงสร้างไฟล์
```
event_system/
├── config.php          ← ตั้งค่าฐานข้อมูล (แก้ตรงนี้ก่อน!)
├── index.php           ← หน้าแรก (redirect → register.php)
├── register.php        ← หน้าลงทะเบียนนักศึกษา (เปิดผ่าน QR)
├── admin_login.php     ← หน้าเข้าสู่ระบบแอดมิน
├── admin.php           ← หน้าจัดการกิจกรรม
├── admin_event.php     ← ดูรายชื่อผู้ลงทะเบียนแยกแผนก/ห้อง
├── admin_qr.php        ← สร้างและดาวน์โหลด QR Code
├── admin_export.php    ← Export รายชื่อเป็น CSV (เปิด Excel ได้)
├── admin_logout.php    ← ออกจากระบบ
└── event_system.sql    ← ไฟล์ฐานข้อมูล (import ครั้งแรก)
```

---

## ขั้นตอนที่ 1: สมัครบริการฟรี

### FreeSQLDatabase (ฐานข้อมูล MySQL ฟรี)
1. ไปที่ https://freesqldatabase.com → Sign Up
2. รับข้อมูลทางอีเมล: DB_HOST, DB_NAME, DB_USER, DB_PASS

### InfinityFree (PHP Hosting ฟรี)
1. ไปที่ https://infinityfree.com → Sign Up
2. สร้าง Hosting Account → ได้ subdomain เช่น yourname.rf.gd

---

## ขั้นตอนที่ 2: Import ฐานข้อมูล

1. เปิด https://www.phpmyadmin.co
2. กรอกข้อมูล Server/Username/Password จาก FreeSQLDatabase
3. เลือก Database ของคุณ → คลิก **Import**
4. เลือกไฟล์ `event_system.sql` → คลิก **Go**

---

## ขั้นตอนที่ 3: แก้ไข config.php

```php
define('DB_HOST', 'sql.freesqldatabase.com');  // จาก FreeSQLDatabase
define('DB_NAME', 'sql_xxxxxxx');              // จาก FreeSQLDatabase
define('DB_USER', 'sql_xxxxxxx');              // จาก FreeSQLDatabase
define('DB_PASS', 'รหัสผ่าน');                 // จาก FreeSQLDatabase
define('SITE_URL', 'https://yourname.rf.gd'); // URL จริงของคุณ
```

---

## ขั้นตอนที่ 4: อัปโหลดไฟล์

### ใช้ File Manager ใน cPanel ของ InfinityFree:
1. เข้า InfinityFree Dashboard → Control Panel
2. เปิด File Manager → โฟลเดอร์ **htdocs**
3. ลบไฟล์ที่มีอยู่เดิม (ถ้ามี)
4. อัปโหลดไฟล์ทั้งหมดจาก event_system/ ลงใน htdocs โดยตรง

---

## ขั้นตอนที่ 5: เข้าใช้งาน

- **แอดมิน:** https://yourname.rf.gd/admin_login.php
  - Username: `admin`
  - Password: `admin1234` ← **เปลี่ยนทันทีหลัง login ครั้งแรก!**

- **นักศึกษา (ผ่าน QR):** สแกน QR Code ที่สร้างจากหน้า admin

---

## วิธีเปลี่ยนรหัสผ่าน Admin

รันคำสั่งนี้ใน phpMyAdmin (SQL tab):
```sql
UPDATE admins 
SET password = '$2y$10$...' 
WHERE username = 'admin';
```
หรือสร้าง hash ใหม่ที่: https://bcrypt-generator.com (Cost: 10)

---

## แผนกที่ระบบรองรับ (แก้ได้ใน register.php)
- แผนกคอมพิวเตอร์ธุรกิจ
- แผนกเทคโนโลยีสารสนเทศ
- แผนกการบัญชี
- แผนกการตลาด
- แผนกการจัดการ
- แผนกการโรงแรม
- แผนกการท่องเที่ยว
- แผนกช่างยนต์
- แผนกช่างไฟฟ้า
- แผนกช่างอิเล็กทรอนิกส์
- แผนกช่างกลโรงงาน
- แผนกการก่อสร้าง
- อื่นๆ

หากต้องการเพิ่ม/แก้แผนก แก้ได้ที่ไฟล์ `register.php` บรรทัด `$departments = [...]`
