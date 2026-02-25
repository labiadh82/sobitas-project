<?php

namespace App\Filament\Pages\Stock;

use App\Filament\Resources\ProductResource;
use App\Models\StockMovement;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class StockMovementsPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationLabel = 'Mouvements de stock';

    protected static ?string $title = 'Mouvements de stock';

    protected static string | \UnitEnum | null $navigationGroup = 'Gestion de stock';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.stock.stock-movements-page';

    public static function getSlug(): string
    {
        return 'stock/movements';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(StockMovement::query()->with(['product:id,designation_fr,slug', 'user:id,name']))
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.designation_fr')
                    ->label('Produit')
                    ->searchable()
                    ->limit(30)
                    ->url(fn (StockMovement $record): string => ProductResource::getUrl('edit', ['record' => $record->product_id])),
                Tables\Columns\TextColumn::make('movement_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => StockMovement::typeLabels()[$state] ?? $state)
                    ->badge()
                    ->color(fn ($state) => in_array($state, ['entry', 'return', 'release'], true) ? 'success' : (in_array($state, ['exit', 'sale'], true) ? 'danger' : 'gray')),
                Tables\Columns\TextColumn::make('qty_before')
                    ->label('Avant'),
                Tables\Columns\TextColumn::make('qty_change')
                    ->label('Changement')
                    ->formatStateUsing(fn ($state) => ($state > 0 ? '+' : '') . $state)
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('qty_after')
                    ->label('Après'),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Raison')
                    ->formatStateUsing(fn ($state) => StockMovement::reasonLabels()[$state] ?? $state)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('reference_type')
                    ->label('Référence')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('note')
                    ->label('Note')
                    ->limit(25)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->filters([
                Tables\Filters\SelectFilter::make('movement_type')
                    ->label('Type')
                    ->options(StockMovement::typeLabels()),
                Tables\Filters\SelectFilter::make('product_id')
                    ->label('Produit')
                    ->relationship('product', 'designation_fr')
                    ->searchable()
                    ->preload(),
            ]);
    }
}
