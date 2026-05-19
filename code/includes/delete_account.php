<?php
session_start();
require_once '../db/db.php';

// Проверка авторизации
if (!isset($_SESSION['user']['id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user']['id'];

// Начинаем транзакцию для надежности
$conn = get_db_connection();
$conn->begin_transaction();

try {
    // Если нужно что-то сделать перед удалением (логирование и т.д.)
    // Например, записать в логи
    error_log("Пользователь ID $user_id удалил свой аккаунт");
    
    // Просто удаляем пользователя (все остальное удалится CASCADE)
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    
    // Фиксируем изменения
    $conn->commit();
    
    // Уничтожаем сессию
    session_destroy();
    
    // Редирект на страницу входа
    header('Location: ../login.php?message=account_deleted');
    exit();
    
} catch (Exception $e) {
    // Откатываем в случае ошибки
    $conn->rollback();
    header('Location: ../settings.php?error=delete_failed');
    exit();
}

$conn->close();
?>