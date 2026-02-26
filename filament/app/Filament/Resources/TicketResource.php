<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use App\Models\Client;
use App\Models\Coordinate;
use App\Models\Ticket;
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

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-ticket';

    protected static string | \UnitEnum | null $navigationGroup = 'Facturation & Tickets';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'numero';

    protected static bool $isGloballySearchable = false;

    public static function form(Schema $schema): Schema
    {
        $coordinate = Coordinate::getCached();

        return $schema->schema([
            Grid::make(2)->schema([
                // Left column: company info (like the image)
                Section::make('')
                    ->schema([
                        Forms\Components\Placeholder::make('company_info')
                            ->label('')
                            ->content(fn () => $coordinate ? new \Illuminate\Support\HtmlString(view('filament.components.company-info', ['coordinate' => $coordinate])->render()) : '—'),
                    ])
                    ->columnSpan(1),

                // Right column: form fields
                Section::make('')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Type de ticket')
                            ->options(Ticket::typeOptions())
                            ->default(Ticket::TYPE_TICKET_CAISSE)
                            ->required()
                            ->live()
                            ->native(false),
                        Forms\Components\Select::make('commande_id')
                            ->label('Commande (obligatoire pour BL)')
                            ->relationship(
                                name: 'commande',
                                titleAttribute: 'numero',
                                modifyQueryUsing: fn ($q) => $q->where('etat', '!=', 'annuler')->orderByDesc('created_at')
                            )
                            ->getOptionLabelFromRecordUsing(fn ($r) => $r->numero . ' — ' . trim($r->nom . ' ' . $r->prenom) . ' — ' . number_format((float) $r->prix_ttc, 2, ',', ' ') . ' DT')
                            ->searchable()
                            ->preload()
                            ->required(fn ($get) => $get('type') === Ticket::TYPE_BON_LIVRAISON)
                            ->hidden(fn ($get) => $get('type') !== Ticket::TYPE_BON_LIVRAISON)
                            ->helperText('Pour un Bon de livraison, sélectionnez la commande concernée.')
                            ->dehydrated(true),
                        Grid::make(2)->schema([
                            Forms\Components\Select::make('client_id')
                                ->label('Client')
                                ->relationship('client', 'name')
                                ->getOptionLabelFromRecordUsing(fn ($record) => (string) ($record->name ?? 'Client #' . $record->id))
                                ->searchable()
                                ->preload()
                                ->required(fn ($get) => $get('type') === Ticket::TYPE_TICKET_CAISSE)
                                ->hidden(fn ($get) => $get('type') !== Ticket::TYPE_TICKET_CAISSE)
                                ->live()
                                ->afterStateUpdated(function ($state, $set) {
                                    if ($state && $client = Client::find($state)) {
                                        $set('client_adresse', $client->adresse ?? '');
                                        $set('client_phone', $client->phone_1 ?? '');
                                    } else {
                                        $set('client_adresse', '');
                                        $set('client_phone', '');
                                    }
                                }),
                            Forms\Components\Placeholder::make('add_client_link')
                                ->label('')
                                ->content(fn () => new \Illuminate\Support\HtmlString(
                                    '<a href="' . e(ClientResource::getUrl('create')) . '" target="_blank" rel="noopener" class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus:ring-2 rounded-lg fi-size-sm fi-btn-color-primary gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-primary-600 text-white hover:bg-primary-500 focus:ring-primary-500/50 dark:bg-primary-500 dark:hover:bg-primary-400 dark:focus:ring-primary-400/50 fi-ac-action"><span class="fi-btn-label">Ajouter Client(e)</span></a>'
                                )),
                        ])->columns(2)->visible(fn ($get) => $get('type') === Ticket::TYPE_TICKET_CAISSE),
                        Forms\Components\TextInput::make('client_adresse')
                            ->label('Adresse :')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($get) => $get('type') === Ticket::TYPE_TICKET_CAISSE),
                        Forms\Components\TextInput::make('client_phone')
                            ->label('N°Tél :')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($get) => $get('type') === Ticket::TYPE_TICKET_CAISSE),
                    ])
                    ->columnSpan(1),
            ])->columnSpanFull(),

            // Barcode + products (right side layout: full width below)
            Section::make('Produits')
                ->schema([
                    Forms\Components\Placeholder::make('barcode_scan')
                        ->label('Scanner code à barre')
                        ->content(fn () => new \Illuminate\Support\HtmlString(view('filament.components.barcode-scan')->render())),
                    Repeater::make('details')
                        ->label('')
                        ->live()
                        ->afterStateUpdated(function ($get, $set) {
                            self::recalculateTicketTotals($get, $set);
                        })
                        ->schema([
                            Forms\Components\Select::make('produit_id')
                                ->label('Produit')
                                ->options(fn () => \App\Models\Product::orderBy('designation_fr')->get()->mapWithKeys(fn ($p) => [$p->id => ($p->designation_fr ?? '') . ($p->code_product ? ' (' . $p->code_product . ')' : '')])->all())
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
                                ->minValue(0.001)
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
                                ->content(fn ($get) => number_format((float) ($get('qte') ?? 0) * (float) ($get('prix_unitaire') ?? 0), 3, '.', ' ') . ' DT'),
                        ])
                        ->columns(4)
                        ->defaultItems(0)
                        ->addActionLabel('Ajouter')
                        ->columnSpanFull()
                        ->itemLabel(fn (array $state) => isset($state['produit_id']) ? (\App\Models\Product::find($state['produit_id'])?->designation_fr ?? 'Ligne') : 'Ligne'),
                ])
                ->columnSpanFull(),

            // Totals: Montant Total, Montant Remise, Pourcentage Remise %, Net à payer
            Section::make('Totaux')
                ->schema([
                    Forms\Components\TextInput::make('prix_ht')
                        ->label('Montant Total')
                        ->numeric()
                        ->prefix('DT')
                        ->disabled()
                        ->dehydrated(true)
                        ->default(0)
                        ->extraInputAttributes(['class' => 'text-right']),
                    Forms\Components\TextInput::make('remise')
                        ->label('Montant Remise')
                        ->numeric()
                        ->prefix('DT')
                        ->default(0)
                        ->live()
                        ->afterStateUpdated(function ($get, $set) {
                            self::recalculateTicketTotals($get, $set);
                        })
                        ->extraInputAttributes(['class' => 'text-right']),
                    Forms\Components\TextInput::make('pourcentage_remise')
                        ->label('Pourcentage Remise %')
                        ->numeric()
                        ->suffix('%')
                        ->default(0)
                        ->live(debounce: 300)
                        ->afterStateUpdated(function ($get, $set) {
                            self::recalculateTicketTotals($get, $set);
                        })
                        ->extraInputAttributes(['class' => 'text-right']),
                    Forms\Components\TextInput::make('prix_ttc')
                        ->label('Net à payer')
                        ->numeric()
                        ->prefix('DT')
                        ->disabled()
                        ->dehydrated(true)
                        ->default(0)
                        ->extraInputAttributes(['class' => 'text-right']),
                ])
                ->columns(4)
                ->columnSpanFull(),

            Forms\Components\Hidden::make('numero'),
        ]);
    }

    public static function recalculateTicketTotals($get, $set): void
    {
        $details = $get('details') ?? [];
        $total = 0.0;
        foreach ($details as $d) {
            if (! empty($d['produit_id'])) {
                $total += (float) ($d['qte'] ?? 0) * (float) ($d['prix_unitaire'] ?? 0);
            }
        }
        $remiseAmount = (float) ($get('remise') ?? 0);
        $remisePct = (float) ($get('pourcentage_remise') ?? 0);
        if ($remisePct > 0 && $total > 0) {
            $remiseAmount = $total * $remisePct / 100;
        }
        $net = max(0, $total - $remiseAmount);
        $set('prix_ht', $total);
        $set('prix_ttc', $net);
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
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (?string $s) => $s === Ticket::TYPE_BON_LIVRAISON ? 'BL' : 'Caisse')
                    ->badge()
                    ->color(fn (?string $s) => $s === Ticket::TYPE_BON_LIVRAISON ? 'info' : 'success'),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('prix_ttc')
                    ->label('Total TTC')
                    ->money('TND')
                    ->sortable()
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->color('gray'),
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
                    ->modalContent(fn (Ticket $record) => view('filament.components.print-modal', [
                        'printUrl' => route('tickets.print', ['ticket' => $record->id]),
                        'title' => 'Ticket ' . $record->numero,
                        'documentType' => 'ticket',
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
            'index'  => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'edit'   => Pages\EditTicket::route('/{record}/edit'),
        ];
    }
}
