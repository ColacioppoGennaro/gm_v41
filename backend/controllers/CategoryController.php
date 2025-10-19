<?php
/**
 * Category Controller
 */

require_once __DIR__ . '/../models/Category.php';

class CategoryController {
    
    public static function index() {
        global $currentUser;
        
        $categoryModel = new Category();
        $categories = $categoryModel->getByUser($currentUser['id']);
        
        echo json_encode([
            'success' => true,
            'data' => $categories
        ]);
    }
    
    public static function create() {
        global $currentUser;
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['name']) || empty($data['color']) || empty($data['icon'])) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Nome, colore e icona sono obbligatori'
                ]
            ]);
            return;
        }
        
        $categoryModel = new Category();
        $categoryId = $categoryModel->create(
            $currentUser['id'],
            $data['name'],
            $data['color'],
            $data['icon']
        );
        
        $category = $categoryModel->getById($categoryId, $currentUser['id']);
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'data' => $category
        ]);
    }
    
    public static function update($id) {
        global $currentUser;
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $categoryModel = new Category();
        $success = $categoryModel->update(
            $id,
            $currentUser['id'],
            $data['name'] ?? null,
            $data['color'] ?? null,
            $data['icon'] ?? null
        );
        
        if (!$success) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => [
                    'code' => 'UPDATE_FAILED',
                    'message' => 'Impossibile aggiornare la categoria'
                ]
            ]);
            return;
        }
        
        $category = $categoryModel->getById($id, $currentUser['id']);
        
        echo json_encode([
            'success' => true,
            'data' => $category
        ]);
    }
    
    public static function delete($id) {
        global $currentUser;
        
        $categoryModel = new Category();
        $success = $categoryModel->delete($id, $currentUser['id']);
        
        if (!$success) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => [
                    'code' => 'DELETE_FAILED',
                    'message' => 'Impossibile eliminare categoria con eventi associati o categoria default'
                ]
            ]);
            return;
        }
        
        http_response_code(204);
    }
}
