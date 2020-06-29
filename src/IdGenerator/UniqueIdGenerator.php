<?php

declare(strict_types=1);

namespace Webclient\Extension\Log\IdGenerator;

final class UniqueIdGenerator implements IdGenerator
{

    public function generate(): string
    {
        return uniqid();
    }
}
