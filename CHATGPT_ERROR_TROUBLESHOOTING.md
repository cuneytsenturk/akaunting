# ChatGPT Hata Giderme Rehberi

## ❌ "MCP OAuth Error" Hatası

Bu hatayı alıyorsanız, muhtemelen aşağıdaki senaryolardan biri geçerlidir:

### Senaryo 1: Custom GPT Actions Kullanıyorsunuz

**Doğru Adımlar:**

1. **ChatGPT'de**: https://chat.openai.com/gpts/editor
2. **Create a GPT** > **Configure** > **Actions**
3. **Create new action** butonuna tıklayın
4. **Authentication** sekmesine gidin:
   - Type: **OAuth**
   - Client ID: `[Client ID nizi]`
   - Client Secret: `[Client Secret inizi]`
   - Authorization URL: `https://YOUR-NGROK-URL/oauth/authorize`
   - Token URL: `https://YOUR-NGROK-URL/oauth/token`
   - Scope: `mcp:use read write`

**NOT:** Manifest URL değil, OAuth config'i manuel girin!

### Senaryo 2: Eski Plugin Sistemi (Artık Çalışmıyor)

OpenAI eski plugin sistemini kapattı. Artık **Custom GPT Actions** kullanmalısınız.

❌ **Çalışmayan Yöntem:**
```
Plugin URL: http://localhost/.well-known/ai-plugin.json
```

✅ **Doğru Yöntem:**
- Custom GPT oluşturun
- Actions ekleyin
- OAuth'u manuel configure edin

## 🔧 Adım Adım Düzeltme

### 1. OAuth Client Oluştur (Confidential)

```powershell
# Akaunting admin panelden:
# Settings > OAuth > Create Client

Name: ChatGPT Actions
Redirect URLs:
  https://chat.openai.com/aip/g-XXXX/oauth/callback
  https://chatgpt.com/aip/g-XXXX/oauth/callback
  
Confidential: ✓ İşaretle
```

Client ID ve Secret'ı kaydedin!

### 2. ngrok Başlat (ZORUNLU!)

```powershell
ngrok http 80
```

Verdiği URL'i kopyalayın: `https://abc123.ngrok.io`

### 3. .env Güncelle

```env
APP_URL=https://abc123.ngrok.io/Ak-Dev/oauth4
```

### 4. ChatGPT Custom GPT Oluştur

1. https://chat.openai.com/gpts/editor
2. **Create**
3. **Name**: Akaunting Assistant
4. **Actions** > **Create new action**

### 5. OpenAPI Schema Ekle

**Minimal Schema (Test için):**

```yaml
openapi: 3.0.0
info:
  title: Akaunting API
  version: 1.0.0
servers:
  - url: https://abc123.ngrok.io/Ak-Dev/oauth4/api
paths:
  /companies:
    get:
      operationId: getCompanies
      summary: Get companies
      responses:
        '200':
          description: OK
components:
  securitySchemes:
    OAuth2:
      type: oauth2
      flows:
        authorizationCode:
          authorizationUrl: https://abc123.ngrok.io/Ak-Dev/oauth4/oauth/authorize
          tokenUrl: https://abc123.ngrok.io/Ak-Dev/oauth4/oauth/token
          scopes:
            mcp:use: MCP access
            read: Read access
            write: Write access
```

**ÖNEMLİ:** `abc123.ngrok.io` yerine kendi ngrok URL'nizi yazın!

### 6. Authentication Ayarla

Actions sayfasında **Authentication** bölümü:

```
Type: OAuth

Client ID: [Adım 1'deki Client ID]
Client Secret: [Adım 1'deki Client Secret]

Authorization URL: https://abc123.ngrok.io/Ak-Dev/oauth4/oauth/authorize
Token URL: https://abc123.ngrok.io/Ak-Dev/oauth4/oauth/token

Scope: mcp:use read write

Token Exchange Method: Default (POST)
```

### 7. Callback URL'i Ekle

GPT kaydettikten sonra ChatGPT size callback URL verecek.

Akaunting'de OAuth client'ı düzenleyin ve bu URL'i ekleyin:
```
https://chat.openai.com/aip/g-abc123xyz/oauth/callback
```

### 8. Test Et!

ChatGPT'de GPT'nizi açın ve "Get my companies" deyin.

Authorization için Akaunting'e yönlendirileceksiniz.

## 🐛 Yaygın Hatalar ve Çözümleri

### "invalid_client"

**Sebep:** Client ID veya Secret yanlış

**Çözüm:**
- Client ID/Secret'ı doğrudan Akaunting'den kopyalayın
- Boşluk veya ekstra karakter olmadığından emin olun

### "unauthorized_client"

**Sebep:** OAuth client "confidential" değil

**Çözüm:**
- OAuth client'ı düzenleyin
- "Confidential Client" kutusunu işaretleyin
- Save edin

### "redirect_uri_mismatch"

**Sebep:** Callback URL kayıtlı değil

**Çözüm:**
- Exact callback URL'i ekleyin
- https:// ile başlamalı
- Trailing slash (/) olmamalı

### "invalid_scope"

**Sebep:** Scope tanımlı değil

**Çözüm:**
```powershell
# Scope'ları kontrol edin
curl https://abc123.ngrok.io/Ak-Dev/oauth4/oauth/.well-known/oauth-authorization-server

# "scopes_supported" alanında olmalı:
# ["mcp:use", "read", "write", "admin"]
```

### "This site can't be reached" veya "localhost refused"

**Sebep:** ChatGPT localhost'a erişemiyor

**Çözüm:**
- ngrok kullanın (yukarıda anlatıldı)
- Tüm URL'leri ngrok URL'i ile değiştirin

### "SSL handshake failed"

**Sebep:** ngrok HTTPS kullanıyor ama Akaunting HTTP

**Çözüm:** 
- Sorun yok! ngrok otomatik HTTPS sağlıyor
- Sadece tüm URL'lerin `https://` ile başladığından emin olun

## ✅ Test Checklist

Başlamadan önce kontrol edin:

- [ ] ngrok çalışıyor
- [ ] APP_URL ngrok URL'i ile güncellendi
- [ ] OAuth client oluşturuldu (confidential)
- [ ] Client ID ve Secret kopyalandı
- [ ] Custom GPT oluşturuldu
- [ ] Actions eklendi
- [ ] OAuth config yapıldı
- [ ] OpenAPI schema eklendi
- [ ] Callback URL client'a eklendi
- [ ] Scope'lar doğru: `mcp:use read write`

## 📞 Hala Çalışmıyor mu?

Log dosyalarını kontrol edin:

```powershell
# Laravel logs
Get-Content storage\logs\laravel-*.log -Tail 100

# OAuth discovery test
curl https://YOUR-NGROK-URL/oauth/.well-known/oauth-authorization-server

# Manifest test
curl https://YOUR-NGROK-URL/.well-known/ai-plugin.json
```

Hataları buraya yapıştırın ve analiz edelim!
