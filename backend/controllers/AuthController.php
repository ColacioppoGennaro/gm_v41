<?php
/**
 * Auth Controller
 */

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/jwt.php';

class AuthController {
    
    public static function register() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validazione
        if (empty($data['email']) || empty($data['password']) || empty($data['password_confirm'])) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Email e password sono obbligatori'
                ]
            ]);
            return;
        }
        
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_EMAIL',
                    'message' => 'Email non valida'
                ]
            ]);
            return;
        }
        
        if (strlen($data['password']) < 8) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'error' => [
                    'code' => 'WEAK_PASSWORD',
                    'message' => 'La password deve contenere almeno 8 caratteri'
                ]
            ]);
            return;
        }
        
        if ($data['password'] !== $data['password_confirm']) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'error' => [
                    'code' => 'PASSWORD_MISMATCH',
                    'message' => 'Le password non corrispondono'
                ]
            ]);
            return;
        }
        
        $userModel = new User();
        
        // Verifica email già esistente
        if ($userModel->findByEmail($data['email'])) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'error' => [
                    'code' => 'EMAIL_EXISTS',
                    'message' => 'Email già registrata'
                ]
            ]);
            return;
        }
        
        $userId = $userModel->create($data['email'], $data['password']);
        
        if (!$userId) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => [
                    'code' => 'REGISTRATION_FAILED',
                    'message' => 'Errore durante la registrazione'
                ]
            ]);
            return;
        }
        
        // Crea categorie default
        $userModel->createDefaultCategories($userId);
        
        $user = $userModel->findById($userId);
        $token = JWT::createToken($userId, $user['email'], $user['plan']);
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'plan' => $user['plan'],
                    'created_at' => $user['created_at']
                ],
                'token' => $token
            ]
        ]);
    }
    
    public static function login() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['email']) || empty($data['password'])) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Email e password sono obbligatori'
                ]
            ]);
            return;
        }
        
        $userModel = new User();
        $user = $userModel->verifyPassword($data['email'], $data['password']);
        
        if (!$user) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_CREDENTIALS',
                    'message' => 'Email o password non corretti'
                ]
            ]);
            return;
        }
        
        if (!$user['is_active']) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => [
                    'code' => 'ACCOUNT_DISABLED',
                    'message' => 'Account disabilitato'
                ]
            ]);
            return;
        }
        
        $token = JWT::createToken($user['id'], $user['email'], $user['plan']);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'plan' => $user['plan'],
                    'ai_queries_count' => $user['ai_queries_count'],
                    'storage_used_bytes' => $user['storage_used_bytes'],
                    'last_login_at' => $user['last_login_at']
                ],
                'token' => $token
            ]
        ]);
    }
    
    public static function me() {
        global $currentUser;
        
        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $currentUser['id'],
                'email' => $currentUser['email'],
                'plan' => $currentUser['plan'],
                'ai_queries_count' => $currentUser['ai_queries_count'],
                'ai_queries_limit' => $currentUser['plan'] === 'pro' ? 500 : 20,
                'storage_used_mb' => round($currentUser['storage_used_bytes'] / 1048576, 2),
                'storage_limit_mb' => $currentUser['plan'] === 'pro' ? 500 : 10,
                'google_calendar_connected' => false, // TODO: implementare
                'created_at' => $currentUser['created_at']
            ]
        ]);
    }
}
