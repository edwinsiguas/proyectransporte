<?php

require_once '../config.php';
require_once '../utils/validation.php';
require_once '../utils/auth-middleware.php';

if (!in_array($_SERVER['REQUEST_METHOD'], ['PUT', 'POST'])) {
    sendResponse(false, null, 'Método no permitido', 405);
}

try {

    $actor = authenticateRequest($pdo);
    $data  = sanitize(getRequestData());

    $id = $data['id'] ?? ($_GET['id'] ?? null);
    if (!$id || !is_numeric($id)) {
        sendResponse(false, null, 'ID inválido', 400);
    }
    $id = (int)$id;

    $isSelf  = ($actor['id'] === $id);
    $isAdmin = ($actor['role'] === ROLE_ADMIN);

    if (!$isSelf && !$isAdmin) {
        sendResponse(false, null, 'No tienes permiso para editar este usuario', 403);
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        sendResponse(false, null, 'Usuario no encontrado', 404);
    }

    $fields = [];
    $params = [];

    if (isset($data['email']) && $data['email'] !== '') {
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            sendResponse(false, null, 'Email inválido', 400);
        }
        $dbValidator = new DatabaseValidator($pdo);
        if (!$dbValidator->isUnique('users', 'email', $data['email'], $id)) {
            sendResponse(false, null, 'El email ya está registrado', 400);
        }
        $fields[] = 'email = ?';
        $params[] = $data['email'];
    }

    if (isset($data['nombre']) && $data['nombre'] !== '') {
        $fields[] = 'nombre = ?';
        $params[] = $data['nombre'];
    }

    if (isset($data['password']) && $data['password'] !== '') {
        if (strlen($data['password']) < 8) {
            sendResponse(false, null, 'La contraseña debe tener al menos 8 caracteres', 400);
        }
        $fields[] = 'password = ?';
        $params[] = hashPassword($data['password']);
    }

    if ($isAdmin) {
        if (isset($data['role']) && $data['role'] !== '') {
            if (!in_array($data['role'], [ROLE_ADMIN, ROLE_OPERADOR, ROLE_FISCALIZADOR], true)) {
                sendResponse(false, null, 'Rol inválido', 400);
            }
            $fields[] = 'role = ?';
            $params[] = $data['role'];
        }

        if (isset($data['is_active']) && $data['is_active'] !== '') {
            $parsedActive = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($parsedActive === null) {
                sendResponse(false, null, 'is_active inválido', 400);
            }
            $fields[] = 'is_active = ?';
            $params[] = $parsedActive ? 1 : 0;
        }

        if (isset($data['estado']) && $data['estado'] !== '') {
            if (!in_array($data['estado'], ['activo', 'inactivo'], true)) {
                sendResponse(false, null, 'Estado inválido', 400);
            }
            $fields[] = 'estado = ?';
            $params[] = $data['estado'];
        }
    }

    if (empty($fields)) {
        sendResponse(false, null, 'No hay campos para actualizar', 400);
    }

    $fields[] = 'updated_at = NOW()';
    $params[]  = $id;

    $sql  = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    logAction($pdo, $actor['id'], 'actualizar', 'usuario', $id, 'Credenciales actualizadas');

    $stmt = $pdo->prepare('SELECT id, email, nombre, role, estado, is_active, created_at, updated_at FROM users WHERE id = ?');
    $stmt->execute([$id]);

    sendResponse(true, $stmt->fetch(), 'Usuario actualizado exitosamente', 200);
} catch (Throwable $e) {
    sendExceptionResponse('Error al actualizar usuario', $e, 'Error al actualizar usuario');
}
?>