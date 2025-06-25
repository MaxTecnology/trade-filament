<?php

namespace App\Filament\Resources\OfertaResource\Pages;

use App\Filament\Resources\OfertaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOfertas extends ListRecords
{
    protected static string $resource = OfertaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
