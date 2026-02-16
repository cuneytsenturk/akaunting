# 🔐 PASSPORT CLIENT SETUP GUIDE

## `passport:client` Komutunu Çalıştırma

### 📋 Client Türleri ve Redirect URL'leri

---

## 1️⃣ **Personal Access Client** (Önerilen - Başlangıç İçin)

**Ne zaman kullanılır?**
- Kendi uygulamanız için token oluşturma
- API testing
- First-party applications

**Komut:**
```bash
php artisan passport:client --personal
```

**Redirect URL:**
```
GEREKMIYOR - Otomatik olarak ayarlanır
```

**Örnek Çıktı:**
```
Personal access client created successfully.
Client ID: 1
Client secret: xxxxxxxxxxxxxxxxxxxxx
```

---

## 2️⃣ **Authorization Code Client** (Third-Party Apps İçin)

**Ne zaman kullanılır?**
- ChatGPT integration
- External applications
- Kullanıcı authorization gerektiren apps

**Komut:**
```bash
php artisan passport:client
```

**Sorulacak Sorular ve Cevaplar:**

### Soru 1: User ID
```
Which user ID should the client be assigned to?
```

**Cevap:**
```
1
```
*(Kendi user ID'nizi girin - genellikle 1)*

### Soru 2: Client Name
```
What should we name the client?
```

**Cevap Örnekleri:**
```
My Application
ChatGPT Integration
External API Client
```

### Soru 3: Redirect URL ⭐ ÖNEMLİ
```
Where should we redirect the request after authorization?
```

**Localhost Development:**
```
http://localhost/oauth/callback
```

**XAMPP ile Development:**
```
http://localhost:8000/oauth/callback
```
veya
```
http://localhost/akaunting/oauth/callback
```

**Production (Domain var ise):**
```
https://yourdomain.com/oauth/callback
```

**ChatGPT için:**
```
https://chatgpt.com/connector_platform_oauth_redirect
```

**Test/Debug için:**
```
http://localhost/oauth/callback
https://oauth.pstmn.io/v1/callback (Postman)
```

---

## 3️⃣ **Password Grant Client**

**Ne zaman kullanılır?**
- First-party mobile apps
- Trusted applications
- Username/password ile direct authentication

**Komut:**
```bash
php artisan passport:client --password
```

**Redirect URL:**
```
GEREKMIYOR
```

---

## 🎯 ÖNERILEN SETUP (Sırayla)

### Adım 1: Personal Access Client Oluştur
```bash
php artisan passport:client --personal
```

**Amaç:** API testing ve development için token oluşturma

### Adım 2: Authorization Code Client Oluştur
```bash
php artisan passport:client
```

**Redirect URL Önerileri:**

**Development:**
```
http://localhost/oauth/callback
```

**Production:**
```
https://yourdomain.com/oauth/callback
```

### Adım 3: Client Bilgilerini Kaydet
```
Client ID: [kaydet]
Client Secret: [kaydet - bir daha göremezsin!]
```

---

## 📝 XAMPP Localhost URL Yapısı

### Akaunting Klasör Yapısı
```
c:\xampp8125\htdocs\Ak-Dev\oauth4\
```

### Muhtemel URL'ler:

**1. Direct Root:**
```
http://localhost/oauth/callback
```

**2. Alt Klasör:**
```
http://localhost/Ak-Dev/oauth4/oauth/callback
```

**3. Virtual Host:**
```
http://oauth4.test/oauth/callback
```

**4. Port ile:**
```
http://localhost:8080/oauth/callback
```

---

## 🔍 Mevcut URL'inizi Bulma

### Tarayıcınızda Test Edin:
```
http://localhost/oauth/.well-known/oauth-authorization-server
```

Eğer bu URL çalışıyorsa, redirect URL'iniz:
```
http://localhost/oauth/callback
```

Eğer bu çalışıyorsa:
```
http://localhost/Ak-Dev/oauth4/oauth/.well-known/oauth-authorization-server
```

Redirect URL'iniz:
```
http://localhost/Ak-Dev/oauth4/oauth/callback
```

---

## ⚡ Hızlı Kurulum (Copy-Paste)

### 1. Personal Access Client
```bash
php artisan passport:client --personal
```

### 2. Test Client (Localhost)
```bash
php artisan passport:client
# User ID: 1
# Name: Test Client
# Redirect: http://localhost/oauth/callback
```

### 3. ChatGPT Client (Production)
```bash
php artisan passport:client
# User ID: 1
# Name: ChatGPT Integration
# Redirect: https://chatgpt.com/connector_platform_oauth_redirect
```

---

## 🎨 Callback Endpoint Oluşturma (Opsiyonel)

Eğer kendi callback endpoint'i yapmak isterseniz:

**Route (routes/web.php):**
```php
Route::get('/oauth/callback', function () {
    // Authorization code burada gelir
    $code = request()->get('code');
    $state = request()->get('state');
    
    return view('oauth.callback', compact('code', 'state'));
});
```

**View (resources/views/oauth/callback.blade.php):**
```html
<h1>Authorization Successful</h1>
<p>Code: {{ $code }}</p>
<p>State: {{ $state }}</p>
```

---

## 🚨 Önemli Notlar

### ✅ HTTPS Gereksinimleri

**Development (localhost):**
- HTTP kabul edilir
- `http://localhost/...` kullanılabilir

**Production:**
- HTTPS zorunlu
- `https://yourdomain.com/...` kullanılmalı

### ⚠️ Redirect URL Kuralları

**DOĞRU:**
```
http://localhost/oauth/callback
https://example.com/oauth/callback
https://chatgpt.com/connector_platform_oauth_redirect
```

**YANLIŞ:**
```
http://example.com/callback (Production'da HTTP)
http://192.168.1.1/callback (IP adresi - production'da)
example.com/callback (Scheme eksik)
```

### 🔄 Redirect URL Değiştirme

Client oluşturduktan sonra redirect URL değiştirmek için:

**Database:**
```sql
UPDATE oauth_clients 
SET redirect = 'https://new-url.com/callback' 
WHERE id = 1;
```

**veya DCR Endpoint ile yeni client oluştur**

---

## 🧪 Test Senaryosu

### 1. Personal Access Client ile Test
```bash
php artisan passport:client --personal
```

### 2. Token Oluştur (Tinker)
```bash
php artisan tinker
```

```php
$user = App\Models\Auth\User::find(1);
$token = $user->createToken('Test Token')->accessToken;
echo $token;
```

### 3. API Request Test
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     http://localhost/api/user
```

---

## 📞 Troubleshooting

### "Invalid redirect URI"
**Sebep:** Redirect URL client'a kaydedilen ile eşleşmiyor

**Çözüm:** 
```sql
SELECT id, name, redirect FROM oauth_clients;
```
Kayıtlı URL'i kontrol et

### "Client not found"
**Sebep:** Client ID yanlış

**Çözüm:**
```sql
SELECT * FROM oauth_clients;
```

### "Unauthenticated"
**Sebep:** Token geçersiz veya expired

**Çözüm:** Yeni token oluştur

---

## 🎯 SONUÇ

### Hızlı Başlangıç:

**1. Personal Access Client:**
```bash
php artisan passport:client --personal
```

**2. Authorization Code Client (Localhost):**
```bash
php artisan passport:client
# Redirect: http://localhost/oauth/callback
```

**3. Client bilgilerini .env'e ekle:**
```env
OAUTH_CLIENT_ID=your_client_id
OAUTH_CLIENT_SECRET=your_client_secret
```

**İşte bu kadar!** 🚀

---

## 📚 Ek Kaynaklar

- [Laravel Passport Docs](https://laravel.com/docs/passport)
- [OAuth 2.0 Specification](https://oauth.net/2/)
- [MCP Specification](https://modelcontextprotocol.io/)

**Proje Dökümanları:**
- `IMPLEMENTATION_SUMMARY.md`
- `OAUTH_TEST_CHECKLIST.md`
