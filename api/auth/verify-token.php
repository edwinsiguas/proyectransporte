<?php

require_once '../config.php';
require_once '../utils/auth-middleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, null, 'Método no permitido', 405);
}

try {
    $user = authenticateRequest($pdo);

    sendResponse(true, [
        'id' => $user['id'],
        'email' => $user['email'],
        'role' => $user['role']
    ], 'Token válido', 200);

} catch (Exception $e) {
    error_log('Error en verificación: ' . $e->getMessage());
    sendResponse(false, null, 'Token inválido', 401);
}

?>