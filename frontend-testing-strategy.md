# Akaunting Core: Frontend Test Stratejisi

*Hazırlanma tarihi: 2026-07-01 — kaynak: `akaunting` core repo. İlgili dokümanlar: `webpack-to-vite-roadmap.md` (1), `vue2-to-vue3-migration-roadmap.md` (2), `spa-feasibility-analysis.md` (3). Bu doküman, üç migrasyona paralel/tamamlayıcı bir "Faz 0" olarak okunmalı.*

## 1. Mevcut Durum

Doğruladım: **frontend'de hiçbir test altyapısı yok.** `package.json`'da test script'i, `jest`/`vitest`/`cypress`/`playwright`/`@vue/test-utils` bağımlılığı yok. CI (`.github/workflows/tests.yml`) sadece `npm run production` (build'in patlamadığını doğruluyor, davranışı değil) ve `php artisan test --parallel` (backend PHPUnit) çalıştırıyor. Frontend kodu `window.axios`/`window.Laravel` gibi global'lara yoğun bağımlı (`mixins/global.js`'de 14, `plugins/form.js`'de 5 kullanım) — bu, test yazarken mocklanması gereken noktalar.

## 2. Akaunting'in Yapısına Göre Hangi Test Katmanı Nerede İşe Yarar

Üç katman var, ama Akaunting'in mimarisi (25 bağımsız sayfa-entry, server-render edilmiş HTML üzerine mount, form verisinin DOM attribute'larından türetilmesi, modül hook sistemi) her katmanın değerini farklılaştırıyor:

**Unit test (saf JS mantığı):** `plugins/form.js`, `plugins/error.js`, mixin içindeki saf yardımcı fonksiyonlar gibi DOM'a bağımlı olmayan/az bağımlı kod parçaları için uygun. Bunlar bugün, hiçbir migrasyonu beklemeden yazılabilir.

**Component test (`@vue/test-utils` ile .vue dosyalarını izole mount etme):** `AkauntingSearch.vue`, `AkauntingModal.vue` gibi 50 component için değerli, ama Element UI + custom global component'lerin mock'lanması gerekiyor — kurulum maliyeti var. **En yüksek getiriyi Vue 3 geçişi sırasında verir** (Bölüm 3).

**E2E test (Playwright, gerçek tarayıcı + çalışan uygulama):** Akaunting'in mimarisine **en uyumlu** katman, çünkü: (a) sayfalar server-render + Vue-mount hibrit — bunu gerçekten test etmenin tek yolu gerçek bir sayfa yüklemek; (b) `form.js`'in DOM-attribute-tabanlı veri toplama modeli, izole unit testte zor taklit edilir ama E2E'de doğal olarak çalışır; (c) modül hook noktalarının (`@stack('content_end')` vb.) gerçekten doğru içerik enjekte ettiğini sadece çalışan bir uygulamada, gerçek bir modül kurulu haldeyken görebilirsiniz; (d) **E2E testleri bundler'dan (Webpack/Vite) ve hatta Vue sürümünden bağımsızdır** — render edilen sonucu test eder, implementasyonu değil. Bu yüzden bugün yazılan bir E2E testi, Vite geçişinden sonra da, Vue 3 geçişinden sonra da, hatta Inertia'ya geçilse bile **değişmeden çalışmaya devam eder.**

## 3. Ne Zaman Eklemeli — Önerilen Sıralama

**Şimdi, hiçbir şeyi değiştirmeden (Faz 0):**

- **Playwright ile küçük bir E2E iskeleti kurun** (mevcut Webpack/Mix, Vue2, Blade — hiçbir şeye dokunmadan). 5-10 kritik akışı kapsayın: login, fatura/gider oluşturma, banking transaction, bir izin-kısıtlı sayfa (yetki modelini doğrulamak için), mümkünse bir modül hook senaryosu. Bu, hem bugün değer üretir hem **üç migrasyonun da ortak regresyon güvenlik ağı** olur — Vite geçişinde `require()`→`import` düzeltmeleri, Vue3'te Element Plus/vee-validate v4 davranış farkları, SPA/Inertia'da navigasyon değişiklikleri, hepsi bu aynı E2E suite ile yakalanabilir.
- **Vitest ile saf mantık için minimal unit test'ler** (`form.js`, `error.js`) — Vitest, projenin build config'inden bağımsız çalıştırılabilir (standalone config), Webpack/Mix'e dokunmaz.
- CI'a yeni, ayrı bir job ekleyin (`npm run test` unit + ayrı bir Playwright job — bu ikincisi çalışan bir app instance'ı gerektirir, `.env.testing` + sqlite ile CI'da ayağa kaldırılabilir, mevcut `tests.yml`'deki PHPUnit job'ına benzer şekilde).

**Vite geçişi sırasında (Faz 1):** Vitest zaten Vite'ın config'ini paylaştığı için doğal bir uyum sağlanır — unit/component test yazımı burada hızlanır. E2E suite, geçişin regresyon kontrolü olarak çalışır (Bölüm 2, madde d).

**Vue 3 geçişi sırasında (Faz 2) — asıl büyük yatırım burada:** Element Plus + vee-validate v4'e geçerken **her component'e dokunuyorsunuz zaten** — bu noktada `@vue/test-utils` v2 + Vitest ile component test yazmak, ayrıca sonradan yazmaktan çok daha ucuz, çünkü migrasyonun kendisi component'i zaten "açıp bakmayı" gerektiriyor. Aynı zamanda tam da bu fazın en riskli kısmını (davranış farkları) yakalayan test bu. Bu fazın "regresyon yok" iddiasının kanıtı, component testler + E2E suite'in yeşil kalması olur.

**SPA/Inertia değerlendirmesi sırasında (Faz 3):** E2E suite ana güvenlik ağı olmaya devam eder; navigasyon davranışı değiştiği için E2E senaryoları güncellenir ama testin kendisi (ne test edildiği) büyük ölçüde aynı kalır.

## 4. Neden Bu Sıralama

- E2E'yi en başta, sıfır risk ve sıfır bağımlılıkla eklemek mantıklı çünkü Akaunting'in mimarisine en uygun katman bu ve üç migrasyon boyunca aynı testler geçerliliğini koruyor — yatırımın amortismanı en yüksek.
- Component test yatırımını Vue3 fazına ertelemek, "önce Vue2 için yaz, sonra Vue3 için tekrar yaz" çift işini önlüyor (`@vue/test-utils` v1↔v2 API farkı var).
- Unit test her zaman düşük maliyetli ve düşük riskli — sıralamadan bağımsız, ne zaman başlarsanız başlayın kayıp yok.

## 5. CI/CD Entegrasyonu — GitHub Actions

Mevcut `.github/workflows/tests.yml`'i inceledim: tek bir `tests` job'ı (PHP 8.1/8.2/8.3 matrix'i), her `push`/`pull_request`/gece yarısı `schedule`/`workflow_dispatch` tetiklendiğinde çalışıyor; adımlar `npm install` → `npm run production` (sadece build'in patlamadığını doğruluyor) → `composer test` (aslında `composer install` + `dump-autoload` yapıyor) → `php artisan test --parallel`. Frontend testleri **backend ile aynı tetikleyicilerle, aynı dosyaya, iki yeni ayrı job olarak** eklenmeli — mevcut PHP job'ına dokunmadan:

**Job 1 — `frontend-unit` (Vitest):** PHP'ye ihtiyaç duymaz, hızlı, diğer job'larla paralel çalışır.

```yaml
frontend-unit:
  name: Frontend Unit Tests
  runs-on: ubuntu-latest
  steps:
    - uses: actions/checkout@v4
    - uses: actions/setup-node@v4
      with:
        node-version: 20
        cache: npm
    - run: npm install
    - run: npm run test        # vitest run
```

**Job 2 — `frontend-e2e` (Playwright):** Gerçek çalışan bir uygulama gerektirdiği için daha ağır — PHP kurulumu, veritabanı, `php artisan serve`, sonra Playwright.

```yaml
frontend-e2e:
  name: Frontend E2E Tests
  runs-on: ubuntu-latest
  steps:
    - uses: actions/checkout@v4
    - uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
        extensions: bcmath, ctype, dom, fileinfo, intl, gd, json, mbstring, pdo, pdo_sqlite, openssl, sqlite, xml, zip
    - run: cp .env.testing .env
    - run: composer test
    - uses: actions/setup-node@v4
      with:
        node-version: 20
        cache: npm
    - run: npm install
    - run: npm run production   # (Vite geçişinden sonra: npm run build)
    - run: npx playwright install --with-deps chromium
    - run: php artisan install --db-name=... --admin-email=test@akaunting.com --admin-password=...
    - run: php artisan serve --port=8000 &
    - run: npx wait-on http://localhost:8000
    - run: npx playwright test
    - uses: actions/upload-artifact@v4
      if: always()
      with:
        name: playwright-report
        path: playwright-report/
        retention-days: 14
```

**Dikkat edilmesi gereken tek gerçek teknik detay:** `.env.testing`'deki `DB_DATABASE=:memory:` ayarı PHPUnit'in tek-process test çalıştırması için uygun ama **E2E için uygun değil** — `php artisan serve`, her HTTP isteğini ayrı bir PHP süreci olarak işleyebilir, bu da in-memory sqlite'ın istekler arasında sıfırlanması anlamına gelir (login olup bir sonraki adımda "kullanıcı yok" hatası almak gibi). E2E job'ı için `.env`'de dosya tabanlı bir sqlite (`DB_DATABASE=database/testing.sqlite`) kullanılmalı, iş başında migrate+seed edilmeli.

**"Dönüp bakıp düzeltmek" ihtiyacı** için `upload-artifact` adımı kritik — `if: always()` sayesinde test geçse de geçmese de Playwright'ın ürettiği HTML rapor, ekran görüntüleri, videolar ve "trace" dosyaları (adım adım geri oynatılabilir) CI çıktısına eklenir; bir regresyon olduğunda GitHub Actions'ın "Artifacts" bölümünden indirip tam olarak neyin, hangi adımda bozulduğunu görebilirsiniz.

**Zorunlu hale getirme:** Bu iki job da GitHub'da repository ayarlarından ("Branch protection rules" → "Required status checks") backend `tests` job'ı gibi **zorunlu** işaretlenmeli — böylece bir PR, frontend'i bozan bir değişiklik içeriyorsa, backend regresyonunda olduğu gibi merge edilemez.

## 6. Öneri

Bugün başlayın: Playwright ile 5-10 kritik akışı kapsayan bir E2E iskeleti + `form.js`/`error.js` için birkaç Vitest unit test'i, CI'a Bölüm 5'teki iki ayrı job olarak eklenir. Bu, mevcut webpack/Vue2/Blade yapısına hiç dokunmadan yapılabilir ve üç migrasyonun da ortak güvenlik ağı olur. Component test yatırımının ağırlığı Vue 3 fazına bırakılır.
