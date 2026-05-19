<?php
    require_once 'init.php'; //сессия
    redirectIfNotLoggedIn(); 
    require_once 'includes/post_function.php';
    require_once 'includes/reactions_function.php';    
    require_once 'includes/comment_function.php';
    require_once 'includes/profile_function.php';   
    require_once 'class/post.php'; 
    require_once 'class/user.php'; 

    $profile_user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    // Если ID не указан и пользователь авторизован - показываем его профиль
    if ($profile_user_id === 0 && isset($_SESSION['user'])) {
        $profile_user_id = $_SESSION['user']['id'];
    }

    if ($profile_user_id === 0) {
        // Ничего не нашли - на главную
        header('Location: feed.php');
        exit();
    }

    //создание поста для ленты
        $current_user = getCurrentUser();
        $conn = get_db_connection();
        $user_obj = new User($conn);
        $post = new Post($conn);
        $posts_result = $post->getFeedOnlyOne($profile_user_id, 50);
        $profile_user = $user_obj->getUserById($profile_user_id);
        $user_row = $profile_user->fetch_assoc();
        if (!$profile_user) {
        // Пользователь не найден
        header('Location: feed.php?error=user_not_found');
        exit();}
 
         $is_own_profile = isset($_SESSION['user']) && $_SESSION['user']['id'] == $profile_user_id;



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
        <?php require_once 'left_panel.php';?>

<main class="content">
    <div class="feed">
            <?php 
            echo renderProfileHeader($user_row, $is_own_profile);?>
            

                                <!-- ЛЕНТА ПОСТОВ -->
           <?php if ($posts_result->num_rows > 0): //проверка есть ли ваще посты
        while ($post_row = $posts_result->fetch_assoc()):?>

    <article class="post" data-post-id="<?php echo htmlspecialchars($post_row['id']); ?>">

        <?php   echo renderPost($post_row, $current_user['id']); //основной контент
                $reactions = $post->getReactions($post_row['id']);
                $reactions_data = $reactions->fetch_assoc();
                echo renderReactions($post_row['id'], $reactions_data, $current_user['id']);?>
    <!-- комменты -->
        <div class="comment-section" style="display: none;">

            <?php   $comm = $post->getComments($post_row['id']); //достаем
                    if ($comm->num_rows > 0):?>
                    <hr class="line">

            <div class="comment-footer">
                <form action="includes/create_comment.php" method="POST" class="comment-footer">
                    <input type="text" name="comm" class="send-comm" autocomplete="off" inputmode="emoji" required placeholder="ваш коментарий..." >
                    <button type="submit" class="action-btn comm-send-btn"></button>
                </form>
            </div>

            <?php    while ($comm_row = $comm->fetch_assoc()):?>
                            <div class="comment">
                    <?php echo renderComments($comm_row);?></div>
                        <?php endwhile; ?>
                    <?php endif;?>
            
        </div>
    </article>

    <?php endwhile; ?>
    <?php else: ?>
            <div class="no-posts">
                <p>тут пока ничего нет</p>
            </div>
    <?php endif; ?>

    </div>
</main>
    <?php require_once 'right_panel.php'?>
</div>
    </div>
    <script src="style/like.js"></script> 
    <script src="style/comment.js"></script>




<?php if ($is_own_profile) require_once 'modal_windows.php';?>








</body>
</html>
<?php $conn->close();?>
