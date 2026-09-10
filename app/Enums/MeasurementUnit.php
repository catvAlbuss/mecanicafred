<?php

namespace App\Enums;

enum MeasurementUnit: string
{
    case Unit = 'unit';
    case Liter = 'liter';
    case Kilogram = 'kilogram';
    case Meter = 'meter';
    case Set = 'set';
    case Box = 'box';

    public function label(): string
    {
        return match ($this) {
            self::Unit => 'Unidad',
            self::Liter => 'Litro',
            self::Kilogram => 'Kilogramo',
            self::Meter => 'Metro',
            self::Set => 'Juego',
            self::Box => 'Caja',
        };
    }
}
