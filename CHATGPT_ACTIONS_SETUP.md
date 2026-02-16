# ChatGPT Custom GPT Actions - OAuth Setup

## 🎯 ChatGPT GPT Actions için OAuth Konfigürasyonu

ChatGPT Custom GPT'de Actions kullanmak için aşağıdaki adımları izleyin.

## 📝 Adım 1: OAuth Client Oluştur

### Web UI'den (Önerilen):

1. Akaunting'e giriş yapın
2. **Settings menüsünden OAuth seçeneğine** gidin (veya doğrudan URL: `http://localhost/Ak-Dev/oauth4/1/settings/oauth`)
3. **Create Client** butonuna tıklayın
4. Formu doldurun:
   ```
   Name: ChatGPT Actions
   Redirect URLs (her satıra bir tane):
   https://chatgpt.com/aip/g-<your-gpt-id>/oauth/callback
   https://chat.openai.com/aip/g-<your-gpt-id>/oauth/callback
   
   ⚠️ NOT: <your-gpt-id> kısmını GPT oluşturduktan sonra alacaksınız
   ```
5. **Confidential**: ✓ İşaretleyin (ChatGPT için gerekli)
6. **Save** yapın
7. **Client ID** ve **Client Secret**'ı kopyalayın ve güvenli bir yere kaydedin

### API ile (Alternatif):

```powershell
$body = @{
    name = "ChatGPT Actions"
    redirect = "https://chatgpt.com/aip/oauth/callback"
    confidential = $true
} | ConvertTo-Json

$response = Invoke-RestMethod `
    -Uri "http://localhost/Ak-Dev/oauth4/api/oauth/clients" `
    -Method POST `
    -Headers @{
        "Authorization" = "Bearer YOUR_API_TOKEN"
        "Content-Type" = "application/json"
    } `
    -Body $body

Write-Host "Client ID: $($response.data.client.id)"
Write-Host "Client Secret: $($response.data.client.secret)"
```

## 🤖 Adım 2: ChatGPT Custom GPT Oluştur

1. https://chat.openai.com/gpts/editor adresine gidin
2. **Create** butonuna tıklayın
3. GPT'nizi configure edin:
   - **Name**: Akaunting Assistant
   - **Description**: Manage your accounting with Akaunting
   - **Instructions**: You are an accounting assistant that helps users manage their invoices, expenses, and financial data using Akaunting.

## 🔧 Adım 3: Actions Ekle

### 3.1 OpenAPI Schema

GPT Editor'da **Actions** sekmesine gidin ve **Create new action** tıklayın.

**OpenAPI Schema URL'i girin** (gelecekte eklenecek):
```
http://localhost/Ak-Dev/oauth4/api/openapi.json
```

Veya **Manuel Schema** (BasitÖrnek):
```yaml
openapi: 3.0.0
info:
  title: Akaunting API
  version: 1.0.0
  description: Akaunting accounting software API
servers:
  - url: http://localhost/Ak-Dev/oauth4/api
paths:
  /invoices:
    get:
      operationId: getInvoices
      summary: Get list of invoices
      security:
        - OAuth2: [read]
      responses:
        '200':
          description: List of invoices
          content:
            application/json:
              schema:
                type: object
  /expenses:
    get:
      operationId: getExpenses
      summary: Get list of expenses
      security:
        - OAuth2: [read]
      responses:
        '200':
          description: List of expenses
components:
  securitySchemes:
    OAuth2:
      type: oauth2
      flows:
        authorizationCode:
          authorizationUrl: http://localhost/Ak-Dev/oauth4/oauth/authorize
          tokenUrl: http://localhost/Ak-Dev/oauth4/oauth/token
          scopes:
            read: Read access to data
            write: Write access to data
            mcp:use: MCP protocol access
```

### 3.2 OAuth Authentication Ayarları

Actions sayfasında **Authentication** bölümüne gidin:

```
Authentication Type: OAuth

Client ID: [Adım 1'den aldığınız Client ID]
Client Secret: [Adım 1'den aldığınız Client Secret]

Authorization URL: http://localhost/Ak-Dev/oauth4/oauth/authorize
Token URL: http://localhost/Ak-Dev/oauth4/oauth/token

Scope: mcp:use read write

Token Exchange Method: Default (POST request)
```

### 3.3 Callback URL'i Güncelle

GPT oluşturduktan sonra ChatGPT size bir **callback URL** verecek:
```
https://chatgpt.com/aip/g-XXXXXXXXXXXX/oauth/callback
```

Bu URL'i OAuth client'ınızın redirect URL'lerine ekleyin:

1. Akaunting'de OAuth Clients sayfasına gidin
2. "ChatGPT Actions" client'ını düzenleyin
3. Redirect URLs alanına callback URL'i ekleyin
4. Save edin

## ⚠️ LOCALHOST SORUNU

ChatGPT **localhost**'a erişemez! Public URL gerekli:

### ngrok ile Public URL:

```powershell
# ngrok indirin: https://ngrok.com/download

# XAMPP için port 80
ngrok http 80

# Verdiği URL'i kopyalayın (örn: https://abc123.ngrok.io)
```

### URL'leri Güncelle:

**.env** dosyasında:
```env
APP_URL=https://abc123.ngrok.io/Ak-Dev/oauth4
```

**ChatGPT Actions'da URL'leri değiştirin**:
```
Authorization URL: https://abc123.ngrok.io/Ak-Dev/oauth4/oauth/authorize
Token URL: https://abc123.ngrok.io/Ak-Dev/oauth4/oauth/token
Server URL: https://abc123.ngrok.io/Ak-Dev/oauth4/api
```

**OAuth Client'ta redirect URL'i güncelleyin**:
```
https://chatgpt.com/aip/g-XXXXXXXXXXXX/oauth/callback
```

## 🧪 Adım 4: Test Et

1. ChatGPT'de GPT'nizi açın
2. Bir şey sorun: "Show me my recent invoices"
3. OAuth authorization için yönlendirileceksiniz
4. Akaunting'de şirketinizi seçin ve **Authorize** edin
5. ChatGPT'ye geri döneceksiniz
6. ChatGPT artık API'nizi kullanabilecek!

## 🐛 Yaygın Hatalar

### "OAuth configuration error"

**Sebep**: Client ID/Secret yanlış veya eksik

**Çözüm**:
- Client ID ve Secret'ı doğru kopyaladığınızdan emin olun
- OAuth client'ın "confidential" olarak işaretlendiğinden emin olun

### "redirect_uri_mismatch"

**Sebep**: Callback URL client'ta kayıtlı değil

**Çözüm**:
- ChatGPT'nin verdiği exact callback URL'i ekleyin
- URL'de https:// olmalı
- Trailing slash (/) olmadan

### "invalid_scope"

**Sebep**: Scope config'de tanımlı değil

**Çözüm**:
```powershell
# config/oauth.php kontroledin
'scopes' => [
    'mcp:use' => 'MCP Access',
    'read' => 'Read access',
    'write' => 'Write access',
],
```

### "localhost refused"

**Sebep**: ChatGPT localhost'a erişemiyor

**Çözüm**: ngrok kullanın (yukarıda anlatıldı)

## 📚 Örnekler

### ChatGPT'de Kullanım:

```
Kullanıcı: "Create an invoice for $500"
ChatGPT: [API'yi çağırır] ✓ Invoice #INV-001 created for $500

Kullanıcı: "Show my expenses this month"
ChatGPT: [API'yi çağırır] You have 12 expenses totaling $2,450 this month:
- Office Rent: $1,200
- Internet: $80
- ...

Kullanıcı: "What's my total revenue?"
ChatGPT: [API'yi çağırır] Your total revenue is $15,750
```

## 🔐 Güvenlik

- ✓ PKCE enabled (S256)
- ✓ Client Secret kullanıyor (confidential client)
- ✓ Scope-based access control
- ✓ Token expiration (60 dakika)
- ✓ Company-aware (her token bir şirkete bağlı)
- ✓ HTTPS zorunlu (production)

## 📖 Alternatif: Claude Desktop

Claude Desktop için MCP kullanmak istiyorsanız:
- [CLAUDE_MCP_SETUP.md](CLAUDE_MCP_SETUP.md) dosyasına bakın

## 🆘 Destek

Hata alıyorsanız log'ları kontrol edin:
```powershell
Get-Content storage\logs\laravel-*.log -Tail 50
```

OAuth test endpoint'i:
```powershell
curl http://localhost/Ak-Dev/oauth4/oauth/.well-known/oauth-authorization-server
```
