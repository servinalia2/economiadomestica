<?php
namespace App\Services;

use PDO;

final class MovementService
{
    public function __construct(private PDO $db)
    {
    }

    public function classify(string $concept, string $type): ?array
    {
        $rules = $this->db->query('SELECT * FROM reglas_clasificacion WHERE activa = 1 ORDER BY prioridad DESC, id ASC')->fetchAll();
        $normalizedConcept = mb_strtoupper($concept);
        foreach ($rules as $rule) {
            if ($rule['tipo'] !== 'ambos' && $rule['tipo'] !== $type) {
                continue;
            }
            if (str_contains($normalizedConcept, mb_strtoupper($rule['palabra_clave']))) {
                return [
                    'categoria_id' => (int) $rule['categoria_id'],
                    'subcategoria_id' => (int) $rule['subcategoria_id'],
                    'pendiente_revision' => 0,
                    'rule_id' => (int) $rule['id'],
                ];
            }
        }
        return null;
    }

    public function buildHash(string $date, string $concept, float $amount, ?float $balance, string $account): string
    {
        return hash('sha256', implode('|', [$date, mb_strtoupper(trim($concept)), number_format($amount, 2, '.', ''), number_format((float) $balance, 2, '.', ''), mb_strtoupper(trim($account))]));
    }

    public function save(array $data): string
    {
        $type = $data['tipo'] ?: (((float) $data['importe'] >= 0) ? 'ingreso' : 'gasto');
        $hash = $data['hash_movimiento'] ?? $this->buildHash($data['fecha'], $data['concepto_banco'], (float) $data['importe'], isset($data['saldo']) ? (float) $data['saldo'] : null, $data['cuenta_bancaria'] ?? '');
        $pending = empty($data['categoria_id']) || empty($data['subcategoria_id']) ? 1 : 0;

        $stmt = $this->db->prepare('INSERT INTO movimientos (fecha, concepto_banco, mas_datos, importe, saldo, tipo, categoria_id, subcategoria_id, origen, cuenta_bancaria, pendiente_revision, hash_movimiento) VALUES (:fecha, :concepto_banco, :mas_datos, :importe, :saldo, :tipo, :categoria_id, :subcategoria_id, :origen, :cuenta_bancaria, :pendiente_revision, :hash_movimiento)');
        $stmt->execute([
            'fecha' => $data['fecha'],
            'concepto_banco' => $data['concepto_banco'],
            'mas_datos' => $data['mas_datos'] ?? null,
            'importe' => $data['importe'],
            'saldo' => $data['saldo'] ?? null,
            'tipo' => $type,
            'categoria_id' => $data['categoria_id'] ?: null,
            'subcategoria_id' => $data['subcategoria_id'] ?: null,
            'origen' => $data['origen'] ?? 'manual',
            'cuenta_bancaria' => $data['cuenta_bancaria'] ?? null,
            'pendiente_revision' => $pending,
            'hash_movimiento' => $hash,
        ]);
        return $hash;
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare('UPDATE movimientos SET fecha = :fecha, concepto_banco = :concepto_banco, mas_datos = :mas_datos, importe = :importe, saldo = :saldo, tipo = :tipo, categoria_id = :categoria_id, subcategoria_id = :subcategoria_id, cuenta_bancaria = :cuenta_bancaria, pendiente_revision = :pendiente_revision WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'fecha' => $data['fecha'],
            'concepto_banco' => $data['concepto_banco'],
            'mas_datos' => $data['mas_datos'] ?? null,
            'importe' => $data['importe'],
            'saldo' => $data['saldo'] ?: null,
            'tipo' => $data['tipo'],
            'categoria_id' => $data['categoria_id'] ?: null,
            'subcategoria_id' => $data['subcategoria_id'] ?: null,
            'cuenta_bancaria' => $data['cuenta_bancaria'] ?? null,
            'pendiente_revision' => empty($data['categoria_id']) || empty($data['subcategoria_id']) ? 1 : 0,
        ]);
    }
}
