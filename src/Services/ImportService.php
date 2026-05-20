<?php
namespace App\Services;

use PDO;
use RuntimeException;

final class ImportService
{
    private const REQUIRED = ['FECHA', 'MOVIMIENTO', 'IMPORTE'];

    public function __construct(private PDO $db, private MovementService $movements)
    {
    }

    public function preview(string $filePath): array
    {
        $rows = $this->readRows($filePath);
        return array_slice($rows, 0, 10);
    }

    public function import(string $filePath, string $account): array
    {
        $rows = $this->readRows($filePath);
        $stats = ['imported' => 0, 'duplicates' => 0, 'classified' => 0, 'pending' => 0];
        foreach ($rows as $row) {
            $date = $this->parseDate($row['FECHA'] ?? '');
            $concept = trim((string) ($row['MOVIMIENTO'] ?? ''));
            $amount = $this->parseNumber($row['IMPORTE'] ?? '0');
            $balance = isset($row['SALDO']) && $row['SALDO'] !== '' ? $this->parseNumber($row['SALDO']) : null;
            if (!$date || $concept === '') {
                continue;
            }
            $type = $amount >= 0 ? 'ingreso' : 'gasto';
            $hash = $this->movements->buildHash($date, $concept, $amount, $balance, $account);
            $exists = $this->db->prepare('SELECT id FROM movimientos WHERE hash_movimiento = ?');
            $exists->execute([$hash]);
            if ($exists->fetch()) {
                $stats['duplicates']++;
                continue;
            }
            $classification = $this->movements->classify($concept, $type);
            $this->movements->save([
                'fecha' => $date,
                'concepto_banco' => $concept,
                'mas_datos' => $row['MÁS DATOS'] ?? $row['MAS DATOS'] ?? null,
                'importe' => $amount,
                'saldo' => $balance,
                'tipo' => $type,
                'categoria_id' => $classification['categoria_id'] ?? null,
                'subcategoria_id' => $classification['subcategoria_id'] ?? null,
                'origen' => 'excel',
                'cuenta_bancaria' => $account,
                'hash_movimiento' => $hash,
            ]);
            $stats['imported']++;
            if ($classification) {
                $stats['classified']++;
            } else {
                $stats['pending']++;
            }
        }
        return $stats;
    }

    private function readRows(string $filePath): array
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (in_array($extension, ['csv', 'txt'], true)) {
            return $this->readCsv($filePath);
        }
        if (!class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
            throw new RuntimeException('Para importar Excel instala dependencias con composer install. Mientras tanto puedes importar CSV.');
        }
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        return $this->normalizeSheet($sheet);
    }

    private function readCsv(string $filePath): array
    {
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            throw new RuntimeException('No se pudo abrir el archivo.');
        }
        $firstLine = fgets($handle);
        rewind($handle);
        $delimiter = substr_count((string) $firstLine, ';') >= substr_count((string) $firstLine, ',') ? ';' : ',';
        $headers = fgetcsv($handle, 0, $delimiter);
        if (!$headers) {
            throw new RuntimeException('El archivo está vacío.');
        }
        $headers = array_map(fn ($h) => $this->normalizeHeader((string) $h), $headers);
        $this->validateHeaders($headers);
        $rows = [];
        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($data === [null] || count(array_filter($data, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $rows[] = array_combine($headers, array_pad($data, count($headers), ''));
        }
        fclose($handle);
        return $rows;
    }

    private function normalizeSheet(array $sheet): array
    {
        $headers = [];
        $rows = [];
        foreach ($sheet as $index => $row) {
            $values = array_values($row);
            if ($index === array_key_first($sheet)) {
                $headers = array_map(fn ($h) => $this->normalizeHeader((string) $h), $values);
                $this->validateHeaders($headers);
                continue;
            }
            $rows[] = array_combine($headers, array_pad($values, count($headers), ''));
        }
        return $rows;
    }

    private function validateHeaders(array $headers): void
    {
        foreach (self::REQUIRED as $required) {
            if (!in_array($required, $headers, true)) {
                throw new RuntimeException('Falta la columna obligatoria: ' . $required);
            }
        }
    }

    private function normalizeHeader(string $header): string
    {
        return mb_strtoupper(trim($header));
    }

    private function parseNumber(string|float|int $value): float
    {
        $value = trim((string) $value);
        $value = str_replace(['€', ' '], '', $value);
        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace('.', '', $value);
        }
        $value = str_replace(',', '.', $value);
        return (float) $value;
    }

    private function parseDate(string $value): ?string
    {
        $value = trim($value);
        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date) {
                return $date->format('Y-m-d');
            }
        }
        return null;
    }
}
