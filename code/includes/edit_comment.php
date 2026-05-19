<?php
session_start();
require_once '../db/db.php';

header('Content-Type: application/json; charset=utf-8');

// Проверка авторизации
if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit();
}

$user_id = $_SESSION['user']['id'];
$comment_id = intval($_POST['comment_id'] ?? 0);
$text = trim($_POST['text'] ?? '');

if ($comment_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Неверный ID комментария']);
    exit();
}

if (empty($text)) {
    echo json_encode(['success' => false, 'error' => 'Комментарий не может быть пустым']);
    exit();
}

if (strlen($text) > 1000) {
    echo json_encode(['success' => false, 'error' => 'Комментарий слишком длинный (макс. 1000 символов)']);
    exit();
}

$conn = get_db_connection();

try {
    // Проверяем, что комментарий принадлежит пользователю
    $stmt = $conn->prepare("SELECT user FROM comments WHERE id = ?");
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $stmt->bind_result($comment_owner_id);
    $stmt->fetch();
    $stmt->close();
    
    if (!$comment_owner_id) {
        echo json_encode(['success' => false, 'error' => 'Комментарий не найден']);
        exit();
    }
    
    if ($comment_owner_id != $user_id) {
        echo json_encode(['success' => false, 'error' => 'Нет прав на редактирование']);
        exit();
    }
    
    // Обновляем текст комментария
    $stmt = $conn->prepare("UPDATE comments SET text = ? WHERE id = ?");
    $stmt->bind_param("si", $text, $comment_id);
    logToDatabase('update', 'comment', $comment_id);
    
    
    if (!$stmt->execute()) {
        throw new Exception('Ошибка обновления комментария: ' . $stmt->error);
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'comment_id' => $comment_id,
        'text' => $text,
        'message' => 'Комментарий обновлен'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>