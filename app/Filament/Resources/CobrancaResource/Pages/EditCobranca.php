<?php

namespace App\Filament\Resources\CobrancaResource\Pages;

use App\Filament\Resources\CobrancaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCobranca extends EditRecord
{
    protected static string $resource = CobrancaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
