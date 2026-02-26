@php
    $company = $coordonnee ?? $company ?? null;
    $documentDate = $ticket->date_ticket ? \Carbon\Carbon::parse($ticket->date_ticket)->format('d/m/Y') : ($ticket->created_at?->format('d/m/Y') ?? '');
    $documentTime = $ticket->created_at?->format('H:i') ?? '';
    $subtotal = (float)($ticket->prix_ht ?? 0);
    $remise = (float)($ticket->remise ?? 0);
    $remisePct = (float)($ticket->pourcentage_remise ?? 0);
    $net = (float)($ticket->prix_ttc ?? $ticket->prix_total ?? 0);
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket {{ $ticket->numero ?? '' }}</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Consolas', 'Monaco', 'Courier New', monospace; font-size: 11px; line-height: 1.35; color: #000; background: #f1f5f9; min-height: 100vh; padding: 12px 0; }
        .receipt-toolbar { width: 72mm; margin: 0 auto 12px; display: flex; justify-content: space-between; align-items: center; }
        .receipt-toolbar .label { font-size: 11px; color: #64748b; }
        .receipt-toolbar .actions { display: flex; gap: 6px; }
        .receipt-btn { display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer; border: none; }
        .receipt-btn-primary { background: #000; color: #fff; }
        .receipt-btn-ghost { background: #fff; color: #333; border: 1px solid #ccc; }
        .receipt { width: 72mm; margin: 0 auto; padding: 4mm 3mm; background: #fff; color: #000; }
        .receipt-sep { border: none; border-bottom: 1px dotted #000; margin: 6px 0; }
        .receipt-sep--top { margin-top: 0; }
        .receipt-header { text-align: center; padding-bottom: 4px; }
        .receipt-logo { max-width: 60px; max-height: 24px; object-fit: contain; display: block; margin: 0 auto 4px; }
        .receipt-brand { font-size: 14px; font-weight: 700; margin: 0 0 2px 0; color: #000; }
        .receipt-welcome { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 4px 0; color: #000; }
        .receipt-company { font-size: 10px; line-height: 1.4; margin: 0; color: #000; }
        .receipt-meta-row { display: flex; justify-content: space-between; align-items: center; font-size: 10px; margin-top: 4px; }
        .receipt-numero { font-size: 11px; font-weight: 700; text-align: center; margin: 4px 0 0 0; color: #000; }
        .receipt-client-line { font-size: 10px; margin: 4px 0 0 0; color: #000; }
        .receipt-table { width: 100%; border-collapse: collapse; font-size: 10px; margin: 4px 0; }
        .receipt-table thead th { text-align: left; font-weight: 700; padding: 2px 0; color: #000; }
        .receipt-table thead th.th-qte { text-align: right; padding-right: 4px; }
        .receipt-table thead th.th-total { text-align: right; }
        .receipt-table thead tr.receipt-th-sep th { border-bottom: 1px dotted #000; padding-bottom: 2px; }
        .receipt-table tbody td { padding: 2px 0; vertical-align: top; color: #000; }
        .receipt-table tbody td.td-prod { text-align: left; max-width: 0; word-wrap: break-word; line-height: 1.3; }
        .receipt-table tbody td.td-num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; padding-left: 6px; }
        .receipt-totals { margin-top: 6px; }
        .receipt-tot-row { display: flex; justify-content: space-between; align-items: baseline; font-size: 10px; padding: 1px 0; color: #000; }
        .receipt-tot-row .amount { font-variant-numeric: tabular-nums; font-weight: 600; }
        .receipt-tot-row.final { margin-top: 4px; padding-top: 4px; border-top: 1px dotted #000; font-weight: 700; font-size: 11px; }
        .receipt-tot-row.final .amount { font-weight: 700; }
        .receipt-footer { text-align: center; margin-top: 8px; padding-top: 6px; }
        .receipt-thanks { font-size: 10px; margin: 0 0 4px 0; color: #000; }
        .receipt-website-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; margin: 0 0 2px 0; color: #000; }
        .receipt-website { font-size: 11px; font-weight: 700; margin: 0; color: #000; }
        @media print {
            body { background: #fff; padding: 0; font-size: 10px; }
            .no-print { display: none !important; }
            .receipt { width: 100%; max-width: none; margin: 0; padding: 2mm 0; box-shadow: none; }
            .receipt-table tr { break-inside: avoid; }
        }
    </style>
</head>
<body>
    @if (!request()->query('embed'))
    <div class="receipt-toolbar no-print">
        <span class="label">Aperçu</span>
        <div class="actions">
            <button type="button" onclick="window.print()" class="receipt-btn receipt-btn-primary">Imprimer</button>
            <button type="button" onclick="window.close()" class="receipt-btn receipt-btn-ghost">Fermer</button>
        </div>
    </div>
    @endif

    <div class="receipt" id="print-area">
        {{-- Header: logo, welcome, address, phone --}}
        <header class="receipt-header">
            @if ($company && !empty($company->logo_facture))
                @php
                    $logoUrl = filter_var($company->logo_facture, FILTER_VALIDATE_URL)
                        ? $company->logo_facture
                        : \Illuminate\Support\Facades\Storage::url($company->logo_facture);
                @endphp
                <img src="{{ $logoUrl }}" alt="" class="receipt-logo" onerror="this.style.display='none'">
            @endif
            <div class="receipt-brand">{{ $company->abbreviation ?? 'SOBITAS' }}</div>
            <p class="receipt-welcome">Bienvenue chez {{ $company->abbreviation ?? 'SOBITAS' }}</p>
            @if ($company)
            <div class="receipt-company">
                @if ($company->adresse_fr ?? null) Adresse: {{ $company->adresse_fr }}<br> @endif
                @if ($company->phone_1 ?? null) Tel: {{ $company->phone_1 }}@if ($company->phone_2 ?? null) / {{ $company->phone_2 }} @endif @endif
            </div>
            @endif
        </header>
        <hr class="receipt-sep receipt-sep--top">

        {{-- Meta: date left, time right; ticket number centered --}}
        <div class="receipt-meta-row">
            <span>{{ $documentDate }}</span>
            @if ($documentTime)<span>{{ $documentTime }}</span>@endif
        </div>
        <div class="receipt-numero">Ticket n°{{ $ticket->numero ?? '—' }}</div>
        @if ($ticket->client)
        <div class="receipt-client-line">Client: {{ $ticket->client->name ?? '—' }}</div>
        @endif
        <hr class="receipt-sep">

        {{-- Items: Produit | Qte | Totale --}}
        <table class="receipt-table">
            <thead>
                <tr>
                    <th>Produit</th>
                    <th class="th-qte">Qte</th>
                    <th class="th-total">Totale</th>
                </tr>
                <tr class="receipt-th-sep">
                    <th></th><th></th><th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($details_ticket ?? [] as $d)
                @php
                    $qte = (float)($d->qte ?? $d->quantite ?? 0);
                    $pu = (float)($d->prix_unitaire ?? 0);
                    $lineTotal = $d->prix_ttc ?? ($qte * $pu);
                @endphp
                <tr>
                    <td class="td-prod">{{ $d->product->designation_fr ?? '—' }}</td>
                    <td class="td-num">{{ number_format($qte, 0, ',', ' ') }}</td>
                    <td class="td-num">{{ number_format((float)$lineTotal, 3, ',', ' ') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <hr class="receipt-sep">

        {{-- Totals: Totale, Remise, %, final (Totale HT / Net à payer) --}}
        <div class="receipt-totals">
            <div class="receipt-tot-row">
                <span>Totale</span>
                <span class="amount">{{ number_format($subtotal, 3, ',', ' ') }} DT</span>
            </div>
            <div class="receipt-tot-row">
                <span>Remise</span>
                <span class="amount">{{ number_format($remise, 3, ',', ' ') }} DT</span>
            </div>
            <div class="receipt-tot-row">
                <span>Pourcentage remise %</span>
                <span class="amount">{{ number_format($remisePct, 1, ',', ' ') }}</span>
            </div>
            <div class="receipt-tot-row final">
                <span>Totale HT</span>
                <span class="amount">{{ number_format($net, 3, ',', ' ') }} DT</span>
            </div>
        </div>

        {{-- Footer --}}
        <hr class="receipt-sep">
        <footer class="receipt-footer">
            <p class="receipt-thanks">{{ $company->abbreviation ?? 'SOBITAS' }} vous remercie de votre visite</p>
            <hr class="receipt-sep">
            <div class="receipt-website-label">Notre site web</div>
            <div class="receipt-website">WWW.PROTEIN.TN</div>
        </footer>
    </div>
</body>
</html>
