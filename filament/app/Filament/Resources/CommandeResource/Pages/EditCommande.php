<?php

namespace App\Filament\Resources\CommandeResource\Pages;

use App\Filament\Resources\CommandeResource;
use App\Filament\Resources\FactureResource;
use App\Services\DocumentConversion\OrderToBlService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCommande extends EditRecord
{
    protected static string $resource = CommandeResource::class;

    public function getSubheading(): ?string
    {
        $parts = [];
        if ($this->record->quotation_id) {
            $parts[] = 'Devis : #' . $this->record->quotation?->numero;
        }
        if ($this->record->factures()->exists()) {
            $parts[] = 'BL : #' . $this->record->factures->first()?->numero;
        }

        return $parts ? implode(' · ', $parts) : null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('createBl')
                ->label('Créer BL')
                ->icon('heroicon-o-document-text')
                ->color('success')
                ->visible(fn () => ! $this->record->factures()->exists())
                ->requiresConfirmation()
                ->modalHeading('Créer un bon de livraison à partir de cette commande')
                ->modalSubmitActionLabel('Créer le BL')
                ->action(function (OrderToBlService $service) {
                    $bl = $service->createBlFromOrder($this->record);
                    Notification::make()
                        ->title('Bon de livraison créé')
                        ->body('BL #' . $bl->numero . ' a été créé.')
                        ->success()
                        ->send();
                    $this->redirect(FactureResource::getUrl('edit', ['record' => $bl]));
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
