<?php
/**
 * Event Controller
 */

require_once __DIR__ . '/../models/Event.php';

class EventController {
    
    public static function index() {
        global $currentUser;
        
        $filters = [];
        
        if (isset($_GET['from'])) {
            $filters['from'] = $_GET['from'];
        }
        if (isset($_GET['to'])) {
            $filters['to'] = $_GET['to'];
        }
        if (isset($_GET['category_id'])) {
            $filters['category_id'] = $_GET['category_id'];
        }
        if (isset($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        if (isset($_GET['limit'])) {
            $filters['limit'] = min((int)$_GET['limit'], 200);
        } else {
            $filters['limit'] = 50;
        }
        if (isset($_GET['offset'])) {
            $filters['offset'] = (int)$_GET['offset'];
        }
        
        $eventModel = new Event();
        $events = $eventModel->getByUser($currentUser['id'], $filters);
        $total = $eventModel->countByUser($currentUser['id'], $filters);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'events' => $events,
                'pagination' => [
                    'total' => $total,
                    'limit' => $filters['limit'],
                    'offset' => $filters['offset'] ?? 0,
                    'has_more' => $total > ($filters['limit'] + ($filters['offset'] ?? 0))
                ]
            ]
        ]);
    }
    
    public static function show($id) {
        global $currentUser;
        
        $eventModel = new Event();
        $event = $eventModel->getById($id, $currentUser['id']);
        
        if (!$event) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => [
                    'code' => 'EVENT_NOT_FOUND',
                    'message' => 'Evento non trovato'
                ]
            ]);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $event
        ]);
    }
    
    public static function create() {
        global $currentUser;
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validazione
        if (empty($data['title']) || empty($data['start_datetime']) || empty($data['category_id'])) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Titolo, data inizio e categoria sono obbligatori'
                ]
            ]);
            return;
        }
        
        // Verifica limite eventi FREE
        if ($currentUser['plan'] === 'free') {
            $eventModel = new Event();
            $thisMonth = date('Y-m-01 00:00:00');
            $nextMonth = date('Y-m-01 00:00:00', strtotime('+1 month'));
            $count = $eventModel->countByUser($currentUser['id'], [
                'from' => $thisMonth,
                'to' => $nextMonth
            ]);
            
            if ($count >= 50) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'error' => [
                        'code' => 'EVENT_LIMIT_REACHED',
                        'message' => 'Limite di 50 eventi/mese raggiunto. Upgrade a PRO per eventi illimitati.'
                    ]
                ]);
                return;
            }
        }
        
        $eventModel = new Event();
        $eventId = $eventModel->create($currentUser['id'], $data);
        
        $event = $eventModel->getById($eventId, $currentUser['id']);
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'data' => $event
        ]);
    }
    
    public static function update($id) {
        global $currentUser;
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $eventModel = new Event();
        
        // Verifica proprietà
        $event = $eventModel->getById($id, $currentUser['id']);
        if (!$event) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => [
                    'code' => 'EVENT_NOT_FOUND',
                    'message' => 'Evento non trovato'
                ]
            ]);
            return;
        }
        
        $success = $eventModel->update($id, $currentUser['id'], $data);
        
        if (!$success) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => [
                    'code' => 'UPDATE_FAILED',
                    'message' => 'Errore durante l\'aggiornamento'
                ]
            ]);
            return;
        }
        
        $event = $eventModel->getById($id, $currentUser['id']);
        
        echo json_encode([
            'success' => true,
            'data' => $event
        ]);
    }
    
    public static function delete($id) {
        global $currentUser;
        
        $eventModel = new Event();
        $success = $eventModel->delete($id, $currentUser['id']);
        
        if (!$success) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => [
                    'code' => 'EVENT_NOT_FOUND',
                    'message' => 'Evento non trovato'
                ]
            ]);
            return;
        }
        
        http_response_code(204);
    }
    
    public static function toggleStatus($id) {
        global $currentUser;
        
        $eventModel = new Event();
        $success = $eventModel->toggleStatus($id, $currentUser['id']);
        
        if (!$success) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => [
                    'code' => 'EVENT_NOT_FOUND',
                    'message' => 'Evento non trovato'
                ]
            ]);
            return;
        }
        
        $event = $eventModel->getById($id, $currentUser['id']);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $event['id'],
                'status' => $event['status']
            ]
        ]);
    }
}
