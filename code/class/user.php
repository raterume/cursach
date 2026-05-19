<?php

class User {
    private $conn;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
    
    // Проверка, существует ли пользователь с таким email или логином
    public function userExists($email, $login) {
        $stmt = $this->conn->prepare("
            SELECT id FROM Users 
            WHERE email = ? OR login = ?");
        
        $stmt->bind_param("ss", $email, $login);
        $stmt->execute();
        $stmt->store_result();
        
        return $stmt->num_rows > 0;
    }
    
    // Регистрация нового пользователя
    public function register($login, $email, $password) {
        // Хэшируем пароль
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        // роль пользователь
        $default_role = 2;
        $stmt = $this->conn->prepare("
            INSERT INTO Users (login, email, password, role, inform) 
            VALUES (?, ?, ?, ?, ' ')");
        $stmt->bind_param("sssi", $login, $email, $hashed_password, $default_role);
        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            $stmt->close();
            return $user_id;
        } else {
            $stmt->close();
            return false;
        }
    }
    
    // Авторизация пользователя
    public function login($email, $password) {
        $stmt = $this->conn->prepare("
            SELECT id, login, email, password, role 
            FROM Users 
            WHERE email = ? 
        ");
// Ищем по email 
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
         $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        // Проверка пароля
        if (password_verify($password, $user['password'] )) {
            unset($user['password']);
            return $user;
        }
    }
    return false;
    }

    public function getUserById($user_id) {
        $stmt = $this->conn->prepare("
        SELECT 
            u.id, u.login, u.email, u.avatar, u.backimg, u.inform, u.role, u.password,
            (SELECT count(*) from subscriptions where user = ?) as podpiski,
            (SELECT count(*) from subscriptions where subs = ?) as podpischiki,
            (SELECT count(*) from likes where user = ?) as likes,
            (SELECT count(*) from comments where user = ?) as comments,
            (SELECT count(*) from posts where user = ?) as posts
        FROM users u WHERE u.id = ?
        LIMIT 1");
        $stmt->bind_param("iiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function isFollowing($follower_id, $following_id) {
    $stmt = $this->conn->prepare("
        SELECT id FROM subscriptions 
        WHERE user = ? AND subs = ?
        LIMIT 1");
    $stmt->bind_param("ii", $follower_id, $following_id);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
}


        // получение подписок и подписчиков
public function getFollowers($user_id) {
    $stmt = $this->conn->prepare("
        SELECT u.id, u.login, u.avatar, u.inform 
        FROM users u
        INNER JOIN subscriptions s ON u.id = s.user
        WHERE s.subs = ?
        ORDER BY s.date_create DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

public function getFollowing($user_id) {
    $stmt = $this->conn->prepare("
        SELECT u.id, u.login, u.avatar, u.inform 
        FROM users u
        INNER JOIN subscriptions s ON u.id = s.subs
        WHERE s.user = ?
        ORDER BY s.date_create DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}


public function getActivity($user_id) {
    $stmt = $this->conn->prepare("
        SELECT * FROM (
            SELECT 
                'follow' as type, u.id as actor_id,
                u.login as actor_login, u.avatar as actor_avatar,
                'подписался(ась) на вас' as message,
                s.date_create as notification_date,
                NULL as post_id, NULL as target_id, NULL as content,
                s.id as ref_id
            FROM subscriptions s
            INNER JOIN Users u ON s.user = u.id
            WHERE s.subs = ?
                AND s.user != ?
            UNION ALL
            
            SELECT 
                'like' as type, u.id as actor_id,
                u.login as actor_login, u.avatar as actor_avatar,
                'лайкнул(а) ваш пост' as message,
                l.date_create as notification_date,
                p.id as post_id, l.id as target_id,
                LEFT(p.text, 100) as content,
                l.id as ref_id
            FROM likes l
            INNER JOIN Users u ON l.user = u.id
            INNER JOIN posts p ON l.post = p.id
            WHERE p.user = ?
                AND l.user != ?
            UNION ALL
            
            SELECT 
                'comment' as type, u.id as actor_id,
                u.login as actor_login, u.avatar as actor_avatar,
                'прокомментировал(а) ваш пост' as message,
                c.date_create as notification_date,
                p.id as post_id, c.id as target_id,
                LEFT(c.text, 100) as content,
                c.id as ref_id
            FROM comments c
            INNER JOIN users u ON c.user = u.id
            INNER JOIN posts p ON c.post = p.id
            WHERE p.user = ?
                AND c.user != ?
        ) as notifications
        ORDER BY notification_date DESC
        limit 6;
    ");
    $stmt->bind_param("iiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

}?>