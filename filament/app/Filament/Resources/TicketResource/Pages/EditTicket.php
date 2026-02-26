<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\FactureTvaResource;
use App\Filament\Resources\TicketResource;
use App\Filament\Widgets\DocumentTimelineWidget;
use App\Models\Ticket;
use App\Services\DocumentConversion\TicketToInvoiceService;
use Filament\Actions;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    public function getHeaderWidgets(): array
    {
        return [DocumentTimelineWidget::class];
    }

    public function getHeading(): string
    {
        return 'Ticket #' . $this->record->numero;
    }

    public function getSubheading(): ?string
    {
        $client = $this->record->client?->name ?? '—';
        $date = $this->record->created_at?->format('d/m/Y') ?? '—';
        $total = number_format((float) ($this->record->prix_ttc ?? 0), 2, ',', ' ') . ' TND';

        return "Client : {$client} · Date : {$date} · Total : {$total}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('createInvoice')
                ->label('Créer Facture TVA pour ce ticket')
                ->icon('heroicon-o-document-duplicate')
                ->color('success')
                ->visible(fn () => $this->record->isTicketCaisse() && $this->record->details()->exists())
                ->modalHeading('Créer une facture TVA (liée à ce ticket)')
                ->modalDescription('La facture sera liée à ce ticket de caisse et ne sera pas comptée une seconde fois dans le CA.')
                ->action(function (TicketToInvoiceService $service) {
                    $invoice = $service->createInvoiceFromTicket($this->record);
                    Notification::make()
                        ->title('Facture TVA créée')
                        ->body('Facture #' . $invoice->numero . ' créée (liée à ce ticket).')
                        ->success()
                        ->send();
                    $this->redirect(FactureTvaResource::getUrl('edit', ['record' => $invoice]));
                }),
            Actions\Action::make('print')
                ->label('Imprimer')
                ->icon('heroicon-o-printer')
                ->modalHeading('Aperçu d\'impression')
                ->modalContent(fn () => view('filament.components.print-modal', [
                    'printUrl' => route('tickets.print', ['ticket' => $this->record->id]),
                    'title' => 'Ticket ' . $this->record->numero,
                ]))
                ->modalSubmitAction(false),
            ActionGroup::make([
                Actions\DeleteAction::make(),
            ])->label('Autres actions')->icon('heroicon-o-ellipsis-vertical'),
        ];
    }
}
