<?php
require_once 'init.php';
function renderPost($post, $current_user_id = null) {
    //время
    $time_ago = Post::formatTime($post['date_create']);
    //картинки
    $images = [];
    if (isset($post['images'])) {
        $images = explode('||', $post['images']);
    }

    //реакции

    // $reaction = getReactions($post['id']);
    // HTML
    ob_start(); //буферизацию
    ?>

<article class="post" data-post-id="<?php echo htmlspecialchars($post['id']); ?>">

        <header class="post-header">
            <div class="post-author">
                <div class="author-avatar">
                    <!-- аватарка -->
                    <img class="user-avatar" src="<?php echo htmlspecialchars($post['avatar'] ? 'pic/' . $post['avatar'] : 'pic/ico.jpg'); ?>" >
                </div>
                <div class="author-info">
                    <h3 class="author-name"><?php echo htmlspecialchars($post['username']); ?></h3> <!-- логин -->
                    <span class="post-time"><?php echo $time_ago; ?></span> <!-- время -->
                </div>
            </div>
        </header>

        <div class="post-content">
            <p><?php echo nl2br(htmlspecialchars($post['text'])); ?></p>
        </div>
            <?php if (!empty($images)): ?>
                <div class="post-images">
                    <?php foreach ($images as $image_path): ?>
                    <?php if (!empty($image_path)): ?>
                <div class="post-image"><img src="<?php echo htmlspecialchars($image_path); ?>" alt="Изображение к посту"></div>
            <?php endif; ?>
            <?php endforeach; ?> </div>
            <?php endif; ?>
                    <!-- реакции -->
        <footer class="post-footer">
            <div class="post-actions">
                <button class="action-btn like-btn" data-post-id="<?php echo $post['id']; ?>">0
                </button>
                <button class="action-btn comment-btn" data-target="comments-<?php echo $post['id']; ?>">0</button>
                <button class="action-btn share-btn">0</button>
            </div>
        </footer>

    <div class="comment-section" style="display: none;">
                    <!-- коментарии -->
    <hr class="line">
        <div class="comment">
            <header class="post-header">
                <div class="post-author">
                    <div class="author-avatar">
                        <img class="comment-avatar" src="pic/ico.jpg">
                    </div>
                    <div class="author-info">
                        <h3 class="comment-name">имя пользователя</h3>
                        <span class="comment-text">текст поста текст поста текст поста текст поста текст поста текст поста текст поста текст поста текст поста</span>
                    </div>
                </div>
            </header>
            <footer class="comment-footer">
                <div class="post-actions">
                    <input type="comment" class="input" required placeholder="ваш коментарий..">
                    <button class="action-btn send-btn"><img src="style/icons/send.svg"></button>
                </div>
            </footer>
        </div>
    </div>
</article>

<?php
return ob_get_clean();
}
?>