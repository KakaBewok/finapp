<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithTitle;

class FinancialReportExport implements FromView, ShouldAutoSize, WithStyles, WithTitle
{
    protected array $data;
    protected string $period;
    protected string $reportType;

    public function __construct(array $data, string $period, string $reportType)
    {
        $this->data = $data;
        $this->period = $period;
        $this->reportType = $reportType;
    }

    public function view(): View
    {
        return view('exports.financial-report', [
            'data' => $this->data,
            'period' => $this->period,
            'reportType' => $this->reportType,
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['italic' => true]],
        ];
    }

    public function title(): string
    {
        return 'Laporan Keuangan';
    }
}
