<?php
namespace Controllers;

use Services\AssignmentService;
use Services\CirculationValidator;

class AssignmentController
{
    private AssignmentService $service;
    private CirculationValidator $validator;
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo       = \Config\Database::getConnection();
        $this->service   = new AssignmentService($this->pdo);
        $this->validator = new CirculationValidator($this->pdo);
    }

    public function listAssignments(): void
    {
        $user = authenticateRequest($this->pdo, ['admin', 'operador']);

        $filters = [
            'vehicle_id' => $_GET['vehicle_id'] ?? null,
            'driver_id'  => $_GET['driver_id']  ?? null,
            'tipo'       => $_GET['tipo']        ?? null,
            'activo'     => isset($_GET['activo']) ? (int)$_GET['activo'] : null,
            'page'       => (int)($_GET['page']  ?? 1),
            'limit'      => (int)($_GET['limit'] ?? 10),
        ];

        $filters = array_filter($filters, fn($v) => $v !== null);

        sendResponse(true, $this->service->listAssignments($filters));
    }

    public function getByVehicle(): void
    {
        $user      = authenticateRequest($this->pdo, ['admin', 'operador']);
        $vehicleId = (int) ($this->getUriSegment(-1));

        $assignment = $this->service->getActiveAssignment($vehicleId);

        if (!$assignment) {
            sendResponse(false, null, 'No hay conductor asignado a este vehículo', 404);
        }

        sendResponse(true, $assignment);
    }

    public function getByDriver(): void
    {
        $user     = authenticateRequest($this->pdo, ['admin', 'operador']);
        $driverId = (int) ($this->getUriSegment(-1));

        $vehicle = $this->service->getDriverActiveVehicle($driverId);

        if (!$vehicle) {
            sendResponse(false, null, 'El conductor no tiene vehículo asignado actualmente', 404);
        }

        sendResponse(true, $vehicle);
    }

    public function assign(): void
    {
        $user = authenticateRequest($this->pdo, ['admin']);
        $data = sanitize(getRequestData());

        if (empty($data['vehicle_id']) || empty($data['driver_id'])) {
            sendResponse(false, null, 'vehicle_id y driver_id son obligatorios', 400);
        }

        $tiposValidos = ['propietario', 'alquiler', 'reemplazo_temporal'];
        $tipo = $data['tipo'] ?? 'propietario';
        if (!in_array($tipo, $tiposValidos, true)) {
            sendResponse(false, null, 'Tipo de asignación inválido. Use: ' . implode(', ', $tiposValidos), 400);
        }

        if ($tipo === 'alquiler') {
            $propietarioNombre = trim((string) ($data['propietario_nombre'] ?? ''));
            $propietarioDni = trim((string) ($data['propietario_dni'] ?? ''));

            if ($propietarioNombre === '' || strlen($propietarioNombre) < 3) {
                sendResponse(false, null, 'propietario_nombre es obligatorio para tipo alquiler', 400);
            }

            if (!preg_match('/^\d{8}$/', $propietarioDni)) {
                sendResponse(false, null, 'propietario_dni debe tener 8 dígitos', 400);
            }
        }

        try {
            $assignment = $this->service->assignDriver(
                (int) $data['vehicle_id'],
                (int) $data['driver_id'],
                $tipo,
                $tipo === 'alquiler' ? ($data['propietario_nombre'] ?? null) : null,
                $tipo === 'alquiler' ? ($data['propietario_dni'] ?? null) : null,
                $data['fecha_fin'] ?? null,
                $user['id'],
                $data['notas'] ?? null
            );

            logAction(
                $this->pdo, $user['id'], 'asignar_conductor', 'vehiculo',
                (int)$data['vehicle_id'],
                "Conductor ID {$data['driver_id']} asignado como $tipo"
            );

            sendResponse(true, $assignment, 'Conductor asignado exitosamente', 201);
        } catch (\RuntimeException $e) {
            sendResponse(false, null, $e->getMessage(), 422);
        }
    }

    public function unassign(): void
    {
        $user      = authenticateRequest($this->pdo, ['admin']);
        $vehicleId = (int) ($this->getUriSegment(-1));

        $unassigned = $this->service->unassignDriver($vehicleId);

        if (!$unassigned) {
            sendResponse(false, null, 'No había conductor activo asignado a este vehículo', 404);
        }

        logAction($this->pdo, $user['id'], 'desasignar_conductor', 'vehiculo', $vehicleId, 'Conductor desasignado');
        sendResponse(true, null, 'Conductor desasignado correctamente');
    }

    public function validate(): void
    {

        $placa         = $_GET['placa'] ?? null;
        $dni           = $_GET['dni']   ?? null;
        $numeroPermiso = $_GET['numero_permiso'] ?? null;

        try {
            if ($placa) {
                $result = $this->validator->validateByPlaca($placa);
            } elseif ($dni) {
                if (!preg_match('/^\d{8}$/', (string) $dni)) {
                    sendResponse(false, null, 'dni debe tener 8 dígitos', 400);
                    return;
                }
                $result = $this->validator->validateByDriverDni($dni);
            } elseif ($numeroPermiso) {
                $result = $this->validator->validateByPermitNumber($numeroPermiso);
            } else {
                sendResponse(false, null, 'Proporcione placa, dni o numero_permiso', 400);
                return;
            }
            sendResponse(true, $result, $result['razon']);
        } catch (\Throwable $e) {
            sendResponse(false, null, 'Error al validar: ' . $e->getMessage(), 500);
        }
    }

    public function vehicleHistory(): void
    {
        $user      = authenticateRequest($this->pdo, ['admin', 'operador']);
        $vehicleId = (int) ($this->getUriSegment(-1));

        sendResponse(true, $this->service->getVehicleHistory($vehicleId));
    }

    private function getUriSegment(int $index): string
    {
        $uri     = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $parts   = array_values(array_filter(explode('/', $uri)));
        $count   = count($parts);
        $absIdx  = $index < 0 ? $count + $index : $index;
        return $parts[$absIdx] ?? '';
    }
}
