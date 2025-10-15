<?php
session_start();

$valid_username = "admin";
$valid_password = "1234";

// التحقق من بيانات الدخول
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === $valid_username && $password === $valid_password) {
        $_SESSION['admin_logged_in'] = true;

        // صفحة Splash بعد الدخول الناجح
        echo '
        <!DOCTYPE html>
        <html lang="ar" dir="rtl">
        <head>
          <meta charset="UTF-8" />
          <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
          <title>Splash - مركز الجود التعليمي</title>
          <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
          <style>
            * {
              box-sizing: border-box;
              margin: 0;
              padding: 0;
            }
            body {
              background-color: white;
              display: flex;
              flex-direction: column;
              justify-content: center;
              align-items: center;
              height: 100vh;
              font-family: "Tajawal", sans-serif;
              text-align: center;
            }
            .logo-container {
              width: 40vw;
              max-width: 200px;
              overflow: hidden;
              position: relative;
            }
            .logo {
              width: 100%;
              height: auto;
              opacity: 0;
              animation: revealFromTop 2.5s ease-out forwards;
            }
            @keyframes revealFromTop {
              0% {
                clip-path: inset(0 0 100% 0);
                opacity: 1;
              }
              100% {
                clip-path: inset(0 0 0 0);
                opacity: 1;
              }
            }
            .welcome-text {
              margin-top: 5vh;
              font-size: clamp(1.2rem, 2.5vw, 2rem);
              color: #444;
              opacity: 0;
              animation: fadeInText 1.5s ease forwards;
              animation-delay: 2.5s;
            }
            @keyframes fadeInText {
              from {
                opacity: 0;
                transform: translateY(10px);
              }
              to {
                opacity: 1;
                transform: translateY(0);
              }
            }
          </style>
          <script>
            setTimeout(function () {
              window.location.href = "dashboard.php";
            }, 5000);
          </script>
        </head>
        <body>
          <div class="logo-container">
            <img class="logo" src="logo.png" alt="شعار مركز الجود التعليمي" />
          </div>
          <div class="welcome-text">أهلاً بك</div>
        </body>
        </html>';
        exit();
    } else {
        $error = "❌ اسم المستخدم أو كلمة المرور غير صحيحة.";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>تسجيل دخول المدير</title>
  <link rel="icon" href="logo.png" type="image/x-icon">
  
  <!-- روابط الخطوط -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  
  <style>
    :root {
        --primary-color: #89355d;
        --secondary-color: #E0BBE4;
        --text-dark: #333;
        --text-medium: #555;
        --white: #ffffff;
    }

    body {
      margin: 0;
      font-family: 'Cairo', sans-serif;
      background: linear-gradient(to bottom, var(--white) 0%, var(--secondary-color) 100%);
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      overflow: hidden;
    }
.login-container {
      background: #fff;
      padding: 40px;
      border-radius: 18px;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
      width: 380px;
      max-width: 90%;
      box-sizing: border-box;
      text-align: right;
      animation: fadeIn 0.7s ease-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-15px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .login-container img {
      display: block;
      margin: 0 auto 25px;
      width: 110px;
      height: auto;
    }

    h2 {
      text-align: center;
      color: #333;
      margin-bottom: 25px;
      font-weight: 700;
      font-size: 26px;
    }

    label {
      display: block;
      font-weight: 600;
      margin-bottom: 8px;
      color: #555;
      font-size: 15px;
    }

    .input-wrapper {
      position: relative;
      margin-bottom: 20px;
    }

    input[type="text"],
    input[type="password"] {
      width: 100%;
      padding: 14px 18px;
      border-radius: 10px;
      border: 1px solid #f9f9f9ff;
      font-size: 16px;
      box-sizing: border-box;
      transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }

    input[type="text"]:focus,
    input[type="password"]:focus {
      border-color: #9C27B0;
      box-shadow: 0 0 0 3px rgba(156, 39, 176, 0.15);
      outline: none;
    }

    .toggle-password {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: #999;
      transition: color 0.2s ease;
    }

    .toggle-password:hover {
      color: #666;
    }

    button {
      width: 100%;
      padding: 15px;
      background-color: #89355d;
      color: white;
      font-size: 17px;
      font-weight: 700;
      border: none;
      border-radius: 12px;
      cursor: pointer;
      transition: background-color 0.3s ease, transform 0.2s ease;
      letter-spacing: 0.5px;
    }

    button:hover {
      background-color: #89355d;
      transform: translateY(-1px);
    }
    button:active {
      transform: translateY(0);
    }

    .error {
      color: #d9534f;
      background-color: #fef1f1;
      border: 1px solid #d9534f;
      padding: 10px;
      border-radius: 8px;
      text-align: center;
      margin-bottom: 18px;
      font-weight: 600;
      font-size: 14px;
      animation: shake 0.5s ease-in-out;
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      20%, 60% { transform: translateX(-5px); }
      40%, 80% { transform: translateX(5px); }
    }

    .bottom-link {
      margin-top: 20px;
      text-align: center;
    }

    .bottom-link a {
      font-size: 14px;
      text-decoration: none;
      color: #757575ff;
      font-weight: 600;
      transition: color 0.3s ease, text-decoration 0.3s ease;
    }

    .bottom-link a:hover {
      text-decoration: underline;
      color: #757575ff;
    }
  </style>
</head>
<body>

  <div class="login-container">
    <img src="logo.png" alt="شعار مركز الجود التعليمي" />
    <h2>تسجيل دخول المدير</h2>

    <?php if (isset($error)): ?>
      <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
      <label for="username">اسم المستخدم</label>
      <div class="input-wrapper">
        <input type="text" id="username" name="username" autocomplete="off" required />
      </div>

      <label for="password">كلمة المرور</label>
      <div class="input-wrapper">
        <input type="password" id="password" name="password" autocomplete="off" required />
        <span class="toggle-password" onclick="togglePasswordVisibility()">
          <i class="fas fa-eye" id="togglePasswordIcon"></i>
        </span>
      </div>

      <button type="submit">تسجيل الدخول</button>
    </form>

    <div class="bottom-link">
      <a href="login.html">العودة لاختيار نوع تسجيل الدخول</a>
    </div>
  </div>

  <script>
    function togglePasswordVisibility() {
      const passwordField = document.getElementById('password');
      const toggleIcon = document.getElementById('togglePasswordIcon');
if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
      } else {
        passwordField.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
      }
    }
  </script>

</body>
</html>
