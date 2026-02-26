<div class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
    @if(!empty($coordinate->logo_facture))
        @php
            $logoUrl = filter_var($coordinate->logo_facture, FILTER_VALIDATE_URL)
                ? $coordinate->logo_facture
                : \Illuminate\Support\Facades\Storage::url($coordinate->logo_facture);
        @endphp
        <img src="{{ $logoUrl }}" alt="Logo" class="h-16 object-contain mb-2" onerror="this.style.display='none'"/>
    @endif
    <p class="font-semibold text-gray-900 dark:text-white">{{ $coordinate->abbreviation ?? 'STE SOBITAS' }}</p>
    <p>{{ $coordinate->phone_1 ?? '' }} @if(!empty($coordinate->phone_2)) / {{ $coordinate->phone_2 }} @endif</p>
    <p>{{ $coordinate->adresse_fr ?? '' }}</p>
</div>
