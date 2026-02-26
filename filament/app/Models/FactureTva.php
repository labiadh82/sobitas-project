<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FactureTva extends Model
{
    protected $table = 'facture_tvas';

    protected $guarded = ['id'];

    protected $casts = [
        'date_facture' => 'date',
        'prix_total' => 'float',
        'tva' => 'float',
        'timbre' => 'float',
        'status' => InvoiceStatus::class,
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function facture(): BelongsTo
    {
        return $this->belongsTo(Facture::class, 'facture_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailsFactureTva::class, 'facture_tva_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'facture_tva_id');
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class, 'facture_tva_id');
    }
}
