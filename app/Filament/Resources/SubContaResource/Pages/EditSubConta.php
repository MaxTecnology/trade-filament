<?php

namespace App\Filament\Resources\SubContaResource\Pages;

use App\Filament\Resources\SubContaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubConta extends EditRecord
{
    protected static string $resource = SubContaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
