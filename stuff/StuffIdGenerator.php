<?php

declare(strict_types=1);

namespace Stuff\Webclient\Extension\Log;

use Webclient\Extension\Log\IdGenerator\IdGenerator;

class StuffIdGenerator implements IdGenerator
{

    /**
     * @var string
     */
    private $id;

    /**
     * @var IdGenerator
     */
    private $generator;

    public function __construct(IdGenerator $generator)
    {
        $this->generator = $generator;
    }

    public function generate(): string
    {
        $this->id = $this->generator->generate();
        return $this->id;
    }

    public function lastId(): string
    {
        return $this->id;
    }
}
