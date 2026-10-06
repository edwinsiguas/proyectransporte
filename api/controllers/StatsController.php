<?php
namespace Controllers;

use PDO;

class StatsController
{
    private PDO $pdo;

    private \Services\StatsService $statsService;

    public function __construct()
    {
        $this->pdo = \Config\Database::getConnection();
        require_once __DIR__ . '/../services/StatsService.php';
        $this->statsService = new \Services\StatsService($this->pdo);
    }

    private function renderJson($data) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $data,
            'message' => null
        ]);
        exit;
    }

    public function getDashboard() {
        $stats = $this->statsService->getDashboardStats();
        $this->renderJson($stats);
    }

    public function getCompanies() {
        $stats = $this->statsService->getCompanyStats();
        $this->renderJson($stats);
    }

    public function analytics(): void
    {
        authenticateRequest($this->pdo, ['admin', 'operador']);

        try {
            $data = [
                'kpis' => $this->getKpis(),
                'documentation' => $this->getDocumentStatus(),
                'growthData' => $this->getGrowthData(),
                'serviceData' => $this->getServiceData(),
                'rankingData' => $this->getRankingData()
            ];

            sendResponse(true, $data, 'Estadísticas recuperadas');
        } catch (\Throwable $e) {
            sendExceptionResponse('Error al obtener estadísticas', $e);
        }
    }

    private function getKpis(): array
    {

        $flotaStmt = $this->pdo->query("SELECT COUNT(*) FROM vehicles WHERE estado = 'activo'");
        $flota = (int) $flotaStmt->fetchColumn();

        $soatStmt = $this->pdo->query("SELECT COUNT(*) FROM vehicles WHERE estado = 'activo' AND soat_vencimiento >= CURDATE()");
        $soatValid = (int) $soatStmt->fetchColumn();
        $soatPercent = $flota > 0 ? round(($soatValid / $flota) * 100, 1) : 0;

        $tramitesStmt = $this->pdo->query("SELECT COUNT(*) FROM permits WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
        $tramites = (int) $tramitesStmt->fetchColumn();

        $empresasStmt = $this->pdo->query("SELECT COUNT(*) FROM companies WHERE estado = 'activo'");
        $empresas = (int) $empresasStmt->fetchColumn();

        return [
            'flota_autorizada' => $flota,
            'cumplimiento_soat' => $soatPercent,
            'tramites_proceso' => $tramites,
            'empresas_activas' => $empresas
        ];
    }

    private function getDocumentStatus(): array
    {

        $vTotalStmt = $this->pdo->query("SELECT COUNT(*) FROM vehicles WHERE estado = 'activo'");
        $vTotal = (int) $vTotalStmt->fetchColumn();

        $soatStmt = $this->pdo->query("SELECT COUNT(*) FROM vehicles WHERE estado = 'activo' AND soat_vencimiento >= CURDATE()");
        $soat = (int) $soatStmt->fetchColumn();

        $rtStmt = $this->pdo->query("SELECT COUNT(*) FROM vehicles WHERE estado = 'activo' AND rt_vencimiento >= CURDATE()");
        $rt = (int) $rtStmt->fetchColumn();

        $dTotalStmt = $this->pdo->query("SELECT COUNT(*) FROM drivers WHERE estado = 'activo'");
        $dTotal = (int) $dTotalStmt->fetchColumn();

        $licStmt = $this->pdo->query("SELECT COUNT(*) FROM drivers WHERE estado = 'activo' AND fecha_vencimiento_licencia >= CURDATE()");
        $lic = (int) $licStmt->fetchColumn();

        return [
            'soat' => ['valid' => $soat, 'total' => $vTotal],
            'rt' => ['valid' => $rt, 'total' => $vTotal],
            'licencias' => ['valid' => $lic, 'total' => $dTotal]
        ];
    }

    private function getGrowthData(): array
    {
        $stmt = $this->pdo->query("
            SELECT DATE_FORMAT(created_at, '%Y-%m') as mes, categoria, COUNT(id) as count
            FROM vehicles
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
            GROUP BY mes, categoria
            ORDER BY mes ASC
        ");
        $rows = $stmt->fetchAll();

        $monthsMapping = [
            '01' => 'Ene', '02' => 'Feb', '03' => 'Mar', '04' => 'Abr',
            '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Ago',
            '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dic'
        ];

        $dataset = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthStr = date('Y-m', strtotime("-$i months"));
            $dataset[$monthStr] = [
                'name' => $monthsMapping[date('m', strtotime("-$i months"))] . ' ' . date('y', strtotime("-$i months")),
                'vehiculos' => 0,
                'mototaxis' => 0
            ];
        }

        foreach ($rows as $row) {
            $m = $row['mes'];
            if (isset($dataset[$m])) {
                if (strtolower(trim($row['categoria'])) === 'moto-taxi') {
                    $dataset[$m]['mototaxis'] += $row['count'];
                } else {
                    $dataset[$m]['vehiculos'] += $row['count'];
                }
            }
        }

        return array_values($dataset);
    }

    private function getServiceData(): array
    {
        $stmt = $this->pdo->query("
            SELECT tipo_servicio, COUNT(id) as value
            FROM vehicles
            WHERE estado = 'activo' AND tipo_servicio IS NOT NULL AND tipo_servicio != ''
            GROUP BY tipo_servicio
        ");

        $colors = ['#0A2342', '#3B82F6', '#93C5FD', '#F59E0B', '#10B981'];
        $results = [];
        $i = 0;

        foreach ($stmt->fetchAll() as $row) {
            $results[] = [
                'name' => ucfirst($row['tipo_servicio']),
                'value' => (int) $row['value'],
                'color' => $colors[$i % count($colors)]
            ];
            $i++;
        }

        return $results;
    }

    private function getRankingData(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                c.id, c.nombre, c.ruc,
                COUNT(v.id) as flota,
                SUM(CASE WHEN v.soat_vencimiento >= CURDATE() THEN 1 ELSE 0 END) as soat_validos
            FROM companies c
            JOIN vehicles v ON v.company_id = c.id
            WHERE v.estado = 'activo'
            GROUP BY c.id
            ORDER BY flota DESC
            LIMIT 5
        ");

        $ranking = [];
        foreach ($stmt->fetchAll() as $row) {
            $flota = (int) $row['flota'];
            $soatValidos = (int) $row['soat_validos'];
            $cumplimiento = $flota > 0 ? round(($soatValidos / $flota) * 100) : 0;

            $ranking[] = [
                'name' => $row['nombre'],
                'ruc' => $row['ruc'],
                'flota' => $flota,
                'categoria' => 'Transporte',
                'cumplimiento' => $cumplimiento,
                'isWarning' => $cumplimiento < 80,
                'logo' => strtoupper(substr($row['nombre'], 0, 2))
            ];
        }

        return $ranking;
    }
}
