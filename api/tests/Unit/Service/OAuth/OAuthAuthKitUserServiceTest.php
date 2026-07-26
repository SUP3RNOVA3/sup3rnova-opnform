<?php

use App\Integrations\OAuth\OAuthProviderService;
use App\Models\User;
use App\Service\OAuth\OAuthUserService;

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

it('links AuthKit to an existing verified user without requiring provider tokens', function () {
    $user = User::factory()->create([
        'email' => 'jual@sup3rnova.com',
    ]);

    $authenticatedUser = app(OAuthUserService::class)->findOrCreateUser([
        'provider_user_id' => 'user_authkit_test',
        'email' => 'JUAL@SUP3RNOVA.COM',
        'email_verified' => true,
        'name' => 'Jualfredo Pérez',
    ], OAuthProviderService::AuthKit);

    expect($authenticatedUser->is($user))->toBeTrue();

    $this->assertDatabaseHas('oauth_providers', [
        'user_id' => $user->id,
        'provider' => 'authkit',
        'provider_user_id' => 'user_authkit_test',
        'access_token' => '',
        'refresh_token' => '',
    ]);
});
