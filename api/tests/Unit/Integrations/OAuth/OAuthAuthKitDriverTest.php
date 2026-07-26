<?php

use App\Integrations\OAuth\Drivers\OAuthAuthKitDriver;
use Illuminate\Support\Facades\Http;

uses(\Tests\TestCase::class);

it('builds an AuthKit authorization URL scoped to the configured organization', function () {
    config()->set('services.authkit.client_id', 'client_test');
    config()->set('services.authkit.redirect', 'https://forms.example.com/oauth/authkit/callback');
    config()->set('services.authkit.organization_id', 'org_test');

    $url = (new OAuthAuthKitDriver())->setState('state_test')->getRedirectUrl();
    parse_str(parse_url($url, PHP_URL_QUERY), $query);

    expect(parse_url($url, PHP_URL_PATH))->toBe('/user_management/authorize')
        ->and($query)->toMatchArray([
            'client_id' => 'client_test',
            'redirect_uri' => 'https://forms.example.com/oauth/authkit/callback',
            'response_type' => 'code',
            'provider' => 'authkit',
            'state' => 'state_test',
            'organization_id' => 'org_test',
        ]);
});

it('maps a verified AuthKit user without persisting WorkOS session tokens', function () {
    config()->set('services.authkit.client_id', 'client_test');
    config()->set('services.authkit.api_key', 'sk_test');
    request()->merge(['code' => 'code_test']);

    Http::fake([
        'api.workos.com/user_management/authenticate' => Http::response([
            'user' => [
                'id' => 'user_test',
                'email' => 'PERSON@SUP3RNOVA.COM',
                'email_verified' => true,
                'first_name' => 'Test',
                'last_name' => 'Person',
            ],
            'access_token' => 'must_not_be_persisted',
            'refresh_token' => 'must_not_be_persisted',
        ]),
    ]);

    $user = (new OAuthAuthKitDriver())->getUser();

    expect($user->getId())->toBe('user_test')
        ->and($user->getEmail())->toBe('person@sup3rnova.com')
        ->and($user->getName())->toBe('Test Person')
        ->and($user->token)->toBeNull()
        ->and($user->refreshToken)->toBeNull();
});

it('rejects an unverified AuthKit email', function () {
    config()->set('services.authkit.client_id', 'client_test');
    config()->set('services.authkit.api_key', 'sk_test');
    request()->merge(['code' => 'code_test']);

    Http::fake([
        'api.workos.com/user_management/authenticate' => Http::response([
            'user' => [
                'id' => 'user_test',
                'email' => 'person@example.com',
                'email_verified' => false,
            ],
        ]),
    ]);

    (new OAuthAuthKitDriver())->getUser();
})->throws(\Symfony\Component\HttpKernel\Exception\HttpException::class);
