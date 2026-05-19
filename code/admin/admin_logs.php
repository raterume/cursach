<?php
session_start();
require_once '../db/db.php';

$conn = get_db_connection();?>

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

    
<main class="content">
    
    <div class="admin-content">
        <h1>Логи действий</h1>
        
        <!-- Фильтры поиска -->
        <div class="admin-filters">
            <form method="GET" action="" class="filter-form">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="search">Поиск пользователя:</label>
                        <input type="text" id="search" name="search" 
                               placeholder="ID или логин пользователя..." 
                               value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="action_type">Тип действия:</label>
                        <select id="action_type" name="action_type">
                            <option value="">Все действия</option>
                            <option value="update" <?= (isset($_GET['action_type']) && $_GET['action_type'] == 'update') ? 'selected' : '' ?>>Обновление</option>
                            <option value="create" <?= (isset($_GET['action_type']) && $_GET['action_type'] == 'create') ? 'selected' : '' ?>>Создание</option>
                            <option value="follow" <?= (isset($_GET['action_type']) && $_GET['action_type'] == 'follow') ? 'selected' : '' ?>>Подписка</option>
                            <option value="unfollow" <?= (isset($_GET['action_type']) && $_GET['action_type'] == 'unfollow') ? 'selected' : '' ?>>Отписка</option>
                            <option value="login" <?= (isset($_GET['action_type']) && $_GET['action_type'] == 'login') ? 'selected' : '' ?>>Вход</option>
                            <option value="logout" <?= (isset($_GET['action_type']) && $_GET['action_type'] == 'logout') ? 'selected' : '' ?>>Выход</option>
                            <option value="delete" <?= (isset($_GET['action_type']) && $_GET['action_type'] == 'delete') ? 'selected' : '' ?>>Удаление</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="target_type">Тип объекта:</label>
                        <select id="target_type" name="target_type">
                            <option value="">Все объекты</option>
                            <option value="post" <?= (isset($_GET['target_type']) && $_GET['target_type'] == 'post') ? 'selected' : '' ?>>Пост</option>
                            <option value="comment" <?= (isset($_GET['target_type']) && $_GET['target_type'] == 'comment') ? 'selected' : '' ?>>Комментарий</option>
                            <option value="user" <?= (isset($_GET['target_type']) && $_GET['target_type'] == 'user') ? 'selected' : '' ?>>Пользователь</option>
                            <option value="avatar" <?= (isset($_GET['target_type']) && $_GET['target_type'] == 'avatar') ? 'selected' : '' ?>>Аватар</option>
                            <option value="backimg" <?= (isset($_GET['target_type']) && $_GET['target_type'] == 'backimg') ? 'selected' : '' ?>>Фон профиля</option>
                            <option value="inform" <?= (isset($_GET['target_type']) && $_GET['target_type'] == 'inform') ? 'selected' : '' ?>>Информация</option>
                            <option value="account" <?= (isset($_GET['target_type']) && $_GET['target_type'] == 'account') ? 'selected' : '' ?>>Аккаунт</option>
                            <option value="like" <?= (isset($_GET['target_type']) && $_GET['target_type'] == 'like') ? 'selected' : '' ?>>Лайк</option>
                            <option value="login" <?= (isset($_GET['target_type']) && $_GET['target_type'] == 'login') ? 'selected' : '' ?>>логин</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="sort">Сортировка:</label>
                        <select id="sort" name="sort">
                            <option value="new" <?= (isset($_GET['sort']) && $_GET['sort'] == 'new') ? 'selected' : '' ?>>Сначала новые</option>
                            <option value="old" <?= (isset($_GET['sort']) && $_GET['sort'] == 'old') ? 'selected' : '' ?>>Сначала старые</option>
                        </select>
                    </div>
                </div>
                
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="date_from">С:</label>
                        <input type="datetime-local" id="date_from" name="date_from" 
                               value="<?= isset($_GET['date_from']) ? $_GET['date_from'] : '' ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="date_to">По:</label>
                        <input type="datetime-local" id="date_to" name="date_to" 
                               value="<?= isset($_GET['date_to']) ? $_GET['date_to'] : '' ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="ip">IP-адрес:</label>
                        <input type="text" id="ip" name="ip" 
                               placeholder="192.168.1.1" 
                               value="<?= isset($_GET['ip']) ? htmlspecialchars($_GET['ip']) : '' ?>">
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn-filter">Применить фильтры</button>
                        <a href="?" class="btn-clear">Сбросить</a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Таблица логов -->
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Время</th>
                        <th>Пользователь</th>
                        <th>Действие</th>
                        <th>Объект</th>
                        <th>ID объекта</th>
                        <th>IP-адрес</th>
                        <th>Детали</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Формируем SQL запрос с фильтрами
                    $sql = "SELECT al.*, u.login as user_login, u.avatar
                    FROM action_logs al 
                    LEFT JOIN Users u ON al.user_id = u.id 
                    WHERE 1=1";
                    
                    $params = [];
                    $types = "";
                    
                    // Фильтр по поиску пользователя
                    if (isset($_GET['search']) && !empty($_GET['search'])) {
                        if (is_numeric($_GET['search'])) {
                            $sql .= " AND (al.user_id = ? OR u.login LIKE ?)";
                            $params[] = intval($_GET['search']);
                            $params[] = "%{$_GET['search']}%";
                            $types .= "is";
                        } else {
                            $sql .= " AND u.login LIKE ?";
                            $params[] = "%{$_GET['search']}%";
                            $types .= "s";
                        }
                    }
                    
                    // Фильтр по типу действия
                    if (isset($_GET['action_type']) && !empty($_GET['action_type'])) {
                        $sql .= " AND al.action_type = ?";
                        $params[] = $_GET['action_type'];
                        $types .= "s";
                    }
                    
                    // Фильтр по типу объекта
                    if (isset($_GET['target_type']) && !empty($_GET['target_type'])) {
                        $sql .= " AND al.target_type = ?";
                        $params[] = $_GET['target_type'];
                        $types .= "s";
                    }
                    
                    // Фильтр по IP-адресу
                    if (isset($_GET['ip']) && !empty($_GET['ip'])) {
                        $sql .= " AND al.ip_address LIKE ?";
                        $params[] = "%{$_GET['ip']}%";
                        $types .= "s";
                    }
                    
                    // Фильтр по дате
                    if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
                        $sql .= " AND al.date_create >= ?";
                        $params[] = $_GET['date_from'];
                        $types .= "s";
                    }
                    
                    if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
                        $sql .= " AND al.date_create <= ?";
                        $params[] = $_GET['date_to'];
                        $types .= "s";
                    }
                    
                    // Сортировка
                    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'new';
                    if ($sort == 'old') {
                        $sql .= " ORDER BY al.date_create ASC";
                    } else {
                        $sql .= " ORDER BY al.date_create DESC";
                    }
                    
                    // Пагинация
                    $limit = 50;
                    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
                    $offset = ($page - 1) * $limit;
                    $sql .= " LIMIT ? OFFSET ?";
                    
                    // Подготовка и выполнение запроса
                    $stmt = $conn->prepare($sql);
                    
                    if (!empty($params)) {
                        $params[] = $limit;
                        $params[] = $offset;
                        $types .= "ii";
                        $stmt->bind_param($types, ...$params);
                    } else {
                        $stmt->bind_param("ii", $limit, $offset);
                    }
                    
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    // Функция для форматирования типа действия
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
                    
                    // Функция для форматирования типа объекта
                    function getTargetLabel($target_type) {
                        $labels = [
                            'post' => 'Пост',
                            'comment' => 'Комментарий',
                            'user' => 'Пользователь',
                            'avatar' => 'Аватар',
                            'backimg' => 'Фон профиля',
                            'inform' => 'Информация',
                            'account' => 'Аккаунт',
                            'like' => 'Лайк',
                            'login' => 'логин'
                        ];
                        return $labels[$target_type] ?? $target_type;
                    }
                    
                    // Функция для получения цвета для типа действия
                    function getActionColor($action_type) {
                        $colors = [
                            'create' => '#4CAF50', // зеленый
                            'update' => '#2196F3', // синий
                            'delete' => '#F44336', // красный
                            'follow' => '#9C27B0', // фиолетовый
                            'unfollow' => '#FF9800', // оранжевый
                            'login' => '#00BCD4', // голубой
                            'logout' => '#607D8B' // серый
                        ];
                        return $colors[$action_type] ?? '#757575';
                    }
                    
                    if ($result->num_rows > 0) {
                        while($log = $result->fetch_assoc()) {
                            $action_color = getActionColor($log['action_type']);
                            ?>
                            <tr>
                                <td><?= $log['id'] ?></td>
                                <td>
                                    <span class="log-time">
                                        <?= date('d.m.Y', strtotime($log['date_create'])) ?><br>
                                        <small><?= date('H:i:s', strtotime($log['date_create'])) ?></small>
                                    </span>
                                </td>
                                <td>
                                    <?php if($log['user_id']): ?>
                                        <div class="user-info-cell">
                                            <?php if($log['avatar']): ?>
                                                <img src="../pic/<?= htmlspecialchars($log['avatar']) ?>" 
                                                     alt="Avatar" class="user-avatar-small">
                                            <?php endif; ?>
                                            <div>
                                                <a href="user_detail.php?id=<?= $log['user_id'] ?>" class="user-link">
                                                    <?= htmlspecialchars($log['user_login'] ?? 'ID: ' . $log['user_id']) ?>
                                                </a>
                                                <br>
                                                <small>ID: <?= $log['user_id'] ?></small>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="no-user">Система</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="action-badge" style="background-color: <?= $action_color ?>">
                                        <?= getActionLabel($log['action_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($log['target_type']): ?>
                                        <span class="target-badge">
                                            <?= getTargetLabel($log['target_type']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="no-target">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($log['target_id']): ?>
                                        <?php
                                        // Показываем ссылку в зависимости от типа объекта
                                        $target_link = '#';
                                        switch($log['target_type']) {
                                            case 'post':
                                                $target_link = "../post.php?id=" . $log['target_id'];
                                                break;
                                            case 'comment':
                                                $target_link = "../post.php?comment_id=" . $log['target_id'];
                                                break;
                                            case 'user':
                                                $target_link = "user_detail.php?id=" . $log['target_id'];
                                                break;
                                        }
                                        ?>
                                        <a href="<?= $target_link ?>" class="target-link" title="Перейти к объекту">
                                            #<?= $log['target_id'] ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="no-target">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($log['ip_address']): ?>
                                        <span class="ip-address" title="<?= htmlspecialchars($log['ip_address']) ?>">
                                            <?= htmlspecialchars($log['ip_address']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="no-ip">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($log['details']): ?>
                                        <button class="btn-details" 
                                                onclick="showDetails(<?= $log['id'] ?>)"
                                                title="Показать детали">
                                            👁️
                                        </button>
                                        <div id="details-<?= $log['id'] ?>" class="log-details" style="display: none;">
                                            <div class="details-content">
                                                <strong>Детали:</strong><br>
                                                <?= nl2br(htmlspecialchars($log['details'])) ?>
                                                <?php if($log['user_agent']): ?>
                                                    <hr>
                                                    <strong>User Agent:</strong><br>
                                                    <small><?= htmlspecialchars($log['user_agent']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="no-details">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo '<tr><td colspan="8" class="no-data">Логи не найдены</td></tr>';
                    }
                    
                    // Получаем общее количество для пагинации
                    $count_sql = "SELECT COUNT(*) as total FROM action_logs al LEFT JOIN Users u ON al.user_id = u.id WHERE 1=1";
                    
// Замените строки 364-373 на этот код:

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_param = $_GET['search'];
    if (is_numeric($search_param)) {
        $count_sql .= " AND (al.user_id = ? OR u.login LIKE ?)";
        $count_stmt = $conn->prepare($count_sql);
        $user_id = intval($search_param);
        $search_like = "%{$search_param}%";
        $count_stmt->bind_param("is", $user_id, $search_like);
    } else {
        $count_sql .= " AND u.login LIKE ?";
        $count_stmt = $conn->prepare($count_sql);
        $search_like = "%{$search_param}%";
        $count_stmt->bind_param("s", $search_like);
    }
} elseif (isset($_GET['action_type']) && !empty($_GET['action_type'])) {
    $count_sql .= " AND al.action_type = ?";
    $count_stmt = $conn->prepare($count_sql);
    $action_type = $_GET['action_type'];
    $count_stmt->bind_param("s", $action_type);
} elseif (isset($_GET['target_type']) && !empty($_GET['target_type'])) {
    $count_sql .= " AND al.target_type = ?";
    $count_stmt = $conn->prepare($count_sql);
    $target_type = $_GET['target_type'];
    $count_stmt->bind_param("s", $target_type);
} elseif (isset($_GET['ip']) && !empty($_GET['ip'])) {
    $count_sql .= " AND al.ip_address LIKE ?";
    $count_stmt = $conn->prepare($count_sql);
    $ip_like = "%{$_GET['ip']}%";
    $count_stmt->bind_param("s", $ip_like);
} else {
    $count_stmt = $conn->prepare($count_sql);
}
                    
                    if (isset($count_stmt)) {
                        $count_stmt->execute();
                        $count_result = $count_stmt->get_result();
                        $total_rows = $count_result->fetch_assoc()['total'];
                    }
                    ?>
                </tbody>
            </table>
            
            <!-- Пагинация -->
            <?php if (isset($total_rows) && $total_rows > $limit): ?>
            <div class="pagination">
                <?php
                $total_pages = ceil($total_rows / $limit);
                $query_params = $_GET;
                unset($query_params['page']);
                
                // Кнопка "Назад"
                if ($page > 1) {
                    $query_params['page'] = $page - 1;
                    echo '<a href="?' . http_build_query($query_params) . '" class="page-link">← Назад</a>';
                }
                
                // Номера страниц
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                for ($i = $start_page; $i <= $end_page; $i++) {
                    $query_params['page'] = $i;
                    $active = ($i == $page) ? 'active' : '';
                    echo '<a href="?' . http_build_query($query_params) . '" class="page-link ' . $active . '">' . $i . '</a>';
                }
                
                // Кнопка "Вперед"
                if ($page < $total_pages) {
                    $query_params['page'] = $page + 1;
                    echo '<a href="?' . http_build_query($query_params) . '" class="page-link">Вперед →</a>';
                }
                ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Сводная статистика -->
        <div class="summary-stats">
            <h3>Статистика логов</h3>
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-number"><?= $total_rows ?? 0 ?></span>
                    <span class="stat-label">Всего записей</span>
                </div>
                <div class="stat-card">
                    <?php
                    $today_stmt = $conn->prepare("SELECT COUNT(*) as count FROM action_logs WHERE DATE(date_create) = CURDATE()");
                    $today_stmt->execute();
                    $today_count = $today_stmt->get_result()->fetch_assoc()['count'];
                    ?>
                    <span class="stat-number"><?= $today_count ?></span>
                    <span class="stat-label">Сегодня</span>
                </div>
                <div class="stat-card">
                    <?php
                    $hour_stmt = $conn->prepare("SELECT COUNT(*) as count FROM action_logs WHERE date_create >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
                    $hour_stmt->execute();
                    $hour_count = $hour_stmt->get_result()->fetch_assoc()['count'];
                    ?>
                    <span class="stat-number"><?= $hour_count ?></span>
                    <span class="stat-label">За час</span>
                </div>
                <div class="stat-card">
                    <?php
                    $login_stmt = $conn->prepare("SELECT COUNT(*) as count FROM action_logs WHERE action_type IN ('login', 'logout')");
                    $login_stmt->execute();
                    $login_count = $login_stmt->get_result()->fetch_assoc()['count'];
                    ?>
                    <span class="stat-number"><?= $login_count ?></span>
                    <span class="stat-label">Входов/выходов</span>
                </div>
            </div>
            
            <!-- Кнопка очистки старых логов -->
            <div class="log-actions">

                
                <!-- Экспорт логов -->
                <a href="export_logs.php?<?= http_build_query($_GET) ?>" class="btn-export">
                    📥 Экспорт в CSV
                </a>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно для деталей (чистый PHP/CSS) -->
    <div id="detailsModal" class="modal" style="display: none;">
        <div class="modal-content modal-wide">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h2>Детали действия</h2>
            <div id="modalDetailsContent"></div>
        </div>
    </div>
    
</main>


<script>
// Простые функции для показа/скрытия деталей (если нужно)
function showDetails(logId) {
    var details = document.getElementById('details-' + logId);
    if (details.style.display === 'block') {
        details.style.display = 'none';
    } else {
        // Скрываем другие открытые детали
        var allDetails = document.querySelectorAll('.log-details');
        allDetails.forEach(function(detail) {
            detail.style.display = 'none';
        });
        details.style.display = 'block';
    }
}

// Для модального окна (альтернативный вариант)
function showModal(details, userAgent) {
    var modal = document.getElementById('detailsModal');
    var content = document.getElementById('modalDetailsContent');
    
    var html = '<strong>Детали:</strong><br>' + details.replace(/\n/g, '<br>');
    if (userAgent) {
        html += '<hr><strong>User Agent:</strong><br><small>' + userAgent + '</small>';
    }
    
    content.innerHTML = html;
    modal.style.display = 'block';
}

function closeModal() {
    document.getElementById('detailsModal').style.display = 'none';
}

// Закрытие модального окна при клике вне его
window.onclick = function(event) {
    var modal = document.getElementById('detailsModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>


</div>


</div>