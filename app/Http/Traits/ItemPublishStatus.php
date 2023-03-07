<?php

namespace App\Http\Traits;

trait ItemPublishStatus
{
    public function getPublishStatus($status): bool|string
    {
        return match ($status) {
            1 => 'requested',
            2 => true,
            default => false,
        };
    }
}
