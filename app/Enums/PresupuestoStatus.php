<?php

namespace App\Enums;

enum PresupuestoStatus: int
{
    case PENDING = 0;
    case APPROVED = 1;
    case CANCELED = 2; // Anulado
    case REJECTED = 3;
    case INVOICED = 4; // Convertido a factura

    public function label(): string
    {
        return match ($this) {
            self::PENDING   => 'Pendiente',
            self::APPROVED  => 'Aprobado',
            self::CANCELED  => 'Anulado',
            self::REJECTED  => 'Rechazado',
            self::INVOICED  => 'Facturado',
        };
    }

    public static function options(): array
    {
        return array_map(fn($enum) => [
            'id' => $enum->value,
            'name' => $enum->label(),
        ], self::cases());
    }
}
