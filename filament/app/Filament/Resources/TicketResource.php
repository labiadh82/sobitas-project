<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use App\Models\Ticket;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
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

    // Exclude from global search — reduces search queries from 11 to 4
    protected static bool $isGloballySearchable = false;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Informations du ticket')
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
                    Forms\Components\Select::make('client_id')
                        ->label('Client')
                        ->relationship('client', 'name')
                        ->getOptionLabelFromRecordUsing(fn ($record) => (string) ($record->name ?? 'Client #' . $record->id))
                        ->searchable()
                        ->preload()
                        ->required(fn ($get) => $get('type') === Ticket::TYPE_TICKET_CAISSE)
                        ->hidden(fn ($get) => $get('type') !== Ticket::TYPE_TICKET_CAISSE),
                    Forms\Components\TextInput::make('numero')
                        ->label('Numéro')
                        ->disabled()
                        ->dehydrated(false),
                    Forms\Components\TextInput::make('prix_ht')
                        ->label('Prix HT')
                        ->numeric()
                        ->prefix('DT')
                        ->disabled(),
                    Forms\Components\TextInput::make('prix_ttc')
                        ->label('Prix TTC')
                        ->numeric()
                        ->prefix('DT')
                        ->disabled(),
                ])
                ->columns(2),
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
        return [
            TicketResource\RelationManagers\DetailsRelationManager::class,
        ];
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
