<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../db/db.php';


// Добавьте в начале edit_post.php после отладки сессии:
$upload_dir = __DIR__ . '/../pic/';
error_log("Проверка директории $upload_dir");
error_log("Директория существует: " . (is_dir($upload_dir) ? 'Да' : 'Нет'));
error_log("Доступна для записи: " . (is_writable($upload_dir) ? 'Да' : 'Нет'));

// Создайте если нет
if (!is_dir($upload_dir)) {
    if (mkdir($upload_dir, 0755, true)) {
        error_log("Директория создана");
    } else {
        error_log("Не удалось создать директорию");
    }
}

$post_id = intval($_POST['post_id'] ?? 0);


if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit();
}

$text = trim($_POST['text'] ?? '');

if ($post_id <= 0) {
    error_log("ОШИБКА: post_id не получен или равен 0. POST: " . print_r($_POST, true));
    echo json_encode(['success' => false, 'error' => 'Неверный ID поста: ' . $post_id]);
    exit();
}

// Проверяем что пост принадлежит пользователю
$conn = get_db_connection();
$stmt = $conn->prepare("SELECT i.place, p.user FROM posts p JOIN Image i on i.post = p.id where p.id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$stmt->bind_result($current_images, $post_owner_id);
$stmt->fetch();
$stmt->close();


// Проверяем что есть хотя бы текст или изображения
$keep_images = $_POST['keep_images'] ?? [];
$has_images = !empty($keep_images) || 
              (isset($_FILES['new_images']) && !empty($_FILES['new_images']['name'][0]));

if (empty($text) && !$has_images) {
    echo json_encode(['success' => false, 'error' => 'Пост должен содержать текст или изображения']);
    exit();
}

// Начинаем транзакцию
$conn->begin_transaction();

try {
    // 1. Обновляем текст поста
    $stmt = $conn->prepare("UPDATE posts SET text = ? WHERE id = ?");
    $stmt->bind_param("si", $text, $post_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Ошибка обновления текста: ' . $stmt->error);
    }
    $stmt->close();
    
    // 2. Обрабатываем изображения
    $current_images_array = $current_images ? explode('||', $current_images) : [];
    $new_images_array = [];
    
    // 2a. Удаляем изображения которые убрали пользователи
    foreach ($current_images_array as $image) {
        if (in_array($image, $keep_images)) {
            $new_images_array[] = $image; // Оставляем
        } else {
            // Удаляем файл
            $file_path = __DIR__ . '/../pic/' . $image;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            // Удаляем из таблицы Image
            $del_stmt = $conn->prepare("DELETE FROM Image WHERE post = ? AND place = ?");
            error_log("вызвана ");
            $del_stmt->bind_param("is", $post_id, $image);
            $del_stmt->execute();
            $del_stmt->close();
        }
    }
    
// 2b. Добавляем новые изображения
if (isset($_FILES['new_images']) && !empty($_FILES['new_images']['name'][0])) {
    $upload_dir = __DIR__ . '/../pic/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $total_files = count($_FILES['new_images']['name']);
    error_log("Найдено новых файлов: $total_files");
    
    for ($i = 0; $i < $total_files; $i++) {
        if ($_FILES['new_images']['error'][$i] !== UPLOAD_ERR_OK) {
            error_log("Ошибка загрузки файла $i: " . $_FILES['new_images']['error'][$i]);
            continue;
        }
        
        $tmp_name = $_FILES['new_images']['tmp_name'][$i];
        $file_name = $_FILES['new_images']['name'][$i];
        $file_size = $_FILES['new_images']['size'][$i];
        
        error_log("Обработка файла $i: $file_name ($file_size байт)");
        
        // Проверяем что это изображение
        $image_info = @getimagesize($tmp_name);
        if (!$image_info) {
            error_log("Файл $file_name не является изображением");
            continue;
        }
        
        if ($file_size > 2 * 1024 * 1024) {
            error_log("Файл $file_name слишком большой: $file_size байт");
            continue;
        }
        
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed_ext)) {
            error_log("Недопустимое расширение файла $file_name: $ext");
            continue;
        }
        
        // Генерируем уникальное имя файла
        $filename = 'post_' . $post_id . '_edit_' . time() . '_' . $i . '.' . $ext;
        $destination = $upload_dir . $filename;
        
        error_log("Попытка сохранить файл: $filename -> $destination");
        
        if (move_uploaded_file($tmp_name, $destination)) {
            error_log("Файл успешно сохранен: $filename");
            $new_images_array[] = $filename;
            
            // Добавляем в таблицу Image
            $img_stmt = $conn->prepare("INSERT INTO Image (post, place) VALUES (?, ?)");
            $img_stmt->bind_param("is", $post_id, $filename);
            
            if ($img_stmt->execute()) {
                error_log("Запись в таблицу Image успешна: post=$post_id, place=$filename");
            } else {
                error_log("Ошибка записи в таблицу Image: " . $img_stmt->error);
                // Удаляем файл если не удалось записать в БД
                unlink($destination);
                // Убираем из массива
                array_pop($new_images_array);
            }
            
            $img_stmt->close();
        } else {
            error_log("Ошибка при перемещении файла $tmp_name в $destination");
        }
    }
}
    

    
    // Фиксируем транзакцию
    $conn->commit();
    logToDatabase('update', 'post', $post_id);
    
    echo json_encode([
        'success' => true,
        'post_id' => $post_id,
        'images_count' => count($new_images_array),
        'message' => 'Пост обновлен'
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
exit();
?>