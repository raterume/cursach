<?php
// date_default_timezone_set('Asia/Irkutsk');

// Стартуем сессию
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Автоподключение классов
spl_autoload_register(function ($class_name) {
    $file = 'classes/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Подключаем основные файлы
require_once 'db/db.php';
require_once 'includes/auth_check.php';

// Проверяем сессию на валидность
function validateSession() {
    if (isset($_SESSION['user'])) {
        // Проверяем, не устарела ли сессия (например, больше 2 часов)
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 7200)) {
            // Сессия устарела
            session_destroy();
            header("Location: login.php?expired=1");
            exit();
        }
        
        // Обновляем время активности
        $_SESSION['last_activity'] = time();
    }
}

// Вызываем проверку
validateSession();
?>