<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "aljood_center";

$conn = new mysqli($host, $user, $password, $dbname);
$conn->set_charset("utf8");

if (isset($_GET["id"])) {
    $id = $_GET["id"];

    $stmt = $conn->prepare("DELETE FROM classes WHERE `رقم`=?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: classes.php");
        exit();
    } else {
        echo "خطأ في الحذف: " . $conn->error;
    }
} else {
    echo "لم يتم تحديد معرف الفصل.";
}
?>
