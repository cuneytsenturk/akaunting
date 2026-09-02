# Webpack→Vite Migrasyonu: Değişiklik İncelemesi ve Webpack Kaldırma Planı

*Hazırlanma tarihi: 2026-07-10 — kaynak: `frontend-testing-and-vite-poc` branch'inin gerçek çalışma dizini (`git status`/`git diff` ile doğrulandı, sadece sohbet özetlerine değil).*

## 0. ÖNCELİKLİ UYARI — Hiçbir Şey Henüz Commit Edilmemiş

Bunu her şeyden önce belirtmem gerekiyor: `git log master..frontend-testing-and-vite-poc` **sıfır commit** gösteriyor. Haftalardır konuştuğumuz, 45 testle doğrulanmış tüm bu iş, şu an sadece **commit edilmemiş çalışma dizini değişikliği** olarak duruyor (56 dosya — 42 değiştirilmiş, 14 yeni). Dosyaların kendisi gerçek ve doğru tarihli (`Script.php` en son 9 Temmuz 15:38'de değişmiş), yani iş kaybolmamış — ama şu ana kadar hiçbir güvenlik ağı yok: bir `git checkout`, yanlışlıkla `git clean -fd`, disk sorunu ya da VS Code'un kendi bir hatası bu işin tamamını geri dönülemez şekilde silebilir.

**Bu dokümanın asıl önerisi budur: önce commit edin (Bölüm 4'teki stratejiyle), sonra gerisiyle ilgilenin.**

## 1. Kapsam Dışı Değişiklikler — Karıştırmayın

`git status`, Vite işiyle hiç ilgisi olmayan, önceden var olan değişiklikler de gösteriyor:

- **`composer.json`** — diff'e baktım, bu **Laravel 11 yükseltme WIP'i** (`LARAVEL_11_UPGRADE_PLAN.md`, yine untracked): PHP `^8.1`→`^8.2`, `laravel/framework`, `akaunting/laravel-mutable-observer`, `barryvdh/laravel-dompdf` gibi paketlerin sürüm bump'ları. Vite'la sıfır ilgisi var — Claude Code'un "dokunmadım" dediği doğru, ama dosya zaten değişik haldeydi.
- **`CLAUDE.md`** (untracked) — daha önceki bir oturumda oluşturulmuş proje dokümantasyonu, Vite işiyle ilgisiz.

Bu ikisini Vite commit'lerine **hiç dahil etmeyin** — ayrı, kendi bağlamlarında ele alınmalı.

## 2. Vite Migrasyonuyla İlgili Değişiklikler

### 2a. Config / Altyapı (5 değişen + 5 yeni dosya)

| Dosya | Değişiklik |
|---|---|
| `package.json` | Sadece **eklemeler** — hiçbir Mix/webpack paketi silinmedi. Yeni script'ler (`build`, `test`, `test:watch`, `test:e2e`), yeni devDependencies (`vite@^5.4.21`, `@vitejs/plugin-vue2`, `laravel-vite-plugin@^1.3.0`, `@rollup/plugin-commonjs`, `@playwright/test`, `vitest`, `jsdom`). `mix`/`production` script'leri dokunulmadan duruyor. |
| `package-lock.json` | Yukarıdakinin otomatik sonucu. |
| `.gitignore` | Playwright çıktı klasörleri + Vite build çıktısı eklendi. |
| `.nvmrc` (yeni) | Node sürümü pinleniyor. |
| `vite.config.mjs` (yeni) | `.js` değil `.mjs` — `laravel-vite-plugin` saf ESM, `package.json`'da `"type":"module"` olmadığı için bu zorunlu (Bölüm 5j, madde 1). |
| `vite-entries.json` (yeni) | `(folder,file)` → kaynak-dosya-yolu eşlemesinin **tek doğru kaynağı** — hem `vite.config.mjs` hem `Script.php` bunu okuyor. |
| `vitest.config.js`, `playwright.config.js` (yeni) | Test altyapısı config'leri. |
| `.github/workflows/tests.yml` | CI'a frontend test adımları eklendi. |

### 2b. Backend — PHP (2 dosya, en yüksek riskli kısım)

**`app/Providers/App.php`** — diff'ini okudum, 13 satırlık, tek amaçlı bir ekleme: `Vite::createAssetPathsUsing()` ile docroot uyuşmazlığını (proje kökü = docroot, `public/` değil) düzeltiyor. İyi yorumlanmış, minimal.

**`app/View/Components/Script.php`** — diff'ini okudum, bu dosyanın kalbi:
- `alias != 'core'` (modül) dalı **algoritma olarak birebir aynı** — sadece bir `if` bloğunun içine taşınmış, davranış değişmedi (`asset($path)` şeklinde explicit çağrı eklendi çünkü `$source` artık `string|Htmlable` tipinde).
- `alias == 'core'` dalı artık yeni bir `coreSource()` static metoduna gidiyor: `vite-entries.json`'dan yolu buluyor, Laravel'in gerçek `Vite` instance'ını invoke ediyor (`@vite()` direktifinin yaptığı ile birebir aynı) — script+CSS+preload tam paketini dönüyor.
- **`resources/views/components/script.blade.php`** de buna göre güncellendi: `core` ise ham HTML (`{!! $source !!}`) basıyor, değilse eskisi gibi `<script src="">`.

### 2c. Blade Layout Dosyaları (4 dosya)

`admin/scripts.blade.php`, `install/scripts.blade.php`, `wizard/scripts.blade.php`, `portal/scripts.blade.php` — diff'ini okudum (admin + wizard örnekleri):
- `install`/`wizard` dosyaları `<x-script>`'i atlayıp `Script::coreSource()`'u doğrudan çağırıyor (Bölüm 5j madde 4 — `@stack`/`@push` sıralama tuzağı yüzünden).
- `admin`/`portal` dosyalarında `generalAction.js`/`popper.js`'e `defer` eklendi, inline `Layout` IIFE'si `type="module"` oldu (Bölüm 5j madde 5 — script çalışma sırası korunuyor).

### 2d. Paylaşılan JS/Vue Component'leri (8 dosya)

`bootstrap.js`, `plugins/functions.js`, `plugins/dashboard-plugin.js`, `plugins/nprogress-axios.js`, `exceptions/trackers/bugsnag.js`, `components/AkauntingModal.vue`, `components/AkauntingSelect.vue`, `components/AkauntingSelectRemote.vue` — bunlar 22 girişin çoğunda ortak kullanılıyor, değişiklikler: `require()`→`import` dönüşümü, ölü kod temizliği (kendi-kendine gereksiz import'lar), eksik export düzeltmeleri (`bugsnag.js`, `nprogress-axios.js`), `element-ui/lib/locale` için `require()`+`typeof` koruması (`dashboard-plugin.js`). Hepsi Mix ile 25/25 (şimdi 22/22) yeniden derlenip regresyon testinden geçti (Bölüm 5c, 5d).

### 2e. 22 Giriş Dosyası (JS entries)

Auth(2), Banking(4), Common(7), Settings(4), Portal(1), Install(2), Wizard(1), Modules(1) — hepsinde aynı mekanik değişiklik: `require('./../../bootstrap')` → `import './../../bootstrap'`. Ek olarak `wizard/Company.vue`'de korumasız dinamik `require()` (flatpickr locale) → `import()`'a çevrildi (Bölüm 5i — gerçek bir Rollup uyumsuzluğuydu, non-English locale'lerde çökme riski taşıyordu).

### 2f. Testler (yeni — `tests/e2e/`, `tests/js/`)

33 E2E test (Playwright) + 12 unit test (Vitest) — Bölüm 5'ten 5j'ye kadar her domain için eklendi, kritik akışları (login, fatura/gider/transfer/reconciliation oluşturma, kullanıcı davet, izin kontrolü, non-English locale) kapsıyor.

### 2g. Dokümantasyon (yeni)

`webpack-to-vite-roadmap.md`, `vue2-to-vue3-migration-roadmap.md`, `spa-feasibility-analysis.md`, `frontend-testing-strategy.md` — bu sürecin tamamının kaydı.

## 3. Webpack'i Kaldırmak İçin Gereken Adımlar

Şu an webpack/Mix hâlâ `package.json`'da duruyor (bilinçli olarak, rollback güvenliği için). Kaldırmadan önce:

1. **Önce commit edin ve en az birkaç hafta production'da (ya da en azından staging'de) sorunsuz çalıştığını görün** — webpack'i silmek geri dönüşü zorlaştırır, aceleye getirilmemeli.
2. `webpack.mix.js` dosyasını silin.
3. `package.json`'dan şu devDependencies'i kaldırın: `laravel-mix`, `webpack`, `webpack-cli`, `vue-loader`, `vue-style-loader`, `vue-template-compiler` (dikkat: bu Vue2 SFC compiler'ı, `@vitejs/plugin-vue2` de kullanıyor olabilir — kaldırmadan önce kontrol edin, muhtemelen kalması gerekiyor), `css-loader`, `mini-css-extract-plugin`, `style-loader`, `url-loader`, `postcss-loader`, `sass-loader`, `node-sass` (zaten kullanılmadığı tespit edilmişti, Bölüm 6), `laravel-mix-tailwind`, `cross-env`, `@vue/cli-plugin-babel`, `@vue/cli-plugin-eslint`, `@vue/cli-service`, `@vue/eslint-config-prettier`, `babel-plugin-component`.
4. `package.json`'daki `dev`/`development`/`watch`/`watch-poll`/`hot`/`prod`/`production` script'lerini kaldırıp `build`'i tek prod-build komutu yapın (ya da `production`'ı `vite build`'e yönlendirin, geriye dönük isim uyumluluğu için).
5. `npm install` ile `package-lock.json`'ı temizleyin, `node_modules`'ı sıfırdan kurup **tüm 45 testi tekrar çalıştırın** — webpack'in gizli bir bağımlılık olarak hâlâ gerekip gerekmediğini bu doğrular.
6. `.github/workflows/tests.yml`'de artık kullanılmayan Mix'e referans kalmadığını doğrulayın.
7. README/kurulum dokümantasyonunda "webpack" geçen yerleri güncelleyin.

## 4. Önerilen Commit Stratejisi

Tek dev commit yerine, mantıksal, gözden geçirilebilir parçalar halinde:

1. `test infra: Playwright + Vitest kurulumu` — `playwright.config.js`, `vitest.config.js`, `tests/e2e/`, `tests/js/`, `package.json` test script'leri.
2. `fix: require()/import karışıklığı ve paylaşılan component'lerdeki ölü kod/export hataları` — Bölüm 2d + 2e'deki tüm dosyalar (bu, Script.php refactöründen bağımsız olarak da anlamlı, çünkü Mix build'i de düzeltiyor).
3. `feat: Vite build altyapısı` — `vite.config.mjs`, `vite-entries.json`, `package.json`'daki vite/laravel-vite-plugin bağımlılıkları.
4. `feat: Script.php'yi Vite manifest'i kullanacak şekilde refactor et` — `app/Providers/App.php`, `app/View/Components/Script.php`, `resources/views/components/script.blade.php`, 4 blade layout dosyası.
5. `ci: Vite build + frontend testlerini pipeline'a ekle` — `.github/workflows/tests.yml`.
6. `docs: migrasyon dokümantasyonu` — 4 roadmap dosyası.

Bu sıralama, PR incelemesini de kolaylaştırır — her commit tek başına anlamlı ve test edilebilir.
