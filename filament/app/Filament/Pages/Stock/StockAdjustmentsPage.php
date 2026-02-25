<?php

namespace App\Filament\Pages\Stock;

use App\Models\Product;
use App\Services\StockService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class StockAdjustmentsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Ajustements de stock';

    protected static ?string $title = 'Ajustement de stock';

    protected static string | \UnitEnum | null $navigationGroup = 'Gestion de stock';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.stock.stock-adjustments-page';

    public ?array $data = [];

    public static function getSlug(): string
    {
        return 'stock/adjustments';
    }

    public function mount(): void
    {
        $productId = request()->query('product_id');
        if ($productId) {
            $product = Product::find($productId);
            if ($product) {
                $this->form->fill([
                    'product_id' => $product->id,
                    'product_name' => $product->designation_fr,
                    'qty_before' => $product->qte,
                    'new_qty' => $product->qte,
                    'reason' => \App\Models\StockMovement::REASON_MANUAL_CORRECTION,
                    'note' => '',
                ]);
            }
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                SchemaSection::make('Produit')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('Produit')
                            ->required()
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search) => Product::where('designation_fr', 'like', "%{$search}%")
                                ->orWhere('slug', 'like', "%{$search}%")
                                ->limit(50)
                                ->pluck('designation_fr', 'id')
                                ->toArray())
                            ->getOptionLabelUsing(fn ($value) => Product::find($value)?->designation_fr ?? $value)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $p = Product::find($state);
                                    if ($p) {
                                        $set('qty_before', $p->qte);
                                        $set('new_qty', $p->qte);
                                        $set('product_name', $p->designation_fr);
                                    }
                                }
                            }),
                        Forms\Components\TextInput::make('product_name')
                            ->label('Désignation')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($get) => $get('product_id')),
                        Forms\Components\TextInput::make('qty_before')
                            ->label('Quantité actuelle')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),
                SchemaSection::make('Ajustement')
                    ->schema([
                        Forms\Components\TextInput::make('new_qty')
                            ->label('Nouvelle quantité')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, $get) {
                                $set('qty_change', (int) $state - (int) ($get('qty_before') ?? 0));
                            }),
                        Forms\Components\TextInput::make('qty_change')
                            ->label('Changement')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($state) => ($state > 0 ? '+' : '') . $state),
                        Forms\Components\Select::make('reason')
                            ->label('Raison')
                            ->options(\App\Models\StockMovement::reasonLabels())
                            ->default(\App\Models\StockMovement::REASON_MANUAL_CORRECTION)
                            ->required(),
                        Forms\Components\Textarea::make('note')
                            ->label('Note')
                            ->rows(2)
                            ->maxLength(500),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function adjust(): void
    {
        $data = $this->form->getState();
        $productId = (int) ($data['product_id'] ?? 0);
        $newQty = (int) ($data['new_qty'] ?? 0);
        $reason = $data['reason'] ?? \App\Models\StockMovement::REASON_MANUAL_CORRECTION;
        $note = $data['note'] ?? '';

        if (!$productId) {
            Notification::make()->title('Sélectionnez un produit')->danger()->send();
            return;
        }

        $service = app(StockService::class);
        $service->adjustStock($productId, $newQty, $reason, $note);

        Notification::make()
            ->title('Stock mis à jour')
            ->body('Le mouvement a été enregistré.')
            ->success()
            ->send();

        $this->form->fill([
            'product_id' => $productId,
            'qty_before' => $newQty,
            'new_qty' => $newQty,
            'reason' => $reason,
            'note' => '',
        ]);
    }
}
