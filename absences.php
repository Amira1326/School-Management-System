<?php
session_start(); // تأكد أن هذه هي أول سطر في ملف PHP

$host = "localhost";
$dbname = "aljood_center";
$username = "root";
$password = "";

$conn = new mysqli($host, $username, $password, $dbname);
$conn->set_charset("utf8");
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

// **الجزء الخاص بمعالجة حذف سجل غياب معين**
if (isset($_GET['delete_absence_id'])) {
    $absence_id_to_delete = (int)$_GET['delete_absence_id'];

    // 1. جلب معرف الطالب المرتبط بسجل الغياب قبل الحذف لتقليل عدد الغيابات
    // تم تغيير 'رقم' إلى 'id' هنا بناءً على بنية جدول student_absences المرفقة
    $stmt_get_student_id = $conn->prepare("SELECT `معرف_الطالب` FROM student_absences WHERE `id` = ?");
    $stmt_get_student_id->bind_param("i", $absence_id_to_delete);
    $stmt_get_student_id->execute();
    $stmt_get_student_id->bind_result($student_id_affected);
    $stmt_get_student_id->fetch();
    $stmt_get_student_id->close();

    if ($student_id_affected) {
        // 2. حذف سجل الغياب من جدول student_absences
        // تم تغيير 'رقم' إلى 'id' هنا بناءً على بنية جدول student_absences المرفقة
        $delete_stmt = $conn->prepare("DELETE FROM student_absences WHERE `id` = ?");
        $delete_stmt->bind_param("i", $absence_id_to_delete);
        $delete_stmt->execute();
        $delete_stmt->close();

        // 3. تقليل عدد الغيابات في جدول students
        $update_student_count_stmt = $conn->prepare("UPDATE students SET `عدد_الغيابات` = `عدد_الغيابات` - 1 WHERE `رقم` = ? AND `عدد_الغيابات` > 0");
        $update_student_count_stmt->bind_param("i", $student_id_affected);
        $update_student_count_stmt->execute();
        $update_student_count_stmt->close();

        // تخزين رسالة النجاح في الجلسة
        $_SESSION['message'] = [
            'type' => 'success',
            'text' => 'تم حذف سجل الغياب بنجاح!'
        ];

        // إعادة التوجيه للحفاظ على الفلاتر وإزالة معلمات GET الخاصة بالحذف
        $current_filters = http_build_query(array_diff_key($_GET, ['delete_absence_id' => '']));
        header("Location: " . $_SERVER['PHP_SELF'] . "?" . $current_filters);
        exit;
    } else {
        // تخزين رسالة الخطأ في الجلسة
        $_SESSION['message'] = [
            'type' => 'error',
            'text' => 'لم يتم العثور على سجل الغياب المطلوب حذفه.'
        ];
        $current_filters = http_build_query(array_diff_key($_GET, ['delete_absence_id' => '']));
        header("Location: " . $_SERVER['PHP_SELF'] . "?" . $current_filters);
        exit;
    }
}

// إضافة الغياب
if (isset($_POST['save_absence'])) {
    $date = $_POST['التاريخ'];
    $absence_records = $_POST['غياب_الطلاب'] ?? [];

    if ($date && !empty($absence_records)) {
        foreach ($absence_records as $record) {
            list($sid, $absence_type) = explode('_', $record);

            $check_stmt = $conn->prepare("SELECT COUNT(*) FROM student_absences WHERE `معرف_الطالب` = ? AND `التاريخ` = ? AND `نوع_الغياب` = ?");
            $check_stmt->bind_param("iss", $sid, $date, $absence_type);
            $check_stmt->execute();
            $check_stmt->bind_result($count);
            $check_stmt->fetch();
            $check_stmt->close();

            if ($count == 0) {
                $insert_stmt = $conn->prepare("INSERT INTO student_absences (`معرف_الطالب`, `التاريخ`, `نوع_الغياب`, `وقت_التسجيل`) VALUES (?, ?, ?, NOW())");
                $insert_stmt->bind_param("iss", $sid, $date, $absence_type);
                $insert_stmt->execute();
                $insert_stmt->close();

                // تحديث عدد الغيابات في جدول students
                $update_stmt = $conn->prepare("UPDATE students SET `عدد_الغيابات` = `عدد_الغيابات` + 1 WHERE `رقم` = ?");
                $update_stmt->bind_param("i", $sid);
                $update_stmt->execute();
                $update_stmt->close();
            }
        }
        // تخزين رسالة النجاح في الجلسة
        $_SESSION['message'] = [
            'type' => 'success',
            'text' => 'تم تسجيل الغياب بنجاح!'
        ];
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// مراحل وفصول
$levels = ["الصف الأول الابتدائي", "الصف الثاني الابتدائي", "الصف الثالث الابتدائي", "الصف الرابع الابتدائي", "الصف الخامس الابتدائي", "الصف السادس الابتدائي", "الصف الأول المتوسط"];
$classes = ["أ", "ب", "ج"];

// بناء جملة الفلترة للطلاب (لجدول تسجيل الغياب)
$where_students = "1";
if (!empty($_GET['level'])) {
    $lvl = $conn->real_escape_string($_GET['level']);
    $where_students .= " AND `المرحلة_الدراسية` = '$lvl'";
}
if (!empty($_GET['class'])) {
    $cls = $conn->real_escape_string($_GET['class']);
    $where_students .= " AND `الفصل` = '$cls'";
}

// إضافة منطق الفرز لجدول الطلاب
$orderBy_students = "ORDER BY `اسم_الطالب` ASC"; // الفرز الافتراضي
if (!empty($_GET['sort_by'])) {
    if ($_GET['sort_by'] === 'most_absent') {
        $orderBy_students = "ORDER BY `عدد_الغيابات` DESC";
    } elseif ($_GET['sort_by'] === 'least_absent') {
        $orderBy_students = "ORDER BY `عدد_الغيابات` ASC";
    }
}

// بناء الاستعلام الكامل للطلاب
$students_query = "SELECT *, `عدد_الغيابات` AS total_absences FROM students WHERE $where_students $orderBy_students";
$students = $conn->query($students_query);


// **الجزء الخاص بجلب الغيابات المسجلة لجدول الحذف**
$absences_where = "1";
if (!empty($_GET['absence_filter_date'])) {
    $filter_date = $conn->real_escape_string($_GET['absence_filter_date']);
    $absences_where .= " AND sa.`التاريخ` = '$filter_date'";
}
if (!empty($_GET['absence_filter_student_name'])) {
    $filter_name = $conn->real_escape_string($_GET['absence_filter_student_name']);
    $absences_where .= " AND s.`اسم_الطالب` LIKE '%$filter_name%'";
}

$absences_query = "
    SELECT
        sa.id AS absence_id,  /* تم التأكيد على استخدام 'id' هنا بناءً على بنية الجدول */
        s.اسم_الطالب,
        s.المرحلة_الدراسية,
        s.الفصل,
        sa.التاريخ,
        sa.نوع_الغياب,
        sa.وقت_التسجيل
    FROM student_absences sa
    JOIN students s ON sa.معرف_الطالب = s.رقم
    WHERE $absences_where
    ORDER BY sa.التاريخ DESC, sa.وقت_التسجيل DESC
";
$recorded_absences = $conn->query($absences_query);

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل غياب الطلاب</title>
    <link rel="icon" href="logo.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        :root {
            --primary-color: #89355d;
            --primary-color-light: #a34e7b;
            --secondary-color: #E0BBE4;
            --text-dark: #333;
            --white: #ffffff;
            --shadow-light: rgba(0, 0, 0, 0.08);
            --shadow-medium: rgba(0, 0, 0, 0.15);
            --border-radius-large: 12px;
            --border-radius-small: 8px;
            --border-radius-pill: 25px;
            --spacing-xs: 0.5rem;
            --spacing-small: 0.75rem;
            --spacing-medium: 1rem;
            --spacing-large: 1.5rem;
            --spacing-xl: 2rem;
            --spacing-xxl: 2.5rem;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(to bottom, var(--white) 0%, var(--secondary-color) 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            direction: rtl;
            color: var(--text-dark);
        }

        .container {
            width: 95%;
            max-width: 1400px; /* تم تكبير هذه القيمة */
            margin: var(--spacing-large) auto;
            padding: var(--spacing-xl);
            padding-bottom: calc(var(--spacing-xl) + 60px); /* إضافة مساحة لزر العودة بالأسفل */
            background: var(--white);
            border-radius: var(--border-radius-large);
            box-shadow: 0 8px 24px var(--shadow-light);
            position: relative; /* مهم للزر المطلق الموضع */
        }

        .logo {
            position: absolute;
            top: var(--spacing-medium);
            right: var(--spacing-medium);
            width: 100px;
            opacity: 0.8;
        }

        h1, h2 {
            text-align: center;
            color: var(--primary-color);
            margin-top: 0;
            margin-bottom: var(--spacing-xl);
            font-size: 2.2rem;
            font-weight: 700;
        }
        h2 {
            font-size: 1.8rem;
            margin-top: var(--spacing-xxl);
        }


        /* تنسيق جديد لنموذج الفلترة المنفصل */
        .filter-form-container, .recorded-absences-filter {
            background-color: #f9f9f9;
            border: 1px solid #eee;
            border-radius: var(--border-radius-large);
            padding: var(--spacing-large);
            margin-bottom: var(--spacing-xl);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
        }

        .filters, .recorded-absences-filters {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: var(--spacing-small);
            margin-bottom: var(--spacing-medium);
        }

        .form-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            align-items: center;
            gap: var(--spacing-medium);
            margin-bottom: var(--spacing-large);
        }

        .form-actions input[type="date"] {
            flex-grow: 1;
            min-width: 150px;
        }

        .form-actions button,
        .form-actions a {
            flex-shrink: 0;
        }

        input[type="date"],
        select,
        input[type="text"] { /* إضافة تنسيق لـ input text الجديد */
            padding: 12px 15px;
            border-radius: var(--border-radius-small);
            border: 1px solid #ddd;
            min-width: 180px;
            font-size: 1rem;
            color: var(--text-dark);
            background-color: var(--white);
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url('data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2020%2020%22%3E%3Cpath%20d%3D%22M9.293%2012.95l.707.707L15.657%208l-1.414-1.414L10%2010.828l-3.536-3.535L5.05%208l4.243%204.95z%22%20fill%3D%22%2389355d%22%2F%3E%3C%2Fsvg%3E'); /* سهم للـ select */
            background-repeat: no-repeat;
            background-position: left 10px center;
            background-size: 16px;
            cursor: pointer;
        }
        input[type="text"] {
            background-image: none; /* إزالة السهم من حقل النص */
            cursor: text;
        }


        input[type="date"]::-webkit-calendar-picker-indicator {
            margin-right: 10px;
            cursor: pointer;
        }

        button,
        .button {
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            padding: 12px 25px;
            border-radius: var(--border-radius-pill);
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 4px 8px var(--shadow-light);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }

        .delete-btn {
            background-color: #dc3545;
            padding: 8px 15px;
            font-size: 0.95rem;
            border-radius: var(--border-radius-small);
            display: inline-flex;
            align-items: center;
        }

        .delete-btn:hover {
            background-color: #c82333;
        }

        /* تنسيق زر العودة للرئيسية في الأسفل واليسار */
        .back-to-home-btn {
            background-color: #6c757d;
            padding: 8px 18px; /* تصغير المسافة الداخلية */
            font-size: 0.9rem; /* تصغير حجم الخط */
            border-radius: var(--border-radius-small); /* شكل مستطيل أكثر */
            box-shadow: none; /* إزالة الظل الكبير */
        }
        .back-to-home-btn:hover {
            background-color: #5a6268;
            transform: translateY(0); /* لا حركة عند التحويم */
            box-shadow: none;
        }

        /* موضع زر العودة للرئيسية */
        .bottom-nav-container {
            position: absolute; /* تحديد الموضع المطلق */
            bottom: var(--spacing-xl); /* المسافة من الأسفل */
            left: var(--spacing-xl); /* المسافة من اليسار */
            z-index: 20; /* لضمان ظهوره فوق العناصر الأخرى */
        }


        button:hover,
        .button:hover {
            background-color: var(--primary-color-light);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px var(--shadow-medium);
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: var(--spacing-medium);
            background: var(--white);
            border-radius: var(--border-radius-large);
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        th,
        td {
            border: none;
            padding: 12px 10px;
            text-align: center;
            vertical-align: middle;
        }

        th {
            background: var(--primary-color);
            color: var(--white);
            font-weight: 700;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        th:first-child {
            border-top-right-radius: var(--border-radius-large);
        }

        th:last-child {
            border-top-left-radius: var(--border-radius-large);
        }

        tr:nth-child(even) {
            background-color: #fcf6f9;
        }

        tr:hover {
            background-color: #f7eaf0;
        }

        td {
            border-bottom: 1px solid #eee;
            border-left: 1px solid #eee;
        }

        td:last-child {
            border-left: none;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .success, .error-message {
            color: var(--primary-color);
            background-color: #e6ffe6;
            border: 1px solid #ccebcc;
            padding: var(--spacing-small);
            border-radius: var(--border-radius-small);
            text-align: center;
            margin: var(--spacing-medium) 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        .success .material-icons, .error-message .material-icons {
            font-size: 20px;
        }

        .absence-options {
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .absence-options label {
            display: flex;
            align-items: center;
            cursor: pointer;
            font-size: 0.95rem;
            color: var(--text-dark);
            user-select: none;
        }

        .absence-options input[type="radio"] {
            position: absolute;
            opacity: 0;
            pointer-events: none;
            width: 0;
            height: 0;
        }

        .absence-options input[type="radio"] + span {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid var(--primary-color-light);
            border-radius: 50%;
            margin-left: 8px;
            position: relative;
            transition: all 0.2s ease-in-out;
        }

        .absence-options input[type="radio"]:checked + span {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .absence-options input[type="radio"]:checked + span::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: var(--white);
        }

        .absence-options label:hover input[type="radio"] + span {
            box-shadow: 0 0 0 4px rgba(137, 53, 93, 0.2);
        }

        hr {
            border: none;
            border-top: 1px solid #eee;
            margin: var(--spacing-xl) 0;
        }

        /* تحسينات للشاشات الصغيرة */
        @media (max-width: 768px) {
            .container {
                padding: var(--spacing-large);
                margin: var(--spacing-small) auto;
                padding-bottom: calc(var(--spacing-large) + 50px); /* مسافة للزر في الأسفل على الشاشات الصغيرة */
            }

            .logo {
                position: static;
                margin: 0 auto var(--spacing-medium);
                display: block;
                width: 80px;
            }

            h1, h2 {
                font-size: 1.8rem;
                margin-bottom: var(--spacing-large);
            }

            .filter-form-container, .recorded-absences-filter {
                padding: var(--spacing-medium);
            }

            .filters,
            .form-actions,
            .recorded-absences-filters {
                flex-direction: column;
                align-items: stretch;
            }

            input[type="date"],
            select,
            input[type="text"],
            button,
            .button {
                min-width: unset;
                width: 100%;
                box-sizing: border-box;
            }

            /* موضع زر العودة للرئيسية على الشاشات الصغيرة */
            .bottom-nav-container {
                position: static; /* إزالة الموضع المطلق */
                margin-top: var(--spacing-large); /* إضافة مسافة من الأعلى */
                text-align: center; /* توسيط الزر */
            }

            .bottom-nav-container .button {
                width: auto; /* السماح للزر بأخذ عرضه الطبيعي */
                display: inline-flex; /* لكي يكون الزر في المنتصف بشكل أفضل */
                padding: 10px 20px;
                font-size: 0.9rem;
            }

            table,
            thead,
            tbody,
            th,
            td,
            tr {
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
                border-radius: var(--border-radius-small);
                overflow: hidden;
            }

            td {
                border: none;
                position: relative;
                padding-left: 50%;
                text-align: right;
                border-bottom: 1px solid #eee;
            }

            td:last-child {
                border-bottom: none;
            }

            tr:last-child td {
                border-bottom: none;
            }

            td::before {
                content: attr(data-label);
                position: absolute;
                right: 10px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                font-weight: 600;
                color: var(--primary-color);
                text-align: right;
            }

            /* Labels for the main student table */
            .main-table td:nth-of-type(1)::before { content: "اسم الطالب"; }
            .main-table td:nth-of-type(2)::before { content: "المرحلة الدراسية"; }
            .main-table td:nth-of-type(3)::before { content: "الفصل"; }
            .main-table td:nth-of-type(4)::before { content: "نوع الغياب"; }
            .main-table td:nth-of-type(5)::before { content: "إجمالي الغيابات"; }

            /* Labels for the recorded absences table */
            .recorded-absences-table td:nth-of-type(1)::before { content: "اسم الطالب"; }
            .recorded-absences-table td:nth-of-type(2)::before { content: "المرحلة الدراسية"; }
            .recorded-absences-table td:nth-of-type(3)::before { content: "الفصل"; }
            .recorded-absences-table td:nth-of-type(4)::before { content: "التاريخ"; }
            .recorded-absences-table td:nth-of-type(5)::before { content: "النوع"; }
            .recorded-absences-table td:nth-of-type(6)::before { content: "وقت التسجيل"; }
            .recorded-absences-table td:nth-of-type(7)::before { content: "إجراءات"; }
        }
    </style>
</head>

<body>

    <div class="container">
        <img src="logo.png" class="logo" alt="شعار المركز">
        <h1>تسجيل غياب الطلاب</h1>

        <?php
        // عرض الرسائل من الجلسة ثم إزالتها
        if (isset($_SESSION['message'])) {
            $message_type = $_SESSION['message']['type'];
            $message_text = $_SESSION['message']['text'];
            echo "<p class=\"$message_type\">";
            echo "<span class=\"material-icons\">" . ($message_type == 'success' ? 'check_circle' : 'error') . "</span> $message_text";
            echo "</p>";
            unset($_SESSION['message']); // إزالة الرسالة بعد عرضها
        }
        ?>

        <div class="filter-form-container">
            <form method="get" class="filters">
                <select name="level">
                    <option value="">اختر المرحلة الدراسية</option>
                    <?php foreach ($levels as $lvl): ?>
                        <option value="<?= htmlspecialchars($lvl) ?>" <?= (isset($_GET['level']) && $_GET['level'] == $lvl) ? 'selected' : '' ?>><?= htmlspecialchars($lvl) ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="class">
                    <option value="">اختر الفصل</option>
                    <?php foreach ($classes as $cls): ?>
                        <option value="<?= htmlspecialchars($cls) ?>" <?= (isset($_GET['class']) && $_GET['class'] == $cls) ? 'selected' : '' ?>><?= htmlspecialchars($cls) ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="sort_by">
                    <option value="">فرز حسب الغياب</option>
                    <option value="most_absent" <?= (isset($_GET['sort_by']) && $_GET['sort_by'] == 'most_absent') ? 'selected' : '' ?>>الأكثر غياباً</option>
                    <option value="least_absent" <?= (isset($_GET['sort_by']) && $_GET['sort_by'] == 'least_absent') ? 'selected' : '' ?>>الأقل غياباً</option>
                </select>

                <button type="submit" class="button">
                    <span class="material-icons">filter_list</span> تطبيق الفلاتر
                </button>
            </form>
        </div>

        <form method="post" onsubmit="return validateAbsenceSelection();">
            <div class="form-actions">
                <input type="date" name="التاريخ" required value="<?= htmlspecialchars($_POST['التاريخ'] ?? date('Y-m-d')) ?>">

                <button type="submit" name="save_absence">
                    <span class="material-icons">save</span> حفظ الغياب
                </button>
            </div>

            <table class="main-table">
                <thead>
                    <tr>
                        <th>اسم الطالب</th>
                        <th>المرحلة الدراسية</th>
                        <th>الفصل</th>
                        <th>نوع الغياب</th>
                        <th>إجمالي الغيابات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($students->num_rows):
                        while ($row = $students->fetch_assoc()): ?>
                            <tr>
                                <td data-label="اسم الطالب"><?= htmlspecialchars($row['اسم_الطالب']) ?></td>
                                <td data-label="المرحلة الدراسية"><?= htmlspecialchars($row['المرحلة_الدراسية']) ?></td>
                                <td data-label="الفصل"><?= htmlspecialchars($row['الفصل']) ?></td>
                                <td data-label="نوع الغياب">
                                    <div class="absence-options">
                                        <label>
                                            <input type="radio" name="غياب_الطلاب[<?= htmlspecialchars($row['رقم']) ?>]"
                                                value="<?= htmlspecialchars($row['رقم']) ?>_بعذر">
                                            <span></span> بعذر
                                        </label>
                                        <label>
                                            <input type="radio" name="غياب_الطلاب[<?= htmlspecialchars($row['رقم']) ?>]"
                                                value="<?= htmlspecialchars($row['رقم']) ?>_بدون عذر">
                                            <span></span> بدون عذر
                                        </label>
                                    </div>
                                </td>
                                <td data-label="إجمالي الغيابات"><?= htmlspecialchars($row['total_absences']) ?></td>
                            </tr>
                        <?php endwhile;
                    else: ?>
                        <tr>
                            <td colspan="5">لا توجد نتائج لعرضها. يرجى اختيار المرحلة والفصل.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>

        <hr>

        <h2>إدارة الغيابات المسجلة</h2>
        <div class="recorded-absences-filter">
            <form method="get" class="recorded-absences-filters">
                <input type="date" name="absence_filter_date" value="<?= htmlspecialchars($_GET['absence_filter_date'] ?? '') ?>">
                <input type="text" name="absence_filter_student_name" placeholder="ابحث باسم الطالب" value="<?= htmlspecialchars($_GET['absence_filter_student_name'] ?? '') ?>">
                <button type="submit" class="button">
                    <span class="material-icons">search</span> بحث الغيابات
                </button>
                <a href="<?= $_SERVER['PHP_SELF'] ?>" class="button back-to-home-btn">
                    <span class="material-icons">refresh</span> مسح الفلاتر
                </a>
            </form>
        </div>

        <table class="recorded-absences-table">
            <thead>
                <tr>
                    <th>اسم الطالب</th>
                    <th>المرحلة الدراسية</th>
                    <th>الفصل</th>
                    <th>التاريخ</th>
                    <th>النوع</th>
                    <th>وقت التسجيل</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($recorded_absences->num_rows):
                    while ($absence_row = $recorded_absences->fetch_assoc()): ?>
                        <tr>
                            <td data-label="اسم الطالب"><?= htmlspecialchars($absence_row['اسم_الطالب']) ?></td>
                            <td data-label="المرحلة الدراسية"><?= htmlspecialchars($absence_row['المرحلة_الدراسية']) ?></td>
                            <td data-label="الفصل"><?= htmlspecialchars($absence_row['الفصل']) ?></td>
                            <td data-label="التاريخ"><?= htmlspecialchars($absence_row['التاريخ']) ?></td>
                            <td data-label="النوع"><?= htmlspecialchars($absence_row['نوع_الغياب']) ?></td>
                            <td data-label="وقت التسجيل"><?= htmlspecialchars($absence_row['وقت_التسجيل']) ?></td>
                            <td data-label="إجراءات">
                                <?php
                                // نحافظ على فلاتر جدول الغيابات عند حذف سجل معين
                                $current_filters_for_delete = http_build_query(array_diff_key($_GET, ['delete_absence_id' => '']));
                                // إضافة فلاتر الغيابات الحالية لضمان بقائها بعد الحذف
                                if (!empty($_GET['absence_filter_date'])) {
                                    $current_filters_for_delete .= '&absence_filter_date=' . urlencode($_GET['absence_filter_date']);
                                }
                                if (!empty($_GET['absence_filter_student_name'])) {
                                    $current_filters_for_delete .= '&absence_filter_student_name=' . urlencode($_GET['absence_filter_student_name']);
                                }
                                ?>
                                <a href="?delete_absence_id=<?= htmlspecialchars($absence_row['absence_id']) ?>&<?= $current_filters_for_delete ?>"
                                   class="button delete-btn"
                                   onclick="return confirm('هل أنت متأكد من حذف سجل الغياب هذا؟\nالطالب: <?= htmlspecialchars($absence_row['اسم_الطالب']) ?>\nالتاريخ: <?= htmlspecialchars($absence_row['التاريخ']) ?>\nالنوع: <?= htmlspecialchars($absence_row['نوع_الغياب']) ?>');">
                                    <span class="material-icons" style="font-size: 1.1rem;">delete</span> حذف
                                </a>
                            </td>
                        </tr>
                    <?php endwhile;
                else: ?>
                    <tr>
                        <td colspan="7">لا توجد غيابات مسجلة لعرضها.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="bottom-nav-container">
            <a href="dashboard.php" class="button back-to-home-btn">
                <span class="material-icons">home</span> العودة للرئيسية
            </a>
        </div>
    </div>

    <script>
        function validateAbsenceSelection() {
            const dateInput = document.querySelector('input[name="التاريخ"]');
            if (!dateInput.value) {
                alert("الرجاء اختيار تاريخ الغياب.");
                return false;
            }

            const radioButtons = document.querySelectorAll('input[type="radio"][name^="غياب_الطلاب"]');
            let anyStudentSelected = false;
            const studentIds = new Set();

            radioButtons.forEach(radio => {
                if (radio.checked) {
                    const studentIdMatch = radio.name.match(/\[(.*?)\]/);
                    if (studentIdMatch && studentIdMatch[1]) {
                        studentIds.add(studentIdMatch[1]);
                    }
                }
            });

            if (studentIds.size === 0) {
                alert("الرجاء اختيار نوع الغياب (بعذر أو بدون عذر) لطالب واحد على الأقل.");
                return false;
            }
            return true;
        }
    </script>

</body>

</html>