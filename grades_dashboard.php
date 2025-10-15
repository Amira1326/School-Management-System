<?php
session_start();

// --- إعدادات الاتصال بقاعدة البيانات ---
$dsn = 'mysql:host=localhost;dbname=aljood_center;charset=utf8';
$username = 'root';
$password = '';
$options = [
    PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES    => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}

// --- متغيرات الرسائل ---
$error_msg = '';
$success_msg = '';

// --- دوال مساعدة ---
function get_class_id($pdo, $grade_level, $class_name) {
    $stmt = $pdo->prepare("SELECT رقم FROM classes WHERE المرحلة_الدراسية = ? AND الفصل = ?");
    $stmt->execute([$grade_level, $class_name]);
    return $stmt->fetchColumn();
}

function get_subject_id($pdo, $subject_name) {
    $stmt = $pdo->prepare("SELECT id FROM subjects WHERE اسم_المادة = ?");
    $stmt->execute([$subject_name]);
    return $stmt->fetchColumn();
}

function get_hijri_years($start_year = 1447, $count = 5) {
    $years = [];
    for ($i = 0; $i < $count; $i++) {
        $start = $start_year + $i;
        $end = $start + 1;
        $years[] = "{$start} - {$end}";
    }
    return $years;
}
$hijri_years = get_hijri_years(1447, 10); // Get 10 academic years starting from 1447


// --- معالجة حفظ الدرجات (POST request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_grades'])) {
    $grade_level = $_POST['grade_level'] ?? '';
    $class_name = $_POST['class_name'] ?? '';
    $subject_name = $_POST['subject_name'] ?? '';
    $term = $_POST['term'] ?? '';
    $students_grades = $_POST['students'] ?? [];

    if (!$grade_level || !$class_name || !$subject_name || !$term) {
        $error_msg = "البيانات غير مكتملة للحفظ.";
    } else {
        $class_id = get_class_id($pdo, $grade_level, $class_name);
        $subject_id = get_subject_id($pdo, $subject_name);

        if (!$class_id || !$subject_id) {
            $error_msg = "البيانات المدخلة (المرحلة، الفصل، أو المادة) غير صحيحة.";
        } else {
            $sql = "
                INSERT INTO grades (
                    رقم_الطالب, subject_id, الفصل_الدراسي, المرحلة_الدراسية, رقم_الفصل,
                    الاختبار_النهائي, الغياب, السلوك, الواجبات, الأنشطة_وأوراق_العمل, المشاركة,
                    الاختبار_الدوري_الأول, الاختبار_الدوري_الثاني, الاختبار_النصفي
                ) VALUES (
                    :student_id, :subject_id, :term, :grade_level, :class_id,
                    :final_exam, :absence, :behavior, :homework, :activities, :participation,
                    :first_test, :second_test, :midterm
                )
                ON DUPLICATE KEY UPDATE
                    الاختبار_النهائي = VALUES(الاختبار_النهائي), الغياب = VALUES(الغياب),
                    السلوك = VALUES(السلوك), الواجبات = VALUES(الواجبات),
                    الأنشطة_وأوراق_العمل = VALUES(الأنشطة_وأوراق_العمل), المشاركة = VALUES(المشاركة),
                    الاختبار_الدوري_الأول = VALUES(الاختبار_الدوري_الأول),
                    الاختبار_الدوري_الثاني = VALUES(الاختبار_الدوري_الثاني),
                    الاختبار_النصفي = VALUES(الاختبار_النصفي)
            ";
            $insert_stmt = $pdo->prepare($sql);

            $pdo->beginTransaction();
            try {
                foreach ($students_grades as $student_id => $grades) {
                    $params = [
                        ':student_id'      => $student_id,
                        ':subject_id'      => $subject_id,
                        ':term'            => $term,
                        ':grade_level'     => $grade_level,
                        ':class_id'        => $class_id,
                        ':final_exam'      => min(max(floatval($grades['final_exam'] ?? 0), 0), 30),
                        ':absence'         => min(max(floatval($grades['absence'] ?? 0), 0), 5),
                        ':behavior'        => min(max(floatval($grades['behavior'] ?? 0), 0), 5),
                        ':homework'        => min(max(floatval($grades['homework'] ?? 0), 0), 10),
                        ':activities'      => min(max(floatval($grades['activities'] ?? 0), 0), 10),
                        ':participation'   => min(max(floatval($grades['participation'] ?? 0), 0), 10),
                        ':first_test'      => min(max(floatval($grades['first_test'] ?? 0), 0), 5),
                        ':second_test'     => min(max(floatval($grades['second_test'] ?? 0), 0), 5),
                        ':midterm'         => min(max(floatval($grades['midterm'] ?? 0), 0), 20),
                    ];
                    $insert_stmt->execute($params);
                }
                $pdo->commit();
                $success_msg = "تم حفظ الدرجات بنجاح.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error_msg = "حدث خطأ أثناء حفظ الدرجات: " . $e->getMessage();
            }
        }
    }
}

// --- جلب البيانات للعرض (GET request) ---
$grade_level = $_GET['grade_level'] ?? '';
$class_name = $_GET['class_name'] ?? '';
$subject_name = $_GET['subject_name'] ?? '';
$term = $_GET['term'] ?? '';

$grade_levels_stmt = $pdo->query("SELECT DISTINCT المرحلة_الدراسية FROM classes ORDER BY المرحلة_الدراسية ASC");
$grade_levels = $grade_levels_stmt->fetchAll(PDO::FETCH_COLUMN);

$class_names = [];
if ($grade_level) {
    $stmt = $pdo->prepare("SELECT DISTINCT الفصل FROM classes WHERE المرحلة_الدراسية = ? ORDER BY الفصل ASC");
    $stmt->execute([$grade_level]);
    $class_names = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$all_subjects_stmt = $pdo->query("SELECT اسم_المادة FROM subjects ORDER BY اسم_المادة ASC");
$all_subjects = $all_subjects_stmt->fetchAll(PDO::FETCH_COLUMN);

$students = [];
$existing_grades = [];
if ($grade_level && $class_name && $subject_name && $term) {
    $class_id = get_class_id($pdo, $grade_level, $class_name);
    $subject_id = get_subject_id($pdo, $subject_name);

    if ($class_id && $subject_id) {
        $stmt_students = $pdo->prepare("SELECT رقم, اسم_الطالب FROM students WHERE الفصل = ? AND المرحلة_الدراسية = ? ORDER BY اسم_الطالب ASC");
        $stmt_students->execute([$class_name, $grade_level]);
        $students = $stmt_students->fetchAll();

        $stmt_grades = $pdo->prepare("
            SELECT رقم_الطالب, الاختبار_النهائي, الغياب, السلوك, الواجبات, الأنشطة_وأوراق_العمل, المشاركة,
            الاختبار_الدوري_الأول, الاختبار_الدوري_الثاني, الاختبار_النصفي
            FROM grades
            WHERE subject_id = ? AND الفصل_الدراسي = ? AND المرحلة_الدراسية = ? AND رقم_الفصل = ?
        ");
        $stmt_grades->execute([$subject_id, $term, $grade_level, $class_id]);
        while ($row = $stmt_grades->fetch()) {
            $existing_grades[$row['رقم_الطالب']] = $row;
        }
    } else {
        if (!$error_msg) {
            $error_msg = "البيانات المدخلة غير صحيحة.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>إدارة الدرجات | لوحة التحكم</title>
<link rel="icon" href="logo.png" type="image/x-icon" />
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet" />
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
<style>
    :root {
        --primary-color: #89355d;
        --primary-hover: #7b2f51;
        --secondary-color: #fdf6fd;
        --accent-color: #e0bbe4;
        --success-color: #2f855a;
        --error-color: #c53030;
        --text-dark: #333;
        --text-light: #888;
        --white: #ffffff;
        --border-color: #e9d8e8;
        --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1 ), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --border-radius: 0.5rem;
        --font-family: 'Cairo', sans-serif;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: var(--font-family); background-color: var(--secondary-color); color: var(--text-dark); line-height: 1.6; }
    .page-container { max-width: 1200px; margin: 2rem auto; padding: 1rem; }
    .header { text-align: center; margin-bottom: 2.5rem; position: relative; }
    .header h1 { font-size: 2.25rem; font-weight: 700; color: var(--primary-color); }
    .header .logo { position: absolute; top: -10px; right: 0; width: 80px; opacity: 0.8; }
    .card { background-color: var(--white); border-radius: var(--border-radius); box-shadow: var(--shadow); padding: 2rem; margin-bottom: 2rem; border: 1px solid var(--border-color); }
    .filter-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.5rem; align-items: center; }
    .form-group { display: flex; flex-direction: column; }
    .form-group label { font-weight: 600; margin-bottom: 0.5rem; color: var(--text-light); font-size: 0.9rem; }
    .form-control { padding: 0.75rem 1rem; font-size: 1rem; font-family: var(--font-family); border-radius: var(--border-radius); border: 1px solid var(--border-color); background-color: #fdfdff; transition: border-color 0.2s, box-shadow 0.2s; }
    .form-control:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(137, 53, 93, 0.2); }
    .form-control:disabled { background-color: #f3eaf2; cursor: not-allowed; }
    .btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1.5rem; font-family: var(--font-family); font-size: 1rem; font-weight: 600; border-radius: var(--border-radius); border: none; cursor: pointer; transition: background-color 0.2s, transform 0.2s; text-decoration: none; white-space: nowrap; }
    .btn-primary { background-color: var(--primary-color); color: var(--white); grid-column: 1 / -1; }
    @media (min-width: 992px) { .btn-primary { grid-column: auto; align-self: end; } }
    .btn-primary:hover { background-color: var(--primary-hover); transform: translateY(-2px); }
    .btn-secondary { background-color: #f3eaf2; color: var(--primary-color); border: 1px solid var(--border-color); }
    .btn-secondary:hover { background-color: var(--accent-color); color: var(--text-dark); }
    .alert { padding: 1rem 1.5rem; margin-bottom: 1.5rem; border-radius: var(--border-radius); border: 1px solid transparent; font-weight: 600; display: flex; align-items: center; gap: 1rem; }
    .alert-success { background-color: #f0fff4; color: var(--success-color); border-color: #9ae6b4; }
    .alert-error { background-color: #fff5f5; color: var(--error-color); border-color: #fed7d7; }
    .table-container { overflow-x: auto; max-height: 60vh; }
    table { width: 100%; border-collapse: collapse; min-width: 1000px; }
    th, td { padding: 0.75rem 1rem; text-align: center; border-bottom: 1px solid var(--border-color); }
    th { background-color: var(--accent-color); font-weight: 700; color: var(--primary-color); position: sticky; top: 0; z-index: 1; }
    tbody tr:nth-child(even) { background-color: #fefbff; }
    tbody tr:hover { background-color: #f3eaf2; }
    .grade-input { width: 75px; padding: 0.5rem; font-size: 1rem; text-align: center; border-radius: var(--border-radius); border: 1px solid var(--border-color); background-color: var(--white); }
    .grade-input:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 2px rgba(137, 53, 93, 0.2); }
    .action-buttons { display: flex; flex-wrap: wrap; gap: 1rem; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color); }
    .no-data { text-align: center; font-weight: 600; padding: 3rem; color: var(--text-light); }
    .low-grade { background-color: #ffebeb !important; }
    
    .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.6); z-index: 100; display: flex; justify-content: center; align-items: center; opacity: 0; visibility: hidden; transition: opacity 0.3s, visibility 0.3s; }
    .modal-overlay.active { opacity: 1; visibility: visible; }
    .modal-content { background-color: var(--white); padding: 2rem; border-radius: var(--border-radius); box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 90%; max-width: 500px; transform: scale(0.9); transition: transform 0.3s; }
    .modal-overlay.active .modal-content { transform: scale(1); }
    .modal-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 1rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); }
    .modal-header h2 { color: var(--primary-color); font-size: 1.5rem; }
    .modal-close { background: transparent; border: none; font-size: 2rem; cursor: pointer; color: var(--text-light); }
    .modal-body .form-group { margin-bottom: 1.5rem; }
    .form-check { display: flex; align-items: center; gap: 0.5rem; margin-top: 1rem; }
    .form-check-label { font-weight: 600; color: var(--text-dark); }
    .modal-footer { display: flex; justify-content: flex-end; gap: 1rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color); margin-top: 1.5rem; }

    @media (max-width: 768px) {
        .page-container { margin: 1rem auto; }
        .header h1 { font-size: 1.75rem; }
        .header .logo { width: 60px; }
        .card { padding: 1.5rem; }
        .action-buttons { flex-direction: column; }
        .action-buttons .btn { width: 100%; }
    }
</style>
</head>
<body>

<div class="page-container">
    <header class="header">
        <img src="logo.png" alt="شعار المركز" class="logo" />
        <h1>لوحة تحكم الدرجات</h1>
    </header>

    <?php if ($error_msg): ?>
        <div class="alert alert-error" role="alert">
            <span class="material-icons">error_outline</span>
            <?= htmlspecialchars($error_msg) ?>
        </div>
    <?php endif; ?>
    <?php if ($success_msg): ?>
        <div class="alert alert-success" role="alert">
            <span class="material-icons">check_circle_outline</span>
            <?= htmlspecialchars($success_msg) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <form method="GET" class="filter-form">
            <div class="form-group">
                <label for="grade_level">المرحلة الدراسية</label>
                <select id="grade_level" name="grade_level" class="form-control" required onchange="this.form.submit()">
                    <option value="" disabled <?= !$grade_level ? 'selected' : '' ?>>اختر...</option>
                    <?php foreach ($grade_levels as $level): ?>
                        <option value="<?= htmlspecialchars($level) ?>" <?= $grade_level == $level ? 'selected' : '' ?>>
                            <?= htmlspecialchars($level) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="class_name">الفصل</label>
                <select id="class_name" name="class_name" class="form-control" required onchange="this.form.submit()" <?= !$grade_level ? 'disabled' : '' ?>>
                    <option value="" disabled <?= !$class_name ? 'selected' : '' ?>>اختر...</option>
                    <?php foreach ($class_names as $cls): ?>
                        <option value="<?= htmlspecialchars($cls) ?>" <?= $class_name == $cls ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cls) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="subject_name">المادة</label>
                <select id="subject_name" name="subject_name" class="form-control" required <?= !$class_name ? 'disabled' : '' ?>>
                    <option value="" disabled <?= !$subject_name ? 'selected' : '' ?>>اختر...</option>
                    <?php foreach ($all_subjects as $subject): ?>
                        <option value="<?= htmlspecialchars($subject) ?>" <?= $subject_name == $subject ? 'selected' : '' ?>>
                            <?= htmlspecialchars($subject) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="term">الفصل الدراسي</label>
                <select id="term" name="term" class="form-control" required <?= !$subject_name ? 'disabled' : '' ?>>
                    <option value="" disabled <?= !$term ? 'selected' : '' ?>>اختر...</option>
                    <option value="الأول" <?= $term == 'الأول' ? 'selected' : '' ?>>الأول</option>
                    <option value="الثاني" <?= $term == 'الثاني' ? 'selected' : '' ?>>الثاني</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">
                <span class="material-icons">search</span> عرض
            </button>
        </form>
    </div>

    <?php if ($grade_level && $class_name && $subject_name && $term && !$error_msg): ?>
        <div class="card">
            <?php if (empty($students)): ?>
                <div class="no-data">لا يوجد طلاب لعرضهم. يرجى التحقق من معايير البحث.</div>
            <?php else: ?>
            <form method="POST">
                <input type="hidden" name="grade_level" value="<?= htmlspecialchars($grade_level) ?>" />
                <input type="hidden" name="class_name" value="<?= htmlspecialchars($class_name) ?>" />
                <input type="hidden" name="subject_name" value="<?= htmlspecialchars($subject_name) ?>" />
                <input type="hidden" name="term" value="<?= htmlspecialchars($term) ?>" />
                
                <div class="table-container">
                    <table id="gradesTable">
                        <thead>
                            <tr>
                                <th scope="col">اسم الطالب</th>
                                <th scope="col">دوري أول (5)</th>
                                <th scope="col">دوري ثاني (5)</th>
                                <th scope="col">نصفي (20)</th>
                                <th scope="col">واجبات (10)</th>
                                <th scope="col">أنشطة (10)</th>
                                <th scope="col">مشاركة (10)</th>
                                <th scope="col">سلوك (5)</th>
                                <th scope="col">غياب (5)</th>
                                <th scope="col">نهائي (30)</th>
                                <th scope="col">المجموع (100)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student):
                                $sid = $student['رقم'];
                                $grades = $existing_grades[$sid] ?? [];
                                $total_grade = 
                                    floatval($grades['الاختبار_الدوري_الأول'] ?? 0) +
                                    floatval($grades['الاختبار_الدوري_الثاني'] ?? 0) +
                                    floatval($grades['الاختبار_النصفي'] ?? 0) +
                                    floatval($grades['الواجبات'] ?? 0) +
                                    floatval($grades['الأنشطة_وأوراق_العمل'] ?? 0) +
                                    floatval($grades['المشاركة'] ?? 0) +
                                    floatval($grades['السلوك'] ?? 0) +
                                    floatval($grades['الغياب'] ?? 0) +
                                    floatval($grades['الاختبار_النهائي'] ?? 0);
                                $row_class = ($total_grade < 50) ? 'low-grade' : '';
                            ?>
                            <tr class="<?= $row_class ?>" data-total-grade="<?= $total_grade ?>">
                                <td><?= htmlspecialchars($student['اسم_الطالب']) ?></td>
                                <td><input class="grade-input" type="number" step="0.1" min="0" max="5" name="students[<?= $sid ?>][first_test]" value="<?= htmlspecialchars($grades['الاختبار_الدوري_الأول'] ?? '') ?>" /></td>
                                <td><input class="grade-input" type="number" step="0.1" min="0" max="5" name="students[<?= $sid ?>][second_test]" value="<?= htmlspecialchars($grades['الاختبار_الدوري_الثاني'] ?? '') ?>" /></td>
                                <td><input class="grade-input" type="number" step="0.1" min="0" max="20" name="students[<?= $sid ?>][midterm]" value="<?= htmlspecialchars($grades['الاختبار_النصفي'] ?? '') ?>" /></td>
                                <td><input class="grade-input" type="number" step="0.1" min="0" max="10" name="students[<?= $sid ?>][homework]" value="<?= htmlspecialchars($grades['الواجبات'] ?? '') ?>" /></td>
                                <td><input class="grade-input" type="number" step="0.1" min="0" max="10" name="students[<?= $sid ?>][activities]" value="<?= htmlspecialchars($grades['الأنشطة_وأوراق_العمل'] ?? '') ?>" /></td>
                                <td><input class="grade-input" type="number" step="0.1" min="0" max="10" name="students[<?= $sid ?>][participation]" value="<?= htmlspecialchars($grades['المشاركة'] ?? '') ?>" /></td>
                                <td><input class="grade-input" type="number" step="0.1" min="0" max="5" name="students[<?= $sid ?>][behavior]" value="<?= htmlspecialchars($grades['السلوك'] ?? '') ?>" /></td>
                                <td><input class="grade-input" type="number" step="0.1" min="0" max="5" name="students[<?= $sid ?>][absence]" value="<?= htmlspecialchars($grades['الغياب'] ?? '') ?>" /></td>
                                <td><input class="grade-input" type="number" step="0.1" min="0" max="30" name="students[<?= $sid ?>][final_exam]" value="<?= htmlspecialchars($grades['الاختبار_النهائي'] ?? '') ?>" /></td>
                                <td class="total-grade-cell"><?= htmlspecialchars($total_grade) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="action-buttons">
                    <button type="submit" name="save_grades" class="btn btn-primary">
                        <span class="material-icons">save</span> حفظ التعديلات
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="sortStudentsByTotalGrade('desc')">
                        <span class="material-icons">trending_up</span> فرز أعلى درجة
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="sortStudentsByTotalGrade('asc')">
                        <span class="material-icons">trending_down</span> فرز أقل درجة
                    </button>
                    <button type="button" id="openPrintModalBtn" class="btn btn-secondary">
                        <span class="material-icons">print</span> طباعة متقدمة
                    </button>
                    <button type="button" onclick="exportTableToExcel('gradesTable', 'درجات_الطلاب')" class="btn btn-secondary">
                        <span class="material-icons">file_download</span> تصدير Excel
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <span class="material-icons">home</span> الرئيسية
                    </a>
                </div>
            </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<div id="printModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h2>خيارات الطباعة</h2>
            <button id="closePrintModalBtn" class="modal-close">&times;</button>
        </div>
        <form id="printForm" target="_blank" method="GET" action="print.php">
            <div class="modal-body">
                <div class="form-group">
                    <label for="print_type">نوع التقرير</label>
                    <select id="print_type" name="type" class="form-control">
                        <option value="midterm_report">تقرير الشهادة النصفية</option>
                        <option value="final_report">تقرير الشهادة النهائية</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="print_student">الطالب</label>
                    <select id="print_student" name="student_id" class="form-control">
                        <option value="all">كل الطلاب</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="academic_year">العام الدراسي</label>
                    <select id="academic_year" name="academic_year" class="form-control">
                        </select>
                </div>
                <input type="hidden" name="grade_level" value="<?= htmlspecialchars($grade_level) ?>">
                <input type="hidden" name="class_name" value="<?= htmlspecialchars($class_name) ?>">
                <input type="hidden" name="subject_name" value="<?= htmlspecialchars($subject_name) ?>">
                <input type="hidden" name="term" value="<?= htmlspecialchars($term) ?>">
            </div>
            <div class="modal-footer">
                <button type="button" id="cancelPrintBtn" class="btn btn-secondary">إلغاء</button>
                <button type="submit" class="btn btn-primary">
                    <span class="material-icons">print</span> طباعة
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function exportTableToExcel(tableID, filename = '') {
    const tableSelect = document.getElementById(tableID);
    if (!tableSelect) { alert('جدول الدرجات غير موجود'); return; }
    const cleanedTable = tableSelect.cloneNode(true);
    const totalColumnHeader = cleanedTable.querySelector('thead tr th:last-child');
    if (totalColumnHeader && totalColumnHeader.innerText.trim() === 'المجموع (100)') {
        totalColumnHeader.remove();
        cleanedTable.querySelectorAll('tbody tr').forEach(row => {
            const lastCell = row.querySelector('td:last-child');
            if (lastCell) lastCell.remove();
        });
    }

    const dataType = 'application/vnd.ms-excel';
    const template = `<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><meta charset="UTF-8"></head><body><table>${cleanedTable.outerHTML}</table></body></html>`;
    const base64 = (s) => window.btoa(unescape(encodeURIComponent(s)));
    const format = (s, c) => s.replace(/{(\w+)}/g, (m, p) => c[p]);
    const ctx = { worksheet: filename || 'Worksheet', table: tableSelect.innerHTML };
    const finalFilename = filename ? filename + '.xls' : 'grades_export.xls';
    const link = document.createElement('a');
    link.download = finalFilename;
    link.href = 'data:' + dataType + ';base64,' + base64(format(template, ctx));
    link.click();
}

function sortStudentsByTotalGrade(direction) {
    const tableBody = document.querySelector('#gradesTable tbody');
    const rows = Array.from(tableBody.querySelectorAll('tr'));

    rows.sort((a, b) => {
        const gradeA = parseFloat(a.getAttribute('data-total-grade'));
        const gradeB = parseFloat(b.getAttribute('data-total-grade'));
        
        if (direction === 'asc') {
            return gradeA - gradeB;
        } else {
            return gradeB - gradeA;
        }
    });

    tableBody.innerHTML = '';
    rows.forEach(row => tableBody.appendChild(row));
}

document.addEventListener('DOMContentLoaded', function() {
    const openModalBtn = document.getElementById('openPrintModalBtn');
    const closeModalBtn = document.getElementById('closePrintModalBtn');
    const cancelBtn = document.getElementById('cancelPrintBtn');
    const modal = document.getElementById('printModal');
    const studentSelect = document.getElementById('print_student');
    const academicYearSelect = document.getElementById('academic_year');

    const studentsData = <?= json_encode($students) ?>;
    const hijriYearsData = <?= json_encode($hijri_years) ?>;
    
    // Update the total grade cell whenever an input is changed
    document.querySelectorAll('.grade-input').forEach(input => {
        input.addEventListener('input', updateSingleTotalGrade);
    });

    function updateSingleTotalGrade(event) {
        const row = event.target.closest('tr');
        if (!row) return;

        let total = 0;
        const inputs = row.querySelectorAll('.grade-input');
        inputs.forEach(input => {
            total += parseFloat(input.value) || 0;
        });

        const totalCell = row.querySelector('.total-grade-cell');
        if (totalCell) {
            totalCell.textContent = total.toFixed(1);
            row.setAttribute('data-total-grade', total.toFixed(1));
            
            if (total < 50) {
                row.classList.add('low-grade');
            } else {
                row.classList.remove('low-grade');
            }
        }
    }

    if (openModalBtn) {
        openModalBtn.addEventListener('click', function() {
            // Populate student options
            studentSelect.innerHTML = '<option value="all">كل الطلاب</option>';
            if (studentsData && studentsData.length > 0) {
                studentsData.forEach(student => {
                    const option = document.createElement('option');
                    option.value = student.رقم;
                    option.textContent = student.اسم_الطالب;
                    studentSelect.appendChild(option);
                });
            }
            
            // Populate academic year options
            academicYearSelect.innerHTML = ''; // Clear previous options
            hijriYearsData.forEach(year => {
                const option = document.createElement('option');
                option.value = year;
                option.textContent = year;
                academicYearSelect.appendChild(option);
            });

            modal.classList.add('active');
        });
    }

    function closeModal() {
        modal.classList.remove('active');
    }

    closeModalBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });
});
</script>

</body>
</html>