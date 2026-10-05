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
    $estado = $_GET['estado'] ?? '';
    $includeInactive = !empty($_GET['include_inactive']);

    $offset = ($page - 1) * $limit;

    $whereConditions = [];
    $params = [];

    if ($search) {
        $whereConditions[] = '(d.nombre_completo LIKE ? OR d.dni LIKE ? OR d.numero_licencia LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($company_id) {
        $whereConditions[] = 'd.company_id = ?';
        $params[] = $company_id;
    }

    if ($estado) {
        $whereConditions[] = 'd.estado = ?';
        $params[] = $estado;
    }

    if (!$includeInactive && !$estado) {
        $whereConditions[] = 'd.estado = ?';
        $params[] = 'activo';
    }

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    $countQuery = "
        SELECT COUNT(*) as total
        FROM drivers d
        LEFT JOIN companies c ON d.company_id = c.id
        $whereClause
    ";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalResult = $countStmt->fetch();
    $total = $totalResult['total'];

    $query = "
        SELECT d.*, c.nombre as company_nombre
        FROM drivers d
        LEFT JOIN companies c ON d.company_id = c.id
        $whereClause
        ORDER BY d.created_at DESC
        LIMIT $limit OFFSET $offset
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $drivers = $stmt->fetchAll();

    sendResponse(true, [
        'data' => $drivers,
        'pagination' => [
            'page' => (int)$page,
            'limit' => (int)$limit,
            'total' => (int)$total,
            'pages' => ceil($total / $limit)
        ]
    ], null, 200);

} catch (Throwable $e) {
    sendExceptionResponse('Error al listar choferes', $e, 'Error al listar choferes');
}

?>