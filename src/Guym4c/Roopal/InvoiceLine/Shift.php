<?php

namespace Guym4c\Roopal\InvoiceLine;

use DateInterval;
use DateTime;

class Shift extends AbstractInvoiceLine {

    const CATEGORY_TYPE = 'shift';

    /** @var int $drops*/
    private $drops;

    /** @var DateTime $timeIn */
    private $timeIn;

    /** @var DateTime $timeOut */
    private $timeOut;

    /**
     * Shift constructor.
     * @param DateTime $timeIn
     * @param DateTime $timeOut
     * @param float $amount
     * @param int $drops
     */
    public function __construct(DateTime $timeIn, DateTime $timeOut, float $amount, int $drops) {
        parent::__construct($amount, self::CATEGORY_TYPE, self::CATEGORY_TYPE);
        $this->drops = $drops;
        $this->timeIn = $timeIn;
        $this->timeOut = $timeOut;
    }

    /**
     * @return DateTime
     */
    public function getTimeIn(): DateTime {
        return $this->timeIn;
    }

    /**
     * @param DateTime $timeIn
     */
    public function setTimeIn(DateTime $timeIn) {
        $this->timeIn = $timeIn;
    }

    /**
     * @return DateTime
     */
    public function getTimeOut(): DateTime {
        return $this->timeOut;
    }

    /**
     * @param DateTime $timeOut
     */
    public function setTimeOut(DateTime $timeOut) {
        $this->timeOut = $timeOut;
    }

    /**
     * @return DateInterval
     */
    public function getHours(): DateInterval {
        return $this->getTimeIn()
            ->diff($this->getTimeOut());
    }

    /**
     * @return int
     */
    public function getDrops(): int {
        return $this->drops;
    }

    /**
     * @param int $drops
     */
    public function setDrops(int $drops): void {
        $this->drops = $drops;
    }

    public function toArray(): array {
        return array_merge(parent::toArray(), [
            'drops' => $this->getDrops(),
            'in'    => $this->getTimeIn()->format(DateTime::ATOM),
            'out'   => $this->getTimeOut()->format(DateTime::ATOM),
        ]);
    }


}