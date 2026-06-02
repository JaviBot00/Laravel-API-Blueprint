<?php

namespace App\Filament\Widgets;

use App\Models\ApiRequestLog;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TopEndpointsChart extends ChartWidget
{
    protected ?string $heading = 'Top 8 endpoints más llamados';
    protected static ?int    $sort    = 3;

    protected function getData(): array
    {
        $data = ApiRequestLog::select(
                DB::raw("CONCAT(method, ' ', path) as endpoint"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('method', 'path')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return [
            'datasets' => [[
                'label'           => 'Llamadas',
                'data'            => $data->pluck('total')->toArray(),
                'backgroundColor' => [
                    '#6366f1','#8b5cf6','#06b6d4','#10b981',
                    '#f59e0b','#ef4444','#ec4899','#84cc16',
                ],
            ]],
            'labels' => $data->pluck('endpoint')->toArray(),
        ];
    }

    protected function getType(): string { return 'doughnut'; }
}
