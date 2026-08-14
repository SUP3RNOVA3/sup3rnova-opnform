<?php

namespace App\Integrations\OAuth\Drivers;

use App\Integrations\OAuth\Drivers\Contracts\OAuthDriver;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Contracts\User;
use Laravel\Socialite\Two\User as SocialiteUser;

class OAuthAuthKitDriver implements OAuthDriver
{
    private ?string $redirectUrl = null;
    private ?string $state = null;

    public function getRedirectUrl(): string
    {
        $parameters = array_filter([
            'client_id' => config('services.authkit.client_id'),
            'redirect_uri' => $this->redirectUrl ?? config('services.authkit.redirect'),
            'response_type' => 'code',
            'provider' => 'authkit',
            'state' => $this->state,
            'organization_id' => config('services.authkit.organization_id'),
        ]);

        return $this->apiUrl('/user_management/authorize') . '?' . http_build_query($parameters);
    }

    public function getUser(): User
    {
        $code = request()->string('code')->toString();
        abort_if(empty($code), 400, 'Missing AuthKit authorization code.');

        $response = Http::acceptJson()
            ->asJson()
            ->timeout(10)
            ->post($this->apiUrl('/user_management/authenticate'), [
                'client_id' => config('services.authkit.client_id'),
                'client_secret' => config('services.authkit.api_key'),
                'code' => $code,
                'grant_type' => 'authorization_code',
            ]);

        abort_unless($response->successful(), 401, 'AuthKit authentication failed.');

        $user = $response->json('user');
        abort_unless(is_array($user) && !empty($user['id']) && !empty($user['email']), 401, 'AuthKit did not return a valid user.');
        abort_unless(($user['email_verified'] ?? false) === true, 401, 'AuthKit email must be verified.');

        $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))
            ?: $user['email'];

        return (new SocialiteUser())->setRaw($user)->map([
            'id' => $user['id'],
            'name' => $name,
            'email' => strtolower($user['email']),
            'avatar' => $user['profile_picture_url'] ?? null,
            'email_verified' => true,
        ]);
    }

    public function canCreateUser(): bool
    {
        return true;
    }

    public function setRedirectUrl(string $url): self
    {
        $this->redirectUrl = $url;
        return $this;
    }

    public function setScopes(array $scopes): self
    {
        return $this;
    }

    public function setState(string $state): self
    {
        $this->state = $state;
        return $this;
    }

    public function getScopesForIntent(string $intent): array
    {
        return [];
    }

    private function apiUrl(string $path): string
    {
        $hostname = preg_replace(
            '~^https?://~i',
            '',
            trim((string) config('services.authkit.api_hostname', 'api.workos.com')),
        );

        return 'https://' . rtrim($hostname, '/') . '/' . ltrim($path, '/');
    }
}
