<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AnalyticsSummaryExport implements FromArray, WithHeadings
{
    /**
     * @param  array<int, array<string, mixed>>  $data
     */
    public function __construct(protected array $data = []) {}

    /**
     * @return array<int, mixed>
     */
    public function array(): array
    {
        return $this->data;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Department / Role',
            'Members',
            'Total Tasks',
            'Completed Tasks',
            'Overdue Tasks',
            'Completion Rate (%)',
            'SLA Compliance (%)',
        ];
    }
}
