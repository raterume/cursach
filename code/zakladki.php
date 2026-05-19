<?php
require_once 'init.php'; 
redirectIfNotLoggedIn(); 
require_once 'includes/post_function.php';
require_once 'includes/reactions_function.php';    
require_once 'includes/comment_function.php';   
require_once 'class/post.php'; 

$current_user = getCurrentUser();
$conn = get_db_connection();
$post = new Post($conn);
$posts_result = $post->getFeedLiked($current_user['id'], 50);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="style/style_main.css">
<title>feed</title>
</head>
<body>
<div class="page-wrapper">    

<div class="container">
    <!-- Левая боковая панель -->
    <?php require_once 'left_panel.php'?>

<main class="content">
            <!-- ЛЕНТА ПОСТОВ -->
    <div class="feed">

        <?php if ($posts_result->num_rows > 0): //проверка есть ли посты
        while ($post_row = $posts_result->fetch_assoc()):?>

        <article class="post" data-post-id="<?php echo htmlspecialchars($post_row['id']); ?>">
                <!-- ссылка на функцию вывода постов -->
            <?php   echo renderPost($post_row, $current_user['id']); 
            $reactions = $post->getReactions($post_row['id']);
            $reactions_data = $reactions->fetch_assoc();
            echo renderReactions($post_row['id'], $reactions_data, $current_user['id']);?>

            <!-- комментарии -->
            <div class="comment-section" style="display: none;">
            <hr class="line">
            <div class="comment-footer">
                    <!-- форма для написания коментария -->
                <form action="includes/create_comment.php" method="POST" class="comment-footer">
                        <input type="hidden" name="post_id" value="<?php echo $post_row['id']; ?>">
                    <input type="text" name="comm" class="send-comm" autocomplete="off" required placeholder="ваш коментарий..." >
                    <button type="submit" class="action-btn comm-send-btn"></button>
                </form>
            </div>
                <!-- ссылка на функцию вывода коментариев -->
            <?php   $comm = $post->getComments($post_row['id']); 
            if ($comm->num_rows > 0):?>
                <?php    while ($comm_row = $comm->fetch_assoc()):?>
                <div class="comment">
                <?php echo renderComments($comm_row);?></div>
            <?php endwhile; ?>
            <?php endif; ?>
            </div>
        </article>
                    <!-- если нет подписок -->
        <?php endwhile; ?>
        <?php else: ?>
        <div class="no-posts">
        <p>Лента пуста. Подпишитесь на кого-нибудь!</p>
        </div>
        <?php endif; ?>

    </div> 
</main>
            <!-- правое меню -->
    <?php require_once 'right_panel.php'?>

</div>
</div>
<script src="style/like.js"></script> 
<script src="style/comment.js"></script>

<?php require_once 'modal_windows.php'?>

</body>
</html>
<?php $conn->close();?>