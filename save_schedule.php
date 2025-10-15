<?php
session_start();

// معلومات الاتصال بقاعدة البيانات
$host = "localhost";
$dbname = "aljood_center";
$username = "root";
$password = "";

$conn = new mysqli($host, $username, $password, $dbname);
$conn->set_charset("utf8");
if ($conn->connect_error) {
    // إرجاع استجابة JSON للخطأ في الاتصال
    echo json_encode(['status' => 'error', 'message' => 'فشل الاتصال بقاعدة البيانات.']);
    exit();
}

// التحقق من أن المعلمة مسجلة الدخول
if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'غير مصرح لك. يرجى تسجيل الدخول.']);
    exit();
}

$teacherId = $_SESSION['teacher_id'];

// استقبال بيانات الجدول من طلب AJAX (بصيغة JSON)
$input = file_get_contents('php://input');
$scheduleData = json_decode($input, true); // تحويل نص JSON إلى مصفوفة PHP

// التحقق من صلاحية البيانات المستقبلة
if (json_last_error() !== JSON_ERROR_NONE || !is_array($scheduleData)) {
    echo json_encode(['status' => 'error', 'message' => 'بيانات الجدول غير صالحة.']);
    exit();
}

// *********** بدء معاملة (Transaction) ***********
// هذا يضمن أن يتم حذف جميع الحصص القديمة وإدراج جميع الحصص الجديدة
// كعملية واحدة متكاملة. إذا حدث أي خطأ، يتم التراجع عن كل شيء.
$conn->begin_transaction();

try {
    // 1. حذف جميع الحصص القديمة للمعلمة الحالية
    // نستخدم اسم العمود الجديد: معرف_المعلمة
    $deleteStmt = $conn->prepare("DELETE FROM `teacher_schedules` WHERE `معرف_المعلمة` = ?");
    $deleteStmt->bind_param("i", $teacherId);
    $deleteStmt->execute();
    $deleteStmt->close();

    // 2. إدراج الحصص الجديدة
    // نستخدم أسماء الأعمدة الجديدة: معرف_المعلمة, اليوم, وقت_الحصة, اسم_المادة
    $insertStmt = $conn->prepare("INSERT INTO `teacher_schedules` (`معرف_المعلمة`, `اليوم`, `وقت_الحصة`, `اسم_المادة`) VALUES (?, ?, ?, ?)");

    foreach ($scheduleData as $day => $slots) {
        foreach ($slots as $slotTime => $subjectName) {
            // قم بحفظ الحصة فقط إذا تم اختيار مادة لها (أي ليست فارغة)
            if (!empty($subjectName)) {
                $insertStmt->bind_param("isss", $teacherId, $day, $slotTime, $subjectName);
                $insertStmt->execute();
            }
        }
    }
    $insertStmt->close();

    // إذا وصلت إلى هنا، فهذا يعني أن كل شيء سار بنجاح.
    $conn->commit(); // تثبيت التغييرات في قاعدة البيانات
    echo json_encode(['status' => 'success', 'message' => '✅ تم حفظ الجدول بنجاح!']);

} catch (Exception $e) {
    // إذا حدث أي خطأ، قم بإلغاء جميع التغييرات التي تمت في هذه المعاملة
    $conn->rollback();
    error_log("خطأ في حفظ الجدول للمعلمة ID: " . $teacherId . " - " . $e->getMessage()); // سجل الخطأ في سجلات الخادم للمراجعة
    echo json_encode(['status' => 'error', 'message' => '❌ فشل حفظ الجدول: ' . $e->getMessage()]);
}

$conn->close(); // إغلاق اتصال قاعدة البيانات
?>