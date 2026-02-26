{{-- Design system: Facturation ERP-lite — cards, tables, badges, spacing (see docs/DESIGN_SYSTEM.md) --}}
<style>
    /* Spacing scale 8/12/16/24 */
    .fi-section-content-ctn {
        border-radius: 12px;
        box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.06), 0 1px 2px -1px rgb(0 0 0 / 0.06);
        padding: 20px 24px;
    }
    .fi-ta-table {
        border-radius: 12px;
        overflow: hidden;
    }
    .fi-ta-table thead th {
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }
    .fi-ta-table tbody tr:hover {
        background-color: rgb(249 250 251 / 0.9);
    }
    .dark .fi-ta-table tbody tr:hover {
        background-color: rgb(30 41 59 / 0.5);
    }
    .fi-ta-table tbody tr:nth-child(even) {
        background-color: rgb(249 250 251 / 0.6);
    }
    .dark .fi-ta-table tbody tr:nth-child(even) {
        background-color: rgb(30 41 59 / 0.35);
    }
    .fi-ta-table td.fi-ta-col-total,
    .fi-ta-table td[data-money],
    .fi-ta-table th:has(+ th) .fi-ta-text-item {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }
    /* Primary actions: orange brand */
    .fi-btn-primary,
    .fi-btn-primary:hover {
        --tw-bg-opacity: 1;
        background-color: rgb(249 115 22 / var(--tw-bg-opacity));
    }
    /* Status badges */
    .badge-statut-brouillon { background-color: #6b7280; color: #fff; border-radius: 9999px; padding: 0.25rem 0.625rem; font-size: 0.75rem; font-weight: 500; }
    .badge-statut-valide { background-color: #059669; color: #fff; border-radius: 9999px; padding: 0.25rem 0.625rem; font-size: 0.75rem; font-weight: 500; }
    .badge-statut-refuse { background-color: #dc2626; color: #fff; border-radius: 9999px; padding: 0.25rem 0.625rem; font-size: 0.75rem; font-weight: 500; }
    .badge-statut-attente { background-color: #d97706; color: #fff; border-radius: 9999px; padding: 0.25rem 0.625rem; font-size: 0.75rem; font-weight: 500; }
    /* Form controls: min height for touch */
    .fi-input {
        min-height: 44px;
        border-radius: 8px;
    }
    @media print {
        body * { visibility: hidden; }
        .print-section, .print-section * { visibility: visible; }
        .print-section { position: absolute; left: 0; top: 0; width: 100%; z-index: 9999; }
    }
</style>
