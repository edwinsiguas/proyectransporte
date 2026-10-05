<?php

require_once '../config.php';
require_once '../utils/validation.php';
require_once '../utils/auth-middleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, null, 'Método no permitido', 405);
}

try {
    $user = authenticateRequest($pdo, ['admin', 'operador']);
    $data = sanitize(getRequestData());

    $validator = new Validator();
    $dbValidator = new DatabaseValidator($pdo);

    $rules = [
        'nombre_completo' => 'required|min:3|max:150',
        'dni' => 'required|dni',
        'telefono' => 'required|phone',
        'numero_licencia' => 'required|min:5|max:20',
        'fecha_vencimiento_licencia' => 'required|date|future_date'
    ];

    if (!$validator->validate($data, $rules)) {
        sendResponse(false, null, 'Validación fallida: ' . $validator->getFirstError(), 400);
    }

    foreach (['user_id', 'company_id'] as $optionalField) {
        if (isset($data[$optionalField]) && $data[$optionalField] !== '' && !is_numeric($data[$optionalField])) {
            sendResponse(false, null, 'El campo ' . $optionalField . ' debe ser numérico', 400);
        }
    }

    if (!$dbValidator->isUnique('drivers', 'dni', $data['dni'])) {
        sendResponse(false, null, 'El DNI ya está registrado', 400);
    }

    if (!$dbValidator->isUnique('drivers', 'numero_licencia', $data['numero_licencia'])) {
        sendResponse(false, null, 'El número de licencia ya existe', 400);
    }

    $stmt = $pdo->prepare('
        INSERT INTO drivers (user_id, company_id, nombre_completo, dni, telefono, numero_licencia, fecha_vencimiento_licencia, estado)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ');

    $stmt->execute([
        !empty($data['user_id']) ? (int)$data['user_id'] : null,
        !empty($data['company_id']) ? (int)$data['company_id'] : null,
        $data['nombre_completo'],
        $data['dni'],
        $data['telefono'],
        $data['numero_licencia'],
        $data['fecha_vencimiento_licencia'],
        'activo'
    ]);

    $driverId = $pdo->lastInsertId();

    logAction($pdo, $user['id'], 'crear', 'chofer', $driverId, 'Chofer creado: ' . $data['nombre_completo']);

    $stmt = $pdo->prepare('SELECT * FROM drivers WHERE id = ?');
    $stmt->execute([$driverId]);
    $driver = $stmt->fetch();

    sendResponse(true, $driver, 'Chofer creado exitosamente', 201);

} catch (PDOException $e) {
    error_log('Error al crear chofer: ' . $e->getMessage());
    sendResponse(false, null, 'Error al crear chofer', 500);
}

?>