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
    $data = sanitize($data);

    $id = $data['id'] ?? null;

    if (!$id || !is_numeric($id)) {
        sendResponse(false, null, 'ID inválido', 400);
    }

    $stmt = $pdo->prepare('SELECT * FROM companies WHERE id = ?');
    $stmt->execute([$id]);
    $company = $stmt->fetch();

    if (!$company) {
        sendResponse(false, null, 'Empresa no encontrada', 404);
    }

    $validator = new Validator();
    $dbValidator = new DatabaseValidator($pdo);

    $rules = [
        'nombre' => 'required|min:3|max:150',
        'ruc' => 'required|ruc',
        'telefono' => 'required|phone',
        'email' => 'required|email',
        'direccion' => 'required|min:5|max:255',
        'contacto' => 'required|min:3|max:150',
        'estado' => 'required|in:activo,inactivo'
    ];

    if (!$validator->validate($data, $rules)) {
        sendResponse(false, null, 'Validación fallida: ' . $validator->getFirstError(), 400);
    }

    if ($data['nombre'] !== $company['nombre'] && !$dbValidator->isUnique('companies', 'nombre', $data['nombre'])) {
        sendResponse(false, null, 'El nombre de la empresa ya existe', 400);
    }

    if ($data['ruc'] !== $company['ruc'] && !$dbValidator->isUnique('companies', 'ruc', $data['ruc'])) {
        sendResponse(false, null, 'El RUC ya está registrado', 400);
    }

    $stmt = $pdo->prepare('
        UPDATE companies
        SET nombre = ?, ruc = ?, telefono = ?, email = ?, direccion = ?, contacto = ?, estado = ?
        WHERE id = ?
    ');

    $stmt->execute([
        $data['nombre'],
        $data['ruc'],
        $data['telefono'],
        $data['email'],
        $data['direccion'],
        $data['contacto'],
        $data['estado'],
        $id
    ]);

    logAction($pdo, $user['id'], 'actualizar', 'empresa', $id, 'Empresa actualizada: ' . $data['nombre']);

    $stmt = $pdo->prepare('SELECT * FROM companies WHERE id = ?');
    $stmt->execute([$id]);
    $updated = $stmt->fetch();

    sendResponse(true, $updated, 'Empresa actualizada exitosamente', 200);

} catch (PDOException $e) {
    error_log('Error al actualizar empresa: ' . $e->getMessage());
    sendResponse(false, null, 'Error al actualizar empresa', 500);
}

?>