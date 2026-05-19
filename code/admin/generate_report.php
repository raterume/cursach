<?php
session_start();
require_once '../db/db.php';

$period = $_POST['period'] ?? 'week';
$conn = get_db_connection();

// Определяем период
switch($period) {
    case 'day':
        $date_condition = "DATE(date_create) = CURDATE()";
        $period_name = "за день";
        break;
    case 'month':
        $date_condition = "date_create >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
        $period_name = "за месяц";
        break;
    case 'all':
        $date_condition = "1=1";
        $period_name = "за все время";
        break;
    default: // week
        $date_condition = "date_create >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        $period_name = "за неделю";
}

// Получаем статистику
$stats_stmt = $conn->prepare("SELECT 
    (SELECT COUNT(*) FROM users WHERE $date_condition) as users_count,
    (SELECT COUNT(*) FROM posts WHERE $date_condition) as posts_count,
    (SELECT COUNT(*) FROM likes WHERE $date_condition) as likes_count,
    (SELECT COUNT(*) FROM comments WHERE $date_condition) as comments_count,
    (SELECT COUNT(*) FROM Subscriptions WHERE $date_condition) as subs_count,
    (SELECT COUNT(*) FROM action_logs WHERE $date_condition) as actions_count");

$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();

// Получаем топ пользователей
$top_users_stmt = $conn->prepare("SELECT 
    u.id, u.login, u.email,
    (SELECT COUNT(*) FROM posts p WHERE p.user = u.id AND $date_condition) as post_count,
    (SELECT COUNT(*) FROM likes l WHERE l.user = u.id AND $date_condition) as like_count,
    (SELECT COUNT(*) FROM comments c WHERE c.user = u.id AND $date_condition) as comment_count
    FROM Users u
    WHERE EXISTS (SELECT 1 FROM action_logs al WHERE al.user_id = u.id AND $date_condition)
    ORDER BY (post_count + like_count + comment_count) DESC
    LIMIT 10");

$top_users_stmt->execute();
$top_users = $top_users_stmt->get_result();

// Создаем HTML отчет (который можно сохранить как PDF через браузер)
$html = '<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отчет по активности - ' . $period_name . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #333; margin-bottom: 5px; }
        .header .subtitle { color: #666; font-size: 16px; }
        .info-block { margin-bottom: 20px; }
        .info-block h2 { color: #444; border-bottom: 2px solid #4a90e2; padding-bottom: 5px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-card { background: #f5f7fa; border-radius: 8px; padding: 15px; text-align: center; }
        .stat-number { font-size: 28px; font-weight: bold; color: #4a90e2; display: block; }
        .stat-label { font-size: 14px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #4a90e2; color: white; padding: 12px; text-align: left; }
        td { padding: 10px; border: 1px solid #ddd; }
        tr:nth-child(even) { background: #f9f9f9; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; color: #888; font-size: 12px; text-align: center; }
        .timestamp { text-align: center; color: #666; margin-bottom: 30px; }
        .total-row { font-weight: bold; background: #e8f4ff; }
        .print-btn { display: inline-block; background: #4a90e2; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; margin: 20px 0; }
        .print-btn:hover { background: #357ae8; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Отчет по активности пользователей</h1>
        <div class="subtitle">Социальная сеть - Административная панель</div>
        <div class="timestamp">Период: ' . $period_name . ' | Дата генерации: ' . date('d.m.Y H:i:s') . '</div>
    </div>
    
    <div class="info-block">
        <h2>📊 Общая статистика</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number">' . ($stats['users_count'] ?? 0) . '</span>
                <span class="stat-label">Пользователей</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">' . ($stats['posts_count'] ?? 0) . '</span>
                <span class="stat-label">Постов</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">' . ($stats['likes_count'] ?? 0) . '</span>
                <span class="stat-label">Лайков</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">' . ($stats['comments_count'] ?? 0) . '</span>
                <span class="stat-label">Комментариев</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">' . ($stats['subs_count'] ?? 0) . '</span>
                <span class="stat-label">Подписок</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">' . ($stats['actions_count'] ?? 0) . '</span>
                <span class="stat-label">Действий</span>
            </div>
        </div>
    </div>';

// Добавляем таблицу топ пользователей
if ($top_users->num_rows > 0) {
    $html .= '
    <div class="info-block">
        <h2>🏆 Топ-10 самых активных пользователей</h2>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Логин</th>
                    <th>Email</th>
                    <th>Посты</th>
                    <th>Лайки</th>
                    <th>Комментарии</th>
                    <th>Всего</th>
                </tr>
            </thead>
            <tbody>';
    
    $counter = 1;
    $total_posts = 0;
    $total_likes = 0;
    $total_comments = 0;
    
    while($user = $top_users->fetch_assoc()) {
        $total = $user['post_count'] + $user['like_count'] + $user['comment_count'];
        $total_posts += $user['post_count'];
        $total_likes += $user['like_count'];
        $total_comments += $user['comment_count'];
        
        $html .= '
                <tr>
                    <td>' . $counter++ . '</td>
                    <td>' . htmlspecialchars($user['login']) . '</td>
                    <td>' . htmlspecialchars($user['email']) . '</td>
                    <td>' . $user['post_count'] . '</td>
                    <td>' . $user['like_count'] . '</td>
                    <td>' . $user['comment_count'] . '</td>
                    <td><strong>' . $total . '</strong></td>
                </tr>';
    }
    
    $html .= '
                <tr class="total-row">
                    <td colspan="3"><strong>Итого:</strong></td>
                    <td><strong>' . $total_posts . '</strong></td>
                    <td><strong>' . $total_likes . '</strong></td>
                    <td><strong>' . $total_comments . '</strong></td>
                    <td><strong>' . ($total_posts + $total_likes + $total_comments) . '</strong></td>
                </tr>
            </tbody>
        </table>
    </div>';
}

// Статистика по типам действий
$actions_stmt = $conn->prepare("SELECT 
    action_type, COUNT(*) as count 
    FROM action_logs 
    WHERE $date_condition 
    GROUP BY action_type 
    ORDER BY count DESC");

$actions_stmt->execute();
$actions_result = $actions_stmt->get_result();

if ($actions_result->num_rows > 0) {
    $html .= '
    <div class="info-block">
        <h2>📈 Статистика по типам действий</h2>
        <table>
            <thead>
                <tr>
                    <th>Тип действия</th>
                    <th>Количество</th>
                    <th>Процент</th>
                </tr>
            </thead>
            <tbody>';
    
    $total_actions = $stats['actions_count'] ?? 0;
    while($action = $actions_result->fetch_assoc()) {
        $percentage = $total_actions > 0 ? round(($action['count'] / $total_actions) * 100, 1) : 0;
        
        // Русские названия для типов действий
        $action_names = [
            'create' => 'Создание',
            'update' => 'Обновление',
            'delete' => 'Удаление',
            'follow' => 'Подписка',
            'unfollow' => 'Отписка',
            'login' => 'Вход',
            'logout' => 'Выход'
        ];
        
        $action_name = $action_names[$action['action_type']] ?? $action['action_type'];
        
        $html .= '
                <tr>
                    <td>' . $action_name . '</td>
                    <td>' . $action['count'] . '</td>
                    <td>' . $percentage . '%</td>
                </tr>';
    }
    
    $html .= '
            </tbody>
        </table>
    </div>';
}

// Закрываем HTML
$html .= '
    <div class="footer">
        <p>Отчет сгенерирован автоматически системой управления социальной сетью MIRA</p>
    </div>
</body>
</html>';

// Сохраняем отчет в файл (альтернатива PDF)
$reports_dir = '../reports/';
if (!file_exists($reports_dir)) {
    mkdir($reports_dir, 0777, true);
}

$report_filename = 'report_' . $period . '_' . date('Y-m-d_H-i-s') . '.html';
$report_filepath = $reports_dir . $report_filename;

file_put_contents($report_filepath, $html);



// Отправляем заголовки для загрузки файла
header('Content-Type: text/html');
header('Content-Disposition: attachment; filename="' . $report_filename . '"');
header('Content-Length: ' . filesize($report_filepath));

// Читаем и выводим файл
readfile($report_filepath);

// Удаляем временный файл (опционально)
// unlink($report_filepath);

exit;
?>