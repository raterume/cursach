<?php
session_start();
require_once '../db/db.php';


if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: users.php');
    exit;
}

$user_id = intval($_GET['id']);
$conn = get_db_connection();

// Получаем основную информацию о пользователе
$stmt = $conn->prepare("SELECT u.*, r.name as role_name FROM Users u LEFT JOIN roles r ON u.role = r.id WHERE u.id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: users.php');
    exit;
}

$user = $result->fetch_assoc();

// Получаем статистику пользователя
$stats_stmt = $conn->prepare("SELECT 
    (SELECT COUNT(*) FROM posts WHERE user = ?) as post_count,
    (SELECT COUNT(*) FROM likes WHERE user = ?) as likes_given,
    (SELECT COUNT(*) FROM likes l INNER JOIN posts p ON l.post = p.id WHERE p.user = ?) as likes_received,
    (SELECT COUNT(*) FROM comments WHERE user = ?) as comment_count,
    (SELECT COUNT(*) FROM Subscriptions WHERE user = ?) as following_count,
    (SELECT COUNT(*) FROM Subscriptions WHERE subs = ?) as followers_count,
    (SELECT COUNT(*) FROM action_logs WHERE user_id = ?) as action_count,
    (SELECT COUNT(*) FROM action_logs WHERE user_id = ? AND action_type = 'login') as login_count,
    (SELECT COUNT(*) FROM action_logs WHERE user_id = ? AND action_type = 'logout') as logout_count,
    (SELECT MAX(date_create) FROM action_logs WHERE user_id = ? AND action_type = 'login') as last_login
    FROM dual");

$stats_stmt->bind_param("iiiiiiiiii", 
    $user_id, $user_id, $user_id, $user_id, 
    $user_id, $user_id, $user_id, $user_id, 
    $user_id, $user_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();

// Получаем последние действия пользователя
$logs_stmt = $conn->prepare("SELECT * FROM action_logs WHERE user_id = ? ORDER BY date_create DESC LIMIT 10");
$logs_stmt->bind_param("i", $user_id);
$logs_stmt->execute();
$logs = $logs_stmt->get_result();

// Получаем последние посты
$posts_stmt = $conn->prepare("SELECT p.*, 
    (SELECT COUNT(*) FROM likes WHERE post = p.id) as likes_count,
    (SELECT COUNT(*) FROM comments WHERE post = p.id) as comments_count
    FROM posts p WHERE p.user = ? ORDER BY date_create DESC LIMIT 5");
$posts_stmt->bind_param("i", $user_id);
$posts_stmt->execute();
$posts = $posts_stmt->get_result();

// Получаем популярные посты (по лайкам)
$popular_posts_stmt = $conn->prepare("SELECT p.*, 
    (SELECT COUNT(*) FROM likes WHERE post = p.id) as likes_count,
    (SELECT COUNT(*) FROM comments WHERE post = p.id) as comments_count
    FROM posts p WHERE p.user = ? 
    ORDER BY likes_count DESC LIMIT 3");
$popular_posts_stmt->bind_param("i", $user_id);
$popular_posts_stmt->execute();
$popular_posts = $popular_posts_stmt->get_result();

// Получаем активность по дням (последние 7 дней)
$activity_stmt = $conn->prepare("SELECT 
    DATE(date_create) as day,
    COUNT(*) as count,
    SUM(CASE WHEN action_type = 'login' THEN 1 ELSE 0 END) as logins,
    SUM(CASE WHEN action_type = 'post' THEN 1 ELSE 0 END) as posts,
    SUM(CASE WHEN action_type IN ('like', 'comment') THEN 1 ELSE 0 END) as interactions
    FROM action_logs 
    WHERE user_id = ? AND date_create >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(date_create)
    ORDER BY day DESC");
$activity_stmt->bind_param("i", $user_id);
$activity_stmt->execute();
$activity = $activity_stmt->get_result();
$activity_data = [];
while($row = $activity->fetch_assoc()) {
    $activity_data[$row['day']] = $row;
}

// Получаем подписки пользователя
$following_stmt = $conn->prepare("SELECT u.id, u.login, u.avatar, 
    (SELECT COUNT(*) FROM posts WHERE user = u.id) as post_count,
    (SELECT COUNT(*) FROM Subscriptions WHERE subs = u.id) as followers_count
    FROM Users u 
    INNER JOIN Subscriptions s ON u.id = s.subs 
    WHERE s.user = ? 
    ORDER BY s.date_create DESC LIMIT 5");
$following_stmt->bind_param("i", $user_id);
$following_stmt->execute();
$following = $following_stmt->get_result();

// Получаем подписчиков пользователя
$followers_stmt = $conn->prepare("SELECT u.id, u.login, u.avatar, 
    (SELECT COUNT(*) FROM posts WHERE user = u.id) as post_count,
    (SELECT COUNT(*) FROM Subscriptions WHERE subs = u.id) as followers_count
    FROM Users u 
    INNER JOIN Subscriptions s ON u.id = s.user
    WHERE s.subs = ? 
    ORDER BY s.date_create DESC LIMIT 5");
$followers_stmt->bind_param("i", $user_id);
$followers_stmt->execute();
$followers = $followers_stmt->get_result();

// Функции для форматирования
function formatDate($date) {
    if (!$date) return 'Никогда';
    return date('d.m.Y H:i', strtotime($date));
}

function timeAgo($date) {
    if (!$date) return '';
 $time = strtotime($date);
        $now = time();
        $diff = $now - $time;
        $plural = function($number, $form1, $form2, $form5) {
            $number = abs($number) % 100;
            if ($number > 10 && $number < 20) return $form5;
            $number = $number % 10;
            if ($number == 1) return $form1;
            if ($number >= 2 && $number <= 4) return $form2;
            return $form5;
        };        
        if ($diff < 60) {
            return 'только что';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return "$minutes минут" . $plural($minutes, '', 'ы', '') . ' назад';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return "$hours час" . $plural($hours, '', 'а', 'ов') . ' назад';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return "$days д" . $plural($days, 'ень', 'ня', 'ней') . ' назад';
        } else {
            return date('d.m.Y H:i', $time);
        }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style/style_main.css">
    <link rel="stylesheet" href="../style/style_admin.css">
    <title>Пользователь <?= htmlspecialchars($user['login']) ?> - Админка</title>
</head>
<body>
<div class="page-wrapper">    
    <div class="container">
        <!-- Левая боковая панель -->
        <?php require_once 'left_admin_panel.php'?>

        <!-- Основная область контента -->
        <main class="content">
            
            <div class="admin-content user-detail">
                <!-- Хлебные крошки -->
                <div class="breadcrumbs">
                    <a href="admin_users.php">Пользователи</a> &gt; 
                    <span><?= htmlspecialchars($user['login']) ?></span>
                </div>
                
                <!-- Заголовок и кнопки действий -->
                <div class="user-header">
                    <div class="user-header-info">
                        <h1>
                            <?php if($user['avatar']): ?>
                                <img src="../pic/<?= htmlspecialchars($user['avatar']) ?>" 
                                     alt="Avatar" class="user-avatar-large">
                            <?php endif; ?>
                            <?= htmlspecialchars($user['login']) ?>
                            <span class="role-badge <?= ($user['role'] == 1) ? 'admin-role' : 'user-role' ?>">
                                <?= htmlspecialchars($user['role_name']) ?>
                            </span>
                        </h1>
                        <div class="user-subinfo">
                            <span class="user-id">ID: <?= $user['id'] ?></span>
                            <span class="user-email"><?= htmlspecialchars($user['email']) ?></span>
                            <span class="user-reg-date">Зарегистрирован: <?= formatDate($user['date_create']) ?></span>
                        </div>
                    </div>
                    
                    <div class="user-header-actions">
                        <?php if($user['role'] != 1): ?>
                        <form method="POST" action="change_role.php" style="display: inline;">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <input type="hidden" name="new_role" value="1">

                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Основная статистика -->
                <div class="stats-grid main-stats">
                    <div class="stat-card">
                        <span class="stat-number"><?= $stats['post_count'] ?></span>
                        <span class="stat-label">Постов</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?= $stats['comment_count'] ?></span>
                        <span class="stat-label">Комментариев</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?= $stats['likes_given'] ?></span>
                        <span class="stat-label">Лайков поставлено</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?= $stats['likes_received'] ?></span>
                        <span class="stat-label">Лайков получено</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?= $stats['following_count'] ?></span>
                        <span class="stat-label">Подписок</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?= $stats['followers_count'] ?></span>
                        <span class="stat-label">Подписчиков</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?= $stats['action_count'] ?></span>
                        <span class="stat-label">Действий в логах</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number">
                            <?= $stats['login_count'] + $stats['logout_count'] ?>
                        </span>
                        <span class="stat-label">Входов/выходов</span>
                    </div>
                </div>
                
                <!-- Две колонки контента -->
                <div class="user-content-columns">
                    <!-- Левая колонка -->
                    <div class="user-left-column">
                        <!-- Информация о пользователе -->
                        <div class="info-section">
                            <h2>📝 Информация</h2>
                            <div class="info-card">
                                <?php if($user['backimg']): ?>
                                    <img src="../pic/<?= htmlspecialchars($user['backimg']) ?>" 
                                         alt="Background" class="user-background">
                                <?php endif; ?>
                                
                                <div class="info-content">
                                    <div class="info-row">
                                        <strong>Email:</strong>
                                        <span><?= htmlspecialchars($user['email']) ?></span>
                                    </div>
                                    <div class="info-row">
                                        <strong>Роль:</strong>
                                        <span class="role-badge <?= ($user['role'] == 1) ? 'admin-role' : 'user-role' ?>">
                                            <?= htmlspecialchars($user['role_name']) ?>
                                        </span>
                                    </div>
                                    <div class="info-row">
                                        <strong>Регистрация:</strong>
                                        <span><?= formatDate($user['date_create']) ?> 
                                            (<?= timeAgo($user['date_create']) ?>)</span>
                                    </div>
                                    <?php if($stats['last_login']): ?>
                                    <div class="info-row">
                                        <strong>Последний вход:</strong>
                                        <span><?= formatDate($stats['last_login']) ?> 
                                            (<?= timeAgo($stats['last_login']) ?>)</span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if($user['inform']): ?>
                                    <div class="info-row">
                                        <strong>О себе:</strong>
                                        <div class="user-bio"><?= nl2br(htmlspecialchars($user['inform'])) ?></div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        

                    </div>
                    
                    <!-- Правая колонка -->
                    <div class="user-right-column">
                        
                        <div class="info-section">
                            <h2>📈 Активность за 7 дней</h2>
                            <div class="activity-card">
                                <?php
                                $days = [];
                                for ($i = 6; $i >= 0; $i--) {
                                    $day = date('Y-m-d', strtotime("-$i days"));
                                    $days[$day] = $activity_data[$day] ?? ['count' => 0, 'logins' => 0, 'posts' => 0, 'interactions' => 0];
                                }
                                
                                foreach ($days as $day => $data):
                                ?>
                                <div class="activity-day">
                                    <div class="activity-date">
                                        <?= date('d.m', strtotime($day)) ?><br>
                                    </div>
                                    <div class="activity-bars">
                                        <div class="activity-bar" style="width: <?= min($data['count'] * 10, 100) ?>%" 
                                             title="Всего: <?= $data['count'] ?> действий"></div>
                                    </div>
                                    <div class="activity-stats">
                                        <span class="activity-stat" title="Входы">🔑 <?= $data['logins'] ?></span>
                                        <span class="activity-stat" title="Посты">📝 <?= $data['posts'] ?></span>
                                        <span class="activity-stat" title="Взаимодействия">💬 <?= $data['interactions'] ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Последние действия -->
                <div class="info-section full-width">
                    <h2>📋 Последние действия</h2>
                    <div class="logs-table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Время</th>
                                    <th>Действие</th>
                                    <th>Объект</th>
                                    <th>ID объекта</th>
                                    <th>IP</th>
                                    <th>Детали</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($logs->num_rows > 0): ?>
                                    <?php while($log = $logs->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= date('d.m.Y H:i:s', strtotime($log['date_create'])) ?></td>
                                        <td>
                                            <span class="action-badge" 
                                                  style="background-color: <?= getActionColor($log['action_type']) ?>">
                                                <?= getActionLabel($log['action_type']) ?>
                                            </span>
                                        </td>
                                        <td><?= getTargetLabel($log['target_type']) ?></td>
                                        <td>
                                            <?php if($log['target_id']): ?>
                                                #<?= $log['target_id'] ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($log['ip_address']): ?>
                                                <span class="ip-address"><?= $log['ip_address'] ?></span>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($log['details']): ?>
                                                <span class="log-details-small">
                                                    <?= mb_substr(htmlspecialchars($log['details']), 0, 50) ?>
                                                    <?= (strlen($log['details']) > 50) ? '...' : '' ?>
                                                </span>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="no-data">Нет записей в логах</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
        </main>
    </div>
</div>

<style>
/* Стили для страницы деталей пользователя */
.user-detail {
    max-width: 1400px;
}

.breadcrumbs {
    margin-bottom: 20px;
    font-size: 14px;
    color: #666;
}

.breadcrumbs a {
    color: #4a90e2;
    text-decoration: none;
}

.breadcrumbs a:hover {
    text-decoration: underline;
}

.user-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #eee;
}

.user-header-info h1 {
    display: flex;
    align-items: center;
    gap: 15px;
    margin: 0 0 10px 0;
}

.user-avatar-large {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.user-subinfo {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    font-size: 14px;
    color: #666;
}

.user-id, .user-email, .user-reg-date {
    background: #f5f7fa;
    padding: 5px 10px;
    border-radius: 20px;
}

.user-header-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.btn-action {
    display: inline-block;
    padding: 10px 20px;
    border-radius: 20px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-edit {
    background: #4a90e2;
    color: white;
}

.btn-edit:hover {
    background: #3a7bc8;
}

.btn-logs {
    background: #9C27B0;
    color: white;
}

.btn-logs:hover {
    background: #7B1FA2;
}

.btn-promote {
    background: #FF9800;
    color: white;
}

.btn-promote:hover {
    background: #F57C00;
}

.main-stats {
    margin-bottom: 30px;
}

.user-content-columns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

@media (max-width: 1024px) {
    .user-content-columns {
        grid-template-columns: 1fr;
    }
}

.info-section {
    margin-bottom: 30px;
}

.info-section h2 {
    font-size: 18px;
    color: #333;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.info-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.user-background {
    width: 100%;
    height: 120px;
    object-fit: cover;
    border-radius: 10px 10px 0 0;
    margin: -20px -20px 20px -20px;
}

.info-content {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.info-row {
    display: flex;
    align-items: flex-start;
    padding-bottom: 10px;
    border-bottom: 1px solid #f0f0f0;
}

.info-row:last-child {
    border-bottom: none;
}

.info-row strong {
    min-width: 150px;
    color: #555;
}

.user-bio {
    margin-top: 5px;
    line-height: 1.5;
    color: #333;
    background: #f9f9f9;
    padding: 10px;
    border-radius: 5px;
    border-left: 3px solid #4a90e2;
}

/* Активность по дням */
.activity-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.activity-day {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}

.activity-day:last-child {
    border-bottom: none;
}

.activity-date {
    text-align: center;
    min-width: 60px;
    font-size: 14px;
    font-weight: 500;
}

.activity-date small {
    display: block;
    font-size: 12px;
    color: #999;
    text-transform: uppercase;
}

.activity-bars {
    flex: 1;
    background: #f0f0f0;
    height: 8px;
    border-radius: 4px;
    overflow: hidden;
}

.activity-bar {
    height: 100%;
    background: linear-gradient(90deg, #4a90e2, #9C27B0);
    border-radius: 4px;
    transition: width 0.3s;
}

.activity-stats {
    display: flex;
    gap: 10px;
}

.activity-stat {
    font-size: 12px;
    color: #666;
}

/* Посты */
.posts-list {
    background: white;
    border-radius: 10px;
    padding: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.post-item {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.2s;
}

.post-item:hover {
    background: #f9f9f9;
}

.post-item:last-child {
    border-bottom: none;
}

.post-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.post-date {
    font-size: 12px;
    color: #999;
}

.post-preview {
    line-height: 1.5;
    color: #333;
    margin-bottom: 10px;
}

.post-stats {
    display: flex;
    gap: 15px;
    align-items: center;
    font-size: 12px;
}

.post-stat {
    color: #666;
    display: flex;
    align-items: center;
    gap: 3px;
}

.post-link {
    margin-left: auto;
    color: #4a90e2;
    text-decoration: none;
    font-size: 12px;
}

.post-link:hover {
    text-decoration: underline;
}

/* Социальные связи */
.connections-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

@media (max-width: 768px) {
    .connections-grid {
        grid-template-columns: 1fr;
    }
}

.connections-column h3 {
    font-size: 16px;
    margin-bottom: 15px;
    color: #555;
}

.connections-list {
    background: white;
    border-radius: 10px;
    padding: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.connection-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}

.connection-item:last-child {
    border-bottom: none;
}

.connection-avatar img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.connection-info {
    flex: 1;
}

.connection-name {
    font-weight: 500;
    color: #333;
    text-decoration: none;
    display: block;
    margin-bottom: 5px;
}

.connection-name:hover {
    color: #4a90e2;
}

.connection-stats {
    display: flex;
    gap: 10px;
    font-size: 11px;
    color: #999;
}

/* Популярные посты */
.popular-posts {
    background: white;
    border-radius: 10px;
    padding: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.popular-post-item {
    display: flex;
    gap: 15px;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}

.popular-post-item:last-child {
    border-bottom: none;
}

.popular-post-stats {
    min-width: 60px;
    text-align: center;
    background: linear-gradient(135deg, #FF9800, #F57C00);
    border-radius: 10px;
    padding: 10px;
    color: white;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.popular-stat {
    font-size: 12px;
    margin: 2px 0;
}

.popular-post-content {
    flex: 1;
}

.popular-post-preview {
    font-size: 14px;
    line-height: 1.4;
    color: #333;
    margin-bottom: 5px;
}

.popular-post-date {
    font-size: 11px;
    color: #999;
}

/* Логи */
.full-width {
    grid-column: 1 / -1;
}

.logs-table-container {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.log-details-small {
    font-size: 12px;
    color: #666;
    background: #f9f9f9;
    padding: 2px 6px;
    border-radius: 4px;
    display: inline-block;
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.view-all-link {
    display: inline-block;
    margin-top: 15px;
    color: #4a90e2;
    text-decoration: none;
    font-size: 14px;
}

.view-all-link:hover {
    text-decoration: underline;
}

.no-data {
    text-align: center;
    padding: 20px;
    color: #999;
    font-style: italic;
}

/* Общие функции (должны быть в отдельном файле или выше) */
<?php
function getActionColor($action_type) {
    $colors = [
        'create' => '#4CAF50',
        'update' => '#2196F3',
        'delete' => '#F44336',
        'follow' => '#9C27B0',
        'unfollow' => '#FF9800',
        'login' => '#00BCD4',
        'logout' => '#607D8B'
    ];
    return $colors[$action_type] ?? '#757575';
}

function getActionLabel($action_type) {
    $labels = [
        'update' => 'Обновление',
        'create' => 'Создание',
        'follow' => 'Подписка',
        'unfollow' => 'Отписка',
        'login' => 'Вход',
        'logout' => 'Выход',
        'delete' => 'Удаление'
    ];
    return $labels[$action_type] ?? $action_type;
}

function getTargetLabel($target_type) {
    $labels = [
        'post' => 'Пост',
        'comment' => 'Комментарий',
        'user' => 'Пользователь',
        'avatar' => 'Аватар',
        'backimg' => 'Фон',
        'inform' => 'Инфо',
        'account' => 'Аккаунт',
        'like' => 'Лайк',
        'login' => 'Вход/выход'
    ];
    return $labels[$target_type] ?? $target_type;
}
?>
</style>
</body>
</html>