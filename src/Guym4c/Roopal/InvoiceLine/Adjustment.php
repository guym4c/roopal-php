<?php

namespace Guym4c\Roopal\InvoiceLine;

class Adjustment extends AbstractInvoiceLine {

    const CATEGORY = 'adjustment';

    /**
     * Adjustment constructor.
     * @param float $amount
     * @param string $type
     */
    public function __construct(float $amount, string $type) {
        parent::__construct($amount, self::CATEGORY, $type);
    }
}