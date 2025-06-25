<?php

namespace App\Filament\Resources\TipoContaResource\Pages;

use App\Filament\Resources\TipoContaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTipoConta extends EditRecord
{
    protected static string $resource = TipoContaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
