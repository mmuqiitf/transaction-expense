<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProfitLossExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    /**
     * @param array{
     *     from: string,
     *     to: string,
     *     months: list<array{key: string, label: string}>,
     *     rows: list<array{id: int, name: string, type: string, amounts: array<string, int>, total: int}>,
     *     totals: array{income: array<string, int>, expense: array<string, int>},
     *     net_income: array<string, int>
     * } $report
     */
    public function __construct(private readonly array $report) {}

    /**
     * @return list<list<int|string>>
     */
    public function array(): array
    {
        $monthKeys = array_column($this->report['months'], 'key');
        $monthLabels = array_column($this->report['months'], 'label');
        $rows = [
            ['Profit/Loss Report'],
            ['Period', $this->report['from'], $this->report['to']],
            [],
            ['Category', 'Classification', ...$monthLabels, 'Total'],
        ];

        foreach ($this->report['rows'] as $row) {
            $rows[] = [
                $row['name'],
                $row['type'],
                ...array_map(fn (string $month): int => $row['amounts'][$month], $monthKeys),
                $row['total'],
            ];
        }

        foreach (['income' => 'Total Income', 'expense' => 'Total Expense'] as $type => $label) {
            $rows[] = [
                $label,
                $type,
                ...array_map(fn (string $month): int => $this->report['totals'][$type][$month], $monthKeys),
                array_sum($this->report['totals'][$type]),
            ];
        }

        $rows[] = [
            'Net Income',
            'net',
            ...array_map(fn (string $month): int => $this->report['net_income'][$month], $monthKeys),
            array_sum($this->report['net_income']),
        ];

        return $rows;
    }

    public function title(): string
    {
        return 'Profit and Loss';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            4 => ['font' => ['bold' => true]],
            $sheet->getHighestRow() => ['font' => ['bold' => true]],
        ];
    }
}
