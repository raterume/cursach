<?php
session_start();
require_once '../db/db.php';

$conn = get_db_connection();

// Используем CURDATE() для получения сегодняшней даты
$stmt = $conn->prepare("SELECT 
    (SELECT COUNT(*) FROM users) as user_count,
    (SELECT COUNT(*) FROM users WHERE DATE(date_create) = CURDATE()) as new_user,
    (SELECT COUNT(*) FROM posts) as post_count,
    (SELECT COUNT(*) FROM likes) as likes_count,
    (SELECT COUNT(*) FROM comments) as comm_count
FROM dual");

$stmt->execute();
$result = $stmt->get_result();
$static = $result->fetch_assoc();

// Получаем дополнительную статистику для отчета
$weekly_stmt = $conn->prepare("SELECT 
    (SELECT COUNT(*) FROM users WHERE date_create >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as weekly_users,
    (SELECT COUNT(*) FROM posts WHERE date_create >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as weekly_posts,
    (SELECT COUNT(*) FROM likes WHERE date_create >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as weekly_likes
FROM dual");
$weekly_stmt->execute();
$weekly_result = $weekly_stmt->get_result();
$weekly_stats = $weekly_result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../style/style_main.css">
<link rel="stylesheet" href="../style/style_admin.css">
<title>admin</title>
</head>
<body>
<div class="page-wrapper">    
    <div class="container">
        <!-- Левая боковая панель -->
        <?php require_once 'left_admin_panel.php'?>

        <!-- Основная область контента -->
        <main class="content">

            <div class=main-static>
                <div class="static">
                    <div class="static-info">
                        <img class="static-img"  src="../style/icons/podpiski.svg" style="filter: invert(34%) sepia(38%) saturate(3504%) hue-rotate(349deg) brightness(97%) contrast(93%);">
                        <div class="colvo-stat">
                            <span class="static-span" style="color: #f04923;">
                                <?php echo $static['user_count'] ?>
                            </span>
                            <span class="static-span-info">пользователей</span>
                        </div>
                    </div>
                </div>

                <div class="static">
                    <div class="static-info">
                        <img class="static-img"  src="../style/icons/person-add.svg" style="filter: invert(21%) sepia(75%) saturate(2825%) hue-rotate(185deg) brightness(94%) contrast(102%);">
                        <div class="colvo-stat">
                            <span class="static-span" style="color: #0067a5;">
                                <?php echo $static['new_user'] ?>
                            </span>
                            <span class="static-span-info">новых</span>
                        </div>
                    </div>
                </div>

                <div class="static">
                    <div class="static-info">
                        <img class="static-img"  src="../style/icons/file-outline.svg" style="filter: invert(59%) sepia(89%) saturate(4222%) hue-rotate(129deg) brightness(90%) contrast(101%);">
                        <div class="colvo-stat">
                            <span class="static-span" style="color: #00a86b;">
                                <?php echo $static['post_count'] ?>
                            </span>
                            <span class="static-span-info">постов</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class=main-static>
                <div class="static">
                    <div class="static-info">
                        <img class="static-img"  src="../style/icons/like.svg" style="filter: invert(34%) sepia(38%) saturate(3504%) hue-rotate(349deg) brightness(97%) contrast(93%);">
                        <div class="colvo-stat">
                            <span class="static-span" style="color: #f04923;">
                                <?php echo $static['likes_count'] ?>
                            </span>
                            <span class="static-span-info">лайков</span>
                        </div>
                    </div>
                </div>
                <div class="static">
                    <div class="static-info">
                        <img class="static-img"  src="../style/icons/comment.svg" style="filter: invert(21%) sepia(75%) saturate(2825%) hue-rotate(185deg) brightness(94%) contrast(102%);">
                        <div class="colvo-stat">
                            <span class="static-span" style="color: #0067a5;">
                                <?php echo $static['comm_count'] ?>
                            </span>
                            <span class="static-span-info">коментариев</span>
                        </div>
                    </div>
                </div>
                
            </div>

<div class="main-static" style="display: inline-block; margin-top: 20px;">
                        <!-- Блок с кнопками выгрузки -->
            <div class="export-actions">
                <div class="export-card">
                    <div class="export-icon">
                        <img src="../style/icons/cloud-download-outline.svg" alt="Report">
                    </div>
                    <div class="export-info">
                        <h3>Отчет по активности</h3>
                        <p>Создание PDF-отчета со статистикой пользователей</p>
                        <div class="export-stats">
                            <div class="export-stat">
                                <span class="stat-label">За неделю:</span>
                                <span class="stat-value"><?= $weekly_stats['weekly_users'] ?> пользователей</span>
                            </div>
                            <div class="export-stat">
                                <span class="stat-label">За неделю:</span>
                                <span class="stat-value"><?= $weekly_stats['weekly_posts'] ?> постов</span>
                            </div>
                        </div>
                        <form method="POST" action="generate_report.php" class="export-form">
                            <div class="form-group">
                                <label for="report_period">Период:</label>
                                <select id="report_period" name="period">
                                    <option value="day">За день</option>
                                    <option value="week" selected>За неделю</option>
                                    <option value="month">За месяц</option>
                                    <option value="all">За все время</option>
                                </select>
                            </div>
                            <button type="submit" class="export-btn">
                                Создать отчет
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="export-card">
                    <div class="export-icon">
                        <img src="../style/icons/save-outline.svg" alt="Backup">
                    </div>
                    <div class="export-info">
                        <h3>Резервная копия БД</h3>
                        <p>Создание полной резервной копии базы данных</p>
                        <div class="export-stats">
                            <div class="export-stat">
                                <span class="stat-label">Размер БД:</span>
                                <span class="stat-value">
                                    <?php
                                    $size_stmt = $conn->prepare("SELECT 
                                        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb
                                        FROM information_schema.tables 
                                        WHERE table_schema = DATABASE()");
                                    $size_stmt->execute();
                                    $size_result = $size_stmt->get_result();
                                    $db_size = $size_result->fetch_assoc()['size_mb'];
                                    echo $db_size ? $db_size . ' MB' : 'Неизвестно';
                                    ?>
                                </span>
                            </div>
                            <div class="export-stat">
                                <span class="stat-label">Дата последнего:</span>
                                <span class="stat-value">
                                    <?php
                                    $backup_dir = '../backups/';
                                    $latest_backup = '';
                                    if (file_exists($backup_dir)) {
                                        $files = glob($backup_dir . '*.sql');
                                        if ($files) {
                                            $latest_file = max($files);
                                            $latest_backup = date('d.m.Y H:i', filemtime($latest_file));
                                        }
                                    }
                                    echo $latest_backup ?: 'Никогда';
                                    ?>
                                </span>
                            </div>
                        </div>
                        <form method="POST" action="create_backup.php" class="export-form" onsubmit="return confirm('Создать резервную копию базы данных?')">
                            <div class="form-group">
                                <label for="backup_type">Тип копии:</label>
                                <select id="backup_type" name="type">
                                    <option value="full">Полная копия</option>
                                    <option value="structure">Только структура</option>
                                    <option value="data">Только данные</option>
                                </select>
                            </div>
                            <div class="export-buttons">
                                <button type="submit" class="export-btn backup-btn">
                                    Создать бэкап
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
</div> 

        </main>
    </div>
</div>


</body>
</html>