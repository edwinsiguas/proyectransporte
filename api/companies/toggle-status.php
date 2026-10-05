<?php

require_once '../config.php';
require_once '../utils/validation.php';
require_once '../utils/auth-middleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    sendResponse(false, null, 'Método no permitido', 405);
}

try {
    $user = authenticateRequest($pdo, 'admin');

    $data = getRequestData();
    $id = $data['id'] ?? ($_GET['id'] ?? null);

    if (!$id || !is_numeric($id)) {
        sendResponse(false, null, 'ID de empresa requerido', 400);
    }

    $stmt = $pdo->prepare('SELECT id, nombre, estado FROM companies WHERE id = ?');
    $stmt->execute([$id]);
    $company = $stmt->fetch();

    if (!$company) {
        sendResponse(false, null, 'Empresa no encontrada', 404);
    }

    $newState = ($company['estado'] === 'activo') ? 'inactivo' : 'activo';

    $updateStmt = $pdo->prepare('UPDATE companies SET estado = ? WHERE id = ?');
    $updateStmt->execute([$newState, $id]);

    logAction($pdo, $user['id'], 'cambio_estado', 'empresa', $id, "Estado de empresa '{$company['nombre']}' cambiado a $newState");

    sendResponse(true, ['id' => $id, 'estado' => $newState], "Estado actualizado a $newState exitosamente", 200);

} catch (PDOException $e) {
    error_log('Error al cambiar estado de empresa: ' . $e->getMessage());
    sendResponse(false, null, 'Error al cambiar estado de empresa', 500);
}