<?php
require_once 'init.php';
function renderReactions($post, $reaction, $current_user_id) {
    //реакции

    $is_liked = false;
    if ($current_user_id) {
        // Проверка в БД
        $conn = get_db_connection(); 
        $stmt = $conn->prepare("SELECT id FROM likes WHERE post = ? AND user = ?");
        $stmt->bind_param("ii", $post, $current_user_id);
        $stmt->execute();
        $stmt->store_result();
        $is_liked = $stmt->num_rows > 0;
        $p = $stmt->num_rows;
        $stmt->close();
    }else{error_log("???????");}


    ob_start(); //буферизация
    ?>

<footer class="post-footer">
    <div class="post-actions">

    <form action="includes/like.php" method="POST">
    <input type="hidden" name="post_id" value="<?php echo $post; ?>">
            <button type="submit" class="action-btn like-btn <?php echo $is_liked ? 'liked' : ''; ?>">
                        <?php echo $reaction['hearts'];?> 
            </button>
                    <!-- коментарии -->
        <button class="action-btn comment-btn" data-target="comments-<?php echo $post; ?>">
                    <?php echo $reaction['coment']; ?>
        </button>
    </form>

        <!-- <button class="action-btn share-btn">0</button> -->
   
    </div>

</footer>


<?php
return ob_get_clean();
}
?>