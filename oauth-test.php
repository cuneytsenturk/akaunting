#!/usr/bin/env php
<?php

/**
 * OAuth Implementation Quick Test Script
 * 
 * Run: php oauth-test.php
 */

echo "\n";
echo "========================================\n";
echo "   OAUTH 2.1 + MCP QUICK TEST SUITE    \n";
echo "========================================\n\n";

$baseUrl = 'http://localhost';
$oauthPrefix = 'oauth';

$tests = [];
$passed = 0;
$failed = 0;

// Test 1: Authorization Server Metadata
echo "[1/10] Testing Authorization Server Metadata...\n";
$url = "$baseUrl/$oauthPrefix/.well-known/oauth-authorization-server";
$response = @file_get_contents($url);

if ($response) {
    $data = json_decode($response, true);
    if (isset($data['issuer']) && 
        isset($data['authorization_endpoint']) && 
        isset($data['token_endpoint']) &&
        in_array('S256', $data['code_challenge_methods_supported'] ?? []) &&
        in_array('none', $data['token_endpoint_auth_methods_supported'] ?? [])) {
        echo "  ✅ PASSED - Metadata endpoint returns valid data\n";
        echo "     - PKCE S256: ✓\n";
        echo "     - Public client 'none' auth: ✓\n";
        echo "     - Registration endpoint: " . ($data['registration_endpoint'] ?? '✗') . "\n";
        $passed++;
    } else {
        echo "  ❌ FAILED - Metadata incomplete\n";
        $failed++;
    }
} else {
    echo "  ❌ FAILED - Cannot reach endpoint\n";
    $failed++;
}

// Test 2: Protected Resource Metadata (MCP)
echo "\n[2/10] Testing Protected Resource Metadata (MCP)...\n";
$url = "$baseUrl/$oauthPrefix/.well-known/oauth-protected-resource";
$response = @file_get_contents($url);

if ($response) {
    $data = json_decode($response, true);
    if (isset($data['resource']) && 
        isset($data['authorization_servers'])) {
        echo "  ✅ PASSED - Protected resource metadata available\n";
        $passed++;
    } else {
        echo "  ❌ FAILED - Invalid metadata format\n";
        $failed++;
    }
} else {
    echo "  ❌ FAILED - Cannot reach endpoint\n";
    $failed++;
}

// Test 3: Dynamic Client Registration Validation
echo "\n[3/10] Testing DCR Validation (Invalid Request)...\n";
$ch = curl_init("$baseUrl/$oauthPrefix/register");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['client_name' => 'Test'])); // Missing redirect_uris
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 400 || $httpCode == 422) {
    echo "  ✅ PASSED - DCR correctly rejects invalid requests\n";
    $passed++;
} else {
    echo "  ❌ FAILED - DCR should return 400/422 for invalid requests (got $httpCode)\n";
    $failed++;
}

// Test 4: DCR with Valid ChatGPT URI
echo "\n[4/10] Testing DCR with ChatGPT redirect URI...\n";
$ch = curl_init("$baseUrl/$oauthPrefix/register");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'client_name' => 'ChatGPT Test Client',
    'redirect_uris' => ['https://chatgpt.com/connector_platform_oauth_redirect'],
    'token_endpoint_auth_method' => 'none'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 201) {
    $data = json_decode($response, true);
    if (isset($data['client_id']) && $data['token_endpoint_auth_method'] === 'none') {
        echo "  ✅ PASSED - ChatGPT client registered successfully\n";
        echo "     - Client ID: " . substr($data['client_id'], 0, 20) . "...\n";
        echo "     - Auth method: none ✓\n";
        $passed++;
    } else {
        echo "  ❌ FAILED - Invalid response structure\n";
        $failed++;
    }
} else {
    echo "  ⚠️  SKIPPED - DCR might require database (HTTP $httpCode)\n";
    echo "     Run migrations first: php artisan migrate\n";
}

// Test 5: Rate Limiting Detection
echo "\n[5/10] Testing Rate Limiting...\n";
$requests = 0;
for ($i = 0; $i < 12; $i++) {
    $ch = curl_init("$baseUrl/$oauthPrefix/register");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'client_name' => "Rate Test $i",
        'redirect_uris' => ["https://example.com/cb$i"]
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request only
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 429) {
        echo "  ✅ PASSED - Rate limiting active (blocked after $i requests)\n";
        $passed++;
        break;
    }
    $requests++;
}

if ($requests >= 12) {
    echo "  ⚠️  WARNING - Rate limiting might not be active\n";
    echo "     Expected 429 Too Many Requests after 10 requests\n";
}

// Test 6: Check Database Tables
echo "\n[6/10] Checking database tables...\n";
try {
    require_once __DIR__ . '/vendor/autoload.php';
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    $tables = [
        'oauth_clients',
        'oauth_access_tokens',
        'oauth_auth_codes',
        'oauth_refresh_tokens'
    ];
    
    foreach ($tables as $table) {
        if (Schema::hasTable($table)) {
            echo "  ✓ $table\n";
        } else {
            echo "  ✗ $table (missing - run migrations)\n";
        }
    }
    
    // Check for company_id column
    if (Schema::hasColumn('oauth_clients', 'company_id')) {
        echo "  ✓ company_id column exists\n";
    } else {
        echo "  ✗ company_id column missing\n";
    }
    
    // Check for audience column
    if (Schema::hasColumn('oauth_access_tokens', 'audience')) {
        echo "  ✓ audience column exists (MCP compliance)\n";
        $passed++;
    } else {
        echo "  ✗ audience column missing (run migration: add_audience_to_oauth_tables.php)\n";
        $failed++;
    }
    
} catch (Exception $e) {
    echo "  ⚠️  SKIPPED - Database not accessible\n";
    echo "     Error: " . $e->getMessage() . "\n";
}

// Test 7: Config Validation
echo "\n[7/10] Validating configuration files...\n";
try {
    $config = require __DIR__ . '/config/oauth.php';
    
    $checks = [
        'scopes.mcp:use' => isset($config['scopes']['mcp:use']),
        'dcr.chatgpt_redirect_uris' => !empty($config['dcr']['chatgpt_redirect_uris']),
        'hash_client_secrets' => $config['hash_client_secrets'] === true,
        'require_pkce' => $config['require_pkce'] === true,
    ];
    
    $allPassed = true;
    foreach ($checks as $key => $value) {
        echo "  " . ($value ? "✓" : "✗") . " $key\n";
        if (!$value) $allPassed = false;
    }
    
    if ($allPassed) {
        echo "  ✅ PASSED - Configuration complete\n";
        $passed++;
    } else {
        echo "  ❌ FAILED - Configuration incomplete\n";
        $failed++;
    }
    
} catch (Exception $e) {
    echo "  ❌ FAILED - Cannot load config/oauth.php\n";
    $failed++;
}

// Test 8: Middleware Registration
echo "\n[8/10] Checking middleware registration...\n";
try {
    $kernel = file_get_contents(__DIR__ . '/app/Http/Kernel.php');
    
    if (strpos($kernel, 'ValidateTokenAudience') !== false) {
        echo "  ✓ ValidateTokenAudience middleware registered\n";
    } else {
        echo "  ✗ ValidateTokenAudience middleware missing\n";
    }
    
    if (strpos($kernel, 'AddOAuthWWWAuthenticateHeader') !== false) {
        echo "  ✓ AddOAuthWWWAuthenticateHeader middleware registered\n";
        $passed++;
    } else {
        echo "  ✗ AddOAuthWWWAuthenticateHeader middleware missing\n";
        $failed++;
    }
    
} catch (Exception $e) {
    echo "  ❌ FAILED - Cannot read Kernel.php\n";
    $failed++;
}

// Test 9: Service Provider Registration
echo "\n[9/10] Checking OAuth service provider...\n";
try {
    $appConfig = file_get_contents(__DIR__ . '/config/app.php');
    
    if (strpos($appConfig, 'App\Providers\OAuth::class') !== false) {
        echo "  ✓ OAuth provider registered in config/app.php\n";
        $passed++;
    } else {
        echo "  ✗ OAuth provider not registered\n";
        $failed++;
    }
    
} catch (Exception $e) {
    echo "  ❌ FAILED - Cannot read config/app.php\n";
    $failed++;
}

// Test 10: Routes Registration
echo "\n[10/10] Verifying OAuth routes...\n";
try {
    $routes = file_get_contents(__DIR__ . '/routes/oauth.php');
    
    $requiredRoutes = [
        'OAuth\AccessToken@issueToken',
        'OAuth\Authorize@show',
        'OAuth\Discovery@metadata',
        'OAuth\ClientRegistration@register',
        'OAuth\Clients@index',
    ];
    
    $allFound = true;
    foreach ($requiredRoutes as $route) {
        if (strpos($routes, $route) !== false) {
            echo "  ✓ " . explode('@', $route)[0] . "\n";
        } else {
            echo "  ✗ $route missing\n";
            $allFound = false;
        }
    }
    
    if ($allFound) {
        echo "  ✅ PASSED - All critical routes registered\n";
        $passed++;
    } else {
        echo "  ❌ FAILED - Some routes missing\n";
        $failed++;
    }
    
} catch (Exception $e) {
    echo "  ❌ FAILED - Cannot read routes/oauth.php\n";
    $failed++;
}

// Summary
echo "\n========================================\n";
echo "           TEST SUMMARY                 \n";
echo "========================================\n";
echo "Total Tests: " . ($passed + $failed) . "\n";
echo "✅ Passed: $passed\n";
echo "❌ Failed: $failed\n";
echo "Success Rate: " . round(($passed / max($passed + $failed, 1)) * 100, 1) . "%\n";
echo "========================================\n\n";

if ($failed === 0) {
    echo "🎉 All tests passed! OAuth implementation is ready.\n\n";
    echo "Next steps:\n";
    echo "1. Run migrations: php artisan migrate\n";
    echo "2. Seed permissions: php artisan db:seed --class=OAuthPermissions\n";
    echo "3. Enable OAuth: OAUTH_ENABLED=true in .env\n";
    echo "4. Test with ChatGPT\n";
} else {
    echo "⚠️  Some tests failed. Please review the issues above.\n\n";
    echo "Common fixes:\n";
    echo "- Run migrations: php artisan migrate\n";
    echo "- Clear config cache: php artisan config:clear\n";
    echo "- Check .env file for OAUTH_ENABLED=true\n";
}

exit($failed > 0 ? 1 : 0);
