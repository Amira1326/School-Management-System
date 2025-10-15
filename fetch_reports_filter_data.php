<?php
// هذا الملف يقوم بجلب البيانات (فصول أو طلاب) بناءً على طلبات AJAX من teacher_reports.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['teacher_id'])) {
    // إذا لم يكن المعلم مسجلاً الدخول، لا ترسل أي بيانات.
    echo json_encode([]);
    exit();
}

include 'db.php'; // تضمين ملف الاتصال بقاعدة البيانات

$teacher_id = $_SESSION['teacher_id'];
$type = $_GET['type'] ?? ''; // لتحديد نوع البيانات المطلوبة (classes أو students)
$stage = $_GET['stage'] ?? ''; // المرحلة الدراسية المختارة
$class = $_GET['class'] ?? ''; // الفصل المختار

header('Content-Type: application/json; charset=utf-8'); // لتحديد أن الاستجابة JSON

$data = [];

if ($type === 'classes' && !empty($stage)) {
    // جلب الفصول بناءً على المرحلة المحددة والمعلمة
    $sql = "SELECT DISTINCT `الفصل` FROM students WHERE `معرف_المعلمة` = ? AND `المرحلة_الدراسية` = ? ORDER BY `الفصل` ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $teacher_id, $stage);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
} elseif ($type === 'students' && !empty($stage) && !empty($class)) {
    // جلب الطلاب بناءً على المرحلة والفصل والمعلمة
    // تم تغيير 'id' إلى 'رقم' في استعلام SELECT
    $sql = "SELECT `رقم`, `اسم_الطالب` FROM students WHERE `معرف_المعلمة` = ? AND `المرحلة_الدراسية` = ? AND `الفصل` = ? ORDER BY `اسم_الطالب` ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $teacher_id, $stage, $class);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
}

echo json_encode($data); // إرجاع البيانات بصيغة JSON

$conn->close();
?>