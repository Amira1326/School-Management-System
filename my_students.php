<?php
session_start();

// معلومات الاتصال بقاعدة البيانات
$host = "localhost";
$dbname = "aljood_center";
$username = "root";
$password = "";

$conn = new mysqli($host, $username, $password, $dbname);
$conn->set_charset("utf8"); // مهم لضمان التعامل الصحيح مع اللغة العربية
if ($conn->connect_error) {
   die("فشل الاتصال: " . $conn->connect_error);
}

// التأكد من أن المعلم مسجل الدخول
if (!isset($_SESSION['teacher_id'])) {
   // توجيه المستخدم لصفحة تسجيل الدخول إذا لم يكن مسجلاً
   header("Location: login.php");
   exit("من فضلك قم بتسجيل الدخول كمعلم.");
}

$teacher_id = $_SESSION['teacher_id'];

// متغيرات الفلترة والبحث
$search_keyword = $_GET['keyword'] ?? '';
$selected_class_char = $_GET['filter_class_char'] ?? ''; // تغيير اسم المتغير ليمثل الحرف وليس الـ ID
$selected_grade_level = $_GET['filter_grade_level'] ?? '';

// بناء الاستعلام لاختيار الطلاب بناءً على الفلاتر وصلاحيات المعلمة
$query_select_fields = "s.رقم, s.اسم_الطالب, c.المرحلة_الدراسية AS class_level, c.الفصل AS class_name, s.الفترة, s.الجنسية, s.رقم_الهوية, s.رقم_ولي_الأمر, c.تاريخ_الإضافة";

$query_parts = [
   "SELECT {$query_select_fields}",
   "FROM students s",
   "INNER JOIN classes c ON s.معرف_الفصل = c.رقم",
   "WHERE c.معرف_المعلمة = ?" // تصفية الطلاب حسب المعلمة المسجلة الدخول
];
$params = [$teacher_id];
$types = "i"; // نوع معرف المعلمة هو عدد صحيح

// إضافة فلتر المرحلة الدراسية
if (!empty($selected_grade_level)) {
   $query_parts[] = " AND c.المرحلة_الدراسية = ?";
   $params[] = $selected_grade_level;
   $types .= "s"; // نوع المرحلة الدراسية هو سلسلة نصية
}

// إضافة فلتر الفصل (باستخدام الحرف 'أ', 'ب', 'ج')
if (!empty($selected_class_char)) {
   // هنا نفلتر بناءً على الحرف في عمود 'الفصل' وليس الـ ID
   $query_parts[] = " AND c.الفصل = ?";
   $params[] = $selected_class_char;
   $types .= "s"; // نوع الفصل (الحرف) هو سلسلة نصية
}

// إضافة وظيفة البحث باسم الطالب أو رقم الهوية
if (!empty($search_keyword)) {
   $query_parts[] = " AND (s.اسم_الطالب LIKE ? OR s.رقم_الهوية LIKE ?)";
   $params[] = "%" . $search_keyword . "%";
   $params[] = "%" . $search_keyword . "%";
   $types .= "ss"; // نوع الكلمات المفتاحية هي سلاسل نصية
}

$query_parts[] = " ORDER BY s.رقم DESC"; // ترتيب النتائج
$base_query = implode(" ", $query_parts); // دمج أجزاء الاستعلام

// --- منطق تصدير Excel المباشر ---
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
   $stmt_export = $conn->prepare($base_query);
   if (!empty($params)) {
       // تمرير المعاملات المجمعة للاستعلام
       $stmt_export->bind_param($types, ...$params);
   }
   $stmt_export->execute();
   $export_result = $stmt_export->get_result();

   // إعداد رأس الاستجابة لملف Excel
   header("Content-Type: application/vnd.ms-excel; charset=utf-8");
   header("Content-Disposition: attachment; filename=students_data.xls");

   // بناء جدول HTML بسيط لتصديره كـ XLS
   echo "<table border='1'>";
   echo "<tr>";
   echo "<th>الرقم</th>";
   echo "<th>اسم الطالب</th>";
   echo "<th>المرحلة الدراسية</th>";
   echo "<th>الفصل</th>";
   echo "<th>الفترة</th>";
   echo "<th>الجنسية</th>";
   echo "<th>رقم الهوية</th>";
   echo "<th>رقم ولي الأمر</th>";
   echo "<th>تاريخ الإضافة</th>";
   echo "</tr>";

   while ($row = $export_result->fetch_assoc()) {
       echo "<tr>";
       // عرض البيانات مع التأكد من عدم وجود مشاكل في الترميز
       echo "<td>" . htmlspecialchars($row['رقم']) . "</td>";
       echo "<td>" . htmlspecialchars($row['اسم_الطالب']) . "</td>";
       echo "<td>" . htmlspecialchars($row['class_level']) . "</td>";
       echo "<td>" . htmlspecialchars($row['class_name']) . "</td>";
       echo "<td>" . htmlspecialchars($row['الفترة']) . "</td>";
       echo "<td>" . htmlspecialchars($row['الجنسية']) . "</td>";
       echo "<td>" . htmlspecialchars($row['رقم_الهوية']) . "</td>";
       echo "<td>" . htmlspecialchars($row['رقم_ولي_الأمر']) . "</td>";
       echo "<td>" . htmlspecialchars($row['تاريخ_الإضافة']) . "</td>";
       echo "</tr>";
   }
   echo "</table>";
   exit; // إنهاء التنفيذ بعد التصدير
}
// --- نهاية منطق تصدير Excel ---

// تنفيذ الاستعلام الأساسي لعرض البيانات في الصفحة
$stmt = $conn->prepare($base_query);
if (!empty($params)) {
   // تمرير المعاملات المجمعة للاستعلام
   $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// معالجة حذف الطالب
if (isset($_GET['delete'])) {
   $id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT); // التحقق من أن الـ ID رقم صحيح
   if ($id) {
       // التحقق من أن الطالب ينتمي لفصول هذه المعلمة قبل الحذف، لضمان الأمان والصلاحيات
       $check_query = "SELECT COUNT(*) FROM students s INNER JOIN classes c ON s.معرف_الفصل = c.رقم WHERE s.رقم = ? AND c.معرف_المعلمة = ?";
       $stmt_check = $conn->prepare($check_query);
       $stmt_check->bind_param("ii", $id, $teacher_id);
       $stmt_check->execute();
       $row_count = $stmt_check->get_result()->fetch_row()[0];
       $stmt_check->close();

       if ($row_count > 0) { // إذا كان الطالب موجوداً ويتبع المعلمة
           $stmt_delete = $conn->prepare("DELETE FROM students WHERE `رقم` = ?");
           $stmt_delete->bind_param("i", $id);
           if ($stmt_delete->execute()) {
               // إعادة التوجيه مع رسالة نجاح/تحذير
               header("Location: my_students.php?status_message=warning&status_message_text=" . urlencode('🗑️ تم حذف الطالب بنجاح.') . "&keyword=" . urlencode($search_keyword) . "&filter_class_char=" . urlencode($selected_class_char) . "&filter_grade_level=" . urlencode($selected_grade_level));
           } else {
               header("Location: my_students.php?status_message=error&status_message_text=" . urlencode('❌ فشل حذف الطالب من قاعدة البيانات.') . "&keyword=" . urlencode($search_keyword) . "&filter_class_char=" . urlencode($selected_class_char) . "&filter_grade_level=" . urlencode($selected_grade_level));
           }
           $stmt_delete->close();
       } else {
           header("Location: my_students.php?status_message=error&status_message_text=" . urlencode('⚠️ لا تملك صلاحية حذف هذا الطالب.') . "&keyword=" . urlencode($search_keyword) . "&filter_class_char=" . urlencode($selected_class_char) . "&filter_grade_level=" . urlencode($selected_grade_level));
       }
   } else {
       header("Location: my_students.php?status_message=error&status_message_text=" . urlencode('⚠️ خطأ: معرف الطالب غير صالح للحذف.') . "&keyword=" . urlencode($search_keyword) . "&filter_class_char=" . urlencode($selected_class_char) . "&filter_grade_level=" . urlencode($selected_grade_level));
   }
   exit; // إنهاء التنفيذ بعد عملية الحذف
}

// تعريف قائمة الفصول الثابتة 'أ', 'ب', 'ج'
$class_options_static = ['أ', 'ب', 'ج'];

// قائمة بالمراحل الدراسية المحتملة
$grades = ["الصف الأول الابتدائي","الصف الثاني الابتدائي","الصف الثالث الابتدائي","الصف الرابع الابتدائي","الصف الخامس الابتدائي","الصف السادس الابتدائي","الصف الأول المتوسط"];
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>قائمة الطلاب - طلابي</title>
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
           --spacing-xs: 0.75rem;     /* 12px */
           --spacing-small: 1rem;     /* 16px */
           --spacing-medium: 1.25rem; /* 20px */
           --spacing-large: 1.875rem; /* 30px */
           --spacing-xl: 3.125rem;    /* 50px */
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
           justify-content: flex-start; /* Changed to flex-start to avoid pushing content too high */
           min-height: 100vh;
           padding-top: var(--spacing-xl); /* Adjusted padding */
           padding-bottom: var(--spacing-xl);
           box-sizing: border-box;
       }

       .container {
           width: 95%;
           max-width: 1200px;
           margin: 0 auto; /* Remove top margin */
           padding: var(--spacing-large);
           background: var(--white);
           border-radius: 12px;
           box-shadow: 0 8px 24px var(--shadow-light);
           position: relative;
           overflow-x: auto; /* Allow horizontal scroll for table */
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

       .filters {
           display: flex;
           flex-wrap: wrap;
           justify-content: center;
           gap: var(--spacing-small);
           margin-bottom: var(--spacing-large);
           padding-bottom: var(--spacing-small);
           border-bottom: 1px solid #eee;
           align-items: center;
       }

       .filter-group {
           display: flex;
           flex-wrap: wrap;
           gap: var(--spacing-small);
           align-items: center;
       }

       .button-group-bottom {
           display: flex;
           flex-wrap: wrap;
           justify-content: flex-start;
           gap: var(--spacing-small);
           margin-top: var(--spacing-large);
           padding-top: var(--spacing-small);
           border-top: 1px solid #eee;
           direction: rtl;
       }


       input[type="text"], input[type="number"], select {
           padding: 12px 15px;
           border-radius: 8px;
           border: 1px solid #ddd;
           min-width: 180px;
           flex-grow: 1;
           font-size: 1rem;
           color: var(--text-dark);
           transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
           box-sizing: border-box;
           height: 48px;
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

       .button-link.export-excel {
           background-color: #89355d;
       }
       .button-link.print-list {
           background-color: #89355d;
       }
       .button-link.back-to-dashboard {
           background-color: #f0ad4e;
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

       .button-link .material-icons,
       button .material-icons {
           order: 1;
       }
       .button-link.print-list .material-icons {
           order: 0;
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
       .alert.error {
           background-color: #ffe6e6;
           color: #dc3545;
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
           }
           h1 {
               text-align: right;
               margin-right: 150px;
           }
           .filters, .button-group-bottom, .alert, td button {
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

       /* Responsive Adjustments */
       @media (max-width: 768px) {
           .container {
               margin: var(--spacing-medium) auto;
               padding: var(--spacing-medium);
           }
           h1 {
               font-size: 1.8rem;
           }
           .filters, .button-group-bottom {
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
           td:last-child {
               text-align: center;
               padding-left: 0;
               display: flex;
               justify-content: center;
               padding: var(--spacing-small);
               background-color: #fdf6fa;
               border-top: 1px solid #eee;
           }
           td:last-child:before {
               content: none;
           }
       }
   </style>
</head>
<body>

<div class="container">
   <img src="logo.png" class="logo" alt="شعار المركز">
   <h1>قائمة الطلاب - طلابي</h1>

   <?php if (isset($_GET['status_message'])): ?>
       <div id="alert-msg" class="alert
           <?php
               if ($_GET['status_message'] === 'success') {
                   echo 'success';
               } elseif ($_GET['status_message'] === 'warning') {
                   echo 'warning';
               } elseif ($_GET['status_message'] === 'error') {
                   echo 'error';
               }
           ?>">
           <?php echo htmlspecialchars($_GET['status_message_text']); ?>
           <span class="close-btn" onclick="closeAlert()">✖</span>
       </div>
   <?php endif; ?>

   <form method="get" class="filters">
       <div class="filter-group">
           <select name="filter_grade_level" onchange="this.form.submit()">
               <option value="">-- اختر المرحلة الدراسية --</option>
               <?php foreach ($grades as $grade): ?>
                   <option value="<?= htmlspecialchars($grade) ?>"
                       <?= ($selected_grade_level == $grade) ? "selected" : "" ?>>
                       <?= htmlspecialchars($grade) ?>
                   </option>
               <?php endforeach; ?>
           </select>

           <select name="filter_class_char" onchange="this.form.submit()">
               <option value="">-- اختر الفصل --</option>
               <?php foreach ($class_options_static as $option): ?>
                   <option value="<?= htmlspecialchars($option) ?>"
                       <?= ($selected_class_char == $option) ? "selected" : "" ?>>
                       <?= htmlspecialchars($option) ?>
                   </option>
               <?php endforeach; ?>
           </select>

           <input type="text" name="keyword" placeholder="بحث باسم الطالب أو رقم الهوية..." value="<?= htmlspecialchars($search_keyword) ?>">
           <button type="submit">
               <span class="material-icons">search</span> بحث
           </button>
           <a href="my_students.php" class="button-link" style="background-color: #6c757d;">
               <span class="material-icons">clear</span> إزالة البحث
           </a>
       </div>
   </form>


   <?php if ($result->num_rows > 0): ?>
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
               <?php $i = 1; // عداد لترقيم الصفوف
               while ($row = $result->fetch_assoc()): ?>
                   <tr>
                       <td data-label="#:"><?= $i++ ?></td>
                       <td data-label="اسم الطالب:"><?= htmlspecialchars($row['اسم_الطالب']) ?></td>
                       <td data-label="المرحلة الدراسية:"><?= htmlspecialchars($row['class_level']) ?></td>
                       <td data-label="الفصل:"><?= htmlspecialchars($row['class_name']) ?></td>
                       <td data-label="الفترة:"><?= htmlspecialchars($row['الفترة']) ?></td>
                       <td data-label="الجنسية:"><?= htmlspecialchars($row['الجنسية']) ?></td>
                       <td data-label="رقم الهوية:"><?= htmlspecialchars($row['رقم_الهوية']) ?></td>
                       <td data-label="رقم ولي الأمر:"><?= htmlspecialchars($row['رقم_ولي_الأمر']) ?></td>
                       <td data-label="تاريخ الإضافة:"><?= htmlspecialchars($row['تاريخ_الإضافة']) ?></td>
                       <td data-label="خيارات:">
                           <a href="?delete=<?= htmlspecialchars($row['رقم']) ?>&keyword=<?= urlencode($search_keyword) ?>&filter_class_char=<?= urlencode($selected_class_char) ?>&filter_grade_level=<?= urlencode($selected_grade_level) ?>" onclick="return confirm('هل أنت متأكد من حذف هذا الطالب؟ لا يمكن التراجع عن هذا الإجراء!')">
                               <button class="delete-btn" title="حذف">
                                   <span class="material-icons">delete</span>
                               </button>
                           </a>
                       </td>
                   </tr>
               <?php endwhile; ?>
           </tbody>
       </table>
   <?php else: ?>
       <p style="text-align: center; color: var(--text-medium); margin-top: var(--spacing-large);">
           لا توجد طلاب حالياً تطابق المعايير المختارة أو لا يوجد طلاب في الفصول المخصصة لك.
       </p>
   <?php endif; ?>

   <div class="button-group-bottom">
       <a href="?export=excel&filter_class_char=<?= urlencode($selected_class_char) ?>&filter_grade_level=<?= urlencode($selected_grade_level) ?>&keyword=<?= urlencode($search_keyword) ?>" class="button-link export-excel">
           <span class="material-icons">download</span> تصدير Excel
       </a>

       <button onclick="window.print()" class="button-link print-list">
           <span class="material-icons">print</span> طباعة القائمة
       </button>

       <a href="teacher-dashboard.php" class="button-link back-to-dashboard">
           <span class="material-icons">dashboard</span> العودة للرئيسية
       </a>
   </div>
   </div>

<script>
   // وظيفة لإخفاء رسائل التنبيه
   function closeAlert() {
       const msg = document.getElementById('alert-msg');
       if (msg) {
           msg.style.opacity = 0; // بدء إخفاء الرسالة تدريجياً
           setTimeout(() => msg.remove(), 500); // إزالة العنصر بعد انتهاء الانتقال
       }
   }
   // إخفاء الرسالة تلقائياً بعد 5 ثوانٍ
   setTimeout(closeAlert, 5000);
</script>

</body>
</html>
<?php $conn->close(); // إغلاق اتصال قاعدة البيانات ?>