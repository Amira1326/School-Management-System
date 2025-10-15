<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>إضافة فصل - مركز الجود</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Cairo', sans-serif;
      background-color: #fceef5;
      margin: 0;
      padding: 0;
    }
    .container {
      max-width: 600px;
      background: white;
      margin: 50px auto;
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
      position: relative;
    }
    h2 {
      text-align: center;
      color: #9c6b82;
      margin-bottom: 20px;
    }
    label {
      display: block;
      margin-top: 15px;
      font-weight: bold;
      color: #444;
    }
    input[type="text"],
    input[type="date"] {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border: 1px solid #ccc;
      border-radius: 8px;
    }
    button {
      margin-top: 25px;
      padding: 12px;
      width: 100%;
      font-size: 16px;
      background-color: #9c6b82;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: 0.3s;
    }
    button:hover {
      background-color: #7b4c64;
    }
    .logo {
      display: block;
      width: 100px;
      margin: 0 auto 20px;
    }
    .back-btn {
      display: inline-block;
      margin-top: 20px;
      text-align: center;
      width: 100%;
      background-color: #aaa;
      color: white;
      text-decoration: none;
      padding: 10px;
      border-radius: 8px;
      transition: 0.3s;
    }
    .back-btn:hover {
      background-color: #888;
    }
    .alert {
      background-color: #d4edda;
      border: 1px solid #c3e6cb;
      padding: 10px;
      border-radius: 8px;
      color: #155724;
      text-align: center;
      margin-bottom: 15px;
      font-weight: bold;
    }
  </style>
</head>
<body>

<div class="container">
  <img src="logo.png" alt="شعار المركز" class="logo">
  <h2>إضافة فصل جديد</h2>

  <?php if (isset($_GET['error']) && $_GET['error'] === 'empty'): ?>
    <div class="alert" style="background:#f8d7da; border-color:#f5c6cb; color:#721c24;">
      ⚠️ الرجاء تعبئة جميع الحقول.
    </div>
  <?php endif; ?>

  <form action="insert_class.php" method="POST">
    <label>اسم الفصل:</label>
    <input type="text" name="class_name" required>

    <label>المرحلة الدراسية:</label>
    <input type="text" name="grade_level" required>

    <label>معرف المعلمة:</label>
    <input type="text" name="teacher_id" required>

    <label>اسم المعلمة:</label>
    <input type="text" name="teacher_name" required>

    <label>تاريخ الإضافة:</label>
    <input type="date" name="created_at" required>

    <button type="submit">➕ إضافة الفصل</button>
  </form>

  <a href="classes.php" class="back-btn">↩️ الرجوع إلى قائمة الفصول</a>
</div>

</body>
</html>
