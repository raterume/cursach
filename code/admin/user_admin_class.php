<?php
class UserModel {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function getUsersWithStats($search = '', $role = '', $sort = 'date_desc', $limit = 10, $offset = 0) {
        // Подготовка условий
        $where_conditions = [];
        $params = [];
        $types = '';
        
        if (!empty($search)) {
            $where_conditions[] = "(u.login LIKE ? OR u.email LIKE ?)";
            $search_term = "%{$search}%";
            $params[] = $search_term;
            $params[] = $search_term;
            $types .= 'ss';
        }
        
        if (!empty($role)) {
            $where_conditions[] = "u.role = ?";
            $params[] = $role;
            $types .= 'i';
        }
        
        $where_sql = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Определение сортировки
        $order_by = match($sort) {
            'date_asc' => 'u.date_create ASC',
            'posts_desc' => 'post_count DESC',
            'likes_desc' => 'like_count DESC',
            'followers_desc' => 'follower_count DESC',
            default => 'u.date_create DESC'
        };
        
        // Запрос с использованием JOIN вместо подзапросов для производительности
        $query = "
        SELECT 
            u.id,
            u.login,
            u.email,
            u.avatar,
            u.date_create,
            r.name as role_name,
            COALESCE(p.post_count, 0) as post_count,
            COALESCE(c.comment_count, 0) as comment_count,
            COALESCE(l.like_count, 0) as like_count,
            COALESCE(f1.follower_count, 0) as follower_count,
            COALESCE(f2.following_count, 0) as following_count
        FROM users u
        LEFT JOIN roles r ON u.role = r.id
        LEFT JOIN (
            SELECT user, COUNT(*) as post_count 
            FROM posts 
            GROUP BY user
        ) p ON u.id = p.user
        LEFT JOIN (
            SELECT user, COUNT(*) as comment_count 
            FROM comments 
            GROUP BY user
        ) c ON u.id = c.user
        LEFT JOIN (
            SELECT user, COUNT(*) as like_count 
            FROM likes 
            GROUP BY user
        ) l ON u.id = l.user
        LEFT JOIN (
            SELECT subs as user_id, COUNT(*) as follower_count 
            FROM subscriptions 
            GROUP BY subs
        ) f1 ON u.id = f1.user_id
        LEFT JOIN (
            SELECT user as user_id, COUNT(*) as following_count 
            FROM subscriptions 
            GROUP BY user
        ) f2 ON u.id = f2.user_id
        {$where_sql}
        ORDER BY {$order_by}
        LIMIT ? OFFSET ?
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        $stmt = $this->conn->prepare($query);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        
        return $users;
    }
    
    public function getTotalUsers($search = '', $role = '') {
        $where_conditions = [];
        $params = [];
        $types = '';
        
        if (!empty($search)) {
            $where_conditions[] = "(login LIKE ? OR email LIKE ?)";
            $search_term = "%{$search}%";
            $params[] = $search_term;
            $params[] = $search_term;
            $types .= 'ss';
        }
        
        if (!empty($role)) {
            $where_conditions[] = "role = ?";
            $params[] = $role;
            $types .= 'i';
        }
        
        $where_sql = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $query = "SELECT COUNT(*) as total FROM users {$where_sql}";
        
        $stmt = $this->conn->prepare($query);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['total'] ?? 0;
    }
    
    public function getAllRoles() {
        $query = "SELECT id, name FROM roles ORDER BY name";
        $result = $this->conn->query($query);
        
        $roles = [];
        while ($row = $result->fetch_assoc()) {
            $roles[] = $row;
        }
        
        return $roles;
    }
    
    public function getTotalStatistics() {
        $query = "
        SELECT 
            (SELECT COUNT(*) FROM posts) as total_posts,
            (SELECT COUNT(*) FROM comments) as total_comments,
            (SELECT COUNT(*) FROM likes) as total_likes
        ";
        
        $result = $this->conn->query($query);
        return $result->fetch_assoc();
    }
}?>