<?php

namespace Guym4c\Roopal;

class Rider {

    /** @var string $name */
    private $name;

    /**
     * Rider constructor.
     * @param $name
     */
    public function __construct(string $name) {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    public function getAnonymisedName(): string {
        return md5($this->name);
    }
}