<?php

namespace App\Filament\Admin\Resources\CartItems\Pages;

use App\Filament\Admin\Resources\CartItems\CartItemResource;
use App\Services\OrderService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListCartItems extends ListRecords
{
    protected static string $resource = CartItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('checkout')
                ->label('Satın Al')
                ->icon('heroicon-o-shopping-bag')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {

                    app(OrderService::class)->checkout(Auth::user());

                    Notification::make()
                        ->title('Sipariş başarıyla oluşturuldu.')
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl());
                }),
        ];
    }
}
