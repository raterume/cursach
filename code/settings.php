<?php
require_once 'init.php'; //сессия
redirectIfNotLoggedIn(); 
require_once 'class/user.php'; 

$current_user = getCurrentUser();
$conn = get_db_connection();
$user = new User($conn);
$user_result = $user->getUserById($current_user['id']);
$user_row = $user_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/style_main.css">
    <title>Document</title>
</head>
<body>
<div class="page-wrapper">
<div class="container">
    <!-- Левая боковая панель -->
        <?php require_once 'left_panel.php'?>

<main class="content">
    <div class="feed">
        <article class="setting-container">

            <div class = "setting">
                <div class = "setting-name">
                    Имя пользователя
                </div>
                <div class = "setting-info" id="current-username">
                    <?php echo htmlspecialchars($user_row['login'])?>
                </div>
                <div class = "setting-buttons">
                    <button type="submit" class="action-btn setting-btn" data-modal="modal-username"></button>
                </div>
            </div>
            <hr class="line-settings">
            



            <div class = "setting">
                <div class = "setting-name">
                    Описание профиля
                </div>
                <div class = "setting-info" id="current-description">
                    <?php echo htmlspecialchars($user_row['inform'])?>
                </div>
                <div class = "setting-buttons">
                    <button type="submit" class="action-btn setting-btn" data-modal="modal-description"></button>
                </div>
            </div>
            <hr class="line-settings">



            <div class = "setting">
                <div class = "setting-name">
                    Электронный аддрес
                </div>
                <div class = "setting-info" id="current-email">
                    <?php echo htmlspecialchars($user_row['email'])?>
                </div>
                <div class = "setting-buttons">
                    <button type="submit" class="action-btn setting-btn" data-modal="modal-email"></button>
                </div>
            </div>
            <hr class="line-settings">





            <div class = "setting">
                <div class = "setting-name">
                    Фото профиля
                </div>
                <div class = "setting-info">
                        <img class="settings-avatar" src="<?php echo htmlspecialchars($user_row['avatar'] ? 'pic/' . $user_row['avatar'] : 'pic/ico.jpg'); ?>" id="avatar-preview">
                </div>
                <div class = "setting-buttons">
                    <button type="submit" class="action-btn upload-btn" data-modal="modal-avatar"></button>
                </div>
            </div>
            <hr class="line-settings">





            <div class = "setting">
                <div class = "setting-name">
                    Обложка профиля
                </div>
                <div class = "setting-info">
                        <img class="settings-img" src="<?php echo htmlspecialchars($user_row['backimg'] ? 'pic/' . $user_row['backimg'] : 'pic/back.jpg'); ?>">
                </div>
                <div class = "setting-buttons">
                    <button type="submit" class="action-btn upload-btn" data-modal="modal-backimg"></button>
                </div>
            </div>
            <hr class="line-settings">




            <div class = "setting">
                <div class = "setting-name">
                    Пароль
                </div>
                <div class = "setting-info">
                    ••••••••
                </div>
                <div class = "setting-buttons">
                    <button type="submit" class="action-btn setting-btn" data-modal="modal-password"></button>
                </div>
            </div>
            <hr class="line-settings">




            <div class = "setting">
                <div class = "setting-name">
                </div>
                <div class = "setting-info">
                    <a href="confirm_delete_account.php" class="drop">удалить аккаунт</a>
                </div>
            </div>

            
        </article>
    </div>
</main>

</div>

<!-- МОДАЛЬНЫЕ ОКНА -->

<!-- Модальное окно для имени пользователя -->
<div id="modal-username" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2>Изменить имя пользователя</h2>
        <form id="form-username" method="POST" action="includes/update_profile.php">
            <input type="hidden" name="field" value="login">
            <div class="form-group">
                <label for="new-username">Новое имя:</label>
                <input type="text" id="new-username" name="new_value" 
                       value="<?php echo htmlspecialchars($user_row['login']); ?>"
                       maxlength="50" required>

                <input type="hidden" id="old-username" name="old_value" 
                       value="<?php echo htmlspecialchars($user_row['login']); ?>"
                       maxlength="50" required>

            </div>
            <div class="modal-buttons">
                <button type="button" class="btn-cancel">Отмена</button>
                <button type="submit" class="btn-save">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<!-- Модальное окно для описания -->
<div id="modal-description" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2>Изменить описание профиля</h2>
        <form id="form-description" method="POST" action="includes/update_profile.php">
            <input type="hidden" name="field" value="inform">
            <div class="form-group">
                <label for="new-description">Новое описание:</label>
                <textarea id="new-description" name="new_value" 
                          maxlength="500" rows="4"><?php echo htmlspecialchars($user_row['inform']); ?></textarea>
            </div>
            <div class="modal-buttons">
                <button type="button" class="btn-cancel">Отмена</button>
                <button type="submit" class="btn-save">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<!-- Модальное окно для email -->
<div id="modal-email" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2>Изменить Email</h2>
        <form id="form-email" method="POST" action="includes/update_profile.php">
            <input type="hidden" name="field" value="email">
            <div class="form-group">
                <label for="new-email">Новый email:</label>
                <input type="email" id="new-email" name="new_value" 
                       value="<?php echo htmlspecialchars($user_row['email']); ?>"
                       required>

                <input type="hidden" id="old-username" name="old_value" 
                       value="<?php echo htmlspecialchars($user_row['email']); ?>"
                       maxlength="50" required>

            </div>
            <div class="form-group">
                <label for="confirm-email">Подтвердите email:</label>
                <input type="email" id="confirm-email" name="confirm_value"
                       placeholder="Введите email еще раз" required>
            </div>
            <div class="modal-buttons">
                <button type="button" class="btn-cancel">Отмена</button>
                <button type="submit" class="btn-save">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<!-- Модальное окно для пароля -->
<div id="modal-password" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2>Изменить пароль</h2>
        <form id="form-password" method="POST" action="includes/update_profile.php">
            <input type="hidden" name="field" value="password">
            <div class="form-group">
                <label for="current-password-input">Текущий пароль:</label>
                <input type="password" id="current-password-input" 
                       name="current_password" required>
            </div>
            <div class="form-group">
                <label for="new-password">Новый пароль:</label>
                <input type="password" id="new-password" name="new_value"
                       minlength="6" required>
            </div>
            <div class="form-group">
                <label for="confirm-password">Подтвердите пароль:</label>
                <input type="password" id="confirm-password" name="confirm_value"
                       minlength="6" required>
            </div>
            <div class="modal-buttons">
                <button type="button" class="btn-cancel">Отмена</button>
                <button type="submit" class="btn-save">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<!-- Модальное окно для аватара -->
<div id="modal-avatar" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2>Изменить фото профиля</h2>
        <form id="form-avatar" method="POST" action="includes/update_avatar.php" enctype="multipart/form-data">
            <input type="hidden" name="field" value="avatar">
            
            <div class="form-group">
                <label>Фото:</label>
                    <img class = "settings-avatar" src="<?php echo htmlspecialchars($user_row['avatar'] ? 'pic/' . $user_row['avatar'] : 'pic/ico.jpg'); ?>" 
                         id="avatar-display" class="modal-image">
            </div>
            
            <div class="form-group">
                <label for="avatar-file">Выберите файл:</label>
                <input type="file" id="avatar-file" name="avatar_file" 
                       accept="image/*" class="file-input">
            </div>
            
            <div class="modal-buttons">
                <button type="button" class="btn-cancel">Отмена</button>
                <button type="submit" class="btn-save">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<!-- Модальное окно для обложки -->
<div id="modal-backimg" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2>Изменить обложку профиля</h2>
        <form id="form-backimg" method="POST" action="includes/update_avatar.php" enctype="multipart/form-data">
            <input type="hidden" name="field" value="backimg">
            
            <div class="form-group">
                <label>Обложка:</label>
                    <img class="settings-img" src="<?php echo htmlspecialchars($user_row['backimg'] ? 'pic/' . $user_row['backimg'] : 'pic/back.jpg'); ?>" 
                         id="backimg-display" class="modal-image">
            </div>
            
            <div class="form-group">
                <label for="backimg-file">Выберите файл:</label>
                <input type="file" id="backimg-file" name="avatar_file" 
                       accept="image/*" class="file-input">
            </div>
            
            <div class="modal-buttons">
                <button type="button" class="btn-cancel">Отмена</button>
                <button type="submit" class="btn-save">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<script src="style/modal.js"></script>

