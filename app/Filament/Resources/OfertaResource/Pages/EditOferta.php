<?php

namespace App\Filament\Resources\OfertaResource\Pages;

use App\Filament\Resources\OfertaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOferta extends EditRecord
{
    protected static string $resource = OfertaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
