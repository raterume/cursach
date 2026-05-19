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

// Получаем подписки
$following = $user_obj->getFollowing($user_id);

if (!$following || $following->num_rows === 0) {
    echo "<p class='no-users'>Нет подписок</p>";
    exit();
}

while ($followed = $following->fetch_assoc()):
?>
<div class="user-item">
    <div class="user-avatar">
        <img src="<?php echo $followed['avatar'] ? 'pic/' . $followed['avatar'] : 'pic/ico.jpg'; ?>" 
             alt="<?php echo htmlspecialchars($followed['login']); ?>">
    </div>
    <div class="user-info">
        <a href="profile.php?id=<?php echo $followed['id']; ?>" class="user-name">
            <?php echo htmlspecialchars($followed['login']); ?>
        </a>
        <p class="user-bio"><?php echo htmlspecialchars($followed['inform'] ?? ''); ?></p>
    </div>
    <?php if (isset($_SESSION['user']['id']) && $_SESSION['user']['id'] != $followed['id']): ?>
    <div class="user-actions">
        <?php
        // Проверяем, подписан ли текущий пользователь на этого пользователя
        $is_following = $user_obj->isFollowing($_SESSION['user']['id'], $followed['id']);
        ?>
        <form action="includes/follow.php" method="POST" class="inline-form">
            <input type="hidden" name="subs_id" value="<?php echo $followed['id']; ?>">
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