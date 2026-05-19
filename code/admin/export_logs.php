<?php
session_start();
require_once '../db/db.php';

$conn = get_db_connection();

$sql = "SELECT al.*, u.login as user_login
FROM action_logs al 
LEFT JOIN Users u ON al.user_id = u.id 
WHERE 1=1";

$params = [];
$types = "";

// Фильтр по поиску пользователя
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_param = $_GET['search'];
    if (is_numeric($search_param)) {
        $sql .= " AND (al.user_id = ? OR u.login LIKE ?)";
        $params[] = intval($search_param);
        $params[] = "%{$search_param}%";
        $types .= "is";
    } else {
        $sql .= " AND u.login LIKE ?";
        $params[] = "%{$search_param}%";
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

// Фильтр по дате С
if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $sql .= " AND al.date_create >= ?";
    $params[] = $_GET['date_from'];
    $types .= "s";
}

// Фильтр по дате ПО
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

// Выполняем запрос
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
} else {
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();

// UTF-8 с BOM 
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=logs_' . date('Y-m-d_H-i') . '.csv');

// Открываем поток вывода
$output = fopen('php://output', 'w');

// Добавляем BOM для правильного определения
fwrite($output, "\xEF\xBB\xBF");

// Заголовки 
fputcsv($output, [
    'ID', 'Дата', 'Пользователь', 'ID пользователя', 
    'Действие', 'Тип объекта', 'ID объекта', 
    'IP-адрес', 'Детали', 'User Agent'
], ';'); 

// Функции для форматирования (такие же как на странице)
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
        'backimg' => 'Фон профиля',
        'inform' => 'Информация',
        'account' => 'Аккаунт',
        'like' => 'Лайк',
        'login' => 'Вход/выход'
    ];
    return $labels[$target_type] ?? $target_type;
}

// Данные
while($log = $result->fetch_assoc()) {
    fputcsv($output, [
        $log['id'],
        $log['date_create'],
        $log['user_login'] ?? ($log['user_id'] ? 'ID: ' . $log['user_id'] : 'Система'),
        $log['user_id'] ?? '',
        getActionLabel($log['action_type']), // Форматированное название действия
        $log['target_type'] ? getTargetLabel($log['target_type']) : '',
        $log['target_id'] ?? '',
        $log['ip_address'] ?? '',
        $log['details'] ?? '',
        $log['user_agent'] ?? ''
    ], ';');
}

fclose($output);
exit;