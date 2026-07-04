<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
// ── CORS ───────────────────────────────────────────────
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = [
    'http://localhost:3000',
    'http://lvh.me:3000',
    'http://localhost:3001',
    'http://lvh.me:3001',

    'https://mvp-client-git-main-vasuki84.vercel.app',
    'https://mvp-client-peach.vercel.app'
];

if (
    preg_match('/^http:\/\/[a-z0-9-]+\.lvh\.me:300[01]$/', $origin) ||
    preg_match('/^https:\/\/.*\.vercel\.app$/', $origin) ||
    in_array($origin, $allowed)
) {
    header("Access-Control-Allow-Origin: $origin");
}

header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token, X-Tenant');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../app/Config/config.php';
require_once __DIR__ . '/../app/Config/constants.php';
require_once __DIR__ . '/../app/Middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Helpers/Response.php';

if (session_status() === PHP_SESSION_NONE) session_start();

CsrfMiddleware::handle();
require_once __DIR__ . '/../app/Routes/api.php';
Response::error('Route not found', 404);