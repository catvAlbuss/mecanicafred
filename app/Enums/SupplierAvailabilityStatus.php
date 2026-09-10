<?php

namespace App\Enums;

enum SupplierAvailabilityStatus: string
{
    case Unknown = 'unknown';
    case Available = 'available';
    case Limited = 'limited';
    case Unavailable = 'unavailable';

    public function label(): string
    {
        return match ($this) {
            self::Unknown => 'Desconocido',
            self::Available => 'Disponible',
            self::Limited => 'Limitado',
            self::Unavailable => 'No disponible',
        };
    }
}
