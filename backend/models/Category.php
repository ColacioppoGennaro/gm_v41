<?php
/**
 * Category Model
 */

require_once __DIR__ . '/../config/database.php';

class Category {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create($userId, $name, $color, $icon, $isDefault = false) {
        $sql = "INSERT INTO categories (user_id, name, color, icon, is_default) 
                VALUES (?, ?, ?, ?, ?)";
        
        $this->db->query($sql, [$userId, $name, $color, $icon, $isDefault]);
        return $this->db->lastInsertId();
    }

    public function getByUser($userId) {
        $sql = "SELECT * FROM categories WHERE user_id = ? ORDER BY is_default DESC, name ASC";
        $stmt = $this->db->query($sql, [$userId]);
        return $stmt->fetchAll();
    }

    public function getById($id, $userId) {
        $sql = "SELECT * FROM categories WHERE id = ? AND user_id = ? LIMIT 1";
        $stmt = $this->db->query($sql, [$id, $userId]);
        return $stmt->fetch();
    }

    public function update($id, $userId, $name, $color, $icon) {
        $sql = "UPDATE categories SET name = ?, color = ?, icon = ? 
                WHERE id = ? AND user_id = ? AND is_default = 0";
        
        $stmt = $this->db->query($sql, [$name, $color, $icon, $id, $userId]);
        return $stmt->rowCount() > 0;
    }

    public function delete($id, $userId) {
        // Verifica che non ci siano eventi associati
        $checkSql = "SELECT COUNT(*) as count FROM events WHERE category_id = ?";
        $stmt = $this->db->query($checkSql, [$id]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            return false; // Non può eliminare categoria con eventi
        }

        $sql = "DELETE FROM categories WHERE id = ? AND user_id = ? AND is_default = 0";
        $stmt = $this->db->query($sql, [$id, $userId]);
        return $stmt->rowCount() > 0;
    }
}
