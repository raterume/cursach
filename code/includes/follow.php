<?php
session_start();
require_once '../db/db.php';
require_once '../class/follows.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}
error_log("вызванна  "); 

$subs_id = intval($_POST['subs_id'] ?? 0);
$user_id = $_SESSION['user']['id'];
error_log($subs_id);
if ($subs_id > 0) {
    error_log("работает");
    $conn = get_db_connection();
    $post = new follows($conn);
    $post->toggleFollow($user_id, $subs_id);
    $conn->close();
}

// Возвращаем обратно
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'followings.php'));
exit();
?>
