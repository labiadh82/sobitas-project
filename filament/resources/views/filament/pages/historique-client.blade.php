<x-filament-panels::page>
    {{-- Search bar at top (same as backend) --}}
    <div class="mb-6">
        <form wire:submit="search" class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10 p-6">
            <label class="fi-fo-field-wrp-label block text-sm font-semibold text-gray-950 dark:text-white mb-3">
                Chercher l'historique de votre Client
            </label>
            <div class="flex flex-wrap items-end gap-4">
                <div class="min-w-0 flex-1">
                    <input
                        type="tel"
                        wire:model="tel"
                        placeholder="Numéro de téléphone"
                        class="fi-input block w-full rounded-lg border-gray-300 shadow-sm outline-none transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white sm:text-sm"
                        inputmode="numeric"
                    />
                </div>
                <button
                    type="submit"
                    class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus:ring-2 rounded-lg fi-size-md fi-btn-color-success gap-1.5 px-4 py-2.5 text-sm inline-grid shadow-sm bg-success-600 text-white hover:bg-success-500 focus:ring-success-500/50 fi-ac-btn-action"
                >
                    <x-filament::icon icon="heroicon-o-magnifying-glass" class="w-5 h-5" />
                    Chercher
                </button>
            </div>
        </form>
    </div>

    {{-- Results --}}
    @if($tel !== null && $tel !== '')
        @if($clients->isEmpty())
            <x-filament::section>
                <p class="text-gray-500 dark:text-gray-400">Aucun client trouvé pour ce numéro.</p>
            </x-filament::section>
        @else
            @foreach($clients as $client)
                <x-filament::section class="mb-6" :heading="'Client : ' . e($client->name ?? '—')">
                    <div class="mb-4">
                        <a
                            href="{{ $this->getClientEditUrl($client) }}"
                            class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus:ring-2 rounded-lg fi-size-sm fi-btn-color-primary gap-1.5 px-3 py-2 text-sm inline-grid fi-ac-btn-action"
                        >
                            Voir la fiche client
                        </a>
                    </div>

                    <div class="space-y-4">
                        @php
                            $commandes = $this->getCommandes($client);
                            $tickets = $this->getTickets($client);
                            $factureTvas = $this->getFactureTvas($client);
                        @endphp

                        @if($commandes->isNotEmpty())
                            <div>
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Commandes ({{ $commandes->count() }})</h4>
                                <ul class="divide-y divide-gray-200 dark:divide-white/10 text-sm">
                                    @foreach($commandes as $c)
                                        <li class="py-2 flex justify-between">
                                            <span>{{ $c->numero ?? '—' }}</span>
                                            <span>{{ $c->created_at?->format('d/m/Y') ?? '—' }} · {{ number_format($c->prix_ttc ?? 0, 2, ',', ' ') }} DT</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if($tickets->isNotEmpty())
                            <div>
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Tickets ({{ $tickets->count() }})</h4>
                                <ul class="divide-y divide-gray-200 dark:divide-white/10 text-sm">
                                    @foreach($tickets as $t)
                                        <li class="py-2 flex justify-between">
                                            <span>{{ $t->numero ?? '—' }}</span>
                                            <span>{{ $t->created_at?->format('d/m/Y') ?? '—' }} · {{ number_format($t->prix_ttc ?? 0, 2, ',', ' ') }} DT</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if($factureTvas->isNotEmpty())
                            <div>
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Factures TVA ({{ $factureTvas->count() }})</h4>
                                <ul class="divide-y divide-gray-200 dark:divide-white/10 text-sm">
                                    @foreach($factureTvas as $f)
                                        <li class="py-2 flex justify-between">
                                            <span>{{ $f->numero ?? '—' }}</span>
                                            <span>{{ $f->created_at?->format('d/m/Y') ?? '—' }} · {{ number_format($f->prix_ttc ?? 0, 2, ',', ' ') }} DT</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if($commandes->isEmpty() && $tickets->isEmpty() && $factureTvas->isEmpty())
                            <p class="text-gray-500 dark:text-gray-400 text-sm">Aucune commande, ticket ou facture pour ce client.</p>
                        @endif
                    </div>
                </x-filament::section>
            @endforeach
        @endif
    @endif
</x-filament-panels::page>
