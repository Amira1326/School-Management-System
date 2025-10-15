<?php
session_start();

$host = "localhost";
$dbname = "aljood_center";
$username = "root";
$password = "";

$conn = new mysqli($host, $username, $password, $dbname);
$conn->set_charset("utf8");
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

// تصدير Excel
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=students.xls");
    $res = $conn->query("SELECT * FROM students ORDER BY `رقم` DESC");
    echo "<table border='1'><tr><th>رقم</th><th>اسم الطالب</th><th>المرحلة الدراسية</th><th>الفصل</th><th>الفترة</th><th>الجنسية</th><th>رقم الهوية</th><th>رقم ولي الأمر</th><th>تاريخ الاضافة</th></tr>";
    while ($r = $res->fetch_assoc()) {
        echo "<tr><td>{$r['رقم']}</td><td>{$r['اسم_الطالب']}</td><td>{$r['المرحلة_الدراسية']}</td><td>{$r['الفصل']}</td><td>{$r['الفترة']}</td><td>{$r['الجنسية']}</td><td>{$r['رقم_الهوية']}</td><td>{$r['رقم_ولي_الأمر']}</td><td>{$r['تاريخ_الاضافة']}</td></tr>";
    }
    echo "</table>";
    exit;
}

// إضافة الطالب
if (isset($_POST['add_student']) || isset($_POST['add_student_and_back'])) {
    $studentIdNumber = $_POST['رقم_الهوية'];

    // خطوة جديدة: التحقق من وجود رقم الهوية بالفعل
    $checkStmt = $conn->prepare("SELECT `رقم` FROM students WHERE `رقم_الهوية` = ?");
    $checkStmt->bind_param("s", $studentIdNumber);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        // إذا كان رقم الهوية موجوداً، أعد توجيه المستخدم مع رسالة خطأ
        header("Location: students.php?error=duplicate_id");
        exit;
    }

    // إذا لم يكن رقم الهوية موجوداً، أكمل عملية الإضافة
    $stmt = $conn->prepare("INSERT INTO students (`اسم_الطالب`,`المرحلة_الدراسية`,`الفصل`,`الفترة`,`الجنسية`,`رقم_الهوية`,`رقم_ولي_الأمر`,`تاريخ_الاضافة`) VALUES (?,?,?,?,?,?,?,CURDATE())");
    $stmt->bind_param("sssssss", $_POST['اسم_الطالب'], $_POST['المرحلة_الدراسية'], $_POST['الفصل'], $_POST['الفترة'], $_POST['الجنسية'], $studentIdNumber, $_POST['رقم_ولي_الأمر']);
    $stmt->execute();

    if (isset($_POST['add_student_and_back'])) {
        header("Location: dashboard.php?saved=1");
    } else {
        header("Location: students.php?saved=added");
    }
    exit;
}

// تحديث الطالب
if (isset($_POST['update_student'])) {
    $stmt = $conn->prepare("UPDATE students SET `اسم_الطالب`=?,`المرحلة_الدراسية`=?,`الفصل`=?,`الفترة`=?,`الجنسية`=?,`رقم_الهوية`=?,`رقم_ولي_الأمر`=? WHERE `رقم`=?");
    $stmt->bind_param("sssssssi", $_POST['اسم_الطالب'], $_POST['المرحلة_الدراسية'], $_POST['الفصل'], $_POST['الفترة'], $_POST['الجنسية'], $_POST['رقم_الهوية'], $_POST['رقم_ولي_الأمر'], $_POST['رقم']);
    $stmt->execute();
    header("Location: students.php?saved=updated");
    exit;
}

// حذف طالب
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // الخطوة 1: حذف جميع سجلات الغياب المرتبطة بالطالب أولاً
    $conn->query("DELETE FROM student_absences WHERE `معرف_الطالب` = $id");

    // الخطوة 2: الآن، وبعد حذف الغيابات المرتبطة، يمكن حذف الطالب من جدول الطلاب
    $conn->query("DELETE FROM students WHERE `رقم` = $id");
    
    // إعادة التوجيه بعد الحذف بنجاح
    header("Location: students.php?deleted=1");
    exit;
}

// جلب بيانات الطالب عند التعديل
$edit_row = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $res = $conn->query("SELECT * FROM students WHERE `رقم` = $id");
    $edit_row = $res->fetch_assoc();
}

// بحث
$where = "";
if (!empty($_GET['keyword'])) {
    $k = "%" . $conn->real_escape_string($_GET['keyword']) . "%";
    $where = "WHERE `اسم_الطالب` LIKE '$k' OR `رقم_ولي_الأمر` LIKE '$k'";
}
$result = $conn->query("SELECT * FROM students $where ORDER BY `رقم` DESC");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>قائمة الطلاب</title>
<link rel="icon" href="logo.png" type="image/x-icon">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<style>
    :root {
        --primary-color: #89355d;
        --secondary-color: #E0BBE4;
        --text-dark: #333;
        --text-medium: #555;
        --white: #ffffff;
        --button-hover-color: #7b5065;
        --shadow-light: rgba(0, 0, 0, 0.08);
        --border-radius-pill: 25px; 
        --spacing-small: 1rem; 
        --spacing-medium: 1.25rem; 
        --spacing-large: 1.875rem; 
        --spacing-xl: 3.125rem; 
    }

    html, body {
        height: 100%;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: 'Cairo', sans-serif;
        background: linear-gradient(to bottom, var(--white) 0%, var(--secondary-color) 100%);
        line-height: 1.6;
        color: var(--text-dark);
        font-size: 1rem; 
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
    }

    .container {
        width: 95%;
        max-width: 1200px;
        margin: var(--spacing-xl) auto;
        padding: var(--spacing-large);
        background: var(--white);
        border-radius: 12px;
        box-shadow: 0 8px 24px var(--shadow-light);
        position: relative;
    }

    .logo {
        position: absolute;
        top: var(--spacing-medium);
        right: var(--spacing-medium); 
        width: 100px;
        height: auto;
        opacity: 0.9;
    }

    h1 {
        text-align: center;
        color: var(--primary-color);
        margin-bottom: var(--spacing-large);
        font-size: 2.2rem;
        font-weight: 700;
    }

    .filters, .form-section {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: var(--spacing-small);
        margin-bottom: var(--spacing-large);
    }
    
    .filters {
        padding-bottom: var(--spacing-small);
        border-bottom: 1px solid #eee;
    }

    .form-section {
        background-color: #fcf8fc;
        padding: var(--spacing-large);
        border-radius: 10px;
        box-shadow: inset 0 0 5px rgba(0,0,0,0.03);
        margin-top: var(--spacing-large);
    }

    .footer-buttons {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-start;
        gap: var(--spacing-small);
        margin-top: var(--spacing-large);
        direction: rtl;
        padding-top: var(--spacing-small);
        border-top: 1px solid #eee;
    }

    input[type="text"], select {
        padding: 12px 15px;
        border-radius: 8px;
        border: 1px solid #ddd;
        min-width: 200px;
        flex-grow: 1;
        font-size: 1rem;
        color: var(--text-dark);
        transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }

    input[type="text"]:focus, select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(137, 53, 93, 0.1);
        outline: none;
    }

    button, .button-link {
        background-color: var(--primary-color);
        color: var(--white);
        border: none;
        padding: 12px 25px;
        border-radius: var(--border-radius-pill);
        cursor: pointer;
        font-size: 1rem;
        font-weight: bold;
        transition: background-color 0.3s ease, transform 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
    }

    button:hover, .button-link:hover {
        background-color: var(--button-hover-color);
        transform: translateY(-2px);
    }
    
    .button-link {
        text-decoration: none;
    }

    table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: var(--spacing-large);
        background: var(--white);
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 12px var(--shadow-light);
    }

    th, td {
        border: 1px solid #f0f0f0;
        padding: 12px 15px;
        text-align: center;
        vertical-align: middle;
    }

    th {
        background: #fdf6fa;
        color: var(--primary-color);
        font-weight: 700;
        font-size: 1.05rem;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    tr:nth-child(even) {
        background-color: #fdfbff;
    }

    tr:hover {
        background-color: #f5f0f3;
    }

    td button {
        padding: 8px 12px;
        font-size: 0.9rem;
        border-radius: 6px;
        gap: 5px;
    }
    
    td button.delete-btn {
        background-color: #e74c3c;
    }

    td button.delete-btn:hover {
        background-color: #c0392b;
    }

    .material-icons {
        font-size: 1.2rem;
        vertical-align: middle;
    }

    .alert {
        position: relative;
        padding: var(--spacing-medium) var(--spacing-large);
        margin: var(--spacing-large) auto;
        width: 90%;
        max-width: 800px;
        text-align: center;
        border-radius: 8px;
        font-weight: bold;
        border: 1px solid;
        font-size: 1.1rem;
        opacity: 1;
        transition: opacity 0.5s ease-out;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .alert.success {
        background-color: #e6ffed;
        color: #28a745;
        border-color: #c9f0d1;
    }

    .alert.warning {
        background-color: #fff8e6;
        color: #ffc107;
        border-color: #ffeeb2;
    }

    .alert.danger {
        background-color: #ffe6e6;
        color: #e74c3c;
        border-color: #f0c9c9;
    }

    .close-btn {
        position: absolute;
        top: 10px;
        left: 15px;
        cursor: pointer;
        font-size: 1.5rem;
        line-height: 1;
        opacity: 0.7;
        transition: opacity 0.2s;
    }

    .close-btn:hover {
        opacity: 1;
    }

    @media print {
        body * {
            visibility: hidden;
        }
        .container, .container * {
            visibility: visible;
        }
        .container {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            margin: 0;
            padding: 0;
            box-shadow: none;
            background: none;
        }
        .logo {
            position: relative;
            float: right;
            margin-bottom: var(--spacing-medium);
        }
        h1 {
            text-align: right;
            margin-right: 150px;
        }
        .filters, .form-section, .footer-buttons, .alert, td button {
            display: none;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: none;
            border-radius: 0;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            font-size: 0.9em;
        }
        th {
            background: #f0f0f0 !important;
            color: #333 !important;
        }
    }

    @media (max-width: 768px) {
        .container {
            margin: var(--spacing-medium) auto;
            padding: var(--spacing-medium);
        }
        h1 {
            font-size: 1.8rem;
        }
        .form-section, .filters, .footer-buttons {
            flex-direction: column;
            align-items: stretch;
        }
        input[type="text"], select, button, .button-link {
            min-width: unset;
            width: 100%;
        }
        .logo {
            top: 10px;
            right: 10px;
            width: 80px;
        }
        table, thead, tbody, th, td, tr {
            display: block;
        }
        thead tr {
            position: absolute;
            top: -9999px;
            left: -9999px;
        }
        tr {
            border: 1px solid #eee;
            margin-bottom: var(--spacing-small);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.03);
        }
        td {
            border: none;
            position: relative;
            padding-right: 50%;
            text-align: right;
        }
        td:before {
            content: attr(data-label);
            position: absolute;
            left: 0;
            width: 45%;
            padding-left: 15px;
            font-weight: bold;
            text-align: left;
            white-space: nowrap;
            color: var(--primary-color);
        }
        td:nth-of-type(1):before { content: "الرقم:"; }
        td:nth-of-type(2):before { content: "اسم الطالب:"; } 
        td:nth-of-type(3):before { content: "المرحلة الدراسية:"; }
        td:nth-of-type(4):before { content: "الفصل:"; } 
        td:nth-of-type(5):before { content: "الفترة:"; }
        td:nth-of-type(6):before { content: "الجنسية:"; }
        td:nth-of-type(7):before { content: "رقم الهوية:"; }
        td:nth-of-type(8):before { content: "رقم ولي الأمر:"; }
        td:nth-of-type(9):before { content: "تاريخ الاضافة:"; }
        td:nth-of-type(10):before { content: "خيارات:"; }
        
        td:last-child {
            display: flex;
            justify-content: center;
            gap: 10px;
            padding-right: 15px;
        }
        td:last-child::before {
            content: '';
            width: 0;
            padding: 0;
        }
    }
</style>
</head>
<body>

<div class="container">
    <img src="logo.png" class="logo" alt="شعار المركز">
    <h1>قائمة الطلاب</h1>

    <?php if (isset($_GET['saved']) || isset($_GET['deleted']) || isset($_GET['error'])): ?>
        <div id="success-msg" class="alert 
            <?php 
                if (isset($_GET['saved'])) {
                    echo ($_GET['saved'] === 'added' || $_GET['saved'] === 'updated') ? 'success' : '';
                } elseif (isset($_GET['deleted'])) {
                    echo 'warning'; 
                } elseif (isset($_GET['error'])) {
                    echo 'danger';
                }
            ?>">
            <?php 
                if (isset($_GET['saved'])) {
                    echo ($_GET['saved'] === 'added' ? '✅ تم إضافة الطالب بنجاح.' : ($_GET['saved'] === 'updated' ? '✏️ تم تحديث بيانات الطالب.' : ''));
                } elseif (isset($_GET['deleted'])) {
                    echo '🗑️ تم حذف الطالب.';
                } elseif (isset($_GET['error'])) {
                    echo '⚠️ رقم الهوية موجود بالفعل. لا يمكن إضافة طالب بنفس رقم الهوية.';
                }
            ?>
            <span class="close-btn" onclick="closeAlert()">✖</span>
        </div>
    <?php endif; ?>

    <form method="get" class="filters">
        <input type="text" name="keyword" placeholder="بحث بالاسم أو رقم ولي الأمر" value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
        <button type="submit">
            <span class="material-icons">search</span> بحث
        </button>
        <a href="students.php" class="button-link" style="background-color: #6c757d;">
            <span class="material-icons">clear</span> إزالة الفلتر
        </a>
    </form>

    <form method="post">
        <div class="form-section">
            <?php if ($edit_row): ?>
                <input type="hidden" name="رقم" value="<?= htmlspecialchars($edit_row['رقم']) ?>">
                <input type="text" name="اسم_الطالب" value="<?= htmlspecialchars($edit_row['اسم_الطالب']) ?>" required>
            <?php else: ?>
                <input type="text" name="اسم_الطالب" placeholder="اسم الطالب/ة" required>
            <?php endif; ?>

            <select name="المرحلة_الدراسية" required>
                <option value="">اختر المرحلة الدراسية</option>
                <?php
                $levels = ["الصف الأول الابتدائي","الصف الثاني الابتدائي","الصف الثالث الابتدائي","الصف الرابع الابتدائي","الصف الخامس الابتدائي","الصف السادس الابتدائي","الصف الأول المتوسط"];
                foreach ($levels as $lvl) {
                    $sel = ($edit_row && $edit_row['المرحلة_الدراسية']==$lvl) ? "selected" : "";
                    echo "<option value=\"$lvl\" $sel>$lvl</option>";
                }
                ?>
            </select>

            <select name="الفصل" required>
                <option value="">اختر الفصل</option>
                <?php
                $classes = ["أ","ب","ج"];
                foreach ($classes as $c) {
                    $sel = ($edit_row && $edit_row['الفصل']==$c) ? "selected" : "";
                    echo "<option value=\"$c\" $sel>$c</option>";
                }
                ?>
            </select>

            <select name="الفترة" required>
                <option value="">اختر الفترة</option>
                <?php
                $shifts = ["صباحي","مسائي"];
                foreach ($shifts as $s) {
                    $sel = ($edit_row && $edit_row['الفترة']==$s) ? "selected" : "";
                    echo "<option value=\"$s\" $sel>$s</option>";
                }
                ?>
            </select>

            <input type="text" name="الجنسية" value="<?= $edit_row?htmlspecialchars($edit_row['الجنسية']):'' ?>" placeholder="الجنسية" required>
            <input type="text" name="رقم_الهوية" value="<?= $edit_row?htmlspecialchars($edit_row['رقم_الهوية']):'' ?>" placeholder="رقم الهوية" required>
            <input type="text" name="رقم_ولي_الأمر" value="<?= $edit_row?htmlspecialchars($edit_row['رقم_ولي_الأمر']):'' ?>" placeholder="رقم ولي الأمر" required>

            <?php if ($edit_row): ?>
                <button type="submit" name="update_student">
                    <span class="material-icons">save</span> تحديث بيانات الطالب
                </button>
            <?php else: ?>
                <button type="submit" name="add_student">
                    <span class="material-icons">add</span> إضافة طالب جديد
                </button>
            <?php endif; ?>
        </div>
    </form>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>اسم الطالب</th>
                <th>المرحلة الدراسية</th>
                <th>الفصل</th>
                <th>الفترة</th>
                <th>الجنسية</th>
                <th>رقم الهوية</th>
                <th>رقم ولي الأمر</th>
                <th>تاريخ الإضافة</th>
                <th>خيارات</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): $i = 1; while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td data-label="#:"><?= $i++ ?></td>
                    <td data-label="اسم الطالب:"><?= htmlspecialchars($row['اسم_الطالب']) ?></td>
                    <td data-label="المرحلة الدراسية:"><?= htmlspecialchars($row['المرحلة_الدراسية']) ?></td>
                    <td data-label="الفصل:"><?= htmlspecialchars($row['الفصل']) ?></td>
                    <td data-label="الفترة:"><?= htmlspecialchars($row['الفترة']) ?></td>
                    <td data-label="الجنسية:"><?= htmlspecialchars($row['الجنسية']) ?></td>
                    <td data-label="رقم الهوية:"><?= htmlspecialchars($row['رقم_الهوية']) ?></td>
                    <td data-label="رقم ولي الأمر:"><?= htmlspecialchars($row['رقم_ولي_الأمر']) ?></td>
                    <td data-label="تاريخ الإضافة:"><?= htmlspecialchars($row['تاريخ_الاضافة']) ?></td>
                    <td data-label="خيارات:">
                        <a href="?edit=<?= htmlspecialchars($row['رقم']) ?>">
                            <button title="تعديل">
                                <span class="material-icons">edit</span>
                            </button>
                        </a>
                        <a href="?delete=<?= htmlspecialchars($row['رقم']) ?>" onclick="return confirm('هل أنت متأكد من حذف هذا الطالب؟ لا يمكن التراجع عن هذا الإجراء!')">
                            <button class="delete-btn" title="حذف">
                                <span class="material-icons">delete</span>
                            </button>
                        </a>
                    </td>
                </tr>
            <?php endwhile; else: ?>
                <tr>
                    <td colspan="10">لا توجد طلاب مضافة حالياً أو لا تطابق نتائج البحث.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer-buttons">
        <a href="?export=excel" class="button-link">
            <span class="material-icons">download</span> تصدير Excel
        </a>
        <button onclick="window.print()">
            <span class="material-icons">print</span> طباعة القائمة
        </button>
        <form method="post" style="display:inline;">
            <input type="hidden" name="اسم_الطالب" value="">
            <input type="hidden" name="المرحلة_الدراسية" value="">
            <input type="hidden" name="الفصل" value="">
            <input type="hidden" name="الفترة" value="">
            <input type="hidden" name="الجنسية" value="">
            <input type="hidden" name="رقم_الهوية" value="">
            <input type="hidden" name="رقم_ولي_الأمر" value="">
            <button type="submit" name="add_student_and_back" class="button-link" style="background-color: #f0ad4e; display: none;">
                <span class="material-icons">save</span> حفظ والعودة للرئيسية
            </button>
        </form>
        <a href="dashboard.php" class="button-link" style="background-color: #f0ad4e;">
            <span class="material-icons">dashboard</span> العودة للرئيسية
        </a>
    </div>
</div>

<script>
    function closeAlert() {
        const msg = document.getElementById('success-msg');
        if (msg) {
            msg.style.opacity = 0;
            setTimeout(() => msg.remove(), 500); 
        }
    }
    setTimeout(closeAlert, 5000);
</script>

</body>
</html>