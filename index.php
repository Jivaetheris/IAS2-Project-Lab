<?php
session_start();
include "dbConnection.php";

define('ENCRYPTION_KEY', 'your_32_character_encryption_key');

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

if (!preg_match("/^[a-zA-Z0-9_]+$/", $username)) {
    die("Invalid username.");
}

$selectedUser = isset($_GET['user']) ? $_GET['user'] : '';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$stmt = $conn->prepare("SELECT username FROM user WHERE username != ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$userList = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat</title>
    <link rel="stylesheet" href="chat.css">
    <script>
        function loadMessages() {
            let user = "<?php echo $selectedUser; ?>";
            if (user) {
                fetch("getMessage.php?user=" + encodeURIComponent(user))
                .then(response => response.text())
                .then(data => {
                    let chatBox = document.getElementById("chat");
                    chatBox.innerHTML = data;
                    chatBox.scrollTop = chatBox.scrollHeight;
                })
                .catch(error => console.error("Error loading messages:", error));
            }
        }

        document.addEventListener("DOMContentLoaded", function () {
            let form = document.getElementById("message-form");

            if (form) {
                form.addEventListener("submit", function (event) {
                    event.preventDefault();

                    let messageInput = document.getElementById("message_box");
                    let receiverInput = document.getElementById("receiver");

                    let message = messageInput.value.trim();
                    let receiver = receiverInput.value.trim();

                    if (message !== "" && receiver !== "") {
                        fetch("insertMessage.php", {
                            method: "POST",
                            headers: { "Content-Type": "application/x-www-form-urlencoded" },
                            body: new URLSearchParams({
                                message: message,
                                receiver: receiver
                            })
                        })
                        .then(response => {
                            if (!response.ok) throw new Error("Message send failed");
                            return response.text();
                        })
                        .then(() => {
                            messageInput.value = ""; 
                            loadMessages();
                        })
                        .catch(error => console.error("Error sending message:", error));
                    }
                });

                document.getElementById("message_box").addEventListener("keypress", function (event) {
                    if (event.key === "Enter" && !event.shiftKey) {
                        event.preventDefault();
                        form.dispatchEvent(new Event("submit"));
                    }
                });
            }

            if ("<?php echo $selectedUser; ?>") {
                setInterval(loadMessages, 1000);
                loadMessages();
            }
        });
    </script>
</head>
<body>

<div id="container">
    <div>
        <label style="float: left; margin-left: 10px; margin-top: 27px; font-weight: bold;">
            <?php echo "User: " . htmlspecialchars($username); ?>
        </label>
        <a id="logout" href="logout.php">Logout</a>
        <br><br><br>
        <hr>
    </div>

    <div class="select-user-section">
        <h3>Select a user to chat with:</h3>
        <?php while ($row = $userList->fetch_assoc()): ?>
            <a class="select-user" href="index.php?user=<?php echo htmlspecialchars($row['username']); ?>">
                <?php echo htmlspecialchars($row['username']); ?>
            </a><br>
        <?php endwhile; ?>
    </div>

    <?php if ($selectedUser): ?>
        <h3>Chat with <span style="color: red;"><?php echo htmlspecialchars($selectedUser); ?></span></h3>

        <div id="chat"></div>

        <div id="chat-footer">
            <form id="message-form" style="display: flex; width: 100%;">
                <input id="message_box" type="text" name="message" placeholder="Type a message..." required>
                <input type="hidden" id="receiver" name="receiver" value="<?php echo htmlspecialchars($selectedUser); ?>">
                <button id="send" type="submit">Send</button>
            </form>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
