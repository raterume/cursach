<?php

function get_db_connection(){
$conn=mysqli_connect('MySQL-8.0', 'root', '', 'mira');

if(!$conn){
    echo 'Ошибка подключения к бд. Код ошибки: '. mysqli_connect_error(). 'ошибка: '. mysqli_connect_error();
    exit;
}

    $conn->set_charset("utf8mb4");
    
    return $conn;
}







function logToDatabase($action, $target_type = null, $target_id = null, $details = null) {
    error_log("Вызвана logToDatabase: $action, target: $target_type, id: $target_id");
    
    static $logged_actions = []; // Храним уже записанные логи в текущем запросе
    
    // 1. Проверка на уровне текущего запроса
    $key = md5($action . $target_type . $target_id . json_encode($details));
    if (isset($logged_actions[$key])) {
        error_log("Дублирующий лог в текущем запросе: $action, пропускаем");
        return false;
    }
    $logged_actions[$key] = true;
    
    $conn = get_db_connection();
    
    // Получаем данные пользователя (если есть сессия)
    $user_id = null;
    if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user'])) {
        $user_id = $_SESSION['user']['id'];
    }
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $details_json = $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null;
    
        // 2. Проверка на уровне БД - ищем похожий лог за последние N секунд
        $time_window = 5; // Секунд для проверки дубликатов
        $check_duplicate = false;
        
        if ($user_id && $action && $target_type && $target_id) {
            $stmt_check = $conn->prepare("
                SELECT id FROM action_logs 
                WHERE user_id = ? 
                AND action_type = ? 
                AND target_type = ? 
                AND target_id = ? 
                AND date_create >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                LIMIT 1
            ");
            
            if ($stmt_check) {
                $stmt_check->bind_param("issii", $user_id, $action, $target_type, $target_id, $time_window);
                $stmt_check->execute();
                $stmt_check->store_result();
                
                if ($stmt_check->num_rows > 0) {
                    error_log("Дублирующий лог найден в БД за последние {$time_window} секунд: $action");
                    $stmt_check->close();
                    $conn->close();
                    return false;
                }
                $stmt_check->close();
            }
        }
        
        // 3. Записываем лог с уникальным хэшем
        
        $stmt = $conn->prepare("
            INSERT INTO action_logs (user_id, action_type, target_type, target_id, details, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
 $stmt->bind_param("ississs", $user_id, $action, $target_type, $target_id, $details_json, $ip, $user_agent);
        
        if ($stmt->execute()) {
            error_log("Лог успешно записан: $action, ID: " . $stmt->insert_id);
            $stmt->close();
            $conn->close();
            return true;
        } else {
            error_log("Ошибка выполнения запроса: " . $stmt->error);
            $stmt->close();
            $conn->close();
            return false;
        }
        
}

?>