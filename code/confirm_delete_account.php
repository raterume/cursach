<?php
// confirm_delete_account.php
session_start();
if (!isset($_SESSION['user']['id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Подтверждение удаления аккаунта</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 500px; margin: 50px auto; padding: 20px; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .buttons { margin-top: 20px; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-delete { background: #dc3545; color: white; }
        .btn-cancel { background: #6c757d; color: white; margin-left: 10px; }
    </style>
</head>
<body>
    <h2>Удаление аккаунта</h2>
    
    <div class="warning">
        <strong>Внимание!</strong> Это действие нельзя отменить.
        <ul>
            <li>Все ваши посты будут удалены</li>
            <li>Все ваши комментарии будут удалены</li>
            <li>Ваши лайки и подписки пропадут</li>
            <li>Аккаунт будет удален навсегда</li>
        </ul>
    </div>
    
    <p>Вы уверены, что хотите удалить свой аккаунт?</p>
    
    <div class="buttons">
        <form action="includes/delete_account.php" method="POST" style="display: inline;">
            <button type="submit" class="btn btn-delete">Да, удалить аккаунт</button>
        </form>
        <a href="settings.php"><button type="button" class="btn btn-cancel">Отмена</button></a>
    </div>
</body>
</html>