<?php

namespace App\Filament\Pages\Stock;

use App\Models\Product;
use App\Services\StockService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class StockReportsPage extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Rapports & exports';

    protected static ?string $title = 'Rapports stock';

    protected static string | \UnitEnum | null $navigationGroup = 'Gestion de stock';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.stock.stock-reports-page';

    public static function getSlug(): string
    {
        return 'stock/reports';
    }

    public function getReports(): array
    {
        $valueByCategory = DB::table('products')
            ->join('sous_categories', 'products.sous_categorie_id', '=', 'sous_categories.id')
            ->join('categs', 'sous_categories.categorie_id', '=', 'categs.id')
            ->where('products.qte', '>', 0)
            ->selectRaw('categs.designation_fr as name, SUM(products.qte * COALESCE(products.prix_ht, products.prix, 0)) as value')
            ->groupBy('categs.id', 'categs.designation_fr')
            ->orderByDesc('value')
            ->limit(10)
            ->get();

        $outOfStock = Product::where('qte', '<=', 0)->orWhere('rupture', 0)->count();
        $lowStock = Product::where('qte', '>', 0)->whereRaw('qte < COALESCE(low_stock_threshold, 10)')->count();

        return [
            'value_by_category' => $valueByCategory,
            'out_of_stock_count' => $outOfStock,
            'low_stock_count' => $lowStock,
        ];
    }
}
