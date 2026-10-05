<?php

require_once '../config.php';
require_once '../utils/auth-middleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(false, null, 'Método no permitido', 405);
}

try {
    $user = authenticateRequest($pdo, ['admin', 'operador']);

    $id = $_GET['id'] ?? null;

    if (!$id || !is_numeric($id)) {
        sendResponse(false, null, 'ID inválido', 400);
    }

    $stmt = $pdo->prepare('SELECT * FROM companies WHERE id = ?');
    $stmt->execute([$id]);
    $company = $stmt->fetch();

    if (!$company) {
        sendResponse(false, null, 'Empresa no encontrada', 404);
    }

    sendResponse(true, $company, null, 200);

} catch (PDOException $e) {
    error_log('Error al obtener empresa: ' . $e->getMessage());
    sendResponse(false, null, 'Error al obtener empresa', 500);
}

?>