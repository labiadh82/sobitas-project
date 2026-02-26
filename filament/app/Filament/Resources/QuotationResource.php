<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuotationResource\Pages;
use App\Filament\Resources\QuotationResource\RelationManagers;
use App\Models\Client;
use App\Models\Coordinate;
use App\Models\Quotation;
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

class QuotationResource extends Resource
{
    protected static ?string $model = Quotation::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static string | \UnitEnum | null $navigationGroup = 'Facturation & Tickets';

    protected static ?string $navigationLabel = 'Devis';

    protected static ?string $modelLabel = 'Devis';

    protected static ?string $pluralModelLabel = 'Devis';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        $coordinate = Coordinate::getCached();
        return $schema->schema([
            Grid::make(2)->schema([
                Section::make('Entreprise')
                    ->schema([
                        Forms\Components\Placeholder::make('company_info')
                            ->label('')
                            ->content(fn () => $coordinate ? new \Illuminate\Support\HtmlString(view('filament.components.company-info', ['coordinate' => $coordinate])->render()) : '—'),
                    ]),
                Section::make('Client')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->label('Client')
                            ->relationship('client', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => (string) ($record->name ?? 'Client #' . $record->id))
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->required()->label('Nom'),
                                Forms\Components\TextInput::make('phone_1')->label('Téléphone 1'),
                                Forms\Components\TextInput::make('email')->email()->label('Email'),
                                Forms\Components\TextInput::make('adresse')->label('Adresse'),
                                Forms\Components\TextInput::make('matricule')->label('Matricule fiscal'),
                            ])
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
                        Forms\Components\TextInput::make('client_adresse')->label('Adresse')->disabled()->dehydrated(false),
                        Forms\Components\TextInput::make('client_phone')->label('N° Tél')->disabled()->dehydrated(false),
                    ]),
            ])->columnSpanFull(),

            Section::make('Produits')
                ->schema([
                    Forms\Components\Placeholder::make('barcode_scan')
                        ->label('Scanner code à barre')
                        ->content(fn () => new \Illuminate\Support\HtmlString(view('filament.components.barcode-scan')->render())),
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
                                ->afterStateUpdated(function ($state, $set) {
                                    if ($state && $product = \App\Models\Product::find($state)) {
                                        $set('prix_unitaire', (float) ($product->prix ?? 0));
                                    }
                                }),
                            Forms\Components\TextInput::make('qte')->label('Qté')->numeric()->default(1)->minValue(1)->required(),
                            Forms\Components\TextInput::make('prix_unitaire')->label('P.U')->numeric()->default(0)->prefix('DT')->required(),
                            Forms\Components\Placeholder::make('prix_total_display')->label('P.T')->content(fn ($get) => number_format((float) $get('qte') * (float) $get('prix_unitaire'), 3, '.', ' ') . ' DT'),
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
                    ->schema([
                        Forms\Components\TextInput::make('prix_ht')->label('Montant Total')->numeric()->prefix('DT')->disabled()->dehydrated(false)->default(0),
                        Forms\Components\TextInput::make('remise')->label('Montant Remise')->numeric()->prefix('DT')->default(0)->live(),
                        Forms\Components\TextInput::make('pourcentage_remise')->label('Poucentage Remise %')->numeric()->suffix('%')->default(0)->live(),
                        Forms\Components\TextInput::make('prix_ttc')->label('Net à payer')->numeric()->prefix('DT')->disabled()->dehydrated(false)->default(0),
                        Forms\Components\Select::make('statut')
                            ->label('Statut')
                            ->options([
                                'brouillon' => 'Brouillon',
                                'en_attente' => 'En attente',
                                'valide' => 'Validé',
                                'refuse' => 'Refusé',
                            ])
                            ->default('brouillon')
                            ->nullable(),
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
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('numero')
                    ->label('N°')
                    ->searchable()
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::Bold),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('prix_ht')
                    ->label('HT')
                    ->money('TND')
                    ->sortable()
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('prix_ttc')
                    ->label('TTC')
                    ->money('TND')
                    ->sortable()
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('statut')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'brouillon' => 'Brouillon',
                        'valide' => 'Validé',
                        'refuse' => 'Refusé',
                        'en_attente' => 'En attente',
                        default => '—',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'brouillon' => 'gray',
                        'valide' => 'success',
                        'refuse' => 'danger',
                        'en_attente' => 'warning',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->filters([
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Du'),
                        Forms\Components\DatePicker::make('until')->label('Au'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('created_at', '<=', $data['until']));
                    }),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\Action::make('print')
                    ->label('Imprimer')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->modalHeading('Aperçu d\'impression')
                    ->modalContent(fn (Quotation $record) => view('filament.components.print-modal', [
                        'printUrl' => route('quotations.print', ['quotation' => $record->id]),
                        'title' => 'Devis ' . $record->numero,
                    ]))
                    ->modalSubmitAction(false),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListQuotations::route('/'),
            'create' => Pages\CreateQuotation::route('/create'),
            'edit'   => Pages\EditQuotation::route('/{record}/edit'),
        ];
    }
}
