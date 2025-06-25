<?php

namespace App\Filament\Resources\ParcelamentoResource\Pages;

use App\Filament\Resources\ParcelamentoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditParcelamento extends EditRecord
{
    protected static string $resource = ParcelamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
