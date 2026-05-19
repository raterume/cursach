<?php
session_start();
require_once '../db/db.php';

ob_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit();
}

$user_id = $_SESSION['user']['id'];
$field = $_POST['field'] ?? ''; // 'avatar' или 'backimg'

// Проверяем что загружен файл
if (!isset($_FILES['avatar_file']) || $_FILES['avatar_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Файл не загружен']);
    exit();
}

$file = $_FILES['avatar_file'];

// Проверяем тип файла
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'error' => 'Разрешены только JPG, PNG, GIF, WebP']);
    exit();
}

// Проверяем размер файла (максимум 5MB)
$max_size = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'error' => 'Файл слишком большой (макс. 5MB)']);
    exit();
}

// Генерируем уникальное имя файла
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('avatar_', true) . '.' . $extension;

// Папка для загрузки
$upload_dir = __DIR__ . '/../pic/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Полный путь к файлу
$destination = $upload_dir . $filename;

// Перемещаем загруженный файл
if (!move_uploaded_file($file['tmp_name'], $destination)) {
    echo json_encode(['success' => false, 'error' => 'Ошибка загрузки файла']);
    exit();
}

// Обновляем запись в БД
$conn = get_db_connection();

// Получаем старое имя файла для удаления
$old_filename = '';
if ($field === 'avatar' || $field === 'backimg') {
    $stmt = $conn->prepare("SELECT $field FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($old_filename);
    $stmt->fetch();
    $stmt->close();
}

// Обновляем в БД
$stmt = $conn->prepare("UPDATE users SET $field = ? WHERE id = ?");
$stmt->bind_param("si", $filename, $user_id);

if ($stmt->execute()) {
    // Удаляем старый файл (кроме стандартного иконки)
    if ($old_filename && $old_filename !== 'ico.jpg' && file_exists($upload_dir . $old_filename)) {
        unlink($upload_dir . $old_filename);
    }
    
    $response = ['success' => true, 'filename' => $filename];
} else {
    // Если ошибка БД - удаляем загруженный файл
    unlink($destination);
    $response = ['success' => false, 'error' => 'Ошибка базы данных'];
}

logToDatabase('update', $field, $user_id, $old_value);

$stmt->close();
$conn->close();

ob_end_clean();
echo json_encode($response);
exit();
?>