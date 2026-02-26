<?php

namespace App\Filament\Resources\QuotationResource\Pages;

use App\Filament\Resources\CommandeResource;
use App\Filament\Resources\QuotationResource;
use App\Filament\Widgets\DocumentTimelineWidget;
use App\Models\DetailsQuotation;
use App\Models\Product;
use App\Services\DocumentConversion\QuotationConversionService;
use Filament\Actions;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditQuotation extends EditRecord
{
    protected static string $resource = QuotationResource::class;

    public function getHeaderWidgets(): array
    {
        return [DocumentTimelineWidget::class];
    }

    public function getHeading(): string
    {
        return 'Devis #' . $this->record->numero;
    }

    public function getSubheading(): ?string
    {
        $client = $this->record->client?->name ?? '—';
        $date = $this->record->created_at?->format('d/m/Y') ?? '—';
        $total = number_format((float) ($this->record->prix_ttc ?? 0), 2, ',', ' ') . ' TND';
        $statut = $this->getStatutLabel($this->record->statut ?? null);

        return "Client : {$client} · Date : {$date} · Total : {$total}" . ($statut ? " · Statut : {$statut}" : '');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['client_adresse'] = $this->record->client?->adresse ?? '';
        $data['client_phone'] = $this->record->client?->phone_1 ?? '';
        $data['details'] = $this->record->details->map(fn ($d) => [
            'produit_id' => $d->produit_id,
            'qte' => $d->qte ?? $d->quantite ?? 0,
            'prix_unitaire' => $d->prix_unitaire,
        ])->toArray();
        if (empty($data['details'])) {
            $data['details'] = [['produit_id' => null, 'qte' => 1, 'prix_unitaire' => 0]];
        }
        return $data;
    }

    protected function afterSave(): void
    {
        foreach ($this->record->details as $old) {
            Product::where('id', $old->produit_id)->increment('qte', $old->qte ?? $old->quantite ?? 0);
        }
        $this->record->details()->delete();
        $details = $this->form->getState()['details'] ?? [];
        foreach ($details as $row) {
            if (empty($row['produit_id'])) {
                continue;
            }
            $qte = (int) ($row['qte'] ?? 1);
            $prixUnitaire = (float) ($row['prix_unitaire'] ?? 0);
            DetailsQuotation::create([
                'quotation_id' => $this->record->id,
                'produit_id' => $row['produit_id'],
                'qte' => $qte,
                'quantite' => $qte,
                'prix_unitaire' => $prixUnitaire,
                'prix_ttc' => $qte * $prixUnitaire,
            ]);
            Product::where('id', $row['produit_id'])->decrement('qte', $qte);
        }
    }

    protected function getHeaderActions(): array
    {
        $r = $this->record;
        return [
            Actions\Action::make('convertToOrder')
                ->label('Transformer en commande')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('success')
                ->size(Actions\Action::SizeLarge)
                ->visible(fn () => ! $this->record->commandes()->exists())
                ->modalHeading('Conversion : Devis → Commande')
                ->modalDescription('Récapitulatif avant création de la commande.')
                ->modalSubmitActionLabel('Confirmer la conversion')
                ->modalContent(fn () => view('filament.components.convert-wizard-summary', [
                    'sourceNumber' => $r->numero,
                    'client' => $r->client?->name ?? '—',
                    'date' => $r->date_quotation?->format('d/m/Y') ?? $r->created_at?->format('d/m/Y'),
                    'itemsCount' => $r->details->count(),
                    'totalTtc' => number_format((float)($r->prix_ttc ?? $r->prix_total ?? 0), 3, ',', ' ') . ' DT',
                ]))
                ->action(function (QuotationConversionService $service) {
                    $commande = $service->convertToOrder($this->record);
                    Notification::make()
                        ->title('Conversion réussie')
                        ->body('Commande #' . $commande->numero . ' a été créée.')
                        ->success()
                        ->send();
                    $this->redirect(CommandeResource::getUrl('edit', ['record' => $commande]));
                }),
            Actions\Action::make('print')
                ->label('Imprimer')
                ->icon('heroicon-o-printer')
                ->size(Actions\Action::SizeLarge)
                ->modalHeading('Aperçu d\'impression')
                ->modalContent(fn () => view('filament.components.print-modal', [
                    'printUrl' => route('quotations.print', ['quotation' => $this->record->id]),
                    'title' => 'Devis ' . $this->record->numero,
                ]))
                ->modalSubmitAction(false),
            ActionGroup::make([
                Actions\Action::make('printDuplicate')->label('Dupliquer')->icon('heroicon-o-document-duplicate'),
                Actions\DeleteAction::make(),
            ])->label('Autres actions')->icon('heroicon-o-ellipsis-vertical'),
        ];
    }

    private function getStatutLabel(?string $statut): string
    {
        return match ($statut) {
            'brouillon' => 'Brouillon',
            'valide' => 'Validé',
            'refuse' => 'Refusé',
            'en_attente' => 'En attente',
            default => '',
        };
    }
}
