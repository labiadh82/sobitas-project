<div class="space-y-2" x-data="{ barcode: '' }" x-init="$nextTick(() => $refs.barcodeInput?.focus())">
    <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-2">
        <span class="text-sm font-medium text-gray-950 dark:text-white">Scanner code à barre</span>
    </label>
    <input
        type="text"
        placeholder="barcode"
        x-model="barcode"
        x-ref="barcodeInput"
        @keydown.enter.prevent="
            if (barcode.trim()) {
                $wire.addProductByBarcode(barcode.trim());
                barcode = '';
                $nextTick(() => $refs.barcodeInput?.focus());
            }
        "
        class="fi-input block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:focus:border-primary-500 sm:text-sm"
        autofocus
    />
</div>
