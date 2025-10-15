<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>تسجيل الغياب - مركز الجود التعليمي</title>
    <link rel="icon" href="logo.png" type="image/x-icon">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<style>
    /* تعريف متغيرات CSS لسهولة التعديل - تم تحديثها لتتوافق مع التصميم السابق */
    :root {
        --primary-color: #89355d; /* لون أساسي للأزرار والعناوين */
        --primary-color-light: #a34e7b; /* لون أفتح للتأثيرات (hover) */
        --secondary-color: #E0BBE4; /* لون خلفية ثانوي */
        --text-dark: #333; /* لون النص الغامق */
        --white: #ffffff; /* اللون الأبيض */
        --shadow-light: rgba(0, 0, 0, 0.08); /* ظل خفيف */
        --shadow-medium: rgba(0, 0, 0, 0.15); /* ظل متوسط */
        --border-radius-large: 12px;
        --border-radius-small: 8px;
        --border-radius-pill: 25px; /* شكل بيضاوي للأزرار */
        --spacing-xs: 0.5rem;
        --spacing-small: 0.75rem;
        --spacing-medium: 1rem; /* 15px is close to 1rem */
        --spacing-large: 1.5rem; /* 40px is closer to 2.5rem, adjusted for better scaling */
        --spacing-xl: 2rem;
    }

    body {
        font-family: 'Cairo', sans-serif;
        background: linear-gradient(to bottom, var(--white) 0%, var(--secondary-color) 100%); /* خلفية متدرجة */
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        direction: rtl;
        color: var(--text-dark); /* تطبيق لون النص الأساسي */
        position: relative; /* مهم لوضع الصورة المطلقة في الخلفية */
        overflow: hidden; /* لمنع ظهور أي أجزاء من الصورة إذا كانت كبيرة جداً */
    }

    /* الصورة الخلفية الشفافة */
    .background-image-overlay {
        position: absolute;
        bottom: -30px; /* أقرب إلى الأسفل أكثر */
        right: -30px; /* أقرب إلى اليمين أكثر */
        transform: rotate(-10deg); /* إمالة خفيفة جدًا لليسار */
        width: 500px; /* تكبير عرض الصورة */
        height: 500px; /* تكبير ارتفاع الصورة */
        background-image: url('ab (2).png'); /* ⬅️ **تأكد من أن هذا هو المسار الصحيح لصورتك** */
        background-size: contain; /* لتناسب الصورة داخل العنصر مع الحفاظ على الأبعاد */
        background-repeat: no-repeat; /* لمنع تكرار الصورة */
        background-position: center; /* لتوسيط الصورة */
        opacity: 0.04; /* شفافية عالية (أقل وضوحًا) - تم الحفاظ عليها من التعديل السابق */
        z-index: -1; /* للتأكد من أنها تظهر خلف المحتوى */
        pointer-events: none; /* لمنع تفاعل الماوس مع الصورة الخلفية */
        filter: blur(1px); /* تأثير ضبابي خفيف جدًا */
    }

    .box {
        background-color: var(--white);
        padding: var(--spacing-xl); /* زيادة الحشو ليتناسب مع التصميم السابق */
        border-radius: var(--border-radius-large);
        box-shadow: 0 8px 24px var(--shadow-light); /* ظل أكثر أناقة */
        text-align: center;
        width: 100%;
        max-width: 450px; /* زيادة أقصى عرض قليلاً */
        box-sizing: border-box;
        position: relative; /* لضمان أن الصندوق يظهر فوق الصورة الخلفية */
        z-index: 1; /* يجب أن يكون أكبر من z-index للخلفية */
    }

    h2 {
        color: var(--primary-color);
        margin-top: 0;
        margin-bottom: var(--spacing-large); /* مسافة أكبر تحت العنوان */
        font-size: 2rem; /* حجم خط أكبر */
        font-weight: 700;
    }

    a {
        display: flex; /* استخدام فليكس بوكس لترتيب الأيقونة والنص */
        align-items: center; /* توسيط عمودي */
        justify-content: center; /* توسيط أفقي للمحتوى داخل الزر */
        gap: 10px; /* مسافة بين الأيقونة والنص */
        margin: var(--spacing-medium) 0;
        padding: 15px 25px; /* حشو أكبر للأزرار */
        background-color: var(--primary-color);
        color: var(--white);
        text-decoration: none;
        border-radius: var(--border-radius-pill); /* حواف دائرية بالكامل */
        font-size: 1.2rem; /* حجم خط أكبر للأزرار */
        font-weight: 600; /* خط أكثر سمكاً */
        transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.2s ease;
        box-shadow: 0 4px 8px var(--shadow-light); /* ظل متناسق */
    }

    a:hover {
        background-color: var(--primary-color-light); /* تغيير اللون عند التحويم */
        transform: translateY(-3px); /* تأثير رفع أوضح قليلاً */
        box-shadow: 0 6px 12px var(--shadow-medium); /* ظل أوضح عند التحويم */
    }

    a .material-icons { /* تنسيق الأيقونات */
        font-size: 28px; /* حجم الأيقونة */
    }

    /* تحسينات للشاشات الصغيرة */
    @media (max-width: 600px) {
        .box {
            padding: var(--spacing-large); /* تقليل الحشو على الشاشات الأصغر */
            margin: var(--spacing-small);
        }
        h2 {
            font-size: 1.8rem;
            margin-bottom: var(--spacing-medium);
        }
        a {
            font-size: 1rem;
            padding: 12px 20px;
            gap: 8px;
        }
        a .material-icons {
            font-size: 24px;
        }
        /* تقليل حجم الصورة الخلفية على الشاشات الصغيرة */
        .background-image-overlay {
            width: 350px; /* حجم أكبر قليلاً على الجوال */
            height: 350px;
            bottom: -60px; /* تعديل الموضع */
            right: -60px; /* تعديل الموضع */
            opacity: 0.03; /* أكثر شفافية على الجوال */
        }
    }
</style>
</head>
<body>
<div class="background-image-overlay"></div>

<div class="box">
    <h2>تسجيل الغياب</h2>
    <a href="absences.php">
        <span class="material-icons">school</span> غياب الطلاب
    </a>
    <a href="teacher_absences.php">
        <span class="material-icons">person</span> غياب المعلمات
    </a>
</div>

</body>
</html>