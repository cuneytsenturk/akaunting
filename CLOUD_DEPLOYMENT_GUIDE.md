# 🚀 Akaunting Cloud Deployment & ChatGPT Integration Guide

## 📋 İÇİNDEKİLER
1. [OAuth Modülü Oluşturma](#1-oauth-modülü-oluşturma)
2. [Akaunting Cloud'a Yükleme](#2-akaunting-clouda-yükleme)
3. [ChatGPT ile Bağlanma](#3-chatgpt-ile-bağlanma)
4. [Test & Troubleshooting](#4-test--troubleshooting)

---

## 1️⃣ OAUTH MODÜLÜ OLUŞTURMA

Akaunting Cloud'da kullanabilmek için OAuth entegrasyonunu bir modül haline getirmeliyiz.

### Adım 1.1: Modül Klasör Yapısını Oluştur

```bash
# modules klasöründe OAuth klasörü oluştur
mkdir modules/OAuth
cd modules/OAuth
```

### Adım 1.2: Modül Dosyalarını Taşı/Kopyala

Aşağıdaki dosyaları `modules/OAuth/` klasörüne organize edin:

```
modules/OAuth/
├── module.json                 # Modül tanım dosyası (YENİ)
├── composer.json               # Bağımlılıklar (YENİ)
├── Config/
│   └── oauth.php              # config/oauth.php'den kopyala
├── Http/
│   ├── Controllers/
│   │   └── OAuth/             # app/Http/Controllers/OAuth/* dosyaları
│   ├── Middleware/            # OAuth middleware'leri
│   └── Requests/
│       └── OAuth/             # Form request'ler
├── Models/
│   └── OAuth/                 # OAuth model'leri
├── Database/
│   ├── Migrations/
│   │   ├── 2026_02_14_000000_oauth_v1.php
│   │   └── 2026_02_15_000000_add_audience_to_oauth_tables.php
│   └── Seeds/
│       └── OAuthPermissions.php
├── Routes/
│   └── oauth.php              # routes/oauth.php
├── Providers/
│   ├── Main.php               # Service provider (YENİ)
│   └── Route.php              # Route provider (YENİ)
├── Resources/
│   ├── views/
│   │   └── oauth/             # resources/views/oauth/* dosyaları
│   └── lang/
│       └── en-GB/
│           └── oauth.php      # Çeviri dosyası
├── Console/
│   └── Commands/
│       └── OAuthCleanupCommand.php
└── README.md                  # Kurulum talimatları
```

### Adım 1.3: module.json Oluştur

`modules/OAuth/module.json` dosyasını oluşturun:

```json
{
    "alias": "oauth",
    "icon": "fa fa-lock",
    "version": "1.0.0",
    "active": 1,
    "category": "api",
    "providers": [
        "Modules\\OAuth\\Providers\\Main",
        "Modules\\OAuth\\Providers\\Route"
    ],
    "aliases": {},
    "files": [],
    "requires": [
        "laravel/passport": "^11.0"
    ],
    "settings": {
        "oauth_enabled": {
            "name": "oauth.enabled",
            "icon": "toggle-on",
            "type": "checkbox",
            "default": false,
            "description": "Enable OAuth 2.1 authentication"
        },
        "oauth_company_aware": {
            "name": "oauth.company_aware",
            "icon": "building",
            "type": "checkbox",
            "default": true,
            "description": "Enable company-aware OAuth tokens"
        },
        "oauth_require_pkce": {
            "name": "oauth.require_pkce",
            "icon": "shield-alt",
            "type": "checkbox",
            "default": true,
            "description": "Require PKCE for public clients"
        }
    },
    "reports": [],
    "widgets": []
}
```

### Adım 1.4: composer.json Oluştur

`modules/OAuth/composer.json`:

```json
{
    "name": "akaunting/oauth",
    "description": "OAuth 2.1 + MCP Server Integration for Akaunting",
    "type": "akaunting-module",
    "version": "1.0.0",
    "keywords": ["akaunting", "oauth", "oauth2", "mcp", "api"],
    "license": "proprietary",
    "authors": [
        {
            "name": "Your Name",
            "email": "your@email.com"
        }
    ],
    "require": {
        "php": "^8.1",
        "laravel/passport": "^11.0"
    },
    "autoload": {
        "psr-4": {
            "Modules\\OAuth\\": ""
        }
    }
}
```

### Adım 1.5: Service Provider Oluştur

`modules/OAuth/Providers/Main.php`:

```php
<?php

namespace Modules\OAuth\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Modules\OAuth\Models\OAuth\Client;
use Modules\OAuth\Models\OAuth\AuthCode;
use Modules\OAuth\Models\OAuth\PersonalAccessClient;
use Modules\OAuth\Models\OAuth\AccessToken;
use Modules\OAuth\Models\OAuth\RefreshToken;

class Main extends ServiceProvider
{
    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViews();
        $this->loadTranslations();
        $this->loadMigrations();
        $this->registerPassportModels();
        $this->configurePassport();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->loadConfig();
    }

    protected function loadViews()
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'oauth');
    }

    protected function loadTranslations()
    {
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'oauth');
    }

    protected function loadMigrations()
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        }
    }

    protected function loadConfig()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/oauth.php', 'oauth'
        );
    }

    protected function registerPassportModels()
    {
        Passport::useClientModel(Client::class);
        Passport::useAuthCodeModel(AuthCode::class);
        Passport::usePersonalAccessClientModel(PersonalAccessClient::class);
        Passport::useTokenModel(AccessToken::class);
        Passport::useRefreshTokenModel(RefreshToken::class);
    }

    protected function configurePassport()
    {
        // Token lifetimes
        Passport::tokensExpireIn(
            now()->addMinutes(config('oauth.expiration.access_token', 60))
        );

        Passport::refreshTokensExpireIn(
            now()->addMinutes(config('oauth.expiration.refresh_token', 20160))
        );

        Passport::personalAccessTokensExpireIn(
            now()->addMinutes(config('oauth.expiration.personal_access_token', 525600))
        );

        // PKCE requirement
        if (config('oauth.require_pkce', true)) {
            Passport::enablePKCE();
        }

        // Hash client secrets
        if (config('oauth.hash_client_secrets', true)) {
            Passport::hashClientSecrets();
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
```

### Adım 1.6: Route Provider Oluştur

`modules/OAuth/Providers/Route.php`:

```php
<?php

namespace Modules\OAuth\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

class Route extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     */
    protected $moduleNamespace = 'Modules\OAuth\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapOAuthRoutes();
    }

    /**
     * Define the "oauth" routes for the application.
     *
     * @return void
     */
    protected function mapOAuthRoutes()
    {
        if (!config('oauth.enabled', false)) {
            return;
        }

        Route::middleware(['oauth'])
            ->namespace($this->moduleNamespace)
            ->prefix(config('oauth.routes.prefix', 'oauth'))
            ->group(__DIR__ . '/../Routes/oauth.php');
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('oauth', function ($request) {
            return Limit::perMinute(config('oauth.rate_limit.per_minute', 60))
                ->by($request->ip());
        });
    }
}
```

### Adım 1.7: README.md Oluştur

`modules/OAuth/README.md`:

```markdown
# OAuth 2.1 + MCP Server Module for Akaunting

## Features
- ✅ OAuth 2.1 compliant
- ✅ MCP (Model Context Protocol) server support
- ✅ Company-aware multi-tenancy
- ✅ PKCE support
- ✅ Dynamic Client Registration (RFC 7591)
- ✅ ChatGPT integration ready
- ✅ RFC 8707 (Resource Indicators) compliant
- ✅ RFC 9728 (Protected Resource Metadata) compliant

## Installation

### 1. Install via Akaunting App Store
Coming soon...

### 2. Manual Installation
1. Upload the module to `modules/OAuth/`
2. Run migrations: `php artisan module:migrate OAuth`
3. Enable module in Akaunting settings
4. Configure OAuth settings in `.env`

## Configuration

Add to your `.env`:

```env
# Enable OAuth
OAUTH_ENABLED=true
API_AUTH_TYPE=passport

# Token Lifetimes (in minutes)
OAUTH_ACCESS_TOKEN_LIFETIME=60          # 1 hour
OAUTH_REFRESH_TOKEN_LIFETIME=20160      # 14 days
OAUTH_PERSONAL_ACCESS_TOKEN_LIFETIME=525600  # 1 year

# Security
OAUTH_REQUIRE_PKCE=true
OAUTH_HASH_CLIENT_SECRETS=true
OAUTH_COMPANY_AWARE=true
```

## First-Time Setup

### 1. Install Passport
```bash
php artisan passport:install
```

### 2. Create OAuth Client for ChatGPT
```bash
php artisan passport:client
```

**Enter these values:**
- User ID: `1` (your admin user ID)
- Client name: `ChatGPT Integration`
- Redirect URL: `https://chatgpt.com/connector_platform_oauth_redirect`

Save the **Client ID** and **Client Secret** - you'll need them for ChatGPT!

## ChatGPT Integration

See [CHATGPT_INTEGRATION.md](CHATGPT_INTEGRATION.md) for detailed instructions.

## Support
For issues and questions, visit: https://github.com/yourusername/akaunting-oauth
```

---

## 2️⃣ AKAUNTING CLOUD'A YÜKLEME

### Seçenek A: Akaunting App Store (Önerilen)

#### Adım 2A.1: Modülü Paketleme

```bash
cd modules/OAuth
zip -r oauth-module-v1.0.0.zip . -x "*.git*" "node_modules/*" "vendor/*"
```

#### Adım 2A.2: Akaunting Developer Portal'a Kayıt

1. https://akaunting.com/developers adresine gidin
2. Developer hesabı oluşturun
3. "Submit Extension" butonuna tıklayın

#### Adım 2A.3: Modül Bilgilerini Girin

- **Name:** OAuth 2.1 + MCP Server
- **Category:** API & Integrations
- **Price:** Free veya Paid
- **Description:** 
  ```
  OAuth 2.1 authentication and MCP server support for Akaunting. 
  Enable secure API access and integrate with ChatGPT and other AI tools.
  ```
- **Features:**
  - OAuth 2.1 compliant authentication
  - MCP (Model Context Protocol) server
  - ChatGPT ready integration
  - Company-aware multi-tenancy
  - PKCE security
- **Screenshots:** OAuth authorization page, client management, token management
- **Upload:** Zip dosyanızı yükleyin

#### Adım 2A.4: Review Sürecini Bekleyin

Akaunting team modülünüzü inceleyecek (1-2 hafta sürebilir).

---

### Seçenek B: Manuel Yükleme (Cloud Self-Hosted)

Eğer Akaunting Cloud'un self-hosted versiyonunu kullanıyorsanız:

#### Adım 2B.1: FTP/SFTP ile Yükleme

```bash
# FTP client ile bağlanın
ftp your-cloud-instance.akaunting.com

# modules klasörüne gidin
cd modules

# OAuth klasörünü yükleyin
put -r OAuth/
```

#### Adım 2B.2: SSH ile Kurulum

```bash
# SSH ile bağlanın
ssh user@your-cloud-instance.akaunting.com

# Akaunting dizinine gidin
cd /var/www/akaunting

# Composer bağımlılıklarını yükleyin
composer require laravel/passport

# Migration'ları çalıştırın
php artisan module:migrate OAuth

# Cache'i temizleyin
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

---

## 3️⃣ CHATGPT İLE BAĞLANMA

### Adım 3.1: Passport Client Oluşturma

Akaunting Cloud instance'ınızda:

```bash
# SSH veya terminal erişimi varsa
php artisan passport:client

# Veya Akaunting admin panel'inden "OAuth Clients" menüsünden
# "Create Client" butonuna tıklayın
```

**Client Bilgileri:**
- **Name:** `ChatGPT Integration`
- **Redirect URL:** `https://chatgpt.com/connector_platform_oauth_redirect`
- **Scopes:** `mcp:use` (veya ihtiyacınız olan diğer scope'lar)

**ÖNEMLİ:** Client ID ve Client Secret'ı kaydedin!

### Adım 3.2: .env Dosyasını Güncelleme

Cloud instance'ınızın `.env` dosyasında:

```env
# OAuth'u aktif et
OAUTH_ENABLED=true
API_AUTH_TYPE=passport

# Production URL'inizi girin
APP_URL=https://your-company.akaunting.com

# Token süreleri
OAUTH_ACCESS_TOKEN_LIFETIME=60
OAUTH_REFRESH_TOKEN_LIFETIME=20160

# Güvenlik
OAUTH_REQUIRE_PKCE=true
OAUTH_HASH_CLIENT_SECRETS=true
```

### Adım 3.3: ChatGPT'de GPT Action Oluşturma

1. **ChatGPT'ye gidin:** https://chatgpt.com
2. **GPT Editor'ü açın:** Sol menüden "Explore" > "Create a GPT"
3. **Configure sekmesine** geçin
4. **Actions** bölümüne inin
5. **Create new action** butonuna tıklayın

### Adım 3.4: OpenAPI Schema Ekleyin

Actions kısmına aşağıdaki OpenAPI schema'yı yapıştırın:

```yaml
openapi: 3.1.0
info:
  title: Akaunting API
  description: Access your Akaunting accounting data via OAuth 2.1
  version: 1.0.0
servers:
  - url: https://your-company.akaunting.com/api
    description: Your Akaunting instance

paths:
  /invoices:
    get:
      summary: Get all invoices
      operationId: getInvoices
      responses:
        '200':
          description: List of invoices
          content:
            application/json:
              schema:
                type: object
                properties:
                  data:
                    type: array
                    items:
                      type: object
  
  /customers:
    get:
      summary: Get all customers
      operationId: getCustomers
      responses:
        '200':
          description: List of customers
          content:
            application/json:
              schema:
                type: object
  
  /bills:
    get:
      summary: Get all bills
      operationId: getBills
      responses:
        '200':
          description: List of bills
          content:
            application/json:
              schema:
                type: object

components:
  securitySchemes:
    OAuth2:
      type: oauth2
      flows:
        authorizationCode:
          authorizationUrl: https://your-company.akaunting.com/oauth/authorize
          tokenUrl: https://your-company.akaunting.com/oauth/token
          scopes:
            mcp:use: Access MCP server
            read-invoices: Read invoices
            write-invoices: Create/update invoices
            read-customers: Read customers
            write-customers: Create/update customers

security:
  - OAuth2:
      - mcp:use
      - read-invoices
      - read-customers
```

**ÖNEMLİ:** `https://your-company.akaunting.com` kısmını kendi domain'inizle değiştirin!

### Adım 3.5: OAuth Ayarlarını Yapın

ChatGPT Actions sayfasında, schema'dan sonra **Authentication** bölümüne:

1. **Authentication Type:** `OAuth`
2. **Client ID:** (Adım 3.1'de aldığınız Client ID)
3. **Client Secret:** (Adım 3.1'de aldığınız Client Secret)
4. **Authorization URL:** `https://your-company.akaunting.com/oauth/authorize`
5. **Token URL:** `https://your-company.akaunting.com/oauth/token`
6. **Scope:** `mcp:use read-invoices read-customers` (ihtiyacınız olan scope'lar)
7. **Token Exchange Method:** `Default (POST request)`

### Adım 3.6: Test Edin

ChatGPT'de deneme yapın:

```
"Show me my latest invoices"
"List all customers"
"What's my total revenue this month?"
```

İlk kullanımda OAuth authorization sayfası açılacak:
1. Akaunting'e giriş yapın
2. "Authorize" butonuna tıklayın
3. ChatGPT'ye geri döneceksiniz

---

## 4️⃣ TEST & TROUBLESHOOTING

### Test Checklist

#### ✅ Modül Yükleme Testi

```bash
# Modülün aktif olduğunu kontrol edin
php artisan module:list

# OAuth routes'ların yüklendiğini kontrol edin
php artisan route:list | grep oauth
```

Şunları görmelisiniz:
- `oauth/authorize`
- `oauth/token`
- `oauth/clients`
- `oauth/.well-known/oauth-authorization-server`

#### ✅ OAuth Discovery Testi

Tarayıcıda açın:
```
https://your-company.akaunting.com/oauth/.well-known/oauth-authorization-server
```

JSON response görmelisiniz:
```json
{
  "issuer": "https://your-company.akaunting.com/oauth",
  "authorization_endpoint": "https://your-company.akaunting.com/oauth/authorize",
  "token_endpoint": "https://your-company.akaunting.com/oauth/token",
  ...
}
```

#### ✅ PKCE Flow Testi

```bash
# Test script'i çalıştırın
php oauth-test.php
```

Veya manuel test:

```bash
# 1. Code verifier oluştur
CODE_VERIFIER=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-43)

# 2. Code challenge oluştur
CODE_CHALLENGE=$(echo -n $CODE_VERIFIER | openssl dgst -binary -sha256 | base64 | tr -d "=+/" | cut -c1-43)

# 3. Authorization URL oluştur
echo "https://your-company.akaunting.com/oauth/authorize?client_id=YOUR_CLIENT_ID&redirect_uri=https://chatgpt.com/connector_platform_oauth_redirect&response_type=code&scope=mcp:use&code_challenge=$CODE_CHALLENGE&code_challenge_method=S256"
```

### Yaygın Sorunlar

#### ❌ "OAuth routes not found"

**Çözüm:**
```bash
# .env'de kontrol edin
OAUTH_ENABLED=true

# Cache temizleyin
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

#### ❌ "Client credentials invalid"

**Çözüm:**
1. Client ID ve Secret'ı kontrol edin
2. `oauth_clients` tablosunda client'ı kontrol edin:
   ```sql
   SELECT * FROM oauth_clients WHERE id = YOUR_CLIENT_ID;
   ```
3. Redirect URI'ın tam olarak eşleştiğinden emin olun

#### ❌ "PKCE required but not provided"

**Çözüm:**
ChatGPT OAuth ayarlarında:
- PKCE support: `Enabled`
- Code challenge method: `S256`

#### ❌ "Company access denied"

**Çözüm:**
Kullanıcının OAuth authorize ettiği şirket erişimi olmalı:
```sql
-- Kullanıcının şirket erişimini kontrol edin
SELECT * FROM user_companies WHERE user_id = YOUR_USER_ID;
```

#### ❌ "Token expired"

**Çözüm:**
Refresh token kullanın:
```bash
curl -X POST https://your-company.akaunting.com/oauth/token \
  -d "grant_type=refresh_token" \
  -d "refresh_token=YOUR_REFRESH_TOKEN" \
  -d "client_id=YOUR_CLIENT_ID" \
  -d "client_secret=YOUR_CLIENT_SECRET"
```

### Loglama

Debug için:

```bash
# Laravel log
tail -f storage/logs/laravel.log

# OAuth specific logging için
# config/oauth.php'de:
'logging' => [
    'enabled' => true,
    'channel' => 'oauth', // config/logging.php'de tanımlayın
],
```

---

## 🎯 ÖZET KONTROL LİSTESİ

### Modül Hazırlama
- [ ] `modules/OAuth/` klasör yapısı oluşturuldu
- [ ] `module.json` dosyası yapılandırıldı
- [ ] Tüm dosyalar doğru konumlara taşındı
- [ ] `composer.json` oluşturuldu
- [ ] Service provider'lar hazırlandı

### Cloud'a Yükleme
- [ ] Modül App Store'a yüklendi / Manuel yüklendi
- [ ] Migration'lar çalıştırıldı
- [ ] `.env` dosyası yapılandırıldı
- [ ] Passport install edildi
- [ ] OAuth routes test edildi

### ChatGPT Entegrasyonu
- [ ] Passport client oluşturuldu
- [ ] Client ID & Secret kaydedildi
- [ ] ChatGPT GPT action oluşturuldu
- [ ] OpenAPI schema eklendi
- [ ] OAuth ayarları yapıldı
- [ ] Test edildi ve çalışıyor

### Production Checklist
- [ ] HTTPS aktif (Let's Encrypt)
- [ ] Rate limiting yapılandırıldı
- [ ] CORS ayarları yapıldı
- [ ] Error handling test edildi
- [ ] Backup planı var
- [ ] Monitoring kuruldu

---

## 📚 EK KAYNAKLAR

- [Akaunting Module Development](https://akaunting.com/docs/developer-manual/modules)
- [Laravel Passport Documentation](https://laravel.com/docs/passport)
- [OAuth 2.1 Specification](https://oauth.net/2.1/)
- [MCP Protocol](https://modelcontextprotocol.io)
- [ChatGPT Actions Guide](https://platform.openai.com/docs/actions)

---

## 🆘 DESTEK

Sorun yaşarsanız:

1. **Documentation:** Bu dosya ve `OAUTH_TEST_CHECKLIST.md`
2. **Logs:** `storage/logs/laravel.log`
3. **Debug mode:** `.env` dosyasında `APP_DEBUG=true`
4. **Community:** Akaunting Forum veya GitHub Issues

---

**Başarılar! 🚀**

Herhangi bir sorunuz varsa yardımcı olmaktan mutluluk duyarım.
