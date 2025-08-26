<?php

namespace App\Enums;

enum CategoryRequestStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function isFinal(): bool
    {
        return in_array($this, [self::APPROVED, self::REJECTED], true);
    }
}
