<?php
session_start();
if (!isset($_SESSION['teacher_name'])) {
    header("Location: teacher-login.php");
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'aljood_center');
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

$teacherName = htmlspecialchars($_SESSION['teacher_name']);

// تم تعديل اسم الجدول من my_students إلى students
// استعلام عدد الطلاب
$totalStudents = $conn->query("SELECT COUNT(*) AS total FROM students")->fetch_assoc()['total'];

// استعلام عدد المواد
$totalSubjects = $conn->query("SELECT COUNT(*) AS total FROM subjects")->fetch_assoc()['total'];

// استعلام عدد الحصص هذا الأسبوع
$classesThisWeek = $conn->query("SELECT COUNT(*) AS total FROM classes WHERE WEEK(تاريخ_الإضافة) = WEEK(CURDATE())")->fetch_assoc()['total'];

// استعلام عدد الحصص اليوم
$classesToday = $conn->query("SELECT COUNT(*) AS total FROM classes WHERE DATE(تاريخ_الإضافة) = CURDATE()")->fetch_assoc()['total'];

$loginTime = date("h:i A");
$avatarFile = 'avtar.jpg';
$currentPage = basename($_SERVER['PHP_SELF']);

function get_greeting_based_on_time() {
    $currentHour = date('H');
    if ($currentHour >= 5 && $currentHour < 12) {
        return "صباح الخير";
    } elseif ($currentHour >= 12 && $currentHour < 18) {
        return "مساء الخير";
    } else {
        return "مساء الخير";
    }
}

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم المعلم</title>
    <link rel="icon" href="logo.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #89355d; /* لون أساسي دافئ (بنفسجي محمر) */
            --primary-light: #d664a7; /* نسخة أفتح من الأساسي */
            --secondary-color: #3b82f6; /* أزرق تكميلي جذاب */
            --background-color: #f0f2f5; /* خلفية فاتحة ناعمة */
            --surface-color: #ffffff; /* للبطاقات والعناصر */
            --text-color: #333333; /* نص داكن */
            --text-light: #666666; /* نص خفيف */
            --border-color: #e0e0e0; /* حدود خفيفة */
            --shadow-color: rgba(0, 0, 0, 0.08); /* ظل خفيف */
            --strong-shadow-color: rgba(0, 0, 0, 0.15); /* ظل أقوى */
            --success-color: #28a745;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --hover-color: #f7f7f7; /* لون عند التحويم فاتح جداً */
            --sidebar-width: 250px;
            --collapsed-sidebar-width: 90px;
            --transition-speed: 0.3s;
            --modal-transition-speed: 0.2s;
            --border-radius-large: 12px;
            --border-radius-medium: 8px;
            --border-radius-small: 6px;

            /* New Variables for Footer and spacing */
            --white: #ffffff;
            --spacing-xl: 2.5rem; /* ~40px */
            --spacing-lg: 1.5rem; /* ~24px */
            --spacing-md: 1rem; /* ~16px */
        }

        /* Dark Mode adjustments */
        body.dark-mode {
            --background-color: #1a1a2e; /* خلفية داكنة عميقة */
            --surface-color: #2e2e42; /* للبطاقات والعناصر الداكنة */
            --text-color: #e0e0e0;
            --text-light: #b0b0b0;
            --border-color: #4a4a6e;
            --shadow-color: rgba(0, 0, 0, 0.4);
            --strong-shadow-color: rgba(0, 0, 0, 0.6);
            --hover-color: #3a3a5a;
            --primary-color: #d664a7; /* عكس الألوان لتناسب الوضع الداكن */
            --primary-light: #89355d;
            --secondary-color: #6a9dfa; /* أزرق فاتح قليلاً */
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Tajawal', sans-serif;
        }
        /* التعديلات الأساسية لتمكين التمرير للصفحة بالكامل وضمان ثبات الشريط الجانبي */
        html, body {
            height: 100%; /* تأكد أن الـ html والـ body يملؤان الارتفاع المتاح */
            overflow-y: auto; /* السماح بالتمرير على مستوى الصفحة بالكامل */
            scroll-behavior: smooth; /* لتحسين تجربة التمرير */
        }
        body {
            display: flex; /* لترتيب الشريط الجانبي والمحتوى جنباً إلى جنب */
            flex-direction: column; /* Changed to column to place footer at the bottom */
            background-color: var(--background-color);
            color: var(--text-color);
            transition: background-color var(--transition-speed), color var(--transition-speed);
            direction: rtl; /* For Arabic */
            text-align: right; /* For Arabic */
            position: relative;
            font-size: 1rem;
        }
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--surface-color);
            padding: 20px 15px;
            box-shadow: 2px 0 15px var(--shadow-color); /* ظل أقوى وناعم */
            display: flex;
            flex-direction: column;
            transition: width var(--transition-speed) ease-in-out, transform var(--transition-speed) ease-in-out, background-color var(--transition-speed), box-shadow var(--transition-speed);
            position: fixed; /* جعل الشريط الجانبي ثابتاً */
            top: 0;
            right: 0; /* Align to right for RTL */
            height: 100vh; /* اجعله يملأ ارتفاع الشاشة بالكامل */
            z-index: 1000;
            flex-shrink: 0;
            overflow-y: auto; /* السماح للشريط الجانبي بالتمرير إذا كان محتواه طويلاً جداً */
            -webkit-overflow-scrolling: touch; /* لتحسين التمرير على iOS */
            scrollbar-width: thin; /* Firefox */
            scrollbar-color: var(--primary-light) var(--background-color); /* Firefox */
        }
        /* Webkit scrollbar styles */
        .sidebar::-webkit-scrollbar {
            width: 8px;
        }
        .sidebar::-webkit-scrollbar-track {
            background: var(--background-color);
            border-radius: 10px;
        }
        .sidebar::-webkit-scrollbar-thumb {
            background-color: var(--primary-light);
            border-radius: 10px;
            border: 2px solid var(--background-color);
        }

        .sidebar.collapsed {
            width: var(--collapsed-sidebar-width);
            align-items: center; /* Center items when collapsed */
        }

        /* For mobile: sidebar slides out from right */
        @media (max-width: 768px) {
            .sidebar {
                right: -250px; /* Hidden by default */
                width: 250px; /* Keep full width for mobile menu */
                transform: translateX(0); /* Reset transform for mobile specific handling */
                padding-top: 60px; /* Space for topbar */
            }

            .sidebar.active {
                transform: translateX(-250px); /* Slide in */
            }
        }

        .sidebar .logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px; /* تباعد أكبر */
            gap: 8px;
            overflow: hidden;
            transition: all var(--transition-speed) ease-in-out;
        }

        /* Hide logo when sidebar is collapsed on desktop */
        .sidebar.collapsed .logo {
            height: 0; /* Collapse height */
            margin-bottom: 0; /* Remove margin */
            opacity: 0; /* Fade out */
            pointer-events: none; /* Prevent interaction */
            display: none; /* Finally hide completely */
        }

        .sidebar .logo img {
            width: 80px; /* حجم أكبر قليلاً */
            height: 80px;
            object-fit: contain;
        }

        .sidebar .logo .logo-text {
            font-size: 1.3rem; /* حجم أكبر للنص */
            font-weight: 700;
            color: var(--primary-color);
            white-space: nowrap;
            opacity: 1;
            transform: scale(1);
            transition: opacity var(--transition-speed) ease-in-out, transform var(--transition-speed) ease-in-out, width var(--transition-speed) ease-in-out;
            display: block;
            width: auto;
        }

        .sidebar.collapsed .logo .logo-text {
            opacity: 0;
            transform: scale(0.8);
            width: 0; /* Ensure no width is taken */
            pointer-events: none;
            display: none; /* Ensure text is gone */
        }

        .sidebar .menu {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 12px; /* تباعد أكبر بين عناصر القائمة */
        }

        .sidebar .menu-item {
            display: flex;
            align-items: center;
            gap: 15px; /* تباعد أكبر بين الأيقونة والنص */
            padding: 12px 18px; /* تباعد أكبر للزر */
            border-radius: var(--border-radius-medium);
            color: var(--text-color);
            text-decoration: none;
            cursor: pointer;
            transition: background-color var(--transition-speed) ease-in-out, color var(--transition-speed) ease-in-out, transform 0.2s ease, box-shadow 0.2s ease;
            white-space: nowrap;
            font-weight: 500;
            font-size: 1rem; /* حجم خط قياسي */
            position: relative;
        }

        .sidebar.collapsed .menu-item {
            justify-content: center;
            padding: 12px 0;
        }

        .sidebar .menu-item .material-icons {
            font-size: 26px; /* أيقونات أكبر */
            color: var(--text-light);
            transition: color var(--transition-speed) ease-in-out, font-size var(--transition-speed) ease-in-out;
        }

        .sidebar.collapsed .menu-item .material-icons {
            font-size: 22px; /* أصغر قليلاً عند الطي */
            margin: 0 auto;
            display: block;
        }

        .sidebar .menu-item .label {
            opacity: 1;
            width: auto;
            transition: opacity var(--transition-speed) ease-in-out, width var(--transition-speed) ease-in-out;
            overflow: hidden;
            display: block;
        }

        .sidebar.collapsed .menu-item .label {
            opacity: 0;
            width: 0;
            overflow: hidden;
            pointer-events: none;
            display: none;
        }

        /* Tooltip for collapsed sidebar */
        .sidebar.collapsed .menu-item .label::after {
            content: attr(data-tooltip);
            position: absolute;
            right: calc(var(--collapsed-sidebar-width) + 15px); /* Adjusted for RTL + more space */
            top: 50%;
            transform: translateY(-50%);
            background-color: var(--surface-color);
            color: var(--text-color);
            padding: 8px 12px; /* تباعد أكبر للتلميح */
            border-radius: var(--border-radius-small);
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.2s ease, visibility 0.2s ease;
            box-shadow: 0 4px 10px var(--shadow-color); /* ظل أفضل */
            z-index: 1001;
            font-size: 0.85rem;
            border: 1px solid var(--border-color); /* حدود خفيفة */
        }

        .sidebar.collapsed .menu-item:hover .label::after {
            opacity: 1;
            visibility: visible;
        }

        .sidebar .menu-item:hover,
        .sidebar .menu-item.active {
            background-color: var(--primary-color); /* اللون الأساسي */
            color: var(--surface-color); /* نص أبيض */
            transform: translateY(-2px);
            box-shadow: 0 6px 16px var(--shadow-color); /* ظل أوضح عند التحويم/التفعيل */
        }

        .sidebar .menu-item:hover .material-icons,
        .sidebar .menu-item.active .material-icons {
            color: var(--surface-color);
        }

        /* Sidebar Overlay for Mobile */
        #sidebar-overlay {
            display: none; /* Hidden by default */
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6); /* خلفية أغمق قليلاً */
            z-index: 1001; /* Below modals, above main content */
            cursor: pointer;
            transition: opacity var(--transition-speed) ease-in-out;
            opacity: 0;
        }
        #sidebar-overlay.active {
            display: block;
            opacity: 1;
        }
        .container {
            display: flex;
            flex-direction: row; /* For desktop: sidebar and main side-by-side */
            flex-grow: 1; /* Allow container to take up remaining height */
            /* Ensure it respects the sidebar width */
            margin-right: var(--sidebar-width); /* Default margin for expanded sidebar */
            transition: margin-right var(--transition-speed) ease-in-out;
        }
        .sidebar.collapsed ~ .container { /* Adjust container margin when sidebar is collapsed */
            margin-right: var(--collapsed-sidebar-width);
        }
        @media (max-width: 768px) {
            .container {
                margin-right: 0; /* No fixed margin on mobile */
                flex-direction: column; /* Stack on mobile */
                width: 100%; /* Take full width */
            }
        }
        .main {
            flex-grow: 1;
            padding: 25px; /* تباعد عام أكبر للمحتوى الرئيسي */
            display: flex;
            flex-direction: column;
            gap: 25px; /* تباعد أكبر بين الأقسام */
            min-height: calc(100vh - 80px); /* Adjust min-height to account for footer */
        }
        @media (max-width: 768px) {
            .main {
                padding: 15px;
                min-height: auto; /* Allow main content to dictate its height */
            }
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px; /* تباعد أكبر */
            border-bottom: 1px solid var(--border-color);
            gap: 15px;
            flex-wrap: wrap;
            flex-shrink: 0;
        position: static;/* لجعل شريط التنقل العلوي ثابتاً */
            top: 0;
            background-color: var(--background-color); /* التأكد من ظهور الخلفية عند الالتصاق */
            z-index: 999; /* التأكد من أنه فوق المحتوى الآخر ولكن تحت الشريط الجانبي */
            padding-top: 5px; /* تباعد بسيط لتجنب الالتصاق بالحافة العلوية مباشرة */
        }

        .topbar-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}

.topbar-actions {
    display: flex;
    gap: 15px;
    align-items: center;
}

        .toggle-btn,
        .notifications-btn,
        .dark-btn {
            background-color: var(--surface-color);
            border: none;
            border-radius: 50%;
            width: 45px; /* حجم أكبر قليلاً */
            height: 45px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            box-shadow: 0 3px 8px var(--shadow-color); /* ظل أفضل */
            transition: background-color var(--transition-speed), transform 0.2s ease, box-shadow var(--transition-speed);
            color: var(--text-color);
            font-size: 26px; /* أيقونات أكبر */
        }

        .toggle-btn:hover,
        .notifications-btn:hover,
        .dark-btn:hover {
            transform: translateY(-3px); /* تأثير رفع أكثر */
            box-shadow: 0 6px 15px var(--strong-shadow-color); /* ظل أقوى عند التحويم */
        }

        .toggle-btn:hover {
            color: var(--primary-color);
        }

        /* Banner */
        .banner {
            background: linear-gradient(to left, var(--primary-light), var(--primary-color)); /* تدرج لوني جذاب */
            color: #ffffff;
            padding: 30px 35px; /* تباعد أكبر */
            border-radius: var(--border-radius-large);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 25px; /* تباعد أكبر */
            flex-wrap: wrap;
            text-align: right;
            box-shadow: 0 8px 20px var(--strong-shadow-color); /* ظل عميق */
            flex-shrink: 0;
        }

        .banner .avatar {
            width: 70px; /* حجم أكبر */
            height: 70px;
            border-radius: 50%;
            border: 3px solid #ffffff; /* حدود أوضح */
            object-fit: cover;
            flex-shrink: 0;
        }
        .banner-content h2 {
            font-size: 1.8rem; /* حجم خط أكبر */
            margin-bottom: 8px;
            font-weight: 700;
        }
        .banner-content p {
            font-size: 1rem; /* حجم خط أكبر */
            opacity: 0.95;
            line-height: 1.5;
        }
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr; /* Adjusted for better balance */
            gap: 25px; /* Increased standard gap */
            flex-grow: 1; /* Allow content grid to take remaining space */
            align-content: start; /* Align grid content to the start of the grid area */
        }

        /* Adjusting for content flow: Carousel, Tasks, Calendar, then Cards */
        .carousel-container {
            grid-column: 1 / 2; /* Left column */
            grid-row: 1 / 2; /* First row */
        }
        .tasks {
            grid-column: 1 / 2; /* Left column */
            grid-row: 2 / 3; /* Second row */
        }
        .calendar-section {
            grid-column: 2 / -1; /* Right column */
            grid-row: 1 / 3; /* Span two rows on the right */
        }
        .cards {
            grid-column: 1 / -1; /* Span full width at the bottom */
            grid-row: 3 / 4; /* Third row, below everything */
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); /* Increased min-width for cards */
            gap: 25px; /* Increased gap */
            flex-shrink: 0;
        }

        .card {
            background-color: var(--surface-color);
            padding: 28px; /* Increased padding for larger look */
            border-radius: var(--border-radius-large);
            box-shadow: 0 5px 15px var(--shadow-color); /* Softer, larger shadow */
            display: flex;
            align-items: center;
            gap: 20px; /* Slightly increased gap */
            transition: transform 0.2s ease, box-shadow 0.2s ease, background-color var(--transition-speed);
            position: relative;
            overflow: hidden;
            min-height: 130px; /* Increased minimum height */
            border: 1px solid var(--border-color); /* Added subtle border */
        }

        .card::before {
            content: '';
            font-family: 'Material Icons';
            position: absolute;
            right: -10px; /* Adjusted for RTL */
            top: 50%;
            transform: translateY(-50%);
            font-size: 6.5rem; /* Increased icon size for more impact */
            color: var(--primary-color);
            opacity: 0.07; /* Slightly less opaque for a softer look */
            z-index: 0;
            transition: color var(--transition-speed);
        }

        /* Icon mapping for cards */
        .card[data-icon="school"]::before { content: "school"; }
        .card[data-icon="group"]::before { content: "group"; }
        .card[data-icon="class"]::before { content: "class"; }
        .card[data-icon="payments"]::before { content: "payments"; }

        .card:hover {
            transform: translateY(-4px); /* More pronounced lift on hover */
            box-shadow: 0 8px 20px var(--strong-shadow-color); /* Stronger shadow on hover */
        }

        .card-info {
            z-index: 1;
        }

        .card-info h3 {
            font-size: 2.8rem; /* Increased font size for numbers for more impact */
            font-weight: 700; /* Bolder numbers */
            color: var(--primary-color);
            margin-bottom: 10px; /* Slightly increased margin */
        }

        .card-info p {
            font-size: 1.1rem; /* Increased font size for text */
            color: var(--text-light);
            font-weight: 500;
        }
        .carousel-container {
        position: relative;
        overflow: hidden;
        border-radius: var(--border-radius-large);
        background-color: var(--surface-color);
        padding: 20px; /* Increased padding */
        box-shadow: 0 4px 12px var(--shadow-color);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        min-height: 280px; /* Adjusted: Increased from 250px */
        flex-shrink: 0;
        border: 1px solid var(--border-color); /* Added subtle border */
    }

    .carousel-container h3 {
        font-size: 1.4rem; /* Larger heading */
        color: var(--text-color);
        margin-bottom: 15px; /* More space */
        font-weight: 600;
    }

    .carousel-content-wrapper {
        flex-grow: 1;
        overflow: hidden;
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: center;
        width: 100%;
    }

    .carousel-wrapper {
    display: flex;
    transition: transform 0.6s ease-in-out;
    border-radius: var(--border-radius-medium);
    overflow: hidden;
    height: 100%;
    width: 100%; /* أضف هذا السطر */
}
.carousel-item {
    min-width: 100%;
    width: 100%;
    box-sizing: border-box;
    height: 100%; /* Make the item take full height */
}

    .carousel-item img {
        width: 100%;
        height: 100%; /* Make the image take full height */
        object-fit: cover;
        border-radius: var(--border-radius-medium);
        display: block;
    }
        

        .carousel-button {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background-color: rgba(0, 0, 0, 0.6); /* Darker background */
            color: white;
            border: none;
            padding: 10px; /* Larger buttons */
            cursor: pointer;
            font-size: 30px; /* Larger icons */
            border-radius: 50%;
            z-index: 1;
            opacity: 0.9; /* Slightly more opaque */
            transition: background-color 0.3s, opacity 0.3s, transform 0.2s ease;
        }

        .carousel-button.prev {
            right: 15px; /* More distance from edge */
        }

        .carousel-button.next {
            left: 15px; /* More distance from edge */
        }

        .carousel-button:hover {
            background-color: rgba(0, 0, 0, 0.8);
            opacity: 1;
            transform: translateY(-50%) scale(1.1); /* Slight zoom on hover */
        }

        .carousel-dots {
            display: flex;
            justify-content: center;
            margin-top: 15px; /* More space */
            gap: 10px; /* More space between dots */
            flex-shrink: 0;
        }

        .dot {
            height: 9px; /* Larger dots */
            width: 9px;
            background-color: var(--text-light);
            border-radius: 50%;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s ease;
        }

        .dot.active,
        .dot:hover {
            background-color: var(--primary-color);
            transform: scale(1.2); /* Zoom active/hover dot */
        }
        .calendar-section {
            background-color: var(--surface-color);
            padding: 25px; /* Increased padding */
            border-radius: var(--border-radius-large);
            box-shadow: 0 4px 12px var(--shadow-color);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            min-height: 450px; /* Adjusted min-height to balance with tasks */
            border: 1px solid var(--border-color); /* Added subtle border */
        }
        .calendar-section h3 {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1.4rem; /* Larger heading */
            color: var(--text-color);
            margin-bottom: 20px; /* More space */
            padding-bottom: 12px; /* More space */
            border-bottom: 1px solid var(--border-color);
            flex-shrink: 0;
            font-weight: 600;
        }
        .calendar-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px; /* More space */
            gap: 10px;
            flex-wrap: wrap;
        }
        .calendar-controls button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 15px; /* Larger buttons */
            border-radius: var(--border-radius-small);
            cursor: pointer;
            font-size: 1rem; /* Standard font size */
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .calendar-controls button:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .calendar-controls span {
            font-size: 1.2rem; /* Larger month/year text */
            font-weight: 600;
            color: var(--text-color);
        }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px; /* More space between days */
            text-align: center;
            font-size: 1rem; /* Standard font size */
            margin-bottom: 20px; /* More space */
        }
        .calendar-grid .day-name {
            font-weight: 700;
            color: var(--primary-color);
            padding: 10px 0; /* More padding */
        }
        .calendar-grid .day-cell {
            padding: 10px 0; /* More padding */
            border-radius: var(--border-radius-small);
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.1s ease;
            position: relative;
        }
        .calendar-grid .day-cell:hover {
            background-color: var(--hover-color);
            transform: translateY(-1px);
        }
        .calendar-grid .day-cell.current-month {
            background-color: var(--background-color);
            color: var(--text-color);
        }
        .calendar-grid .day-cell.not-current-month {
            color: var(--text-light);
            opacity: 0.5;
            cursor: default;
        }
        .calendar-grid .day-cell.today {
            background-color: var(--primary-color);
            color: white;
            font-weight: bold;
            box-shadow: 0 2px 8px var(--shadow-color);
            transform: scale(1.05); /* Highlight today */
        }
        .calendar-grid .day-cell.today .event-dot {
            background-color: white; /* White dot for today */
        }
        .calendar-grid .day-cell .event-dot {
            position: absolute;
            bottom: 4px; /* Slightly lower */
            left: 50%;
            transform: translateX(-50%);
            width: 6px; /* Larger dot */
            height: 6px;
            background-color: var(--secondary-color);
            border-radius: 50%;
        }
        .calendar-events-list {
            flex-grow: 1; /* Allow it to take available height */
            overflow-y: auto; /* Enable scrolling for event list only */
            max-height: 250px; /* Increased max-height for event list */
            border-top: 1px solid var(--border-color);
            padding-top: 20px; /* More padding */
            margin-top: 15px;
            scrollbar-width: thin; /* Firefox */
            scrollbar-color: var(--primary-light) var(--background-color); /* Firefox */
        }
        /* Webkit scrollbar for events list */
        .calendar-events-list::-webkit-scrollbar {
            width: 6px;
        }
        .calendar-events-list::-webkit-scrollbar-track {
            background: var(--background-color);
            border-radius: 10px;
        }
        .calendar-events-list::-webkit-scrollbar-thumb {
            background-color: var(--primary-light);
            border-radius: 10px;
            border: 1px solid var(--background-color);
        }

        .calendar-events-list h4 {
            font-size: 1.1rem; /* Larger heading */
            color: var(--text-color);
            margin-bottom: 15px; /* More space */
            font-weight: 600;
        }
        .calendar-events-list ul {
            list-style: none;
            padding-left: 0;
        }
        .calendar-events-list li {
            font-size: 0.95rem; /* Standard font size */
            padding: 10px 0; /* More padding */
            border-bottom: 1px dashed var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--text-light);
            transition: color 0.2s ease;
        }
        .calendar-events-list li:last-child {
            border-bottom: none;
        }
        .calendar-events-list li:hover {
            color: var(--text-color); /* Darken text on hover */
        }
        .calendar-events-list li .event-time {
            font-weight: bold;
            color: var(--primary-color);
            margin-left: 8px; /* Adjusted for RTL */
        }
        .calendar-events-list li .event-type {
            font-size: 0.8rem; /* Slightly larger */
            background-color: var(--primary-light); /* Lighter primary for badge */
            color: white;
            padding: 4px 10px; /* Larger padding for badge */
            border-radius: var(--border-radius-small);
            margin-right: 8px; /* Adjusted for RTL */
        }
        .add-event-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 15px; /* Larger button */
            border-radius: var(--border-radius-small);
            cursor: pointer;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 8px; /* More space */
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .add-event-btn .material-icons {
            font-size: 22px; /* Larger icon */
        }
        .add-event-btn:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .tasks {
            background-color: var(--surface-color);
            padding: 25px; /* Increased padding */
            border-radius: var(--border-radius-large);
            box-shadow: 0 4px 12px var(--shadow-color);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            min-height: 350px; /* Adjusted min-height to balance with calendar */
            border: 1px solid var(--border-color); /* Added subtle border */
        }
        .tasks h3 {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1.4rem; /* Larger heading */
            color: var(--text-color);
            margin-bottom: 20px; /* More space */
            padding-bottom: 12px; /* More space */
            border-bottom: 1px solid var(--border-color);
            flex-shrink: 0;
            font-weight: 600;
        }
        .tasks ul {
            list-style: none;
            padding: 0;
            flex-grow: 1;
            overflow-y: auto; /* Enable scrolling for task list only */
            max-height: 280px; /* Increased max-height */
            scrollbar-width: thin; /* Firefox */
            scrollbar-color: var(--primary-light) var(--background-color); /* Firefox */
        }
        /* Webkit scrollbar for task list */
        .tasks ul::-webkit-scrollbar {
            width: 6px;
        }
        .tasks ul::-webkit-scrollbar-track {
            background: var(--background-color);
            border-radius: 10px;
        }
        .tasks ul::-webkit-scrollbar-thumb {
            background-color: var(--primary-light);
            border-radius: 10px;
            border: 1px solid var(--background-color);
        }

        .tasks .task-controls {
            display: flex;
            gap: 12px; /* More space between controls */
            align-items: center;
        }
        .tasks .add-task-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius-small);
            padding: 10px 15px; /* Larger button */
            cursor: pointer;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 8px; /* More space */
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .tasks .add-task-btn .material-icons {
            font-size: 22px; /* Larger icon */
        }
        .tasks .add-task-btn:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .tasks .toggle-completed-tasks-btn {
            background-color: var(--text-light);
            color: white;
            border: none;
            border-radius: 50%;
            padding: 10px; /* Larger button */
            cursor: pointer;
            font-size: 22px; /* Larger icon */
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.2s ease;
            width: 45px; /* Larger button */
            height: 45px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .tasks .toggle-completed-tasks-btn:hover {
            background-color: #888;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .tasks .toggle-completed-tasks-btn.active {
            background-color: var(--success-color);
        }

        .tasks li {
            display: flex;
            justify-content: space-between;
            align-items: flex-start; /* Align to top for multi-line content */
            padding: 12px 0; /* More padding */
            border-bottom: 1px dashed var(--border-color);
            transition: background-color 0.2s ease;
            flex-wrap: wrap;
            gap: 8px;
        }
        .tasks li:last-child {
            border-bottom: none;
        }
        .tasks li:hover {
            background-color: var(--hover-color);
        }
        .tasks li.completed {
            text-decoration: line-through;
            opacity: 0.7;
            color: var(--text-light);
        }
        .tasks li .task-details {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            flex-grow: 1;
            flex-basis: calc(100% - 100px); /* Allow actions to take 100px */
        }
        .tasks li .task-main-info {
            display: flex;
            align-items: center;
            gap: 12px; /* More space */
            width: 100%;
            margin-bottom: 5px;
        }
        .tasks li .task-checkbox {
            margin-left: 12px; /* Adjusted for RTL */
            transform: scale(1.2); /* Larger checkbox */
            min-width: 20px; /* Ensure space for checkbox */
            min-height: 20px;
        }
        .tasks li .task-text {
            font-weight: 500;
            color: var(--text-color);
            flex-grow: 1;
            word-break: break-word;
            font-size: 1rem; /* Standard font size */
        }
        .tasks li.completed .task-text {
            color: var(--text-light);
        }
        .tasks li .task-meta {
            font-size: 0.85rem; /* Slightly larger */
            color: var(--text-light);
            display: flex;
            gap: 15px; /* More space */
            flex-wrap: wrap;
            width: 100%;
            padding-left: 32px; /* Adjusted for RTL, to align with checkbox */
        }
        .tasks li .task-meta span {
            display: flex;
            align-items: center;
            gap: 4px; /* More space */
        }

        /* Priority Colors */
        .tasks li .priority-high { color: var(--danger-color); font-weight: bold; }
        .tasks li .priority-medium { color: var(--info-color); }
        .tasks li .priority-low { color: var(--success-color); }

        .tasks li .task-actions {
            display: flex;
            gap: 8px; /* More space */
            flex-shrink: 0;
            align-items: center;
        }
        .tasks li .task-actions button {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 22px; /* Larger icons */
            transition: transform 0.2s ease, color 0.2s ease;
            opacity: 0.9;
        }
        .tasks li .task-actions button:hover {
            opacity: 1;
            transform: scale(1.15); /* More pronounced zoom */
        }
        .tasks li .task-actions .edit-task-btn {
            color: var(--info-color);
        }
        .tasks li .task-actions .delete-task-btn {
            color: var(--danger-color);
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.7); /* Darker overlay */
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity var(--modal-transition-speed) ease-in-out;
        }
        .modal.show {
            display: flex;
            opacity: 1;
        }

        .modal-content {
            background-color: var(--surface-color);
            margin: auto;
            padding: 30px; /* Increased padding */
            border-radius: var(--border-radius-large);
            box-shadow: 0 6px 20px var(--strong-shadow-color); /* Stronger shadow */
            width: 95%; /* Slightly wider */
            max-width: 550px; /* Larger max-width */
            position: relative;
            text-align: right; /* Adjusted for RTL */
            transition: background-color var(--transition-speed), transform var(--modal-transition-speed) ease-in-out;
            transform: translateY(-70px); /* More pronounced slide-in */
            opacity: 0;
            border: 1px solid var(--border-color); /* Added subtle border */
        }

        .modal.show .modal-content {
            transform: translateY(0);
            opacity: 1;
        }

        .modal-content h2 {
            color: var(--primary-color);
            margin-bottom: 25px; /* More space */
            font-size: 1.8rem; /* Larger heading */
            font-weight: 700;
        }

        .modal-content input:not([type='checkbox']),
        .modal-content select,
        .modal-content input[type="datetime-local"] {
            width: calc(100% - 0px); /* Fill full width */
            padding: 12px; /* Increased padding */
            margin-bottom: 18px; /* More space */
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-small);
            font-size: 1rem;
            color: var(--text-color);
            background-color: var(--background-color);
            transition: border-color 0.3s, background-color var(--transition-speed), box-shadow 0.2s ease;
        }
        .modal-content input:not([type='checkbox']):focus,
        .modal-content select:focus,
        .modal-content input[type="datetime-local"]:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(var(--primary-color-rgb), 0.2); /* Subtle focus ring */
        }

        .modal-buttons {
            display: flex;
            justify-content: flex-start; /* Adjusted for RTL */
            gap: 12px; /* More space */
            margin-top: 25px; /* More space */
        }

        .modal-content button {
            padding: 12px 25px; /* Larger buttons */
            border: none;
            border-radius: var(--border-radius-medium);
            cursor: pointer;
            font-size: 1.05rem; /* Slightly larger font */
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .modal-content button:not(.cancel-btn) {
            background-color: var(--primary-color);
            color: white;
        }

        .modal-content button:not(.cancel-btn):hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        .modal-content .cancel-btn {
            background-color: var(--text-light);
            color: white;
        }

        .modal-content .cancel-btn:hover {
            background-color: #777;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        .close-button {
            position: absolute;
            top: 20px; /* More space from top */
            left: 20px; /* Adjusted for RTL */
            font-size: 32px; /* Larger icon */
            cursor: pointer;
            color: var(--text-light);
            transition: color 0.3s, transform 0.2s ease;
        }

        .close-button:hover {
            color: var(--danger-color);
            transform: rotate(90deg); /* Spin on hover */
        }
        
        footer {
            background-color: var(--primary-color);
            color: var(--white);
            text-align: center;
            padding: var(--spacing-md) 0; /* Using spacing variable */
            font-size: 0.875rem;
            width: 100%;
            margin-top: var(--spacing-xl); /* Using spacing variable */
            box-shadow: 0 -0.125rem 0.25rem rgba(0, 0, 0, 0.1);
            flex-shrink: 0; /* Prevent footer from shrinking */
        }

        footer p {
            color: var(--white);
            text-align: center;
            margin: 0;
        }
        @media (max-width: 992px) {
            body { font-size: 0.95rem; }
            .main {
                padding: 20px;
                gap: 20px;
            }
            .banner-content h2 { font-size: 1.6rem; }
            .banner-content p { font-size: 0.9rem; }
            .banner .avatar { width: 60px; height: 60px; }
            .cards {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
            }
            .card { padding: 22px; min-height: 110px; }
            .card-info h3 { font-size: 2.4rem; }
            .card-info p { font-size: 0.95rem; }
            .card::before { font-size: 5.5rem; right: -8px; }
            .carousel-item img { max-height: 200px; }
            .carousel-container { min-height: 250px; padding: 18px; }
            .carousel-button { font-size: 28px; padding: 8px; }
            .dot { height: 8px; width: 8px; }

            .tasks h3, .calendar-section h3 { font-size: 1.3rem; }
            .tasks .add-task-btn, .add-event-btn { padding: 8px 12px; font-size: 0.95rem; }
            .tasks .add-task-btn .material-icons, .add-event-btn .material-icons { font-size: 20px; }
            .tasks li { padding: 10px 0; }
            .tasks li .task-text { font-size: 0.95rem; }
            .tasks li .task-meta { font-size: 0.8rem; padding-left: 30px; }
            .tasks li .task-actions button { font-size: 20px; }
            .calendar-controls button { padding: 9px 13px; font-size: 0.95rem; }
            .calendar-controls span { font-size: 1.1rem; }
            .calendar-grid { gap: 6px; font-size: 0.95rem; }
            .calendar-grid .day-name { padding: 9px 0; }
            .calendar-grid .day-cell { padding: 9px 0; }
            .calendar-grid .day-cell .event-dot { width: 5px; height: 5px; bottom: 3px; }
            .calendar-events-list h4 { font-size: 1rem; }
            .calendar-events-list li { font-size: 0.9rem; padding: 9px 0; }
            .calendar-events-list li .event-type { font-size: 0.75rem; padding: 3px 8px; }
            .modal-content { padding: 25px; max-width: 500px; }
            .modal-content h2 { font-size: 1.6rem; }
            .modal-content input:not([type='checkbox']),
            .modal-content select,
            .modal-content input[type="datetime-local"] { font-size: 0.95rem; padding: 10px; }
            .modal-content button { font-size: 0.95rem; padding: 10px 20px; }
            .close-button { font-size: 28px; top: 18px; left: 18px; }

            .tasks .toggle-completed-tasks-btn { width: 40px; height: 40px; font-size: 20px; }
            footer { margin-top: var(--spacing-lg); } /* Adjust margin for smaller screens */
        }

        @media (max-width: 768px) {
            body {
                flex-direction: column; /*STACK items on mobile*/
            }
            /* main will occupy full width on mobile, sidebar slides in/out */
            .main {
                margin-right: 0; /* Remove fixed margin for mobile */
                padding: 15px;
                min-height: auto; /* Allow content to dictate height */
                gap: 20px;
            }
            .sidebar {
                width: 220px; /* Slightly narrower for mobile */
                right: -220px; /* Hidden by default off-screen */
                padding-top: 60px; /* Space for topbar */
            }
            .sidebar.active {
                transform: translateX(-220px); /* Slide in */
            }
            .container {
                flex-direction: column; /* Ensure container stacks as well */
                margin-right: 0;
            }
            .sidebar .logo { margin-bottom: 20px; }
            .sidebar .logo img { width: 50px; height: 50px; }
            .sidebar .logo .logo-text { font-size: 1.2rem; }
            .sidebar .menu-item { padding: 10px 15px; font-size: 0.95rem; }
            .sidebar .menu-item .material-icons { font-size: 22px; }

            .topbar { padding: 10px 15px; }
            .toggle-btn { margin-left: 10px; }
            .notifications-btn, .dark-btn { width: 40px; height: 40px; font-size: 22px; }

            .banner { padding: 20px 25px; gap: 20px; }
            .banner h2 { font-size: 1.4rem; }
            .banner p { font-size: 0.85rem; }
            .banner .avatar { width: 60px; height: 60px; }

            .content-grid {
                grid-template-columns: 1fr; /* Stack columns on smaller screens */
                gap: 20px;
            }
            .carousel-container,
            .tasks,
            .calendar-section,
            .cards {
                grid-column: 1 / -1; /* Take full width when stacked */
                grid-row: auto; /* Allow auto placement */
            }

            .carousel-item img { max-height: 180px; }
            .carousel-container { min-height: 220px; }
            .card { padding: 20px; gap: 15px; min-height: 100px; }
            .card-info h3 { font-size: 2.2rem; }
            .card-info p { font-size: 0.9rem; }
            .card::before { font-size: 5rem; right: -7px; }

            .tasks h3, .calendar-section h3 { font-size: 1.2rem; }
            .tasks .add-task-btn, .add-event-btn { font-size: 0.9rem; padding: 7px 10px; }
            .tasks .add-task-btn .material-icons, .add-event-btn .material-icons { font-size: 18px; }
            .tasks li { padding: 9px 0; }
            .tasks li .task-text { font-size: 0.9rem; }
            .tasks li .task-meta { font-size: 0.8rem; padding-left: 28px; }
            .tasks li .task-actions button { font-size: 18px; }
            .calendar-grid .day-name { font-size: 0.85rem; }
            .calendar-grid .day-cell { padding: 8px 0; }
            .calendar-events-list h4 { font-size: 0.95rem; }
            .calendar-events-list li { font-size: 0.85rem; }
            .calendar-events-list li .event-type { font-size: 0.7rem; padding: 3px 7px; }
            .modal-content { padding: 20px; max-width: 450px; }
            .modal-content h2 { font-size: 1.4rem; }
            .modal-content input:not([type='checkbox']),
            .modal-content select,
            .modal-content input[type="datetime-local"] { font-size: 0.9rem; padding: 9px; }
            .modal-content button { font-size: 0.9rem; padding: 8px 18px; }
            .close-button { font-size: 26px; top: 15px; left: 15px; }
            #scrollToTopBtn { width: 50px; height: 50px; font-size: 28px; bottom: 20px; left: 20px; }
            .calendar-events-list { max-height: 200px; } /* Adjust for stacked view */
            .tasks ul { max-height: 250px; } /* Adjust for stacked view */
            footer { margin-top: var(--spacing-lg); } /* Adjust margin for smaller screens */
        }

        @media (max-width: 480px) {
            .topbar-controls { gap: 8px; }
            .banner h2 { font-size: 1.2rem; }
            .banner p { font-size: 0.8rem; }
            .cards { grid-template-columns: 1fr; /* Single column on very small screens */ }
            .card-info h3 { font-size: 2rem; }
            .card-info p { font-size: 0.85rem; }
            .carousel-item img { max-height: 140px; }
            .carousel-container { min-height: 170px; }
            .tasks li .task-meta { font-size: 0.75rem; }
            .modal-content h2 { font-size: 1.3rem; }
            #scrollToTopBtn { width: 45px; height: 45px; font-size: 24px; bottom: 15px; left: 15px; }
            .sidebar { width: 180px; right: -180px; }
            .sidebar.active { transform: translateX(-180px); }
            .sidebar .logo .logo-text { font-size: 1rem; }
            .sidebar .menu-item { font-size: 0.85rem; padding: 8px 10px; }
            .sidebar .menu-item .material-icons { font-size: 20px; }
            .tasks .toggle-completed-tasks-btn { width: 35px; height: 35px; font-size: 18px; }
            .tasks .add-task-btn, .add-event-btn { padding: 6px 9px; font-size: 0.85rem; }
            .tasks .add-task-btn .material-icons, .add-event-btn .material-icons { font-size: 16px; }
            .modal-content button { padding: 7px 14px; font-size: 0.8rem; }
        }
    </style>
</head>
<body>
    <aside class="sidebar" id="sidebar">
        <div class="logo">
            <img id="mainLogo" src="logo.png" alt="شعار لوحة التحكم"
                 data-light-src="logo.png"
                 data-dark-src="white.png" />
            <span class="logo-text">مركز الجود التعليمي</span>
        </div>
       <nav class="menu">
    <a href="teacher-dashboard.php" class="menu-item <?= ($currentPage == 'teacher-dashboard.php') ? 'active' : '' ?>" data-tooltip="الرئيسية">
        <span class="material-icons">dashboard</span>
        <span class="label">الرئيسية</span>
    </a>
    <a href="my_students.php" class="menu-item <?= ($currentPage == 'my_students.php') ? 'active' : '' ?>" data-tooltip="طلابي">
        <span class="material-icons">person</span>
        <span class="label">طلابي</span>
    </a>
    <a href="my_schedule.php" class="menu-item <?= ($currentPage == 'my_schedule.php') ? 'active' : '' ?>" data-tooltip="جدولي">
        <span class="material-icons">schedule</span>
        <span class="label">جدولي</span>
    </a>
    <a href="manage_grades.php" class="menu-item <?= ($currentPage == 'manage_grades.php') ? 'active' : '' ?>" data-tooltip="الدرجات">
        <span class="material-icons">grading</span>
        <span class="label">الدرجات</span>
    </a>
    <a href="logout.php" class="menu-item" data-tooltip="تسجيل الخروج">
        <span class="material-icons">logout</span>
        <span class="label">تسجيل الخروج</span>
    </a>
</nav>
        </nav>
    </aside>

    <div id="sidebar-overlay"></div>

    <div class="container" id="mainContainer">
        <main class="main" id="mainContent">
            <header class="topbar">
                <div class="topbar-controls">
    <button class="toggle-btn material-icons" aria-label="تبديل الشريط الجانبي">menu</button>
    <div class="topbar-actions">
        <button class="dark-btn material-icons" aria-label="تبديل الوضع الليلي">brightness_2</button>
    </div>
</div>
            </header>



             <section class="banner">
                <div class="banner-content">
                    <h2><?php echo get_greeting_based_on_time(); ?> يا أستاذ <?php echo $teacherName ?> 👋</h2>
                    <p>تم تسجيل دخولك الساعة: <?php echo $loginTime; ?></p>
                </div>
                <img class="avatar" src="avtar.jpg" alt="صورة الملف الشخصي للمعلم">
            </section>

            <div class="content-grid">
                <section class="carousel-container" aria-label="معرض الصور">
                    <div class="loading-spinner" id="carouselSpinner"></div>
                    <div class="carousel-wrapper" id="carouselWrapper">
    <div class="carousel-item">
        <img src="sud.png" alt="صورة طلاب في الفصل"> </div>
                </section>
                <section class="tasks">
                    <h3>
                        المهام اليومية
                        <div class="task-controls">
                            <button class="add-task-btn" onclick="openAddTaskModal()">
                                <span class="material-icons">add</span>
                                إضافة مهمة
                            </button>
                            <button class="toggle-completed-tasks-btn" id="toggleCompletedTasksBtn" title="عرض/إخفاء المهام المنجزة">
                                <span class="material-icons">check_circle_outline</span>
                            </button>
                        </div>
                    </h3>
                    <ul id="taskList"></ul>
                </section>

                <section class="calendar-section">
                    <h3>
                        التقويم
                        <button class="add-event-btn" onclick="openAddEventModal()">
                            <span class="material-icons">event</span>
                            إضافة حدث
                        </button>
                    </h3>
                    <div class="calendar-controls">
                        <button class="material-icons" id="prevMonthBtn">chevron_right</button>
                        <span id="currentMonthYear"></span>
                        <button class="material-icons" id="nextMonthBtn">chevron_left</button>
                    </div>
                    <div class="calendar-grid" id="calendarGrid">
                        </div>
                    <div class="calendar-events-list" id="calendarEventsList">
                        <h4>أحداث هذا الشهر:</h4>
                        <ul id="currentMonthEvents"></ul>
                    </div>
                </section>

              <section class="cards">
    <div class="card" data-icon="school">
        <div class="card-info">
            <p>عدد الطلاب</p>
            <h3><?php echo $totalStudents; ?></h3>
        </div>
    </div>
    <div class="card" data-icon="subject">
        <span class="material-icons" aria-hidden="true">subject</span>
        <div class="card-info">
            <p>عدد المواد</p>
            <h3><?php echo $totalSubjects; ?></h3>
        </div>
    </div>
    <div class="card" data-icon="event_note">
        <span class="material-icons" aria-hidden="true">event_note</span>
        <div class="card-info">
            <p>حصص هذا الأسبوع</p>
            <h3><?php echo $classesThisWeek; ?></h3>
        </div>
    </div>
    <div class="card" data-icon="today">
        <span class="material-icons" aria-hidden="true">today</span>
        <div class="card-info">
            <p>حصص اليوم</p>
            <h3><?php echo $classesToday; ?></h3>
        </div>
    </div>
</section>
            </div>
        </main>
    </div> <footer>
        <p>&copy; 2025 مركز الجود التعليمي. جميع الحقوق محفوظة.</p>
    </footer>



    <div id="addTaskModal" class="modal">
        <div class="modal-content">
            <span class="close-button material-icons" onclick="closeAddTaskModal()">close</span>
            <h2>إضافة مهمة جديدة</h2>
            <input type="text" id="taskTextInput" placeholder="اكتب المهمة هنا..." aria-label="اسم المهمة الجديدة">
            <select id="taskPriorityInput" aria-label="أولوية المهمة">
                <option value="منخفضة">منخفضة</option>
                <option value="متوسطة">متوسطة</option>
                <option value="عالية">عالية</option>
            </select>
            <input type="datetime-local" id="taskDueDateInput" aria-label="تاريخ الاستحقاق">
            <select id="taskCategoryInput" aria-label="تصنيف المهمة">
                <option value="إدارية">إدارية</option>
                <option value="طلاب">طلاب</option>
                <option value="معلمين">معلمين</option>
                <option value="أخرى">أخرى</option>
            </select>
            <div class="modal-buttons">
                <button class="cancel-btn" onclick="closeAddTaskModal()">إلغاء</button>
                <button onclick="addTask()">إضافة</button>
            </div>
        </div>
    </div>

    <div id="addEventModal" class="modal">
        <div class="modal-content">
            <span class="close-button material-icons" onclick="closeAddEventModal()">close</span>
            <h2>إضافة حدث جديد</h2>
            <input type="text" id="eventTitleInput" placeholder="عنوان الحدث" aria-label="عنوان الحدث">
            <input type="datetime-local" id="eventDateTimeInput" aria-label="تاريخ ووقت الحدث">
            <select id="eventTypeInput" aria-label="نوع الحدث">
                <option value="عام">عام</option>
                <option value="فصل">فصل</option>
                <option value="إجازة">إجازة</option>
                <option value="اجتماع">اجتماع</option>
                <option value="دفع">دفع رسوم</option>
            </select>
            <div class="modal-buttons">
                <button class="cancel-btn" onclick="closeAddEventModal()">إلغاء</button>
                <button onclick="addEvent()">إضافة</button>
            </div>
        </div>
    </div>

    <div id="editTaskModal" class="modal">
        <div class="modal-content">
            <span class="close-button material-icons" onclick="closeEditTaskModal()">close</span>
            <h2>تعديل المهمة</h2>
            <input type="hidden" id="editTaskIndex">
            <input type="text" id="editTaskTextInput" placeholder="نص المهمة" aria-label="نص المهمة">
            <select id="editTaskPriorityInput" aria-label="أولوية المهمة">
                <option value="منخفضة">منخفضة</option>
                <option value="متوسطة">متوسطة</option>
                <option value="عالية">عالية</option>
            </select>
            <input type="datetime-local" id="editTaskDueDateInput" aria-label="تاريخ الاستحقاق">
            <select id="editTaskCategoryInput" aria-label="تصنيف المهمة">
                <option value="إدارية">إدارية</option>
                <option value="طلاب">طلاب</option>
                <option value="معلمين">معلمين</option>
                <option value="أخرى">أخرى</option>
            </select>
            <div class="modal-buttons">
                <button class="cancel-btn" onclick="closeEditTaskModal()">إلغاء</button>
                <button onclick="updateTask()">تحديث</button>
            </div>
        </div>
    </div>

    <div id="messageModal" class="modal">
        <div class="modal-content">
            <h2 id="messageModalTitle"></h2>
            <p id="messageModalText"></p>
            <div class="modal-buttons">
                <button onclick="closeMessageModal()">موافق</button>
            </div>
        </div>
    </div>

    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <h2 id="confirmModalTitle">تأكيد</h2>
            <p id="confirmModalText"></p>
            <div class="modal-buttons">
                <button class="cancel-btn" id="confirmCancelBtn">إلغاء</button>
                <button id="confirmOkBtn">تأكيد</button>
            </div>
        </div>
    </div>

   <script>
    // تعليقات عامة: هذا السكربت يدير واجهة لوحة التحكم، بما في ذلك الشريط الجانبي، الوضع الداكن، إدارة المهام، والتقويم.

    // References to DOM elements (الإشارة إلى عناصر DOM)
    // الحصول على عنصر الشريط الجانبي باستخدام ID
    const sidebar = document.getElementById('sidebar');
    // الحصول على عنصر التراكب (overlay) الخاص بالشريط الجانبي
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    // الحصول على زر التبديل (لفتح/إغلاق الشريط الجانبي)
    const toggleBtn = document.querySelector('.toggle-btn');
    // الحصول على زر تبديل الوضع الداكن
    const darkModeToggle = document.querySelector('.dark-btn');
    // الحصول على عنصر شعار الموقع الرئيسي
    const mainLogo = document.getElementById('mainLogo');
    // الحصول على منطقة المحتوى الرئيسية
    const mainContent = document.getElementById('mainContent');
    // الحصول على الحاوية الرئيسية للوحة التحكم
    const mainContainer = document.getElementById('mainContainer');
    // الحصول على زر "العودة للأعلى"
    const scrollToTopBtn = document.getElementById('scrollToTopBtn');

    // Task Management elements (عناصر إدارة المهام)
    // الحصول على قائمة المهام (حيث تُعرض المهام)
    const taskList = document.getElementById('taskList');
    // الحصول على نافذة إضافة مهمة جديدة (مودال)
    const addTaskModal = document.getElementById('addTaskModal');
    // الحصول على حقل إدخال نص المهمة في مودال الإضافة
    const taskTextInput = document.getElementById('taskTextInput');
    // الحصول على حقل اختيار أولوية المهمة في مودال الإضافة
    const taskPriorityInput = document.getElementById('taskPriorityInput');
    // الحصول على حقل إدخال تاريخ استحقاق المهمة في مودال الإضافة
    const taskDueDateInput = document.getElementById('taskDueDateInput');
    // الحصول على حقل اختيار تصنيف المهمة في مودال الإضافة
    const taskCategoryInput = document.getElementById('taskCategoryInput');
    // الحصول على زر تبديل عرض/إخفاء المهام المنجزة
    const toggleCompletedTasksBtn = document.getElementById('toggleCompletedTasksBtn');

    // Edit Task Modal elements (عناصر نافذة تعديل المهمة)
    // الحصول على نافذة تعديل المهمة (مودال)
    const editTaskModal = document.getElementById('editTaskModal');
    // حقل مخفي لتخزين معرف المهمة التي يتم تعديلها
    const editTaskIndexInput = document.getElementById('editTaskIndex');
    // حقل إدخال نص المهمة في مودال التعديل
    const editTaskTextInput = document.getElementById('editTaskTextInput');
    // حقل اختيار أولوية المهمة في مودال التعديل
    const editTaskPriorityInput = document.getElementById('editTaskPriorityInput');
    // حقل إدخال تاريخ استحقاق المهمة في مودال التعديل
    const editTaskDueDateInput = document.getElementById('editTaskDueDateInput');
    // حقل اختيار تصنيف المهمة في مودال التعديل
    const editTaskCategoryInput = document.getElementById('editTaskCategoryInput');

    // Calendar elements (عناصر التقويم)
    // الحصول على شبكة أيام التقويم
    const calendarGrid = document.getElementById('calendarGrid');
    // الحصول على عنصر عرض الشهر والسنة الحاليين
    const currentMonthYearSpan = document.getElementById('currentMonthYear');
    // الحصول على زر الانتقال للشهر السابق
    const prevMonthBtn = document.getElementById('prevMonthBtn');
    // الحصول على زر الانتقال للشهر التالي
    const nextMonthBtn = document.getElementById('nextMonthBtn');
    // الحصول على نافذة إضافة حدث جديد (مودال)
    const addEventModal = document.getElementById('addEventModal');
    // الحصول على حقل إدخال عنوان الحدث في مودال الإضافة
    const eventTitleInput = document.getElementById('eventTitleInput');
    // الحصول على حقل إدخال تاريخ ووقت الحدث في مودال الإضافة
    const eventDateTimeInput = document.getElementById('eventDateTimeInput');
    // الحصول على حقل اختيار نوع الحدث في مودال الإضافة
    const eventTypeInput = document.getElementById('eventTypeInput');
    // الحصول على قائمة عرض أحداث الشهر الحالي
    const currentMonthEventsList = document.getElementById('currentMonthEvents');

    // Custom Message/Confirm Modals (نوافذ الرسائل/التأكيد المخصصة)
    // الحصول على نافذة عرض الرسائل (مودال)
    const messageModal = document.getElementById('messageModal');
    // الحصول على عنوان نافذة الرسائل
    const messageModalTitle = document.getElementById('messageModalTitle');
    // الحصول على نص الرسالة في نافذة الرسائل
    const messageModalText = document.getElementById('messageModalText');

    // الحصول على نافذة التأكيد (مودال)
    const confirmModal = document.getElementById('confirmModal');
    // الحصول على نص الرسالة في نافذة التأكيد
    const confirmModalText = document.getElementById('confirmModalText');
    // الحصول على زر الإلغاء في نافذة التأكيد
    const confirmCancelBtn = document.getElementById('confirmCancelBtn');
    // الحصول على زر التأكيد (موافق) في نافذة التأكيد
    const confirmOkBtn = document.getElementById('confirmOkBtn');

    // Global variables (متغيرات عامة)
    // متغير لتخزين دالة رد الاتصال لزر التأكيد (يتم استدعاؤها عند الضغط على موافق)
    let confirmCallback = null;
    // مصفوفة لتخزين المهام، يتم تحميلها من التخزين المحلي أو تكون فارغة إذا لم توجد
    let tasks = JSON.parse(localStorage.getItem('tasks')) || [];
    // مصفوفة لتخزين الأحداث، يتم تحميلها من التخزين المحلي أو تكون فارغة إذا لم توجد
    let events = JSON.parse(localStorage.getItem('events')) || [];
    // حالة لتحديد ما إذا كان يجب عرض المهام المنجزة (true) أو إخفائها (false)
    let showCompletedTasks = false;
    // التاريخ الحالي المعروض في التقويم
    let currentCalendarDate = new Date();

    // Helper functions for safer DOM element selection (دوال مساعدة لاختيار عناصر DOM بأمان)
    // دالة للحصول على عنصر بواسطة ID مع تحذير إذا لم يتم العثور عليه
    function getElementByIdSafe(id) {
        const element = document.getElementById(id);
        if (!element) {
            console.warn(`Element with ID '${id}' not found.`);
        }
        return element;
    }

    // دالة للحصول على أول عنصر يطابق المحدد (selector) مع تحذير
    function querySelectorSafe(selector, parent = document) {
        const element = parent.querySelector(selector);
        if (!element) {
            console.warn(`Element with selector '${selector}' not found.`);
        }
        return element;
    }

    // دالة للحصول على جميع العناصر التي تطابق المحدد (selector)
    function querySelectorAllSafe(selector, parent = document) {
        return parent.querySelectorAll(selector);
    }

    // --- Sidebar Functions (دوال الشريط الجانبي) ---
    // دالة لتبديل حالة الشريط الجانبي (مفتوح/مغلق)
    function toggleSidebar() {
        // التحقق مما إذا كانت الشاشة بحجم الجوال أو أصغر (أقل من 768 بكسل)
        if (window.innerWidth <= 768) {
            // سلوك الجوال: فتح/إغلاق الشريط الجانبي وعرض/إخفاء التراكب
            // تبديل فئة 'active' على الشريط الجانبي
            if (sidebar) sidebar.classList.toggle('active');
            // تبديل فئة 'active' على التراكب بناءً على حالة الشريط الجانبي
            if (sidebarOverlay) {
                sidebarOverlay.classList.toggle('active', sidebar && sidebar.classList.contains('active'));
            }
        } else {
            // سلوك سطح المكتب: تبديل فئة 'collapsed'
            if (sidebar) sidebar.classList.toggle('collapsed');
            // تغيير أيقونة زر التبديل بناءً على حالة الشريط الجانبي
            if (toggleBtn) {
                toggleBtn.textContent = sidebar && sidebar.classList.contains('collapsed') ? 'menu_open' : 'menu';
            }
            // ضبط الهامش الأيمن للحاوية الرئيسية بناءً على حالة الشريط الجانبي لسطح المكتب
            if (mainContainer && sidebar) {
                mainContainer.style.marginRight = sidebar.classList.contains('collapsed') ?
                    'var(--collapsed-sidebar-width)' : // هامش أقل إذا كان الشريط مصغراً
                    'var(--sidebar-width)'; // هامش كامل إذا كان الشريط مفتوحاً
            }
        }
    }

    // دالة لضبط الشريط الجانبي عند تغيير حجم النافذة
    function adjustSidebarOnResize() {
        // إذا كان حجم الشاشة أكبر من 768 بكسل (سطح المكتب)
        if (window.innerWidth > 768) {
            // إزالة فئة 'active' من الشريط الجانبي (لضمان عدم فتحه كلياً على سطح المكتب)
            if (sidebar) sidebar.classList.remove('active');
            // إخفاء التراكب
            if (sidebarOverlay) sidebarOverlay.classList.remove('active');
            // ضبط أيقونة زر التبديل
            if (toggleBtn && sidebar) {
                toggleBtn.textContent = sidebar.classList.contains('collapsed') ? 'menu_open' : 'menu';
            }
            // ضبط هامش الحاوية الرئيسية لسطح المكتب
            if (mainContainer && sidebar) {
                mainContainer.style.marginRight = sidebar.classList.contains('collapsed') ?
                    'var(--collapsed-sidebar-width)' :
                    'var(--sidebar-width)';
            }
        } else {
            // إعادة ضبط الإعدادات للجوال
            if (sidebar) {
                sidebar.classList.remove('collapsed'); // إزالة حالة "مصغر"
                sidebar.classList.remove('active');    // إزالة حالة "نشط"
            }
            // إخفاء التراكب
            if (sidebarOverlay) sidebarOverlay.classList.remove('active');
            // إعادة أيقونة زر التبديل إلى حالتها الافتراضية
            if (toggleBtn) {
                toggleBtn.textContent = 'menu';
            }
            // إزالة الهامش من الحاوية الرئيسية على الجوال
            if (mainContainer) mainContainer.style.marginRight = '0';
        }
    }

    // --- Dark Mode Functions (دوال الوضع الداكن) ---
    // دالة لتبديل الوضع الداكن/الفاتح
    function toggleDark() {
        // تبديل فئة 'dark-mode' على عنصر body
        document.body.classList.toggle('dark-mode');
        // إذا كان الوضع الداكن مفعلاً
        if (document.body.classList.contains('dark-mode')) {
            // تغيير مصدر الصورة الرئيسية للشعار إلى النسخة الداكنة
            if (mainLogo) mainLogo.src = mainLogo.getAttribute('data-dark-src');
            // حفظ تفضيل الوضع الداكن في التخزين المحلي
            localStorage.setItem('theme', 'dark');
        } else {
            // تغيير مصدر الصورة الرئيسية للشعار إلى النسخة الفاتحة
            if (mainLogo) mainLogo.src = mainLogo.getAttribute('data-light-src');
            // حفظ تفضيل الوضع الفاتح في التخزين المحلي
            localStorage.setItem('theme', 'light');
        }
    }

    // دالة لتهيئة الوضع الداكن عند تحميل الصفحة
    function initDarkMode() {
        // الحصول على تفضيل الوضع المحفوظ من التخزين المحلي
        const savedTheme = localStorage.getItem('theme');
        // إذا كان الوضع المحفوظ "dark" أو (لم يكن هناك وضع محفوظ ويفضل المستخدم الوضع الداكن في إعدادات النظام)
        if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            // إضافة فئة 'dark-mode' إلى عنصر body
            document.body.classList.add('dark-mode');
            // تغيير مصدر الصورة الرئيسية للشعار إلى النسخة الداكنة
            if (mainLogo) mainLogo.src = mainLogo.getAttribute('data-dark-src');
        } else {
            // إزالة فئة 'dark-mode' من عنصر body
            document.body.classList.remove('dark-mode');
            // تغيير مصدر الصورة الرئيسية للشعار إلى النسخة الفاتحة
            if (mainLogo) mainLogo.src = mainLogo.getAttribute('data-light-src');
        }
    }

    // --- Task Management Functions (دوال إدارة المهام) ---
    // دالة لعرض المهام في القائمة
    function renderTasks() {
        // الخروج إذا لم يتم العثور على عنصر قائمة المهام
        if (!taskList) return;
        // مسح المهام الموجودة حالياً في القائمة
        taskList.innerHTML = '';

        // تصفية المهام بناءً على حالة 'showCompletedTasks' (عرض المهام المنجزة أو لا)
        const filteredTasks = tasks.filter(task => showCompletedTasks || !task.completed);

        // إذا لم تكن هناك مهام بعد التصفية
        if (filteredTasks.length === 0) {
            const noTaskMessage = document.createElement('li'); // إنشاء عنصر قائمة لرسالة
            // تحديد نص الرسالة بناءً على ما إذا كانت المهام المنجزة معروضة أم لا
            noTaskMessage.textContent = showCompletedTasks ? 'لا توجد مهام منجزة حاليًا.' : 'لا توجد مهام معلقة حاليًا.';
            // إضافة بعض التنسيقات لرسالة "لا توجد مهام"
            noTaskMessage.style.cssText = 'text-align: center; color: var(--text-light); padding: 10px 0; border-bottom: none;';
            taskList.appendChild(noTaskMessage); // إضافة الرسالة إلى القائمة
            return; // الخروج من الدالة
        }

        // المرور على كل مهمة في المهام المصفاة وإنشاء عنصر القائمة الخاص بها
        filteredTasks.forEach((task) => {
            const listItem = document.createElement('li'); // إنشاء عنصر قائمة (li) للمهمة
            listItem.classList.add('task-item'); // إضافة فئة للمهمة
            // إذا كانت المهمة مكتملة، أضف فئة 'completed'
            if (task.completed) {
                listItem.classList.add('completed');
            }

            // تهيئة تاريخ الاستحقاق للعرض
            const dueDate = task.dueDate ? new Date(task.dueDate) : null;
            // تنسيق تاريخ الاستحقاق ليكون قابلاً للقراءة
            const formattedDueDate = dueDate && !isNaN(dueDate.getTime()) ?
                `${dueDate.toLocaleDateString('ar-EG', { day: 'numeric', month: 'short' })} ${dueDate.toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' })}`
                : 'لا يوجد تاريخ'; // إذا لم يكن هناك تاريخ

            listItem.dataset.taskId = task.id; // تخزين معرف المهمة كخاصية بيانات على عنصر القائمة
            // تعيين المحتوى الداخلي لعنصر القائمة باستخدام قالب (template literal)
            listItem.innerHTML = `
                <div class="task-details">
                    <div class="task-main-info">
                        <input type="checkbox" class="task-checkbox" ${task.completed ? 'checked' : ''} data-task-id="${task.id}" aria-label="أنجزت المهمة">
                        <span class="task-text">${task.text}</span>
                    </div>
                    <div class="task-meta">
                        <span>الأولوية: <strong class="priority-${task.priority.toLowerCase()}">${task.priority}</strong></span>
                        <span>التصنيف: ${task.category}</span>
                        <span>التاريخ: ${formattedDueDate}</span>
                    </div>
                </div>
                <div class="task-actions">
                    <button class="edit-task-btn material-icons" data-task-id="${task.id}" aria-label="تعديل المهمة">edit</button>
                    <button class="delete-task-btn material-icons" data-task-id="${task.id}" aria-label="حذف المهمة">delete</button>
                </div>
            `;
            taskList.appendChild(listItem); // إضافة عنصر القائمة إلى قائمة المهام
        });

        // إضافة مستمعي الأحداث للعناصر المنشأة حديثاً (مثل مربعات الاختيار والأزرار)
        // لكل مربع اختيار مهمة، أضف مستمع حدث 'change'
        querySelectorAllSafe('.task-checkbox', taskList).forEach(checkbox => {
            checkbox.addEventListener('change', (e) => toggleTaskCompleted(parseInt(e.target.dataset.taskId)));
        });
        // لكل زر تعديل مهمة، أضف مستمع حدث 'click'
        querySelectorAllSafe('.edit-task-btn', taskList).forEach(btn => {
            btn.addEventListener('click', (e) => openEditTaskModal(parseInt(e.target.dataset.taskId)));
        });
        // لكل زر حذف مهمة، أضف مستمع حدث 'click' يعرض نافذة تأكيد
        querySelectorAllSafe('.delete-task-btn', taskList).forEach(btn => {
            btn.addEventListener('click', (e) => {
                showConfirmModal('هل أنت متأكد أنك تريد حذف هذه المهمة؟', () => deleteTaskConfirmed(parseInt(e.target.dataset.taskId)));
            });
        });
    }

    // --- Scroll to Top Button Functions (دوال زر "العودة للأعلى") ---
    // دالة لتبديل عرض زر "العودة للأعلى" بناءً على موضع التمرير
    function toggleScrollToTopButton() {
        if (scrollToTopBtn) { // التأكد من وجود الزر
            // إذا كان موضع التمرير الرأسي أكبر من 300 بكسل
            if (window.pageYOffset > 300) {
                scrollToTopBtn.style.display = 'flex'; // إظهار الزر
            } else {
                scrollToTopBtn.style.display = 'none'; // إخفاء الزر
            }
        }
    }

    // دالة لتمرير الصفحة إلى الأعلى بسلاسة
    function scrollToTop() {
        // استخدام سلوك التمرير السلس
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // --- Task Modals and Actions (نوافذ المهام وإجراءاتها) ---
    // دالة لفتح نافذة إضافة مهمة جديدة
    function openAddTaskModal() {
        // التحقق من وجود جميع عناصر الإدخال والمودال
        if (addTaskModal && taskTextInput && taskPriorityInput && taskDueDateInput && taskCategoryInput) {
            addTaskModal.classList.add('show'); // إضافة فئة 'show' لإظهار المودال
            // مسح حقول الإدخال لمهمة جديدة
            taskTextInput.value = '';
            taskPriorityInput.value = 'متوسطة';
            taskDueDateInput.value = '';
            taskCategoryInput.value = 'إدارية';
            taskTextInput.focus(); // تركيز المؤشر على حقل نص المهمة
        }
    }

    // دالة لإغلاق نافذة إضافة مهمة جديدة
    function closeAddTaskModal() {
        if (addTaskModal) { // التأكد من وجود المودال
            addTaskModal.classList.remove('show'); // إزالة فئة 'show' لإخفاء المودال
        }
    }

    // دالة لإضافة مهمة
    function addTask() {
        // الخروج إذا لم يتم العثور على أي من حقول الإدخال
        if (!taskTextInput || !taskPriorityInput || !taskDueDateInput || !taskCategoryInput) return;
        // الحصول على قيم حقول الإدخال وإزالة المسافات البيضاء الزائدة
        const newTaskText = taskTextInput.value.trim();
        const newPriority = taskPriorityInput.value;
        const newDueDate = taskDueDateInput.value;
        const newCategory = taskCategoryInput.value;

        // إذا كان نص المهمة غير فارغ
        if (newTaskText !== '') {
            // إضافة مهمة جديدة إلى المصفوفة
            tasks.push({
                id: Date.now(), // معرف فريد للمهمة (الطابع الزمني الحالي)
                text: newTaskText,
                priority: newPriority,
                dueDate: newDueDate,
                category: newCategory,
                completed: false // المهمة غير مكتملة افتراضياً
            });
            localStorage.setItem('tasks', JSON.stringify(tasks)); // حفظ المهام في التخزين المحلي
            renderTasks(); // إعادة عرض قائمة المهام
            closeAddTaskModal(); // إغلاق المودال
        } else {
            showMessageModal('خطأ', 'الرجاء إدخال نص للمهمة!'); // عرض رسالة خطأ إذا كان نص المهمة فارغاً
        }
    }

    // دالة لتأكيد حذف مهمة (يتم استدعاؤها بعد تأكيد المستخدم)
    function deleteTaskConfirmed(taskId) {
        // تصفية مصفوفة المهام لإزالة المهمة ذات المعرف المطابق
        tasks = tasks.filter(task => task.id !== taskId);
        localStorage.setItem('tasks', JSON.stringify(tasks)); // حفظ المهام المحدثة في التخزين المحلي
        renderTasks(); // إعادة عرض قائمة المهام
    }

    // دالة لفتح نافذة تعديل المهمة
    function openEditTaskModal(taskId) {
        // البحث عن المهمة المراد تعديلها بواسطة المعرف
        const taskToEdit = tasks.find(t => t.id === taskId);
        if (!taskToEdit) { // إذا لم يتم العثور على المهمة
            showMessageModal('خطأ', 'المهمة غير موجودة.'); // عرض رسالة خطأ
            return;
        }

        // التحقق من وجود جميع عناصر الإدخال والمودال
        if (editTaskModal && editTaskIndexInput && editTaskTextInput && editTaskPriorityInput && editTaskDueDateInput && editTaskCategoryInput) {
            editTaskIndexInput.value = taskId; // تخزين معرف المهمة للتحديث
            editTaskTextInput.value = taskToEdit.text;
            editTaskPriorityInput.value = taskToEdit.priority;
            editTaskDueDateInput.value = taskToEdit.dueDate || ''; // التعامل مع تاريخ استحقاق فارغ
            editTaskCategoryInput.value = taskToEdit.category;
            editTaskModal.classList.add('show'); // إظهار مودال التعديل
        }
    }

    // دالة لإغلاق نافذة تعديل المهمة
    function closeEditTaskModal() {
        if (editTaskModal) { // التأكد من وجود المودال
            editTaskModal.classList.remove('show'); // إخفاء مودال التعديل
        }
    }

    // دالة لتحديث المهمة
    function updateTask() {
        // الحصول على معرف المهمة من حقل الإدخال المخفي
        const taskId = parseInt(editTaskIndexInput.value);
        // البحث عن فهرس المهمة في مصفوفة المهام
        const taskIndex = tasks.findIndex(t => t.id === taskId);

        // إذا لم يتم العثور على معرف المهمة أو الفهرس غير صحيح
        if (isNaN(taskId) || taskIndex === -1) {
            showMessageModal('خطأ', 'فشل تحديث المهمة.'); // عرض رسالة خطأ
            return;
        }

        // الحصول على القيم المحدثة من حقول الإدخال وإزالة المسافات البيضاء
        const updatedText = editTaskTextInput.value.trim();
        const updatedPriority = editTaskPriorityInput.value;
        const updatedDueDate = editTaskDueDateInput.value;
        const updatedCategory = editTaskCategoryInput.value;

        // إذا كان النص المحدث غير فارغ
        if (updatedText !== '') {
            // تحديث خصائص المهمة في المصفوفة
            tasks[taskIndex].text = updatedText;
            tasks[taskIndex].priority = updatedPriority;
            tasks[taskIndex].dueDate = updatedDueDate;
            tasks[taskIndex].category = updatedCategory;
            localStorage.setItem('tasks', JSON.stringify(tasks)); // حفظ المهام المحدثة
            renderTasks(); // إعادة عرض قائمة المهام
            closeEditTaskModal(); // إغلاق مودال التعديل
        } else {
            showMessageModal('خطأ', 'الرجاء إدخال نص للمهمة!'); // عرض رسالة خطأ إذا كان النص فارغاً
        }
    }

    // دالة لتبديل حالة إنجاز المهمة (مكتملة/غير مكتملة)
    function toggleTaskCompleted(taskId) {
        // البحث عن فهرس المهمة بواسطة المعرف
        const taskIndex = tasks.findIndex(t => t.id === taskId);
        if (taskIndex > -1) { // إذا تم العثور على المهمة
            tasks[taskIndex].completed = !tasks[taskIndex].completed; // تبديل حالة الإنجاز
            localStorage.setItem('tasks', JSON.stringify(tasks)); // حفظ المهام المحدثة
            renderTasks(); // إعادة عرض قائمة المهام
        }
    }

    // دالة لتبديل عرض المهام المنجزة (إظهار/إخفاء)
    function toggleCompletedTasksView() {
        showCompletedTasks = !showCompletedTasks; // تبديل حالة العرض
        if (toggleCompletedTasksBtn) { // التأكد من وجود الزر
            // تبديل فئة 'active' على الزر
            toggleCompletedTasksBtn.classList.toggle('active', showCompletedTasks);
            // تغيير أيقونة الزر بناءً على حالة العرض
            toggleCompletedTasksBtn.querySelector('.material-icons').textContent = showCompletedTasks ? 'visibility_off' : 'check_circle_outline';
            // تغيير تلميح (title) الزر
            toggleCompletedTasksBtn.title = showCompletedTasks ? 'إخفاء المهام المنجزة' : 'عرض المهام المنجزة';
        }
        renderTasks(); // إعادة عرض المهام بالفلتر الجديد
    }

    // --- Calendar Functions (دوال التقويم) ---
    // دالة لعرض التقويم
    function renderCalendar() {
        // الخروج إذا لم يتم العثور على شبكة التقويم أو عنصر عرض الشهر/السنة
        if (!calendarGrid || !currentMonthYearSpan) return;

        calendarGrid.innerHTML = ''; // مسح أيام التقويم السابقة
        currentMonthEventsList.innerHTML = ''; // مسح قائمة الأحداث السابقة

        // أسماء الشهور والأيام باللغة العربية
        const monthNames = ["يناير", "فبراير", "مارس", "أبريل", "مايو", "يونيو", "يوليو", "أغسطس", "سبتمبر", "أكتوبر", "نوفمبر", "ديسمبر"];
        const dayNames = ["الأحد", "الاثنين", "الثلاثاء", "الأربعاء", "الخميس", "الجمعة", "السبت"];

        // تحديث نص عرض الشهر والسنة الحاليين
        currentMonthYearSpan.textContent = `${monthNames[currentCalendarDate.getMonth()]} ${currentCalendarDate.getFullYear()}`;

        // إضافة أسماء الأيام إلى شبكة التقويم
        dayNames.forEach(day => {
            const dayNameCell = document.createElement('div'); // إنشاء خلية لاسم اليوم
            dayNameCell.classList.add('day-name'); // إضافة فئة لتنسيق اسم اليوم
            dayNameCell.textContent = day; // تعيين نص اسم اليوم
            calendarGrid.appendChild(dayNameCell); // إضافة الخلية إلى شبكة التقويم
        });

        // الحصول على أول وآخر يوم في الشهر الحالي
        const firstDayOfMonth = new Date(currentCalendarDate.getFullYear(), currentCalendarDate.getMonth(), 1);
        const lastDayOfMonth = new Date(currentCalendarDate.getFullYear(), currentCalendarDate.getMonth() + 1, 0);
        // عدد الأيام في الشهر
        const daysInMonth = lastDayOfMonth.getDate();
        // اليوم الأول من الأسبوع في الشهر (0 للأحد، 1 للاثنين، إلخ)
        const firstDayOfWeek = firstDayOfMonth.getDay();

        // إضافة خلايا فارغة للأيام قبل اليوم الأول من الشهر
        for (let i = 0; i < firstDayOfWeek; i++) {
            const emptyCell = document.createElement('div'); // إنشاء خلية فارغة
            emptyCell.classList.add('day-cell', 'not-current-month'); // إضافة فئات للخلية الفارغة
            calendarGrid.appendChild(emptyCell); // إضافة الخلية الفارغة إلى شبكة التقويم
        }

        // إضافة الأيام الفعلية للشهر
        for (let day = 1; day <= daysInMonth; day++) {
            const dayCell = document.createElement('div'); // إنشاء خلية لليوم
            dayCell.classList.add('day-cell', 'current-month'); // إضافة فئات للخلية
            dayCell.textContent = day; // تعيين رقم اليوم
            // تخزين التاريخ الكامل كخاصية بيانات
            dayCell.dataset.date = `${currentCalendarDate.getFullYear()}-${currentCalendarDate.getMonth() + 1}-${day}`;

            const today = new Date(); // الحصول على تاريخ اليوم
            // إذا كان اليوم هو اليوم الحالي من الشهر والسنة
            if (day === today.getDate() &&
                currentCalendarDate.getMonth() === today.getMonth() &&
                currentCalendarDate.getFullYear() === today.getFullYear()) {
                dayCell.classList.add('today'); // تمييز تاريخ اليوم
            }

            // إضافة نقاط الأحداث إذا كانت هناك أحداث في هذا اليوم
            const dayEvents = events.filter(event => {
                const eventDate = new Date(event.dateTime);
                // التحقق مما إذا كان تاريخ الحدث يطابق اليوم والشهر والسنة الحاليين
                return eventDate.getDate() === day &&
                    eventDate.getMonth() === currentCalendarDate.getMonth() &&
                    eventDate.getFullYear() === currentCalendarDate.getFullYear();
            });

            if (dayEvents.length > 0) { // إذا كان هناك أحداث في هذا اليوم
                const eventDot = document.createElement('span'); // إنشاء نقطة حدث
                eventDot.classList.add('event-dot'); // إضافة فئة لنقطة الحدث
                dayCell.appendChild(eventDot); // إضافة نقطة الحدث إلى خلية اليوم
            }
            calendarGrid.appendChild(dayCell); // إضافة خلية اليوم إلى شبكة التقويم
        }
        renderEventsForCurrentMonth(); // عرض قائمة الأحداث للشهر الحالي
    }

    // دالة لتغيير الشهر في التقويم
    function changeMonth(delta) {
        // تغيير الشهر الحالي في كائن التاريخ (بزيادة أو نقصان دلتا)
        currentCalendarDate.setMonth(currentCalendarDate.getMonth() + delta);
        renderCalendar(); // إعادة عرض التقويم
    }

    // دالة لفتح نافذة إضافة حدث جديد
    function openAddEventModal() {
        // التحقق من وجود جميع عناصر الإدخال والمودال
        if (addEventModal && eventTitleInput && eventDateTimeInput && eventTypeInput) {
            addEventModal.classList.add('show'); // إظهار المودال
            // مسح حقول الإدخال لحدث جديد
            eventTitleInput.value = '';
            eventDateTimeInput.value = '';
            eventTypeInput.value = 'عام'; // تعيين القيمة الافتراضية
        }
    }

    // دالة لإغلاق نافذة إضافة حدث جديد
    function closeAddEventModal() {
        if (addEventModal) { // التأكد من وجود المودال
            addEventModal.classList.remove('show'); // إخفاء المودال
        }
    }

    // دالة لإضافة حدث
    function addEvent() {
        // الخروج إذا لم يتم العثور على أي من حقول الإدخال
        if (!eventTitleInput || !eventDateTimeInput || !eventTypeInput) return;
        // الحصول على قيم حقول الإدخال وإزالة المسافات البيضاء
        const title = eventTitleInput.value.trim();
        const dateTime = eventDateTimeInput.value;
        const type = eventTypeInput.value;

        // إذا كان العنوان والتاريخ/الوقت غير فارغين
        if (title && dateTime) {
            // إضافة حدث جديد إلى المصفوفة
            events.push({
                id: Date.now(), // معرف فريد للحدث
                title: title,
                dateTime: dateTime,
                type: type
            });
            localStorage.setItem('events', JSON.stringify(events)); // حفظ الأحداث في التخزين المحلي
            renderCalendar(); // إعادة عرض التقويم (والذي يقوم أيضاً بتحديث قائمة الأحداث)
            closeAddEventModal(); // إغلاق المودال
        } else {
            showMessageModal('خطأ', 'الرجاء إدخال عنوان وتاريخ/وقت للحدث.'); // عرض رسالة خطأ
        }
    }

    // دالة لعرض الأحداث للشهر الحالي
    function renderEventsForCurrentMonth() {
        if (!currentMonthEventsList) return; // الخروج إذا لم يتم العثور على قائمة الأحداث
        currentMonthEventsList.innerHTML = ''; // مسح الأحداث الموجودة حالياً

        // تصفية الأحداث للشهر الحالي وترتيبها حسب التاريخ/الوقت
        const currentMonthEvents = events.filter(event => {
            const eventDate = new Date(event.dateTime);
            // التحقق مما إذا كان تاريخ الحدث يقع في الشهر والسنة الحاليين
            return eventDate.getMonth() === currentCalendarDate.getMonth() &&
                eventDate.getFullYear() === currentCalendarDate.getFullYear();
        }).sort((a, b) => new Date(a.dateTime) - new Date(b.dateTime)); // ترتيب الأحداث تصاعدياً

        if (currentMonthEvents.length === 0) { // إذا لم تكن هناك أحداث في الشهر الحالي
            currentMonthEventsList.innerHTML = '<li style="text-align: center; color: var(--text-light); border-bottom: none;">لا توجد أحداث هذا الشهر.</li>';
            return; // الخروج من الدالة
        }

        // المرور على كل حدث في الأحداث المصفاة وعرضه
        currentMonthEvents.forEach(event => {
            const listItem = document.createElement('li'); // إنشاء عنصر قائمة للحدث
            const eventDateTime = new Date(event.dateTime);
            // تنسيق الوقت والتاريخ للعرض
            const formattedTime = eventDateTime.toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' });
            const formattedDate = eventDateTime.toLocaleDateString('ar-EG', { day: 'numeric', month: 'short' });

            // تعيين المحتوى الداخلي لعنصر القائمة باستخدام قالب
            listItem.innerHTML = `
                <span>
                    <span class="event-time">${formattedDate} - ${formattedTime}</span>
                    ${event.title}
                </span>
                <span class="event-type">${event.type}</span>
                <button class="delete-event-btn material-icons" title="حذف الحدث" style="background:none;border:none;color:var(--danger-color);font-size:20px;cursor:pointer;">delete</button>
            `;
            // إضافة مستمع حدث لزر الحذف
            listItem.querySelector('.delete-event-btn').addEventListener('click', () => {
                showConfirmModal('هل أنت متأكد أنك تريد حذف هذا الحدث؟', () => deleteEventConfirmed(event.id));
            });

            currentMonthEventsList.appendChild(listItem); // إضافة عنصر القائمة إلى قائمة الأحداث
        });
    }

    // دالة لتأكيد حذف حدث (يتم استدعاؤها بعد تأكيد المستخدم)
    function deleteEventConfirmed(eventId) {
        // تصفية مصفوفة الأحداث لإزالة الحدث ذي المعرف المطابق
        events = events.filter(event => event.id !== eventId);
        localStorage.setItem('events', JSON.stringify(events)); // حفظ الأحداث المحدثة
        renderCalendar(); // إعادة عرض التقويم لتحديث نقاط الأحداث والقائمة
    }

    // --- Generic Modal Functions (دوال المودال العامة) ---
    // دالة عامة لإظهار أي مودال
    function showModal(modalElement) {
        if (modalElement) { // التأكد من وجود العنصر
            modalElement.classList.add('show'); // إضافة فئة 'show' لإظهار المودال
        }
    }

    // دالة عامة لإخفاء أي مودال
    function hideModal(modalElement) {
        if (modalElement) { // التأكد من وجود العنصر
            modalElement.classList.remove('show'); // إزالة فئة 'show' لإخفاء المودال
        }
    }

    // --- Message Modal Functions (دوال مودال الرسائل) ---
    // دالة لعرض مودال رسالة
    function showMessageModal(title, message) {
        // التحقق من وجود جميع عناصر المودال
        if (messageModal && messageModalTitle && messageModalText) {
            messageModalTitle.textContent = title; // تعيين عنوان الرسالة
            messageModalText.innerHTML = message; // تعيين نص الرسالة (يمكن أن يحتوي على HTML)
            showModal(messageModal); // إظهار المودال
        }
    }

    // دالة لإغلاق مودال الرسائل
    function closeMessageModal() {
        hideModal(messageModal); // إخفاء المودال
    }

    // --- Confirm Modal Functions (دوال مودال التأكيد) ---
    // دالة لعرض مودال التأكيد
    function showConfirmModal(message, callback) {
        // التحقق من وجود جميع عناصر المودال
        if (confirmModal && confirmModalText) {
            confirmModalText.textContent = message; // تعيين نص رسالة التأكيد
            confirmCallback = callback; // تخزين دالة رد الاتصال (لتشغيلها عند التأكيد)
            showModal(confirmModal); // إظهار المودال
        }
    }

    // دالة لإغلاق مودال التأكيد
    function closeConfirmModal() {
        hideModal(confirmModal); // إخفاء المودال
        confirmCallback = null; // مسح دالة رد الاتصال
    }

    // --- Event Listeners on DOMContentLoaded (مستمعي الأحداث عند تحميل محتوى DOM) ---
    // يتم تنفيذ هذا الكود عندما يتم تحميل جميع عناصر DOM
    document.addEventListener('DOMContentLoaded', () => {
        initDarkMode(); // تهيئة الوضع الداكن
        renderTasks(); // عرض قائمة المهام الأولية
        renderCalendar(); // عرض التقويم والأحداث الأولية

        adjustSidebarOnResize(); // ضبط حالة الشريط الجانبي عند التحميل الأولي

        // مستمعو أحداث الشريط الجانبي وتبديل الوضع الداكن
        if (toggleBtn) toggleBtn.addEventListener('click', toggleSidebar); // زر تبديل الشريط الجانبي
        if (sidebarOverlay) sidebarOverlay.addEventListener('click', toggleSidebar); // تراكب الشريط الجانبي (لإغلاقه عند النقر خارجه)
        if (darkModeToggle) darkModeToggle.addEventListener('click', toggleDark); // زر تبديل الوضع الداكن

        // مستمعو أحداث إدارة المهام
        if (toggleCompletedTasksBtn) toggleCompletedTasksBtn.addEventListener('click', toggleCompletedTasksView); // زر تبديل عرض المهام المنجزة

        // التنقل في التقويم
        if (prevMonthBtn) prevMonthBtn.addEventListener('click', () => changeMonth(1)); // زر الشهر التالي (RTL)
        if (nextMonthBtn) nextMonthBtn.addEventListener('click', () => changeMonth(-1)); // زر الشهر السابق (RTL)

        // إغلاق المودالات عند النقر خارجها
        window.addEventListener('click', (event) => {
            if (event.target === addTaskModal) closeAddTaskModal(); // مودال إضافة مهمة
            if (event.target === messageModal) closeMessageModal(); // مودال الرسائل
            if (event.target === confirmModal) closeConfirmModal(); // مودال التأكيد
            if (event.target === addEventModal) closeAddEventModal(); // مودال إضافة حدث
            if (event.target === editTaskModal) closeEditTaskModal(); // مودال تعديل مهمة
        });

        // وظائف زر "العودة للأعلى"
        window.addEventListener('scroll', toggleScrollToTopButton); // عرض/إخفاء الزر عند التمرير
        if (scrollToTopBtn) {
            scrollToTopBtn.addEventListener('click', scrollToTop); // النقر على الزر للتمرير للأعلى
        }

        // إجراءات أزرار مودال التأكيد
        if (confirmOkBtn) { // زر التأكيد (موافق)
            confirmOkBtn.addEventListener('click', () => {
                if (confirmCallback) confirmCallback(); // تنفيذ دالة رد الاتصال المخزنة
                closeConfirmModal(); // إغلاق المودال
            });
        }
        if (confirmCancelBtn) confirmCancelBtn.addEventListener('click', closeConfirmModal); // زر الإلغاء
    });
</script>
</body>
</html>

