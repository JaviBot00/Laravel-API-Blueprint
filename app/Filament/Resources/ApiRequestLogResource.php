<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApiRequestLogResource\Pages;
use App\Models\ApiRequestLog;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ApiRequestLogResource extends Resource
{
    protected static ?string $model          = ApiRequestLog::class;
    protected static ?string $navigationIcon  = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Logs de API';
    protected static ?string $navigationGroup = 'Estadísticas';
    protected static ?int    $navigationSort  = 2;

    // Solo lectura: no se crean ni editan registros desde el panel.
    // Los registros los genera automáticamente el middleware LogApiRequest.
    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->label('Fecha'),

                Tables\Columns\BadgeColumn::make('method')
                    ->colors([
                        'success' => 'GET',
                        'primary' => 'POST',
                        'warning' => 'PUT',
                        'danger'  => 'DELETE',
                    ])
                    ->label('Método'),

                Tables\Columns\TextColumn::make('path')
                    ->searchable()
                    ->label('Endpoint'),

                Tables\Columns\BadgeColumn::make('status_code')
                    ->colors([
                        'success' => fn ($state) => $state >= 200 && $state < 300,
                        'warning' => fn ($state) => $state >= 300 && $state < 400,
                        'danger'  => fn ($state) => $state >= 400,
                    ])
                    ->label('Status'),

                Tables\Columns\TextColumn::make('response_time')
                    ->suffix(' ms')
                    ->sortable()
                    ->label('Tiempo'),

                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->label('Usuario')
                    ->default('—'),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('method')
                    ->options(['GET' => 'GET', 'POST' => 'POST', 'PUT' => 'PUT', 'DELETE' => 'DELETE']),

                Tables\Filters\SelectFilter::make('status_code')
                    ->options([
                        '200' => '200 OK',        '201' => '201 Created',
                        '401' => '401 Unauthorized', '403' => '403 Forbidden',
                        '404' => '404 Not Found',  '422' => '422 Unprocessable',
                        '500' => '500 Server Error',
                    ])
                    ->label('Código HTTP'),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('Desde'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Hasta'),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data['from'],  fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                        ->when($data['until'], fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
                    ),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s'); // Refresca automáticamente cada 30 segundos
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApiRequestLogs::route('/'),
        ];
    }
}
