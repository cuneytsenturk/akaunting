# ChatGPT MCP Bağlantı Kurulumu

## ✅ Ön Kontrol

Tüm gerekli endpoint'ler hazır:

- ✓ OAuth Discovery: `http://localhost/Ak-Dev/oauth4/oauth/.well-known/oauth-authorization-server`
- ✓ ChatGPT Manifest: `http://localhost/Ak-Dev/oauth4/.well-known/ai-plugin.json`
- ✓ MCP Manifest: `http://localhost/Ak-Dev/oauth4/.well-known/mcp.json`
- ✓ Dynamic Client Registration: `http://localhost/Ak-Dev/oauth4/oauth/register`
- ✓ PKCE S256: Enabled
- ✓ Scope'lar: `mcp:use`, `read`, `write`, `admin`

## 🚀 Adım 1: OAuth Client Oluştur 

### Manuel Yöntem (Önerilen)

1. Akaunting admin paneline giriş yapın
2. **Settings > OAuth Clients** sayfasına gidin  
3. **New OAuth Client** butonuna tıklayın
4. Bilgileri doldurun:
   - **Name**: `ChatGPT MCP`
   - **Redirect URLs** (her satıra bir tane):
     ```
     https://chatgpt.com/connector_platform_oauth_redirect
     https://platform.openai.com/apps-manage/oauth
     ```
   - **Confidential Client**: İşaretsiz bırakın (public client)

5. **Save** butonuna tıklayın
6. **Client ID**'yi kopyalayın (Client Secret oluşturulmayacak çünkü public client)

### Alternatif: Dynamic Client Registration via API

```powershell
$body = @{
    client_name = "ChatGPT MCP"
    redirect_uris = @(
        "https://chatgpt.com/connector_platform_oauth_redirect",
        "https://platform.openai.com/apps-manage/oauth"
    )
    token_endpoint_auth_method = "none"
    grant_types = @("authorization_code", "refresh_token")
    response_types = @("code")
} | ConvertTo-Json

$response = Invoke-RestMethod `
    -Uri "http://localhost/Ak-Dev/oauth4/oauth/register" `
    -Method POST `
    -Body $body `
    -ContentType "application/json"

Write-Host "Client ID: $($response.client_id)"
```

## 🔧 Adım 2: ChatGPT'de Bağlantıyı Kur

### A. ChatGPT Settings

1. ChatGPT'ye gidin: https://chatgpt.com/
2. Sol alt köşedeki profil resminize tıklayın
3. **Settings** > **Connections** veya **Actions** bölümüne gidin
4. **Add Connection** veya **Create new action** seçeneğini bulun

### B. Manifest URL Girin

Manifest URL olarak şunu girin:
```
http://localhost/Ak-Dev/oauth4/.well-known/ai-plugin.json
```

**ÖNE

MLİ NOT:** Localhost production'da çalışmaz! Geliştirme için:
- ngrok kullanın: `ngrok http 80`
- Veya public bir domain/IP kullanın

### C. OAuth Bilgileri

ChatGPT otomatik olarak manifest'ten çekecek, ama manuel gerekirse:

- **Authorization URL**: `http://localhost/Ak-Dev/oauth4/oauth/authorize`
- **Token URL**: `http://localhost/Ak-Dev/oauth4/oauth/token`
- **Client ID**: (Adım 1'de oluşturduğunuz)
- **Client Secret**: Boş (public client)
- **Scope**: `mcp:use`

## 🌐 Adım 3: ngrok ile Public URL (Zorunlu!)

ChatGPT localhost'a erişemez. ngrok kullanın:

```powershell
# ngrok'u indirin: https://ngrok.com/download

# XAMPP için (Port 80)
ngrok http 80

# Laravel serve için (Port 8000)
# ngrok http 8000
```

ngrok başladıktan sonra size bir URL verecek:
```
https://abc123.ngrok.io -> http://localhost:80
```

**ÖNEMLİ:** .env dosyanızda APP_URL'i güncelleyin:
```env
APP_URL=https://abc123.ngrok.io/Ak-Dev/oauth4
```

Artık manifest URL'iniz:
```
https://abc123.ngrok.io/Ak-Dev/oauth4/.well-known/ai-plugin.json
```

## 🧪 Adım 4: Test Et

1. ChatGPT'de bağlantıyı authorize edin
2. Akaunting authorization sayfası açılacak
3. Şirketinizi seçin ve **Authorize** butonuna tıklayın
4. ChatGPT'ye geri yönlendirileceksiniz

Artık ChatGPT ile konuşarak:
```
"Show me my recent invoices"
"Create a new expense for $150"
"What's my total revenue this month?"
```

## 🐛 Hata Giderme

### "The requested scope is invalid"

**Çözüm:** Scope'lar düzgün kaydedilmemiş.
```powershell
php artisan config:clear
php artisan cache:clear
```

### "localhost refused to connect"

**Çözüm:** ngrok kullanın (yukarıda anlatıldı).

### "redirect_uri mismatch"

**Çözüm:** OAuth client'ta redirect URL'lerin doğru olduğundan emin olun:
- https://chatgpt.com/connector_platform_oauth_redirect
- https://platform.openai.com/apps-manage/oauth

### "Invalid client_id"

**Çözüm:** Client ID'yi doğru kopyaladığınızdan emin olun.

## 📋 Production Checklist

Production'a almadan önce:

- [ ] `APP_URL` gerçek domain'inize ayarlı
- [ ] HTTPS kullanıyorsunuz
- [ ] `OAUTH_REQUIRE_PKCE=true` enabled
- [ ] `OAUTH_HASH_CLIENT_SECRETS=true` enabled  
- [ ] OAuth client redirect URL'leri güvenli
- [ ] Rate limiting aktif
- [ ] Error logging aktif
- [ ] Scope'lar doğru tanımlı

## 🔐 Güvenlik Notları

1. **PKCE (S256)** zorunlu - MCP standardı
2. **Public Client** - Secret yok, PKCE ile güvenli
3. **Scope kontrolü** - Her token sadece gerekli scope'lara sahip
4. **Company aware** - Tokenlar tek bir şirkete bağlı
5. **HTTPS zorunlu** - Production'da sadece HTTPS redirect_uri kabul edilir

## 📚 Kaynaklar

- [MCP Specification](https://modelcontextprotocol.io/specification/2025-06-18)
- [OAuth 2.1](https://datatracker.ietf.org/doc/html/draft-ietf-oauth-v2-1-07)
- [PKCE RFC 7636](https://datatracker.ietf.org/doc/html/rfc7636)
- [DCR RFC 7591](https://datatracker.ietf.org/doc/html/rfc7591)

## 🆘 Destek

Sorun yaşıyorsanız log'lara bakın:
```powershell
Get-Content storage\logs\laravel-*.log -Tail 50
```
