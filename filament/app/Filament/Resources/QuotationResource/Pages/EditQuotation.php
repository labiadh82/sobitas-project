<?php

namespace App\Filament\Resources\QuotationResource\Pages;

use App\Filament\Resources\CommandeResource;
use App\Filament\Resources\QuotationResource;
use App\Models\DetailsQuotation;
use App\Models\Product;
use App\Services\DocumentConversion\QuotationConversionService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditQuotation extends EditRecord
{
    protected static string $resource = QuotationResource::class;

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
        $actions = [
            Actions\Action::make('convertToOrder')
                ->label('Transformer en commande')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('success')
                ->visible(fn () => ! $this->record->commandes()->exists())
                ->requiresConfirmation()
                ->modalHeading('Créer une commande à partir de ce devis')
                ->modalSubmitActionLabel('Créer la commande')
                ->action(function (QuotationConversionService $service) {
                    $commande = $service->convertToOrder($this->record);
                    Notification::make()
                        ->title('Commande créée')
                        ->body('Commande #' . $commande->numero . ' a été créée.')
                        ->success()
                        ->send();
                    $this->redirect(CommandeResource::getUrl('edit', ['record' => $commande]));
                }),
            Actions\Action::make('print')
                ->label('Imprimer')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->modalHeading('Aperçu d\'impression')
                ->modalContent(fn () => view('filament.components.print-modal', [
                    'printUrl' => route('quotations.print', ['quotation' => $this->record->id]),
                    'title' => 'Devis ' . $this->record->numero,
                ]))
                ->modalSubmitAction(false),
            Actions\DeleteAction::make(),
        ];

        return array_values($actions);
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
