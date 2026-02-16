# OAuth 2.1 + MCP Implementation Test Checklist

## ✅ PHASE 1: Configuration Tests

### Config Files
- [ ] `config/oauth.php` exists and has all required keys
  - [x] enabled
  - [x] scopes (mcp:use included)
  - [x] dcr configuration
  - [x] hash_client_secrets = true
  - [x] require_pkce = true
  - [x] ChatGPT redirect URIs

- [ ] `config/passport.php` exists
  - [x] Personal access client config
  - [x] Token expiration settings

### Service Providers
- [x] `App\Providers\OAuth` registered in config/app.php
- [x] OAuth provider boots correctly
- [x] Custom models registered with Passport

---

## ✅ PHASE 2: Database Tests

### Migrations
- [x] `oauth_v1.php` migration exists
  - oauth_clients table
  - oauth_access_tokens table
  - oauth_auth_codes table
  - oauth_refresh_tokens table
  - oauth_personal_access_clients table

- [x] `add_audience_to_oauth_tables.php` migration exists
  - audience column in oauth_access_tokens
  - audience column in oauth_auth_codes
  - audience column in oauth_refresh_tokens

### Run Migrations
```bash
php artisan migrate --path=database/migrations/2026_02_14_000000_oauth_v1.php
php artisan migrate --path=database/migrations/2026_02_15_000000_add_audience_to_oauth_tables.php
```

### Verify Tables
```sql
DESCRIBE oauth_clients;
DESCRIBE oauth_access_tokens;
DESCRIBE oauth_auth_codes;
DESCRIBE oauth_refresh_tokens;

-- Check company_id column exists
SELECT company_id FROM oauth_clients LIMIT 1;

-- Check audience column exists
SELECT audience FROM oauth_access_tokens LIMIT 1;
```

---

## ✅ PHASE 3: Model Tests

### OAuth Models
- [x] `App\Models\OAuth\Client` extends PassportClient
  - company_id field
  - Global company scope
  - isPublicClient() method
  - requiresPKCE() method

- [x] `App\Models\OAuth\AccessToken` extends PassportAccessToken
  - company_id field
  - audience field
  - Inherits audience from AuthCode

- [x] `App\Models\OAuth\AuthCode` extends PassportAuthCode
  - company_id field
  - audience field

- [x] `App\Models\OAuth\RefreshToken` extends PassportRefreshToken
  - Inherits audience from AccessToken

### Test Model Behavior
```php
// In tinker: php artisan tinker

// Test Client creation
$client = App\Models\OAuth\Client::create([
    'name' => 'Test Client',
    'secret' => null, // Public client
    'redirect' => 'https://example.com/callback',
    'personal_access_client' => false,
    'password_client' => false,
    'revoked' => false,
]);

// Verify company_id auto-assigned
echo $client->company_id; // Should be current company_id

// Test public client
echo $client->isPublicClient() ? 'YES' : 'NO'; // Should be YES
echo $client->requiresPKCE() ? 'YES' : 'NO'; // Should be YES
```

---

## ✅ PHASE 4: Middleware Tests

### Registered Middleware
- [x] `ValidateTokenAudience` in Kernel.php
- [x] `AddOAuthWWWAuthenticateHeader` in Kernel.php

### Test WWW-Authenticate Headers
```bash
# Should return 401 with WWW-Authenticate header
curl -I http://localhost/oauth/token/introspect
```

Expected:
```
HTTP/1.1 401 Unauthorized
WWW-Authenticate: Bearer realm="OAuth 2.0"
```

---

## ✅ PHASE 5: Route Tests

### Discovery Endpoints
```bash
# Authorization Server Metadata (RFC 8414)
curl http://localhost/oauth/.well-known/oauth-authorization-server | jq

# Protected Resource Metadata (RFC 9728) - MCP REQUIRED
curl http://localhost/oauth/.well-known/oauth-protected-resource | jq

# Verify response contains:
# - issuer
# - authorization_endpoint
# - token_endpoint
# - code_challenge_methods_supported: ["S256"]
# - token_endpoint_auth_methods_supported includes "none"
# - registration_endpoint
```

### Dynamic Client Registration (RFC 7591)
```bash
# Test ChatGPT redirect URI
curl -X POST http://localhost/oauth/register \
  -H "Content-Type: application/json" \
  -d '{
    "client_name": "ChatGPT Test",
    "redirect_uris": ["https://chatgpt.com/connector_platform_oauth_redirect"],
    "token_endpoint_auth_method": "none"
  }' | jq

# Expected response:
# {
#   "client_id": "...",
#   "client_name": "ChatGPT Test",
#   "redirect_uris": [...],
#   "token_endpoint_auth_method": "none",
#   "grant_types": ["authorization_code", "refresh_token"]
# }
```

### Rate Limiting Test
```bash
# Try registering 11 clients from same IP (should fail on 11th)
for i in {1..11}; do
  curl -X POST http://localhost/oauth/register \
    -H "Content-Type: application/json" \
    -d "{\"client_name\":\"Test $i\",\"redirect_uris\":[\"https://example.com/cb$i\"]}"
done
```

---

## ✅ PHASE 6: Controller Tests

### ClientRegistration Controller
- [x] register() method uses ClientRegistrationRequest
- [x] Validates redirect URIs (HTTPS required)
- [x] ChatGPT URIs whitelisted
- [x] Public clients get token_endpoint_auth_method: "none"

### Authorize Controller
- [x] show() method displays company selection
- [x] approve() uses AuthorizeRequest
- [x] Company_id validation via Form Request
- [x] Token inherits company_id

### Clients Controller
- [x] index() shows authorized apps
- [x] revoke() revokes all tokens
- [x] destroy() deletes dynamic clients only
- [x] Permission middleware applied

---

## ✅ PHASE 7: Form Request Tests

### ClientRegistration Request
```php
// Test validation rules
$request = new App\Http\Requests\OAuth\ClientRegistration;
$rules = $request->rules();

// Should require HTTPS redirect URIs
// Should allow known scopes
// Should validate email contacts
```

### AuthorizeRequest
```php
// Test company access validation
$request = new App\Http\Requests\OAuth\AuthorizeRequest;

// Should verify user has access to company_id
// Should fail if company doesn't belong to user
```

---

## ✅ PHASE 8: UI/UX Tests

### Authorization Page
- [ ] Visit `/oauth/authorize?client_id=...&redirect_uri=...&response_type=code&state=...`
- [ ] Company selection displayed if multiple companies
- [ ] Company logo shown
- [ ] Scopes listed correctly
- [ ] Approve/Deny buttons functional

### Authorized Applications Page
- [ ] Visit `/oauth/clients`
- [ ] Shows list of authorized apps
- [ ] Active token count displayed
- [ ] Revoke access button works
- [ ] Delete dynamic client works

### Blade Components
- [x] Uses `<x-layouts.auth>`
- [x] Uses `<x-form>`, `<x-button>`
- [x] Tailwind CSS classes applied
- [x] Akaunting logo displayed

---

## ✅ PHASE 9: Security Tests

### PKCE Enforcement
```bash
# Public client WITHOUT PKCE should fail
curl -X POST http://localhost/oauth/token \
  -d "grant_type=authorization_code" \
  -d "client_id=PUBLIC_CLIENT_ID" \
  -d "code=AUTH_CODE" \
  -d "redirect_uri=https://example.com/callback"

# Expected: Error "PKCE code_verifier is required"
```

### Audience Validation
```bash
# Token with wrong audience should be rejected
# (Tested via middleware)
```

### Client Secret Hashing
```php
// In database, client secrets should be hashed
$client = App\Models\OAuth\Client::find(1);
// secret field should start with "$2y$" (bcrypt hash)
```

---

## ✅ PHASE 10: Cleanup Job Tests

```bash
# Run cleanup command
php artisan oauth:cleanup --all

# Test specific cleanup
php artisan oauth:cleanup --clients
php artisan oauth:cleanup --tokens
php artisan oauth:cleanup --codes
php artisan oauth:cleanup --refresh-tokens
```

Verify:
- Expired clients deleted (90+ days unused)
- Expired tokens deleted
- Old auth codes deleted

---

## ✅ PHASE 11: Permission Tests

```bash
# Seed OAuth permissions
php artisan db:seed --class=OAuthPermissions
```

Verify permissions created:
- read-oauth-clients
- update-oauth-clients
- delete-oauth-clients
- read-oauth-tokens
- create-oauth-tokens
- delete-oauth-tokens

---

## ✅ PHASE 12: MCP Compliance Tests

### Required Features Checklist
- [x] Authorization Server Metadata endpoint
- [x] Protected Resource Metadata endpoint
- [x] PKCE S256 support
- [x] Public client "none" auth method
- [x] Resource indicators (audience)
- [x] mcp:use scope
- [x] Dynamic Client Registration

### ChatGPT Integration Test
1. ChatGPT calls DCR endpoint
2. Receives client_id
3. Redirects user to /oauth/authorize
4. User approves with company selection
5. ChatGPT receives authorization code
6. Exchanges code for token with PKCE
7. Token has audience and company_id

---

## ✅ PHASE 13: Multi-Tenancy Tests

### Company Isolation
```php
// User in Company A
company_id(1);

// Create client
$client = App\Models\OAuth\Client::create([...]);
echo $client->company_id; // Should be 1

// Switch to Company B
company_id(2);

// Try to access Company A's client
$found = App\Models\OAuth\Client::find($client->id);
echo $found; // Should be NULL (global scope filters it)

// Access without scope
$found = App\Models\OAuth\Client::withoutGlobalScope('company')->find($client->id);
echo $found; // Should work
```

### Token Company Association
```php
// Token should only access data from its company
$token = App\Models\OAuth\AccessToken::find(1);
echo $token->company_id; // Should match client's company_id

// API requests with this token should be scoped to company_id
```

---

## ✅ PHASE 14: Error Handling Tests

### Invalid Requests
```bash
# Missing redirect_uri
curl -X POST http://localhost/oauth/register \
  -H "Content-Type: application/json" \
  -d '{"client_name":"Test"}'
# Expected: 400 Bad Request

# Invalid redirect_uri (HTTP instead of HTTPS)
curl -X POST http://localhost/oauth/register \
  -H "Content-Type: application/json" \
  -d '{
    "client_name":"Test",
    "redirect_uris":["http://example.com/callback"]
  }'
# Expected: 400 Bad Request
```

---

## 🎯 FINAL VERIFICATION

### Production Readiness Checklist
- [ ] All migrations run successfully
- [ ] OAuth provider registered
- [ ] Middleware registered
- [ ] Routes accessible
- [ ] Discovery endpoints return valid JSON
- [ ] DCR accepts ChatGPT URIs
- [ ] PKCE enforced for public clients
- [ ] Company-aware tokens working
- [ ] Rate limiting active
- [ ] Cleanup job functional
- [ ] UI pages render correctly
- [ ] Permissions seeded
- [ ] Form Request validation working
- [ ] Error messages translated

### Performance Tests
- [ ] 100 concurrent authorization requests
- [ ] 1000 token validations per second
- [ ] Discovery endpoint cached
- [ ] Database queries optimized (N+1 check)

---

## 📊 Test Results Summary

**Total Tests:** 80+
**Passed:** ___
**Failed:** ___
**Skipped:** ___

### Critical Issues Found:
_[List any blocking issues]_

### Non-Critical Issues:
_[List minor issues for future improvement]_

---

## 🚀 Next Steps

1. Run all database migrations
2. Seed OAuth permissions
3. Test DCR endpoint with curl
4. Test authorization flow with real client
5. Monitor rate limiting logs
6. Schedule cleanup job in cron
7. Enable in production: `OAUTH_ENABLED=true`
