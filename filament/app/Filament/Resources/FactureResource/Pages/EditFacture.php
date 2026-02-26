<?php

namespace App\Filament\Resources\FactureResource\Pages;

use App\Filament\Resources\FactureResource;
use App\Filament\Resources\FactureTvaResource;
use App\Models\DetailsFacture;
use App\Models\Product;
use App\Services\DocumentConversion\BlToInvoiceService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditFacture extends EditRecord
{
    protected static string $resource = FactureResource::class;

    public function getHeading(): string
    {
        return 'Bon de livraison #' . $this->record->numero;
    }

    public function getSubheading(): ?string
    {
        $client = $this->record->client?->name ?? '—';
        $date = $this->record->created_at?->format('d/m/Y') ?? '—';
        $total = number_format((float) ($this->record->prix_ttc ?? 0), 2, ',', ' ') . ' TND';
        $parts = ["Client : {$client}", "Date : {$date}", "Total : {$total}"];
        if ($this->record->commande_id) {
            $parts[] = 'Commande : #' . $this->record->commande?->numero;
        }
        if ($this->record->factureTvas()->exists()) {
            $parts[] = 'Facture TVA : #' . $this->record->factureTvas->first()?->numero;
        }

        return implode(' · ', $parts);
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
        $details = $this->form->getState()['details'] ?? [];
        foreach ($this->record->details as $old) {
            Product::where('id', $old->produit_id)->increment('qte', $old->qte ?? $old->quantite ?? 0);
        }
        $this->record->details()->delete();
        foreach ($details as $row) {
            if (empty($row['produit_id'])) {
                continue;
            }
            $qte = (int) ($row['qte'] ?? 1);
            $prixUnitaire = (float) ($row['prix_unitaire'] ?? 0);
            DetailsFacture::create([
                'facture_id' => $this->record->id,
                'produit_id' => $row['produit_id'],
                'qte' => $qte,
                'prix_unitaire' => $prixUnitaire,
                'prix_ttc' => $qte * $prixUnitaire,
            ]);
            Product::where('id', $row['produit_id'])->decrement('qte', $qte);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('convertToInvoice')
                ->label('Transformer en facture TVA')
                ->icon('heroicon-o-document-duplicate')
                ->color('success')
                ->visible(fn () => ! $this->record->factureTvas()->exists())
                ->requiresConfirmation()
                ->modalHeading('Créer une facture TVA à partir de ce BL')
                ->modalSubmitActionLabel('Créer la facture')
                ->action(function (BlToInvoiceService $service) {
                    $invoice = $service->createInvoiceFromBl($this->record);
                    Notification::make()
                        ->title('Facture TVA créée')
                        ->body('Facture #' . $invoice->numero . ' a été créée.')
                        ->success()
                        ->send();
                    $this->redirect(FactureTvaResource::getUrl('edit', ['record' => $invoice]));
                }),
            Actions\Action::make('print')
                ->label('Imprimer')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->modalHeading('Aperçu d\'impression')
                ->modalContent(fn () => view('filament.components.print-modal', [
                    'printUrl' => route('factures.print', ['facture' => $this->record->id]),
                    'title' => 'Bon de livraison ' . $this->record->numero,
                ]))
                ->modalSubmitAction(false),
            Actions\DeleteAction::make(),
        ];
    }
}
