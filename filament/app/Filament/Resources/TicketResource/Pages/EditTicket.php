<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\FactureTvaResource;
use App\Filament\Resources\TicketResource;
use App\Filament\Widgets\DocumentTimelineWidget;
use Filament\Actions;
use Filament\Actions\ActionGroup;
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
                ->label('Créer facture TVA')
                ->icon('heroicon-o-document-duplicate')
                ->color('success')
                ->url(FactureTvaResource::getUrl('create'))
                ->openUrlInNewTab(),
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
