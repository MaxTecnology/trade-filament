<?php

namespace App\Filament\Resources\UsuarioResource\Pages;

use App\Filament\Resources\UsuarioResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUsuario extends CreateRecord
{
    protected static string $resource = UsuarioResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Usuário criado com sucesso!';
    }
}
