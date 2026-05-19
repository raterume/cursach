<?php

class follows {
    private $conn;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

        // те на КОГО подписан
    public function getFollowings($user_id) {
        $stmt = $this->conn->prepare("
            SELECT 
                s.id,
                s.user,
                s.subs,
                s.date_create,
                u.id as user_id,
                u.login as username,
                u.avatar
            FROM subscriptions s
            JOIN users u on s.subs = u.id
            where s.user = ?");
        
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result();
    }

        // те КТО подписан
    public function getFollowers($user_id) {
        $stmt = $this->conn->prepare("
            SELECT 
                s.id,
                s.user,
                s.subs,
                s.date_create,
                u.id as user_id,
                u.login as username,
                u.avatar
            FROM subscriptions s
            JOIN users u on s.user = u.id
            where s.subs = ?");
        
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result();
    }



    // Поиск пользователя
    public function searchUsers($search_term, $exclude_user_id=null, $limit = 20) {
    $search_term = '%' . $search_term . '%';
        $stmt = $this->conn->prepare("
        SELECT id as user_id, login as username, avatar 
            FROM Users 
            WHERE (login LIKE ? ) 
            AND id != ?
            LIMIT ?
        ");
    $stmt->bind_param("sii",  $search_term, $exclude_user_id, $limit);
    $stmt->execute();
    return $stmt->get_result();
}


public function toggleFollow($user_id, $subs_id) {
    // Проверяем, существует ли уже лайк
    $check_stmt = $this->conn->prepare("
        SELECT id FROM subscriptions 
        WHERE user = ? and subs = ?
    ");
    $check_stmt->bind_param("ii", $user_id, $subs_id);
    $check_stmt->execute();
    $check_stmt->store_result();
    $rr = $check_stmt->num_rows;
    error_log("форма ==== ".$rr);
    
    if ($check_stmt->num_rows > 0) {
        // Удаляем лайк
        $delete_stmt = $this->conn->prepare("
            DELETE FROM subscriptions 
            WHERE user = ? and subs = ?
        ");
        $delete_stmt->bind_param("ii", $user_id, $subs_id);
        $success = $delete_stmt->execute();
        $action = 'removed';
        logToDatabase('unfollow', 'user', $subs_id);
    } else {
        error_log("добавлена");
        // Добавляем лайк
        $insert_stmt = $this->conn->prepare("
            INSERT INTO subscriptions (user, subs) 
            VALUES (?, ?)
        ");
        $insert_stmt->bind_param("ii", $user_id, $subs_id);
        $success = $insert_stmt->execute();
        $action = 'added';
        logToDatabase('follow', 'user', $subs_id);
    }
    return ['success' => $success, 'action' => $action];
}

}
?>