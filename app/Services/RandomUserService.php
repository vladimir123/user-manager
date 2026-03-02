<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RandomUserService
{
    private const API_V1 = 'https://randomuser.me/api/';
    private const API_V08 = 'https://randomuser.me/api/0.8/';

    public function fetchAndImport(int $count = 50, string $version = '1.4'): array
    {
        $url = $version === '0.8' ? self::API_V08 : self::API_V1;

        $response = Http::timeout(30)->get($url, ['results' => $count]);

        if ($response->failed()) {
            throw new \RuntimeException('Failed to fetch data from randomuser.me API');
        }

        $results = $response->json('results', []);

        if (empty($results)) {
            throw new \RuntimeException(
                'The randomuser.me API returned no users. The service may be temporarily unavailable. Please try again later.'
            );
        }

        return $version === '0.8'
            ? $this->importV08($results, $response->json('nationality'))
            : $this->importV14($results);
    }

    // -------------------------------------------------------------------------
    // API v1.4 parser
    // -------------------------------------------------------------------------
    private function importV14(array $results): array
    {
        $imported = 0;
        $updated  = 0;

        foreach ($results as $data) {
            $externalId = $data['login']['uuid'];
            $isNew      = !User::where('email', $data['email'])->exists();

            $dob = isset($data['dob']['date'])
                ? date('Y-m-d', strtotime($data['dob']['date']))
                : null;

            // v1.4: nationality is per-user in the 'nat' field
            $nationality = $data['nat'] ?? null;

            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'external_id'       => $externalId,
                    'name'              => $data['name']['first'] . ' ' . $data['name']['last'],
                    'first_name'        => $data['name']['first'],
                    'last_name'         => $data['name']['last'],
                    'username'          => $data['login']['username'],
                    'gender'            => $data['gender'],
                    'date_of_birth'     => $dob,
                    'nationality'       => $nationality,
                    'picture_large'     => $data['picture']['large'] ?? null,
                    'picture_thumbnail' => $data['picture']['thumbnail'] ?? null,
                    'password'          => bcrypt(Str::random(16)),
                ]
            );

            Contact::updateOrCreate(
                ['user_id' => $user->id],
                ['phone' => $data['phone'] ?? null, 'cell' => $data['cell'] ?? null]
            );

            // v1.4: location.street is an object {number: int, name: string}
            $street = $data['location']['street'] ?? [];
            Address::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'street_number' => isset($street['number']) ? (string) $street['number'] : null,
                    'street_name'   => $street['name'] ?? null,
                    'city'          => $data['location']['city']    ?? null,
                    'state'         => $data['location']['state']   ?? null,
                    'postcode'      => (string) ($data['location']['postcode'] ?? ''),
                    'country'       => $data['location']['country'] ?? null,
                    'latitude'      => $data['location']['coordinates']['latitude']  ?? null,
                    'longitude'     => $data['location']['coordinates']['longitude'] ?? null,
                ]
            );

            $isNew ? $imported++ : $updated++;
        }

        return ['imported' => $imported, 'updated' => $updated, 'total' => count($results)];
    }

    // -------------------------------------------------------------------------
    // API v0.8 parser
    // -------------------------------------------------------------------------
    private function importV08(array $results, ?string $responseNationality): array
    {
        $imported = 0;
        $updated  = 0;

        foreach ($results as $item) {
            // v0.8: each result is wrapped in a "user" key
            $data = $item['user'];

            $isNew = !User::where('email', $data['email'])->exists();

            // v0.8: dob is a Unix timestamp integer
            $dob = isset($data['dob'])
                ? date('Y-m-d', (int) $data['dob'])
                : null;

            // v0.8: nationality is at the response root level, not per-user
            $nationality = $responseNationality ?? null;

            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'external_id'       => $data['md5'],
                    'name'              => $data['name']['first'] . ' ' . $data['name']['last'],
                    'first_name'        => $data['name']['first'],
                    'last_name'         => $data['name']['last'],
                    'username'          => $data['username'],
                    'gender'            => $data['gender'],
                    'date_of_birth'     => $dob,
                    'nationality'       => $nationality,
                    'picture_large'     => $data['picture']['large'] ?? null,
                    'picture_thumbnail' => $data['picture']['thumbnail'] ?? null,
                    'password'          => bcrypt(Str::random(16)),
                ]
            );

            Contact::updateOrCreate(
                ['user_id' => $user->id],
                ['phone' => $data['phone'] ?? null, 'cell' => $data['cell'] ?? null]
            );

            // v0.8: location.street is a full string like "1234 Street Name"; zip not postcode
            $street = $data['location']['street'] ?? '';
            preg_match('/^(\d+)\s+(.+)$/', $street, $parts);

            Address::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'street_number' => $parts[1] ?? null,
                    'street_name'   => $parts[2] ?? $street ?: null,
                    'city'          => $data['location']['city']  ?? null,
                    'state'         => $data['location']['state'] ?? null,
                    'postcode'      => (string) ($data['location']['zip'] ?? ''),
                    'country'       => null,  // not available in v0.8
                    'latitude'      => null,
                    'longitude'     => null,
                ]
            );

            $isNew ? $imported++ : $updated++;
        }

        return ['imported' => $imported, 'updated' => $updated, 'total' => count($results)];
    }
}
