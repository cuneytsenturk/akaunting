# Akaunting OAuth 2.0 Test Suite

Bu test suite, Akaunting OAuth 2.0 implementasyonunun tüm özelliklerini test eder.

## Test Coverage

### ✅ Kapsanan Özellikler:

1. **Personal Access Token Management**
   - Token oluşturma
   - Company ID otomasyonu
   - Token listeleme
   - Token silme

2. **OAuth Client Management**
   - Client CRUD işlemleri
   - Client secret regeneration
   - Company isolation

3. **Token Operations**
   - Token introspection (RFC 7662)
   - Token revocation (RFC 7009)
   - Token expiration kontrolü

4. **Company-Aware Features**
   - Token'da company_id
   - Company isolation
   - Cross-company access prevention
   - Priority system (Token > Header > Query)

5. **Discovery & Metadata**
   - OAuth server metadata (RFC 8414)
   - Scope listing

6. **Security Features**
   - Company access control
   - Token validation
   - Expired token detection

## Test Çalıştırma

### Tüm OAuth Testlerini Çalıştır:
```bash
php artisan test --filter=OAuthFlowTest
```

### Tek Bir Test:
```bash
php artisan test --filter=it_can_create_personal_access_token_with_company_id
```

### Verbose Output:
```bash
php artisan test --filter=OAuthFlowTest --verbose
```

### Coverage Report:
```bash
php artisan test --filter=OAuthFlowTest --coverage
```

## Test Senaryoları (15 Test)

| # | Test Adı | Açıklama | Priority |
|---|----------|----------|----------|
| 1 | `it_can_create_personal_access_token_with_company_id` | Personal token oluşturma | 🔴 High |
| 2 | `it_uses_company_id_from_token_automatically` | Token'dan otomatik company_id | 🔴 High |
| 3 | `it_can_introspect_token_and_get_company_id` | Token introspection | 🟡 Medium |
| 4 | `it_can_revoke_access_token` | Token iptal etme | 🟡 Medium |
| 5 | `it_only_shows_tokens_for_current_company` | Company isolation | 🔴 High |
| 6 | `it_creates_oauth_client_with_company_id` | OAuth client oluşturma | 🟡 Medium |
| 7 | `it_creates_separate_tokens_for_different_companies` | Multi-company tokens | 🔴 High |
| 8 | `it_can_delete_personal_access_token` | Token silme | 🟡 Medium |
| 9 | `it_can_list_available_scopes` | Scope listesi | 🟢 Low |
| 10 | `it_can_regenerate_client_secret` | Secret yenileme | 🟡 Medium |
| 11 | `it_returns_oauth_server_metadata` | Discovery endpoint | 🟢 Low |
| 12 | `it_prevents_access_to_other_company_tokens` | Security - Cross-company | 🔴 High |
| 13 | `it_detects_expired_tokens_in_introspection` | Token expiration | 🟡 Medium |
| 14 | `it_can_perform_full_client_crud` | Client CRUD | 🟡 Medium |
| 15 | `it_prioritizes_token_company_id_over_header_and_query` | Priority system | 🔴 High |

## Manuel Test (Postman/Curl)

### 1. Personal Access Token Oluştur:
```bash
POST http://localhost/oauth/personal-access-tokens
Headers:
  Authorization: Bearer {session_token}
  Content-Type: application/json

Body:
{
  "name": "Test Mobile App",
  "scopes": ["read", "write"]
}
```

### 2. Token ile API Request:
```bash
GET http://localhost/api/invoices
Headers:
  Authorization: Bearer {access_token}
  Accept: application/json
```

### 3. Token Introspection:
```bash
POST http://localhost/oauth/token/introspect
Body:
  token={access_token}
  token_type_hint=access_token
```

## Troubleshooting

### Test Başarısız Oluyorsa:

1. **Migration kontrolü:**
```bash
php artisan migrate:status
```

2. **Passport keys:**
```bash
php artisan passport:keys
```

3. **Database temizle:**
```bash
php artisan migrate:fresh --seed
```

4. **Config cache:**
```bash
php artisan config:clear
php artisan cache:clear
```

## Expected Results

Tüm testler geçerse:
```
✅ ALL TESTS PASSED! (15/15)
```

## CI/CD Integration

GitHub Actions için:
```yaml
- name: Run OAuth Tests
  run: php artisan test --filter=OAuthFlowTest
```

## Notes

- Testler RefreshDatabase trait kullanır (her test'te database sıfırlanır)
- Companies ve Users otomatik oluşturulur
- Passport installation otomatik yapılır
- OAuth config otomatik aktif edilir
