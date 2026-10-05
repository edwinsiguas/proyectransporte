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
        'email' => 'required|email',
        'password' => 'required|min:8',
        'nombre' => 'required|min:3|max:150',
        'role'     => 'required|in:admin,operador,fiscalizador'
    ];

    if (!$validator->validate($data, $rules)) {
        sendResponse(false, null, 'Validación fallida: ' . $validator->getFirstError(), 400);
    }

    if (!$dbValidator->isUnique('users', 'email', $data['email'])) {
        sendResponse(false, null, 'El email ya está registrado', 400);
    }

    $hashedPassword = hashPassword($data['password']);

    $stmt = $pdo->prepare('
        INSERT INTO users (email, password, nombre, role, estado, is_active)
        VALUES (?, ?, ?, ?, ?, ?)
    ');

    $stmt->execute([
        $data['email'],
        $hashedPassword,
        $data['nombre'],
        $data['role'],
        'activo',
        1
    ]);

    $userId = $pdo->lastInsertId();

    logAction($pdo, $user['id'], 'crear', 'usuario', $userId, 'Usuario creado: ' . $data['email']);

    $stmt = $pdo->prepare('SELECT id, email, nombre, role, estado, is_active, created_at FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $newUser = $stmt->fetch();

    sendResponse(true, $newUser, 'Usuario creado exitosamente', 201);

} catch (PDOException $e) {
    error_log('Error al crear usuario: ' . $e->getMessage());
    sendResponse(false, null, 'Error al crear usuario', 500);
}

?>