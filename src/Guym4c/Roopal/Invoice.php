<?php

namespace Guym4c\Roopal;

use DateTime;
use Guym4c\Roopal\InvoiceLine;
use Guym4c\Roopal\InvoiceLine\Adjustment;
use Guym4c\Roopal\InvoiceLine\Shift;
use InvalidArgumentException;
use League\Csv\Writer;
use Ramsey\Uuid\Uuid;
use \Smalot\PdfParser as Pdf;
use SplTempFileObject;

class Invoice {

    const CSV_DATETIME_FORMAT = 'Y-m-d H:i:s';

    /** @var string $id */
    private $id;

    /** @var Rider $rider */
    private $rider;

    /** @var DateTime $dateFrom */
    private $dateFrom;

    /** @var DateTime $dateTo */
    private $dateTo;

    /** @var Shift[] $shifts */
    private $shifts;

    /** @var InvoiceLine\Adjustment[] $adjustments */
    private $adjustments;

    /** @var bool $anonymised */
    private $anonymised;

    /**
     * Invoice constructor.
     * @param string $pathToPdf
     * @param bool $anonymise
     * @throws \Exception
     */
    public function __construct(string $pathToPdf, bool $anonymise = false) {

        $this->anonymised = $anonymise;

        if (strtolower(pathinfo($pathToPdf, PATHINFO_EXTENSION)) != 'pdf') {
            throw new InvalidArgumentException("File provided is not a PDF");
        }

        $pdf = (new Pdf\Parser())->parseFile($pathToPdf)->getText();
        $pdf = explode("\n", $pdf);

        $this->id = Uuid::uuid4()->toString();

        $shiftsStart = -1;
        $shiftsFinish = -1;
        $adjustmentsStart = -1;
        $adjustmentsFinish = -1;
        $tips = null;

        $matches = [];
        foreach ($pdf as $i => $line) {

            // rider
            $matched = preg_match('/^Pay to: ([A-Za-zÀ-ÖØ-öø-ÿ\s\-]+)$/', $line, $matches);
            if ($matched) {
                $this->rider = new Rider($matches[1]);
            }

            // Invoice dates
            $matched = preg_match('/^Services? (?:provided|Hours) - ([0-9]+[a-zA-Z\s]+[0-9]+) - ([0-9]+[a-zA-Z\s]+[0-9]+)/', $line, $matches);
            if ($matched) {
                $this->dateFrom = new DateTime($matches[1]);
                $this->dateTo = new DateTime($matches[2]);
            }

            if (preg_match('/Orders\s+Delivered\s+Total/', $line)) {
                $shiftsStart = $i + 1;
            }

            if (preg_match( '/(?:Fee|Payment)\s+Adjustments/', $line)) {
                $shiftsFinish = $i - 1;
            }

            if (preg_match('/Category\s+Note\s+Amount/', $line)) {
                $adjustmentsStart = $i + 1;
            }

            if (preg_match('/Total\s+Adjustments/', $line)) {
                $adjustmentsFinish = $i - 1;
            }

            if (preg_match('/Tips/', $line)) {
                $tips = $i;
            }
        }

        if ($shiftsFinish == -1) {
            $shiftsFinish = array_search('Summary', $pdf) - 1;
        }

        $this->shifts = [];
        $this->adjustments = [];

        if ($shiftsStart > 0 &&
            $shiftsFinish > 0) {
            $this->parseShifts(array_slice($pdf, $shiftsStart, $shiftsFinish - $shiftsStart + 1));
        }

        $tips = $tips == null ? null : $pdf[$tips];
        $this->parseAdjustments(array_slice($pdf, $adjustmentsStart, $adjustmentsFinish - $adjustmentsStart + 1), $tips);
    }

    /**
     * @param array $shifts
     * @throws \Exception
     */
    private function parseShifts(array $shifts): void {

        $matches = [];
        for ($i = 0; $i < count($shifts); $i++) {

            if (preg_match('/([a-zA-Z]+\s+[0-9]+\s+[a-zA-Z]+\s+[0-9]{4})\s+([0-9]{2}:[0-9]{2})\s+([0-9]{2}:[0-9]{2})\s+[0-9]\.[0-9]h\s+([0-9]+):\s+.([[0-9]+\.[0-9]+)/', $shifts[$i], $matches)) {
                $this->shifts[] = new Shift(
                    new DateTime($matches[1] . ' ' . $matches[2]),
                    new DateTime($matches[1] . ' ' . $matches[3]),
                    $matches[5],
                    $matches[4]);
            }
        }
    }

    /**
     * @param array $adjustments
     * @param string|null $tips
     */
    private function parseAdjustments(array $adjustments, string $tips = null): void {

        $adjustments = array_merge($adjustments, [$tips]);

        $matches = [];
        for ($i = 0; $i < count($adjustments); $i++) {

            if (preg_match('/-?[0-9]+\.[0-9]{2}/', $adjustments[$i], $matches)) {
                $amount = (float) $matches[0];
                if ($matches[0] != 0) {
                    $this->adjustments[] = new Adjustment(
                        $amount,
                        preg_replace('/.[0-9]+\.[0-9]{2}/', '', $adjustments[$i]));
                }
            } else if ($i + 1 < count($adjustments)) {
                $adjustments[$i + 1] = $adjustments[$i] . ' ' . $adjustments[$i + 1];
            }
        }
    }

    /**
     * @return string
     */
    public function getId(): string {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void {
        $this->id = $id;
    }

    /**
     * @return Rider
     */
    public function getRider(): Rider {
        return $this->rider;
    }

    /**
     * @param Rider $rider
     */
    public function setRider(Rider $rider): void {
        $this->rider = $rider;
    }

    /**
     * @return DateTime
     */
    public function getDateFrom(): DateTime {
        return $this->dateFrom;
    }

    /**
     * @param DateTime $dateFrom
     */
    public function setDateFrom(DateTime $dateFrom): void {
        $this->dateFrom = $dateFrom;
    }

    /**
     * @return DateTime
     */
    public function getDateTo(): DateTime {
        return $this->dateTo;
    }

    /**
     * @param DateTime $dateTo
     */
    public function setDateTo(DateTime $dateTo): void {
        $this->dateTo = $dateTo;
    }

    /**
     * @return Shift[]
     */
    public function getShifts(): array {
        return $this->shifts;
    }

    /**
     * @param Shift[] $shifts
     */
    public function setShifts(array $shifts): void {
        $this->shifts = $shifts;
    }

    /**
     * @return Adjustment[]
     */
    public function getAdjustments(): array {
        return $this->adjustments;
    }

    /**
     * @param Adjustment[] $adjustments
     */
    public function setAdjustments(array $adjustments): void {
        $this->adjustments = $adjustments;
    }

    /**
     * @return bool
     */
    public function isAnonymised(): bool {
        return $this->anonymised;
    }

    /**
     * @param bool $anonymised
     */
    public function setAnonymised(bool $anonymised): void {
        $this->anonymised = $anonymised;
    }

    /**
     * @return array
     */
    public function toArray(): array {
        $a = [];
        foreach ($this->getShifts() as $shift) {
            $a[] = array_merge($this->getMetadataArray(),
                $shift->toArray());
        }

        foreach ($this->getAdjustments() as $adjustment) {
            $a[] = array_merge($this->getMetadataArray(),
                $adjustment->toArray(),
                [
                    'drops' => 0,
                    'in'    => $this->getDateFrom()->format(self::CSV_DATETIME_FORMAT),
                    'out'   => $this->getDateTo()->format(self::CSV_DATETIME_FORMAT),
                ]);
        }

        return $a;
    }

    /**
     * @return array
     */
    private function getMetadataArray(): array {
        return array_merge(['invoice' => $this->getId()],
            $this->getRiderData());
    }

    /**
     * @return array
     */
    private function getRiderData(): array {
        $rider = $this->getRider();
        $a['rider_anonymised'] = $rider->getAnonymisedName();
        if (!$this->anonymised) {
            $a['rider_pii'] = $rider->getName();
        }
        return $a;
    }

    /**
     * @param Invoice[] $invoices
     * @return string
     * @throws \League\Csv\CannotInsertRecord
     */
    public static function toCsv(array $invoices): string {
        $csv = Writer::createFromFileObject(new SplTempFileObject());
        $csv->insertOne(array_keys($invoices[0]->toArray()[0]));
        foreach ($invoices as $invoice) {
            $csv->insertAll($invoice->toArray());
        }
        return $csv->getContent();
    }
}