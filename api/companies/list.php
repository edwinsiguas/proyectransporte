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
    $estado = $_GET['estado'] ?? '';

    $offset = ($page - 1) * $limit;

    $whereConditions = [];
    $params = [];

    if ($search) {
        $whereConditions[] = '(c.nombre LIKE ? OR c.ruc LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($estado) {
        $whereConditions[] = 'c.estado = ?';
        $params[] = $estado;
    }

    if ($user['role'] === 'empresa') {

    }

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    $countQuery = "SELECT COUNT(*) as total FROM companies c $whereClause";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalResult = $countStmt->fetch();
    $total = $totalResult['total'];

    $activeCountStmt = $pdo->query("SELECT COUNT(*) as active_total FROM companies WHERE estado = 'activo'");
    $activeTotal = $activeCountStmt->fetch()['active_total'];

    $query = "
        SELECT c.*, COUNT(v.id) as flota
        FROM companies c
        LEFT JOIN vehicles v ON v.company_id = c.id AND v.estado = 'activo'
        $whereClause
        GROUP BY c.id
        ORDER BY c.created_at DESC
        LIMIT $limit OFFSET $offset
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $companies = $stmt->fetchAll();

    sendResponse(true, [
        'data' => $companies,
        'pagination' => [
            'page' => (int)$page,
            'limit' => (int)$limit,
            'total' => (int)$total,
            'active_total' => (int)$activeTotal,
            'pages' => ceil($total / $limit)
        ]
    ], null, 200);

} catch (Throwable $e) {
    sendExceptionResponse('Error al listar empresas', $e, 'Error al listar empresas');
}

?>