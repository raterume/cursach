<?php
require_once 'init.php';
function renderComments($comm) {
    $time_ago = Post::formatTime($comm['date_create']);
    ob_start(); //буферизацию
?>

<header class="post-header">
    <div class="comment-author">
        <div class="author-avatar">
            <img class="user-avatar" src="<?php echo htmlspecialchars($comm['avatar'] ? 'pic/' . $comm['avatar'] : 'pic/ico.jpg'); ?>" >
        </div>

        <div class="author-info">
            <div class="comment-name"><a href="profile.php?id=<?php echo $comm['user_id']; ?>" class="author-link">
            <?php echo htmlspecialchars($comm['username']); ?></a>
            </div>
            <div class="comm-time"><?php echo $time_ago; ?></div>

            <div class="post-content">
                <span class="comment-text"><?php echo htmlspecialchars($comm['text']); ?></span>
            </div>
        </div>

    </div>

        <div class="post-buttons">
                <?php if($_SESSION['user']['id'] === $comm['user_id']){?>

                <button type="button" class="setting-comm-btn edit-comment-btn"
                    data-comment-id="<?php echo $comm['id']; ?>"
                    data-comment-text="<?php echo htmlspecialchars($comm['text']); ?>">
                </button>


                <button type="button" class="drop-comm-btn delete-comment-btn"
                    data-comment-id="<?php echo $comm['id']; ?>">
                </button>

                    <?}?>
        </div>


</header>




<?php
        return ob_get_clean();
}
?>