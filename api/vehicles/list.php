<?php

require_once '../config.php';
require_once '../utils/auth-middleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(false, null, 'Método no permitido', 405);
}

try {
    $user = authenticateRequest($pdo, ['admin', 'operador']);

    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, (int)($_GET['limit'] ?? 10));
    $search = $_GET['search'] ?? '';
    $company_id = $_GET['company_id'] ?? '';
    $driver_id = $_GET['driver_id'] ?? '';
    $estado = $_GET['estado'] ?? '';
    $includeInactive = !empty($_GET['include_inactive']);

    $offset = ($page - 1) * $limit;

    $whereConditions = [];
    $params = [];

    if ($search) {
        $whereConditions[] = '(v.placa LIKE ? OR v.marca LIKE ? OR v.modelo LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($company_id) {
        $whereConditions[] = 'v.company_id = ?';
        $params[] = $company_id;
    }

    if ($driver_id) {

        $whereConditions[] = 'EXISTS (SELECT 1 FROM vehicle_assignments va WHERE va.vehicle_id = v.id AND va.driver_id = ? AND va.activo = 1)';
        $params[] = $driver_id;
    }

    if ($estado) {
        $whereConditions[] = 'v.estado = ?';
        $params[] = $estado;
    }

    if (!$includeInactive && !$estado) {
        $whereConditions[] = 'v.estado = ?';
        $params[] = 'activo';
    }

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    $countQuery = "
        SELECT COUNT(*) as total
        FROM vehicles v
        LEFT JOIN vehicle_assignments va ON va.vehicle_id = v.id AND va.activo = 1
        LEFT JOIN drivers d ON va.driver_id = d.id
        LEFT JOIN companies c ON v.company_id = c.id
        $whereClause
    ";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalResult = $countStmt->fetch();
    $total = $totalResult['total'];

    $query = "
        SELECT v.*,
               d.nombre_completo as driver_nombre,
               d.numero_licencia as driver_licencia,
               d.estado as driver_estado,
               va.tipo as assignment_tipo,
               va.id as assignment_id,
               c.nombre as company_nombre,
               (
                   SELECT COUNT(*)
                   FROM vehicle_images vi
                   WHERE vi.vehicle_id = v.id
               ) as images_count,
               (
                   SELECT vi.file_path
                   FROM vehicle_images vi
                   WHERE vi.vehicle_id = v.id
                   ORDER BY vi.sort_order ASC, vi.id ASC
                   LIMIT 1
               ) as primary_image
        FROM vehicles v
        LEFT JOIN vehicle_assignments va ON va.vehicle_id = v.id AND va.activo = 1
        LEFT JOIN drivers d ON va.driver_id = d.id
        LEFT JOIN companies c ON v.company_id = c.id
        $whereClause
        ORDER BY v.created_at DESC
        LIMIT $limit OFFSET $offset
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $vehicles = $stmt->fetchAll();

    $apiBase = rtrim(API_URL, '/');
    foreach ($vehicles as &$v) {
        if (!empty($v['primary_image'])) {
            $v['primary_image'] = $apiBase . '/' . ltrim($v['primary_image'], '/');
        }
    }
    unset($v);

    sendResponse(true, [
        'data' => $vehicles,
        'pagination' => [
            'page' => (int)$page,
            'limit' => (int)$limit,
            'total' => (int)$total,
            'pages' => ceil($total / $limit)
        ]
    ], null, 200);

} catch (Throwable $e) {
    sendExceptionResponse('Error al listar vehículos', $e, 'Error al listar vehículos');
}

?>