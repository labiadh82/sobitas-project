<x-filament-panels::page>
    <form wire:submit="adjust">
        {{ $this->form }}

        <div class="mt-6 flex gap-2">
            <x-filament::button type="submit">
                Enregistrer l'ajustement
            </x-filament::button>
            <x-filament::button color="gray" tag="a" :href="\App\Filament\Pages\Stock\StockProductsPage::getUrl()">
                Retour aux produits
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
