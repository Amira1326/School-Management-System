<?php
session_start();

// Database connection details
$host = "localhost";
$dbname = "aljood_center";
$username = "root";
$password = "";

// Establish database connection
$conn = new mysqli($host, $username, $password, $dbname);
$conn->set_charset("utf8"); // Set character set for Arabic support
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

// Handle adding a new payment
if (isset($_POST['add_payment'])) {
    // Sanitize and validate inputs
    $student_id = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
    $amount = filter_input(INPUT_POST, 'المبلغ', FILTER_VALIDATE_FLOAT);
    $payment_type = filter_input(INPUT_POST, 'نوع_الدفع', FILTER_SANITIZE_STRING);

    // Basic validation check
    if (!$student_id || $amount === false || empty($payment_type)) {
        header("Location: payments.php?error=invalid_input");
        exit;
    }

    // Fetch student name based on ID using prepared statement to prevent SQL injection
    $stmt_student = $conn->prepare("SELECT اسم_الطالب FROM students WHERE رقم = ?");
    $stmt_student->bind_param("i", $student_id);
    $stmt_student->execute();
    $student_name_res = $stmt_student->get_result();
    $student_name_row = $student_name_res->fetch_assoc();
    $student_name = $student_name_row ? $student_name_row['اسم_الطالب'] : 'غير معروف'; // Default if student not found
    $stmt_student->close();

    // Prepare and execute the insert statement for payments
    // التعديل هنا: إضافة 'معرف_الطالب' إلى الأعمدة والقيم
    $stmt = $conn->prepare("INSERT INTO payments (`معرف_الطالب`, `اسم_الطالب`, `المبلغ`, `نوع_الدفع`, `تاريخ_الدفع`) VALUES (?, ?, ?, ?, CURDATE())");
    // التعديل هنا: إضافة 'i' لـ student_id وإضافة $student_id إلى bind_param
    $stmt->bind_param("isds", $student_id, $student_name, $amount, $payment_type);

    if ($stmt->execute()) {
        header("Location: payments.php?saved=added"); // Redirect with success message
    } else {
        // يمكنك إضافة تسجيل للخطأ هنا لمعرفة المزيد عن سبب الفشل: error_log($stmt->error);
        header("Location: payments.php?error=db_insert_failed"); // Redirect with database error message
    }
    $stmt->close();
    exit;
}

// Handle exporting data to Excel
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=payments.xls");
    
    // Fetch all payments
    // التعديل هنا: تضمين 'معرف_الطالب' في الاستعلام للتصدير
    $res = $conn->query("SELECT رقم, اسم_الطالب, معرف_الطالب, المبلغ, نوع_الدفع, تاريخ_الدفع FROM payments ORDER BY `رقم` DESC");

    // Output table headers
    echo "<table border='1'><tr><th>رقم</th><th>اسم الطالب</th><th>معرف الطالب</th><th>المبلغ</th><th>نوع الدفع</th><th>تاريخ الدفع</th></tr>";
    
    // Output table data
    while ($r = $res->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($r['رقم']) . "</td>";
        echo "<td>" . htmlspecialchars($r['اسم_الطالب']) . "</td>";
        echo "<td>" . htmlspecialchars($r['معرف_الطالب']) . "</td>"; // يتم عرضه هنا الآن
        echo "<td>" . number_format($r['المبلغ'], 2) . "</td>"; // Format amount for Excel
        echo "<td>" . htmlspecialchars($r['نوع_الدفع']) . "</td>";
        echo "<td>" . htmlspecialchars($r['تاريخ_الدفع']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    exit;
}

// Handle deleting a payment
if (isset($_GET['delete'])) {
    $id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT); // Sanitize and validate ID
    if ($id) {
        $stmt_delete = $conn->prepare("DELETE FROM payments WHERE `رقم` = ?");
        $stmt_delete->bind_param("i", $id);
        if ($stmt_delete->execute()) {
            header("Location: payments.php?deleted=1"); // Redirect with deletion message
        } else {
            header("Location: payments.php?error=delete_failed");
        }
        $stmt_delete->close();
    } else {
        header("Location: payments.php?error=invalid_delete_id");
    }
    exit;
}

// Fetch all payments for display, with search functionality
$where = "";
if (!empty($_GET['keyword'])) {
    // Sanitize keyword for LIKE query
    $k = "%" . $conn->real_escape_string($_GET['keyword']) . "%";
    // التعديل هنا: تضمين 'معرف_الطالب' في البحث
    $where = "WHERE `اسم_الطالب` LIKE '$k' OR `تاريخ_الدفع` LIKE '$k' OR `المبلغ` LIKE '$k' OR `معرف_الطالب` LIKE '$k'";
}
// التعديل هنا: جلب 'معرف_الطالب' لعرضه في الجدول إذا لزم الأمر
$result = $conn->query("SELECT رقم, اسم_الطالب, معرف_الطالب, المبلغ, نوع_الدفع, تاريخ_الدفع FROM payments $where ORDER BY `رقم` DESC");

// Fetch list of students for the dropdown
$students_query = $conn->query("SELECT رقم, اسم_الطالب FROM students ORDER BY اسم_الطالب ASC");
$students_list = [];
while ($student = $students_query->fetch_assoc()) {
    $students_list[] = $student;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الرسوم الدراسية</title>
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
            --spacing-small: 1rem;     /* 16px */
            --spacing-medium: 1.25rem; /* 20px */
            --spacing-large: 1.875rem; /* 30px */
            --spacing-xl: 3.125rem;    /* 50px */
        }

        /* الأساسيات لضمان امتداد الخلفية على كامل الصفحة */
        html, body {
            height: 100%; /* تأكد من أن الـ html والـ body يمتدان على كامل ارتفاع النافذة */
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Cairo', sans-serif;
            /* تطبيق التدرج اللوني الذي يبدأ بالأبيض وينتهي باللون الثانوي */
            background: linear-gradient(to bottom, var(--white) 0%, var(--secondary-color) 100%);
            line-height: 1.6;
            color: var(--text-dark);
            font-size: 1rem; /* Base font size */
            display: flex; /* استخدام Flexbox لتوسيط المحتوى العمودي */
            flex-direction: column;
            align-items: center;
            justify-content: center; /* توسيط المحتوى عمودياً */
            min-height: 100vh; /* تأكد من أن الـ body يأخذ على الأقل ارتفاع الشاشة الكامل */
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
            right: var(--spacing-medium); /* Adjusted for RTL */
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
            justify-content: center; /* Changed to center to match teachers.php */
            gap: var(--spacing-small);
            margin-bottom: var(--spacing-large);
        }
        
        .filters {
            padding-bottom: var(--spacing-small);
            border-bottom: 1px solid #eee;
        }

        .form-section {
            background-color: #fcf8fc; /* Lighter background for form, as in your provided CSS */
            padding: var(--spacing-large);
            border-radius: 10px;
            box-shadow: inset 0 0 5px rgba(0,0,0,0.03);
            margin-top: var(--spacing-large);
        }

        .footer-buttons {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-start; /* Original: flex-start, changed to center for consistency */
            gap: var(--spacing-small);
            margin-top: var(--spacing-large);
            direction: rtl; /* Ensure buttons flow correctly for RTL */
            padding-top: var(--spacing-small);
            border-top: 1px solid #eee;
        }

        input[type="text"], select {
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            min-width: 200px;
            flex-grow: 1; /* Allow inputs to grow */
            font-size: 1rem;
            color: var(--text-dark);
            transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        /* Specific input styles for number type in form section */
        .form-section input[type="number"] {
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            min-width: 200px;
            flex-grow: 1;
            font-size: 1rem;
            color: var(--text-dark);
            transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            box-sizing: border-box; /* Include padding and border in the element's total width and height */
            height: 48px; /* Consistent height for inputs and selects */
        }


        input[type="text"]:focus, input[type="number"]:focus, select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(137, 53, 93, 0.1);
            outline: none;
        }

        button, .button-link {
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            padding: 12px 25px;
            border-radius: var(--border-radius-pill); /* More rounded buttons */
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
            transition: background-color 0.3s ease, transform 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap; /* Prevent text wrapping */
        }

        button:hover, .button-link:hover {
            background-color: var(--button-hover-color);
            transform: translateY(-2px);
        }
        
        .button-link {
            text-decoration: none; /* For anchor tags styled as buttons */
        }

        table {
            width: 100%;
            border-collapse: separate; /* For rounded corners */
            border-spacing: 0;
            margin-top: var(--spacing-large);
            background: var(--white);
            border-radius: 10px; /* Slightly rounded table */
            overflow: hidden; /* Ensures rounded corners are visible */
            box-shadow: 0 4px 12px var(--shadow-light);
        }

        th, td {
            border: 1px solid #f0f0f0;
            padding: 12px 15px;
            text-align: center;
            vertical-align: middle;
        }

        th {
            background: #fdf6fa; /* Lighter background for headers */
            color: var(--primary-color);
            font-weight: 700;
            font-size: 1.05rem;
            position: sticky; /* Make headers sticky for scrollable tables */
            top: 0;
            z-index: 10;
        }

        tr:nth-child(even) {
            background-color: #fdfbff; /* Subtle stripe effect */
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
        /* Added error alert style */
        .alert.error {
            background-color: #ffe6e6;
            color: #dc3545;
            border-color: #f0c9c9;
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
                float: right; /* Adjust for print layout */
                margin-bottom: var(--spacing-medium);
            }
            h1 {
                text-align: right;
                margin-right: 150px; /* Space for logo */
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
                background: #f0f0f0 !important; /* Ensure background prints */
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
            input[type="text"], input[type="number"], select, button, .button-link {
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
                padding-right: 50%; /* Space for pseudo-element */
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
            /* تحديث تسميات الخلايا لتناسب جدول الدفعات */
            td:nth-of-type(1):before { content: "الرقم:"; }
            td:nth-of-type(2):before { content: "اسم الطالب:"; }
            td:nth-of-type(3):before { content: "معرف الطالب:"; } /* NEW */
            td:nth-of-type(4):before { content: "المبلغ:"; }
            td:nth-of-type(5):before { content: "نوع الدفع:"; } 
            td:nth-of-type(6):before { content: "التاريخ:"; }
            td:nth-of-type(7):before { content: "خيارات:"; } /* Adjusted index due to new column */
            
            td:last-child { /* Actions column */
                display: flex;
                justify-content: center;
                gap: 10px;
                padding-right: 15px; /* Reset padding */
            }
            td:last-child::before {
                content: ''; /* Hide pseudo-element for actions */
                width: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <img src="logo.png" class="logo" alt="شعار المركز">
    <h1>الرسوم الدراسية</h1>

    <?php if (isset($_GET['saved']) || isset($_GET['deleted']) || isset($_GET['error'])): ?>
        <div id="success-msg" class="alert 
            <?php 
                if (isset($_GET['saved'])) {
                    echo ($_GET['saved'] === 'added') ? 'success' : '';
                } elseif (isset($_GET['deleted'])) {
                    echo 'warning'; 
                } elseif (isset($_GET['error'])) {
                    echo 'error'; 
                }
            ?>">
            <?php 
                if (isset($_GET['saved'])) {
                    echo ($_GET['saved'] === 'added' ? '✅ تم إضافة الدفعة بنجاح.' : '');
                } elseif (isset($_GET['deleted'])) {
                    echo '🗑️ تم حذف الدفعة.';
                } elseif (isset($_GET['error'])) {
                    $errorMessage = '⚠️ حدث خطأ غير معروف.';
                    if ($_GET['error'] === 'invalid_input') {
                        $errorMessage = '⚠️ خطأ: يرجى التحقق من صحة البيانات المدخلة.';
                    } elseif ($_GET['error'] === 'db_insert_failed') {
                        $errorMessage = '❌ فشل حفظ الدفعة في قاعدة البيانات.';
                    } elseif ($_GET['error'] === 'invalid_delete_id') {
                        $errorMessage = '⚠️ خطأ: معرف الدفعة غير صالح للحذف.';
                    }
                    echo $errorMessage;
                }
            ?>
            <span class="close-btn" onclick="closeAlert()">✖</span>
        </div>
    <?php endif; ?>

    <form method="get" class="filters">
        <input type="text" name="keyword" placeholder="بحث بالاسم أو التاريخ أو المبلغ أو المعرف" value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
        <button type="submit">
            <span class="material-icons">search</span> بحث
        </button>
        <a href="payments.php" class="button-link" style="background-color: #6c757d;">
            <span class="material-icons">clear</span> إزالة الفلتر
        </a>
    </form>

    <form method="post">
        <div class="form-section">
            <div class="form-field-wrapper">
                <select name="student_id" id="student_id" required>
                    <option value="">-- اختر الطالب --</option>
                    <?php foreach ($students_list as $student): ?>
                        <option value="<?= htmlspecialchars($student['رقم']) ?>">
                            <?= htmlspecialchars($student['اسم_الطالب']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-field-wrapper">
                <input type="number" name="المبلغ" id="المبلغ" placeholder="أدخل المبلغ" step="0.01" min="0" required>
            </div>

            <div class="form-field-wrapper">
                <select name="نوع_الدفع" id="نوع_الدفع" required>
                    <option value="">-- اختر نوع الدفع --</option>
                    <option value="نقدي">نقدي</option>
                    <option value="تحويل بنكي">تحويل بنكي</option>
                    <option value="شبكة">شبكة</option>
                </select>
            </div>

            <button type="submit" name="add_payment">
                <span class="material-icons">add_circle</span> إضافة دفعة
            </button>
        </div>
    </form>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>اسم الطالب</th>
                <th>معرف الطالب</th> <th>المبلغ</th>
                <th>نوع الدفع</th>
                <th>التاريخ</th>
                <th>خيارات</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): $i = 1;
            while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td data-label="#:"><?= $i++ ?></td>
                    <td data-label="اسم الطالب:"><?= htmlspecialchars($row['اسم_الطالب']) ?></td>
                    <td data-label="معرف الطالب:"><?= htmlspecialchars($row['معرف_الطالب'] ?? 'N/A') ?></td> <td data-label="المبلغ:"><?= number_format($row['المبلغ'], 2) ?> ﷼</td>
                    <td data-label="نوع الدفع:"><?= htmlspecialchars($row['نوع_الدفع']) ?></td>
                    <td data-label="التاريخ:"><?= $row['تاريخ_الدفع'] ?></td>
                    <td data-label="خيارات:">
                        <a href="?delete=<?= htmlspecialchars($row['رقم']) ?>" onclick="return confirm('هل أنت متأكد من حذف هذه الدفعة؟ لا يمكن التراجع عن هذا الإجراء!')">
                            <button class="delete-btn" title="حذف">
                                <span class="material-icons">delete</span>
                            </button>
                        </a>
                    </td>
                </tr>
            <?php endwhile; else: ?>
                <tr>
                    <td colspan="7">لا توجد دفعات حالياً أو لا تطابق نتائج البحث.</td> </tr>
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
        <a href="dashboard.php" class="button-link" style="background-color: #f0ad4e;">
            <span class="material-icons">dashboard</span> العودة للرئيسية
        </a>
    </div>
</div>

<script>
    // JavaScript for closing alert messages
    function closeAlert() {
        const msg = document.getElementById('success-msg');
        if (msg) {
            msg.style.opacity = 0;
            setTimeout(() => msg.remove(), 500); // Remove element after fade out
        }
    }
    // Automatically hide alert message after 5 seconds
    setTimeout(closeAlert, 5000);
</script>

</body>
</html>