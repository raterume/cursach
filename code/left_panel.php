<?php
    require_once 'init.php'; //сессия
    require_once 'class/user.php'; 
     $profile_user_id_left = $_SESSION['user']['id'];
    $conn = get_db_connection();
    $user_obj = new User($conn);

    $profile_user_left = $user_obj->getUserById($profile_user_id_left);
    $user_row_left = $profile_user_left->fetch_assoc();
    
?>
    
           
           <link rel="stylesheet" href="style/shimmer.css">
           <link rel="stylesheet" href="style/style_main.css">
       
       <aside class="sidebar">
        <h1 class="shimmer-text" data-text="MIRA">MIRA</h1>
            <!-- Основная навигация -->
            <nav class="main-nav">
                <ul>
                    <li><a href="profile.php" class="nav-link">
                        <img src="style/icons/mainpage.svg" class="nav-icon"><span class="nav-text">профиль</span>
                    </a></li>
                    <li><a href="feed.php" class="nav-link">
                        <img src="style/icons/home.svg" class="nav-icon"><span class="nav-text">главная</span>
                    </a></li>
                    <li><a href="followings.php" class="nav-link">
                        <img src="style/icons/podpiski.svg" class="nav-icon"><span class="nav-text">подписки</span>
                    </a></li>
                    <li><a href="zakladki.php" class="nav-link">
                        <img src="style/icons/zakladki.svg" class="nav-icon"><span class="nav-text">заклади</span>
                    </a></li>
                    <li><a href="settings.php" class="nav-link">
                        <img src="style/icons/settings.svg" class="nav-icon"><span class="nav-text">настройки</span>
                    </a></li>

        <?php if($user_row_left['role'] === 1){?>
                <li><a href="admin/dashboard.php" class="nav-link">
                    <img src="style/icons/book.svg" class="nav-icon"><span class="nav-text">админ</span>
                </a></li>
        <?}?>


                    <button class="btn-create" data-modal="modal-create-post">
                        <img src="style/icons/create.svg" class="nav-icon"><span class="nav-text">создать</span></button>
                </ul>
            </nav>
        </aside>



        <!-- Модальное окно создания поста -->
<div id="modal-create-post" class="modal">
    <div class="modal-content modal-wide">
        <span class="close-modal">&times;</span>
        <h2>Создать пост</h2>
        
        <form id="form-create-post" method="POST" action="includes/create_post.php" enctype="multipart/form-data">
            <!-- Текст поста -->
            <div class="form-group">
                <label for="post-text">Текст поста:</label>
                <textarea id="post-text" name="text" 
                          placeholder="О чем думаете?" 
                          rows="4" maxlength="1000" class="form-control"></textarea>
                <div class="char-counter">
                    <span id="char-count">0</span>/1000
                </div>
            </div>
            
            <!-- Загрузка изображений -->
            <div class="form-group">
                <label>Добавить изображения (до 4):</label>
                <div class="image-upload-area">
                    <!-- Контейнер для превью -->
                    <div class="image-previews" id="image-previews">
                        <div class="image-preview-item empty">
                            <label for="post-images" class="upload-label">
                                <div class="upload-placeholder">
                                    <span>+</span>
                                    <small>Добавить фото</small>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Скрытый input для файлов -->
                    <input type="file" id="post-images" class="file-input-hidden" 
                           accept="image/*" multiple class="file-input-hidden">
                </div>
                <small class="help-text">Можно загрузить JPG, PNG, GIF (макс. 2MB каждое)</small>
            </div>
            
            <div class="modal-buttons">
                <button type="button" class="btn-cancel">Отмена</button>
                <button type="submit" class="btn-save">Опубликовать</button>
            </div>
        </form>
    </div>
</div>

<script src="style/modal_post.js"></script>
