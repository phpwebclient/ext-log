<?php

declare(strict_types=1);

namespace Webclient\Extension\Log\IdGenerator;

interface IdGenerator
{

    public function generate(): string;
}
