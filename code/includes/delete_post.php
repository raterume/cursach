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
$post_id = intval($_POST['post_id'] ?? 0);

if ($post_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Неверный ID поста']);
    exit();
}

$conn = get_db_connection();

try {
    // Начинаем транзакцию
    $conn->begin_transaction();
    
    
    // 2. Удаляем сам пост
    $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $stmt->close();
    
    
    // Фиксируем транзакцию
    $conn->commit();
    logToDatabase('delete', 'post', $post_id);

        // 3. Удаляем физические файлы изображений
    if (!empty($post_images)) {
        $images_array = explode('||', $post_images);
        foreach ($images_array as $image) {
            if (!empty($image)) {
                $file_path = __DIR__ . '/../pic/' . $image;
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Пост успешно удален',
        'post_id' => $post_id
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => 'Ошибка при удалении: ' . $e->getMessage()]);
}

$conn->close();
?>