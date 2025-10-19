<?php
/**
 * Auth Middleware
 * Verifica JWT token e carica utente
 */

require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../models/User.php';

class AuthMiddleware {
    public static function authenticate() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (empty($authHeader) || strpos($authHeader, 'Bearer ') !== 0) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Token mancante o non valido'
                ]
            ]);
            exit;
        }

        $token = substr($authHeader, 7);
        $payload = JWT::decode($token);

        if (!$payload) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_TOKEN',
                    'message' => 'Token non valido o scaduto'
                ]
            ]);
            exit;
        }

        // Carica utente dal DB
        $userModel = new User();
        $user = $userModel->findById($payload['user_id']);

        if (!$user || !$user['is_active']) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => [
                    'code' => 'USER_NOT_FOUND',
                    'message' => 'Utente non trovato o disabilitato'
                ]
            ]);
            exit;
        }

        // Aggiungi utente al contesto globale
        global $currentUser;
        $currentUser = $user;

        return $user;
    }

    public static function requirePlan($requiredPlan = 'pro') {
        global $currentUser;
        
        if (!isset($currentUser)) {
            self::authenticate();
        }

        if ($currentUser['plan'] !== $requiredPlan) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => [
                    'code' => 'PLAN_REQUIRED',
                    'message' => "Piano {$requiredPlan} richiesto per questa funzionalità"
                ]
            ]);
            exit;
        }
    }
}
