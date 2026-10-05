<?php

require_once '../config.php';
require_once '../utils/auth-middleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    sendResponse(false, null, 'Método no permitido', 405);
}

try {
    $user = authenticateRequest($pdo, 'admin');

    $data = getRequestData();
    $id = $data['id'] ?? ($_GET['id'] ?? null);

    if (!$id || !is_numeric($id)) {
        sendResponse(false, null, 'ID de vehículo requerido', 400);
    }

    $stmt = $pdo->prepare('SELECT id, placa, estado FROM vehicles WHERE id = ?');
    $stmt->execute([$id]);
    $vehicle = $stmt->fetch();

    if (!$vehicle) {
        sendResponse(false, null, 'Vehículo no encontrado', 404);
    }

    $newState = ($vehicle['estado'] === 'activo') ? 'inactivo' : 'activo';

    $updateStmt = $pdo->prepare('UPDATE vehicles SET estado = ? WHERE id = ?');
    $updateStmt->execute([$newState, $id]);

    logAction($pdo, $user['id'], 'cambio_estado', 'vehiculo', $id, "Estado de vehículo '{$vehicle['placa']}' cambiado a $newState");

    sendResponse(true, ['id' => $id, 'estado' => $newState], "Estado actualizado a $newState exitosamente", 200);

} catch (PDOException $e) {
    error_log('Error al cambiar estado de vehículo: ' . $e->getMessage());
    sendResponse(false, null, 'Error al cambiar estado de vehículo', 500);
}