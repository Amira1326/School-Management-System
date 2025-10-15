<?php
session_start();

// --- إعدادات الاتصال بقاعدة البيانات ---
$dsn = 'mysql:host=localhost;dbname=aljood_center;charset=utf8';
$username = 'root';
$password = '';
$options = [
    PDO::ATTR_ERRMODE              => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES    => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}

// --- جلب المتغيرات من الرابط ---
$student_id = $_GET['student_id'] ?? null;
$print_type = $_GET['type'] ?? 'final_report';
$grade_level = $_GET['grade_level'] ?? '';
$class_name = $_GET['class_name'] ?? '';
$term = $_GET['term'] ?? '';

if (!$grade_level || !$class_name || !$term) {
    die("بيانات غير كافية للطباعة.");
}

// --- دوال مساعدة ---
function get_class_info($pdo, $grade_level, $class_name) {
    $stmt = $pdo->prepare("
        SELECT c.رقم, t.اسم_المعلمة AS مرشد_الصف
        FROM classes c
        LEFT JOIN teachers t ON c.مرشد_الصف_id = t.id
        WHERE c.المرحلة_الدراسية = ? AND c.الفصل = ?
    ");
    $stmt->execute([$grade_level, $class_name]);
    return $stmt->fetch();
}

$class_info = get_class_info($pdo, $grade_level, $class_name);
if (!$class_info || !$class_info['رقم']) {
    die("بيانات الفصل غير صحيحة.");
}
$class_id = $class_info['رقم'];
$class_supervisor_name = $class_info['مرشد_الصف'] ?? 'غير محدد';

// --- جلب بيانات الطلاب ---
$students = [];
$students_ids = [];
if ($student_id && $student_id !== 'all') {
    $stmt_students = $pdo->prepare("SELECT رقم, اسم_الطالب FROM students WHERE رقم = ? AND الفصل = ? AND المرحلة_الدراسية = ?");
    $stmt_students->execute([$student_id, $class_name, $grade_level]);
    $students[] = $stmt_students->fetch();
    $students_ids[] = $student_id;
} else {
    $stmt_students = $pdo->prepare("SELECT رقم, اسم_الطالب FROM students WHERE الفصل = ? AND المرحلة_الدراسية = ? ORDER BY اسم_الطالب ASC");
    $stmt_students->execute([$class_name, $grade_level]);
    $students = $stmt_students->fetchAll();
    $students_ids = array_column($students, 'رقم');
}

if (empty($students)) {
    die("لا يوجد طلاب لعرضهم.");
}

// --- جلب كل درجات المواد لجميع الطلاب المختارين ---
$all_grades = [];
if (!empty($students_ids)) {
    $placeholders = implode(',', array_fill(0, count($students_ids), '?'));
    $sql_grades = "
        SELECT
            g.رقم_الطالب,
            s.اسم_المادة,
            g.الاختبار_الدوري_الأول, g.الاختبار_الدوري_الثاني, g.الاختبار_النصفي,
            g.الواجبات, g.الأنشطة_وأوراق_العمل, g.المشاركة, g.السلوك, g.الغياب, g.الاختبار_النهائي
        FROM grades g
        JOIN subjects s ON g.subject_id = s.id
        WHERE g.الفصل_الدراسي = ? AND g.رقم_الفصل = ? AND g.رقم_الطالب IN ($placeholders)
        ORDER BY g.رقم_الطالب ASC, s.id ASC
    ";
    $params = array_merge([$term, $class_id], $students_ids);
    $stmt_grades = $pdo->prepare($sql_grades);
    $stmt_grades->execute($params);
    
    while ($row = $stmt_grades->fetch()) {
        $student_id_from_db = $row['رقم_الطالب'];
        if (!isset($all_grades[$student_id_from_db])) {
            $all_grades[$student_id_from_db] = [];
        }
        $all_grades[$student_id_from_db][$row['اسم_المادة']] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>شهادة درجات</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet" />
    <style>
        body { font-family: 'Cairo', sans-serif; background-color: #f7f7f7; color: #333; margin: 0; padding: 0; }
        .certificate-container {
            width: 21cm;
            min-height: 29.7cm;
            margin: 1cm auto;
            background-color: #fff;
            border: 2px solid #000;
            padding: 1cm;
            box-sizing: border-box;
            position: relative;
            display: flex;
            flex-direction: column;
        }
        .page-break { page-break-after: always; }
        
        .header-section {
            display: grid;
            grid-template-columns: 1fr 2fr 1fr;
            align-items: center;
            border-bottom: 2px solid #000;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        .header-section .logo { width: 100px; display: block; margin: 0 auto; }
        .header-section .center-info {
            text-align: center;
            line-height: 1.6;
        }
        .header-section .center-info h2 { font-size: 1.2rem; margin: 0; }
        .header-section .center-info p { margin: 0; font-size: 0.8rem; }
        .header-section .ministry-info {
            text-align: right;
            font-size: 0.9rem;
            line-height: 1.6;
        }
        .header-section .ministry-info p { margin: 0; }
        
        .title-section { text-align: center; margin-bottom: 1.5rem; }
        .title-section h3 { font-size: 1.5rem; color: #89355d; border: 2px solid #89355d; padding: 0.5rem 1rem; display: inline-block; }

        .student-details { border: 2px solid #000; padding: 1rem; margin-bottom: 1.5rem; }
        .student-details p { margin: 0.5rem 0; font-size: 1rem; }
        .student-details span { font-weight: 700; color: #89355d; }
        .student-details .highlight { color: #000; font-weight: 700; border-bottom: 1px solid #000; padding-bottom: 2px; }

        .grades-table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; }
        .grades-table th, .grades-table td {
            border: 1px solid #000;
            padding: 0.5rem;
            text-align: center;
            font-size: 0.9rem;
        }
        .grades-table thead th {
            background-color: #89355d;
            color: #fff;
            font-weight: 700;
        }
        .grades-table thead .main-header { font-size: 1.2rem; }
        .grades-table tbody td:first-child { text-align: right; font-weight: 600; padding-right: 1rem; }

        .summary-section {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1rem;
            align-items: center;
            text-align: center;
            margin-bottom: 1rem;
            padding: 1rem;
            border: 2px solid #000;
        }
        .summary-item { font-size: 1rem; font-weight: 700; }
        .summary-item .label { border-bottom: 1px solid #000; padding-bottom: 0.2rem; margin-bottom: 0.5rem; }
        .summary-item .value { font-size: 1.2rem; color: #89355d; }

        .signatures-section {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1rem;
            text-align: center;
            align-items: end;
            margin-top: auto;
            padding-top: 2rem;
            position: relative;
        }
        .signature-item { position: relative; }
        .signature-item .label { border-top: 1px solid #000; padding-top: 0.5rem; font-weight: 600; font-size: 0.9rem; }
        .signature-item img { max-width: 150px; position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%); opacity: 0.7; }
        .seal { max-width: 100px; }
        .quote {
            position: absolute;
            bottom: -5rem;
            right: 0;
            width: 250px;
            font-size: 0.8rem;
            line-height: 1.4;
            text-align: center;
            padding: 1rem;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
        }
        
        @media print {
            body { background: none; }
            .certificate-container { border: none; box-shadow: none; margin: 0; padding: 1cm; }
            .certificate-container, .grades-table { font-size: 10pt; }
            .header-section .logo { width: 80px; }
            .header-section .center-info h2 { font-size: 1rem; }
            .header-section .center-info p { font-size: 0.7rem; }
            .header-section .ministry-info { font-size: 0.8rem; }
            .title-section h3 { font-size: 1.2rem; }
            .grades-table th, .grades-table td { padding: 0.3rem; font-size: 0.8rem; }
            .summary-item .label { font-size: 0.8rem; }
            .summary-item .value { font-size: 1rem; }
            .signatures-section { grid-template-columns: 1fr 1fr 1fr; gap: 0.5rem; }
            .signature-item .label { font-size: 0.8rem; }
            .seal { max-width: 80px; }
            .quote { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <?php foreach ($students as $student):
        if (!$student) continue;

        $sid = $student['رقم'];
        $student_grades_by_subject = $all_grades[$sid] ?? [];

        // Calculate total grades for the current student across all subjects
        $total_final_all_subjects = 0;
        $subjects_count = count($student_grades_by_subject);
        // The total possible grade is now 80 points per subject (50 session work + 10 midterm + 20 final)
        $total_possible_grade = 80 * $subjects_count;

        foreach ($student_grades_by_subject as $subject_data) {
            $session_work_grade = floatval($subject_data['الاختبار_الدوري_الأول'] ?? 0) +
                                           floatval($subject_data['الاختبار_الدوري_الثاني'] ?? 0) +
                                           floatval($subject_data['الواجبات'] ?? 0) +
                                           floatval($subject_data['الأنشطة_وأوراق_العمل'] ?? 0) +
                                           floatval($subject_data['المشاركة'] ?? 0);
            
            $midterm_grade = floatval($subject_data['الاختبار_النصفي'] ?? 0);
            $final_exam_grade = floatval($subject_data['الاختبار_النهائي'] ?? 0);
            // The grades for 'السلوك' and 'الغياب' are no longer included in the total
            $total_subject = $session_work_grade + $midterm_grade + $final_exam_grade;
            
            $total_final_all_subjects += $total_subject;
        }

        $overall_percentage = $total_possible_grade > 0 ? ($total_final_all_subjects / $total_possible_grade) * 100 : 0;
        $result = $overall_percentage >= 50 ? 'ناجح' : 'راسب';
        
        $assessment = '';
        if ($overall_percentage >= 90) $assessment = 'ممتاز';
        else if ($overall_percentage >= 80) $assessment = 'جيد جداً';
        else if ($overall_percentage >= 70) $assessment = 'جيد';
        else if ($overall_percentage >= 60) $assessment = 'مقبول';
        else $assessment = 'ضعيف';

        // Set the academic year for display
        $academic_year = '١٤٤٦هـ';
        $arabic_term = '';
        if ($term === '1') {
            $arabic_term = 'الأول';
        } elseif ($term === '2') {
            $arabic_term = 'الثاني';
        }
    ?>
    <div class="certificate-container">
        <div class="header-section">
            <div class="ministry-info">
                <p>المملكة العربية السعودية</p>
                <p>إدارة الإشراف على المقرات الخيرية</p>
                <p>مركز الجود لتعليم الكتاب والسنة</p>
                <p>منطقة جازان (مدينة جازان)</p>
            </div>
            <div class="center-info">
                <img src="logo.png" alt="شعار المركز" class="logo">
            </div>
            <div class="ministry-info">
                </div>
        </div>

        <div class="title-section">
            <h3>شهادة طالب</h3>
        </div>

        <div class="student-details">
            <p>
                المكرم ولي أمر الطالب: 
                <span class="highlight"><?= htmlspecialchars($student['اسم_الطالب']) ?></span>
                بناءً على الصف <?= htmlspecialchars($grade_level) ?>.
            </p>
            <p>
                <p>
                السلام عليكم ورحمة الله وبركاته،
                يسرنا أن نرفق لكم التقرير النهائي عن مستوى الطالب في نهاية الفصل الدراسي <?= htmlspecialchars($term_name) ?> لعام <?= htmlspecialchars($academic_year) ?> ه.
            </p>
            </p>
        </div>

        <table class="grades-table">
            <thead>
                <tr>
                    <th rowspan="2">المادة</th>
                    <th rowspan="2">درجات أعمال الفصل</th>
                    <th rowspan="2">درجات منتصف الفصل</th>
                    <th rowspan="2">درجات نهاية الفصل</th>
                    <th rowspan="2">المجموع</th>
                    <th rowspan="2">التقدير</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($student_grades_by_subject as $subject_name => $grades): ?>
                <?php
                    // Calculation for 'درجات أعمال الفصل' (50 points)
                    $session_work_grade = floatval($grades['الاختبار_الدوري_الأول'] ?? 0) +
                                           floatval($grades['الاختبار_الدوري_الثاني'] ?? 0) +
                                           floatval($grades['الواجبات'] ?? 0) +
                                           floatval($grades['الأنشطة_وأوراق_العمل'] ?? 0) +
                                           floatval($grades['المشاركة'] ?? 0);
                    
                    $midterm_grade = floatval($grades['الاختبار_النصفي'] ?? 0);
                    $final_exam_grade = floatval($grades['الاختبار_النهائي'] ?? 0);
                    $total_subject = $session_work_grade + $midterm_grade + $final_exam_grade;
                    
                    $subject_assessment = '';
                    if ($total_subject >= 90) $subject_assessment = 'ممتاز';
                    else if ($total_subject >= 80) $subject_assessment = 'جيد جداً';
                    else if ($total_subject >= 70) $subject_assessment = 'جيد';
                    else if ($total_subject >= 60) $subject_assessment = 'مقبول';
                    else $subject_assessment = 'ضعيف';
                ?>
                <tr>
                    <td><?= htmlspecialchars($subject_name) ?></td>
                    <td><?= htmlspecialchars($session_work_grade) ?></td>
                    <td><?= htmlspecialchars($midterm_grade) ?></td>
                    <td><?= htmlspecialchars($final_exam_grade) ?></td>
                    <td><?= htmlspecialchars($total_subject) ?></td>
                    <td><?= htmlspecialchars($subject_assessment) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="summary-section">
            <div class="summary-item">
                <div class="label">التقدير العام:</div>
                <div class="value"><?= htmlspecialchars($assessment) ?></div>
            </div>
            <div class="summary-item">
                <div class="label">النسبة المئوية:</div>
                <div class="value"><?= number_format($overall_percentage, 2) ?>%</div>
            </div>
            <div class="summary-item">
                <div class="label">النتيجة:</div>
                <div class="value"><?= htmlspecialchars($result) ?></div>
            </div>
        </div>

        <div class="signatures-section">
            <div class="signature-item">
                <p>مديرة المركز:</p>
                <p class="label">حنان الصامتي</p>
                <p>التوقيع: ................</p>
            </div>
            <div class="signature-item">
                <img src="seal.png" alt="ختم المركز" class="seal">
            </div>
            <div class="signature-item">
                <p>مرشدة الفصل:</p>
                <p class="label"><?= htmlspecialchars($class_supervisor_name) ?></p>
                <p>التوقيع: ................</p>
            </div>
        </div>
    </div>
    <?php if ($student_id === 'all' && $student !== end($students)): ?>
    <div class="page-break"></div>
    <?php endif; ?>
    <?php endforeach; ?>
</body>
</html>





