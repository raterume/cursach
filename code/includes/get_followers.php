<?php
require_once '../db/db.php';
require_once '../class/user.php';

session_start();
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id <= 0) {
    echo "Ошибка: неверный ID пользователя";
    exit();
}

$conn = get_db_connection();
$user_obj = new User($conn);

// Получаем подписчиков
$followers = $user_obj->getFollowers($user_id);

if (!$followers || $followers->num_rows === 0) {
    echo "<p class='no-users'>Нет подписчиков</p>";
    exit();
}

while ($follower = $followers->fetch_assoc()):
?>
<div class="user-item">
    <div class="user-avatar">
        <img class="user-avatar" src="<?php echo $follower['avatar'] ? 'pic/' . $follower['avatar'] : 'pic/ico.jpg'; ?>" 
             alt="<?php echo htmlspecialchars($follower['login']); ?>">
    </div>
    <div class="user-info">
        <a href="profile.php?id=<?php echo $follower['id']; ?>" class="user-name">
            <?php echo htmlspecialchars($follower['login']); ?>
        </a>
        <p class="user-bio"><?php echo htmlspecialchars($follower['inform'] ?? ''); ?></p>
    </div>
    <?php if (isset($_SESSION['user']['id']) && $_SESSION['user']['id'] != $follower['id']): ?>
    <div class="user-actions">
        <?php
        // Проверяем, подписан ли текущий пользователь на этого подписчика
        $is_following = $user_obj->isFollowing($_SESSION['user']['id'], $follower['id']);
        ?>
        <form action="includes/follow.php" method="POST" class="inline-form">
            <input type="hidden" name="subs_id" value="<?php echo $follower['id']; ?>">
            <button type="submit" class="<?php echo $is_following ? 'unfollow-btn small' : 'follow-btn small'; ?>">
                <?php echo $is_following ? 'Отписаться' : 'Подписаться'; ?>
            </button>
        </form>
    </div>
    <?php endif; ?>
</div>
<?php
endwhile;

$conn->close();
?>