<?php
class Post {
    private $conn;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
    
    // Получение постов (посты челов, на которых подписан юзер)
    public function getFeed($user_id, $limit = 50) {
        $stmt = $this->conn->prepare("
            SELECT 
                p.id, 
                p.text, 
                p.date_create,
                u.id as user_id,
                u.login as username,
                u.avatar,
                GROUP_CONCAT(i.place SEPARATOR '||') as images
            FROM posts p
            JOIN Users u ON p.user = u.id
            LEFT JOIN Image i ON p.id = i.post
            WHERE p.user IN (
                SELECT s.subs 
                FROM subscriptions s 
                WHERE s.user = ?
            ) OR p.user = ?
            GROUP BY p.id  
            ORDER BY p.date_create DESC
            LIMIT ?");
        
        $stmt->bind_param("iii", $user_id, $user_id, $limit);
        $stmt->execute();
        return $stmt->get_result();
    }
    
        // Получение постов (для страницы пользователя)
    public function getFeedOnlyOne($user_id, $limit = 50) {
        $stmt = $this->conn->prepare("
            SELECT 
                p.id, 
                p.text, 
                p.date_create,
                u.id as user_id,
                u.login as username,
                u.avatar,
                GROUP_CONCAT(i.place SEPARATOR '||') as images
            FROM posts p
            JOIN Users u ON p.user = u.id
            LEFT JOIN Image i ON p.id = i.post
            WHERE p.user = ?
            GROUP BY p.id  
            ORDER BY p.date_create DESC
            LIMIT ?");
        
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        return $stmt->get_result();
    }


            // Получение постов (для страницы лайков)
    public function getFeedLiked($user_id, $limit = 50) {
        $stmt = $this->conn->prepare("
            SELECT 
                p.id, 
                p.text, 
                p.date_create,
                u.id as user_id,
                u.login as username,
                u.avatar,
                GROUP_CONCAT(i.place SEPARATOR '||') as images
            FROM posts p
            JOIN Users u ON p.user = u.id
            LEFT JOIN Image i ON p.id = i.post
            WHERE p.id in (select l.post from likes l where l.user = ?)
            GROUP BY p.id  
            ORDER BY p.date_create DESC
            LIMIT ?");
        
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        return $stmt->get_result();
    }

    
    // Получение всех постов (для админки)
    public function getAllPosts($limit = 100) {
        $stmt = $this->conn->prepare("
            SELECT 
                p.*,
                u.login as username,
                u.avatar
            FROM posts p
            JOIN Users u ON p.user = u.id
            ORDER BY p.date_create DESC
            LIMIT ?");
        
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        return $stmt->get_result();
    }
    
    
    // Создание нового поста
    public function create($user_id, $text, $images = []) {
        $stmt = $this->conn->prepare("
            INSERT INTO posts (user, text) 
            VALUES (?, ?)
        ");
        
        $stmt->bind_param("is", $user_id, $text);
        
        if ($stmt->execute()) {
            $post_id = $stmt->insert_id;
            
            // Сохраняем картинки, если есть
            if (!empty($images)) {
                $this->saveImages($post_id, $images);
            }
            
            return $post_id;
        }
        
        return false;
    }
    
    // Сохранение картинок для поста
    private function saveImages($post_id, $images) {
        $stmt = $this->conn->prepare("
            INSERT INTO Image (post, place) 
            VALUES (?, ?)
        ");
        
        foreach ($images as $image_path) {
            $stmt->bind_param("is", $post_id, $image_path);
            $stmt->execute();
        }
        
        $stmt->close();
    }

    // Форматирование времени 
    public static function formatTime($datetime) {
        $time = strtotime($datetime);
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

            //    !!!!! РЕАКЦИИ !!!!!
    
    //реакции кол во штук всего которых
    public function getReactions($post_id){
        $stmt = $this->conn->prepare("
        SELECT 
            (SELECT COUNT(*) FROM likes WHERE post = ?) as hearts,
            (SELECT COUNT(*) FROM comments WHERE post = ?) as coment
        FROM posts p
        WHERE p.id = ?;");
        $stmt->bind_param("iii", $post_id, $post_id, $post_id);
        $stmt->execute();
        return $stmt->get_result();
    }

        //  комменты поста
    public function getComments($post_id) {
        $stmt = $this->conn->prepare("
            SELECT 
                c.id, c.text, c.date_create,
                u.id as user_id, u.login as username, u.avatar
            FROM comments c
            JOIN users u ON c.user = u.id
            WHERE c.post = ?
            ORDER BY c.date_create DESC;
        ");
        
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
        return $stmt->get_result();
    }



public function toggleLike($post_id, $user_id) {
    // Проверяем, существует ли уже лайк
    $check_stmt = $this->conn->prepare("
        SELECT id FROM likes 
        WHERE post = ? AND user = ?
    ");
    $check_stmt->bind_param("ii", $post_id, $user_id);
    $check_stmt->execute();
    $check_stmt->store_result();
    $rr = $check_stmt->num_rows;
    error_log("форма ==== ".$rr);
    
    if ($check_stmt->num_rows > 0) {
        // Удаляем лайк
        $delete_stmt = $this->conn->prepare("
            DELETE FROM likes 
            WHERE post = ? AND user = ?
        ");
        $delete_stmt->bind_param("ii", $post_id, $user_id);
        $success = $delete_stmt->execute();
            logToDatabase('delete', 'like', $post_id);
        $action = 'removed';
        error_log("удален пост ".$post_id." польз ".$user_id);
    } else {
        error_log("добавлена");
        // Добавляем лайк
        $insert_stmt = $this->conn->prepare("
            INSERT INTO likes (post, user) 
            VALUES (?, ?)
        ");
        $insert_stmt->bind_param("ii", $post_id, $user_id);
        $success = $insert_stmt->execute();
            logToDatabase('create', 'like', $post_id);
        $action = 'added';
        error_log("добавлен пост ".$post_id." польз ".$user_id);
    }
    return ['success' => $success, 'action' => $action];
}


            // СОЗДАНИЕ КОМЕНТА
    public function createComment($user_id, $post_id, $comment) {
        $stmt = $this->conn->prepare("
            INSERT INTO comments (`user`, `post`, `text`) 
            VALUES (?,?,?)
        ");
        $stmt->bind_param("iis", $user_id, $post_id, $comment);
        $success = $stmt->execute();
        $action = 'added';
        return ['success' => $success, 'action' => $action];
    }


}
?>