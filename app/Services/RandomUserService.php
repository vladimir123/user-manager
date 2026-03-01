<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RandomUserService
{
    private const API_URL = 'https://randomuser.me/api/';

    public function fetchAndImport(int $count = 50): array
    {
        $response = Http::timeout(30)->get(self::API_URL, [
            'results' => $count,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Failed to fetch data from randomuser.me API');
        }

        $results  = $response->json('results', []);
        $imported = 0;
        $updated  = 0;

        foreach ($results as $data) {
            // v1.4: external_id = login.uuid (stable unique identifier per user)
            $externalId = $data['login']['uuid'];

            $isNew = !User::where('email', $data['email'])->exists();

            // v1.4: dob.date is an ISO-8601 string e.g. "1988-05-31T18:24:05.987Z"
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
                [
                    'phone' => $data['phone'] ?? null,
                    'cell'  => $data['cell']  ?? null,
                ]
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
}
