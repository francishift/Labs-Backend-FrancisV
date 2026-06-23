<?php

namespace App\Enums;

/**
 * Enum para representar los estados de una factura de compra.
 */
enum PurchaseFacturaStatus: string
{
    case RECIBIDA = 'recibida';
    case PAGADO = 'pagado';
    case PROCESANDO = 'procesando';
    case DUPLICADA = 'duplicada';
    case ERROR_IA = 'error_ia';
    case ERROR = 'error';

    /**
     * Obtiene la etiqueta amigable en castellano para el estado.
     */
    public function label(): string
    {
        return match ($this) {
            self::RECIBIDA => 'Recibida',
            self::PAGADO => 'Pagado',
            self::PROCESANDO => 'Procesando',
            self::DUPLICADA => 'Duplicada',
            self::ERROR_IA => 'Error IA',
            self::ERROR => 'Error',
        };
    }

    /**
     * Retorna las opciones disponibles formateadas para el select del frontend.
     */
    public static function options(): array
    {
        return array_map(fn($enum) => [
            'id' => $enum->value,
            'name' => $enum->label(),
        ], self::cases());
    }
}
