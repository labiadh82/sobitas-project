<?php

namespace App\Services;

use App\Models\Client;

/**
 * Client lookup/creation by phone (e.g. for orders).
 * Phone is normalized: trim, digits only, +216 prefix handled.
 */
class ClientService
{
    public const SOURCE_ONLINE = 'online';

    /**
     * Normalize phone for lookup: trim, keep digits, optional +216 prefix.
     * E.g. "+216 12 345 678", "12345678" -> "12345678" or "21612345678" for storage.
     * We store normalized as digits only (no leading + or 0); Tunisian 8 digits.
     */
    public function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return null;
        }
        $digits = preg_replace('/\D/', '', $phone);
        if ($digits === '') {
            return null;
        }
        // Tunisian: 216 then 8 digits, or 8 digits starting with 2/9/5/7
        if (str_starts_with($digits, '216') && strlen($digits) >= 11) {
            $digits = substr($digits, 3); // keep 8 digits after 216
        }
        if (strlen($digits) > 8 && str_starts_with($digits, '0')) {
            $digits = ltrim($digits, '0');
        }
        return $digits ?: null;
    }

    /**
     * Find client by phone (phone_1 or phone_2 normalized), or create minimal client.
     * Returns the client and sets commande user_id to this client.
     */
    public function findOrCreateClientByPhone(
        string $phone,
        ?string $name = null,
        ?string $email = null,
        ?string $address = null,
        ?string $region = null
    ): ?Client {
        $normalized = $this->normalizePhone($phone);
        if ($normalized === null) {
            return null;
        }

        // Match by normalized form (DB may store +216 12 345 678 or 12345678)
        $client = Client::query()
            ->whereNotNull('phone_1')
            ->orWhereNotNull('phone_2')
            ->get()
            ->first(function (Client $c) use ($normalized) {
                $n1 = $this->normalizePhone($c->phone_1);
                $n2 = $this->normalizePhone($c->phone_2);
                return $n1 === $normalized || $n2 === $normalized;
            });

        if ($client) {
            return $client;
        }

        $client = new Client();
        $client->name = $name ?: 'Client ' . substr($normalized, -4);
        $client->phone_1 = $phone;
        $client->email = $email;
        $client->adresse = $address;
        $client->source = self::SOURCE_ONLINE;
        $client->sms = false;
        $client->save();

        return $client;
    }
}
