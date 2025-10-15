<?php
$id = $_GET['id'];
$conn = new mysqli("localhost", "root", "", "aljood_center");
$result = $conn->query("SELECT * FROM classes WHERE رقم = $id");
$row = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تعديل الفصل - مركز الجود</title>
    <style>
        /* نفس التنسيق السابق */
    </style>
</head>
<body>
    <div class="container">
        <img src="logo.png" alt="شعار مركز الجود" class="logo">
        <h2>تعديل بيانات الفصل</h2>
        <form action="update_class.php" method="POST">
            <input type="hidden" name="id" value="<?= $row['رقم'] ?>">

            <label>اسم الفصل:</label>
            <input type="text" name="class_name" value="<?= $row['اسم الفصل'] ?>" required>

            <label>المرحلة الدراسية:</label>
            <input type="text" name="grade_level" value="<?= $row['المرحلة الدراسية'] ?>" required>

            <label>اسم المعلم:</label>
            <input type="text" name="teacher_name" value="<?= $row['اسم المعلم'] ?>" required>

            <label>تاريخ الإضافة:</label>
            <input type="date" name="created_at" value="<?= $row['تاريخ الإضافة'] ?>" required>

            <button type="submit">تحديث</button>
        </form>
    </div>
</body>
</html>
