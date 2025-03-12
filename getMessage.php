<?php
include 'dbConnection.php';
session_start();

define('ENCRYPTION_KEY', 'StormFuryBlazeEchoNightWindHawk');

function decryptMessage($encryptedMessage, $key) {
    $data = base64_decode($encryptedMessage);
    $iv = substr($data, 0, 16);
    $ciphertext = substr($data, 16);
    return openssl_decrypt($ciphertext, 'AES-256-CBC', $key, 0, $iv);
}

if (!isset($_SESSION['username'])) {
    die("Unauthorized access.");
}

$username = $_SESSION['username'];
$selectedUser = isset($_GET['user']) ? $_GET['user'] : '';

if (!empty($selectedUser) && $conn) {
    $stmt = $conn->prepare("SELECT username, message, time FROM message WHERE (username = ? AND receiver = ?) OR (username = ? AND receiver = ?) ORDER BY time ASC");
    $stmt->bind_param("ssss", $username, $selectedUser, $selectedUser, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    while ($row = $result->fetch_assoc()) {
        $decryptedMessage = decryptMessage($row['message'], ENCRYPTION_KEY);
        $isSent = ($row['username'] === $username);

        echo '<div class="message-container ' . ($isSent ? "sent" : "received") . '">
                <div class="message">' . htmlspecialchars($decryptedMessage) . '</div>
                <span class="timestamp">' . $row['time'] . '</span>
              </div>';
    }
}
?>
