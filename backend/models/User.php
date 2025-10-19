<?php
/**
 * User Model
 */

require_once __DIR__ . '/../config/database.php';

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create($email, $password) {
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        
        $sql = "INSERT INTO users (email, password_hash, plan, ai_queries_reset_at) 
                VALUES (?, ?, 'free', NOW())";
        
        try {
            $this->db->query($sql, [$email, $passwordHash]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                return false;
            }
            throw $e;
        }
    }

    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
        $stmt = $this->db->query($sql, [$email]);
        return $stmt->fetch();
    }

    public function findById($id) {
        $sql = "SELECT * FROM users WHERE id = ? LIMIT 1";
        $stmt = $this->db->query($sql, [$id]);
        return $stmt->fetch();
    }

    public function verifyPassword($email, $password) {
        $user = $this->findByEmail($email);
        
        if (!$user) {
            return false;
        }

        if (password_verify($password, $user['password_hash'])) {
            // Aggiorna last_login
            $this->updateLastLogin($user['id']);
            return $user;
        }

        return false;
    }

    public function updateLastLogin($userId) {
        $sql = "UPDATE users SET last_login_at = NOW() WHERE id = ?";
        $this->db->query($sql, [$userId]);
    }

    public function incrementAiQueries($userId) {
        $sql = "UPDATE users SET ai_queries_count = ai_queries_count + 1 WHERE id = ?";
        $this->db->query($sql, [$userId]);
    }

    public function checkAiQuota($userId) {
        $user = $this->findById($userId);
        
        if (!$user) {
            return false;
        }

        $limit = $user['plan'] === 'pro' ? 500 : 20;
        
        // Reset se è passato un mese
        $resetDate = strtotime($user['ai_queries_reset_at']);
        $oneMonthAgo = strtotime('-1 month');
        
        if ($resetDate < $oneMonthAgo) {
            $this->resetAiQueries($userId);
            return true;
        }

        return $user['ai_queries_count'] < $limit;
    }

    public function resetAiQueries($userId) {
        $sql = "UPDATE users SET ai_queries_count = 0, ai_queries_reset_at = NOW() WHERE id = ?";
        $this->db->query($sql, [$userId]);
    }

    public function updateStorage($userId, $bytesAdded) {
        $sql = "UPDATE users SET storage_used_bytes = storage_used_bytes + ? WHERE id = ?";
        $this->db->query($sql, [$bytesAdded, $userId]);
    }

    public function checkStorageQuota($userId, $fileSize) {
        $user = $this->findById($userId);
        
        if (!$user) {
            return false;
        }

        $limit = $user['plan'] === 'pro' ? 524288000 : 10485760; // 500MB : 10MB
        
        return ($user['storage_used_bytes'] + $fileSize) <= $limit;
    }

    public function createDefaultCategories($userId) {
        require_once __DIR__ . '/Category.php';
        $categoryModel = new Category();
        
        $defaults = [
            ['Lavoro', '#3B82F6', '💼'],
            ['Famiglia', '#10B981', '👨‍👩‍👧‍👦'],
            ['Personale', '#8B5CF6', '🧘'],
            ['Altro', '#6B7280', '📌']
        ];

        foreach ($defaults as $cat) {
            $categoryModel->create($userId, $cat[0], $cat[1], $cat[2], true);
        }
    }
}
