<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\ListUsers;

class ListUsers extends ListUsers
{
    protected static string $resource = UserResource::class;
}
