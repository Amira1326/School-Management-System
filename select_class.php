
<?php
// Database connection details - Consider moving these to a separate config file for better security and management
$servername = "localhost";
$username = "root"; // Replace with your database username
$password = "";    // Replace with your database password
$dbname = "aljood_center";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection and handle errors gracefully
if ($conn->connect_error) {
    // Log the error instead of displaying it directly to the user in a production environment
    error_log("Connection failed: " . $conn->connect_error);
    die("حدث خطأ في الاتصال بقاعدة البيانات. يرجى المحاولة لاحقًا."); // User-friendly message
}

// Set character set to UTF-8 for proper Arabic display
$conn->set_charset("utf8mb4");

// -------------------------------------------------------------
// PHP: Fetch ALL data needed for dropdowns at once
// -------------------------------------------------------------

$all_data = [];
$all_subjects = [];

// Fetch ALL subjects from the `subjects` table
// Using prepared statements for all queries to prevent SQL injection
$sql_all_subjects = "SELECT DISTINCT اسم_المادة FROM subjects ORDER BY اسم_المادة ASC";
$stmt_all_subjects = $conn->prepare($sql_all_subjects);
if ($stmt_all_subjects === false) {
    error_log("Prepare failed: " . $conn->error);
    die("حدث خطأ في تحضير الاستعلام. يرجى المحاولة لاحقًا.");
}
$stmt_all_subjects->execute();
$result_all_subjects = $stmt_all_subjects->get_result();
if ($result_all_subjects && $result_all_subjects->num_rows > 0) {
    while($row = $result_all_subjects->fetch_assoc()) {
        $all_subjects[] = $row["اسم_المادة"];
    }
}
$stmt_all_subjects->close();

// Fetch unique stages (المرحلة_الدراسية) with proper ordering
// Improved ordering logic for stages, making it more robust
$sql_stages = "SELECT DISTINCT المرحلة_الدراسية FROM classes ORDER BY CASE
    WHEN المرحلة_الدراسية = 'الصف الأول الابتدائي' THEN 1
    WHEN المرحلة_الدراسية = 'الصف الثاني الابتدائي' THEN 2
    WHEN المرحلة_الدراسية = 'الصف الثالث الابتدائي' THEN 3
    WHEN المرحلة_الدراسية = 'الصف الرابع الابتدائي' THEN 4
    WHEN المرحلة_الدراسية = 'الصف الخامس الابتدائي' THEN 5
    WHEN المرحلة_الدراسية = 'الصف السادس الابتدائي' THEN 6
    WHEN المرحلة_الدراسية = 'الصف الأول المتوسط' THEN 7
    WHEN المرحلة_الدراسية = 'الصف الثاني المتوسط' THEN 8
    WHEN المرحلة_الدراسية = 'الصف الثالث المتوسط' THEN 9
    WHEN المرحلة_الدراسية = 'الصف الأول الثانوي' THEN 10
    WHEN المرحلة_الدراسية = 'الصف الثاني الثانوي' THEN 11
    WHEN المرحلة_الدراسية = 'الصف الثالث الثانوي' THEN 12
    ELSE 99 -- For any new stages not explicitly ordered
END, المرحلة_الدراسية ASC";
$stmt_stages = $conn->prepare($sql_stages);
if ($stmt_stages === false) {
    error_log("Prepare failed: " . $conn->error);
    die("حدث خطأ في تحضير الاستعلام. يرجى المحاولة لاحقًا.");
}
$stmt_stages->execute();
$result_stages = $stmt_stages->get_result();
if ($result_stages->num_rows > 0) {
    while($row = $result_stages->fetch_assoc()) {
        $stage = $row["المرحلة_الدراسية"];
        $all_data[$stage] = [];

        // For each stage, fetch unique الفصل
        $sql_فصل = "SELECT DISTINCT الفصل FROM classes WHERE المرحلة_الدراسية = ? ORDER BY الفصل ASC";
        $stmt_فصل = $conn->prepare($sql_فصل);
        if ($stmt_فصل === false) {
            error_log("Prepare failed: " . $conn->error);
            die("حدث خطأ في تحضير الاستعلام. يرجى المحاولة لاحقًا.");
        }
        $stmt_فصل->bind_param("s", $stage);
        $stmt_فصل->execute();
        $result_فصل = $stmt_فصل->get_result();
        if ($result_فصل->num_rows > 0) {
            while($row_فصل = $result_فصل->fetch_assoc()) {
                $فصل_val = $row_فصل["الفصل"];
                // Assign ALL subjects to EACH class
                $all_data[$stage][$فصل_val] = $all_subjects;
            }
        }
        $stmt_فصل->close();
    }
}
$stmt_stages->close();

// Encode the entire data structure to JSON for JavaScript
$json_all_data = json_encode($all_data, JSON_UNESCAPED_UNICODE);

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم في الدرجات</title>
    <link rel="icon" href="logo.png" type="image/x-icon">

    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        /* Define your custom CSS variables */
        :root {
            font-size: 16px;
            --primary-color: #5F6B75; /* Dark grey for main elements */
            --secondary-color-start: #ffffff; /* White for background gradient start */
            --secondary-color-end: #E0BBE4; /* Light purple for background gradient end */
            --accent-color: #89355d; /* Deep plum for accents/buttons */
            --text-color: #343a40; /* Dark grey for general text */
            --heading-color: #212529; /* Even darker grey for headings */
            --card-bg: #ffffff; /* White for card backgrounds */
            --shadow-light: rgba(0, 0, 0, 0.08);
            --shadow-medium: rgba(0, 0, 0, 0.15);
            --transition-speed: 0.3s ease-in-out;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--secondary-color-start), var(--secondary-color-end));
            color: var(--text-color);
            direction: rtl; /* Right-to-left for Arabic */
            text-align: right;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px; /* Add some padding for smaller screens */
        }
        .container {
            background-color: var(--card-bg);
            padding: 40px; /* Increased padding */
            border-radius: 15px; /* More rounded corners */
            box-shadow: 0 8px 20px var(--shadow-medium); /* Stronger shadow */
            max-width: 600px; /* Max width for better form presentation */
            width: 100%; /* Ensure it's responsive */
        }
        h2, h3 {
            color: var(--heading-color);
            margin-bottom: 25px;
            font-weight: 700; /* Bolder headings */
            text-align: center;
        }
        h3 {
            color: var(--primary-color); /* Lighter color for subheading */
            font-weight: 600;
        }
        .form-group label {
            font-weight: 600; /* Reverted to semi-bold labels */
            margin-bottom: 10px; /* More space below labels */
            color: var(--primary-color);
            display: block; /* Ensure label is on its own line */
        }
        
        /* Revised styling for dropdowns */
        .form-control {
            border-radius: 8px;
            border: 1px solid #ced4da;
            padding: 6px 10px; /* Smaller padding */
            font-size: 0.9rem; /* Further reduced font size */
            font-weight: 400; /* Reverted to normal font weight */
            background-color: #fcfcfc;
            color: #000000;
            transition: border-color var(--transition-speed), box-shadow var(--transition-speed);
            white-space: nowrap; /* Prevent text from wrapping inside the dropdown */
            overflow: hidden; /* Hide any overflow text */
            text-overflow: ellipsis; /* Add ellipsis (...) if text is too long */
        }
        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.25rem rgba(137, 53, 93, 0.25);
            outline: none;
        }
        /* Specific style for dropdown placeholder text */
        .form-control option[value=""] {
            color: #555555;
            font-style: italic;
        }
        .form-control option:not([value=""]) {
            color: #000000;
        }
        
        .btn-primary {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            padding: 12px 30px;
            font-size: 1.2rem;
            border-radius: 8px;
            transition: background-color var(--transition-speed), border-color var(--transition-speed), transform var(--transition-speed);
            box-shadow: 0 4px 10px var(--shadow-light);
        }
        .btn-primary:hover {
            background-color: darken(var(--accent-color), 10%);
            border-color: darken(var(--accent-color), 10%);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px var(--shadow-medium);
        }
        .btn-submit {
            display: block;
            width: 100%;
            margin-top: 35px;
        }
        .text-center {
            text-align: center;
        }
        /* Style for FontAwesome icon inside button */
        .btn-primary i {
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mb-4">لوحة التحكم في الدرجات</h2>
        <form id="classSelectionForm" method="GET" action="manage_grades.php">
            <div class="form-group">
                <label for="المرحلة_الدراسية">المرحلة الدراسية:</label>
                <select class="form-control" id="المرحلة_الدراسية" name="المرحلة_الدراسية" required>
                    <option value="">اختر المرحلة الدراسية</option>
                    <?php
                    // Populate initial stages from the PHP fetched data
                    foreach ($all_data as $stage_name => $فصل_data): ?>
                        <option value="<?php echo htmlspecialchars($stage_name); ?>">
                            <?php echo htmlspecialchars($stage_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="الفصل">الفصل (الشعبة):</label>
                <select class="form-control" id="الفصل" name="الفصل" required>
                    <option value="">اختر الفصل (مثال: أ، ب، ج)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="المادة">اسم المادة:</label>
                <select class="form-control" id="المادة" name="المادة" required>
                    <option value="">اختر اسم المادة</option>
                    <?php
                    // Populate the subjects dropdown with all subjects from the database
                    foreach ($all_subjects as $subject_name): ?>
                        <option value="<?php echo htmlspecialchars($subject_name); ?>">
                            <?php echo htmlspecialchars($subject_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="الفصل_الدراسي">الفصل الدراسي:</label>
                <select class="form-control" id="الفصل_الدراسي" name="الفصل_الدراسي" required>
                    <option value="">اختر الفصل الدراسي</option>
                    <option value="الأول">الفصل الدراسي الأول</option>
                    <option value="الثاني">الفصل الدراسي الثاني</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary btn-submit">عرض الدرجات <i class="fas fa-arrow-circle-right"></i></button>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        // Pass the PHP array to JavaScript
        var allData = <?php echo $json_all_data; ?>;

        $(document).ready(function() {
            // Function to populate dropdowns based on selections
            function populateDropdown(dropdownId, optionsArray, defaultText) {
                var dropdown = $(dropdownId);
                dropdown.empty().append('<option value="">' + defaultText + '</option>');
                if (optionsArray && optionsArray.length > 0) {
                    optionsArray.sort();
                    $.each(optionsArray, function(index, value) {
                        dropdown.append('<option value="' + value + '">' + value + '</option>');
                    });
                }
            }

            $('#المرحلة_الدراسية').change(function() {
                var selectedStage = $(this).val();
                var فصلOptions = [];
                if (selectedStage && allData[selectedStage]) {
                    فصلOptions = Object.keys(allData[selectedStage]);
                }
                populateDropdown('#الفصل', فصلOptions, 'اختر الفصل (مثال: أ، ب، ج)');
            });
        });
    </script>
</body>
</html>

