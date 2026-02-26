<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ClientResource;
use App\Models\Client;
use App\Models\Commande;
use App\Models\FactureTva;
use App\Models\Ticket;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class HistoriqueClient extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationLabel = 'Historique Client';

    protected static ?string $title = 'Historique Client';

    protected static ?string $slug = 'historique-client';

    protected static string | \UnitEnum | null $navigationGroup = 'Clients';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.historique-client';

    public ?string $tel = null;

    /** @var Collection<int, Client> */
    public Collection $clients;

    public function mount(?string $tel = null): void
    {
        $this->tel = $tel ?? request()->query('tel');
        $this->clients = collect();
        if ($this->tel !== null && $this->tel !== '') {
            $this->search();
        }
    }

    public function search(): void
    {
        $tel = trim((string) $this->tel);
        if ($tel === '') {
            $this->clients = collect();
            return;
        }

        $this->clients = Client::query()
            ->where(function ($q) use ($tel) {
                $q->where('phone_1', 'like', "%{$tel}%")
                    ->orWhere('phone_2', 'like', "%{$tel}%");
            })
            ->orderBy('name')
            ->get();
    }

    public function getTitle(): string | Htmlable
    {
        return 'Historique Client';
    }

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?string $tenant = null): string
    {
        $params = isset($parameters['tel']) ? ['tel' => $parameters['tel']] : [];
        return parent::getUrl($params, $isAbsolute, $panel, $tenant);
    }

    public function getCommandes(Client $client): Collection
    {
        return Commande::where('user_id', $client->id)->orderByDesc('created_at')->limit(20)->get();
    }

    public function getTickets(Client $client): Collection
    {
        return Ticket::where('client_id', $client->id)->orderByDesc('created_at')->limit(20)->get();
    }

    public function getFactureTvas(Client $client): Collection
    {
        return FactureTva::where('client_id', $client->id)->orderByDesc('created_at')->limit(20)->get();
    }

    public function getClientEditUrl(Client $client): string
    {
        return ClientResource::getUrl('edit', ['record' => $client]);
    }
}
