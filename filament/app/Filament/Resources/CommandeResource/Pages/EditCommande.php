<?php

namespace App\Filament\Resources\CommandeResource\Pages;

use App\Filament\Resources\CommandeResource;
use App\Filament\Resources\FactureResource;
use App\Filament\Widgets\DocumentTimelineWidget;
use App\Services\DocumentConversion\OrderToBlService;
use Filament\Actions;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCommande extends EditRecord
{
    protected static string $resource = CommandeResource::class;

    public function getHeaderWidgets(): array
    {
        return [DocumentTimelineWidget::class];
    }

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
        $r = $this->record;
        return [
            Actions\Action::make('createBl')
                ->label('Créer BL (Bon de livraison)')
                ->icon('heroicon-o-document-text')
                ->color('success')
                ->size(Actions\Action::SizeLarge)
                ->visible(fn () => ! $this->record->factures()->exists())
                ->modalHeading('Conversion : Commande → Bon de livraison')
                ->modalDescription('Récapitulatif avant création du BL.')
                ->modalSubmitActionLabel('Confirmer la conversion')
                ->modalContent(fn () => view('filament.components.convert-wizard-summary', [
                    'sourceNumber' => $r->numero,
                    'client' => $r->getFullNameAttribute() ?: ($r->nom . ' ' . $r->prenom) ?: '—',
                    'date' => $r->created_at?->format('d/m/Y'),
                    'itemsCount' => $r->details->count(),
                    'totalTtc' => number_format((float)($r->prix_ttc ?? 0), 3, ',', ' ') . ' DT',
                ]))
                ->action(function (OrderToBlService $service) {
                    $bl = $service->createBlFromOrder($this->record);
                    Notification::make()
                        ->title('Conversion réussie')
                        ->body('BL #' . $bl->numero . ' a été créé.')
                        ->success()
                        ->send();
                    $this->redirect(FactureResource::getUrl('edit', ['record' => $bl]));
                }),
            Actions\Action::make('print')
                ->label('Imprimer')
                ->icon('heroicon-o-printer')
                ->size(Actions\Action::SizeLarge)
                ->url(fn () => $this->record->factures()->first() ? route('factures.print', ['facture' => $this->record->factures->first()->id]) : null)
                ->openUrlInNewTab()
                ->visible(fn () => $this->record->factures()->exists()),
            ActionGroup::make([
                Actions\DeleteAction::make(),
            ])->label('Autres actions')->icon('heroicon-o-ellipsis-vertical'),
        ];
    }
}
