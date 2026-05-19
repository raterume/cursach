<?php
require_once 'init.php';

function renderfollowers($follow, $current_user_id = null) {

    $is_followed = false;
    if ($current_user_id) {
        $conn = get_db_connection(); 
        $stmt = $conn->prepare("SELECT id FROM subscriptions WHERE user = ? and subs = ?");
        $stmt->bind_param("ii", $current_user_id, $follow['user_id']);
        $stmt->execute();
        $stmt->store_result();
        $is_followed = $stmt->num_rows > 0;
        $p = $stmt->num_rows;
        $stmt->close();
    }
    error_log("фффффффф ".htmlspecialchars($follow['user_id']));
?>
    <div class="follow-info">

        <div class="f-avatar">
            <img class="follow-avatar" src="<?php echo htmlspecialchars($follow['avatar'] ? 'pic/' . $follow['avatar'] : 'pic/ico.jpg'); ?>" >
        </div>

        <div class="f-name"><h3 class="follow-name">
            <a href="profile.php?id=<?php echo $follow['user_id']; ?>" class="user-link">
                <?php echo htmlspecialchars($follow['username']); ?>
            </a>
        </h3></div>

        <div class="f-button">
            <form action="includes/follow.php" method="POST">
            <input type="hidden" name="subs_id" value="<?php echo htmlspecialchars($follow['user_id']); ?>">
                <button type="submit" class="<?php echo $is_followed ? 'unfollow-btn' : 'follow-btn'; ?>">
                    <?php echo $is_followed ? 'отписаться' : 'подписаться'; ?>
                </button>
            </form>
        </div>
    </div>

<?}?>