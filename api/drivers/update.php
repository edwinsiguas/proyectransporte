<?php

require_once '../config.php';
require_once '../utils/validation.php';
require_once '../utils/auth-middleware.php';

if (!in_array($_SERVER['REQUEST_METHOD'], ['PUT', 'POST'])) {
    sendResponse(false, null, 'Método no permitido', 405);
}

try {
    $user = authenticateRequest($pdo, ['admin', 'operador']);
    $data = sanitize(getRequestData());

    $id = $data['id'] ?? ($_GET['id'] ?? null);
    if (!$id || !is_numeric($id)) {
        sendResponse(false, null, 'ID inválido', 400);
    }

    $id = (int)$id;

    $stmt = $pdo->prepare('SELECT id FROM drivers WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        sendResponse(false, null, 'Chofer no encontrado', 404);
    }

    $fields = [];
    $params = [];

    foreach (['user_id', 'company_id'] as $optionalField) {
        if (isset($data[$optionalField]) && $data[$optionalField] !== '' && !is_numeric($data[$optionalField])) {
            sendResponse(false, null, 'El campo ' . $optionalField . ' debe ser numérico', 400);
        }
        if (array_key_exists($optionalField, $data)) {
            $fields[] = $optionalField . ' = ?';
            $params[] = $data[$optionalField] !== '' ? (int)$data[$optionalField] : null;
        }
    }

    foreach (['nombre_completo', 'dni', 'telefono', 'numero_licencia', 'fecha_vencimiento_licencia', 'estado'] as $field) {
        if (!array_key_exists($field, $data) || $data[$field] === '') {
            continue;
        }

        if ($field === 'estado' && !in_array($data[$field], ['activo', 'inactivo', 'suspendido'], true)) {
            sendResponse(false, null, 'Estado inválido', 400);
        }

        $fields[] = $field . ' = ?';
        $params[] = $data[$field];
    }

    if (empty($fields)) {
        sendResponse(false, null, 'No hay campos para actualizar', 400);
    }

    if (isset($data['dni']) && $data['dni'] !== '') {
        $dbValidator = new DatabaseValidator($pdo);
        if (!$dbValidator->isUnique('drivers', 'dni', $data['dni'], $id)) {
            sendResponse(false, null, 'El DNI ya está registrado', 400);
        }
    }

    if (isset($data['numero_licencia']) && $data['numero_licencia'] !== '') {
        $dbValidator = new DatabaseValidator($pdo);
        if (!$dbValidator->isUnique('drivers', 'numero_licencia', $data['numero_licencia'], $id)) {
            sendResponse(false, null, 'El número de licencia ya existe', 400);
        }
    }

    $fields[] = 'updated_at = NOW()';
    $params[] = $id;

    $sql = 'UPDATE drivers SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    logAction($pdo, $user['id'], 'actualizar', 'chofer', $id, 'Chofer actualizado');

    $stmt = $pdo->prepare('SELECT * FROM drivers WHERE id = ?');
    $stmt->execute([$id]);

    sendResponse(true, $stmt->fetch(), 'Chofer actualizado exitosamente', 200);
} catch (Throwable $e) {
    sendExceptionResponse('Error al actualizar chofer', $e, 'Error al actualizar chofer');
}

?>