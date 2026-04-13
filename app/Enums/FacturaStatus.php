<?php

namespace App\Enums;

enum FacturaStatus: int
{
    case PENDING = 0;
    case PAID = 1;
    case PARTIAL = 2;
    case CANCELED = 3; // Anulada

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente',
            self::PAID => 'Pagada',
            self::PARTIAL => 'Parcial',
            self::CANCELED => 'Anulada',
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
