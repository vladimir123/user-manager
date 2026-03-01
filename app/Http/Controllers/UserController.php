<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search');

        $users = User::with(['contact', 'address'])
            ->when($search, fn($q) => $q
                ->where('first_name', 'ilike', "%{$search}%")
                ->orWhere('last_name', 'ilike', "%{$search}%")
                ->orWhere('email', 'ilike', "%{$search}%")
                ->orWhere('username', 'ilike', "%{$search}%")
            )
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => ['search' => $search->toString()],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Users/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'username' => 'nullable|string|max:100|unique:users,username',
            'gender' => 'nullable|in:male,female,other',
            'date_of_birth' => 'nullable|date',
            'nationality' => 'nullable|string|max:10',
            'contact.phone' => 'nullable|string|max:50',
            'contact.cell' => 'nullable|string|max:50',
            'address.street_number' => 'nullable|string|max:20',
            'address.street_name' => 'nullable|string|max:200',
            'address.city' => 'nullable|string|max:100',
            'address.state' => 'nullable|string|max:100',
            'address.postcode' => 'nullable|string|max:20',
            'address.country' => 'nullable|string|max:100',
        ]);

        $user = User::create([
            ...$validated,
            'name' => $validated['first_name'] . ' ' . $validated['last_name'],
            'password' => bcrypt('password'),
        ]);

        if (!empty($validated['contact'])) {
            $user->contact()->create($validated['contact']);
        }
        if (!empty($validated['address'])) {
            $user->address()->create($validated['address']);
        }

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Users/Edit', [
            'user' => $user->load(['contact', 'address']),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => "required|email|unique:users,email,{$user->id}",
            'username' => "nullable|string|max:100|unique:users,username,{$user->id}",
            'gender' => 'nullable|in:male,female,other',
            'date_of_birth' => 'nullable|date',
            'nationality' => 'nullable|string|max:10',
            'contact.phone' => 'nullable|string|max:50',
            'contact.cell' => 'nullable|string|max:50',
            'address.street_number' => 'nullable|string|max:20',
            'address.street_name' => 'nullable|string|max:200',
            'address.city' => 'nullable|string|max:100',
            'address.state' => 'nullable|string|max:100',
            'address.postcode' => 'nullable|string|max:20',
            'address.country' => 'nullable|string|max:100',
        ]);

        $user->update([
            ...$validated,
            'name' => $validated['first_name'] . ' ' . $validated['last_name'],
        ]);

        if (!empty($validated['contact'])) {
            $user->contact()->updateOrCreate(['user_id' => $user->id], $validated['contact']);
        }
        if (!empty($validated['address'])) {
            $user->address()->updateOrCreate(['user_id' => $user->id], $validated['address']);
        }

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:users,id',
        ]);

        $count = User::whereIn('id', $validated['ids'])->delete();

        return redirect()->route('users.index')
            ->with('success', "{$count} user(s) deleted successfully.");
    }
}
