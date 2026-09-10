<?php

namespace App\Enums;

enum ProductType: string
{
    case Tool = 'tool';
    case SparePart = 'spare_part';
    case Material = 'material';
    case Consumable = 'consumable';
    case Lubricant = 'lubricant';

    public function label(): string
    {
        return match ($this) {
            self::Tool => 'Herramienta',
            self::SparePart => 'Repuesto',
            self::Material => 'Material',
            self::Consumable => 'Consumible',
            self::Lubricant => 'Lubricante',
        };
    }
}
