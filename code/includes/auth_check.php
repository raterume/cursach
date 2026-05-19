<?php
// Проверка
function isLoggedIn() {
    return isset($_SESSION['user']) && !empty($_SESSION['user']);
}

// вход
function redirectIfLoggedIn($redirect_to = 'feed.php') {
    if (isLoggedIn()) {
        header("Location: $redirect_to");
        exit();
    }
}

// пошел вон
function redirectIfNotLoggedIn($redirect_to = 'login.php') {
    if (!isLoggedIn()) {
        header("Location: $redirect_to");
        exit();
    }
}

// данные из сессии
function getCurrentUser() {
    if (isLoggedIn()) {
        return $_SESSION['user'];
    }
    return null;
}


?>