<?php
// ВКЛЮЧАЕМ БУФЕР
ob_start();

session_start();
require_once '../db/db.php';

// ОБЪЯВЛЯЕМ $response ЗДЕСЬ, В ГЛОБАЛЬНОЙ ОБЛАСТИ ВИДИМОСТИ
$response = ['success' => false, 'error' => 'Неизвестная ошибка'];

// Проверка авторизации
if (!isset($_SESSION['user']['id'])) {
    $response = ['success' => false, 'error' => 'Не авторизован'];
    sendResponse($response);
}

$user_id = $_SESSION['user']['id'];
$text = trim($_POST['text'] ?? '');

// Проверяем что есть хотя бы текст или изображения
if (empty($text) && (!isset($_FILES['images']) || empty($_FILES['images']['name'][0]))) {
    $response = ['success' => false, 'error' => 'Добавьте текст или изображение'];
    sendResponse($response);
}

// Подключаемся к БД
$conn = get_db_connection();
if ($conn->connect_error) {
    $response = ['success' => false, 'error' => 'Ошибка подключения к БД'];
    sendResponse($response);
}

// Начинаем транзакцию
$conn->begin_transaction();

try {
    // 1. Создаем запись поста
    $stmt = $conn->prepare("INSERT INTO posts (user, text) VALUES (?, ?)");
    if (!$stmt) {
        throw new Exception('Ошибка подготовки запроса: ' . $conn->error);
    }
    
    $stmt->bind_param("is", $user_id, $text);
    
    if (!$stmt->execute()) {
        throw new Exception('Ошибка создания поста: ' . $stmt->error);
    }
    

    $post_id = $conn->insert_id;
        logToDatabase('create', 'post', $post_id);
    $stmt->close();
    
    // 2. Обрабатываем изображения (если есть)
    $image_count = 0;
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $upload_dir = __DIR__ . '/../pic/';
        
        // Создаем папку если нет
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Обрабатываем каждый файл
        $total_files = count($_FILES['images']['name']);
        for ($i = 0; $i < $total_files; $i++) {
            // Пропускаем если есть ошибка
            if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }
            
            $tmp_name = $_FILES['images']['tmp_name'][$i];
            $file_size = $_FILES['images']['size'][$i];
            
            // Проверяем что это изображение
            if (!@getimagesize($tmp_name)) {
                continue;
            }
            
            // Проверяем размер (макс 2MB)
            if ($file_size > 2 * 1024 * 1024) {
                continue;
            }
            
            // Определяем расширение
            $original_name = $_FILES['images']['name'][$i];
            $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (!in_array($ext, $allowed_ext)) {
                continue;
            }
            
            // Генерируем уникальное имя
            $filename = 'post_' . $post_id . '_' . $i . '_' . time() . '.' . $ext;
            $destination = $upload_dir . $filename;
            
            // Перемещаем файл
            if (move_uploaded_file($tmp_name, $destination)) {
                // Сохраняем в БД
                $stmt_img = $conn->prepare("INSERT INTO Image (post, place) VALUES (?, ?)");
                if ($stmt_img) {
                    $stmt_img->bind_param("is", $post_id, $filename);
                    if ($stmt_img->execute()) {
                        $image_count++;
                    }
                    $stmt_img->close();
                }
            }
        }
    }
    
    // Фиксируем транзакцию
    $conn->commit();
    
    $response = [
        'success' => true,
        'post_id' => $post_id,
        'image_count' => $image_count,
        'message' => 'Пост создан' . ($image_count > 0 ? ' с ' . $image_count . ' изображениями' : '')
    ];
    
} catch (Exception $e) {
    // Откатываем транзакцию при ошибке
    $conn->rollback();
    $response = ['success' => false, 'error' => $e->getMessage()];
}

$conn->close();

// ОТПРАВЛЯЕМ ОТВЕТ
sendResponse($response);

// ФУНКЦИЯ ДЛЯ ОТПРАВКИ ОТВЕТА
function sendResponse($response) {
    // Очищаем буфер если что-то в нем есть
    if (ob_get_length() > 0) {
        $output = ob_get_contents();
        
        // Если в буфере есть данные - это ошибка (не должно быть вывода до JSON)
        if (!empty($output) && trim($output) !== '') {
            // Логируем но не выводим
            error_log("НЕОЖИДАННЫЙ ВЫВОД: " . substr($output, 0, 500));
        }
        
        ob_clean();
    }
    
    // Устанавливаем заголовок
    header('Content-Type: application/json; charset=utf-8');
    
    // Отправляем JSON
    echo json_encode($response);
    
    // Завершаем выполнение
    exit();
}
?>