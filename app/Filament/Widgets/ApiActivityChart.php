<?php

namespace App\Filament\Widgets;

use App\Models\ApiRequestLog;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ApiActivityChart extends ChartWidget
{
    protected static ?string $heading = 'Llamadas a la API — últimos 14 días';
    protected static ?int    $sort    = 2;

    protected function getData(): array
    {
        // Una sola query agrupa totales y errores por día
        $logs = ApiRequestLog::select(
                DB::raw('DATE(created_at) as day'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as errors')
            )
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $labels = $totals = $errors = [];

        for ($i = 13; $i >= 0; $i--) {
            $date     = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('d/m');
            $totals[] = $logs[$date]->total  ?? 0;
            $errors[] = $logs[$date]->errors ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Total llamadas',
                    'data'            => $totals,
                    'borderColor'     => '#6366f1',
                    'backgroundColor' => 'rgba(99,102,241,0.1)',
                    'fill'            => true,
                    'tension'         => 0.4,
                ],
                [
                    'label'           => 'Errores (4xx/5xx)',
                    'data'            => $errors,
                    'borderColor'     => '#ef4444',
                    'backgroundColor' => 'rgba(239,68,68,0.1)',
                    'fill'            => true,
                    'tension'         => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string { return 'line'; }
}
