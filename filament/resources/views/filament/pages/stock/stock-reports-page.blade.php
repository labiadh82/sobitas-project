<x-filament-panels::page>
    @php $reports = $this->getReports(); @endphp

    <div class="grid gap-6 md:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">Valeur du stock par catégorie (top 10)</x-slot>
            <ul class="divide-y divide-gray-200 dark:divide-white/10">
                @forelse($reports['value_by_category'] as $row)
                    <li class="flex justify-between py-2 text-sm">
                        <span>{{ $row->name }}</span>
                        <span class="font-medium">{{ number_format($row->value ?? 0, 0, ',', ' ') }} DT</span>
                    </li>
                @empty
                    <li class="py-4 text-gray-500">Aucune donnée</li>
                @endforelse
            </ul>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Résumé alertes</x-slot>
            <div class="space-y-2">
                <p><strong>Rupture / indisponible :</strong> {{ $reports['out_of_stock_count'] }} produits</p>
                <p><strong>Stock faible :</strong> {{ $reports['low_stock_count'] }} produits</p>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
