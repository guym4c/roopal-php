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

        $matches = [];

        // invoice id
        $fileName = pathinfo($pathToPdf, PATHINFO_FILENAME);
        $matched = preg_match('/[a-f0-9]{8}[-_][a-f0-9]{4}[-_]4[a-f0-9]{3}[-_][89ab][a-f0-9]{3}[-_][a-f0-9]{12}/', $fileName, $matches);
        if ($matched) {
            $this->id = $matches[0];
        } else {
            $this->id = Uuid::uuid4()->toString();
        }

        $shiftsStart = -1;
        $shiftsFinish = -1;
        $adjustmentsStart = -1;
        $adjustmentsFinish = -1;
        $tips = null;

        foreach ($pdf as $i => $line) {

            // rider
            $matched = preg_match('/^Pay to: ([A-Za-zÀ-ÖØ-öø-ÿ\s\-]+)$/', $line, $matches);
            if ($matched) {
                $this->rider = new Rider($matches[1]);
            }

            // Invoice dates
            $matched = preg_match('/^Services provided - ([0-9]+[a-zA-Z\s]+[0-9]+) - ([0-9]+[a-zA-Z\s]+[0-9]+)$/', $line, $matches);
            if ($matched) {
                $this->dateFrom = new DateTime($matches[1]);
                $this->dateTo = new DateTime($matches[2]);
            }

            if (preg_match('/Worked Orders Delivered Total/', $line)) {
                $shiftsStart = $i + 1;
            }

            if ($line == 'Fee Adjustments') {
                $shiftsFinish = $i - 1;
            }

            if ($line == 'Category Note Amount') {
                $adjustmentsStart = $i + 1;
            }

            if (preg_match('/Total Adjustments/', $line)) {
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

        $this->parseShifts(array_slice($pdf, $shiftsStart, $shiftsFinish - $shiftsStart + 1));

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

            if (preg_match('/([a-zA-Z]+ [0-9]+ [a-zA-Z]+ [0-9]{4}) ([0-9]{2}:[0-9]{2}) ([0-9]{2}:[0-9]{2}) [0-9]\.[0-9]h ([0-9]+): .([[0-9]+\.[0-9]+)/', $shifts[$i], $matches)) {
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

            if (preg_match('/[0-9]+\.[0-9]+$/', $adjustments[$i], $matches)) {
                $this->adjustments[] = new Adjustment(
                    $matches[0],
                    preg_replace('/.[0-9]+\.[0-9]+$/', '', $adjustments[$i]));
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
                    'in'    => $this->getDateFrom()->format(DATE_ATOM),
                    'out'   => $this->getDateTo()->format(DATE_ATOM),
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
     * @param string $outputPath
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