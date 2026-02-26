<x-filament-widgets::widget>
    <x-filament::section class="client-historique-search-section">
        <form wire:submit="searchHistorique" class="flex flex-wrap items-end gap-4">
            <div class="min-w-0 flex-1">
                <label for="historique-tel" class="fi-fo-field-wrp-label inline-flex text-sm font-semibold text-gray-950 dark:text-white mb-1.5">
                    Chercher l'historique de votre Client
                </label>
                <input
                    id="historique-tel"
                    type="tel"
                    wire:model="tel"
                    placeholder="Numéro de téléphone"
                    class="fi-input block w-full rounded-lg border-gray-300 shadow-sm outline-none transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 disabled:bg-gray-50 disabled:text-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-white dark:focus:border-primary-500 dark:disabled:bg-white/5 dark:disabled:text-gray-400 sm:text-sm"
                    inputmode="numeric"
                />
            </div>
            <button
                type="submit"
                class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus:ring-2 rounded-lg fi-size-md fi-btn-color-success gap-1.5 px-4 py-2.5 text-sm inline-grid shadow-sm bg-success-600 text-white hover:bg-success-500 focus:ring-success-500/50 dark:bg-success-500 dark:hover:bg-success-400 dark:focus:ring-success-400/50 fi-ac-btn-action"
            >
                <x-filament::icon icon="heroicon-o-magnifying-glass" class="w-5 h-5" />
                Chercher
            </button>
        </form>
    </x-filament::section>
</x-filament-widgets::widget>
