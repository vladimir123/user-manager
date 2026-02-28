<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RandomUserService
{
    private const API_URL = 'https://randomuser.me/api/0.8/';

    public function fetchAndImport(int $count = 50): array
    {
        $response = Http::timeout(30)->get(self::API_URL, [
            'results' => $count,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Failed to fetch data from randomuser.me API');
        }

        $results = $response->json('results', []);
        // v0.8: nationality is at the root of the response, not per user
        $nationality = $response->json('nationality') ?? null;
        $imported = 0;
        $updated = 0;

        foreach ($results as $item) {
            // v0.8: each result is wrapped in a "user" key
            $data = $item['user'];

            // Use md5 hash as external ID (no uuid in v0.8)
            $externalId = $data['md5'];

            $isNew = !User::where('email', $data['email'])->exists();

            // v0.8: dob is a Unix timestamp integer
            $dob = isset($data['dob'])
                ? date('Y-m-d', (int) $data['dob'])
                : null;

            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'external_id'       => $externalId,
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
                [
                    'phone' => $data['phone'] ?? null,
                    'cell'  => $data['cell'] ?? null,
                ]
            );

            // v0.8: location.street is a full string, location.zip instead of postcode
            $street = $data['location']['street'] ?? '';
            // Try to split "1234 Street Name" into number + name
            preg_match('/^(\d+)\s+(.+)$/', $street, $streetParts);

            Address::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'street_number' => $streetParts[1] ?? null,
                    'street_name'   => $streetParts[2] ?? $street,
                    'city'          => $data['location']['city'] ?? null,
                    'state'         => $data['location']['state'] ?? null,
                    'postcode'      => (string) ($data['location']['zip'] ?? ''),
                    'country'       => null, // not present in v0.8
                    'latitude'      => null,
                    'longitude'     => null,
                ]
            );

            $isNew ? $imported++ : $updated++;
        }

        return ['imported' => $imported, 'updated' => $updated, 'total' => count($results)];
    }
}
