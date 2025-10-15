<?php
// التحقق مما إذا كانت الجلسة غير نشطة قبل البدء
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// تفاصيل الاتصال بقاعدة البيانات
$conn = new mysqli("localhost", "root", "", "aljood_center");
$conn->set_charset("utf8"); // تعيين ترميز الأحرف إلى UTF-8 لدعم اللغة العربية

// التحقق من نجاح الاتصال بقاعدة البيانات
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

// معالجة طلب حذف معلمة
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']); // تحويل الـ ID إلى عدد صحيح لمنع حقن SQL

    // الخطوة 1: حذف جميع سجلات الغياب المرتبطة بالمعلمة أولاً
    // هذا يمنع أخطاء Integrity Constraint عند حذف المعلمة إذا كانت مرتبطة بسجلات غياب
    $conn->query("DELETE FROM teacher_absences WHERE `معرف_المعلمة` = $id");

    // الخطوة 2: الآن يمكن حذف المعلمة من جدول المعلمات
    $conn->query("DELETE FROM teachers WHERE `id` = $id"); 
    
    // إعادة التوجيه لمنع إعادة إرسال طلب الحذف عند تحديث الصفحة
    header("Location: teachers.php?deleted=1");
    exit; // إنهاء السكريبت بعد إعادة التوجيه
}

// معالجة طلب إضافة معلمة جديدة
if (isset($_POST['add_teacher'])) { 
    // استخدام PreparedStatement لمنع حقن SQL ولتحسين الأداء
    // **ترتيب الأعمدة هنا يجب أن يطابق ترتيب القيم المرسلة في bind_param لاحقًا.**
    $stmt = $conn->prepare("INSERT INTO teachers (`اسم_المعلمة`,`رائدة الصف`,`المرحلة_الدراسية`,`الفصل`,`رقم الجوال`,`تاريخ الإضافة`) VALUES (?,?,?,?,?,CURDATE())");
    // "sssss" تحدد أنواع البيانات للمتغيرات: خمس سلاسل نصية (string)
    // **ترتيب المتغيرات هنا يجب أن يطابق ترتيب الأعمدة في الاستعلام أعلاه.**
    $stmt->bind_param("sssss", $_POST['اسم_المعلمة'], $_POST['رائدة_الصف'], $_POST['المرحلة_الدراسية'], $_POST['الفصل'], $_POST['رقم_الجوال']);
    $stmt->execute(); // تنفيذ الاستعلام

    header("Location: teachers.php?saved=added"); 
    exit;
}

// معالجة طلب تعديل بيانات معلمة موجودة
if (isset($_POST['edit_teacher'])) {
    // استخدام PreparedStatement لمنع حقن SQL
    // **ترتيب الأعمدة هنا يجب أن يطابق ترتيب القيم المرسلة في bind_param لاحقًا.**
    $stmt = $conn->prepare("UPDATE teachers SET `اسم_المعلمة`=?, `رائدة الصف`=?, `المرحلة_الدراسية`=?, `الفصل`=?, `رقم الجوال`=? WHERE `id`=?");
    // "sssssi" تحدد أنواع البيانات للمتغيرات: خمس سلاسل نصية (string) وعدد صحيح (integer) للـ ID
    // **ترتيب المتغيرات هنا يجب أن يطابق ترتيب الأعمدة في الاستعلام أعلاه.**
    $stmt->bind_param("sssssi", $_POST['اسم_المعلمة'], $_POST['رائدة_الصف'], $_POST['المرحلة_الدراسية'], $_POST['الفصل'], $_POST['رقم_الجوال'], $_POST['id']);
    $stmt->execute(); // تنفيذ الاستعلام
    header("Location: teachers.php?saved=updated");
    exit;
}

// جلب بيانات معلمة معينة للتعديل (إذا كان هناك طلب تعديل في الـ URL)
$edit = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $res = $conn->query("SELECT * FROM teachers WHERE `id` = $id");
    $edit = $res->fetch_assoc(); // جلب الصف كـ associative array
}

// إعداد شرط البحث
$where = "";
if (!empty($_GET['keyword'])) {
    $k = "%" . $conn->real_escape_string($_GET['keyword']) . "%"; // إعداد الكلمة المفتاحية للبحث الآمن
    // البحث في حقول الاسم، رقم الجوال، المرحلة الدراسية، و الفصل
    $where = "WHERE `اسم_المعلمة` LIKE '$k' OR `رقم الجوال` LIKE '$k' OR `المرحلة_الدراسية` LIKE '$k' OR `الفصل` LIKE '$k'";
}

// جلب جميع بيانات المعلمات أو نتائج البحث
// **ترتيب الأعمدة في استعلام SELECT هنا لا يهم لترتيب العرض
// ولكن يجب أن تكون جميع الأعمدة المطلوبة موجودة.**
$result = $conn->query("SELECT `id`, `اسم_المعلمة`, `رائدة الصف`, `الفصل`, `المرحلة_الدراسية`, `رقم الجوال`, `تاريخ الإضافة` FROM teachers $where ORDER BY `id` DESC");

// تصدير البيانات إلى Excel
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    // إعداد رؤوس HTTP لتنزيل ملف Excel
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=teachers.xls");
    
    // بدء جدول HTML الذي سيتم تحويله إلى Excel. **هنا نحدد ترتيب الرؤوس لـ Excel بالترتيب المطلوب.**
    echo "<table border='1'><tr><th>اسم المعلمة</th><th>رائدة الصف</th><th>المرحلة الدراسية</th><th>الفصل</th><th>رقم الجوال</th><th>تاريخ الإضافة</th><th>ID</th></tr>";
    
    // جلب جميع المعلمات لغرض التصدير
    $res = $conn->query("SELECT `id`, `اسم_المعلمة`, `رائدة الصف`, `الفصل`, `المرحلة_الدراسية`, `رقم الجوال`, `تاريخ الإضافة` FROM teachers ORDER BY `id` DESC");
    
    // تعبئة الجدول ببيانات المعلمات. **هنا نحدد ترتيب البيانات لـ Excel بالترتيب المطلوب.**
    while ($r = $res->fetch_assoc()) {
        echo "<tr><td>" . htmlspecialchars($r['اسم_المعلمة'] ?? '') . "</td><td>" . htmlspecialchars($r['رائدة الصف'] ?? '') . "</td><td>" . htmlspecialchars($r['المرحلة_الدراسية'] ?? '') . "</td><td>" . htmlspecialchars($r['الفصل'] ?? '') . "</td><td>" . htmlspecialchars($r['رقم الجوال'] ?? '') . "</td><td>" . htmlspecialchars($r['تاريخ الإضافة'] ?? '') . "</td><td>{$r['id']}</td></tr>";
    }
    echo "</table>"; // إغلاق جدول HTML
    exit; // إنهاء السكريبت بعد التصدير
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قائمة المعلمات</title>
    <link rel="icon" href="logo.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        /* تعريف المتغيرات لألوان و مسافات CSS */
        :root {
            --primary-color: #89355d; /* اللون الأساسي: بنفسجي غامق */
            --secondary-color: #E0BBE4; /* لون ثانوي: بنفسجي فاتح */
            --text-dark: #333; /* لون النص الغامق */
            --text-medium: #555; /* لون النص المتوسط */
            --white: #ffffff; /* اللون الأبيض */
            --button-hover-color: #7b5065; /* لون الأزرار عند التحويم */
            --shadow-light: rgba(0, 0, 0, 0.08); /* ظل خفيف */
            --border-radius-pill: 25px; /* نصف قطر للحواف الدائرية (شكل حبة الدواء) */
            --spacing-small: 1rem;      /* 16px */
            --spacing-medium: 1.25rem;  /* 20px */
            --spacing-large: 1.875rem;  /* 30px */
            --spacing-xl: 3.125rem;     /* 50px */
        }

        /* الأساسيات لضمان امتداد الخلفية على كامل الصفحة */
        html {
            height: 100%; /* تأكد من أن الـ html يمتد على كامل ارتفاع النافذة */
            margin: 0;
            padding: 0;
            /* تطبيق التدرج اللوني على html وجعله ثابتًا */
            background: linear-gradient(to bottom, var(--white) 0%, var(--secondary-color) 100%);
            background-attachment: fixed; /* يجعل الخلفية ثابتة أثناء التمرير */
        }

        body {
            min-height: 100vh; /* تأكد من أن الـ body يأخذ على الأقل ارتفاع الشاشة الكامل */
            margin: 0;
            padding: 0;
            font-family: 'Cairo', sans-serif; /* الخط الأساسي للصفحة */
            line-height: 1.6; /* تباعد الأسطر */
            color: var(--text-dark); /* لون النص الافتراضي */
            font-size: 1rem; /* حجم الخط الأساسي */
        }

        .container {
            width: 95%; /* عرض الحاوية */
            max-width: 1200px; /* أقصى عرض للحاوية */
            margin: var(--spacing-xl) auto; /* تعيين مسافات علوية وسفلية للمحتوى الرئيسي وتوسيعه أفقيًا */
            padding: var(--spacing-large); /* مسافات داخلية للحاوية */
            background: var(--white); /* خلفية بيضاء */
            border-radius: 12px; /* حواف دائرية */
            box-shadow: 0 8px 24px var(--shadow-light); /* ظل للحاوية */
            position: relative; /* مهم لـ position: absolute للشعار */
            z-index: 1; /* تأكد أن الحاوية فوق الخلفية */
        }

        .logo {
            position: absolute;
            top: var(--spacing-medium);
            right: var(--spacing-medium); /* تم التعديل ليكون على اليمين ليتوافق مع RTL */
            width: 100px; /* عرض الشعار */
            height: auto; /* ارتفاع تلقائي للحفاظ على نسبة العرض إلى الارتفاع */
            opacity: 0.9; /* شفافية خفيفة */
            z-index: 10; /* تأكد أن الشعار يظهر فوق أي عناصر أخرى */
        }

        h1 {
            text-align: center; /* توسيط العنوان */
            color: var(--primary-color); /* لون العنوان */
            margin-bottom: var(--spacing-large); /* مسافة سفلية */
            font-size: 2.2rem; /* حجم الخط */
            font-weight: 700; /* وزن الخط (عريض) */
        }

        .filters, .form-section {
            display: flex; /* استخدام فليكس بوكس لترتيب العناصر */
            flex-wrap: wrap; /* السماح للعناصر بالانتقال إلى سطر جديد */
            justify-content: center; /* توسيط العناصر أفقياً */
            gap: var(--spacing-small); /* مسافة بين العناصر */
            margin-bottom: var(--spacing-large); /* مسافة سفلية */
        }
        
        .filters {
            padding-bottom: var(--spacing-small); /* مسافة سفلية داخلية */
            border-bottom: 1px solid #eee; /* خط فاصل سفلي */
        }

        .form-section {
            background-color: #fcf8fc; /* لون خلفية خفيف */
            padding: var(--spacing-large); /* مسافات داخلية */
            border-radius: 10px; /* حواف دائرية */
            box-shadow: inset 0 0 5px rgba(0,0,0,0.03); /* ظل داخلي خفيف */
            margin-top: var(--spacing-large); /* مسافة علوية */
        }

        .footer-buttons {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-start; /* محاذاة الأزرار لليسار (مع RTL ستكون على اليمين) */
            gap: var(--spacing-small);
            margin-top: var(--spacing-large);
            direction: rtl; /* تحديد الاتجاه من اليمين لليسار */
            padding-top: var(--spacing-small);
            border-top: 1px solid #eee; /* خط فاصل علوي */
        }

        input[type="text"], select {
            padding: 12px 15px; /* مسافة داخلية */
            border-radius: 8px; /* حواف دائرية */
            border: 1px solid #ddd; /* حدود */
            min-width: 200px; /* أدنى عرض */
            flex-grow: 1; /* السماح للعناصر بالتمدد */
            font-size: 1rem; /* حجم الخط */
            color: var(--text-dark); /* لون النص */
            transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out; /* انتقال سلس عند التركيز */
            font-family: 'Cairo', sans-serif; /* خط النص */
        }

        input[type="text"]:focus, select:focus {
            border-color: var(--primary-color); /* تغيير لون الحدود عند التركيز */
            box-shadow: 0 0 0 3px rgba(137, 53, 93, 0.1); /* ظل عند التركيز */
            outline: none; /* إزالة الخط الأزرق الافتراضي عند التركيز */
        }

        button, .button-link {
            background-color: var(--primary-color); /* لون الخلفية */
            color: var(--white); /* لون النص */
            border: none; /* بلا حدود */
            padding: 12px 25px; /* مسافة داخلية */
            border-radius: var(--border-radius-pill); /* حواف دائرية */
            cursor: pointer; /* مؤشر اليد */
            font-size: 1rem; /* حجم الخط */
            font-weight: bold; /* خط عريض */
            transition: background-color 0.3s ease, transform 0.2s ease; /* انتقال سلس عند التحويم */
            display: inline-flex; /* عرض كفليكس لسهولة توسيط الأيقونات */
            align-items: center; /* توسيط العناصر عمودياً */
            gap: 8px; /* مسافة بين النص والأيقونة */
            white-space: nowrap; /* منع النص من الالتفاف */
        }

        button:hover, .button-link:hover {
            background-color: var(--button-hover-color); /* لون أغمق عند التحويم */
            transform: translateY(-2px); /* رفع الزر قليلاً عند التحويم */
        }
        
        .button-link {
            text-decoration: none; /* إزالة خط التسطير للروابط */
        }

        table {
            width: 100%; /* عرض الجدول بالكامل */
            border-collapse: separate; /* فصل الحدود بين الخلايا */
            border-spacing: 0; /* إزالة المسافة بين الخلايا */
            margin-top: var(--spacing-large); /* مسافة علوية */
            background: var(--white); /* خلفية بيضاء */
            border-radius: 10px; /* حواف دائرية */
            overflow: hidden; /* إخفاء أي محتوى يتجاوز الحدود الدائرية */
            box-shadow: 0 4px 12px var(--shadow-light); /* ظل للجدول */
        }

        th, td {
            border: 1px solid #f0f0f0; /* حدود خفيفة بين الخلايا */
            padding: 12px 15px; /* مسافة داخلية للخلايا */
            text-align: center; /* توسيط النص */
            vertical-align: middle; /* توسيط المحتوى عمودياً */
        }

        th {
            background: #fdf6fa; /* خلفية رؤوس الجدول */
            color: var(--primary-color); /* لون نص رؤوس الجدول */
            font-weight: 700; /* وزن الخط (عريض) */
            font-size: 1.05rem; /* حجم الخط */
            position: sticky; /* جعل الرؤوس ثابتة عند التمرير */
            top: 0; /* تثبيتها في الأعلى */
            z-index: 10; /* التأكد من أنها فوق المحتوى الآخر */
        }

        tr:nth-child(even) {
            background-color: #fdfbff; /* لون خلفية للصفوف الزوجية */
        }

        tr:hover {
            background-color: #f5f0f3; /* تغيير لون خلفية الصف عند التحويم */
        }

        td button {
            padding: 8px 12px; /* مسافة داخلية للأزرار داخل الخلايا */
            font-size: 0.9rem; /* حجم خط أصغر */
            border-radius: 6px; /* حواف دائرية أصغر */
            gap: 5px; /* مسافة بين الأيقونة والنص */
        }
        
        td button.delete-btn {
            background-color: #e74c3c; /* لون أحمر لزر الحذف */
        }

        td button.delete-btn:hover {
            background-color: #c0392b; /* لون أحمر أغمق عند التحويم */
        }

        .material-icons {
            font-size: 1.2rem; /* حجم أيقونات Material Icons */
            vertical-align: middle; /* محاذاة عمودية للأيقونات */
        }

        /* رسائل التنبيه (Alert Messages) */
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
            transition: opacity 0.5s ease-out; /* انتقال سلس لظهور/اختفاء الرسالة */
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .alert.success {
            background-color: #e6ffed; /* خلفية خضراء فاتحة للنجاح */
            color: #28a745; /* نص أخضر للنجاح */
            border-color: #c9f0d1; /* حدود خضراء للنجاح */
        }

        .alert.warning {
            background-color: #fff8e6; /* خلفية صفراء فاتحة للتحذير */
            color: #ffc107; /* نص أصفر للتحذير */
            border-color: #ffeeb2; /* حدود صفراء للتحذير */
        }

        .close-btn {
            position: absolute;
            top: 10px;
            left: 15px; /* تم التعديل ليكون على اليسار ليتوافق مع RTL */
            cursor: pointer;
            font-size: 1.5rem;
            line-height: 1;
            opacity: 0.7;
            transition: opacity 0.2s;
        }

        .close-btn:hover {
            opacity: 1;
        }

        /* أنماط الطباعة */
        @media print {
            body * {
                visibility: hidden; /* إخفاء كل شيء في الصفحة عند الطباعة */
            }
            .container, .container * {
                visibility: visible; /* إظهار فقط الحاوية ومحتوياتها */
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
                float: right; /* تعديل ليتوافق مع تخطيط الطباعة */
                margin-bottom: var(--spacing-medium);
            }
            h1 {
                text-align: right;
                margin-right: 150px; /* مسافة للشعار */
            }
            .filters, .form-section, .footer-buttons, .alert, td button {
                display: none; /* إخفاء عناصر التحكم والأزرار عند الطباعة */
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
                background: #f0f0f0 !important; /* ضمان طباعة الخلفية */
                color: #333 !important;
            }
        }

        /* تعديلات للعرض المتجاوب (Responsive) على الشاشات الصغيرة */
        @media (max-width: 768px) {
            .container {
                margin: var(--spacing-medium) auto;
                padding: var(--spacing-medium);
            }
            h1 {
                font-size: 1.8rem;
            }
            .form-section, .filters, .footer-buttons {
                flex-direction: column; /* ترتيب العناصر عمودياً */
                align-items: stretch; /* تمديد العناصر لملء العرض */
            }
            input[type="text"], select, button, .button-link {
                min-width: unset;
                width: 100%; /* جعل عناصر النموذج تأخذ العرض الكامل */
            }
            .logo {
                top: 10px;
                right: 10px;
                width: 80px;
            }
            table, thead, tbody, th, td, tr {
                display: block; /* جعل عناصر الجدول تظهر ككتل (Block) */
            }
            thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px; /* إخفاء رأس الجدول الأصلي */
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
                padding-right: 50%; /* مسافة لعنصر pseudo-element */
                text-align: right; /* محاذاة النص لليمين */
            }
            td:before {
                content: attr(data-label); /* عرض التسمية من data-label */
                position: absolute;
                left: 0;
                width: 45%;
                padding-left: 15px;
                font-weight: bold;
                text-align: left;
                white-space: nowrap;
                color: var(--primary-color);
            }
            /* تحديث تسميات الخلايا لتناسب جدول المعلمات في الجوال بترتيبك الجديد */
            td:nth-of-type(1):before { content: "اسم المعلمة:"; } 
            td:nth-of-type(2):before { content: "رائدة الصف:"; }
            td:nth-of-type(3):before { content: "المرحلة الدراسية:"; }
            td:nth-of-type(4):before { content: "الفصل:"; }
            td:nth-of-type(5):before { content: "رقم الجوال:"; } 
            td:nth-of-type(6):before { content: "تاريخ الإضافة:"; }
            td:nth-of-type(7):before { content: "الرقم:"; } /* ID */
            td:nth-of-type(8):before { content: "خيارات:"; } 
            
            td:last-child { /* عمود الإجراءات */
                display: flex;
                justify-content: center;
                gap: 10px;
                padding-right: 15px; /* إعادة تعيين المساحة الداخلية */
            }
            td:last-child::before {
                content: ''; /* إخفاء عنصر pseudo-element لعمود الإجراءات */
                width: 0;
                padding: 0;
            }
            /* CSS لزر العودة للأعلى */
            #scrollToTopBtn {
                position: fixed; /* يبقى الزر في مكانه عند التمرير */
                bottom: 20px; /* 20 بكسل من الأسفل */
                left: 20px;  /* 20 بكسل من اليسار (للصفحات RTL) */
                z-index: 100; /* تأكد أنه أعلى من كل شيء آخر */
                background-color: var(--primary-color);
                color: var(--white);
                border: none;
                border-radius: 50%; /* لجعله دائرياً */
                width: 50px; /* العرض */
                height: 50px; /* الارتفاع */
                display: flex; /* لسهولة توسيط أيقونة Material Icons */
                align-items: center; /* توسيط عمودي */
                justify-content: center; /* توسيط أفقي */
                cursor: pointer;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                transition: background-color 0.3s, transform 0.3s;
            }

            #scrollToTopBtn:hover {
                background-color: var(--button-hover-color);
                transform: scale(1.05); /* تكبير طفيف عند التحويم */
            }

            #scrollToTopBtn .material-icons {
                font-size: 28px; /* حجم الأيقونة */
            }
        }
    </style>
</head>
<body>

<div class="container">
    <img src="logo.png" class="logo" alt="شعار المركز">
    <h1>قائمة المعلمات</h1>

    <?php if (isset($_GET['saved']) || isset($_GET['deleted'])): ?>
        <div id="success-msg" class="alert 
            <?php 
                if (isset($_GET['saved'])) {
                    echo ($_GET['saved'] === 'added' || $_GET['saved'] === 'updated') ? 'success' : '';
                } elseif (isset($_GET['deleted'])) {
                    echo 'warning'; 
                }
            ?>">
            <?php 
                if (isset($_GET['saved'])) {
                    echo ($_GET['saved'] === 'added' ? '✅ تم إضافة المعلمة بنجاح.' : ($_GET['saved'] === 'updated' ? '✏️ تم تحديث بيانات المعلمة.' : ''));
                } elseif (isset($_GET['deleted'])) {
                    echo '🗑️ تم حذف المعلمة.';
                }
            ?>
            <span class="close-btn" onclick="closeAlert()">✖</span>
        </div>
    <?php endif; ?>

    <form method="get" class="filters">
        <input type="text" name="keyword" placeholder="بحث بالاسم أو رقم الجوال أو المرحلة أو الفصل" value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
        <button type="submit">
            <span class="material-icons">search</span> بحث
        </button>
        <a href="teachers.php" class="button-link" style="background-color: #6c757d;">
            <span class="material-icons">clear</span> إزالة الفلتر
        </a>
    </form>

    <form method="post">
        <div class="form-section">
            <?php if ($edit): ?>
                <input type="hidden" name="id" value="<?= htmlspecialchars($edit['id'] ?? '') ?>"> 
                <input type="text" name="اسم_المعلمة" value="<?= htmlspecialchars($edit['اسم_المعلمة'] ?? '') ?>" required> 
            <?php else: ?>
                <input type="text" name="اسم_المعلمة" placeholder="اسم المعلمة" required>
            <?php endif; ?>

            <select name="رائدة_الصف" required>
                <option value="">رائدة الصف؟</option>
                <?php foreach (['نعم', 'لا'] as $option): ?>
                    <option value="<?= htmlspecialchars($option) ?>" <?= $edit && ($edit['رائدة الصف'] ?? '') === $option ? 'selected' : '' ?>><?= htmlspecialchars($option) ?></option>
                <?php endforeach; ?>
            </select>
            
            <select name="المرحلة_الدراسية" required>
                <option value="">اختر المرحلة الدراسية</option>
                <?php $stages = [
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
                foreach ($stages as $stage): ?>
                    <option value="<?= htmlspecialchars($stage) ?>" <?= $edit && ($edit['المرحلة_الدراسية'] ?? '') === $stage ? 'selected' : '' ?>><?= htmlspecialchars($stage) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="الفصل" required>
                <option value="">اختر الفصل</option>
                <?php // قائمة الفصول (أ, ب, ج)
                $classes = ['أ', 'ب', 'ج']; 
                foreach ($classes as $class): ?>
                    <option value="<?= htmlspecialchars($class) ?>" <?= $edit && ($edit['الفصل'] ?? '') === $class ? 'selected' : '' ?>><?= htmlspecialchars($class) ?></option>
                <?php endforeach; ?>
            </select>
            
            <input type="text" name="رقم_الجوال" placeholder="05xxxxxxxx (اختياري)" value="<?= $edit ? htmlspecialchars($edit['رقم الجوال'] ?? '') : '' ?>">

            <?php if ($edit): ?>
                <button type="submit" name="edit_teacher">
                    <span class="material-icons">save</span> تحديث بيانات المعلمة
                </button>
            <?php else: ?>
                <button type="submit" name="add_teacher">
                    <span class="material-icons">add</span> إضافة معلمة جديدة
                </button>
            <?php endif; ?>
        </div>
    </form>

    <table>
        <thead>
            <tr>
                <th>اسم المعلمة</th>
                <th>رائدة الصف</th>
                <th>المرحلة الدراسية</th>
                <th>الفصل</th>
                <th>رقم الجوال</th>
                <th>تاريخ الإضافة</th>
                <th>#</th>
                <th>خيارات</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td data-label="اسم المعلمة:"><?= htmlspecialchars($row['اسم_المعلمة'] ?? '') ?></td> 
                    <td data-label="رائدة الصف:"><?= htmlspecialchars($row['رائدة الصف'] ?? '') ?></td>
                    <td data-label="المرحلة الدراسية:"><?= htmlspecialchars($row['المرحلة_الدراسية'] ?? '') ?></td>
                    <td data-label="الفصل:"><?= htmlspecialchars($row['الفصل'] ?? '') ?></td>
                    <td data-label="رقم الجوال:"><?= htmlspecialchars($row['رقم الجوال'] ?? '') ?></td>
                    <td data-label="تاريخ الإضافة:"><?= htmlspecialchars($row['تاريخ الإضافة'] ?? '') ?></td>
                    <td data-label="#:"><?= htmlspecialchars($row['id'] ?? '') ?></td>
                    <td data-label="خيارات:">
                        <a href="?edit=<?= htmlspecialchars($row['id'] ?? '') ?>"> 
                            <button title="تعديل">
                                <span class="material-icons">edit</span>
                            </button>
                        </a>
                        <a href="?delete=<?= htmlspecialchars($row['id'] ?? '') ?>" onclick="return confirm('هل أنت متأكد من حذف هذه المعلمة؟ لا يمكن التراجع عن هذا الإجراء!')"> 
                            <button class="delete-btn" title="حذف">
                                <span class="material-icons">delete</span>
                            </button>
                        </a>
                    </td>
                </tr>
            <?php endwhile; else: ?>
                <tr>
                    <td colspan="8">لا توجد معلمات مضافة حالياً أو لا تطابق نتائج البحث.</td>
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
        <a href="dashboard.php" class="button-link" style="background-color: #f0ad4e;">
            <span class="material-icons">dashboard</span> العودة للرئيسية
        </a>
    </div>
</div>

<button id="scrollToTopBtn" class="material-icons" aria-label="العودة لأعلى الصفحة" title="العودة لأعلى">arrow_upward</button>

<script>
    // دالة لإغلاق رسائل التنبيه
    function closeAlert() {
        const msg = document.getElementById('success-msg');
        if (msg) {
            msg.style.opacity = 0; // إخفاء الرسالة تدريجياً
            setTimeout(() => msg.remove(), 500); // إزالة العنصر بعد انتهاء الانتقال
        }
    }
    // إغلاق رسائل التنبيه تلقائياً بعد 5 ثوانٍ
    setTimeout(closeAlert, 5000);

    // ********************************************
    // كود زر العودة للأعلى - يبدأ من هنا
    // ********************************************

    // الحصول على عنصر الزر
    const scrollToTopBtn = document.getElementById('scrollToTopBtn');

    // دالة لإظهار/إخفاء الزر بناءً على موضع التمرير
    function toggleScrollToTopButton() {
        if (scrollToTopBtn) { // التأكد من أن عنصر الزر موجود في HTML
            // إذا كان موضع التمرير الرأسي أكبر من 300 بكسل
            if (window.pageYOffset > 300) {
                scrollToTopBtn.style.display = 'flex'; // إظهار الزر (باستخدام flex لتوسيط الأيقونة)
            } else {
                scrollToTopBtn.style.display = 'none'; // إخفاء الزر
            }
        }
    }

    // دالة لتمرير الصفحة إلى الأعلى بسلاسة
    function scrollToTop() {
        window.scrollTo({ top: 0, behavior: 'smooth' }); // استخدام سلوك التمرير السلس
    }

    // إضافة مستمع حدث للتمرير: عند التمرير، سيتم استدعاء دالة toggleScrollToTopButton
    window.addEventListener('scroll', toggleScrollToTopButton);

    // إضافة مستمع حدث للنقر على الزر: عند النقر، سيتم استدعاء دالة scrollToTop
    if (scrollToTopBtn) { // التأكد مرة أخرى أن الزر موجود قبل إضافة المستمع
        scrollToTopBtn.addEventListener('click', scrollToTop);
    }
    toggleScrollToTopButton();
</script>

</body>
</html>