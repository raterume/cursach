<?php
session_start();
require_once '../db/db.php';
require_once '../class/post.php';


if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit();
}
error_log("вызванна");

$post_id = intval($_POST['post_id'] ?? 0);
$user_id = $_SESSION['user']['id'];
$text = trim($_POST['comm'] ?? '');

if ($post_id > 0) {
    error_log("работает");
    $conn = get_db_connection();
    $post = new Post($conn);
    $post->createComment($user_id, $post_id, $text);
    $comm_id = $conn->insert_id;
        logToDatabase('create', 'comment', $comm_id);

    $conn->close();
}


// Возвращаем обратно
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'feed.php'));

exit();
?>