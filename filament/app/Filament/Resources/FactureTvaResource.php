<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FactureTvaResource\Pages;
use App\Models\Client;
use App\Models\Coordinate;
use App\Models\FactureTva;
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

class FactureTvaResource extends Resource
{
    protected static ?string $model = FactureTva::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-duplicate';
    protected static string | \UnitEnum | null $navigationGroup = 'Facturation & Tickets';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'Facture TVA';
    protected static ?string $pluralModelLabel = 'Factures TVA';
    protected static ?string $recordTitleAttribute = 'numero';

    protected static bool $isGloballySearchable = false;

    public static function form(Schema $schema): Schema
    {
        $coordinate = Coordinate::getCached();
        $defaultTva = $coordinate && isset($coordinate->tva) ? (float) $coordinate->tva : 19;
        return $schema->schema([
            Grid::make(2)->schema([
                Section::make('Entreprise')
                    ->schema([
                        Forms\Components\Placeholder::make('company_info')
                            ->label('')
                            ->content(fn () => $coordinate ? view('filament.components.company-info', ['coordinate' => $coordinate])->render() : '—'),
                    ]),
                Section::make('Client')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->label('Client')
                            ->relationship('client', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => (string) ($record->name ?? 'Client #' . $record->id))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $client = Client::find($state);
                                    $set('client_adresse', $client?->adresse ?? '');
                                    $set('client_phone', $client?->phone_1 ?? '');
                                } else {
                                    $set('client_adresse', '');
                                    $set('client_phone', '');
                                }
                            }),
                        Forms\Components\TextInput::make('client_adresse')->label('Adresse')->disabled()->dehydrated(false),
                        Forms\Components\TextInput::make('client_phone')->label('N° Tél')->disabled()->dehydrated(false),
                    ]),
            ])->columnSpanFull(),

            Section::make('Produits')
                ->schema([
                    Forms\Components\Placeholder::make('barcode_scan')
                        ->label('Scanner code à barre')
                        ->content(fn () => view('filament.components.barcode-scan')->render()),
                    Repeater::make('details')
                        ->label('')
                        ->schema([
                            Forms\Components\Select::make('produit_id')
                                ->label('Produit')
                                ->options(fn () => \App\Models\Product::where('qte', '>', 0)->get()->mapWithKeys(fn ($p) => [$p->id => ($p->designation_fr ?? '') . ' (' . (int) $p->qte . ')'])->all())
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    if ($state && $product = \App\Models\Product::find($state)) {
                                        $set('prix_unitaire', (float) ($product->prix ?? 0));
                                    }
                                }),
                            Forms\Components\TextInput::make('qte')->label('Qté')->numeric()->default(1)->minValue(1)->required(),
                            Forms\Components\TextInput::make('prix_unitaire')->label('P.U')->numeric()->default(0)->prefix('DT')->required(),
                            Forms\Components\Placeholder::make('prix_ht_display')->label('P.T/HT')->content(fn (Forms\Get $get) => number_format((float) $get('qte') * (float) $get('prix_unitaire'), 3, '.', ' ') . ' DT'),
                            Forms\Components\TextInput::make('tva_pct')->label('TVA (%)')->numeric()->default($defaultTva)->suffix('%')->required(),
                            Forms\Components\Placeholder::make('prix_ttc_display')->label('TVA')->content(fn (Forms\Get $get) => number_format((float) $get('qte') * (float) $get('prix_unitaire') * (float) ($get('tva_pct') ?? $defaultTva) / 100, 3, '.', ' ') . ' DT'),
                        ])
                        ->columns(5)
                        ->defaultItems(1)
                        ->addActionLabel('Ajouter')
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),

            Grid::make(2)->schema([
                Forms\Components\Placeholder::make('_spacer')->label('')->content('')->columnSpan(1),
                Section::make('Totaux')
                    ->schema([
                        Forms\Components\TextInput::make('prix_ht')->label('Montant Total HT')->numeric()->prefix('DT')->disabled()->dehydrated(false)->default(0),
                        Forms\Components\TextInput::make('remise')->label('Montant Remise')->numeric()->prefix('DT')->default(0)->live(),
                        Forms\Components\TextInput::make('pourcentage_remise')->label('Poucentage Remise %')->numeric()->suffix('%')->default(0)->live(),
                        Forms\Components\TextInput::make('prix_ht_apres_remise')->label('Montant HT après remise')->numeric()->prefix('DT')->disabled()->dehydrated(false)->default(0),
                        Forms\Components\TextInput::make('tva')->label('Montant Totale TVA')->numeric()->prefix('DT')->disabled()->dehydrated(false)->default(0),
                        Forms\Components\TextInput::make('prix_ttc')->label('Montant Totale TTC')->numeric()->prefix('DT')->disabled()->dehydrated(false)->default(0),
                        Forms\Components\TextInput::make('timbre')->label('Timbre fiscal')->numeric()->prefix('DT')->default(0)->live(),
                        Forms\Components\TextInput::make('net_a_payer')->label('Net à payer')->numeric()->prefix('DT')->disabled()->dehydrated(false)->default(0),
                    ])
                    ->columns(1)
                    ->columnSpan(1),
            ])->columnSpanFull(),

            Forms\Components\Hidden::make('numero'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('client:id,name'))
            ->columns([
                Tables\Columns\TextColumn::make('numero')->label('N°')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('client.name')->label('Client')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('prix_ttc')->label('Total TTC')->money('TND')->sortable(),
                Tables\Columns\TextColumn::make('tva')->label('TVA')->suffix('%'),
                Tables\Columns\TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->actions([
                Actions\EditAction::make(),
                Actions\Action::make('print')
                    ->label('Imprimer')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->modalHeading('Aperçu d\'impression')
                    ->modalContent(fn (FactureTva $record) => view('filament.components.print-modal', [
                        'printUrl' => route('facture-tvas.print', ['factureTva' => $record->id]),
                        'title' => 'Facture ' . $record->numero,
                        'showStyleSwitcher' => true,
                    ]))
                    ->modalSubmitAction(false),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([Actions\DeleteBulkAction::make()]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListFactureTvas::route('/'),
            'create' => Pages\CreateFactureTva::route('/create'),
            'edit'   => Pages\EditFactureTva::route('/{record}/edit'),
        ];
    }
}
