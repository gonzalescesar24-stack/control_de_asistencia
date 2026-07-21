<?php
declare(strict_types=1);

class ConfiguracionRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Recupera toda la estructura academica (programas -> modulos -> periodos -> unidades)
     * Optimizando las consultas para evitar problemas N+1.
     */
    public function getEstructuraAcademica(): array
    {
        $stmtProg = $this->pdo->query("SELECT * FROM programas ORDER BY id");
        $programas = $stmtProg->fetchAll(PDO::FETCH_ASSOC);

        // Fetch all dependencies to avoid N+1 queries
        $stmtMod = $this->pdo->query("SELECT * FROM modulos_formativos ORDER BY programa_id, numero");
        $todosModulos = $stmtMod->fetchAll(PDO::FETCH_ASSOC);

        $stmtPer = $this->pdo->query("SELECT * FROM periodos_curriculares ORDER BY modulo_id, id");
        $todosPeriodos = $stmtPer->fetchAll(PDO::FETCH_ASSOC);

        $stmtUni = $this->pdo->query("SELECT id, nombre, periodo_curricular_id FROM unidades_didacticas ORDER BY periodo_curricular_id, id");
        $todasUnidades = $stmtUni->fetchAll(PDO::FETCH_ASSOC);

        // Group units by period
        $unidadesPorPeriodo = [];
        foreach ($todasUnidades as $u) {
            $unidadesPorPeriodo[$u['periodo_curricular_id']][] = $u['nombre'];
        }

        // Group periods by module
        $periodosPorModulo = [];
        foreach ($todosPeriodos as $p) {
            $periodosPorModulo[$p['modulo_id']][] = [
                'nombre' => $p['nombre'],
                'unidades' => $unidadesPorPeriodo[$p['id']] ?? []
            ];
        }

        // Group modules by program
        $modulosPorPrograma = [];
        foreach ($todosModulos as $m) {
            $modulosPorPrograma[$m['programa_id']][] = [
                'num' => $m['numero'],
                'nombre' => $m['nombre'],
                'periodos' => $periodosPorModulo[$m['id']] ?? []
            ];
        }

        // Assemble final structure
        foreach ($programas as &$prog) {
            $prog['modulos'] = $modulosPorPrograma[$prog['id']] ?? [];
        }

        return $programas;
    }

    public function getPeriodosAcademicos(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM periodos_academicos ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
