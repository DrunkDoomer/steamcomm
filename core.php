<?php
// core.php - Основной файл ядра системы

// Настройки ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Конфигурация базы данных
define('DB_HOST', 'localhost');
define('DB_NAME', 'u3118876_1tt');
define('DB_USER', 'u3118876_1t');
define('DB_PASS', 'u3118876_1tt');

// Константы для уровней XP
define('XP_PER_POST', 10);
define('XP_PER_COMMENT', 5);
define('XP_PER_LIKE_RECEIVED', 1);
define('XP_PER_LIKE_GIVEN', 1);

// Инициализация сессии
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Автозагрузчик классов
spl_autoload_register(function ($class_name) {
    include 'classes/' . $class_name . '.class.php';
});

// Подключение к базе данных
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => false,
                    PDO::ATTR_TIMEOUT => 5
                ]
            );
            
            // Проверка соединения
            $this->connection->query("SELECT 1");
            
        } catch (PDOException $e) {
            // Логирование ошибки
            error_log("Database connection failed: " . $e->getMessage());
            
            // Пользовательское сообщение
            die("Ошибка подключения к базе данных. Пожалуйста, попробуйте позже или обратитесь к администратору.");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        // Проверяем активность соединения
        try {
            $this->connection->query("SELECT 1");
        } catch (PDOException $e) {
            // Пытаемся переподключиться
            self::$instance = new self();
        }
        
        return $this->connection;
    }
    
    // Запрещаем клонирование и десериализацию
    private function __clone() {}
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Базовый класс модели
abstract class Model {
    protected $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
}

// Модель пользователя
class User extends Model {
    public function register($username, $email, $password) {
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $password_hash]);
        
        $user_id = $this->db->lastInsertId();
        
        // Создаем профиль по умолчанию
        $this->createProfile($user_id);
        
        return $user_id;
    }
    
    private function createProfile($user_id) {
        $stmt = $this->db->prepare("INSERT INTO user_profiles (user_id) VALUES (?)");
        $stmt->execute([$user_id]);
    }
    
    public function login($username, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Обновляем время последнего входа
            $this->updateLastLogin($user['id']);
            
            return $user;
        }
        
        return false;
    }
    
    private function updateLastLogin($user_id) {
        $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user_id]);
    }
    
    public function getUserById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getUserByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }
    
    public function getUserProfile($user_id) {
        $stmt = $this->db->prepare("
            SELECT u.*, up.*, l.level_name, l.badge_url 
            FROM users u
            JOIN user_profiles up ON u.id = up.user_id
            LEFT JOIN levels l ON up.level_id = l.id
            WHERE u.id = ?
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    }
    
    public function updateProfile($user_id, $data) {
        $stmt = $this->db->prepare("
            UPDATE user_profiles 
            SET avatar_url = ?, bio = ?, location = ?, website = ?, dark_theme = ?
            WHERE user_id = ?
        ");
        $stmt->execute([
            $data['avatar_url'] ?? null,
            $data['bio'] ?? null,
            $data['location'] ?? null,
            $data['website'] ?? null,
            $data['dark_theme'] ?? true,
            $user_id
        ]);
    }
    
    public function addXP($user_id, $xp) {
        $stmt = $this->db->prepare("UPDATE user_profiles SET xp = xp + ? WHERE user_id = ?");
        $stmt->execute([$xp, $user_id]);
        
        // Проверяем и обновляем уровень
        $this->checkLevelUp($user_id);
    }
    
    private function checkLevelUp($user_id) {
        $profile = $this->getUserProfile($user_id);
        $current_xp = $profile['xp'];
        $current_level = $profile['level_id'];
        
        $stmt = $this->db->prepare("SELECT id FROM levels WHERE min_xp > ? ORDER BY min_xp ASC LIMIT 1");
        $stmt->execute([$current_xp]);
        $next_level = $stmt->fetch();
        
        if ($next_level && $next_level['id'] > $current_level) {
            $stmt = $this->db->prepare("UPDATE user_profiles SET level_id = ? WHERE user_id = ?");
            $stmt->execute([$next_level['id'], $user_id]);
        }
    }
    
    public function searchUsers($query) {
        $query = "%$query%";
        $stmt = $this->db->prepare("
            SELECT u.id, u.username, up.avatar_url 
            FROM users u
            JOIN user_profiles up ON u.id = up.user_id
            WHERE u.username LIKE ? OR up.bio LIKE ?
            LIMIT 20
        ");
        $stmt->execute([$query, $query]);
        return $stmt->fetchAll();
    }
}

// Модель постов
class Post extends Model {
    public function createPost($user_id, $content, $visibility = 'public', $parent_post_id = null) {
        $stmt = $this->db->prepare("
            INSERT INTO posts (user_id, content, visibility, parent_post_id)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $content, $visibility, $parent_post_id]);
        
        $post_id = $this->db->lastInsertId();
        
        // Начисляем XP за создание поста
        $userModel = new User();
        $userModel->addXP($user_id, XP_PER_POST);
        
        return $post_id;
    }
    
    public function getPostById($post_id) {
        $stmt = $this->db->prepare("
            SELECT p.*, u.username, up.avatar_url, up.level_id
            FROM posts p
            JOIN users u ON p.user_id = u.id
            JOIN user_profiles up ON u.id = up.user_id
            WHERE p.id = ?
        ");
        $stmt->execute([$post_id]);
        return $stmt->fetch();
    }
    
    public function getPostsByUser($user_id, $current_user_id = null, $limit = 20, $offset = 0) {
        $visibility_condition = $current_user_id == $user_id ? "" : "AND (p.visibility = 'public' OR (p.visibility = 'followers' AND EXISTS (
            SELECT 1 FROM followers WHERE follower_id = ? AND followed_id = p.user_id
        )))";
        
        $params = [$user_id];
        if ($current_user_id != $user_id && strpos($visibility_condition, '?') !== false) {
            $params[] = $current_user_id;
        }
        
        $params = array_merge($params, [$limit, $offset]);
        
        $stmt = $this->db->prepare("
            SELECT p.*, u.username, up.avatar_url, up.level_id,
            (SELECT COUNT(*) FROM likes WHERE target_type = 'post' AND target_id = p.id) as like_count,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
            (SELECT COUNT(*) FROM likes WHERE target_type = 'post' AND target_id = p.id AND user_id = ?) as is_liked
            FROM posts p
            JOIN users u ON p.user_id = u.id
            JOIN user_profiles up ON u.id = up.user_id
            WHERE p.user_id = ? {$visibility_condition}
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ");
        
        array_unshift($params, $current_user_id);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getFeed($user_id, $limit = 20, $offset = 0) {
        $stmt = $this->db->prepare("
            SELECT p.*, u.username, up.avatar_url, up.level_id,
            (SELECT COUNT(*) FROM likes WHERE target_type = 'post' AND target_id = p.id) as like_count,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
            (SELECT COUNT(*) FROM likes WHERE target_type = 'post' AND target_id = p.id AND user_id = ?) as is_liked
            FROM posts p
            JOIN users u ON p.user_id = u.id
            JOIN user_profiles up ON u.id = up.user_id
            WHERE p.visibility = 'public' OR p.user_id = ? OR EXISTS (
                SELECT 1 FROM followers 
                WHERE follower_id = ? AND followed_id = p.user_id
            )
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$user_id, $user_id, $user_id, $limit, $offset]);
        return $stmt->fetchAll();
    }
    
    public function deletePost($post_id, $user_id) {
        // Проверяем, принадлежит ли пост пользователю
        $post = $this->getPostById($post_id);
        if (!$post || $post['user_id'] != $user_id) {
            return false;
        }
        
        $stmt = $this->db->prepare("DELETE FROM posts WHERE id = ?");
        return $stmt->execute([$post_id]);
    }
    
    public function addMediaToPost($post_id, $media_url, $media_type, $thumbnail_url = null, $description = '') {
        $stmt = $this->db->prepare("
            INSERT INTO media (post_id, media_url, media_type, thumbnail_url, description)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$post_id, $media_url, $media_type, $thumbnail_url, $description]);
        return $this->db->lastInsertId();
    }
    
    public function getPostMedia($post_id) {
        $stmt = $this->db->prepare("SELECT * FROM media WHERE post_id = ? ORDER BY sort_order");
        $stmt->execute([$post_id]);
        return $stmt->fetchAll();
    }
    
    public function getPostsByTag($tag_name, $user_id = null, $limit = 20, $offset = 0) {
        $stmt = $this->db->prepare("
            SELECT p.*, u.username, up.avatar_url, up.level_id,
            (SELECT COUNT(*) FROM likes WHERE target_type = 'post' AND target_id = p.id) as like_count,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
            (SELECT COUNT(*) FROM likes WHERE target_type = 'post' AND target_id = p.id AND user_id = ?) as is_liked
            FROM posts p
            JOIN users u ON p.user_id = u.id
            JOIN user_profiles up ON u.id = up.user_id
            JOIN post_tags pt ON p.id = pt.post_id
            JOIN tags t ON pt.tag_id = t.id
            WHERE t.tag_name = ? AND (p.visibility = 'public' OR p.user_id = ? OR EXISTS (
                SELECT 1 FROM followers 
                WHERE follower_id = ? AND followed_id = p.user_id
            ))
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$user_id, $tag_name, $user_id, $user_id, $limit, $offset]);
        return $stmt->fetchAll();
    }
}

// Модель комментариев
class Comment extends Model {
    public function addComment($user_id, $post_id, $content, $parent_comment_id = null) {
        $stmt = $this->db->prepare("
            INSERT INTO comments (user_id, post_id, content, parent_comment_id)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $post_id, $content, $parent_comment_id]);
        
        $comment_id = $this->db->lastInsertId();
        
        // Начисляем XP за комментарий
        $userModel = new User();
        $userModel->addXP($user_id, XP_PER_COMMENT);
        
        // Создаем уведомление для автора поста
        $this->createCommentNotification($user_id, $post_id, $comment_id);
        
        return $comment_id;
    }
    
    private function createCommentNotification($user_id, $post_id, $comment_id) {
        $post = (new Post())->getPostById($post_id);
        if ($post && $post['user_id'] != $user_id) {
            $stmt = $this->db->prepare("
                INSERT INTO notifications (user_id, type, source_user_id, target_id)
                VALUES (?, 'comment', ?, ?)
            ");
            $stmt->execute([$post['user_id'], $user_id, $comment_id]);
        }
    }
    
    public function getCommentsByPost($post_id, $user_id = null) {
        $stmt = $this->db->prepare("
            SELECT c.*, u.username, up.avatar_url, up.level_id,
            (SELECT COUNT(*) FROM likes WHERE target_type = 'comment' AND target_id = c.id) as like_count,
            (SELECT COUNT(*) FROM likes WHERE target_type = 'comment' AND target_id = c.id AND user_id = ?) as is_liked
            FROM comments c
            JOIN users u ON c.user_id = u.id
            JOIN user_profiles up ON u.id = up.user_id
            WHERE c.post_id = ?
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([$user_id, $post_id]);
        return $stmt->fetchAll();
    }
    
    public function getCommentById($comment_id) {
        $stmt = $this->db->prepare("
            SELECT c.*, u.username, up.avatar_url
            FROM comments c
            JOIN users u ON c.user_id = u.id
            JOIN user_profiles up ON u.id = up.user_id
            WHERE c.id = ?
        ");
        $stmt->execute([$comment_id]);
        return $stmt->fetch();
    }
    
    public function deleteComment($comment_id, $user_id) {
        // Проверяем, принадлежит ли комментарий пользователю
        $comment = $this->getCommentById($comment_id);
        if (!$comment || $comment['user_id'] != $user_id) {
            return false;
        }
        
        $stmt = $this->db->prepare("DELETE FROM comments WHERE id = ?");
        return $stmt->execute([$comment_id]);
    }
}

// Модель лайков
class Like extends Model {
    public function addLike($user_id, $target_type, $target_id) {
        // Проверяем, не поставил ли пользователь уже лайк
        if ($this->hasLike($user_id, $target_type, $target_id)) {
            return false;
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO likes (user_id, target_type, target_id)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$user_id, $target_type, $target_id]);
        
        // Начисляем XP за лайк
        $userModel = new User();
        $userModel->addXP($user_id, XP_PER_LIKE_GIVEN);
        
        // Создаем уведомление для получателя
        $this->createLikeNotification($user_id, $target_type, $target_id);
        
        return true;
    }
    
    private function createLikeNotification($user_id, $target_type, $target_id) {
        $target_owner_id = null;
        
        if ($target_type === 'post') {
            $post = (new Post())->getPostById($target_id);
            $target_owner_id = $post['user_id'];
        } elseif ($target_type === 'comment') {
            $comment = (new Comment())->getCommentById($target_id);
            $target_owner_id = $comment['user_id'];
        }
        
        if ($target_owner_id && $target_owner_id != $user_id) {
            $stmt = $this->db->prepare("
                INSERT INTO notifications (user_id, type, source_user_id, target_id)
                VALUES (?, 'like', ?, ?)
            ");
            $stmt->execute([$target_owner_id, $user_id, $target_id]);
            
            // Начисляем XP получателю лайка
            $userModel = new User();
            $userModel->addXP($target_owner_id, XP_PER_LIKE_RECEIVED);
        }
    }
    
    public function removeLike($user_id, $target_type, $target_id) {
        $stmt = $this->db->prepare("
            DELETE FROM likes 
            WHERE user_id = ? AND target_type = ? AND target_id = ?
        ");
        return $stmt->execute([$user_id, $target_type, $target_id]);
    }
    
    public function hasLike($user_id, $target_type, $target_id) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM likes 
            WHERE user_id = ? AND target_type = ? AND target_id = ?
        ");
        $stmt->execute([$user_id, $target_type, $target_id]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }
    
    public function getLikeCount($target_type, $target_id) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM likes 
            WHERE target_type = ? AND target_id = ?
        ");
        $stmt->execute([$target_type, $target_id]);
        $result = $stmt->fetch();
        return $result['count'];
    }
}

// Модель подписок
class Follower extends Model {
    public function follow($follower_id, $followed_id) {
        if ($follower_id == $followed_id) {
            return false; // Нельзя подписаться на себя
        }
        
        // Проверяем, не подписан ли уже
        if ($this->isFollowing($follower_id, $followed_id)) {
            return false;
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO followers (follower_id, followed_id)
            VALUES (?, ?)
        ");
        $stmt->execute([$follower_id, $followed_id]);
        
        // Создаем уведомление
        $this->createFollowNotification($follower_id, $followed_id);
        
        return true;
    }
    
    private function createFollowNotification($follower_id, $followed_id) {
        $stmt = $this->db->prepare("
            INSERT INTO notifications (user_id, type, source_user_id)
            VALUES (?, 'follow', ?)
        ");
        $stmt->execute([$followed_id, $follower_id]);
    }
    
    public function unfollow($follower_id, $followed_id) {
        $stmt = $this->db->prepare("
            DELETE FROM followers 
            WHERE follower_id = ? AND followed_id = ?
        ");
        return $stmt->execute([$follower_id, $followed_id]);
    }
    
    public function isFollowing($follower_id, $followed_id) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM followers 
            WHERE follower_id = ? AND followed_id = ?
        ");
        $stmt->execute([$follower_id, $followed_id]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }
    
    public function getFollowers($user_id, $limit = 20, $offset = 0) {
        $stmt = $this->db->prepare("
            SELECT u.id, u.username, up.avatar_url
            FROM followers f
            JOIN users u ON f.follower_id = u.id
            JOIN user_profiles up ON u.id = up.user_id
            WHERE f.followed_id = ?
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$user_id, $limit, $offset]);
        return $stmt->fetchAll();
    }
    
    public function getFollowing($user_id, $limit = 20, $offset = 0) {
        $stmt = $this->db->prepare("
            SELECT u.id, u.username, up.avatar_url
            FROM followers f
            JOIN users u ON f.followed_id = u.id
            JOIN user_profiles up ON u.id = up.user_id
            WHERE f.follower_id = ?
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$user_id, $limit, $offset]);
        return $stmt->fetchAll();
    }
    
    public function getFollowCounts($user_id) {
        $stmt = $this->db->prepare("
            SELECT 
                (SELECT COUNT(*) FROM followers WHERE followed_id = ?) as followers_count,
                (SELECT COUNT(*) FROM followers WHERE follower_id = ?) as following_count
        ");
        $stmt->execute([$user_id, $user_id]);
        return $stmt->fetch();
    }
}

// Модель уведомлений
class Notification extends Model {
    public function getUserNotifications($user_id, $limit = 20, $offset = 0) {
        $stmt = $this->db->prepare("
            SELECT n.*, u.username, up.avatar_url,
            CASE 
                WHEN n.type = 'like' AND n.target_id IS NOT NULL THEN 
                    CASE 
                        WHEN (SELECT target_type FROM likes WHERE id = n.target_id) = 'post' THEN
                            (SELECT content FROM posts WHERE id = (SELECT target_id FROM likes WHERE id = n.target_id))
                        WHEN (SELECT target_type FROM likes WHERE id = n.target_id) = 'comment' THEN
                            (SELECT content FROM comments WHERE id = (SELECT target_id FROM likes WHERE id = n.target_id))
                    END
                WHEN n.type = 'comment' THEN (SELECT content FROM comments WHERE id = n.target_id)
                ELSE NULL
            END as content_preview
            FROM notifications n
            LEFT JOIN users u ON n.source_user_id = u.id
            LEFT JOIN user_profiles up ON u.id = up.user_id
            WHERE n.user_id = ?
            ORDER BY n.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$user_id, $limit, $offset]);
        return $stmt->fetchAll();
    }
    
    public function markAsRead($notification_id, $user_id) {
        $stmt = $this->db->prepare("
            UPDATE notifications 
            SET is_read = TRUE 
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([$notification_id, $user_id]);
    }
    
    public function markAllAsRead($user_id) {
        $stmt = $this->db->prepare("
            UPDATE notifications 
            SET is_read = TRUE 
            WHERE user_id = ? AND is_read = FALSE
        ");
        return $stmt->execute([$user_id]);
    }
    
    public function getUnreadCount($user_id) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM notifications 
            WHERE user_id = ? AND is_read = FALSE
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result['count'];
    }
}

// Модель тегов
class Tag extends Model {
    public function getOrCreateTag($tag_name) {
        // Проверяем, существует ли тег
        $stmt = $this->db->prepare("SELECT id FROM tags WHERE tag_name = ?");
        $stmt->execute([$tag_name]);
        $tag = $stmt->fetch();
        
        if ($tag) {
            return $tag['id'];
        }
        
        // Создаем новый тег
        $stmt = $this->db->prepare("INSERT INTO tags (tag_name) VALUES (?)");
        $stmt->execute([$tag_name]);
        return $this->db->lastInsertId();
    }
    
    public function addTagsToPost($post_id, $tags) {
        foreach ($tags as $tag_name) {
            $tag_name = trim($tag_name);
            if (empty($tag_name)) continue;
            
            $tag_id = $this->getOrCreateTag($tag_name);
            
            // Проверяем, не добавлен ли уже тег к посту
            $stmt = $this->db->prepare("SELECT 1 FROM post_tags WHERE post_id = ? AND tag_id = ?");
            $stmt->execute([$post_id, $tag_id]);
            if ($stmt->fetch()) continue;
            
            // Добавляем связь тега с постом
            $stmt = $this->db->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)");
            $stmt->execute([$post_id, $tag_id]);
        }
    }
    
    public function getPostTags($post_id) {
        $stmt = $this->db->prepare("
            SELECT t.tag_name 
            FROM post_tags pt
            JOIN tags t ON pt.tag_id = t.id
            WHERE pt.post_id = ?
        ");
        $stmt->execute([$post_id]);
        return $stmt->fetchAll();
    }
    
    public function getPopularTags($limit = 10) {
        $stmt = $this->db->prepare("
            SELECT t.tag_name, COUNT(pt.post_id) as post_count
            FROM tags t
            JOIN post_tags pt ON t.id = pt.tag_id
            GROUP BY t.id
            ORDER BY post_count DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}

// Модель игровых платформ
class GamePlatform extends Model {
    public function getPlatforms() {
        $stmt = $this->db->prepare("SELECT * FROM game_platforms");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getUserGameAccounts($user_id) {
        $stmt = $this->db->prepare("
            SELECT uga.*, gp.name as platform_name
            FROM user_game_accounts uga
            JOIN game_platforms gp ON uga.platform_id = gp.id
            WHERE uga.user_id = ? AND uga.is_public = TRUE
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }
    
    public function linkGameAccount($user_id, $platform_id, $platform_user_id, $profile_url = null) {
        // Проверяем, не привязан ли уже этот аккаунт
        $stmt = $this->db->prepare("
            SELECT 1 
            FROM user_game_accounts 
            WHERE platform_id = ? AND platform_user_id = ?
        ");
        $stmt->execute([$platform_id, $platform_user_id]);
        if ($stmt->fetch()) {
            return false;
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO user_game_accounts (user_id, platform_id, platform_user_id, profile_url)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                platform_user_id = VALUES(platform_user_id),
                profile_url = VALUES(profile_url),
                last_sync = NOW()
        ");
        return $stmt->execute([$user_id, $platform_id, $platform_user_id, $profile_url]);
    }
    
    public function unlinkGameAccount($account_id, $user_id) {
        $stmt = $this->db->prepare("
            DELETE FROM user_game_accounts 
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([$account_id, $user_id]);
    }
    
    public function setAccountVisibility($account_id, $user_id, $is_public) {
        $stmt = $this->db->prepare("
            UPDATE user_game_accounts 
            SET is_public = ?
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([$is_public, $account_id, $user_id]);
    }
}

// Вспомогательные функции
class Helper {
    public static function sanitize($input) {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $input[$key] = self::sanitize($value);
            }
            return $input;
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    public static function validateUsername($username) {
        return preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username);
    }
    
    public static function extractHashtags($text) {
        preg_match_all('/#(\w+)/', $text, $matches);
        return array_unique($matches[1]);
    }
    
    public static function formatDate($datetime) {
        $now = new DateTime();
        $date = new DateTime($datetime);
        $diff = $now->diff($date);
        
        if ($diff->y > 0) return $diff->y . 'y';
        if ($diff->m > 0) return $diff->m . 'mo';
        if ($diff->d > 0) return $diff->d . 'd';
        if ($diff->h > 0) return $diff->h . 'h';
        if ($diff->i > 0) return $diff->i . 'm';
        return $diff->s . 's';
    }
    
    public static function isImage($file_type) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        return in_array($file_type, $allowed);
    }
    
    public static function isVideo($file_type) {
        $allowed = ['video/mp4', 'video/webm', 'video/ogg'];
        return in_array($file_type, $allowed);
    }
    
    public static function uploadMedia($file, $upload_dir = 'uploads/') {
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = uniqid() . '_' . basename($file['name']);
        $target_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            return $target_path;
        }
        
        return false;
    }
}


// Модель для работы с API
class Api extends Model {
    public function handleLike($user_id, $post_id, $action) {
        try {
            $like = new Like();
            
            if ($action === 'like') {
                $success = $like->addLike($user_id, 'post', $post_id);
            } else {
                $success = $like->removeLike($user_id, 'post', $post_id);
            }
            
            if ($success) {
                $count = $like->getLikeCount('post', $post_id);
                return ['success' => true, 'new_count' => $count];
            }
            
            return ['success' => false];
        } catch (Exception $e) {
            error_log("Like error: " . $e->getMessage());
            return ['success' => false];
        }
    }
    
    public function handleDeletePost($user_id, $post_id) {
        try {
            $post = new Post();
            $success = $post->deletePost($post_id, $user_id);
            return ['success' => $success];
        } catch (Exception $e) {
            error_log("Delete post error: " . $e->getMessage());
            return ['success' => false];
        }
    }
    
    public function uploadMedia($user_id, $file) {
        try {
            $upload_dir = 'uploads/' . date('Y/m/d') . '/';
            $file_path = $helper->uploadMedia($file, $upload_dir);
            
            if ($file_path) {
                return [
                    'success' => true,
                    'file_path' => $file_path,
                    'file_type' => strpos($file['type'], 'image/') === 0 ? 'image' : 'video'
                ];
            }
            
            return ['success' => false];
        } catch (Exception $e) {
            error_log("Upload error: " . $e->getMessage());
            return ['success' => false];
        }
    }
    
    public function getPostComments($post_id, $page = 1, $per_page = 10) {
        try {
            $offset = ($page - 1) * $per_page;
            $comment = new Comment();
            $comments = $comment->getCommentsByPost($post_id, $_SESSION['user_id'] ?? null, $per_page, $offset);
            
            return [
                'success' => true,
                'comments' => $comments,
                'has_more' => count($comments) >= $per_page
            ];
        } catch (Exception $e) {
            error_log("Get comments error: " . $e->getMessage());
            return ['success' => false];
        }
    }
    
    public function addComment($user_id, $post_id, $content) {
        try {
            $comment = new Comment();
            $comment_id = $comment->addComment($user_id, $post_id, $content);
            
            return [
                'success' => (bool)$comment_id,
                'comment_id' => $comment_id
            ];
        } catch (Exception $e) {
            error_log("Add comment error: " . $e->getMessage());
            return ['success' => false];
        }
    }
}

// Инициализация основных классов
$user = new User();
$post = new Post();
$comment = new Comment();
$like = new Like();
$follower = new Follower();
$notification = new Notification();
$tag = new Tag();
$gamePlatform = new GamePlatform();
$helper = new Helper();
$api = new Api();