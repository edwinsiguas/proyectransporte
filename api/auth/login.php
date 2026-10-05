<?php

require_once '../config.php';
require_once '../utils/validation.php';
require_once '../utils/auth-middleware.php';
require_once '../utils/rate-limiter.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, null, 'Método no permitido', 405);
}

RateLimiter::check($pdo, 'login', maxRequests: 5, windowSeconds: 900);

$data = getRequestData();
$data = sanitize($data);

$validator = new Validator();
$rules = [
    'email' => 'required|email',
    'password' => 'required|min:6'
];

if (!$validator->validate($data, $rules)) {
    sendResponse(false, null, 'Validación fallida: ' . $validator->getFirstError(), 400);
}

$email = $data['email'];
$password = $data['password'];

try {

    $stmt = $pdo->prepare('
        SELECT id, email, password, nombre, role, estado, is_active
        FROM users
        WHERE email = ? AND estado = ? AND is_active = 1
    ');
    $stmt->execute([$email, 'activo']);
    $user = $stmt->fetch();

    if (!$user) {

        sendResponse(false, null, 'Email o contraseña incorrectos', 401);
    }

    if (!verifyPassword($password, $user['password'])) {

        logAction($pdo, $user['id'], 'login_intento', 'usuario', $user['id'], 'Intento fallido de login', $_SERVER['REMOTE_ADDR']);
        sendResponse(false, null, 'Email o contraseña incorrectos', 401);
    }

    $token = generateToken($user['id'], $user['role']);

    logAction($pdo, $user['id'], 'login', 'usuario', $user['id'], 'Login exitoso', $_SERVER['REMOTE_ADDR']);

    $response = [
        'id' => $user['id'],
        'email' => $user['email'],
        'nombre' => $user['nombre'],
        'role' => $user['role'],
        'token' => $token
    ];

    sendResponse(true, $response, 'Login exitoso', 200);

} catch (Exception $e) {
    error_log('Error en login: ' . $e->getMessage());
    sendResponse(false, null, 'Error del servidor', 500);
}

?>