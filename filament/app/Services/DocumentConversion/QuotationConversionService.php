<?php

namespace App\Services\DocumentConversion;

use App\Models\AuditLog;
use App\Models\Commande;
use App\Models\CommandeDetail;
use App\Models\Quotation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuotationConversionService
{
    public function __construct() {}

    /**
     * Convert a quotation to an order. Copies header + lines with snapshots, sets commande.quotation_id.
     */
    public function convertToOrder(Quotation $quotation): Commande
    {
        return DB::transaction(function () use ($quotation) {
            $quotation->load('details.product');

            $commande = new Commande();
            $commande->quotation_id = $quotation->id;
            $nb = Commande::whereYear('created_at', date('Y'))->count() + 1;
            $commande->numero = date('Y') . '/' . str_pad((string) $nb, 4, '0', STR_PAD_LEFT);
            $commande->etat = Commande::STATUS_NEW;

            $client = $quotation->client;
            if ($client) {
                $commande->nom = $client->name;
                $commande->prenom = '';
                $commande->email = $client->email ?? null;
                $commande->phone = $client->phone_1 ?? null;
                $commande->adresse1 = $client->adresse ?? null;
                $commande->user_id = $client->id;
            }
            $commande->prix_ht = (float) ($quotation->prix_ht ?? $quotation->prix_total ?? 0);
            $commande->prix_ttc = (float) ($quotation->prix_ttc ?? $quotation->prix_total ?? 0);
            $commande->remise = (float) ($quotation->remise ?? 0);
            $commande->frais_livraison = 0;
            $commande->save();

            foreach ($quotation->details as $line) {
                if (! $line->produit_id) {
                    continue;
                }
                $qte = (int) ($line->qte ?? $line->quantite ?? 1);
                $pu = (float) ($line->prix_unitaire ?? 0);
                $detail = new CommandeDetail();
                $detail->commande_id = $commande->id;
                $detail->produit_id = $line->produit_id;
                $detail->qte = $qte;
                $detail->prix_unitaire = $pu;
                $detail->prix_ht = $qte * $pu;
                $detail->prix_ttc = $qte * $pu;
                $detail->save();
            }

            $this->audit('quotation.converted_to_order', $quotation, [
                'quotation_id' => $quotation->id,
                'commande_id' => $commande->id,
            ]);

            return $commande->fresh(['details']);
        });
    }

    protected function audit(string $action, $entity, array $after = []): void
    {
        if (! class_exists(AuditLog::class)) {
            return;
        }
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_type' => $entity instanceof Quotation ? 'quotation' : get_class($entity),
            'entity_id' => $entity->id,
            'after' => $after,
        ]);
    }
}
