<?php
session_start();

// تفاصيل الاتصال بقاعدة البيانات
$host = "localhost";
$dbname = "aljood_center";
$username = "root";
$password = "";

// إنشاء الاتصال بقاعدة البيانات
$conn = new mysqli($host, $username, $password, $dbname);
$conn->set_charset("utf8");

// التحقق من الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

// --- تصدير إلى Excel ---
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=classes.xls");
    
    // جلب جميع الفصول مرتبة حسب 'رقم' تنازلياً
    $res = $conn->query("SELECT * FROM classes ORDER BY `رقم` DESC");
    
    // إخراج جدول HTML لملف Excel
    echo "<table border='1'>
                <tr>
                    <th>رقم</th>
                    <th>الفصل</th>
                    <th>المرحلة الدراسية</th>
                    <th>معرف المعلمة</th>
                    <th>اسم المعلمة</th>
                    <th>تاريخ الإضافة</th>
                </tr>";
    while ($r = $res->fetch_assoc()) {
        echo "<tr>
                    <td>{$r['رقم']}</td>
                    <td>{$r['الفصل']}</td>
                    <td>{$r['المرحلة_الدراسية']}</td>
                    <td>{$r['معرف_المعلمة']}</td>
                    <td>{$r['اسم_المعلمة']}</td>
                    <td>{$r['تاريخ_الإضافة']}</td>
                  </tr>";
    }
    echo "</table>";
    exit; // إيقاف تنفيذ السكريبت بعد التصدير
}

// --- إضافة أو تحديث فصل ---
if (isset($_POST['add_class']) || isset($_POST['update_class'])) {
    // تنظيف وجلب بيانات POST بأسماء الحقول الجديدة
    $class_name = htmlspecialchars($_POST['الفصل']); 
    $stage_name = htmlspecialchars($_POST['المرحلة_الدراسية']);
    $teacher_id = htmlspecialchars($_POST['معرف_المعلمة']); 
    $teacher_name = htmlspecialchars($_POST['اسم_المعلمة']); 

    if (isset($_POST['update_class'])) {
        // تحديث فصل موجود
        $id = intval($_POST['رقم']);
        // استعلام SQL يستخدم أسماء الأعمدة الجديدة بالكامل
        $stmt = $conn->prepare("UPDATE classes SET `الفصل`=?, `المرحلة_الدراسية`=?, `معرف_المعلمة`=?, `اسم_المعلمة`=? WHERE `رقم`=?"); 
        $stmt->bind_param("ssssi", $class_name, $stage_name, $teacher_id, $teacher_name, $id);
        $stmt->execute();
        header("Location: classes.php?saved=updated");
    } else {
        // إضافة فصل جديد
        // استعلام SQL يستخدم أسماء الأعمدة الجديدة بالكامل
        $stmt = $conn->prepare("INSERT INTO classes (`الفصل`, `المرحلة_الدراسية`, `معرف_المعلمة`, `اسم_المعلمة`, `تاريخ_الاضافة`) VALUES (?,?,?,?,CURDATE())"); 
        $stmt->bind_param("ssss", $class_name, $stage_name, $teacher_id, $teacher_name);
        $stmt->execute();

        header("Location: " . $_SERVER['PHP_SELF'] . "?saved=added");
    }
    exit; // إيقاف تنفيذ السكريبت بعد الإضافة/التحديث
}

// --- حذف فصل ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']); // التأكد من أن الرقم صحيح لأمان أفضل
    $conn->query("DELETE FROM classes WHERE `رقم` = $id");
    header("Location: classes.php?saved=deleted");
    exit; // إيقاف تنفيذ السكريبت بعد الحذف
}

// --- جلب البيانات للتعديل ---
$edit_row = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    // جلب الأعمدة بأسمائها الجديدة
    $res = $conn->query("SELECT * FROM classes WHERE `رقم` = $id");
    $edit_row = $res->fetch_assoc();
}

// --- منطق التصفية (الفلترة) ---
$where = "";
if (!empty($_GET['keyword'])) {
    $k = "%" . $conn->real_escape_string($_GET['keyword']) . "%";
    // التصفية بأسماء الأعمدة الجديدة بالكامل
    $where = "WHERE `الفصل` LIKE '$k' OR `المرحلة_الدراسية` LIKE '$k' OR `اسم_المعلمة` LIKE '$k' OR `معرف_المعلمة` LIKE '$k'"; 
}

// جلب الفصول بناءً على الفلتر
$result = $conn->query("SELECT * FROM classes $where ORDER BY `رقم` DESC");

// تعريف خيارات القوائم المنسدلة للنموذج
$class_options = ['أ', 'ب', 'ج'];
$stage_options = [
    'الصف الأول الابتدائي',
    'الصف الثاني الابتدائي',
    'الصف الثالث الابتدائي',
    'الصف الرابع الابتدائي',
    'الصف الخامس الابتدائي',
    'الصف السادس الابتدائي',
    'الصف الأول المتوسط',
    'الصف الثاني المتوسط',
    'الصف الثالث المتوسط'
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الفصول الدراسية</title>
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
            --spacing-small: 1rem;      /* 16px */
            --spacing-medium: 1.25rem; /* 20px */
            --spacing-large: 1.875rem; /* 30px */
            --spacing-xl: 3.125rem;    /* 50px */
        }

        /* الأساسيات لضمان امتداد الخلفية على كامل الصفحة */
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
            display: flex; /* Keep flexbox for overall alignment */
            flex-direction: column;
            align-items: center;
            min-height: 100vh; 
            /* Remove justify-content: center to allow content to flow naturally and scroll */
        }

        .container {
            width: 95%;
            max-width: 1200px;
            margin: var(--spacing-xl) auto; /* Keep margin for spacing */
            padding: var(--spacing-large);
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 8px 24px var(--shadow-light);
            position: relative;
            flex-grow: 1; /* Allow container to grow and fill available space */
            display: flex;
            flex-direction: column; /* Organize content within container vertically */
        }

        .logo {
            position: absolute;
            top: var(--spacing-medium);
            right: var(--spacing-medium); /* Adjusted for RTL */
            width: 100px;
            height: auto;
            opacity: 0.9;
            z-index: 20; /* Ensure logo stays on top */
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

        /* Table container for scrollability */
        .table-container {
            overflow-x: auto; /* Enable horizontal scroll if content overflows */
            overflow-y: auto; /* Enable vertical scroll if content overflows */
            max-height: 500px; /* Adjust as needed - makes table scroll vertically */
            margin-top: var(--spacing-large);
            border-radius: 10px;
            box-shadow: 0 4px 12px var(--shadow-light);
            background: var(--white);
        }

        table {
            width: 100%;
            border-collapse: separate; 
            border-spacing: 0;
            /* Removed margin-top and box-shadow from here as they are on .table-container */
            border-radius: 10px; 
            overflow: hidden; 
            min-width: 600px; /* Ensure table doesn't get too squished on smaller screens */
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

        /* Alert Messages */
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
            z-index: 10; /* Ensure alerts are on top */
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

        .close-btn {
            position: absolute;
            top: 10px;
            left: 15px; /* Adjusted for RTL */
            cursor: pointer;
            font-size: 1.5rem;
            line-height: 1;
            opacity: 0.7;
            transition: opacity 0.2s;
        }

        .close-btn:hover {
            opacity: 1;
        }

        /* Print Styles */
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
                opacity: 1; /* Ensure logo is visible on print */
            }
            h1 {
                text-align: right;
                margin-right: 150px; 
            }
            .filters, .form-section, .footer-buttons, .alert, td button {
                display: none;
            }
            .table-container { /* Hide scroll behavior for print */
                overflow: visible;
                max-height: unset;
                box-shadow: none;
                border-radius: 0;
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

        /* Responsive Adjustments */
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
            td:nth-of-type(2):before { content: "الفصل:"; } 
            td:nth-of-type(3):before { content: "المرحلة الدراسية:"; }
            td:nth-of-type(4):before { content: "معرف المعلمة:"; } 
            td:nth-of-type(5):before { content: "اسم المعلمة:"; }
            td:nth-of-type(6):before { content: "تاريخ الإضافة:"; }
            td:nth-of-type(7):before { content: "الخيارات:"; }
            
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
    <h1>إدارة الفصول الدراسية</h1>

    <?php if (isset($_GET['saved'])): ?>
        <div id="success-msg" class="alert <?= $_GET['saved'] === 'added' ? 'success' : ($_GET['saved'] === 'updated' ? 'success' : 'warning') ?>">
            <?= $_GET['saved'] === 'added' ? '✅ تم إضافة الفصل بنجاح.' :
                ($_GET['saved'] === 'updated' ? '✏️ تم تعديل بيانات الفصل بنجاح.' : '🗑️ تم حذف الفصل بنجاح.') ?>
            <span class="close-btn" onclick="closeAlert()">✖</span>
        </div>
    <?php endif; ?>

    <form method="get" class="filters">
        <input type="text" name="keyword" placeholder="ابحث بالفصل، المرحلة، أو اسم المعلمة..." value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
        <button type="submit">
            <span class="material-icons">search</span> بحث
        </button>
        <a href="classes.php" class="button-link" style="background-color: #6c757d;">
            <span class="material-icons">clear</span> إزالة الفلتر
        </a>
    </form>

    <form method="post">
        <div class="form-section">
            <?php if ($edit_row): ?>
                <input type="hidden" name="رقم" value="<?= htmlspecialchars($edit_row['رقم']) ?>">
            <?php endif; ?>
            
            <select name="الفصل" required>
                <option value="">اختر الفصل</option>
                <?php foreach ($class_options as $option): ?>
                    <option value="<?= htmlspecialchars($option) ?>" 
                        <?= ($edit_row && $edit_row['الفصل'] == $option) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($option) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="المرحلة_الدراسية" required>
                <option value="">اختر المرحلة الدراسية</option>
                <?php foreach ($stage_options as $option): ?>
                    <option value="<?= htmlspecialchars($option) ?>" 
                        <?= ($edit_row && $edit_row['المرحلة_الدراسية'] == $option) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($option) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <input type="text" name="معرف_المعلمة" placeholder="معرف المعلمة" value="<?= htmlspecialchars($edit_row['معرف_المعلمة'] ?? '') ?>" required> 
            <input type="text" name="اسم_المعلمة" placeholder="اسم المعلمة" value="<?= htmlspecialchars($edit_row['اسم_المعلمة'] ?? '') ?>" required>
            <?php if ($edit_row): ?>
                <button type="submit" name="update_class">
                    <span class="material-icons">save</span> تحديث بيانات الفصل
                </button>
            <?php else: ?>
                <button type="submit" name="add_class">
                    <span class="material-icons">add</span> إضافة فصل جديد
                </button>
            <?php endif; ?>
        </div>
    </form>

    <div class="table-container"> <table>
            <thead>
                <tr>
                    <th>رقم</th>
                    <th>الفصل</th>
                    <th>المرحلة الدراسية</th>
                    <th>معرف المعلمة</th>
                    <th>اسم المعلمة</th>
                    <th>تاريخ الإضافة</th>
                    <th>الخيارات</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td data-label="الرقم:"><?= htmlspecialchars($row['رقم']) ?></td>
                            <td data-label="الفصل:"><?= htmlspecialchars($row['الفصل']) ?></td>
                            <td data-label="المرحلة الدراسية:"><?= htmlspecialchars($row['المرحلة_الدراسية']) ?></td>
                            <td data-label="معرف المعلمة:"><?= htmlspecialchars($row['معرف_المعلمة']) ?></td> 
                            <td data-label="اسم المعلمة:"><?= htmlspecialchars($row['اسم_المعلمة']) ?></td>
                            <td data-label="تاريخ الإضافة:"><?= htmlspecialchars($row['تاريخ_الإضافة']) ?></td>
                            <td data-label="الخيارات:">
                                <a href="?edit=<?= htmlspecialchars($row['رقم']) ?>">
                                    <button title="تعديل">
                                        <span class="material-icons">edit</span>
                                    </button>
                                </a>
                                <a href="?delete=<?= htmlspecialchars($row['رقم']) ?>" onclick="return confirm('هل أنت متأكد من حذف هذا الفصل؟ لا يمكن التراجع عن هذا الإجراء!')">
                                    <button class="delete-btn" title="حذف">
                                        <span class="material-icons">delete</span>
                                    </button>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">لا توجد فصول مضافة حالياً أو لا تطابق نتائج البحث.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="footer-buttons">
        <a href="?export=excel" class="button-link">
            <span class="material-icons">download</span> تصدير Excel
        </a>
        <button onclick="window.print()">
            <span class="material-icons">print</span> طباعة القائمة
        </button>
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