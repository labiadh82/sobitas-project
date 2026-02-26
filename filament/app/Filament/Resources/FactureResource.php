<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FactureResource\Pages;
use App\Models\Client;
use App\Models\Coordinate;
use App\Models\Facture;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FactureResource extends Resource
{
    protected static ?string $model = Facture::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static string | \UnitEnum | null $navigationGroup = 'Facturation & Tickets';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Bon de Livraison';

    protected static ?string $pluralModelLabel = 'Bons de Livraison';

    protected static ?string $recordTitleAttribute = 'numero';

    public static function getGloballySearchableAttributes(): array
    {
        return ['numero'];
    }

    public static function form(Schema $schema): Schema
    {
        $coordinate = Coordinate::getCached();
        return $schema->schema([
            Grid::make(2)->schema([
                Section::make('Entreprise')
                    ->icon('heroicon-o-building-office-2')
                    ->schema([
                        Forms\Components\Placeholder::make('company_info')
                            ->label('')
                            ->content(fn () => $coordinate ? new \Illuminate\Support\HtmlString(view('filament.components.company-info', ['coordinate' => $coordinate])->render()) : '—'),
                    ]),
                Section::make('Client')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->label('Client')
                            ->relationship('client', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => (string) ($record->name ?? 'Client #' . $record->id))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $client = Client::find($state);
                                    $set('client_adresse', $client?->adresse ?? '');
                                    $set('client_phone', $client?->phone_1 ?? '');
                                } else {
                                    $set('client_adresse', '');
                                    $set('client_phone', '');
                                }
                            }),
                        Forms\Components\TextInput::make('client_adresse')
                            ->label('Adresse')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('client_phone')
                            ->label('N° Tél')
                            ->disabled()
                            ->dehydrated(false),
                    ]),
            ])->columnSpanFull(),

            Section::make('Produits')
                ->icon('heroicon-o-cube')
                ->description('Scannez un code-barres ou ajoutez des lignes manuellement.')
                ->schema([
                    Forms\Components\Placeholder::make('barcode_scan')
                        ->label('Scanner code à barre')
                        ->content(fn () => new \Illuminate\Support\HtmlString(view('filament.components.barcode-scan')->render())),
                    Repeater::make('details')
                        ->label('')
                        ->live()
                        ->afterStateUpdated(function ($get, $set) {
                            $details = $get('details') ?? [];
                            $total = 0.0;
                            foreach ($details as $d) {
                                if (!empty($d['produit_id'])) {
                                    $total += (float) ($d['qte'] ?? 0) * (float) ($d['prix_unitaire'] ?? 0);
                                }
                            }
                            $remise = (float) ($get('remise') ?? 0);
                            $set('prix_ht', $total);
                            $set('prix_ttc', $total - $remise);
                        })
                        ->schema([
                            Forms\Components\Select::make('produit_id')
                                ->label('Produit')
                                ->options(fn () => \App\Models\Product::where('qte', '>', 0)->get()->mapWithKeys(fn ($p) => [$p->id => ($p->designation_fr ?? '') . ' (' . (int) $p->qte . ')'])->all())
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, $set) {
                                    if ($state && $product = \App\Models\Product::find($state)) {
                                        $set('prix_unitaire', (float) ($product->prix ?? 0));
                                    }
                                }),
                            Forms\Components\TextInput::make('qte')
                                ->label('Qté')
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->required()
                                ->live(debounce: 300),
                            Forms\Components\TextInput::make('prix_unitaire')
                                ->label('P.U')
                                ->numeric()
                                ->default(0)
                                ->prefix('DT')
                                ->required()
                                ->live(debounce: 300),
                            Forms\Components\Placeholder::make('prix_total_display')
                                ->label('P.T')
                                ->content(fn ($get) => number_format((float) $get('qte') * (float) $get('prix_unitaire'), 3, '.', ' ') . ' DT'),
                        ])
                        ->columns(4)
                        ->defaultItems(1)
                        ->addActionLabel('Ajouter')
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),

            Grid::make(2)->schema([
                Forms\Components\Placeholder::make('_spacer')->label('')->content('')->columnSpan(1),
                Section::make('Totaux')
                    ->icon('heroicon-o-calculator')
                    ->schema([
                        Forms\Components\TextInput::make('prix_ht')
                            ->label('Montant Total')
                            ->numeric()
                            ->prefix('DT')
                            ->disabled()
                            ->dehydrated(false)
                            ->default(0),
                        Forms\Components\TextInput::make('remise')
                            ->label('Montant Remise')
                            ->numeric()
                            ->prefix('DT')
                            ->default(0)
                            ->live()
                            ->afterStateUpdated(function ($state, $get, $set) {
                                $details = $get('details') ?? [];
                                $total = 0.0;
                                foreach ($details as $d) {
                                    if (!empty($d['produit_id'])) {
                                        $total += (float) ($d['qte'] ?? 0) * (float) ($d['prix_unitaire'] ?? 0);
                                    }
                                }
                                $set('prix_ht', $total);
                                $set('prix_ttc', $total - (float) ($state ?? 0));
                            }),
                        Forms\Components\TextInput::make('pourcentage_remise')
                            ->label('Pourcentage Remise %')
                            ->numeric()
                            ->suffix('%')
                            ->default(0)
                            ->live(),
                        Forms\Components\TextInput::make('prix_ttc')
                            ->label('Net à payer')
                            ->numeric()
                            ->prefix('DT')
                            ->disabled()
                            ->dehydrated(false)
                            ->default(0),
                    ])
                    ->columns(1)
                    ->columnSpan(1),
            ])->columnSpanFull(),

            Forms\Components\Hidden::make('numero'),
            Forms\Components\Hidden::make('timbre')->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // Eager load client to prevent N+1 on client.name column
            ->modifyQueryUsing(fn (Builder $query) => $query->with('client:id,name'))
            ->columns([
                Tables\Columns\TextColumn::make('numero')
                    ->label('N°')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label() ?? (is_string($state) ? $state : '—'))
                    ->color(fn ($state) => match ($state?->value ?? '') {
                        'issued' => 'success',
                        'delivered' => 'info',
                        default => 'gray',
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('prix_ttc')
                    ->label('Total TTC')
                    ->money('TND')
                    ->sortable(),
                Tables\Columns\TextColumn::make('remise')
                    ->label('Remise')
                    ->money('TND')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->striped()
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'draft' => 'Brouillon',
                        'issued' => 'Émis',
                        'delivered' => 'Livré',
                    ])
                    ->placeholder('Tous'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\Action::make('convertToInvoice')
                    ->label('Transformer en facture TVA')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('success')
                    ->visible(fn (Facture $record): bool => \Illuminate\Support\Facades\Schema::hasColumn('facture_tvas', 'facture_id') && ! $record->factureTvas()->exists())
                    ->requiresConfirmation()
                    ->modalSubmitActionLabel('Confirmer')
                    ->action(function (Facture $record) {
                        $invoice = app(\App\Services\DocumentConversion\BlToInvoiceService::class)->createInvoiceFromBl($record);
                        \Filament\Notifications\Notification::make()->title('Facture #' . $invoice->numero . ' créée')->success()->send();
                        return redirect(\App\Filament\Resources\FactureTvaResource::getUrl('edit', ['record' => $invoice]));
                    }),
                Actions\Action::make('print')
                    ->label('Imprimer')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->modalHeading('Aperçu d\'impression')
                    ->modalContent(fn (Facture $record) => view('filament.components.print-modal', [
                        'printUrl' => route('factures.print', ['facture' => $record->id]),
                        'title' => 'Bon de livraison ' . $record->numero,
                    ]))
                    ->modalSubmitAction(false),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListFactures::route('/'),
            'create' => Pages\CreateFacture::route('/create'),
            'edit'   => Pages\EditFacture::route('/{record}/edit'),
        ];
    }
}
