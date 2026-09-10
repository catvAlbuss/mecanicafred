<?php

namespace App\Enums;

enum PurchaseOrderStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Confirmed = 'confirmed';
    case PartiallyReceived = 'partially_received';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Sent => 'Enviado',
            self::Confirmed => 'Confirmado',
            self::PartiallyReceived => 'Recibido parcialmente',
            self::Received => 'Recibido',
            self::Cancelled => 'Cancelado',
        };
    }
}
