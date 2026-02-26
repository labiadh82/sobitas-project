@extends('print.layout')

@section('print-table')
<table class="print-table">
    <thead>
        <tr>
            <th style="width:5%">#</th>
            <th style="width:45%">Désignation</th>
            <th class="num" style="width:18%">Qté</th>
            <th class="num" style="width:16%">P.U</th>
            <th class="num" style="width:16%">Total</th>
        </tr>
    </thead>
    <tbody>
        @php $i = 1; @endphp
        @foreach ($details_ticket ?? [] as $d)
        <tr>
            <td>{{ $i++ }}</td>
            <td class="prod">{{ $d->product->designation_fr ?? '—' }}</td>
            <td class="num">{{ $d->quantite ?? $d->qte ?? 0 }}</td>
            <td class="num">{{ number_format((float)($d->prix_unitaire ?? 0), 3, ',', ' ') }} DT</td>
            <td class="num">{{ number_format((float)(($d->quantite ?? $d->qte ?? 0) * ($d->prix_unitaire ?? 0)), 3, ',', ' ') }} DT</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection
