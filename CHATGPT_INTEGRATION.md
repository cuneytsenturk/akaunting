# 🤖 ChatGPT Integration Guide - Akaunting OAuth

## 📋 ÖN KOŞULLAR

- ✅ Akaunting OAuth modülü yüklü ve aktif
- ✅ Akaunting Cloud/Production instance çalışıyor
- ✅ HTTPS aktif (ChatGPT HTTP kabul etmez!)
- ✅ ChatGPT Plus veya Team hesabı (Free hesap GPT Actions desteklemez)

---

## 1️⃣ AKAUNTING TARAFINDA HAZIRLIK

### Adım 1.1: OAuth Client Oluşturma

#### Option A: Akaunting Web Interface (Önerilen)

1. Akaunting'e admin olarak giriş yapın
2. **Settings** > **OAuth Clients** menüsüne gidin
3. **New Client** butonuna tıklayın
4. Formu doldurun:
   - **Name:** `ChatGPT Integration`
   - **Redirect URI:** `https://chatgpt.com/connector_platform_oauth_redirect`
   - **Confidential:** ✅ Checked (Client Secret kullanacağız)
   - **PKCE Required:** ✅ Checked (Güvenlik için)
   - **Scopes:** 
     - `mcp:use` ✅
     - `read-invoices` ✅
     - `read-customers` ✅
     - `read-bills` ✅
     - İhtiyacınız olan diğer scope'ları seçin
5. **Save** butonuna tıklayın
6. **Client ID** ve **Client Secret** ekranda görünecek - **MUTLAKA KAYIT EDİN!**
   - Client Secret bir daha gösterilmeyecek!

#### Option B: Artisan Command

SSH erişiminiz varsa:

```bash
php artisan passport:client
```

**Sorulara cevaplar:**
```
Which user ID should the client be assigned to?
> 1

What should we name the client?
> ChatGPT Integration

Where should we redirect the request after authorization?
> https://chatgpt.com/connector_platform_oauth_redirect
```

**Çıktıda göreceksiniz:**
```
Client ID: 3
Client secret: aBcDeFgHiJkLmNoPqRsTuVwXyZ123456789...
```

**💾 Bu bilgileri kaydedin:**
```
Client ID: ______________________
Client Secret: ______________________
```

### Adım 1.2: Test OAuth Endpoints

Tarayıcıda açın ve JSON response aldığınızdan emin olun:

```
https://your-domain.akaunting.com/oauth/.well-known/oauth-authorization-server
```

Beklenen response:
```json
{
  "issuer": "https://your-domain.akaunting.com/oauth",
  "authorization_endpoint": "https://your-domain.akaunting.com/oauth/authorize",
  "token_endpoint": "https://your-domain.akaunting.com/oauth/token",
  "response_types_supported": ["code"],
  "grant_types_supported": ["authorization_code", "refresh_token"],
  "code_challenge_methods_supported": ["S256"],
  ...
}
```

❌ **Eğer 404 hatası alırsanız:**
```bash
# .env kontrol
OAUTH_ENABLED=true

# Cache temizle
php artisan config:clear
php artisan route:clear
```

---

## 2️⃣ CHATGPT TARAFINDA KURULUM

### Adım 2.1: GPT Oluşturma

1. https://chatgpt.com adresine gidin
2. Sol menüden **Explore** > **Create a GPT** tıklayın
3. **Create** sekmesinde GPT'nize bir isim verin:
   ```
   Name: Akaunting Assistant
   Description: Your personal accounting assistant. Access your invoices, customers, and financial data.
   ```
4. **Configure** sekmesine geçin

### Adım 2.2: GPT Instructions (İsteğe bağlı)

**Instructions** alanına:

```
You are an Akaunting accounting assistant. You have access to the user's accounting data through the Akaunting API.

When the user asks about:
- Invoices: Use the getInvoices action
- Customers: Use the getCustomers action  
- Bills: Use the getBills action
- Payments: Use the getPayments action

Always provide clear, formatted responses. When showing financial data, format currencies properly and use tables when appropriate.

If there's an error accessing the data, explain it clearly and suggest what the user should check.
```

### Adım 2.3: Actions Ekleme

1. Aşağı scroll edin, **Actions** bölümünü bulun
2. **Create new action** butonuna tıklayın
3. **Schema** kutusuna aşağıdaki OpenAPI specification'ı yapıştırın

#### 📋 OpenAPI Schema (Tam Versiyon)

```yaml
openapi: 3.1.0
info:
  title: Akaunting API
  description: |
    Access your Akaunting accounting data with OAuth 2.1 authentication.
    
    This API provides access to:
    - Invoices and sales
    - Bills and expenses  
    - Customers and vendors
    - Payments and transactions
    - Accounts and categories
    
    All requests are company-aware and filtered by your current company context.
  version: 3.0.0
  contact:
    name: Akaunting Support
    url: https://akaunting.com/support

servers:
  - url: https://your-domain.akaunting.com/api
    description: Your Akaunting Cloud Instance

paths:
  # INVOICES
  /invoices:
    get:
      summary: List all invoices
      description: Get a list of all invoices for the current company
      operationId: getInvoices
      parameters:
        - name: limit
          in: query
          description: Number of results to return
          schema:
            type: integer
            default: 25
        - name: page
          in: query
          description: Page number
          schema:
            type: integer
            default: 1
        - name: search
          in: query
          description: Search by invoice number or customer name
          schema:
            type: string
      responses:
        '200':
          description: Successful response
          content:
            application/json:
              schema:
                type: object
                properties:
                  data:
                    type: array
                    items:
                      $ref: '#/components/schemas/Invoice'
        '401':
          description: Unauthorized
        '403':
          description: Forbidden - No company access
    
    post:
      summary: Create a new invoice
      description: Create a new invoice for the current company
      operationId: createInvoice
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/InvoiceInput'
      responses:
        '201':
          description: Invoice created
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/Invoice'
        '422':
          description: Validation error

  /invoices/{id}:
    get:
      summary: Get a specific invoice
      operationId: getInvoice
      parameters:
        - name: id
          in: path
          required: true
          schema:
            type: integer
      responses:
        '200':
          description: Successful response
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/Invoice'
        '404':
          description: Invoice not found

  # CUSTOMERS
  /customers:
    get:
      summary: List all customers
      description: Get a list of all customers for the current company
      operationId: getCustomers
      parameters:
        - name: limit
          in: query
          schema:
            type: integer
            default: 25
        - name: search
          in: query
          schema:
            type: string
      responses:
        '200':
          description: Successful response
          content:
            application/json:
              schema:
                type: object
                properties:
                  data:
                    type: array
                    items:
                      $ref: '#/components/schemas/Customer'

    post:
      summary: Create a new customer
      operationId: createCustomer
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/CustomerInput'
      responses:
        '201':
          description: Customer created
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/Customer'

  /customers/{id}:
    get:
      summary: Get a specific customer
      operationId: getCustomer
      parameters:
        - name: id
          in: path
          required: true
          schema:
            type: integer
      responses:
        '200':
          description: Successful response
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/Customer'

  # BILLS
  /bills:
    get:
      summary: List all bills
      description: Get a list of all bills (expenses) for the current company
      operationId: getBills
      parameters:
        - name: limit
          in: query
          schema:
            type: integer
            default: 25
        - name: page
          in: query
          schema:
            type: integer
            default: 1
      responses:
        '200':
          description: Successful response
          content:
            application/json:
              schema:
                type: object
                properties:
                  data:
                    type: array
                    items:
                      $ref: '#/components/schemas/Bill'

  # PAYMENTS
  /payments:
    get:
      summary: List all payments
      operationId: getPayments
      parameters:
        - name: limit
          in: query
          schema:
            type: integer
      responses:
        '200':
          description: Successful response
          content:
            application/json:
              schema:
                type: object
                properties:
                  data:
                    type: array
                    items:
                      $ref: '#/components/schemas/Payment'

  # ACCOUNTS
  /accounts:
    get:
      summary: List all accounts
      description: Get all bank and cash accounts
      operationId: getAccounts
      responses:
        '200':
          description: Successful response
          content:
            application/json:
              schema:
                type: object
                properties:
                  data:
                    type: array
                    items:
                      $ref: '#/components/schemas/Account'

  # CATEGORIES
  /categories:
    get:
      summary: List all categories
      operationId: getCategories
      parameters:
        - name: type
          in: query
          description: Filter by category type
          schema:
            type: string
            enum: [income, expense, item, other]
      responses:
        '200':
          description: Successful response
          content:
            application/json:
              schema:
                type: object
                properties:
                  data:
                    type: array
                    items:
                      $ref: '#/components/schemas/Category'

  # MCP ENDPOINT
  /mcp:
    post:
      summary: MCP Server Endpoint
      description: Model Context Protocol server for AI interactions
      operationId: mcpRequest
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              properties:
                method:
                  type: string
                params:
                  type: object
      responses:
        '200':
          description: MCP response
          content:
            application/json:
              schema:
                type: object

components:
  schemas:
    Invoice:
      type: object
      properties:
        id:
          type: integer
        company_id:
          type: integer
        invoice_number:
          type: string
        invoice_date:
          type: string
          format: date
        due_date:
          type: string
          format: date
        amount:
          type: number
          format: float
        currency_code:
          type: string
        status:
          type: string
          enum: [draft, sent, viewed, partial, paid, cancelled]
        customer:
          $ref: '#/components/schemas/Customer'
        items:
          type: array
          items:
            type: object

    InvoiceInput:
      type: object
      required:
        - customer_id
        - invoice_date
        - due_date
        - items
      properties:
        customer_id:
          type: integer
        invoice_number:
          type: string
        invoice_date:
          type: string
          format: date
        due_date:
          type: string
          format: date
        currency_code:
          type: string
          default: USD
        items:
          type: array
          items:
            type: object
            required:
              - name
              - quantity
              - price
            properties:
              name:
                type: string
              description:
                type: string
              quantity:
                type: number
              price:
                type: number

    Customer:
      type: object
      properties:
        id:
          type: integer
        company_id:
          type: integer
        name:
          type: string
        email:
          type: string
        phone:
          type: string
        address:
          type: string
        currency_code:
          type: string
        enabled:
          type: boolean

    CustomerInput:
      type: object
      required:
        - name
        - email
      properties:
        name:
          type: string
        email:
          type: string
          format: email
        phone:
          type: string
        address:
          type: string
        currency_code:
          type: string
          default: USD

    Bill:
      type: object
      properties:
        id:
          type: integer
        bill_number:
          type: string
        billed_at:
          type: string
          format: date
        due_at:
          type: string
          format: date
        amount:
          type: number
        currency_code:
          type: string
        status:
          type: string

    Payment:
      type: object
      properties:
        id:
          type: integer
        amount:
          type: number
        paid_at:
          type: string
          format: date
        payment_method:
          type: string
        reference:
          type: string

    Account:
      type: object
      properties:
        id:
          type: integer
        name:
          type: string
        number:
          type: string
        currency_code:
          type: string
        opening_balance:
          type: number
        current_balance:
          type: number

    Category:
      type: object
      properties:
        id:
          type: integer
        name:
          type: string
        type:
          type: string
        color:
          type: string
        enabled:
          type: boolean

  securitySchemes:
    OAuth2:
      type: oauth2
      description: |
        OAuth 2.1 authentication with PKCE support.
        
        After authorization, you'll receive an access token that provides
        company-scoped access to your Akaunting data.
      flows:
        authorizationCode:
          authorizationUrl: https://your-domain.akaunting.com/oauth/authorize
          tokenUrl: https://your-domain.akaunting.com/oauth/token
          refreshUrl: https://your-domain.akaunting.com/oauth/token
          scopes:
            mcp:use: Access MCP server functionality
            read-invoices: Read invoice data
            write-invoices: Create and modify invoices
            delete-invoices: Delete invoices
            read-customers: Read customer data
            write-customers: Create and modify customers
            delete-customers: Delete customers
            read-bills: Read bill/expense data
            write-bills: Create and modify bills
            delete-bills: Delete bills
            read-payments: Read payment data
            write-payments: Create and modify payments
            read-accounts: Read account data
            read-categories: Read category data

security:
  - OAuth2:
      - mcp:use
      - read-invoices
      - read-customers
      - read-bills
      - read-payments
      - read-accounts
      - read-categories
```

**🔧 ÖNEMLI:** Yukarıdaki schema'da **iki yerde** `https://your-domain.akaunting.com` yazan yerleri kendi domain'inizle değiştirin:
1. `servers[0].url` satırı
2. `securitySchemes.OAuth2.flows.authorizationCode` altındaki URL'ler

### Adım 2.4: Authentication Ayarları

Schema'yı yapıştırdıktan sonra, sayfayı aşağı scroll edin:

1. **Authentication** dropdown'unu açın
2. **Authentication Type:** `OAuth` seçin
3. Aşağıdaki bilgileri girin:

```
Client ID: [Adım 1.1'de aldığınız Client ID]
Client Secret: [Adım 1.1'de aldığınız Client Secret]
Authorization URL: https://your-domain.akaunting.com/oauth/authorize
Token URL: https://your-domain.akaunting.com/oauth/token
Scope: mcp:use read-invoices read-customers read-bills read-payments read-accounts
Token Exchange Method: Default (POST request)
```

4. **Save** butonuna tıklayın

### Adım 2.5: Privacy Policy (İsteğe bağlı)

ChatGPT Action'lar privacy policy ister:

```
Privacy Policy URL: https://your-domain.akaunting.com/privacy
```

Eğer privacy page'iniz yoksa, bir tane oluşturun veya genel bir privacy statement ekleyin.

---

## 3️⃣ TEST ETME

### İlk Test: OAuth Flow

1. ChatGPT konuşma penceresinde yazın:
   ```
   Show me my latest invoices
   ```

2. ChatGPT bir popup açacak: "Authorize this GPT to access your Akaunting account"

3. **Allow** butonuna tıklayın

4. Akaunting OAuth authorization sayfası açılacak:
   - Akaunting'e giriş yapın (zaten giriş yaptıysanız atlanır)
   - **Hangi şirkete erişim izni verileceğini** seçin
   - **Hangi scope'ların izin verileceğini** görüyorsunuz
   - **Authorize** butonuna tıklayın

5. ChatGPT'ye geri yönlendirileceksiniz

6. ChatGPT artık faturalarınızı gösterebilecek!

### Test Senaryoları

```
// Invoice queries
"Show me my last 5 invoices"
"What's my total unpaid invoice amount?"
"Show me invoices from last month"
"Create a new invoice for customer John Doe"

// Customer queries
"List all my customers"
"Show me customers with outstanding invoices"
"Find customer with email john@example.com"

// Bills queries
"Show me all unpaid bills"
"What's my total expenses this month?"

// Financial queries
"What's my current cash balance?"
"Show me all my bank accounts"
"What are my income categories?"

// MCP queries
"Analyze my revenue trend for the last 6 months"
"Which customers owe me the most money?"
"Give me a financial summary for this quarter"
```

### Debug / Troubleshooting

#### ❌ "OAuth authorization failed"

**Kontrol edin:**
1. Client ID ve Secret doğru mu?
2. Redirect URL tam eşleşiyor mu?
   - Akaunting client: `https://chatgpt.com/connector_platform_oauth_redirect`
   - ChatGPT action: Aynı URL
3. HTTPS kullanıyor musunuz? (HTTP çalışmaz!)

**Log kontrol:**
```bash
tail -f storage/logs/laravel.log | grep -i oauth
```

#### ❌ "PKCE required but not provided"

ChatGPT otomatik PKCE kullanır, ama eğer hata alırsanız:

`.env` dosyasında:
```env
OAUTH_REQUIRE_PKCE=true
```

Config cache temizleyin:
```bash
php artisan config:clear
```

#### ❌ "Invalid scope"

Talep edilen scope'lar Akaunting'de tanımlı mı kontrol edin:

`config/oauth.php`:
```php
'scopes' => [
    'mcp:use' => 'Access MCP server',
    'read-invoices' => 'Read invoices',
    'read-customers' => 'Read customers',
    // ... diğerleri
],
```

#### ❌ "Company access denied"

OAuth authorization sırasında kullanıcının erişim izni olan şirketi seçtiğinden emin olun.

Database kontrol:
```sql
SELECT * FROM user_companies 
WHERE user_id = YOUR_USER_ID;
```

#### ❌ "Action timeout"

API response çok yavaşsa:

1. Database index'leri kontrol edin
2. Query optimization yapın
3. Caching ekleyin
4. ChatGPT action timeout'u artırın (max 45 saniye)

---

## 4️⃣ GELİŞMİŞ KULLANIM

### Özel Promptlar

GPT instructions'a ekleyebilirsiniz:

```markdown
## Data Formatting Rules
- Always format currency amounts with proper symbols and decimals
- Use tables for lists of invoices/customers
- Show totals at the end of financial summaries
- Include dates in user's preferred format

## Error Handling
- If API returns an error, explain it in simple terms
- Suggest corrective actions when possible
- Don't expose technical error messages to users

## Privacy & Security
- Never ask for or store OAuth credentials
- Don't share sensitive financial data in chat history
- Remind users to review authorization scopes
```

### Batch Operations

ChatGPT'ye kompleks işlemler yaptırabilirsiniz:

```
"Create invoices for these customers:
- John Doe, $500, due in 30 days
- Jane Smith, $750, due in 15 days  
- Acme Corp, $2000, due in 60 days"
```

### Reporting & Analytics

```
"Generate a monthly report showing:
- Total revenue
- Outstanding invoices
- Top 5 customers by revenue
- Expense breakdown by category"
```

### Webhooks & Notifications (İleride)

Akaunting'e webhook ekleyerek ChatGPT'yi otomatik bilgilendirebilirsiniz:
- Yeni ödeme alındığında
- Fatura vadesi yaklaştığında
- Düşük bakiye uyarısı

---

## 5️⃣ GÜVENLİK EN İYİ PRATİKLER

### ✅ YAPILMASI GEREKENLER

1. **HTTPS Kullanın**
   - Let's Encrypt ücretsiz SSL
   - OAuth HTTP üzerinde çalışmaz

2. **Minimum Scope İzni**
   ```
   # Sadece ihtiyacınız olanları verin
   mcp:use read-invoices read-customers
   
   # TÜMÜNÜ VERMEYIN:
   # * (tüm izinler) ❌
   ```

3. **Token Expiration**
   ```env
   OAUTH_ACCESS_TOKEN_LIFETIME=60      # 1 saat
   OAUTH_REFRESH_TOKEN_LIFETIME=20160  # 14 gün
   ```

4. **Rate Limiting**
   ```php
   // config/oauth.php
   'rate_limit' => [
       'per_minute' => 60,
       'per_hour' => 1000,
   ],
   ```

5. **Audit Logging**
   - OAuth erişim loglarını tutun
   - Anormal aktiviteleri izleyin
   - Düzenli olarak authorized apps'leri review edin

6. **Client Secret Güvenliği**
   - Client Secret'ı asla paylaşmayın
   - Git repository'e commit etmeyin
   - Environment variable olarak saklayın

### ❌ YAPILMAMASI GEREKENLER

1. **HTTP Kullanmayın** (HTTPS şart!)
2. **Client Secret'ı Frontend'de Saklamayın**
3. **Wildcard Redirect URI Kullanmayın**
4. **Token'ları URL'de Göndermeyin**
5. **Long-lived Token'lar Oluşturmayın** (1 yıldan uzun)

### 🔐 Token Revocation

Kullanıcılar istediği zaman erişimi iptal edebilmeli:

**Akaunting Panelinde:**
1. **Settings** > **OAuth Clients**
2. **Authorized Apps** sekmesi
3. ChatGPT Integration yanında **Revoke** butonu

**Programmatik:**
```bash
curl -X POST https://your-domain.akaunting.com/oauth/token/revoke \
  -d "token=ACCESS_TOKEN" \
  -d "client_id=CLIENT_ID" \
  -d "client_secret=CLIENT_SECRET"
```

---

## 6️⃣ PRODUCTION CHECKLIST

Canlıya almadan önce kontrol edin:

### Infrastructure
- [ ] HTTPS aktif ve geçerli
- [ ] SSL sertifikası valid (Let's Encrypt recommended)
- [ ] Domain DNS ayarları doğru
- [ ] Firewall OAuth endpoints'lere izin veriyor
- [ ] CDN (varsa) OAuth paths'i bypass ediyor

### Akaunting OAuth Module
- [ ] Module yüklü ve aktif
- [ ] Migrations çalıştırıldı
- [ ] `.env` ayarları production için yapılandırıldı
- [ ] Passport installed (`php artisan passport:install`)
- [ ] Client oluşturuldu ve test edildi
- [ ] Discovery endpoint çalışıyor (`/.well-known/oauth-authorization-server`)

### ChatGPT Configuration
- [ ] GPT oluşturuldu
- [ ] OpenAPI schema eklendi ve domain güncellendi
- [ ] OAuth client credentials doğru girildi
- [ ] Scope'lar minimal ve gerekli olanlarla sınırlı
- [ ] Privacy policy URL eklendi
- [ ] Test conversation yapıldı ve başarılı

### Security
- [ ] PKCE enabled
- [ ] Client secrets hashed
- [ ] Rate limiting aktif
- [ ] Audit logging açık
- [ ] CORS ayarları yapılandırıldı
- [ ] Token lifetime'lar makul (access: 1 saat, refresh: 14 gün)

### Monitoring
- [ ] Error tracking (Sentry, Bugsnag, vs.)
- [ ] OAuth endpoint monitoring
- [ ] Alert'ler kuruldu (failed auth, rate limit, vs.)
- [ ] Log rotation aktif

### Documentation
- [ ] Kullanıcılar için OAuth rehberi hazır
- [ ] Support team bilgilendirildi
- [ ] FAQ hazırlandı

### Backup & Recovery
- [ ] Database backup planı var
- [ ] OAuth clients/tokens backup'ı alınıyor
- [ ] Disaster recovery planı var
- [ ] Rollback prosedürü test edildi

---

## 7️⃣ DESTEK & KAYNAKLAR

### Official Docs
- **Akaunting API:** https://akaunting.com/docs/api
- **Laravel Passport:** https://laravel.com/docs/passport
- **ChatGPT Actions:** https://platform.openai.com/docs/actions
- **OAuth 2.1:** https://oauth.net/2.1/
- **MCP Protocol:** https://modelcontextprotocol.io

### Community
- **Akaunting Forum:** https://akaunting.com/forum
- **Discord:** [Akaunting Discord invite link]
- **GitHub Issues:** [Your repo issues page]

### Video Tutorials
*(Kendi tutorial videolarınızı buraya ekleyebilirsiniz)*

### Common Questions

**Q: ChatGPT her seferinde authorization istiyor?**  
A: Refresh token expired olabilir. Token lifetime'ı artırın veya "Remember me" seçeneği ekleyin.

**Q: Birden fazla şirkete erişim verebilir miyim?**  
A: Evet, authorization sırasında kullanıcı seçebilir. Multiple companies için multiple authorization yapılması gerekir.

**Q: Free ChatGPT hesabıyla çalışır mı?**  
A: Hayır, GPT Actions sadece ChatGPT Plus, Team, ve Enterprise hesaplarda mevcut.

**Q: MCP endpoint nedir, neden gerekli?**  
A: MCP (Model Context Protocol) AI agents'lerin structured data'ya erişmesini sağlar. ChatGPT daha iyi context anlayışı için kullanır.

**Q: Rate limit aştım, ne yapmalıyım?**  
A: `config/oauth.php`'de rate limit değerlerini artırın, veya caching ekleyin.

**Q: Token'ları nasıl revoke ederim?**  
A: Akaunting panelinden **Settings > OAuth Clients > Authorized Apps** veya programmatik olarak `/oauth/token/revoke` endpoint.

---

## 🎉 BAŞARIYLA TAMAMLANDI!

Artık ChatGPT ile Akaunting hesabınızı yönetebilirsiniz! 🚀

**Örnek kullanım:**
```
💬 "Show me my top 10 customers by revenue this year"
💬 "Create an invoice for $1,500 to Acme Corp, due in 30 days"
💬 "What's my total outstanding receivables?"
💬 "List all unpaid invoices older than 60 days"
💬 "Give me a financial summary for Q1 2026"
```

---

**Sorularınız için:**
- 📧 Email: support@yourcompany.com
- 💬 Live chat: [Your support URL]
- 📚 Docs: [Documentation link]

**Mutlu muhasebecilik! 📊✨**