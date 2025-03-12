<?php
include 'dbConnection.php';
session_start();

define('ENCRYPTION_KEY', 'matanisatanas');

function encryptMessage($message, $key) {
    $iv = openssl_random_pseudo_bytes(16);
    $ciphertext = openssl_encrypt($message, 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($iv . $ciphertext);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['message']) && isset($_POST['receiver'])) {
    if (!isset($_SESSION['username'])) {
        die("Unauthorized access.");
    }

    $username = $_SESSION['username'];
    $message = trim($_POST['message']);
    $receiver = trim($_POST['receiver']);

    if (!empty($message) && !empty($receiver)) {
        $encryptedMessage = encryptMessage($message, ENCRYPTION_KEY);
        $stmt = $conn->prepare("INSERT INTO message (username, receiver, message, time) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sss", $username, $receiver, $encryptedMessage);
        $stmt->execute();
        $stmt->close();
    }
    $conn->close();
}
?>
