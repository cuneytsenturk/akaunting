# Claude Desktop MCP Bağlantı Kurulumu

## 📋 Genel Bakış

Claude Desktop, MCP (Model Context Protocol) üzerinden Akaunting'e bağlanabilir. 
OAuth 2.1 + PKCE kullanarak güvenli bağlantı sağlar.

## ✅ Ön Gereksinimler

- Claude Desktop uygulaması yüklü (https://claude.ai/download)
- Akaunting OAuth özelliği aktif (`OAUTH_ENABLED=true`)
- ngrok veya public URL (localhost çalışmaz)

## 🚀 Adım 1: OAuth Client Oluştur

### Manuel Yöntem

1. Akaunting admin paneline giriş yapın
2. **Settings > OAuth Clients** sayfasına gidin
3. **New OAuth Client** butonuna tıklayın
4. Bilgileri doldurun:
   - **Name**: `Claude Desktop MCP`
   - **Redirect URLs**:
     ```
     http://127.0.0.1:6337/oauth/callback
     http://localhost:6337/oauth/callback
     claude://oauth/callback
     ```
   - **Confidential Client**: İşaretsiz bırakın (public client)

5. **Save** ve **Client ID**'yi kopyalayın

### PowerShell ile Otomatik Oluşturma

```powershell
$body = @{
    client_name = "Claude Desktop MCP"
    redirect_uris = @(
        "http://127.0.0.1:6337/oauth/callback",
        "http://localhost:6337/oauth/callback",
        "claude://oauth/callback"
    )
    token_endpoint_auth_method = "none"
    grant_types = @("authorization_code", "refresh_token")
    response_types = @("code")
    scope = "mcp:use read"
} | ConvertTo-Json

$response = Invoke-RestMethod `
    -Uri "http://localhost/Ak-Dev/oauth4/oauth/register" `
    -Method POST `
    -Body $body `
    -ContentType "application/json"

Write-Host "`n=== OAuth Client Created ===" -ForegroundColor Green
Write-Host "Client ID: $($response.client_id)"
Write-Host "Client ID Issued At: $($response.client_id_issued_at)"
Write-Host "`nSave this Client ID for Claude configuration!"
```

## 🌐 Adım 2: ngrok ile Public URL

Claude Desktop localhost'a bağlanamaz. ngrok kullanın:

```powershell
# ngrok yükleyin: https://ngrok.com/download
# Ücretsiz hesap oluşturun ve auth token alın

# XAMPP için
ngrok http 80 --domain=your-subdomain.ngrok.io

# Çıktı:
# Forwarding https://your-subdomain.ngrok.io -> http://localhost:80
```

**.env dosyasını güncelleyin:**
```env
APP_URL=https://your-subdomain.ngrok.io/Ak-Dev/oauth4
OAUTH_ENABLED=true
API_AUTH_TYPE=passport
OAUTH_REQUIRE_PKCE=true
```

```powershell
php artisan config:clear
php artisan cache:clear
```

## ⚙️ Adım 3: Claude Desktop Konfigürasyonu

### Windows

Claude Desktop config dosyası: `%APPDATA%\Claude\claude_desktop_config.json`

```powershell
# Config dizinine gidin
cd $env:APPDATA\Claude

# Eğer dosya yoksa oluşturun
if (-not (Test-Path "claude_desktop_config.json")) {
    New-Item -ItemType File -Name "claude_desktop_config.json"
}

# Dosyayı açın
notepad claude_desktop_config.json
```

### macOS/Linux

Config dosyası: `~/.config/claude/claude_desktop_config.json`

```bash
mkdir -p ~/.config/claude
nano ~/.config/claude/claude_desktop_config.json
```

### Config İçeriği

```json
{
  "mcpServers": {
    "akaunting": {
      "type": "oauth",
      "name": "Akaunting Accounting",
      "description": "Access your Akaunting accounting data",
      "oauth": {
        "authorizationEndpoint": "https://your-subdomain.ngrok.io/Ak-Dev/oauth4/oauth/authorize",
        "tokenEndpoint": "https://your-subdomain.ngrok.io/Ak-Dev/oauth4/oauth/token",
        "clientId": "YOUR_CLIENT_ID_HERE",
        "scope": "mcp:use read",
        "pkce": {
          "required": true,
          "method": "S256"
        },
        "redirectUri": "http://127.0.0.1:6337/oauth/callback"
      },
      "api": {
        "baseUrl": "https://your-subdomain.ngrok.io/Ak-Dev/oauth4/api",
        "endpoints": {
          "invoices": "/sales/invoices",
          "expenses": "/purchases/expenses",
          "customers": "/sales/customers",
          "reports": "/reports"
        }
      },
      "capabilities": ["resources", "tools", "prompts"]
    }
  }
}
```

**ÖNEMLİ:** 
- `YOUR_CLIENT_ID_HERE` yerine Adım 1'deki Client ID'yi yazın
- `your-subdomain.ngrok.io` yerine kendi ngrok URL'inizi yazın

## 🔄 Adım 4: Claude Desktop'u Yeniden Başlatın

1. Claude Desktop'u tamamen kapatın (System Tray'den de kapatın)
2. Tekrar açın
3. Settings > Integrations > MCP Servers bölümüne gidin
4. "Akaunting Accounting" görünmeli

## ✅ Adım 5: Authorize Edin

1. Claude'da MCP server'ı aktifleştirin
2. Tarayıcınızda Akaunting authorization sayfası açılacak
3. Login yapın (gerekirse)
4. Şirketinizi seçin
5. **Authorize** butonuna tıklayın
6. Claude'a geri yönlendirileceksiniz

## 🧪 Test Et

Claude'da şunları deneyin:

```
"Show me my recent invoices from Akaunting"
"What are my total expenses this month?"
"Create a new customer in Akaunting"
"Generate a sales report"
```

Claude artık Akaunting API'sine erişebilir!

## 🐛 Hata Giderme

### "MCP server not found"

**Çözüm:** Config dosyasının JSON formatı doğru mu kontrol edin.
```powershell
# JSON validate et
Get-Content "$env:APPDATA\Claude\claude_desktop_config.json" | ConvertFrom-Json
```

### "OAuth authorization failed"

**Çözüm:** 
1. Client ID doğru mu?
2. ngrok çalışıyor mu?
3. Redirect URI'ler eşleşiyor mu?

```powershell
# ngrok status
curl https://your-subdomain.ngrok.io/Ak-Dev/oauth4/oauth/.well-known/oauth-authorization-server
```

### "Invalid scope"

**Çözüm:**
```powershell
php artisan config:clear
php artisan cache:clear
```

### "Connection timeout"

**Çözüm:** 
- Firewall/antivirus Claude'u engelliyor olabilir
- ngrok tunnel çalışıyor mu kontrol edin
- XAMPP/Apache çalışıyor mu kontrol edin

## 📊 Claude Desktop Logs

### Windows

```powershell
# Claude logs
Get-Content "$env:APPDATA\Claude\logs\main.log" -Tail 50

# Akaunting logs  
Get-Content "storage\logs\laravel-*.log" -Tail 50
```

### macOS/Linux

```bash
# Claude logs
tail -f ~/.config/claude/logs/main.log

# Akaunting logs
tail -f storage/logs/laravel-*.log
```

## 🔐 Güvenlik En İyi Uygulamaları

1. **PKCE zorunlu** - Claude Desktop S256 kullanır
2. **Public client** - Secret yok, PKCE ile korunur
3. **Scope sınırlama** - Sadece gerekli scope'ları verin
4. **Token süresi** - Kısa access token, uzun refresh token
5. **HTTPS** - Production'da zorunlu
6. **Rate limiting** - API aşırı kullanımı engelleyin

## 🔄 Token Yenileme

Claude Desktop otomatik olarak refresh token kullanır. Manuel yenilemek isterseniz:

1. Claude Settings > Integrations
2. Akaunting'i bulun
3. "Reconnect" veya "Reauthorize" tıklayın

## 🌍 Production Deployment

Production için:

```env
# .env
APP_URL=https://yourdomain.com
OAUTH_ENABLED=true
API_AUTH_TYPE=passport
OAUTH_REQUIRE_PKCE=true
OAUTH_HASH_CLIENT_SECRETS=true
OAUTH_REQUIRE_AUDIENCE=true

# Rate limits
OAUTH_DCR_MAX_PER_IP=10
```

Claude Desktop config:
```json
{
  "mcpServers": {
    "akaunting": {
      "oauth": {
        "authorizationEndpoint": "https://yourdomain.com/oauth/authorize",
        "tokenEndpoint": "https://yourdomain.com/oauth/token",
        "clientId": "production-client-id",
        "scope": "mcp:use read",
        "pkce": {
          "required": true,
          "method": "S256"
        }
      }
    }
  }
}
```

## 📚 İleri Seviye

### Custom Tools/Prompts

Claude Desktop'ta custom tools tanımlayabilirsiniz:

```json
{
  "mcpServers": {
    "akaunting": {
      "tools": {
        "create_invoice": {
          "description": "Create a new invoice",
          "parameters": {
            "customer_id": "number",
            "items": "array",
            "due_date": "string"
          },
          "endpoint": "/sales/invoices",
          "method": "POST"
        }
      }
    }
  }
}
```

### Webhook Integration

Real-time updates için webhook ekleyin:

```json
{
  "webhooks": {
    "invoice_created": "https://your-webhook-endpoint.com/invoice-created"
  }
}
```

## 🆘 Destek

Sorun yaşıyorsanız:

1. Config dosyasını kontrol edin
2. Logs'a bakın (hem Claude hem Akaunting)
3. ngrok/network bağlantısını test edin
4. OAuth client ayarlarını doğrulayın

## 📖 Kaynaklar

- [Claude Desktop MCP Docs](https://docs.anthropic.com/claude/docs/mcp)
- [MCP Specification](https://modelcontextprotocol.io)
- [OAuth 2.1 with PKCE](https://oauth.net/2.1/)
- [Akaunting API Docs](https://akaunting.com/docs/api)

---

**Not:** Claude Desktop sürekli güncelleniyor. Config formatı değişebilir.
En güncel dokümantasyon için Claude'un resmi dokümanlarını kontrol edin.
