<?php
require_once 'db/db.php';
require_once 'class/User.php';
require_once 'includes/validation.php';
require_once 'includes/auth_check.php';

session_start();
    // перенаправляем пользователя если он уже вошел в аккаунт
redirectIfloggedIn();


$conn = get_db_connection();
$user = new User($conn);
$errors = [];
$email = $login = '';
    // получаем данные из формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

$hcaptcha_response = $_POST['h-captcha-response'] ?? '';
if (empty($hcaptcha_response)) {
    $errors['captcha'] = 'Пройдите проверку безопасности';
}


    $form_data = compact('email', 'login', 'password', 'password_confirm');
    // отпраляем данные в функцию проверки
    $errors = validateRegistration($form_data);
    if (empty($errors)) {
        if ($user->userExists($email, $login)) {
            $errors['general'] = 'Пользователь с таким email или логином уже существует';
        } else {
            $user_id = $user->register($login, $email, $password);
            if ($user_id) {
                $_SESSION['user'] = [
                    'id' => $user_id,
                    'login' => $login,
                    'email' => $email
                ];
                logToDatabase('create', 'account');
                header('Location: feed.php');
                exit();
            } else {
                $errors['general'] = 'Ошибка при регистрации. Попробуйте позже.';
            }
        }
    }
}
    // отображение разметки страницы
require_once 'regin.php';
$conn->close();
?>