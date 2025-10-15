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

// معالجة حذف سجل غياب معلم معين
if (isset($_GET['delete_absence_id'])) {
    $absence_id_to_delete = (int)$_GET['delete_absence_id'];
    $stmt_get_teacher_id = $conn->prepare("SELECT `معرف_المعلمة` FROM teacher_absences WHERE `id` = ?");
    $stmt_get_teacher_id->bind_param("i", $absence_id_to_delete);
    $stmt_get_teacher_id->execute();
    $stmt_get_teacher_id->bind_result($teacher_id_affected);
    $stmt_get_teacher_id->fetch();
    $stmt_get_teacher_id->close();

    if ($teacher_id_affected) {
        $delete_stmt = $conn->prepare("DELETE FROM teacher_absences WHERE `id` = ?");
        $delete_stmt->bind_param("i", $absence_id_to_delete);
        $delete_stmt->execute();
        $delete_stmt->close();

        $update_teacher_count_stmt = $conn->prepare("UPDATE teachers SET `إجمالي_الغيابات` = `إجمالي_الغيابات` - 1 WHERE `id` = ? AND `إجمالي_الغيابات` > 0");
        $update_teacher_count_stmt->bind_param("i", $teacher_id_affected);
        $update_teacher_count_stmt->execute();
        $update_teacher_count_stmt->close();

        $_SESSION['message'] = [
            'type' => 'success',
            'text' => 'تم حذف سجل غياب المعلم بنجاح!'
        ];
        $current_filters = http_build_query(array_diff_key($_GET, ['delete_absence_id' => '']));
        header("Location: " . $_SERVER['PHP_SELF'] . "?" . $current_filters);
        exit;
    } else {
        $_SESSION['message'] = [
            'type' => 'error',
            'text' => 'لم يتم العثور على سجل غياب المعلم المطلوب حذفه.'
        ];
        $current_filters = http_build_query(array_diff_key($_GET, ['delete_absence_id' => '']));
        header("Location: " . $_SERVER['PHP_SELF'] . "?" . $current_filters);
        exit;
    }
}

// معالجة حذف سجل تأخر معلم معين
if (isset($_GET['delete_lateness_id'])) {
    $lateness_id_to_delete = (int)$_GET['delete_lateness_id'];
    $stmt_get_teacher_id_lateness = $conn->prepare("SELECT `معرف_المعلمة` FROM teacher_latenesses WHERE `id` = ?");
    $stmt_get_teacher_id_lateness->bind_param("i", $lateness_id_to_delete);
    $stmt_get_teacher_id_lateness->execute();
    $stmt_get_teacher_id_lateness->bind_result($teacher_id_affected_lateness);
    $stmt_get_teacher_id_lateness->fetch();
    $stmt_get_teacher_id_lateness->close();

    if ($teacher_id_affected_lateness) {
        $delete_lateness_stmt = $conn->prepare("DELETE FROM teacher_latenesses WHERE `id` = ?");
        $delete_lateness_stmt->bind_param("i", $lateness_id_to_delete);
        $delete_lateness_stmt->execute();
        $delete_lateness_stmt->close();

        $update_teacher_lateness_count_stmt = $conn->prepare("UPDATE teachers SET `إجمالي_التأخيرات` = `إجمالي_التأخيرات` - 1 WHERE `id` = ? AND `إجمالي_التأخيرات` > 0");
        $update_teacher_lateness_count_stmt->bind_param("i", $teacher_id_affected_lateness);
        $update_teacher_lateness_count_stmt->execute();
        $update_teacher_lateness_count_stmt->close();

        $_SESSION['message'] = [
            'type' => 'success',
            'text' => 'تم حذف سجل تأخر المعلم بنجاح!'
        ];
        $current_filters = http_build_query(array_diff_key($_GET, ['delete_lateness_id' => '']));
        header("Location: " . $_SERVER['PHP_SELF'] . "?" . $current_filters);
        exit;
    } else {
        $_SESSION['message'] = [
            'type' => 'error',
            'text' => 'لم يتم العثور على سجل تأخر المعلم المطلوب حذفه.'
        ];
        $current_filters = http_build_query(array_diff_key($_GET, ['delete_lateness_id' => '']));
        header("Location: " . $_SERVER['PHP_SELF'] . "?" . $current_filters);
        exit;
    }
}

// إضافة غياب المعلم
if (isset($_POST['save_absence'])) {
    $date = $_POST['absence_date'];
    $absence_records = $_POST['teacher_absences'] ?? [];

    if ($date && !empty($absence_records)) {
        $any_saved = false;
        foreach ($absence_records as $record) {
            list($tid, $absence_type) = explode('_', $record);

            $check_stmt = $conn->prepare("SELECT COUNT(*) FROM teacher_absences WHERE `معرف_المعلمة` = ? AND `التاريخ` = ? AND `نوع_الغياب` = ?");
            $check_stmt->bind_param("iss", $tid, $date, $absence_type);
            $check_stmt->execute();
            $check_stmt->bind_result($count);
            $check_stmt->fetch();
            $check_stmt->close();

            if ($count == 0) {
                $insert_stmt = $conn->prepare("INSERT INTO teacher_absences (`معرف_المعلمة`, `التاريخ`, `نوع_الغياب`, `وقت_التسجيل`) VALUES (?, ?, ?, NOW())");
                $insert_stmt->bind_param("iss", $tid, $date, $absence_type);
                if ($insert_stmt->execute()) {
                    $any_saved = true;
                    $update_stmt = $conn->prepare("UPDATE teachers SET `إجمالي_الغيابات` = `إجمالي_الغيابات` + 1 WHERE `id` = ?");
                    $update_stmt->bind_param("i", $tid);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
                $insert_stmt->close();
            }
        }
        if ($any_saved) {
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'تم تسجيل غياب المعلمين بنجاح!'
            ];
        } else {
            $_SESSION['message'] = [
                'type' => 'info',
                'text' => 'لم يتم تسجيل أي غياب جديد. قد تكون السجلات موجودة بالفعل.'
            ];
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $_SESSION['message'] = [
            'type' => 'error',
            'text' => 'الرجاء اختيار تاريخ الغياب وتحديد معلم واحد على الأقل.'
        ];
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// إضافة تأخر المعلم
if (isset($_POST['save_lateness'])) {
    $lateness_date = $_POST['lateness_date'];
    $lateness_records = $_POST['teacher_latenesses_data'] ?? [];

    if ($lateness_date && !empty($lateness_records)) {
        $any_saved = false;
        $decoded_lateness_records = json_decode($lateness_records[0], true);
        if (is_array($decoded_lateness_records)) {
            foreach ($decoded_lateness_records as $record_string) {
                list($tid, $lateness_time, $lateness_duration, $lateness_reason) = explode('|', $record_string, 4);

                if (!preg_match("/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/", $lateness_time)) {
                    $_SESSION['message'] = [
                        'type' => 'error',
                        'text' => 'تنسيق وقت التأخير غير صحيح. يرجى استخدام HH:MM.'
                    ];
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;
                }
                if (!is_numeric($lateness_duration) || $lateness_duration <= 0) {
                    $_SESSION['message'] = [
                        'type' => 'error',
                        'text' => 'مدة التأخير يجب أن تكون رقماً صحيحاً وموجباً.'
                    ];
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;
                }

                $check_lateness_stmt = $conn->prepare("SELECT COUNT(*) FROM teacher_latenesses WHERE `معرف_المعلمة` = ? AND `التاريخ` = ? AND `وقت_التأخير` = ?");
                $check_lateness_stmt->bind_param("iss", $tid, $lateness_date, $lateness_time);
                $check_lateness_stmt->execute();
                $check_lateness_stmt->bind_result($lateness_count);
                $check_lateness_stmt->fetch();
                $check_lateness_stmt->close();

                if ($lateness_count == 0) {
                    $insert_lateness_stmt = $conn->prepare("INSERT INTO teacher_latenesses (`معرف_المعلمة`, `التاريخ`, `وقت_التأخير`, `مدة_التأخير_بالدقائق`, `السبب`, `وقت_التسجيل`) VALUES (?, ?, ?, ?, ?, NOW())");
                    $insert_lateness_stmt->bind_param("isiss", $tid, $lateness_date, $lateness_time, $lateness_duration, $lateness_reason);
                    if ($insert_lateness_stmt->execute()) {
                        $any_saved = true;
                        $update_lateness_stmt = $conn->prepare("UPDATE teachers SET `إجمالي_التأخيرات` = `إجمالي_التأخيرات` + 1 WHERE `id` = ?");
                        $update_lateness_stmt->bind_param("i", $tid);
                        $update_lateness_stmt->execute();
                        $update_lateness_stmt->close();
                    }
                    $insert_lateness_stmt->close();
                }
            }
        }

        if ($any_saved) {
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'تم تسجيل تأخر المعلمين بنجاح!'
            ];
        } else {
            $_SESSION['message'] = [
                'type' => 'info',
                'text' => 'لم يتم تسجيل أي تأخر جديد. قد تكون السجلات موجودة بالفعل.'
            ];
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $_SESSION['message'] = [
            'type' => 'error',
            'text' => 'الرجاء اختيار تاريخ التأخر وإدخال وقت ومدة التأخر لمعلمة واحدة على الأقل.'
        ];
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// بناء جملة الفلترة للمعلمين
$where_teachers = "1";
if (!empty($_GET['teacher_id'])) {
    $teacher_filter_id = (int)$_GET['teacher_id'];
    $where_teachers .= " AND `id` = $teacher_filter_id";
}

// إضافة منطق الفرز للمعلمين
$orderBy_teachers = "ORDER BY `اسم_المعلمة` ASC";
if (!empty($_GET['sort_by'])) {
    if ($_GET['sort_by'] === 'most_absent') {
        $orderBy_teachers = "ORDER BY `إجمالي_الغيابات` DESC";
    } elseif ($_GET['sort_by'] === 'least_absent') {
        $orderBy_teachers = "ORDER BY `إجمالي_الغيابات` ASC";
    } elseif ($_GET['sort_by'] === 'most_latenesses') {
        $orderBy_teachers = "ORDER BY `إجمالي_التأخيرات` DESC";
    } elseif ($_GET['sort_by'] === 'least_latenesses') {
        $orderBy_teachers = "ORDER BY `إجمالي_التأخيرات` ASC";
    }
}

// بناء الاستعلام الكامل للمعلمين
$teachers_query = "SELECT `id`, `اسم_المعلمة`, `المادة`, `إجمالي_الغيابات`, `إجمالي_التأخيرات` FROM teachers WHERE $where_teachers $orderBy_teachers";
$teachers = $conn->query($teachers_query);

// جلب الغيابات المسجلة للمعلمين
$absences_where = "1";
if (!empty($_GET['absence_filter_date'])) {
    $filter_date = $conn->real_escape_string($_GET['absence_filter_date']);
    $absences_where .= " AND ta.`التاريخ` = '$filter_date'";
}
if (!empty($_GET['absence_filter_teacher_name'])) {
    $filter_name = $conn->real_escape_string($_GET['absence_filter_teacher_name']);
    $absences_where .= " AND t.`اسم_المعلمة` LIKE '%$filter_name%'";
}

$recorded_absences_query = "
    SELECT
        ta.id AS absence_id,
        t.اسم_المعلمة,
        t.المادة,
        ta.التاريخ,
        ta.نوع_الغياب,
        ta.وقت_التسجيل
    FROM teacher_absences ta
    JOIN teachers t ON ta.معرف_المعلمة = t.id
    WHERE $absences_where
    ORDER BY ta.التاريخ DESC, ta.وقت_التسجيل DESC
";
$recorded_teacher_absences = $conn->query($recorded_absences_query);

// جلب التأخرات المسجلة للمعلمين
$latenesses_where = "1";
if (!empty($_GET['lateness_filter_date'])) {
    $lateness_filter_date = $conn->real_escape_string($_GET['lateness_filter_date']);
    $latenesses_where .= " AND tl.`التاريخ` = '$lateness_filter_date'";
}
if (!empty($_GET['lateness_filter_teacher_name'])) {
    $lateness_filter_name = $conn->real_escape_string($_GET['lateness_filter_teacher_name']);
    $latenesses_where .= " AND t.`اسم_المعلمة` LIKE '%$lateness_filter_name%'";
}

$recorded_latenesses_query = "
    SELECT
        tl.id AS lateness_id,
        t.اسم_المعلمة,
        t.المادة,
        tl.التاريخ,
        tl.وقت_التأخير,
        tl.مدة_التأخير_بالدقائق,
        tl.السبب,
        tl.وقت_التسجيل
    FROM teacher_latenesses tl
    JOIN teachers t ON tl.معرف_المعلمة = t.id
    WHERE $latenesses_where
    ORDER BY tl.التاريخ DESC, tl.وقت_التسجيل DESC
";
$recorded_teacher_latenesses = $conn->query($recorded_latenesses_query);

// جلب قائمة المعلمين لفلترة المعلمين
$all_teachers_query = "SELECT `id`, `اسم_المعلمة` FROM teachers ORDER BY `اسم_المعلمة` ASC";
$all_teachers_result = $conn->query($all_teachers_query);
$all_teachers = [];
if ($all_teachers_result) {
    while ($row = $all_teachers_result->fetch_assoc()) {
        $all_teachers[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل غياب وتأخر المعلمين - مركز الجود التعليمي</title>
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
            max-width: 1400px;
            margin: var(--spacing-large) auto;
            padding: var(--spacing-xl);
            padding-bottom: calc(var(--spacing-xl) + 60px);
            background: var(--white);
            border-radius: var(--border-radius-large);
            box-shadow: 0 8px 24px var(--shadow-light);
            position: relative;
        }

        .logo {
            position: absolute;
            top: var(--spacing-medium);
            right: var(--spacing-medium);
            width: 100px;
            opacity: 0.8;
        }

        h1 {
            text-align: center;
            color: var(--primary-color);
            margin-top: 0;
            margin-bottom: var(--spacing-xl);
            font-size: 2.2rem;
            font-weight: 700;
        }

        h2 {
            text-align: center;
            color: var(--primary-color);
            margin-top: var(--spacing-xxl);
            margin-bottom: var(--spacing-large);
            font-size: 1.8rem;
            font-weight: 700;
            border-bottom: 2px solid var(--primary-color-light);
            padding-bottom: 10px;
            display: inline-block;
            width: fit-content;
            margin-right: auto;
            margin-left: auto;
        }

        h3 {
            color: var(--primary-color);
            margin-top: var(--spacing-xl);
            margin-bottom: var(--spacing-medium);
            font-size: 1.5rem;
            font-weight: 700;
            text-align: center;
        }

        .section-card {
            background-color: #f9f9f9;
            border: 1px solid #eee;
            border-radius: var(--border-radius-large);
            padding: var(--spacing-large);
            margin-bottom: var(--spacing-xl);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
        }

        .filters, .form-actions, .filter-buttons {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: var(--spacing-small);
            margin-bottom: var(--spacing-medium);
        }

        .form-actions {
            justify-content: flex-end;
            margin-bottom: var(--spacing-large);
            gap: var(--spacing-medium);
        }

        input[type="date"],
        input[type="time"],
        input[type="number"],
        select,
        input[type="text"] {
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

        .back-to-home-btn {
            background-color: #6c757d;
            padding: 8px 18px;
            font-size: 0.9rem;
            border-radius: var(--border-radius-small);
            box-shadow: none;
        }

        .bottom-nav-container {
            position: absolute;
            bottom: var(--spacing-xl);
            left: var(--spacing-xl);
            z-index: 20;
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

        tr:nth-child(even) {
            background-color: #fcf6f9;
        }

        tr:hover {
            background-color: #f7eaf0;
        }

        .success, .error-message, .info-message {
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
        .info-message {
            color: #007bff;
            background-color: #e7f3ff;
            border-color: #b8daff;
        }

        .absence-options, .lateness-inputs {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
            padding: 5px 0;
        }

        .absence-options label {
            display: flex;
            align-items: center;
            cursor: pointer;
            font-size: 0.95rem;
            color: var(--text-dark);
            user-select: none;
        }

        .lateness-inputs input {
            width: 100%;
            box-sizing: border-box;
        }

        hr {
            border: none;
            border-top: 1px solid #eee;
            margin: var(--spacing-xl) 0;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                padding: var(--spacing-large);
                margin: var(--spacing-small) auto;
                padding-bottom: calc(var(--spacing-large) + 50px);
            }

            .logo {
                position: static;
                margin: 0 auto var(--spacing-medium);
                display: block;
                width: 80px;
            }

            h1, h2, h3 {
                font-size: 1.8rem;
                margin-bottom: var(--spacing-large);
                display: block;
                width: 100%;
                padding-bottom: 5px;
            }

            .section-card {
                padding: var(--spacing-medium);
            }

            .filters,
            .form-actions,
            .filter-buttons {
                flex-direction: column;
                align-items: stretch;
            }

            input[type="date"],
            select,
            input[type="text"],
            input[type="time"],
            input[type="number"],
            button,
            .button {
                min-width: unset;
                width: 100%;
                box-sizing: border-box;
            }

            .bottom-nav-container {
                position: static;
                margin-top: var(--spacing-large);
                text-align: center;
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
        }
    </style>
</head>

<body>

    <div class="container">
        <img src="logo.png" class="logo" alt="شعار المركز">
        <h1>إدارة غياب وتأخر المعلمين</h1>

        <h2>اختيار نوع السجل</h2>
        <div class="section-card">
            <form id="recordTypeForm">
                <select id="recordType" onchange="toggleForm()">
                    <option value="">اختر نوع السجل</option>
                    <option value="absence">غياب</option>
                    <option value="lateness">تأخير</option>
                </select>
            </form>
        </div>

        <div id="absenceForm" style="display:none;">
            <h2>تسجيل الغياب الجديد</h2>
            <form method="post" name="absence_form" onsubmit="return validateAbsenceSelection();">
                <div class="form-actions">
                    <input type="date" name="absence_date" required value="<?= htmlspecialchars($_POST['absence_date'] ?? date('Y-m-d')) ?>">
                    <button type="submit" name="save_absence">
                        <span class="material-icons">save</span> حفظ الغياب
                    </button>
                </div>
                <table class="main-table">
                    <thead>
                        <tr>
                            <th>اسم المعلم</th>
                            <th>المادة</th>
                            <th>نوع الغياب</th>
                            <th>إجمالي الغيابات</th>
                            <th>إجمالي التأخيرات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($teachers->num_rows): 
                            $teachers->data_seek(0);
                            while ($row = $teachers->fetch_assoc()): ?>
                                <tr>
                                    <td data-label="اسم المعلم"><?= htmlspecialchars($row['اسم_المعلمة']) ?></td>
                                    <td data-label="المادة"><?= htmlspecialchars($row['المادة']) ?></td>
                                    <td data-label="نوع الغياب">
                                        <div class="absence-options">
                                            <label>
                                                <input type="radio" name="teacher_absences[<?= htmlspecialchars($row['id']) ?>]"
                                                    value="<?= htmlspecialchars($row['id']) ?>_بعذر">
                                                <span></span> بعذر
                                            </label>
                                            <label>
                                                <input type="radio" name="teacher_absences[<?= htmlspecialchars($row['id']) ?>]"
                                                    value="<?= htmlspecialchars($row['id']) ?>_بدون عذر">
                                                <span></span> بدون عذر
                                            </label>
                                        </div>
                                    </td>
                                    <td data-label="إجمالي الغيابات"><?= htmlspecialchars($row['إجمالي_الغيابات']) ?></td>
                                    <td data-label="إجمالي التأخيرات"><?= htmlspecialchars($row['إجمالي_التأخيرات']) ?></td>
                                </tr>
                            <?php endwhile; 
                        else: ?>
                            <tr>
                                <td colspan="5">لا توجد معلمات لعرضها. يرجى إضافة معلمين.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>
        </div>

        <div id="latenessForm" style="display:none;">
            <h2>تسجيل التأخر الجديد</h2>
            <form method="post" name="lateness_form" onsubmit="return validateLatenessSelection();">
                <div class="form-actions">
                    <input type="date" name="lateness_date" required value="<?= htmlspecialchars($_POST['lateness_date'] ?? date('Y-m-d')) ?>">
                    <button type="submit" name="save_lateness">
                        <span class="material-icons">save</span> حفظ التأخر
                    </button>
                </div>
                <table class="main-table">
                    <thead>
                        <tr>
                            <th>اسم المعلم</th>
                            <th>المادة</th>
                            <th>وقت التأخر</th>
                            <th>مدة التأخر (دقيقة)</th>
                            <th>السبب (اختياري)</th>
                            <th>إجمالي الغيابات</th>
                            <th>إجمالي التأخيرات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $teachers->data_seek(0); 
                        if ($teachers->num_rows):
                            while ($row = $teachers->fetch_assoc()): ?>
                                <tr>
                                    <td data-label="اسم المعلم"><?= htmlspecialchars($row['اسم_المعلمة']) ?></td>
                                    <td data-label="المادة"><?= htmlspecialchars($row['المادة']) ?></td>
                                    <td data-label="وقت التأخر">
                                        <div class="lateness-inputs">
                                            <input type="time" name="lateness_time[<?= htmlspecialchars($row['id']) ?>]" value="">
                                        </div>
                                    </td>
                                    <td data-label="مدة التأخر (دقيقة)">
                                        <div class="lateness-inputs">
                                            <input type="number" name="lateness_duration[<?= htmlspecialchars($row['id']) ?>]" min="1" max="240" placeholder="دقائق" value="">
                                        </div>
                                    </td>
                                    <td data-label="السبب (اختياري)">
                                        <div class="lateness-inputs">
                                            <input type="text" name="lateness_reason[<?= htmlspecialchars($row['id']) ?>]" placeholder="السبب">
                                        </div>
                                    </td>
                                    <td data-label="إجمالي الغيابات"><?= htmlspecialchars($row['إجمالي_الغيابات']) ?></td>
                                    <td data-label="إجمالي التأخيرات"><?= htmlspecialchars($row['إجمالي_التأخيرات']) ?></td>
                                </tr>
                            <?php endwhile; 
                        else: ?>
                            <tr>
                                <td colspan="7">لا توجد معلمات لعرضها. يرجى إضافة معلمين.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>
        </div>

        <div class="bottom-nav-container">
            <a href="dashboard.php" class="button back-to-home-btn">
                <span class="material-icons">home</span> العودة للرئيسية
            </a>
        </div>
    </div>

    <script>
        function toggleForm() {
            const recordType = document.getElementById('recordType').value;
            document.getElementById('absenceForm').style.display = recordType === 'absence' ? 'block' : 'none';
            document.getElementById('latenessForm').style.display = recordType === 'lateness' ? 'block' : 'none';
        }

        function validateAbsenceSelection() {
            const dateInput = document.querySelector('form[name="absence_form"] input[name="absence_date"]');
            if (!dateInput.value) {
                alert("الرجاء اختيار تاريخ الغياب.");
                return false;
            }

            const radioButtons = document.querySelectorAll('form[name="absence_form"] input[type="radio"][name^="teacher_absences"]');
            let anyTeacherSelected = false;

            radioButtons.forEach(radio => {
                if (radio.checked) {
                    anyTeacherSelected = true;
                }
            });

            if (!anyTeacherSelected) {
                alert("الرجاء اختيار نوع الغياب (بعذر أو بدون عذر) لمعلم واحد على الأقل.");
                return false;
            }
            return true;
        }

        function validateLatenessSelection() {
            const dateInput = document.querySelector('form[name="lateness_form"] input[name="lateness_date"]');
            if (!dateInput.value) {
                alert("الرجاء اختيار تاريخ التأخر.");
                return false;
            }

            const teacherRows = document.querySelectorAll('form[name="lateness_form"] table.main-table tbody tr');
            let anyLatenessRecorded = false;
            const latenessData = [];

            teacherRows.forEach(row => {
                const teacherIdMatch = row.querySelector('input[name^="lateness_time"]').name.match(/\[(.*?)\]/);
                if (teacherIdMatch && teacherIdMatch[1]) {
                    const teacherId = teacherIdMatch[1];
                    const timeInput = row.querySelector(`input[name="lateness_time[${teacherId}]"]`);
                    const durationInput = row.querySelector(`input[name="lateness_duration[${teacherId}]"]`);
                    const reasonInput = row.querySelector(`input[name="lateness_reason[${teacherId}]"]`);

                    if (timeInput.value && durationInput.value) {
                        const duration = parseInt(durationInput.value);
                        if (isNaN(duration) || duration <= 0) {
                            alert(`الرجاء إدخال مدة تأخر صحيحة وموجبة بالدقائق للمعلمة ${row.querySelector('td:first-child').textContent}.`);
                            anyLatenessRecorded = false;
                            return; // Stop current iteration
                        }

                        anyLatenessRecorded = true;
                        latenessData.push(`${teacherId}|${timeInput.value}|${durationInput.value}|${reasonInput.value || ''}`);
                    }
                }
            });

            if (!anyLatenessRecorded) {
                alert("الرجاء إدخال وقت ومدة التأخر لمعلمة واحدة على الأقل.");
                return false;
            }

            const existingHiddenInput = document.querySelector('form[name="lateness_form"] input[name="teacher_latenesses_data[]"]');
            if (existingHiddenInput) {
                existingHiddenInput.remove();
            }

            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'teacher_latenesses_data[]';
            hiddenInput.value = JSON.stringify(latenessData);
            document.querySelector('form[name="lateness_form"]').appendChild(hiddenInput);

            return true;
        }
    </script>
</body>
</html>