<?php

require_once '../config.php';
require_once '../utils/validation.php';
require_once '../utils/auth-middleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, null, 'Método no permitido', 405);
}

try {
    $user = authenticateRequest($pdo, 'admin');
    $data = getRequestData();
    $data = sanitize($data);

    $validator = new Validator();
    $dbValidator = new DatabaseValidator($pdo);

    $rules = [
        'nombre' => 'required|min:3|max:150',
        'ruc' => 'required|ruc',
        'telefono' => 'required|phone',
        'email' => 'required|email',
        'direccion' => 'required|min:5|max:255',
        'contacto' => 'required|min:3|max:150'
    ];

    if (!$validator->validate($data, $rules)) {
        sendResponse(false, null, 'Validación fallida: ' . $validator->getFirstError(), 400);
    }

    if (!$dbValidator->isUnique('companies', 'nombre', $data['nombre'])) {
        sendResponse(false, null, 'El nombre de la empresa ya existe', 400);
    }

    if (!$dbValidator->isUnique('companies', 'ruc', $data['ruc'])) {
        sendResponse(false, null, 'El RUC ya está registrado', 400);
    }

    $stmt = $pdo->prepare('
        INSERT INTO companies (nombre, ruc, telefono, email, direccion, contacto, estado)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ');

    $stmt->execute([
        $data['nombre'],
        $data['ruc'],
        $data['telefono'],
        $data['email'],
        $data['direccion'],
        $data['contacto'],
        'activo'
    ]);

    $companyId = $pdo->lastInsertId();

    logAction($pdo, $user['id'], 'crear', 'empresa', $companyId, 'Empresa creada: ' . $data['nombre']);

    $stmt = $pdo->prepare('SELECT * FROM companies WHERE id = ?');
    $stmt->execute([$companyId]);
    $company = $stmt->fetch();

    sendResponse(true, $company, 'Empresa creada exitosamente', 201);

} catch (PDOException $e) {
    error_log('Error al crear empresa: ' . $e->getMessage());
    sendResponse(false, null, 'Error al crear empresa', 500);
}

?>