<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class PersonalAccessTokenController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token_name' => ['required', 'string', 'max:255'],
            'abilities' => ['nullable', 'string'],
        ]);

        $token = $request->user()->createToken(
            $validated['token_name'],
            $this->parseAbilities($validated['abilities'] ?? null),
        );

        return redirect()
            ->route('dashboard')
            ->with('plain_text_token', $token->plainTextToken)
            ->with('status', 'API token created.');
    }

    public function destroy(Request $request, PersonalAccessToken $token): RedirectResponse
    {
        $token = $request->user()->tokens()->findOrFail($token->getKey());
        $token->delete();

        return redirect()
            ->route('dashboard')
            ->with('status', 'API token revoked.');
    }

    /**
     * @return list<string>
     */
    private function parseAbilities(?string $abilities): array
    {
        $parsed = collect(explode(',', (string) $abilities))
            ->map(fn (string $ability) => trim($ability))
            ->filter()
            ->values()
            ->all();

        return $parsed === [] ? ['*'] : $parsed;
    }
}
