<?php

namespace App\Imports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Row;
use Carbon\Carbon;

class ClientImport implements OnEachRow, WithStartRow
{
    /**
     * @return int
     */
    public function startRow(): int
    {
        return 2; // Saltamos la fila de cabecera
    }

    public function onRow(Row $row)
    {
        $data = $row->toArray();

        // Limpieza de datos (trim) para evitar duplicados por espacios invisibles
        $name    = trim($data[0] ?? '');
        $cif     = trim($data[1] ?? '');
        $email   = trim($data[2] ?? '');
        
        if (empty($name)) {
            return; // Saltamos filas sin nombre
        }

        $fields = [
            'name'             => $name,
            'email'            => !empty($email) ? $email : null,
            'phone'            => trim($data[3] ?? '') ?: null,
            'mobile'           => trim($data[4] ?? '') ?: null,
            'address'          => trim($data[5] ?? '') ?: null,
            'city'             => trim($data[6] ?? '') ?: null,
            'zip_code'         => trim($data[7] ?? '') ?: null,
            'province'         => trim($data[8] ?? '') ?: null,
            'country'          => trim($data[9] ?? '') ?: null,
            'excel_created_at' => $this->transformDate($data[10] ?? null),
        ];

        if (!empty($cif)) {
            // Caso 1: Tiene CIF. Es el identificador principal.
            Client::updateOrCreate(
                ['cif_nif' => $cif],
                $fields
            );
        } elseif (!empty($email)) {
            // Caso 2: No tiene CIF pero tiene Email. Usamos Nombre + Email para evitar duplicados.
            Client::updateOrCreate(
                ['name' => $name, 'email' => $email],
                $fields
            );
        } else {
            // Caso 3: No tiene ni CIF ni Email. Solo podemos comparar por nombre (más arriesgado).
            Client::updateOrCreate(
                ['name' => $name, 'cif_nif' => null, 'email' => null],
                $fields
            );
        }
    }

    private function transformDate($value)
    {
        if (empty($value)) {
            return Carbon::now();
        }

        try {
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
            }
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return Carbon::now();
        }
    }
}
