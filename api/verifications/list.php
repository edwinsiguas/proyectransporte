<?php

require_once '../config.php';
require_once '../utils/auth-middleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(false, null, 'Método no permitido', 405);
}

$user = authenticateRequest($pdo, ROLES_VERIFICACION);

$page    = max(1, (int)($_GET['page'] ?? 1));
$limit   = min(50, max(1, (int)($_GET['limit'] ?? 20)));
$offset  = ($page - 1) * $limit;

$resultado  = $_GET['resultado'] ?? null;
$dateFrom   = $_GET['date_from'] ?? null;
$dateTo     = $_GET['date_to'] ?? null;

$onlyMine = ($user['role'] === ROLE_FISCALIZADOR);

$where  = ['1=1'];
$params = [];

if ($onlyMine) {
    $where[]  = 'v.user_id = ?';
    $params[] = $user['id'];
} elseif (!empty($_GET['user_id'])) {
    $where[]  = 'v.user_id = ?';
    $params[] = (int)$_GET['user_id'];
}

if ($resultado && in_array($resultado, ['autorizado', 'rechazado'], true)) {
    $where[]  = 'v.resultado = ?';
    $params[] = $resultado;
}
if ($dateFrom) {
    $where[]  = 'DATE(v.created_at) >= ?';
    $params[] = $dateFrom;
}
if ($dateTo) {
    $where[]  = 'DATE(v.created_at) <= ?';
    $params[] = $dateTo;
}

$whereSQL = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM verifications v WHERE $whereSQL");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$dataStmt = $pdo->prepare("
    SELECT
        v.id,
        v.search_type,
        v.search_query,
        v.resultado,
        v.motivos,
        v.vehicle_data,
        v.ip_address,
        v.created_at,
        u.nombre  AS verificador_nombre,
        u.role    AS verificador_role
    FROM verifications v
    JOIN users u ON v.user_id = u.id
    WHERE $whereSQL
    ORDER BY v.created_at DESC
    LIMIT ? OFFSET ?
");
$dataStmt->execute([...$params, $limit, $offset]);
$rows = $dataStmt->fetchAll();

foreach ($rows as &$row) {
    $row['motivos']      = $row['motivos']      ? json_decode($row['motivos'],      true) : [];
    $row['vehicle_data'] = $row['vehicle_data']  ? json_decode($row['vehicle_data'], true) : null;
}

$todayStats = null;
if ($onlyMine) {
    $statsStmt = $pdo->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(resultado = 'autorizado') AS autorizados,
            SUM(resultado = 'rechazado')  AS rechazados
        FROM verifications
        WHERE user_id = ? AND DATE(created_at) = CURDATE()
    ");
    $statsStmt->execute([$user['id']]);
    $todayStats = $statsStmt->fetch();
}

sendResponse(true, [
    'data'       => $rows,
    'today_stats'=> $todayStats,
    'pagination' => [
        'page'  => $page,
        'limit' => $limit,
        'total' => $total,
        'pages' => $total > 0 ? ceil($total / $limit) : 1,
    ],
], 'OK');