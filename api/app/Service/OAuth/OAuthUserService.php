<?php

namespace App\Service\OAuth;

use App\Integrations\OAuth\OAuthProviderService;
use App\Models\User;
use App\Models\UserWorkspace;
use App\Models\OAuthProvider;
use App\Service\License\SelfHostedSeatLimitService;
use App\Service\WorkspaceInviteService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * OAuthUserService
 *
 * Handles OAuth user creation and lookup.
 * Responsibilities:
 * - Finding existing users by email and OAuth provider
 * - Creating new user accounts from OAuth data
 * - Assigning users to workspaces (default or invited)
 * - Retrieving and storing UTM tracking data
 * - Preventing duplicate account creation and account takeover
 *
 * Retrieves UTM data from OAuthContextService for consistent tracking
 * across both redirect-based and widget-based OAuth flows.
 */
class OAuthUserService
{
    public function __construct(
        private OAuthContextService $contextService,
    ) {
    }

    /**
     * Find existing user or create new one from OAuth data
     *
     * @param array $userData OAuth user data (name, email, provider_user_id, etc.)
     * @param OAuthProviderService $providerService The OAuth provider (Google, GitHub, etc.)
     * @param string|null $inviteToken Workspace invitation token if applicable
     * @return User Created or existing user with new_user flag if newly created
     * @throws HttpResponseException If email already exists under different provider
     */
    public function findOrCreateUser(array $userData, OAuthProviderService $providerService, ?string $inviteToken = null): User
    {
        $email = strtolower($userData['email']);
        $user = User::whereEmail($email)->first();

        if ($user) {
            // Check if user has this specific OAuth provider linked
            $hasOAuthProvider = $user->oauthProviders()
                ->where('provider', $providerService->getDatabaseProvider())
                ->where('provider_user_id', $userData['provider_user_id'])
                ->exists();

            if ($hasOAuthProvider) {
                // User has this specific OAuth provider linked - allow authentication
                return $user;
            }

            if (
                $providerService === OAuthProviderService::AuthKit
                && ($userData['email_verified'] ?? false)
            ) {
                OAuthProvider::create([
                    'user_id' => $user->id,
                    'provider' => $providerService->getDatabaseProvider(),
                    'provider_user_id' => $userData['provider_user_id'],
                    'name' => $userData['name'],
                    'email' => $email,
                    'access_token' => null,
                    'refresh_token' => null,
                    'scopes' => [],
                ]);

                return $user;
            }

            // User exists but doesn't have this OAuth provider linked and no password
            // This prevents account takeover via different OAuth providers with same email
            throw new HttpResponseException(
                response()->json([
                    'message' => 'An account with this email already exists. Please sign in with your original method or contact support.',
                    'error' => 'email_already_exists_different_provider'
                ], 409)
            );
        }

        // No existing user - create new account
        // Check if registration is allowed in self-hosted mode
        if (config('app.self_hosted') && empty($inviteToken) && app()->environment() !== 'testing') {
            abort(422, 'User registration is not allowed.');
        }

        // Retrieve UTM data from context service (works for both redirect and widget flows)
        // For redirect flows: gets from state token
        // For widget flows: gets from session context
        $utmData = $this->contextService->getUtmData() ?? $this->contextService->getWidgetContext()['utm_data'] ?? null;

        app(SelfHostedSeatLimitService::class)->assertCanCreateUser($email);

        // Validate and consume the invitation before creating the local account.
        $workspaceInviteService = app(WorkspaceInviteService::class);
        [$workspace, $role] = $workspaceInviteService->getWorkspaceAndRole([
            'email' => $email,
            'invite_token' => $inviteToken,
        ]);

        $user = User::create([
            'name' => $userData['name'],
            'email' => $email,
            'password' => Hash::make(Str::random(64)),
            'hear_about_us' => null,
            'utm_data' => $utmData,
            'meta' => [
                'signup_provider' => $providerService->value,
                'signup_provider_user_id' => $userData['provider_user_id'] ?? null,
            ],
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        UserWorkspace::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);

        $user->new_user = true;

        return $user;
    }
}
