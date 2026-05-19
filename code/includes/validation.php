<?php

function validateRegistration($data) {
    $errors = [];
    
    // Валидация email
    if (empty($data['email'])) {
        $errors['email'] = 'Email обязателен';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Некорректный email';
    }
    
    // Валидация логина
    if (empty($data['login'])) {
        $errors['login'] = 'Логин обязателен';
    } elseif (strlen($data['login']) < 3) {
        $errors['login'] = 'Логин должен быть не менее 3 символов';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['login'])) {
        $errors['login'] = 'Логин может содержать только буквы, цифры и подчеркивание';
    }
    
    // Валидация пароля
    if (empty($data['password'])) {
        $errors['password'] = 'Пароль обязателен';
    } elseif (strlen($data['password']) < 6) {
        $errors['password'] = 'Пароль должен быть не менее 6 символов';
    }
    
    // Проверка совпадения паролей
    if ($data['password'] !== $data['password_confirm']) {
        $errors['password_confirm'] = 'Пароли не совпадают';
    }
    
    return $errors;
}


?>