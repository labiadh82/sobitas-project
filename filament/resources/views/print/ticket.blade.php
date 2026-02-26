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
        @page { size: auto; margin: 10mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            font-size: 14px;
            line-height: 1.4;
            color: #1a1a1a;
            background: #f1f5f9;
            min-height: 100vh;
            padding: 24px 16px;
        }
        .ticket-toolbar {
            max-width: 340px;
            margin: 0 auto 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .ticket-toolbar .label { font-size: 13px; color: #64748b; }
        .ticket-toolbar .actions { display: flex; gap: 8px; }
        .ticket-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
        }
        .ticket-btn-primary { background: #ea580c; color: #fff; }
        .ticket-btn-primary:hover { background: #c2410c; }
        .ticket-btn-ghost { background: #fff; color: #475569; border: 1px solid #e2e8f0; }

        .ticket-sheet {
            max-width: 340px;
            margin: 0 auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            overflow: hidden;
            padding: 28px 24px;
        }

        .ticket-header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
        }
        .ticket-logo-wrap {
            margin-bottom: 12px;
        }
        .ticket-logo {
            max-width: 140px;
            max-height: 44px;
            object-fit: contain;
        }
        .ticket-brand {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #ea580c;
            margin: 0 0 4px 0;
        }
        .ticket-welcome {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #64748b;
            margin-bottom: 14px;
        }
        .ticket-company-meta {
            font-size: 12px;
            color: #475569;
            line-height: 1.6;
        }
        .ticket-company-meta a { color: #ea580c; text-decoration: none; }

        .ticket-meta {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px solid #f1f5f9;
            font-size: 13px;
            color: #64748b;
        }
        .ticket-numero {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 8px;
        }

        .ticket-client {
            margin-top: 16px;
            padding: 12px 16px;
            background: #f8fafc;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            text-align: left;
        }
        .ticket-client-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            margin-bottom: 4px;
        }
        .ticket-client-name {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
        }
        .ticket-client-details {
            font-size: 12px;
            color: #475569;
            margin-top: 6px;
            line-height: 1.5;
        }

        .ticket-table-wrap {
            margin: 20px 0;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #f1f5f9;
        }
        .ticket-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .ticket-table thead th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            text-align: left;
            padding: 12px 14px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid #e2e8f0;
        }
        .ticket-table thead th.th-qte,
        .ticket-table thead th.th-total { text-align: right; }
        .ticket-table tbody td {
            padding: 12px 14px;
            border-bottom: 1px solid #f8fafc;
        }
        .ticket-table tbody tr:last-child td { border-bottom: none; }
        .ticket-table tbody td.td-prod { font-weight: 600; color: #0f172a; }
        .ticket-table tbody td.td-num { text-align: right; font-variant-numeric: tabular-nums; font-weight: 500; color: #334155; }

        .ticket-totals {
            margin-top: 20px;
            padding: 18px 20px;
            background: linear-gradient(180deg, #fff7ed 0%, #ffedd5 100%);
            border-radius: 12px;
            border: 1px solid #fed7aa;
        }
        .ticket-tot-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            font-size: 13px;
            color: #475569;
        }
        .ticket-tot-row .amount { font-variant-numeric: tabular-nums; font-weight: 600; color: #334155; }
        .ticket-tot-row.net {
            margin-top: 12px;
            padding-top: 14px;
            border-top: 2px solid #ea580c;
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
        }
        .ticket-tot-row.net .amount { font-size: 18px; color: #c2410c; }

        .ticket-footer {
            text-align: center;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        .ticket-thanks {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 16px;
        }
        .ticket-website-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #94a3b8;
            margin-bottom: 4px;
        }
        .ticket-website {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: 0.02em;
        }

        @media print {
            body { background: #fff; padding: 0; }
            .no-print { display: none !important; }
            .ticket-sheet { box-shadow: none; max-width: 100%; }
        }
    </style>
</head>
<body>
    @if (!request()->query('embed'))
    <div class="ticket-toolbar no-print">
        <span class="label">Aperçu</span>
        <div class="actions">
            <button type="button" onclick="window.print()" class="ticket-btn ticket-btn-primary">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m3.84 1.86c-.899.96-2.099 1.44-3.3 1.44s-2.403-.48-3.3-1.44m3.3-1.44c.899.96 2.099 1.44 3.3 1.44s2.403-.48 3.3-1.44m-6.6-12.48c-.899.96-2.099 1.44-3.3 1.44s-2.403-.48-3.3-1.44M12 15.66h.01" /></svg>
                Imprimer
            </button>
            <button type="button" onclick="window.close()" class="ticket-btn ticket-btn-ghost">Fermer</button>
        </div>
    </div>
    @endif

    <div class="ticket-sheet" id="print-area">
        <header class="ticket-header">
            <div class="ticket-logo-wrap">
                @if ($company && !empty($company->logo_facture))
                    @php
                        $logoUrl = filter_var($company->logo_facture, FILTER_VALIDATE_URL)
                            ? $company->logo_facture
                            : \Illuminate\Support\Facades\Storage::url($company->logo_facture);
                    @endphp
                    <img src="{{ $logoUrl }}" alt="" class="ticket-logo" onerror="this.style.display='none'">
                @endif
            </div>
            <h1 class="ticket-brand">{{ $company->abbreviation ?? 'SOBITAS' }}</h1>
            <p class="ticket-welcome">Bienvenue chez {{ $company->abbreviation ?? 'SOBITAS' }}</p>
            @if ($company)
            <div class="ticket-company-meta">
                @if ($company->adresse_fr ?? null) {{ $company->adresse_fr }}<br> @endif
                @if ($company->phone_1 ?? null) Tél. {{ $company->phone_1 }}@if ($company->phone_2 ?? null) / {{ $company->phone_2 }} @endif @endif
            </div>
            @endif
            <div class="ticket-meta">
                <span>{{ $documentDate }} @if($documentTime) · {{ $documentTime }} @endif</span>
            </div>
            <div class="ticket-numero">Ticket n°{{ $ticket->numero ?? '—' }}</div>

            @if ($ticket->client)
            <div class="ticket-client">
                <div class="ticket-client-label">Client</div>
                <div class="ticket-client-name">{{ $ticket->client->name ?? '—' }}</div>
                @if ($ticket->client->adresse ?? $ticket->client->phone_1 ?? null)
                <div class="ticket-client-details">
                    @if ($ticket->client->adresse ?? null) {{ $ticket->client->adresse }}<br> @endif
                    @if ($ticket->client->phone_1 ?? null) Tél. {{ $ticket->client->phone_1 }} @endif
                </div>
                @endif
            </div>
            @endif
        </header>

        <div class="ticket-table-wrap">
            <table class="ticket-table">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th class="th-qte">Qté</th>
                        <th class="th-total">Total</th>
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
                        <td class="td-num">{{ number_format((float)$lineTotal, 3, ',', ' ') }} DT</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="ticket-totals">
            <div class="ticket-tot-row">
                <span>Totale</span>
                <span class="amount">{{ number_format($subtotal, 3, ',', ' ') }} DT</span>
            </div>
            <div class="ticket-tot-row">
                <span>Remise</span>
                <span class="amount">{{ number_format($remise, 3, ',', ' ') }} DT</span>
            </div>
            <div class="ticket-tot-row">
                <span>Pourcentage remise %</span>
                <span class="amount">{{ number_format($remisePct, 1, ',', ' ') }}</span>
            </div>
            <div class="ticket-tot-row net">
                <span>Net à payer</span>
                <span class="amount">{{ number_format($net, 3, ',', ' ') }} DT</span>
            </div>
        </div>

        <footer class="ticket-footer">
            <p class="ticket-thanks">{{ $company->abbreviation ?? 'SOBITAS' }} vous remercie de votre visite</p>
            <div class="ticket-website-label">Notre site web</div>
            <div class="ticket-website">WWW.PROTEIN.TN</div>
        </footer>
    </div>
</body>
</html>
