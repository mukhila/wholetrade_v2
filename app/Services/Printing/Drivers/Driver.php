<?php

namespace App\Services\Printing\Drivers;

interface Driver
{
    public function render(array $ops): string;
}
