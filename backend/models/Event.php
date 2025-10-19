<?php
/**
 * Event Model
 */

require_once __DIR__ . '/../config/database.php';

class Event {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create($userId, $data) {
        $sql = "INSERT INTO events (
            user_id, category_id, title, description, start_datetime, 
            end_datetime, amount, status, recurrence_pattern, reminders, color
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)";
        
        $reminders = isset($data['reminders']) ? json_encode($data['reminders']) : null;
        
        $this->db->query($sql, [
            $userId,
            $data['category_id'],
            $data['title'],
            $data['description'] ?? null,
            $data['start_datetime'],
            $data['end_datetime'] ?? null,
            $data['amount'] ?? null,
            $data['recurrence_pattern'] ?? null,
            $reminders,
            $data['color'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }

    public function getByUser($userId, $filters = []) {
        $sql = "SELECT e.*, c.name as category_name, c.color as category_color, c.icon as category_icon
                FROM events e
                LEFT JOIN categories c ON e.category_id = c.id
                WHERE e.user_id = ?";
        
        $params = [$userId];
        
        if (isset($filters['from'])) {
            $sql .= " AND e.start_datetime >= ?";
            $params[] = $filters['from'];
        }
        
        if (isset($filters['to'])) {
            $sql .= " AND e.start_datetime <= ?";
            $params[] = $filters['to'];
        }
        
        if (isset($filters['category_id'])) {
            $sql .= " AND e.category_id = ?";
            $params[] = $filters['category_id'];
        }
        
        if (isset($filters['status'])) {
            $sql .= " AND e.status = ?";
            $params[] = $filters['status'];
        }
        
        $sql .= " ORDER BY e.start_datetime ASC";
        
        if (isset($filters['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = (int)$filters['limit'];
            
            if (isset($filters['offset'])) {
                $sql .= " OFFSET ?";
                $params[] = (int)$filters['offset'];
            }
        }
        
        $stmt = $this->db->query($sql, $params);
        $events = $stmt->fetchAll();
        
        // Decodifica JSON reminders
        foreach ($events as &$event) {
            $event['reminders'] = json_decode($event['reminders'], true) ?? [];
        }
        
        return $events;
    }

    public function getById($id, $userId) {
        $sql = "SELECT e.*, c.name as category_name, c.color as category_color, c.icon as category_icon
                FROM events e
                LEFT JOIN categories c ON e.category_id = c.id
                WHERE e.id = ? AND e.user_id = ?
                LIMIT 1";
        
        $stmt = $this->db->query($sql, [$id, $userId]);
        $event = $stmt->fetch();
        
        if ($event) {
            $event['reminders'] = json_decode($event['reminders'], true) ?? [];
        }
        
        return $event;
    }

    public function update($id, $userId, $data) {
        $fields = [];
        $params = [];
        
        $allowedFields = ['category_id', 'title', 'description', 'start_datetime', 
                          'end_datetime', 'amount', 'status', 'recurrence_pattern', 'color'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }
        
        if (isset($data['reminders'])) {
            $fields[] = "reminders = ?";
            $params[] = json_encode($data['reminders']);
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $id;
        $params[] = $userId;
        
        $sql = "UPDATE events SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?";
        $stmt = $this->db->query($sql, $params);
        
        return $stmt->rowCount() > 0;
    }

    public function delete($id, $userId) {
        $sql = "DELETE FROM events WHERE id = ? AND user_id = ?";
        $stmt = $this->db->query($sql, [$id, $userId]);
        return $stmt->rowCount() > 0;
    }

    public function toggleStatus($id, $userId) {
        $sql = "UPDATE events SET status = CASE 
                    WHEN status = 'pending' THEN 'completed' 
                    ELSE 'pending' 
                END 
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->db->query($sql, [$id, $userId]);
        return $stmt->rowCount() > 0;
    }

    public function countByUser($userId, $filters = []) {
        $sql = "SELECT COUNT(*) as total FROM events WHERE user_id = ?";
        $params = [$userId];
        
        if (isset($filters['from'])) {
            $sql .= " AND start_datetime >= ?";
            $params[] = $filters['from'];
        }
        
        if (isset($filters['to'])) {
            $sql .= " AND start_datetime <= ?";
            $params[] = $filters['to'];
        }
        
        $stmt = $this->db->query($sql, $params);
        $result = $stmt->fetch();
        return $result['total'];
    }
}
