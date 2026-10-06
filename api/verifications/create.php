<?php

require_once '../config.php';
require_once '../utils/auth-middleware.php';
require_once '../utils/rate-limiter.php';
require_once '../services/CirculationValidator.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, null, 'Método no permitido', 405);
}

RateLimiter::check($pdo, 'permit_verify', 60, 60);

$user = authenticateRequest($pdo, ROLES_VERIFICACION);

$data        = getRequestData();
$searchType  = trim($data['search_type'] ?? '');
$searchQuery = strtoupper(trim($data['search_query'] ?? ''));

if (!in_array($searchType, ['placa', 'dni', 'permiso'], true)) {
    sendResponse(false, null, 'Tipo de búsqueda inválido. Use: placa, dni o permiso', 400);
}
if ($searchQuery === '') {
    sendResponse(false, null, 'El término de búsqueda es requerido', 400);
}

$validator = new Services\CirculationValidator($pdo);

$validators = [
    'placa'   => fn() => $validator->validateByPlaca($searchQuery),
    'dni'     => fn() => $validator->validateByDriverDni($searchQuery),
    'permiso' => fn() => $validator->validateByPermitNumber($searchQuery),
];

$result = ($validators[$searchType])();

$resultado   = ($result['puede_circular'] ?? false) ? 'autorizado' : 'rechazado';
$motivos     = $result['bloqueos'] ?? [];
$vehicleData = $result['data'] ?? null;

$ip = $_SERVER['HTTP_CF_CONNECTING_IP']
    ?? $_SERVER['HTTP_X_REAL_IP']
    ?? $_SERVER['HTTP_X_FORWARDED_FOR']
    ?? $_SERVER['REMOTE_ADDR']
    ?? '0.0.0.0';
$ip = trim(explode(',', $ip)[0]);

$stmt = $pdo->prepare("
    INSERT INTO verifications
        (user_id, search_type, search_query, resultado, motivos, vehicle_data, ip_address)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([
    $user['id'],
    $searchType,
    $searchQuery,
    $resultado,
    json_encode($motivos, JSON_UNESCAPED_UNICODE),
    $vehicleData ? json_encode($vehicleData, JSON_UNESCAPED_UNICODE) : null,
    $ip,
]);

sendResponse(true, $result, 'Verificación completada', 200);