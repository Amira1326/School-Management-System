<?php
session_start();
session_destroy(); // حذف الجلسة
header("Location: admin-login.php"); // إعادة التوجيه لصفحة الدخول
exit();
?>
