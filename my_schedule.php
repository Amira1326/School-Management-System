<?php
session_start();

// Database Connection Information
$host = "localhost";
$dbname = "aljood_center";
$username = "root";
$password = "";

$conn = new mysqli($host, $username, $password, $dbname);
$conn->set_charset("utf8"); // Essential for proper handling of Arabic characters

// Check for connection errors
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// Check if the teacher is logged in
if (!isset($_SESSION['teacher_id'])) {
    header("Location: teacher-login.php"); // Ensure a teacher login page exists
    exit("من فضلك قومي بتسجيل الدخول كمعلمة.");
}

$teacherId = $_SESSION['teacher_id'];
$teacherName = htmlspecialchars($_SESSION['teacher_name'] ?? 'معلمة'); // Teacher's name from the session

// Days of the week and lesson slots
$daysOfWeek = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس'];
$slots = ['الحصة الأولى', 'الحصة الثانية', 'الحصة الثالثة', 'الحصة الرابعة', 'الحصة الخامسة', 'الحصة السادسة', 'الحصة السابعة'];

// Available subjects for selection in the schedule
$availableSubjects = [
    '', // Empty value to represent "Select Subject"
    'الرياضيات', 'الاجتماعيات', 'علوم', 'لغتي',
    'اللغة الإنجليزية', 'الدراسات الإسلامية', 'المهارات الرقمية','القرآن الكريم' ,'-'
    // Add more subjects here as needed
];

// Determine the selected period (morning/evening) from URL parameter
$selectedPeriod = $_GET['period'] ?? null;

// If the period is not selected yet, display the period selection options
if (is_null($selectedPeriod) || !in_array($selectedPeriod, ['صباحي', 'مسائي'])) {
    ?>
    <!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>اختيار الفترة - مركز الجود التعليمي</title>
        <link rel="icon" href="logo.png" type="image/x-icon">
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        <style>
            :root {
                --primary-color: #89355d;
                --primary-color-light: #a34e7b;
                --secondary-color: #E0BBE4;
                --text-dark: #333;
                --white: #ffffff;
                --gray-light: #f9f9f9;
                --border-radius-large: 12px;
                --border-radius-pill: 25px;
                --spacing-large: 1.875rem;
                --spacing-xl: 3.125rem;
                --spacing-medium: 1rem;
                --spacing-small: 0.75rem;
            }
            body {
                font-family: 'Cairo', sans-serif;
                direction: rtl;
                margin: 0;
                padding: 0;
                background: linear-gradient(to bottom, var(--white) 0%, var(--secondary-color) 100%);
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                box-sizing: border-box;
                color: var(--text-dark);
            }
            .container {
                width: 90%;
                max-width: 500px;
                padding: var(--spacing-large) var(--spacing-xl);
                background: var(--white);
                border-radius: var(--border-radius-large);
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
                text-align: center;
            }
            h1 {
                color: var(--primary-color);
                margin-bottom: var(--spacing-large);
                font-size: 2rem;
                font-weight: 700;
            }
            h2 {
                color: var(--text-dark);
                font-size: 1.5rem;
                margin-bottom: var(--spacing-medium);
            }
            .period-selection {
                display: flex;
                flex-direction: column;
                gap: 15px;
                margin-top: var(--spacing-large);
            }
            .period-selection button {
                padding: 15px 30px;
                font-size: 1.2rem;
                font-weight: bold;
                color: var(--white);
                background-color: var(--primary-color);
                border: none;
                border-radius: var(--border-radius-pill);
                cursor: pointer;
                transition: background-color 0.3s ease, transform 0.2s ease;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
            }
            .period-selection button:hover {
                background-color: var(--primary-color-light);
                transform: translateY(-2px);
                box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
            }
            .period-selection .material-icons {
                font-size: 28px;
            }

            /* Responsive adjustments for Period Selection */
            @media (max-width: 600px) {
                .container {
                    padding: var(--spacing-medium) var(--spacing-large);
                    width: 95%;
                }
                h1 {
                    font-size: 1.7rem;
                }
                h2 {
                    font-size: 1.3rem;
                }
                .period-selection button {
                    font-size: 1rem;
                    padding: 12px 20px;
                }
                .period-selection .material-icons {
                    font-size: 24px;
                }
            }
        </style>
    </head>
    <body class="period-selection-container">
        <div class="container">
            <h1>مرحباً، <?php echo $teacherName; ?>!</h1>
            <h2>الرجاء اختيار الفترة لعرض الجدول:</h2>
            <div class="period-selection">
                <button onclick="window.location.href='?period=صباحي'">
                    <span class="material-icons">wb_sunny</span>
                    الفترة الصباحية
                </button>
                <button onclick="window.location.href='?period=مسائي'">
                    <span class="material-icons">nights_stay</span>
                    الفترة المسائية
                </button>
            </div>
        </div>
    </body>
    </html>
    <?php
    $conn->close();
    exit(); // Stop further execution after displaying the selection page
}

// ****** If the period is selected, display the schedule as usual ******

// Fetch the current teacher's schedule from the database using Arabic column names and period type
$currentSchedule = [];
// Use backticks for column names with spaces or special characters
$stmt = $conn->prepare("SELECT `اليوم`, `وقت_الحصة`, `اسم_المادة` FROM `teacher_schedules` WHERE `معرف_المعلمة` = ? AND `نوع_الفترة` = ?");
$stmt->bind_param("is", $teacherId, $selectedPeriod); // 'is' stands for integer then string
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $currentSchedule[$row['اليوم']][$row['وقت_الحصة']] = $row['اسم_المادة'];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جدول المعلمة التفاعلي - مركز الجود التعليمي (<?= htmlspecialchars($selectedPeriod) ?>)</title>
    <link rel="icon" href="logo.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <style>
        :root {
            --primary-color: #89355d;
            --primary-color-light: #a34e7b;
            --secondary-color: #E0BBE4;
            --text-dark: #333;
            --text-medium: #555;
            --white: #ffffff;
            --gray-light: #f9f9f9;
            --border-color: #ddd;
            --shadow-light: rgba(0, 0, 0, 0.08);
            --border-radius-large: 12px;
            --border-radius-small: 8px;
            --border-radius-pill: 25px;
            --spacing-xs: 0.5rem;
            --spacing-small: 0.75rem;
            --spacing-medium: 1rem;
            --spacing-large: 1.5rem;
            --spacing-xl: 2rem;
        }

        body {
            font-family: 'Cairo', sans-serif;
            direction: rtl;
            margin: 0;
            padding: 0;
            background: linear-gradient(to bottom, var(--white) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-sizing: border-box;
            color: var(--text-dark);
        }

        .container {
            width: 98%;
            max-width: 1400px;
            margin: var(--spacing-medium) auto;
            padding: var(--spacing-medium);
            background: var(--white);
            border-radius: var(--border-radius-large);
            box-shadow: 0 8px 24px var(--shadow-light);
            position: relative;
            overflow-x: auto; /* Allows horizontal scrolling for large tables */
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
        }

        .header-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: var(--spacing-medium);
            padding-top: var(--spacing-small);
        }

        .logo {
            width: 80px;
            height: auto;
            opacity: 0.9;
            margin-bottom: var(--spacing-xs);
        }

        h1 {
            color: var(--primary-color);
            margin-top: 0;
            margin-bottom: var(--spacing-xs);
            font-size: 1.8rem;
            font-weight: 700;
            text-align: center;
        }
        
        .schedule-header h2 {
            font-size: 1.5rem;
            text-align: center;
            margin-bottom: var(--spacing-small);
            color: var(--primary-color);
            font-weight: 700;
        }

        .schedule-container {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            max-height: calc(100vh - 220px); /* Adjust height to prevent content overflow */
            margin-top: var(--spacing-medium);
        }

        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: var(--spacing-small);
            border-radius: var(--border-radius-small);
            overflow: hidden;
            flex-grow: 1;
            display: table;
            table-layout: fixed; /* Ensures columns have fixed width */
        }

        .schedule-table th,
        .schedule-table td {
            padding: 8px 4px;
            text-align: center;
            border: 1px solid var(--border-color);
            font-size: 0.8rem;
        }

        .schedule-table th:first-child,
        .schedule-table td:first-child {
            width: 120px; /* Fixed width for the first column */
            min-width: 120px;
        }

        .schedule-table th:not(:first-child),
        .schedule-table td:not(:first-child) {
            width: auto; /* Auto width for other columns */
        }

        .schedule-table th {
            background-color: var(--primary-color);
            color: var(--white);
            font-weight: 700;
        }

        .schedule-table tbody tr:nth-child(even) {
            background-color: var(--gray-light);
        }

        .schedule-table tbody tr:hover {
            background-color: #f0e6f2;
        }

        .schedule-table select {
            width: 95%;
            padding: 5px 8px;
            border-radius: var(--border-radius-small);
            border: 1px solid var(--border-color);
            font-size: 0.8rem;
            transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            background-color: var(--white);
            appearance: none; /* Removes default dropdown arrow */
        }

        .schedule-table select:focus {
            border-color: var(--primary-color-light);
            box-shadow: 0 0 0 3px rgba(137, 53, 93, 0.1);
            outline: none;
        }

        .action-buttons-container {
            display: flex;
            /* التعديل هنا: دفع جميع الأزرار لليمين */
            justify-content: flex-end; /* يدفع العناصر إلى نهاية المحور الرئيسي (اليمين في RTL) */
            align-items: center;
            gap: 15px; /* مسافة بين الأزرار */
            margin-top: var(--spacing-medium);
            margin-bottom: var(--spacing-small);
            padding: 0 var(--spacing-small);
        }

        .save-btn, .print-btn, .back-to-main-btn {
            padding: 10px 25px;
            font-size: 1rem;
            font-weight: bold;
            border: none;
            border-radius: var(--border-radius-pill);
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--white); /* Common color for text */
        }

        .save-btn {
            background-color: var(--primary-color);
        }

        .save-btn:hover {
            background-color: var(--primary-color-light);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .print-btn {
            background-color: #007bff; /* Blue for print button */
        }

        .print-btn:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .back-to-main-btn {
            background-color: #f7a73e; /* Orange for back button */
            text-decoration: none; /* Remove underline for links */
        }

        .back-to-main-btn:hover {
            background-color: #e5902d;
            transform: translateY(-2px);
        }

        .back-to-main-btn .material-icons {
            font-size: 24px;
        }

        /* Alert Messages */
        .alert {
            position: fixed;
            top: 20px;
            right: 20px;
            width: auto;
            max-width: 350px;
            padding: 15px 25px;
            text-align: right;
            border-radius: var(--border-radius-small);
            font-weight: bold;
            border: 1px solid;
            font-size: 0.95rem;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert.show {
            opacity: 1;
            visibility: visible;
        }

        .alert.success {
            background-color: #e6ffed;
            color: #28a745;
            border-color: #c9f0d1;
        }

        .alert.error {
            background-color: #ffe6e6;
            color: #dc3545;
            border-color: #f0c9c9;
        }

        .alert .material-icons {
            font-size: 1.4rem;
        }

        /* Responsive Design for smaller screens (under 768px) */
        @media (max-width: 768px) {
            .container {
                margin: var(--spacing-small) auto;
                padding: var(--spacing-small);
            }
            h1 {
                font-size: 1.5rem;
            }
            .schedule-table th,
            .schedule-table td {
                font-size: 0.7rem;
                padding: 6px 3px;
            }
            .schedule-table select {
                font-size: 0.7rem;
                padding: 3px 5px;
            }
            .action-buttons-container {
                flex-direction: column; /* Stack buttons vertically */
                align-items: flex-end; /* تبقى الأزرار محاذية لليمين حتى عند التكدس */
                gap: 10px;
            }
            .save-btn, .print-btn, .back-to-main-btn {
                width: auto; /* يمكن إزالة 100% للسماح بحجم المحتوى إذا كان align-items: flex-end */
                /* إذا أردت أن تبقى بعرض 100% حتى في الموبايل، أعد width: 100% هنا */
                text-align: center;
                justify-content: center; /* للمحافظة على توسيط النص والأيقونة داخل الزر */
            }
            .alert {
                width: calc(100% - 40px);
                left: 20px;
                right: 20px;
            }
            /* Adjust first column width for smaller screens to save space */
            .schedule-table th:first-child,
            .schedule-table td:first-child {
                width: 90px;
                min-width: 90px;
            }
        }

        /* Print-specific styles: DESIGN SHOULD MATCH SCREEN DESIGN */
        @media print {
            /* Remove default browser margins and ensure background prints */
            @page {
                margin: 0.5cm; /* Small margin for printing */
            }
            body {
                background: none !important; /* Keep original background for container */
                -webkit-print-color-adjust: exact; /* Ensure background colors are printed */
                print-color-adjust: exact; /* Standard property for exact color printing */
            }

            .container {
                box-shadow: none !important; /* Remove shadow to prevent print artifacts */
                margin: 0 auto !important; /* Center container on print page */
                width: 100% !important; /* Use full width for print */
                max-width: 100% !important; /* No max width */
                padding: 10px !important; /* Small padding */
                border-radius: 0 !important; /* No border radius in print */
            }

            /* Hide elements not needed in print */
            .action-buttons-container,
            .alert {
                display: none !important;
            }

            /* Ensure header and logo are visible and styled as on screen */
            .header-section {
                display: flex; /* Keep flex display */
                flex-direction: column;
                align-items: center;
                margin-bottom: 10px; /* Reduced margin */
                padding-top: 5px;
            }

            .logo {
                width: 70px; /* Slightly smaller logo for print */
                height: auto;
                opacity: 1; /* Ensure full opacity */
                margin-bottom: 5px;
            }

            h1, .schedule-header h2 {
                color: var(--primary-color) !important; /* Keep original color */
                text-align: center;
                margin-bottom: 5px !important;
                margin-top: 5px !important;
            }
            
            .schedule-table {
                width: 100% !important;
                border-collapse: collapse;
                border: 1px solid var(--border-color) !important; /* Keep original border */
                margin-bottom: 10px !important;
            }

            .schedule-table th,
            .schedule-table td {
                padding: 6px !important; /* Consistent padding */
                border: 1px solid var(--border-color) !important; /* Keep original cell border */
                font-size: 0.85em !important; /* Slightly larger font for readability */
                color: var(--text-dark) !important; /* Keep original text color */
                background-color: inherit !important; /* Ensure background colors from screen persist */
            }

            .schedule-table th {
                background-color: var(--primary-color) !important; /* Keep original header background */
                color: var(--white) !important; /* Keep original header text color */
            }

            .schedule-table tbody tr:nth-child(even) {
                background-color: var(--gray-light) !important; /* Keep striped rows */
            }

            .schedule-table select {
                -webkit-appearance: none;
                -moz-appearance: none;
                appearance: none;
                border: none !important; /* Remove border from select */
                background: none !important; /* Remove background from select */
                padding: 0 !important;
                margin: 0 !important;
                width: auto !important;
                color: var(--text-dark) !important; /* Ensure text color for selected option */
                font-weight: bold; /* Make selected text bold */
            }

            /* Adjust column widths for better print layout */
            .schedule-table th:first-child,
            .schedule-table td:first-child {
                width: 15% !important; /* Adjust percentage as needed for visual balance */
                min-width: 100px !important;
            }
            .schedule-table th:not(:first-child),
            .schedule-table td:not(:first-child) {
                width: auto !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-section">
            <img src="logo.png" class="logo" alt="شعار المركز">
            <h1>جدول المعلمة - الفترة <?php echo htmlspecialchars($selectedPeriod); ?></h1>
        </div>

            <form id="scheduleForm">
                <input type="hidden" name="selected_period" id="selectedPeriodInput" value="<?= htmlspecialchars($selectedPeriod) ?>">
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th>اليوم/الحصة</th>
                            <?php foreach ($slots as $slot): ?>
                                <th><?php echo $slot; ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daysOfWeek as $day): ?>
                            <tr>
                                <td><?php echo $day; ?></td>
                                <?php foreach ($slots as $slot): ?>
                                    <td>
                                        <select name="schedule[<?php echo $day . '][' . $slot; ?>]">
                                            <?php foreach ($availableSubjects as $subject): ?>
                                                <option value="<?= htmlspecialchars($subject) ?>"
                                                    <?= (isset($currentSchedule[$day][$slot]) && $currentSchedule[$day][$slot] === $subject) ? 'selected' : '' ?>>
                                                    <?= empty($subject) ? 'اختر المادة' : htmlspecialchars($subject) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="action-buttons-container">
                    <button type="button" class="save-btn" onclick="saveSchedule()">
                        <span class="material-icons">save</span> حفظ الجدول
                    </button>
                    <button type="button" class="print-btn" onclick="printSchedule()">
                        <span class="material-icons">print</span> طباعة الجدول
                    </button>
                    <a href="teacher-dashboard.php" class="back-to-main-btn">
    <span class="material-icons">home</span>
    العودة للرئيسية
</a>
                </div>
            </form>
        </div>
    </div>

    <div id="alertMessage" class="alert">
        <span id="alertIcon" class="material-icons"></span>
        <span id="alertText"></span>
    </div>

    <script>
        // Function to display alert messages
        function showAlert(message, type = 'success') {
            const alertBox = document.getElementById('alertMessage');
            const alertText = document.getElementById('alertText');
            const alertIcon = document.getElementById('alertIcon');

            alertText.textContent = message;
            alertBox.className = `alert ${type} show`;

            if (type === 'success') {
                alertIcon.textContent = 'check_circle';
            } else if (type === 'error') {
                alertIcon.textContent = 'error';
            }

            setTimeout(() => {
                alertBox.className = 'alert';
            }, 3000);
        }

        // Function to save the schedule using AJAX
        async function saveSchedule() {
            const form = document.getElementById('scheduleForm');
            const formData = new FormData(form);
            const scheduleData = {};
            const selectedPeriod = document.getElementById('selectedPeriodInput').value;

            formData.forEach((value, key) => {
                const parts = key.match(/schedule\[(.*?)\]\[(.*?)\]/);
                if (parts && parts.length === 3) {
                    const day = parts[1];
                    const slot = parts[2];
                    if (!scheduleData[day]) {
                        scheduleData[day] = {};
                    }
                    scheduleData[day][slot] = value;
                }
            });

            // Add the selected period to the data sent
            const dataToSend = {
                period: selectedPeriod,
                schedule: scheduleData
            };

            try {
                const response = await fetch('save_schedule.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(dataToSend)
                });

                const result = await response.json();

                if (result.status === 'success') {
                    showAlert(result.message, 'success');
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (error) {
                console.error('An error occurred during save request:', error);
                showAlert('حدث خطأ غير متوقع أثناء حفظ الجدول. يرجى المحاولة مرة أخرى.', 'error');
            }
        }

        // Function to trigger print
        function printSchedule() {
            window.print(); // Opens the browser's print dialog
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>