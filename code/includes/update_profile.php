<?php
session_start();
require_once '../db/db.php';
ob_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit();
    error_log("нет");
}

$conn = get_db_connection();

$user_id = $_SESSION['user']['id'];
$field = $_POST['field'] ?? '';
$new_value = trim($_POST['new_value'] ?? '');

$old_value = trim($_POST['old_value'] ?? '');

$confirm_value = trim($_POST['confirm_value'] ?? '');
$current_password = $_POST['current_password'] ?? '';

$response = ['success' => false];

// Валидация
$allowed_fields = ['login', 'inform', 'email', 'password'];
if (!in_array($field, $allowed_fields)) {
    $response['error'] = 'Неверное поле';
    echo json_encode($response);
    exit();
}

// Проверка уникальности логина и почты
if ($field === 'email') {
    // Проверяем совпадение подтверждения
    if ($new_value !== $confirm_value) {
        $response['error'] = ($field === 'email' ? 'Почты' : 'Логины') . ' не совпадают';
        echo json_encode($response);
        exit();
    }
    
    // Проверяем, не занят ли логин другим пользователем
    $stmt = $conn->prepare("SELECT id FROM users WHERE $field = ? AND id != ?");
    $stmt->bind_param("si", $new_value, $user_id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $response['error'] = ($field === 'email' ? 'Этот email' : 'Этот логин') . ' уже занят';
        echo json_encode($response);
        exit();
    }
}


// Особые проверки для пароля
if ($field === 'password') {
    // Проверяем совпадение новых паролей
    if ($new_value !== $confirm_value) {
        $response['error'] = 'Новые пароли не совпадают';
        echo json_encode($response);
        exit();
    }
    
    // Проверяем длину пароля
    if (strlen($new_value) < 6) {
        $response['error'] = 'Пароль должен быть минимум 6 символов';
        echo json_encode($response);
        exit();
    }
    
    // Проверяем текущий пароль
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($hashed_password);
    $stmt->fetch();
    $stmt->close();
    
    if (!password_verify($current_password, $hashed_password)) {
        $response['error'] = 'Неверный текущий пароль';
        echo json_encode($response);
        exit();
    }
    
    // Хэшируем новый пароль
    $new_value = password_hash($new_value, PASSWORD_DEFAULT);
}


// Обновление в БД
if ($field === 'password') {
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?"); 
} else {
    if($field === 'login') {
        $stmt = $conn->prepare("UPDATE users SET login = ? WHERE id = ?");
    }else{
    $stmt = $conn->prepare("UPDATE users SET $field = ? WHERE id = ?");}
}

$stmt->bind_param("si", $new_value, $user_id);

if ($stmt->execute()) {
    $response['success'] = true;
    
    // Обновляем сессию если нужно
    if ($field === 'login') {
        $_SESSION['username'] = $new_value;
    } elseif ($field === 'email') {
        $_SESSION['email'] = $new_value;
    }
} else {
    $response['error'] = 'Ошибка базы данных: ' . $conn->error;
}
logToDatabase('update', $field, $user_id, $old_value);

$stmt->close();
$conn->close();


ob_end_clean();
echo json_encode($response);
exit();
?>
