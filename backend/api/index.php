<?php
/**
 * GM_V41 - SmartLife AI Organizer
 * API Router
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);  // ← Cambia da 0 a 1 per vedere errori

// Aggiungi questo debug
error_log("REQUEST_URI: " . $_SERVER['REQUEST_URI']);
error_log("Path parsed: " . (isset($path) ? $path : 'N/A'));

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/EventController.php';

$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
$path = str_replace($scriptName, '', $requestUri);
$path = strtok($path, '?');
$path = trim($path, '/');
// Normalizza path: rimuovi gm_v41/ e api/ se presenti
$path = preg_replace('#^(gm_v41/)?api/#', '', $path);
$path = trim($path, '/');
$method = $_SERVER['REQUEST_METHOD'];

// Ripeti normalizzazione e aggiungi DEBUG richiesto
$path = preg_replace('#^(gm_v41/)?api/#', '', $path);
$path = trim($path, '/');

// DEBUG - Mostra path e esci
if ($method === 'GET' && (strpos($path, 'events') !== false || strpos($path, 'categories') !== false)) {
    echo json_encode([
        'DEBUG' => true,
        'original_uri' => $_SERVER['REQUEST_URI'],
        'script_name' => $_SERVER['SCRIPT_NAME'],
        'path_after_normalization' => $path,
        'method' => $method
    ]);
    exit;
}

function notFound() {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Endpoint non trovato']]);
    exit;
}

try {
    // Health check (no auth)
    if (strpos($path, 'health') !== false) {
        echo json_encode(['status' => 'ok', 'version' => '1.0.0', 'timestamp' => date('c')]);
        exit;
    }
    
    // Auth endpoints (no auth required)
    if (strpos($path, 'auth/register') !== false && $method === 'POST') {
        AuthController::register();
        exit;
    }
    if (strpos($path, 'auth/login') !== false && $method === 'POST') {
        AuthController::login();
        exit;
    }
    
    // All other endpoints require authentication
    AuthMiddleware::authenticate();
    
    // User profile
    if (strpos($path, 'auth/me') !== false && $method === 'GET') {
        AuthController::me();
        exit;
    }
    
    // Events list
    if (preg_match('#events/?$#', $path)) {
        if ($method === 'GET') EventController::index();
        elseif ($method === 'POST') EventController::create();
        else notFound();
        exit;
    }
    
    // Single event
    if (preg_match('#events/(\d+)$#', $path, $m)) {
        $id = $m[1];
        if ($method === 'GET') EventController::show($id);
        elseif ($method === 'PUT') EventController::update($id);
        elseif ($method === 'DELETE') EventController::delete($id);
        else notFound();
        exit;
    }
    
    // Toggle event status
    if (preg_match('#events/(\d+)/complete#', $path, $m) && $method === 'POST') {
        EventController::toggleStatus($m[1]);
        exit;
    }
    
    // Categories
    if (strpos($path, 'categories') !== false && $method === 'GET') {
        CategoryController::index();
        exit;
    }
    
    notFound();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => ['code' => 'SERVER_ERROR', 'message' => $e->getMessage()]]);
}
