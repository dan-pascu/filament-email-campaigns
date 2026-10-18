<?php

namespace DanPascu\FilamentEmailCampaigns\Filament\Actions;

use DanPascu\FilamentEmailCampaigns\Actions\ExportSubscribersActionHandler;
use DanPascu\FilamentEmailCampaigns\Models\EmailList;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ExportSubscribersAction
{
    public static function make(): Action
    {
        return Action::make('exportSubscribers')
            ->label(__('filament-email-campaigns::translations.actions.export_subscribers'))
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->action(function (EmailList $record): void {
                app(ExportSubscribersActionHandler::class)($record, Auth::user());

                Notification::make()
                    ->title(__('filament-email-campaigns::translations.notifications.export_started'))
                    ->success()
                    ->send();
            });
    }
}
