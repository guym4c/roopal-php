<?php

namespace Guym4c\Roopal\InvoiceLine;

use Guym4c\Roopal\Rider;

abstract class AbstractInvoiceLine {

    /** @var float $amount */
    protected $amount;

    /** @var string $type */
    protected $category;

    /** @var string $type */
    protected $type;

    /**
     * AbstractInvoiceLine constructor.
     * @param float $amount
     * @param string $category
     * @param string $type
     */
    public function __construct(float $amount, string $category, string $type) {
        $this->amount = $amount;
        $this->category = $category;
        $this->type = $type;
    }

    /**
     * @return float
     */
    public function getAmount(): float {
        return $this->amount;
    }

    /**
     * @param float $amount
     */
    public function setAmount(float $amount) {
        $this->amount = $amount;
    }

    /**
     * @return string
     */
    public function getCategory(): string {
        return $this->category;
    }

    /**
     * @param string $category
     */
    public function setCategory(string $category): void {
        $this->category = $category;
    }

    /**
     * @return string
     */
    public function getType(): string {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void {
        $this->type = $type;
    }

    public function toArray(): array {
        return [
            'category'  => $this->getCategory(),
            'type'      => $this->getType(),
            'pay'       => $this->getAmount(),
        ];
    }
}