<?php

require_once '../config.php';
require_once '../utils/auth-middleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, null, 'Método no permitido', 405);
}

try {
    $user = authenticateRequest($pdo);

    logAction($pdo, $user['id'], 'logout', 'usuario', $user['id'], 'Logout realizado', $_SERVER['REMOTE_ADDR']);

    sendResponse(true, null, 'Logout exitoso', 200);

} catch (Exception $e) {
    error_log('Error en logout: ' . $e->getMessage());
    sendResponse(false, null, 'Error del servidor', 500);
}

?>