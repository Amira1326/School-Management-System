<?php
$host = "localhost";
$dbname = "aljood_center";
$username = "root";
$password = "";

$conn = new mysqli($host, $username, $password, $dbname);
$conn->set_charset("utf8");

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=student_absences.xls");

echo "<table border='1'><tr><th>رقم الطالب</th><th>التاريخ</th><th>وقت التسجيل</th></tr>";

$res = $conn->query("SELECT * FROM student_absences ORDER BY التاريخ DESC");
while ($row = $res->fetch_assoc()) {
    echo "<tr><td>{$row['معرف_الطالب']}</td><td>{$row['التاريخ']}</td><td>{$row['وقت_التسجيل']}</td></tr>";
}

echo "</table>";
exit;
?>



هذا جدول classes 

رقم	المرحلة_الدراسية	الفصل	معرف المعلمة	اسم المعلمة	تاريخ الاضافة

هذا جدول student 
