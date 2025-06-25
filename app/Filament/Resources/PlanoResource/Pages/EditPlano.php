<?php

namespace App\Filament\Resources\PlanoResource\Pages;

use App\Filament\Resources\PlanoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlano extends EditRecord
{
    protected static string $resource = PlanoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
