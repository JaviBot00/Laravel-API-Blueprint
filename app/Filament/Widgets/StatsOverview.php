<?php

namespace App\Filament\Widgets;

use App\Models\ApiRequestLog;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use OwenIt\Auditing\Models\Audit;
use Spatie\Permission\Models\Role;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $callsToday     = ApiRequestLog::whereDate('created_at', today())->count();
        $callsYesterday = ApiRequestLog::whereDate('created_at', today()->subDay())->count();
        $trend          = $callsYesterday > 0
            ? round((($callsToday - $callsYesterday) / $callsYesterday) * 100, 1)
            : 0;

        $avgMs     = (int) ApiRequestLog::whereDate('created_at', today())->avg('response_time');
        $errors    = ApiRequestLog::whereDate('created_at', today())->where('status_code', '>=', 400)->count();

        return [
            Stat::make('Usuarios totales', User::count())
                ->description(Role::count() . ' roles definidos')
                ->icon('heroicon-o-users')
                ->color('primary'),

            Stat::make('Llamadas API hoy', number_format($callsToday))
                ->description(($trend >= 0 ? '+' : '') . $trend . '% vs ayer')
                ->icon('heroicon-o-arrow-path')
                ->color($trend >= 0 ? 'success' : 'warning'),

            Stat::make('Tiempo medio respuesta', $avgMs . ' ms')
                ->description('Hoy · todos los endpoints')
                ->icon('heroicon-o-clock')
                ->color($avgMs < 200 ? 'success' : 'warning'),

            Stat::make('Errores hoy', $errors)
                ->description('Respuestas HTTP 4xx / 5xx')
                ->icon('heroicon-o-exclamation-triangle')
                ->color($errors === 0 ? 'success' : 'danger'),

            Stat::make('Auditorías hoy', Audit::whereDate('created_at', today())->count())
                ->description('Cambios registrados en modelos')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('info'),
        ];
    }
}
