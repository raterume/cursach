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

    <!-- Основная область контента -->
<!-- Основная область контента -->
<main class="content">
    
    <div class="admin-content">
        <h1>Статистика пользователей</h1>
        
        <!-- Фильтры поиска -->
        <div class="admin-filters">
            <form method="GET" action="" class="filter-form">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="search">Поиск:</label>
                        <input type="text" id="search" name="search" 
                               placeholder="Логин, email..." 
                               value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="role">Роль:</label>
                        <select id="role" name="role">
                            <option value="">Все роли</option>
                            <?php
                            $role_stmt = $conn->prepare("SELECT id, name FROM roles ORDER BY id");
                            $role_stmt->execute();
                            $roles = $role_stmt->get_result();
                            
                            while($role = $roles->fetch_assoc()) {
                                $selected = (isset($_GET['role']) && $_GET['role'] == $role['id']) ? 'selected' : '';
                                echo "<option value='{$role['id']}' $selected>{$role['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="sort">Сортировка:</label>
                        <select id="sort" name="sort">
                            <option value="new" <?= (isset($_GET['sort']) && $_GET['sort'] == 'new') ? 'selected' : '' ?>>Сначала новые</option>
                            <option value="old" <?= (isset($_GET['sort']) && $_GET['sort'] == 'old') ? 'selected' : '' ?>>Сначала старые</option>
                            <option value="name_asc" <?= (isset($_GET['sort']) && $_GET['sort'] == 'name_asc') ? 'selected' : '' ?>>Логин А-Я</option>
                            <option value="name_desc" <?= (isset($_GET['sort']) && $_GET['sort'] == 'name_desc') ? 'selected' : '' ?>>Логин Я-А</option>
                        </select>
                    </div>
                </div>
                
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="date_from">С:</label>
                        <input type="date" id="date_from" name="date_from" 
                               value="<?= isset($_GET['date_from']) ? $_GET['date_from'] : '' ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="date_to">По:</label>
                        <input type="date" id="date_to" name="date_to" 
                               value="<?= isset($_GET['date_to']) ? $_GET['date_to'] : '' ?>">
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn-filter">Применить фильтры</button>
                        <a href="?" class="btn-clear">Сбросить</a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Таблица пользователей -->
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Логин</th>
                        <th>Email</th>
                        <th>Роль</th>
                        <th>Дата регистрации</th>
                        <th>Посты</th>
                        <th>Лайки</th>
                        <th>Подписки</th>
                        <th>Подписчики</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Формируем SQL запрос с фильтрами
                    $sql = "SELECT u.*, r.name as role_name, 
                           (SELECT COUNT(*) FROM posts p WHERE p.user = u.id) as post_count,
                           (SELECT COUNT(*) FROM likes l WHERE l.user = u.id) as likes_count,
                           (SELECT COUNT(*) FROM Subscriptions s1 WHERE s1.user = u.id) as following_count,
                           (SELECT COUNT(*) FROM Subscriptions s2 WHERE s2.subs = u.id) as followers_count
                    FROM Users u 
                    LEFT JOIN roles r ON u.role = r.id 
                    WHERE 1=1";
                    
                    $params = [];
                    $types = "";
                    
                    // Фильтр по поиску
                    if (isset($_GET['search']) && !empty($_GET['search'])) {
                        $search = "%{$_GET['search']}%";
                        $sql .= " AND (u.login LIKE ? OR u.email LIKE ?)";
                        $params[] = $search;
                        $params[] = $search;
                        $types .= "ss";
                    }
                    
                    // Фильтр по роли
                    if (isset($_GET['role']) && is_numeric($_GET['role'])) {
                        $sql .= " AND u.role = ?";
                        $params[] = $_GET['role'];
                        $types .= "i";
                    }
                    
                    // Фильтр по дате
                    if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
                        $sql .= " AND DATE(u.date_create) >= ?";
                        $params[] = $_GET['date_from'];
                        $types .= "s";
                    }
                    
                    if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
                        $sql .= " AND DATE(u.date_create) <= ?";
                        $params[] = $_GET['date_to'];
                        $types .= "s";
                    }
                    
                    // Сортировка
                    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'new';
                    switch ($sort) {
                        case 'old':
                            $sql .= " ORDER BY u.date_create ASC";
                            break;
                        case 'name_asc':
                            $sql .= " ORDER BY u.login ASC";
                            break;
                        case 'name_desc':
                            $sql .= " ORDER BY u.login DESC";
                            break;
                        default:
                            $sql .= " ORDER BY u.date_create DESC";
                    }
                    
                    // Пагинация
                    $limit = 20;
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
                    
                    if ($result->num_rows > 0) {
                        while($user = $result->fetch_assoc()) {
                            $role_class = ($user['role'] == 1) ? 'admin-role' : 'user-role';
                            ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td>
                                    <div class="user-info-cell">
                                        <?= htmlspecialchars($user['login']) ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><span class="role-badge <?= $role_class ?>"><?= htmlspecialchars($user['role_name']) ?></span></td>
                                <td><?= date('d.m.Y H:i', strtotime($user['date_create'])) ?></td>
                                <td><?= $user['post_count'] ?></td>
                                <td><?= $user['likes_count'] ?></td>
                                <td><?= $user['following_count'] ?></td>
                                <td><?= $user['followers_count'] ?></td>
                                <td class="actions-cell">
                                    <a href="user_detail.php?id=<?= $user['id'] ?>" class="btn-view" title="Просмотр">
                                        👁️
                                    </a>
                                    <a href="?delete=<?= $user['id'] ?>" 
                                       class="btn-delete" 
                                       title="Удалить"
                                       onclick="return confirm('Вы уверены?')">
                                        🗑️
                                    </a>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo '<tr><td colspan="10" class="no-data">Пользователи не найдены</td></tr>';
                    }
                    
                    // Получаем общее количество для пагинации
                    $count_sql = "SELECT COUNT(*) as total FROM Users u WHERE 1=1";
                    if (isset($_GET['search']) && !empty($_GET['search'])) {
                        $search = "%{$_GET['search']}%";
                        $count_sql .= " AND (u.login LIKE ? OR u.email LIKE ?)";
                        $count_stmt = $conn->prepare($count_sql);
                        $count_stmt->bind_param("ss", $search, $search);
                    } elseif (isset($_GET['role']) && is_numeric($_GET['role'])) {
                        $count_sql .= " AND u.role = ?";
                        $count_stmt = $conn->prepare($count_sql);
                        $count_stmt->bind_param("i", $_GET['role']);
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
            <h3>Общая статистика</h3>
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-number"><?= $total_rows ?? 0 ?></span>
                    <span class="stat-label">Всего пользователей</span>
                </div>
                <div class="stat-card">
                    <?php
                    $today_stmt = $conn->prepare("SELECT COUNT(*) as count FROM Users WHERE DATE(date_create) = CURDATE()");
                    $today_stmt->execute();
                    $today_count = $today_stmt->get_result()->fetch_assoc()['count'];
                    ?>
                    <span class="stat-number"><?= $today_count ?></span>
                    <span class="stat-label">Зарегистрировано сегодня</span>
                </div>
                <div class="stat-card">
                    <?php
                    $week_stmt = $conn->prepare("SELECT COUNT(*) as count FROM Users WHERE date_create >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
                    $week_stmt->execute();
                    $week_count = $week_stmt->get_result()->fetch_assoc()['count'];
                    ?>
                    <span class="stat-number"><?= $week_count ?></span>
                    <span class="stat-label">За 7 дней</span>
                </div>
                <div class="stat-card">
                    <?php
                    $admin_stmt = $conn->prepare("SELECT COUNT(*) as count FROM Users WHERE role = 1");
                    $admin_stmt->execute();
                    $admin_count = $admin_stmt->get_result()->fetch_assoc()['count'];
                    ?>
                    <span class="stat-number"><?= $admin_count ?></span>
                    <span class="stat-label">Администраторов</span>
                </div>
            </div>
        </div>
    </div>
    
</main>



</div>


</div>