<?php

namespace App\Services;

class GstCalculationService
{
    const INTRA_STATE = 'intra';
    const INTER_STATE = 'inter';
    const EXPORT      = 'export';
    const SEZ         = 'sez';

    public function calculate(
        float  $taxableValue,
        float  $gstRate,
        float  $cessRate,
        string $supplyType
    ): array {
        $result = [
            'taxable_value' => round($taxableValue, 2),
            'cgst_rate'     => 0, 'cgst_amount' => 0,
            'sgst_rate'     => 0, 'sgst_amount' => 0,
            'igst_rate'     => 0, 'igst_amount' => 0,
            'cess_rate'     => $cessRate,
            'cess_amount'   => 0,
            'total'         => 0,
        ];

        match ($supplyType) {
            self::INTRA_STATE => $this->applyIntra($result, $taxableValue, $gstRate),
            self::INTER_STATE => $this->applyInter($result, $taxableValue, $gstRate),
            self::EXPORT      => null,
            self::SEZ         => $this->applyInter($result, $taxableValue, $gstRate),
        };

        if ($cessRate > 0) {
            $result['cess_amount'] = round($taxableValue * $cessRate / 100, 2);
        }

        $result['total'] = round(
            $taxableValue
            + $result['cgst_amount']
            + $result['sgst_amount']
            + $result['igst_amount']
            + $result['cess_amount'],
            2
        );

        return $result;
    }

    private function applyIntra(array &$r, float $val, float $rate): void
    {
        $r['cgst_rate']   = $rate / 2;
        $r['sgst_rate']   = $rate / 2;
        $r['cgst_amount'] = round($val * ($rate / 2) / 100, 2);
        $r['sgst_amount'] = round($val * ($rate / 2) / 100, 2);
    }

    private function applyInter(array &$r, float $val, float $rate): void
    {
        $r['igst_rate']   = $rate;
        $r['igst_amount'] = round($val * $rate / 100, 2);
    }

    public function determineSupplyType(
        string $sellerStateCode,
        string $buyerStateCode,
        bool   $isExport = false,
        bool   $isSez    = false
    ): string {
        if ($isExport) return self::EXPORT;
        if ($isSez)    return self::SEZ;
        return $sellerStateCode === $buyerStateCode
            ? self::INTRA_STATE
            : self::INTER_STATE;
    }

    public function reversal(array $originalCalc): array
    {
        return array_map(fn($v) => is_numeric($v) ? -abs($v) : $v, $originalCalc);
    }
}