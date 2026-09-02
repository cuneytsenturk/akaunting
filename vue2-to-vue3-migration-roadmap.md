# Akaunting Core: Vue 2 → Vue 3 Geçiş Analizi ve Yol Haritası

*Hazırlanma tarihi: 2026-07-01 — kaynak: `akaunting` core repo (temiz checkout, `modules/` boş). Sıralama: `webpack-to-vite-roadmap.md` (1) → bu doküman (2) → `spa-feasibility-analysis.md` (3).*

## 1. Sonuç (kısa)

**Teknik olarak mümkün, ama bu Webpack→Vite geçişinden tamamen farklı bir kategoride bir proje.** Vite geçişi sadece build aracını değiştiriyordu (core-only, izole, geri dönülebilir, haftalar sürer). Vue 3 geçişi bir **framework runtime** değişikliği — Element UI, vee-validate, vue2-editor gibi kritik bağımlılıkların değişmesini, ~25 giriş noktasının yeniden yazılmasını ve **modül (App) ekosistemi için stratejik bir karar**ı gerektiriyor. Gerçekçi süre: aylar (3-6+), tek geliştiriciyle daha uzun. Sıralama önerim: **önce Vite, sonra Vue 3** — gerekçesi Bölüm 6'da.

## 2. Neden Vue 3? (motivasyon)

- **Composition API:** Büyük, karmaşık component'ler (örn. `mixins/global.js` — 1651 satır, ~24 paylaşılan component merkezi olarak burada register ediliyor) için çok daha sürdürülebilir bir kod organizasyonu sunuyor; mixin'lerin "hangi property nereden geliyor" belirsizliği ortadan kalkıyor.
- **Performans:** Daha küçük runtime, daha hızlı reactivity sistemi (Proxy tabanlı), daha iyi tree-shaking.
- **Ekosistem yönü:** Yeni kütüphaneler artık öncelikle Vue 3 hedefliyor; Vue 2 resmi olarak EOL (yeni özellik/güvenlik güncellemesi gelmiyor).
- **SPA'ya hazırlık:** Sizin de belirttiğiniz gibi ileride single-page app'e geçiş konuşulacaksa, bunu Vue 3 + Composition API üzerine kurmak, Vue 2 Options API'nin dağınık mixin yapısına kurmaktan çok daha sağlıklı olur.

## 3. Mevcut Yapının Vue 3 Açısından Analizi

Kod tabanını tarayarak çıkardığım somut bulgular:

**Giriş noktası kalıbı:** Her biri `new Vue({ el: '#main-body', mixins: [Global], data() {...} })` şeklinde, server-render edilmiş Blade HTML'i üzerine mount ediliyor (bkz. `webpack-to-vite-roadmap.md` Bölüm 2). Vue 3'te bu `createApp({...}).mount('#main-body')` olur — 25 dosyada mekanik ama tek tek doğrulanması gereken bir değişiklik.

**Global API kalıpları — merkezi, dağınık değil (olumlu bulgu):** `Vue.use()`, `Vue.component()` (24 kayıt), `Vue.prototype.$notify`/`$notifications` (`components/NotificationPlugin/index.js`), ve Vue 2'nin async component `resolve/reject` factory kalıbı (`Vue.component('add-new-component', (resolve, reject) => {...})`, `mixins/global.js` içinde 5 kez) — hepsi Vue 3'te API değiştiriyor (`app.use()`, `app.component()`, `app.config.globalProperties`, `defineAsyncComponent()`). Ama bunlar `dashboard-plugin.js`, `globalComponents.js`, `globalDirectives.js` ve `mixins/global.js` gibi **az sayıda merkezi dosyada toplanmış**, 50 `.vue` dosyasına dağılmış değil. Bu, riski önemli ölçüde azaltıyor.

**Template tarafında düşük risk:** `{{ x | filter }}` pipe-filtre sözdizimi (Vue 3'te tamamen kaldırıldı) **hiç kullanılmıyor** — ilk bakışta bulunan 4 `filters:` eşleşmesi aslında düz veri objeleriymiş (yanlış alarm). `slot-scope` (deprecated, Vue 3'te kaldırıldı) sadece 1 yerde. `.sync` modifier'ı (Vue 3'te v-model'e taşındı) hiç kullanılmıyor. Bunlar pratikte risk oluşturmuyor.

**Element UI ayak izi öngörülenden dar:** Sadece 7 `.vue` dosyası doğrudan `<el-*>` tag kullanıyor; birkaç plugin/entry dosyası (`mixins/global.js`, `globalComponents.js`, `views/common/dashboards.js`, `views/install/update.js`, `views/modules/apps.js`) seçici component import ediyor (Select, Option, Steps, Step, Button, Link, Tooltip, ColorPicker, Input, Popover, DatePicker, Progress) — tam `Vue.use(Element)` değil, tree-shake edilmiş bir kullanım.

## 4. Zorunlu Paket Değişiklikleri

| Paket | Mevcut | Hedef | Risk | Not |
|---|---|---|---|---|
| element-ui | 2.15.13 | Element Plus | **Yüksek** | Vue3 karşılığı yok, farklı paket. Prop/event isimleri kısmen değişti. Kullanım alanı dar (7 dosya + birkaç plugin) ama görsel regresyon testi şart. |
| vee-validate | v2 | v4 | **Yüksek** | Tamamen farklı (composition-based) API, geriye dönük uyumsuz. `plugins/form.js` VeeValidate iç detaylarına doğrudan bağlı görünmüyor (soyutlama var) ama validasyon akışı uygulama genelinde yeniden test edilmeli. |
| vue2-editor | — | Farklı kütüphane (TipTap/Quill) | **Yüksek** | Vue3 karşılığı yok, component-level yeniden yazım. |
| vuedraggable | v2 | v4 | Orta | API büyük ölçüde benzer, versiyon+test. |
| vue-router | v3 | v4 | Düşük-Orta | Sadece `install.js`/`wizard.js`'de kullanılıyor, kapsam dar. |
| v-money | v0.8 | v-money3 | Düşük | 6 dosyada kullanılıyor. |
| @fullcalendar/vue | v6 (Vue2 adapter) | @fullcalendar/vue3 | Düşük | Ayrı paket, API benzer. |
| @sentry/vue | v7 | v7/v8 (Vue3 destekli) | Düşük | Framework-agnostic, config güncellemesi yeterli. |

## 5. Ekosistem / Marketplace Etkisi — En Kritik Risk

Webpack→Vite geçişinin aksine, **bu core-only bir değişiklik değil.** Modüller (App'ler) kendi bağımsız derlenmiş Vue2+Element UI kopyalarını taşımaya devam etseler bile, core'un component sözleşmeleri, CSS class isimleri (Element UI ≠ Element Plus class isimleri) ve paylaşılan davranış kalıpları değişince ekosistem **görsel/işlevsel olarak parçalanma riski** taşır. Bu, pazardaki App'ler için de bir geçiş dalgası tetikleyebilir ya da geçiş yapmayan App'lerin core ile görsel tutarsızlık göstermesine yol açabilir. Bu, teknik bir detay değil, **stratejik bir karar** — ayrı bir ekosistem-iletişim/geçiş-süresi planı gerektirir (App geliştiricilerine önceden duyuru, geçiş rehberi, bir süre için iki sürümün paralel desteklenmesi gibi).

## 6. Sıralama: Önce Vite mi, Önce Vue 3 mü?

**Öneri: Önce Vite, sonra Vue 3.**

Gerekçe:

1. **Riski izole etmek için tek seferde tek değişken değiştirin.** Vite geçişi düşük riskli, izole, geri dönülebilir bir proje — Vue 2 gibi bildiğiniz, stabil bir framework üzerinde sadece build aracını değiştiriyorsunuz. Bir şey bozulursa, tek değişken olduğu için (build aracı) teşhis kolay. Vue 3 + Vite'ı **aynı anda** yapmaya kalkarsanız, bir hata çıktığında bunun Vue 3 runtime sorunu mu yoksa Vite bundling sorunu mu olduğunu ayırt etmek çok daha zor olur.
2. **Vite geçişi, Vue 3 geçişini kolaylaştıran altyapıyı zaten kuruyor.** `require()`/`import` karışıklığının temizlenmesi, `Script.php`'nin modernize edilmesi, per-entry Vite config'i — bunların hepsi Vue 3 geçişinde de aynen kullanılacak, tekrar yapılmayacak. Yani Vite'ı önce yapmak "kaybedilen" bir iş değil, ileride tekrar yapılmayacak temel bir yatırım.
3. **Vue 3 geçişi gibi büyük, riskli, çok sayıda component'i etkileyen bir işi hızlı geri bildirim döngüsüyle yapmak isteriz.** Element Plus + vee-validate v4 + vue2-editor değişimlerini test ederken her kayıtta saniyeler süren Webpack rebuild'i beklemek yerine, Vite'ın anlık HMR'ı ile çalışmak, bu büyük migrasyonu hem daha hızlı hem daha güvenli hale getirir. Yani asıl zor işi (Vue 3) daha iyi bir araçla yapmış olursunuz.
4. **Tek "maliyet":** Vite aşamasında Vue 2.7 için geçici olarak `@vitejs/plugin-vue2` (bakımı sınırlı, community plugin) kullanmanız gerekir; Vue 3'e geçince bunu resmi `@vitejs/plugin-vue` ile değiştirirsiniz. Bu değişim küçük ve iyi tanımlı bir adım (aynı SFC pipeline'ına farklı bir plugin takmak) — atılan iş değil.

Alternatif (Vue 3'ü önce yapmak) teorik olarak mümkün ama iki dezavantajı var: (a) en büyük, en riskli işi hâlâ yavaş Webpack tooling'iyle yapmış olursunuz, (b) Vite projesini yine de sonradan yapmanız gerekir — toplam işi azaltmaz, sadece riskli kısmı daha zor koşullarda öne alır.

**Üçüncü faz olarak SPA:** Bahsettiğiniz single-page app dönüşümü, bu iki migrasyondan sonra konuşulacak üçüncü ve en büyük adım olarak mantıklı bir sıra izliyor — hem Vite'ın dev deneyimi hem Vue 3'ün Composition API'si, gerçek bir SPA mimarisi kurmak için Vue 2 + Options API'den çok daha uygun bir temel sağlıyor.

## 7. Adım Adım Yol Haritası

1. **Ön koşul:** `webpack-to-vite-roadmap.md`'deki Vite geçişi tamamlanmış olmalı (Opsiyon B — gerçek `@vite()` + dev server).
2. **Keşif ve POC (1-2 hafta):** Element Plus + vee-validate v4'ü tek bir view'da (örn. `banking/accounts`) uçtan uca dene; component API farklarını, form validasyon akışını, CSS görünümünü doğrula.
3. **Merkezi altyapı (2-3 hafta):** `dashboard-plugin.js`, `globalComponents.js`, `globalDirectives.js`, `mixins/global.js` içindeki global API kalıplarını (`Vue.use`→`app.use`, `Vue.component`→`app.component`, `Vue.prototype`→`app.config.globalProperties`, async component factory→`defineAsyncComponent`) güncelle. `@vitejs/plugin-vue2`'yi `@vitejs/plugin-vue`'ya çevir.
4. **Bağımlılık geçişleri (2-3 hafta):** vee-validate v2→v4 (form akışı yeniden test), vue2-editor→alternatif kütüphane (component yeniden yazım), vuedraggable v2→v4, vue-router v3→v4 (install/wizard), v-money→v-money3, @fullcalendar/vue→vue3.
5. **Kademeli view göçü (4-8 hafta, paralelleştirilebilir):** Domain domain (Banking → Common → Settings → Auth → Portal → Wizard/Install), her domain ayrı PR, her sayfa için görsel + işlevsel regresyon testi (form submit, validasyon mesajları, Element Plus component'leri, drag&drop, editor, tarih/para input'ları).
6. **Ekosistem iletişimi (paralel yürütülmeli):** App/modül geliştiricilerine geçiş planı, zaman çizelgesi ve geçiş rehberi duyurusu — core'un ne zaman Vue 3'e geçeceği, eski Vue2 App'lerin ne kadar süre desteklenmeye devam edeceği netleşmeli.
7. **Regresyon ve kabul testi:** Backend PHPUnit paketi bu değişikliklerden etkilenmez (frontend-only), bu yüzden manuel/görsel test planı bu fazda kritik.

**Tahmini toplam süre:** ~3-5 ay (Vite fazı hariç, 1-2 geliştirici ile), ekosistem iletişimi ayrı bir zaman çizelgesi gerektirir.

## 8. Riskler / Dikkat Edilecekler

- vee-validate v4 ve Element Plus, v2/Element UI'nin birebir eşleniği değil — davranış farkları (özellikle validasyon zamanlaması, hata mesajı gösterimi) gizli regresyonlara yol açabilir; kapsamlı manuel test şart.
- Ekosistem parçalanma riski (Bölüm 5) — bu tamamen teknik değil, ürün/iş kararı gerektiriyor.
- vue2-editor'ün yerine geçecek kütüphane seçimi (TipTap/Quill vb.) ayrı bir değerlendirme gerektirir — mevcut içerik/format uyumluluğu kontrol edilmeli.
- Vue 3 geçişi sırasında Vite altyapısı zaten oturmuş olacağı için CI/CD tarafında ek bir değişiklik beklenmiyor (Bölüm 6, madde 2).

## 9. Öneri

Sıralama: **Vite → Vue 3 → (ileride) SPA.** Vue 3 fazına Element Plus + vee-validate v4 odaklı bir POC ile başlanmalı; ekosistem iletişimi teknik çalışmayla paralel, mümkün olduğunca erken başlatılmalı çünkü bu, App marketplace'i için en az teknik çalışma kadar önemli bir boyut.
