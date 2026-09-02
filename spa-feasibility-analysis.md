# Akaunting Core: SPA (Single Page App) Geçiş Fizibilite Analizi

*Hazırlanma tarihi: 2026-07-01 — kaynak: `akaunting` core repo. İlgili dokümanlar: `webpack-to-vite-roadmap.md`, `vue2-to-vue3-migration-roadmap.md`. Sıralama: bu doküman, önceki iki migrasyon tamamlandıktan sonra devreye giriyor.*

## 1. Sonuç (kısa)

**Tam, klasik client-routed bir SPA'ya geçiş düşük olasılıklı ve önerilmiyor** — çünkü Akaunting'in yetki ve genişletme (modül) modeli, sayfaların **sunucuda, her istekte yeniden render edilmesi** üzerine kurulu. Bunu koruyarak "SPA hissi" almak isteniyorsa, gerçekçi yol **Inertia.js tarzı bir yaklaşım** (Bölüm 5).

## 2. Mevcut Genişletme / Yetki Modelinin Analizi

Kod tabanını tarayarak çıkardığım somut bulgular:

**Blade `@stack()` tabanlı hook sistemi:** Core'un layout dosyalarında (`head.blade.php`, `scripts.blade.php` vb.) **~11 isimlendirilmiş hook noktası** var: `head_start`/`head_end`, `body_start`/`body_end`, `content_start`/`content_end`, `scripts_start`/`scripts_end`, `button_print_start`/`button_print_end`, `button_pdf_start`/`button_pdf_end`, `css`/`js`/`stylesheet`/`scripts`. Modüller kendi Laravel event listener'ları (her modülün `Providers/Event.php` stub'ı — `config/module.php`'de tanımlı) ve blade partial'ları üzerinden bu stack'lere `@push` ederek, **sayfa sunucuda render edilirken** mevcut sayfaya HTML/attribute/buton/widget enjekte ediyor. WordPress'in action-hook sistemine benzer, ama Blade stack + Laravel event tabanlı, **request-time çalışan bir sunucu tarafı genişletme modeli.**

**Form verisi de HTML kaynaklı:** `resources/assets/js/plugins/form.js`, form state'ini DOM'daki `data-field`/`data-item` attribute'larından türetiyor — form yapısı ayrıca JS'de tanımlanmıyor, **HTML tek doğruluk kaynağı.**

**Yetki (permission) modeli:** Sayfa içindeki koşullu HTML (`@can` gibi Laratrust directive'leri) sunucuda, istek anında, kullanıcının rolüne göre hesaplanıp render ediliyor — tarayıcıya zaten "izin verilen" HTML gidiyor.

## 3. Neden Tam SPA Bu Modelle Çatışıyor

Gerçek bir SPA'da ilk sayfa yüklendikten sonra sayfa geçişleri client-side router (Vue Router) üzerinden, API'den gelen JSON ile **tarayıcıda** render edilir — her geçişte Laravel'e taze bir istek gitmez. Bu durumda:

- **Hook sistemi kırılır:** `@stack()` tabanlı enjeksiyon sadece ilk tam sayfa yüklemesinde çalışır. Kullanıcı SPA içinde bir "sayfadan" diğerine geçtiğinde Blade hiç devreye girmez — modülün enjekte ettiği buton/widget/alan **kaybolur.** Düzeltmek için modülün tüm hook mekanizmasının client-side bir plugin registry'sine yeniden yazılması gerekir — bu, pazardaki **her App'i** etkileyen, tek başına Vue 3 geçişinden bile büyük bir ekosistem projesi.
- **Yetki mantığı ikiye bölünür:** Server-render anında hesaplanan `@can` kontrollerinin, SPA'da ya API üzerinden (her response'ta izin bilgisi) ya da ayrı bir client-side izin katmanında **tekrar** uygulanması gerekir. Mantığın iki yerde yaşaması senkron kalması gereken sürekli bir bakım yükü doğurur; gerçek güvenlik enforcement'ı API tarafında kalsa da, UI tutarlılığı için ciddi bir duplikasyon riski var.
- **Form yapısı yeniden tanımlanmalı:** `form.js`'in DOM-attribute-tabanlı veri toplama modeli, sunucudan gelen HTML olmadığı için SPA'da çalışmaz — form şemalarının ayrıca (JSON/JS tanımlı) yeniden yazılması gerekir.

## 4. Olasılık Değerlendirmesi

Tam, klasik client-routed SPA: **düşük olasılık.** Teknik olarak imkansız değil, ama tam olarak korumak istediğiniz şeyi (sunucunun hem yetki hem genişletme için tek doğruluk kaynağı olması) kırıyor. Bunu korumak isterseniz App marketplace'inin tamamı için hook sistemini client-side'a taşımanız gerekir — bu, Vue 3 geçişinden bile büyük, ekosistem çapında, App geliştiricilerini de kapsayan bir proje olur ve muhtemelen yıllar sürer.

## 5. Alternatif: Inertia.js Yaklaşımı

Tam SPA ile "hiçbir şey değişmesin" arasında gerçekçi bir orta yol: **Inertia.js.**

Inertia'da her sayfa geçişi hâlâ bir Laravel controller isteğidir — yani yetki kontrolü, event dispatch, hook mantığı **aynı request-time'da, sunucuda** çalışmaya devam eder. Fark, tam sayfa yenilemesi olmadan, XHR ile, controller'ın döndürdüğü veri bir Vue component'ine "prop" olarak geçilip client'ta render edilmesi. Böylece:

- SPA'nın akıcı geçiş hissini (sayfa yenilenmeden navigasyon) alırsınız.
- Sunucu, yetki ve genişletme için tek doğruluk kaynağı olmaya devam eder — her navigasyon hâlâ bir Laravel isteği.
- Modül hook sisteminin **bir miktar** uyarlanması gerekir (Blade `@stack()` yerine, controller'ın Inertia'ya prop olarak geçtiği bir "hook verisi" yapısı) — ama bu, tam client-routed SPA'ya göre çok daha küçük, kontrollü ve kademeli yapılabilir bir değişiklik.
- Vue 3 + Composition API ile doğal bir uyum var (Inertia'nın resmi Vue adaptörü Vue 3 hedefliyor) — bu da sıralamayı (Vite → Vue3 → Inertia değerlendirmesi) destekliyor.

Inertia yaklaşımının kendi maliyeti de var: `form.js`'in DOM-tabanlı veri toplama modelinin, Inertia'nın kendi form/state yönetimine (`useForm` gibi) taşınması, ve `@stack()` hook'larının prop-tabanlı bir sisteme evrilmesi — ama bu, mevcut mimarinin temel felsefesini (server = tek doğruluk kaynağı) korurken yapılabilir.

## 6. Sıralama İçindeki Yeri

1. **Webpack → Vite** (`webpack-to-vite-roadmap.md`) — build aracı, izole, düşük risk.
2. **Vue 2 → Vue 3** (`vue2-to-vue3-migration-roadmap.md`) — framework runtime, orta-yüksek risk, ekosistem iletişimi gerekli.
3. **SPA/Inertia değerlendirmesi (bu doküman)** — mimari karar noktası. Vue 3 tamamlandıktan sonra, gerçek ihtiyaç ("neden SPA istiyoruz — performans mı, UX mi, geliştirici deneyimi mi?") netleştirilip Inertia POC'u ile başlanması önerilir; tam client-routed SPA'nın kapsamı (hook sisteminin komple yeniden yazımı) ayrı, çok daha büyük bir karar olarak değerlendirilmeli.

## 7. Öneri

Tam SPA yerine **Inertia.js POC'u** ile başlanması — bu, "sunucu tek doğruluk kaynağı" prensibini korurken modern, akıcı bir navigasyon deneyimi sağlıyor ve mevcut hook/yetki mimarisiyle en az çatışan yol. Tam client-routed SPA, ancak App marketplace'i için client-side bir hook/plugin sistemi kurmaya değecek çok güçlü bir iş gerekçesi varsa (örn. offline-first ihtiyacı, çok büyük ölçekte performans sorunu) ayrıca değerlendirilmeli — bu senaryoda bile kademeli, App geliştiricileriyle koordineli bir geçiş şart.
