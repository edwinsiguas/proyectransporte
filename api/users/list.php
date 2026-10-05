<?php

require_once '../config.php';
require_once '../utils/auth-middleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(false, null, 'Método no permitido', 405);
}

try {
    $user = authenticateRequest($pdo, 'admin');

    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, (int)($_GET['limit'] ?? 10));
    $search = $_GET['search'] ?? '';
    $role = $_GET['role'] ?? '';
    $estado = $_GET['estado'] ?? '';
    $includeInactive = !empty($_GET['include_inactive']);

    $offset = ($page - 1) * $limit;

    $whereConditions = [];
    $params = [];

    if ($search) {
        $whereConditions[] = '(email LIKE ? OR nombre LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($role) {
        $whereConditions[] = 'role = ?';
        $params[] = $role;
    }

    if ($estado) {
        $whereConditions[] = 'estado = ?';
        $params[] = $estado;
    }

    if (!$includeInactive && !$estado) {
        $whereConditions[] = 'is_active = 1';
    }

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    $countQuery = "SELECT COUNT(*) as total FROM users $whereClause";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalResult = $countStmt->fetch();
    $total = $totalResult['total'];

    $query = "
        SELECT id, email, nombre, role, estado, is_active, created_at
        FROM users
        $whereClause
        ORDER BY created_at DESC
        LIMIT $limit OFFSET $offset
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll();

    sendResponse(true, [
        'data' => $users,
        'pagination' => [
            'page' => (int)$page,
            'limit' => (int)$limit,
            'total' => (int)$total,
            'pages' => ceil($total / $limit)
        ]
    ], null, 200);

} catch (Throwable $e) {
    sendExceptionResponse('Error al listar usuarios', $e, 'Error al listar usuarios');
}

?>