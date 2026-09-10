<?php

namespace App\Enums;

enum SupplierInquiryStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Answered = 'answered';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Sent => 'Enviada',
            self::Answered => 'Respondida',
            self::Closed => 'Cerrada',
            self::Cancelled => 'Cancelada',
        };
    }
}
