<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use App\Models\Ticket;
use Filament\Resources\Pages\CreateRecord;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $nb = Ticket::whereYear('created_at', date('Y'))->count() + 1;
        $nb = str_pad($nb, 4, '0', STR_PAD_LEFT);
        $data['numero'] = date('Y') . '/' . $nb;

        $type = $data['type'] ?? Ticket::TYPE_TICKET_CAISSE;
        if ($type === Ticket::TYPE_TICKET_CAISSE) {
            $data['commande_id'] = null;
        }
        if ($type === Ticket::TYPE_BON_LIVRAISON && ! empty($data['commande_id'])) {
            $commande = \App\Models\Commande::find($data['commande_id']);
            if ($commande && $commande->user_id) {
                $data['client_id'] = $commande->user_id;
            }
        }

        return $data;
    }
}
