<?php

namespace App\Filament\Resources\CobrancaResource\Pages;

use App\Filament\Resources\CobrancaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCobrancas extends ListRecords
{
    protected static string $resource = CobrancaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
