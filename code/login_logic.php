<?php
require_once 'db/db.php';
require_once 'class/user.php';
require_once 'includes/validation.php';
require_once 'includes/auth_check.php';

session_start();

$conn = get_db_connection();
$user = new User($conn);

$errors = [];
$email = $login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
$hcaptcha_response = $_POST['h-captcha-response'] ?? '';
if (empty($hcaptcha_response)) {
    $errors['captcha'] = 'Пройдите проверку безопасности';
}
    
    $form_data = compact('email');
    
    if (empty($errors)) {
            $user_data = $user->login($email, $password);
            
            if ($user_data) {
                $_SESSION['user'] = [
                    'id' => $user_data['id'],
                    'login' => $user_data['login'],
                    'email' => $user_data['email']
                ];
        // $_SESSION['user_id'] = $user['id'];
        // $_SESSION['username'] = $user['username'];
        //         $_SESSION['last_activity'] = time();
                
            logToDatabase('login', 'account');
                header('Location: feed.php');
                exit();
            } else {
                $errors['general'] = 'Неверный email или пароль';
            }
        }
    }

require_once 'login.php';

$conn->close();

?>