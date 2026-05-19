<?php
require_once 'init.php';
function renderPost($post, $current_user_id = null) {
    //время
    $time_ago = Post::formatTime($post['date_create']);
    //картинки

    $images = [];
    if (!empty($post['images'])) {
            $images = explode('||', $post['images']);
            $images = array_filter($images);
    }

    // HTML
    ob_start(); //буферизацию
    ?>

        <header class="post-header">
            <div class="post-author">
                <div class="author-avatar">
                    <!-- аватарка -->
                    <img class="user-avatar" src="<?php echo htmlspecialchars($post['avatar'] ? 'pic/' . $post['avatar'] : 'pic/ico.jpg'); ?>" >
                </div>
                <div class="author-info">
                    <!-- имя пользователя -->
                    <div class="author-name"><a href="profile.php?id=<?php echo $post['user_id']; ?>" class="author-link">
                        <?php echo htmlspecialchars($post['username']); ?></a></div>
                    <span class="post-time"><?php echo $time_ago; ?></span> <!-- время -->
                </div>
            </div>
            <div class="post-buttons">
                <?php if($_SESSION['user']['id'] === $post['user_id']){?>

                    <button type="submit" class="action-btn setting-btn edit-post-btn" data-modal="modal-edit-post"
                        data-post-id="<?php echo $post['id']; ?>"
                        data-post-text="<?php echo htmlspecialchars($post['text']); ?>"
                        data-post-images="<?php echo htmlspecialchars($post['images'] ?? ''); ?>"></button>

                            <button type="button" class="action-btn drop-btn delete-post-btn"
                                data-post-id="<?php echo $post['id']; ?>">
                            </button>

                    <?}?>
                </div>
        </header>

        <div class="post-content">
            <p><?php echo nl2br(htmlspecialchars($post['text'])); ?></p>
        </div>

            <?php if (!empty($images)): ?>
                <div class="post-images">
                    <?php foreach ($images as $index => $image_path): ?>
                        <?php if (!empty($image_path)): ?>
                <div class="post-image-container">
                    <img src="pic/<?php echo htmlspecialchars($image_path); ?>" 
                        alt="Изображение <?php echo $index + 1; ?>"
                        class="post-image"
                         data-index="<?php echo $index; ?>"
                        data-post-id="<?php echo $post['id']; ?>">
                </div>
            <?php endif; ?>
            <?php endforeach; ?> </div>
            <?php endif; ?>




<?php
return ob_get_clean();
}
?>