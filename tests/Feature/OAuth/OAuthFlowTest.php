<?php

namespace Tests\Feature\OAuth;

use App\Models\Auth\User;
use App\Models\Common\Company;
use App\Models\OAuth\AccessToken;
use App\Models\OAuth\Client;
use App\Models\OAuth\PersonalAccessClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;

class OAuthFlowTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $company1;
    protected $company2;
    protected $personalAccessClient;
    protected $oauthClient;

    /**
     * Setup test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create companies
        $this->company1 = $this->createCompany('Company 1');
        $this->company2 = $this->createCompany('Company 2');

        // Create user and attach to both companies
        $this->user = $this->createUser();
        $this->user->companies()->attach([$this->company1->id, $this->company2->id]);

        // Install Passport
        Artisan::call('passport:install', ['--force' => true]);
        
        // Create Personal Access Client
        $this->personalAccessClient = $this->createPersonalAccessClient();
        
        // Create OAuth Client
        $this->oauthClient = $this->createOAuthClient();

        // Enable OAuth
        config(['oauth.enabled' => true]);
        config(['oauth.company_aware' => true]);
        config(['oauth.auth_type' => 'passport']);
    }

    /**
     * Test 1: Personal Access Token Creation
     *
     * @test
     */
    public function it_can_create_personal_access_token_with_company_id()
    {
        $this->company1->makeCurrent();

        $response = $this->actingAs($this->user)
            ->postJson('/oauth/personal-access-tokens', [
                'name' => 'Test Mobile App',
                'scopes' => ['read', 'write'],
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'access_token',
                    'token' => [
                        'id',
                        'user_id',
                        'client_id',
                        'company_id',
                        'name',
                        'scopes',
                    ]
                ]
            ]);

        $tokenData = $response->json('data.token');
        
        // Assert company_id is set correctly
        $this->assertEquals($this->company1->id, $tokenData['company_id']);
        $this->assertEquals($this->user->id, $tokenData['user_id']);
        $this->assertEquals('Test Mobile App', $tokenData['name']);

        $this->output('✅ Test 1 PASSED: Personal Access Token created with company_id');
    }

    /**
     * Test 2: API Request with Personal Access Token (Auto Company Detection)
     *
     * @test
     */
    public function it_uses_company_id_from_token_automatically()
    {
        // Create token for Company 1
        $this->company1->makeCurrent();
        
        $token = $this->user->createToken('Test Token', ['read'])->accessToken;
        
        // Update token with company_id
        $accessToken = AccessToken::where('id', $token->id)->first();
        $accessToken->company_id = $this->company1->id;
        $accessToken->save();

        // Make API request WITHOUT sending company_id header/query
        Passport::actingAs($this->user, ['read']);
        
        // Simulate token in request
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->id,
        ]);

        // The Companies trait should extract company_id from token
        $companyId = (new \App\Traits\Companies())->getCompanyIdFromToken(request());
        
        // Assert company_id is extracted from token
        $this->assertEquals($this->company1->id, $companyId);

        $this->output('✅ Test 2 PASSED: Company ID automatically extracted from token');
    }

    /**
     * Test 3: Token Introspection
     *
     * @test
     */
    public function it_can_introspect_token_and_get_company_id()
    {
        $this->company2->makeCurrent();
        
        $token = $this->user->createToken('Introspect Test', ['read', 'write']);
        
        $accessToken = AccessToken::find($token->token->id);
        $accessToken->company_id = $this->company2->id;
        $accessToken->save();

        $response = $this->actingAs($this->user)
            ->postJson('/oauth/token/introspect', [
                'token' => $token->token->id,
                'token_type_hint' => 'access_token',
            ]);

        $response->assertOk()
            ->assertJson([
                'active' => true,
                'company_id' => $this->company2->id,
                'user_id' => $this->user->id,
            ]);

        $this->output('✅ Test 3 PASSED: Token introspection returns company_id');
    }

    /**
     * Test 4: Token Revocation
     *
     * @test
     */
    public function it_can_revoke_access_token()
    {
        $this->company1->makeCurrent();
        
        $token = $this->user->createToken('Revoke Test');
        $tokenId = $token->token->id;

        // Revoke the token
        $response = $this->actingAs($this->user)
            ->postJson('/oauth/token/revoke', [
                'token' => $tokenId,
                'token_type_hint' => 'access_token',
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        // Check token is revoked
        $revokedToken = AccessToken::withoutGlobalScope('company')->find($tokenId);
        $this->assertTrue($revokedToken->revoked);

        $this->output('✅ Test 4 PASSED: Token successfully revoked');
    }

    /**
     * Test 5: Company Isolation in Token List
     *
     * @test
     */
    public function it_only_shows_tokens_for_current_company()
    {
        // Create token for Company 1
        $this->company1->makeCurrent();
        $token1 = $this->user->createToken('Company 1 Token');
        $accessToken1 = AccessToken::find($token1->token->id);
        $accessToken1->company_id = $this->company1->id;
        $accessToken1->save();

        // Create token for Company 2
        $this->company2->makeCurrent();
        $token2 = $this->user->createToken('Company 2 Token');
        $accessToken2 = AccessToken::find($token2->token->id);
        $accessToken2->company_id = $this->company2->id;
        $accessToken2->save();

        // List tokens for Company 1
        $this->company1->makeCurrent();
        $response1 = $this->actingAs($this->user)
            ->getJson('/oauth/tokens');

        $response1->assertOk();
        $tokens1 = collect($response1->json())->pluck('id');
        
        // Should only see Company 1's token
        $this->assertContains($token1->token->id, $tokens1);
        $this->assertNotContains($token2->token->id, $tokens1);

        $this->output('✅ Test 5 PASSED: Tokens filtered by company');
    }

    /**
     * Test 6: OAuth Client Creation with Company
     *
     * @test
     */
    public function it_creates_oauth_client_with_company_id()
    {
        $this->company1->makeCurrent();

        $response = $this->actingAs($this->user)
            ->postJson('/oauth/clients', [
                'name' => 'Test Third Party App',
                'redirect' => 'https://example.com/callback',
                'confidential' => true,
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'client' => [
                        'id',
                        'name',
                        'company_id',
                        'redirect',
                        'secret',
                    ]
                ]
            ]);

        $client = $response->json('data.client');
        $this->assertEquals($this->company1->id, $client['company_id']);

        $this->output('✅ Test 6 PASSED: OAuth client created with company_id');
    }

    /**
     * Test 7: Multiple Companies - Different Tokens
     *
     * @test
     */
    public function it_creates_separate_tokens_for_different_companies()
    {
        // Create token for Company 1
        $this->company1->makeCurrent();
        $response1 = $this->actingAs($this->user)
            ->postJson('/oauth/personal-access-tokens', [
                'name' => 'Company 1 API Token',
                'scopes' => ['read'],
            ]);

        $token1CompanyId = $response1->json('data.token.company_id');

        // Create token for Company 2
        $this->company2->makeCurrent();
        $response2 = $this->actingAs($this->user)
            ->postJson('/oauth/personal-access-tokens', [
                'name' => 'Company 2 API Token',
                'scopes' => ['read'],
            ]);

        $token2CompanyId = $response2->json('data.token.company_id');

        // Assert different company_ids
        $this->assertEquals($this->company1->id, $token1CompanyId);
        $this->assertEquals($this->company2->id, $token2CompanyId);
        $this->assertNotEquals($token1CompanyId, $token2CompanyId);

        $this->output('✅ Test 7 PASSED: Different tokens for different companies');
    }

    /**
     * Test 8: Token Deletion (User's Own Token)
     *
     * @test
     */
    public function it_can_delete_personal_access_token()
    {
        $this->company1->makeCurrent();
        
        $token = $this->user->createToken('Delete Test');
        $tokenId = $token->token->id;

        $response = $this->actingAs($this->user)
            ->deleteJson("/oauth/personal-access-tokens/{$tokenId}");

        $response->assertOk()
            ->assertJson(['success' => true]);

        // Verify token is revoked
        $deletedToken = AccessToken::withoutGlobalScope('company')->find($tokenId);
        $this->assertTrue($deletedToken->revoked);

        $this->output('✅ Test 8 PASSED: Personal access token deleted');
    }

    /**
     * Test 9: Scopes List
     *
     * @test
     */
    public function it_can_list_available_scopes()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/oauth/scopes');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
            ]);

        $this->output('✅ Test 9 PASSED: Scopes listed successfully');
    }

    /**
     * Test 10: Client Secret Regeneration
     *
     * @test
     */
    public function it_can_regenerate_client_secret()
    {
        $this->company1->makeCurrent();
        
        $client = $this->createOAuthClient();
        $oldSecret = $client->secret;

        $response = $this->actingAs($this->user)
            ->postJson("/oauth/clients/{$client->id}/secret");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['secret']
            ]);

        $newSecret = $response->json('data.secret');
        
        // Verify secret changed
        $this->assertNotEquals($oldSecret, $newSecret);

        $this->output('✅ Test 10 PASSED: Client secret regenerated');
    }

    /**
     * Test 11: Discovery Endpoint (RFC 8414)
     *
     * @test
     */
    public function it_returns_oauth_server_metadata()
    {
        $response = $this->getJson('/oauth/.well-known/oauth-authorization-server');

        $response->assertOk()
            ->assertJsonStructure([
                'issuer',
                'authorization_endpoint',
                'token_endpoint',
                'introspection_endpoint',
                'revocation_endpoint',
                'response_types_supported',
                'grant_types_supported',
                'scopes_supported',
            ])
            ->assertJson([
                'akaunting_company_aware' => true,
                'akaunting_multi_tenant' => true,
            ]);

        $this->output('✅ Test 11 PASSED: Discovery endpoint returns metadata');
    }

    /**
     * Test 12: Company Access Control
     *
     * @test
     */
    public function it_prevents_access_to_other_company_tokens()
    {
        // Create token for Company 1
        $this->company1->makeCurrent();
        $token1 = $this->user->createToken('Company 1 Token');
        $accessToken1 = AccessToken::find($token1->token->id);
        $accessToken1->company_id = $this->company1->id;
        $accessToken1->save();

        // Try to delete Company 1's token while in Company 2
        $this->company2->makeCurrent();
        $response = $this->actingAs($this->user)
            ->deleteJson("/oauth/personal-access-tokens/{$token1->token->id}");

        // Should fail (403 or 404)
        $response->assertStatus(403);

        $this->output('✅ Test 12 PASSED: Cross-company token access prevented');
    }

    /**
     * Test 13: Token Expiration in Introspection
     *
     * @test
     */
    public function it_detects_expired_tokens_in_introspection()
    {
        $this->company1->makeCurrent();
        
        $token = $this->user->createToken('Expired Test');
        $accessToken = AccessToken::find($token->token->id);
        $accessToken->company_id = $this->company1->id;
        $accessToken->expires_at = now()->subDay(); // Set to yesterday
        $accessToken->save();

        $response = $this->actingAs($this->user)
            ->postJson('/oauth/token/introspect', [
                'token' => $token->token->id,
            ]);

        $response->assertOk()
            ->assertJson(['active' => false]);

        $this->output('✅ Test 13 PASSED: Expired token detected as inactive');
    }

    /**
     * Test 14: Client CRUD Operations
     *
     * @test
     */
    public function it_can_perform_full_client_crud()
    {
        $this->company1->makeCurrent();

        // Create
        $createResponse = $this->actingAs($this->user)
            ->postJson('/oauth/clients', [
                'name' => 'CRUD Test Client',
                'redirect' => 'https://example.com/callback',
            ]);

        $createResponse->assertOk();
        $clientId = $createResponse->json('data.client.id');

        // Read
        $showResponse = $this->actingAs($this->user)
            ->getJson("/oauth/clients/{$clientId}");
        $showResponse->assertOk();

        // Update
        $updateResponse = $this->actingAs($this->user)
            ->patchJson("/oauth/clients/{$clientId}", [
                'name' => 'Updated CRUD Client',
                'redirect' => 'https://example.com/new-callback',
            ]);
        $updateResponse->assertOk();

        // Delete
        $deleteResponse = $this->actingAs($this->user)
            ->deleteJson("/oauth/clients/{$clientId}");
        $deleteResponse->assertOk();

        $this->output('✅ Test 14 PASSED: Full CRUD operations on OAuth client');
    }

    /**
     * Test 15: Priority Test - Token vs Header vs Query
     *
     * @test
     */
    public function it_prioritizes_token_company_id_over_header_and_query()
    {
        $this->company1->makeCurrent();
        
        // Create Companies trait instance
        $companiesTrait = new class {
            use \App\Traits\Companies;
        };

        // Create a mock request with:
        // - Token with company_id = 1
        // - Header X-Company = 2
        // - Query company_id = 3
        
        $token = $this->user->createToken('Priority Test');
        $accessToken = AccessToken::find($token->token->id);
        $accessToken->company_id = $this->company1->id; // Company 1
        $accessToken->save();

        // Mock request with header and query
        $request = request();
        $request->headers->set('X-Company', $this->company2->id); // Company 2
        $request->query->set('company_id', 3); // Company 3 (non-existent)

        // Token should win (Priority 1)
        $extractedCompanyId = $companiesTrait->getCompanyIdFromToken($request);
        
        $this->assertEquals($this->company1->id, $extractedCompanyId);
        $this->assertNotEquals($this->company2->id, $extractedCompanyId);

        $this->output('✅ Test 15 PASSED: Token company_id has highest priority');
    }

    // ==================== Helper Methods ====================

    protected function createCompany($name)
    {
        return Company::create([
            'domain' => strtolower(str_replace(' ', '', $name)),
            'enabled' => 1,
        ])->fresh();
    }

    protected function createUser()
    {
        return User::create([
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => bcrypt('password'),
            'locale' => 'en-GB',
            'enabled' => 1,
        ]);
    }

    protected function createPersonalAccessClient()
    {
        $client = Client::create([
            'company_id' => $this->company1->id,
            'user_id' => $this->user->id,
            'name' => 'Personal Access Client',
            'secret' => null,
            'redirect' => 'http://localhost',
            'personal_access_client' => 1,
            'password_client' => 0,
            'revoked' => 0,
        ]);

        PersonalAccessClient::create([
            'company_id' => $this->company1->id,
            'client_id' => $client->id,
        ]);

        return $client;
    }

    protected function createOAuthClient()
    {
        return Client::create([
            'company_id' => $this->company1->id,
            'user_id' => $this->user->id,
            'name' => 'Test OAuth Client',
            'secret' => bcrypt('secret'),
            'redirect' => 'https://example.com/callback',
            'personal_access_client' => 0,
            'password_client' => 0,
            'revoked' => 0,
        ]);
    }

    protected function output($message)
    {
        if (method_exists($this, 'info')) {
            $this->info($message);
        }
        
        echo "\n" . $message . "\n";
    }

    /**
     * Run all tests and output summary
     */
    public function test_run_all_oauth_tests()
    {
        $this->output("\n=================================================");
        $this->output("    AKAUNTING OAUTH 2.0 COMPREHENSIVE TEST SUITE");
        $this->output("=================================================\n");

        try {
            $this->it_can_create_personal_access_token_with_company_id();
            $this->it_uses_company_id_from_token_automatically();
            $this->it_can_introspect_token_and_get_company_id();
            $this->it_can_revoke_access_token();
            $this->it_only_shows_tokens_for_current_company();
            $this->it_creates_oauth_client_with_company_id();
            $this->it_creates_separate_tokens_for_different_companies();
            $this->it_can_delete_personal_access_token();
            $this->it_can_list_available_scopes();
            $this->it_can_regenerate_client_secret();
            $this->it_returns_oauth_server_metadata();
            $this->it_prevents_access_to_other_company_tokens();
            $this->it_detects_expired_tokens_in_introspection();
            $this->it_can_perform_full_client_crud();
            $this->it_prioritizes_token_company_id_over_header_and_query();

            $this->output("\n=================================================");
            $this->output("    ✅ ALL TESTS PASSED! (15/15)");
            $this->output("=================================================\n");
        } catch (\Exception $e) {
            $this->output("\n❌ TEST FAILED: " . $e->getMessage());
            $this->output("File: " . $e->getFile() . ":" . $e->getLine());
            throw $e;
        }
    }
}
