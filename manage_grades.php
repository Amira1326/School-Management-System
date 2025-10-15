<?php
session_start();

// إعدادات الاتصال بقاعدة البيانات
$dsn = 'mysql:host=localhost;dbname=aljood_center;charset=utf8';
$username = 'root';
$password = '';

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}

$selected_grade_level = $_GET['المرحلة_الدراسية'] ?? null;
$selected_فصل = $_GET['الفصل'] ?? null;
$selected_subject_name = $_GET['المادة'] ?? null;
$selected_فصل_دراسي = $_GET['الفصل_الدراسي'] ?? null;

if (!$selected_grade_level || !$selected_فصل || !$selected_subject_name || !$selected_فصل_دراسي) {
    header("Location: select_class.php?error=missing_selection");
    exit();
}

$stmt_class_id = $pdo->prepare("SELECT رقم FROM classes WHERE المرحلة_الدراسية = ? AND الفصل = ?");
$stmt_class_id->execute([$selected_grade_level, $selected_فصل]);
$class_id = $stmt_class_id->fetchColumn();

$stmt_subject_id = $pdo->prepare("SELECT id FROM subjects WHERE اسم_المادة = ?");
$stmt_subject_id->execute([$selected_subject_name]);
$subject_id = $stmt_subject_id->fetchColumn();

$stmt_students = $pdo->prepare("SELECT رقم, اسم_الطالب FROM students WHERE الفصل = ? AND المرحلة_الدراسية = ? ORDER BY اسم_الطالب ASC");
$stmt_students->execute([$selected_فصل, $selected_grade_level]);
$students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

$existing_grades = [];
$stmt_grades = $pdo->prepare("
    SELECT رقم_الطالب, الاختبار_النهائي, الغياب, السلوك, الواجبات, الأنشطة_وأوراق_العمل, المشاركة,
    الاختبار_الدوري_الأول, الاختبار_الدوري_الثاني, الاختبار_النصفي
    FROM grades
    WHERE subject_id = ? AND الفصل_الدراسي = ? AND المرحلة_الدراسية = ? AND رقم_الفصل = ?
");
$stmt_grades->execute([
    $subject_id,
    $selected_فصل_دراسي,
    $selected_grade_level,
    $class_id
]);

while ($row = $stmt_grades->fetch(PDO::FETCH_ASSOC)) {
    $existing_grades[$row['رقم_الطالب']] = $row;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>إدارة درجات الطلاب</title>
<link rel="icon" href="logo.png" type="image/x-icon" />
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet" />
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
<style>
    :root {
        --primary-color: #89355d;
        --secondary-color: #E0BBE4;
        --text-dark: #333;
        --white: #fff;
        --button-hover-color: #7b5065;
        --border-radius: 12px;
        --spacing: 1rem;
    }
    body {
        background: var(--secondary-color);
        font-family: 'Cairo', sans-serif;
        margin: 0; padding: 0;
        display: flex;
        justify-content: center;
        padding: 2rem 0;
        min-height: 100vh;
    }
    .container {
        background: var(--white);
        border-radius: var(--border-radius);
        box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        max-width: 1200px;
        width: 95%;
        padding: 2rem;
        display: flex;
        flex-direction: column;
    }
    form {
        width: 100%;
        display: flex;
        flex-direction: column;
    }
    /* رأس الفورم: شعار على اليمين والعنوان في المنتصف */
    .form-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
    }
    .form-header img {
        height: 90px;
        border-radius: 10px;
        box-shadow: none; /* حذف الظل */
    }
    .form-header h1 {
        flex-grow: 1;
        text-align: center;
        font-size: 2rem;
        color: var(--primary-color);
        font-weight: 700;
        margin: 0;
        padding: 0 1rem;
        user-select: none;
    }
    .info-bar {
        background-color: #f8f9fa;
        border-radius: var(--border-radius);
        padding: 1rem;
        display: flex;
        gap: 1rem;
        justify-content: center;
        font-weight: 700;
        color: #555;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
    }
    .info-bar span {
        white-space: nowrap;
    }
    .table-container {
        overflow-x: auto;
        border-radius: var(--border-radius);
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        min-width: 1000px;
    }
    th, td {
        border: 1px solid #f0f0f0;
        padding: 12px 15px;
        text-align: center;
        vertical-align: middle;
        white-space: nowrap;
    }
    th {
        background: #fdf6fa;
        color: var(--primary-color);
        font-weight: 700;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    tr:nth-child(even) {
        background-color: #faf8fb;
    }
    tr:hover {
        background-color: #f5f0f3;
    }
    .grade-input {
        width: 70px;
        padding: 5px;
        border-radius: 5px;
        border: 1px solid #ccc;
        background-color: #fafafa;
        text-align: center;
        font-size: 1rem;
    }
    .grade-input:focus {
        border-color: var(--primary-color);
        outline: none;
        box-shadow: 0 0 5px rgba(137,53,93,0.5);
    }
    .total-grade {
        font-weight: 700;
        background-color: #e6ffed;
        color: #28a745;
    }
    .footer-buttons {
        margin-top: 2rem;
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }
    button, .button-link {
        background-color: var(--primary-color);
        color: var(--white);
        border: none;
        padding: 12px 25px;
        border-radius: 25px;
        font-weight: 700;
        font-size: 1rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        user-select: none;
        transition: background-color 0.3s ease, transform 0.2s ease;
    }
    button:hover, .button-link:hover {
        background-color: var(--button-hover-color);
        transform: translateY(-2px);
    }
    /* رسالة نجاح */
    .alert {
        background-color: #d4edda;
        color: #155724;
        border-radius: var(--border-radius);
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
        position: relative;
        font-weight: 700;
        box-shadow: 0 2px 8px rgba(0,0,0,0.07);
    }
    .alert .close-btn {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        font-size: 1.4rem;
        opacity: 0.7;
        transition: opacity 0.2s ease;
    }
    .alert .close-btn:hover {
        opacity: 1;
    }
    @media (max-width: 768px) {
        .form-header {
            flex-direction: column-reverse;
            align-items: center;
            gap: 1rem;
        }
        .form-header h1 {
            padding: 0;
            text-align: center;
        }
        .table-container {
            overflow-x: auto;
        }
        .footer-buttons {
            flex-direction: column;
            align-items: stretch;
        }
        button, .button-link {
            width: 100%;
            justify-content: center;
        }
    }
</style>
</head>
<body>
    <div class="container">

        <?php if (isset($_GET['saved'])): ?>
        <div id="success-msg" class="alert" role="alert" aria-live="assertive">
            ✅ تم حفظ الدرجات بنجاح.
            <span class="close-btn" onclick="closeAlert()" role="button" aria-label="إغلاق الرسالة">✖</span>
        </div>
        <?php endif; ?>

        <form id="save-grades-form" action="save_grades.php" method="POST" novalidate>

            <div class="form-header" role="banner" aria-label="رأس نموذج إدخال الدرجات">
                <img src="logo.png" alt="شعار المركز" />
                <h1>إدارة درجات الطلاب</h1>
            </div>

            <div class="info-bar" role="region" aria-label="معلومات الفصل والمادة">
                <span>المرحلة الدراسية: <strong><?= htmlspecialchars($selected_grade_level) ?></strong></span>
                <span>الفصل: <strong><?= htmlspecialchars($selected_فصل) ?></strong></span>
                <span>المادة: <strong><?= htmlspecialchars($selected_subject_name) ?></strong></span>
                <span>الفصل الدراسي: <strong><?= htmlspecialchars($selected_فصل_دراسي) ?></strong></span>
            </div>

            <input type="hidden" name="subject_id" value="<?= htmlspecialchars($subject_id) ?>" />
            <input type="hidden" name="الفصل_الدراسي" value="<?= htmlspecialchars($selected_فصل_دراسي) ?>" />
            <input type="hidden" name="المرحلة_الدراسية" value="<?= htmlspecialchars($selected_grade_level) ?>" />
            <input type="hidden" name="رقم_الفصل" value="<?= htmlspecialchars($class_id) ?>" />
            <input type="hidden" name="الفصل" value="<?= htmlspecialchars($selected_فصل) ?>" />
            <input type="hidden" name="المادة" value="<?= htmlspecialchars($selected_subject_name) ?>" />

            <div class="table-container">
                <table role="grid" aria-label="جدول درجات الطلاب">
                    <thead>
                        <tr>
                            <th rowspan="2" scope="col">اسم الطالب</th>
                            <th colspan="2" scope="colgroup">اختبارات دورية (5+5)</th>
                            <th rowspan="2" scope="col">اختبار نصفي (20)</th>
                            <th rowspan="2" scope="col">واجبات (10)</th>
                            <th rowspan="2" scope="col">أنشطة (10)</th>
                            <th rowspan="2" scope="col">مشاركة (10)</th>
                            <th rowspan="2" scope="col">سلوك (5)</th>
                            <th rowspan="2" scope="col">غياب (5)</th>
                            <th rowspan="2" scope="col">اختبار نهائي (30)</th>
                            <th rowspan="2" scope="col">المجموع (100)</th>
                        </tr>
                        <tr>
                            <th scope="col">الأول</th>
                            <th scope="col">الثاني</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr><td colspan="11">لا يوجد طلاب في هذا الفصل.</td></tr>
                        <?php else: ?>
                            <?php foreach ($students as $student):
                                $student_id = $student['رقم'];
                                $grades = $existing_grades[$student_id] ?? [];
                            ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($student['اسم_الطالب']) ?>
                                        <input type="hidden" name="students[<?= $student_id ?>][student_id]" value="<?= $student_id ?>">
                                    </td>
                                    <td><input type="number" step="0.01" min="0" max="5" name="students[<?= $student_id ?>][الاختبار_الدوري_الأول]" value="<?= htmlspecialchars($grades['الاختبار_الدوري_الأول'] ?? '') ?>" class="grade-input" oninput="calculateTotal(this)"></td>
                                    <td><input type="number" step="0.01" min="0" max="5" name="students[<?= $student_id ?>][الاختبار_الدوري_الثاني]" value="<?= htmlspecialchars($grades['الاختبار_الدوري_الثاني'] ?? '') ?>" class="grade-input" oninput="calculateTotal(this)"></td>
                                    <td><input type="number" step="0.01" min="0" max="20" name="students[<?= $student_id ?>][الاختبار_النصفي]" value="<?= htmlspecialchars($grades['الاختبار_النصفي'] ?? '') ?>" class="grade-input" oninput="calculateTotal(this)"></td>
                                    <td><input type="number" step="0.01" min="0" max="10" name="students[<?= $student_id ?>][الواجبات]" value="<?= htmlspecialchars($grades['الواجبات'] ?? '') ?>" class="grade-input" oninput="calculateTotal(this)"></td>
                                    <td><input type="number" step="0.01" min="0" max="10" name="students[<?= $student_id ?>][الأنشطة_وأوراق_العمل]" value="<?= htmlspecialchars($grades['الأنشطة_وأوراق_العمل'] ?? '') ?>" class="grade-input" oninput="calculateTotal(this)"></td>
                                    <td><input type="number" step="0.01" min="0" max="10" name="students[<?= $student_id ?>][المشاركة]" value="<?= htmlspecialchars($grades['المشاركة'] ?? '') ?>" class="grade-input" oninput="calculateTotal(this)"></td>
                                    <td><input type="number" step="0.01" min="0" max="5" name="students[<?= $student_id ?>][السلوك]" value="<?= htmlspecialchars($grades['السلوك'] ?? '') ?>" class="grade-input" oninput="calculateTotal(this)"></td>
                                    <td><input type="number" step="0.01" min="0" max="5" name="students[<?= $student_id ?>][الغياب]" value="<?= htmlspecialchars($grades['الغياب'] ?? '') ?>" class="grade-input" oninput="calculateTotal(this)"></td>
                                    <td><input type="number" step="0.01" min="0" max="30" name="students[<?= $student_id ?>][الاختبار_النهائي]" value="<?= htmlspecialchars($grades['الاختبار_النهائي'] ?? '') ?>" class="grade-input" oninput="calculateTotal(this)"></td>
                                    <td class="total-grade"><span id="total-<?= $student_id ?>">0.00</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="footer-buttons" role="group" aria-label="أزرار التحكم">
                <button type="submit" name="save_grades" aria-label="حفظ الدرجات">
                    <span class="material-icons" aria-hidden="true">save</span> حفظ
                </button>
                <a href="select_class.php" class="button-link" aria-label="العودة لاختيار الفصل" role="button">
                    <span class="material-icons" aria-hidden="true">arrow_forward</span> العودة لاختيار الفصل
                </a>
                <a href="index.php" class="button-link" aria-label="العودة للرئيسية" role="button" style="background-color: #6c757d;">
                    <span class="material-icons" aria-hidden="true">home</span> الرئيسية
                </a>
            </div>

        </form>
    </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('tbody tr').forEach(row => {
        const firstInput = row.querySelector('.grade-input');
        if (firstInput) {
            calculateTotal(firstInput);
        }
    });
});

function calculateTotal(input) {
    const row = input.closest('tr');
    if (!row) return;

    const inputs = row.querySelectorAll('.grade-input');
    let total = 0;

    inputs.forEach(i => {
        const maxMap = {
            'الاختبار_الدوري_الأول': 5,
            'الاختبار_الدوري_الثاني': 5,
            'الغياب': 5,
            'السلوك': 5,
            'الواجبات': 10,
            'الأنشطة_وأوراق_العمل': 10,
            'المشاركة': 10,
            'الاختبار_النصفي': 20,
            'الاختبار_النهائي': 30
        };

        let max = 0;
        for (const key in maxMap) {
            if (i.name.includes(key)) {
                max = maxMap[key];
                break;
            }
        }

        let val = parseFloat(i.value);
        if (isNaN(val) || val < 0) val = 0;
        if (val > max) {
            i.value = max.toFixed(2);
            val = max;
        }
        total += val;
    });

    const studentId = row.querySelector('input[type=hidden]').value;
    const totalSpan = document.getElementById('total-' + studentId);
    if (totalSpan) {
        totalSpan.textContent = total.toFixed(2);
    }
}

function closeAlert() {
    const alertBox = document.getElementById('success-msg');
    if (alertBox) {
        alertBox.style.opacity = '0';
        setTimeout(() => alertBox.remove(), 500);
    }
}
setTimeout(closeAlert, 5000);
</script>
</body>
</html>
