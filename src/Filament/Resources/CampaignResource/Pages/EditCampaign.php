<?php

namespace DanPascu\FilamentEmailCampaigns\Filament\Resources\CampaignResource\Pages;

use DanPascu\FilamentEmailCampaigns\Enums\CampaignStatus;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\CampaignResource;
use DanPascu\FilamentEmailCampaigns\Jobs\SendCampaignJob;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCampaign extends EditRecord
{
    protected static string $resource = CampaignResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['scheduled_at']) && $this->record->status === CampaignStatus::Draft) {
            $data['status'] = CampaignStatus::Scheduled->value;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendNow')
                ->label(__('filament-email-campaigns::translations.actions.send_now'))
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->isSendable())
                ->action(function (): void {
                    SendCampaignJob::dispatch($this->record->id);

                    Notification::make()
                        ->title(__('filament-email-campaigns::translations.notifications.campaign_sending_title'))
                        ->success()
                        ->send();

                    $this->redirect(CampaignResource::getUrl('index'));
                }),
            DeleteAction::make(),
        ];
    }
}
