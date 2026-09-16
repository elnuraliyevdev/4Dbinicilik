# 🏇 4D Binicilik (Angora Riding Club) — Kapsamlı Manuel Test Senaryoları Kılavuzu

> **Hazırlayan:** Elnur ALIYEV (QA & Core Systems Engineer)  
> **Hedef Sistem:** 4D Binicilik B2B SaaS (`http://49.13.209.98:8088/`)  
> **Kapsam:** Üye, Antrenör ve Yönetici Akışları, Rezervasyon, Kredi Muhasebesi, Aile Havuzu, CSV Export, Güvenlik ve İzolasyon.

---

## 🔑 Test Hesapları ve Giriş Bilgileri

| Rol | Kullanıcı Adı / Telefon / E-posta | Şifre / PIN | Görev & Yetki Alanı |
|---|---|---|---|
| 👑 **Süper Yönetici** | `admin@4dbinicilik.local` | Parola: `O#^_*3b:/Y-j0MwV`<br>PIN: `0001` | Tüm kulüp yönetimi, üye kütüğü, seans onayları, fiyatlandırma, audit logları |
| 🎯 **Baş Antrenör (Emre)** | Seçim: `Emre Özmen` | PIN: `3184` | Günlük seans listesi, yoklama alma, öğrenci gelişim notu |
| 🎯 **Antrenör (İrem)** | Seçim: `İrem Öztürk` | PIN: `3406` | Seans listesi, yoklama |
| 👤 **Kulüp Üyesi (Elnur)** | `+90 555 123 45 67` | `MemberPass123!` | Seans rezervasyonu, bakiye görüntüleme, safari turu, geçmiş |
| 👤 **Aile Üyesi (Çocuk/Eş)** | Referans kodu veya telefon | Şifre | Ortak aile havuzundan ders hakkı kullanımı |

---

## 📋 MODÜL 1: KİMLİK DOĞRULAMA & ROL İZOLASYONU (AUTH & RBAC)

### TC-01: Yetkisiz Giriş Kalkanı (Lockdown)
* **Önkoşul:** Tarayıcıda oturum açık değil.
* **Adımlar:**
  1. `http://49.13.209.98:8088/` adresine gidin.
  2. Doğrudan konsoldan `switchView('admin')` veya `switchView('dashboard')` çalıştırmayı deneyin.
* **Beklenen Sonuç:**
  - Sayfa `body.auth-locked` sınıfı ile kilitli kalır, lüks login gateway ekranı tüm sayfayı kaplar.
  - İçerik veya navigasyon çubuğu sızmaz.

### TC-02: Geçersiz Kimlik Bilgisi ve Türkçe Hata Mesajı
* **Adımlar:**
  1. Üye girişinde telefonu boş bırakıp "Giriş Yap" butonuna basın.
  2. Yönetici girişinde PIN'i boş bırakıp giriş yapın.
* **Beklenen Sonuç:**
  - `"validation.required"` gibi ham kod çıkmaz.
  - *"Telefon alanı zorunludur."* veya *"PIN alanı zorunludur."* şeklinde Türkçe uyarı toast'u çıkar.

### TC-03: Antrenör Rol İzolasyonu (RBAC Guard)
* **Adımlar:**
  1. Antrenör sekmesinden `Emre Özmen` seçip `3184` PIN'i ile giriş yapın.
  2. Topbar'da görünen menüleri kontrol edin.
  3. Tarayıcı konsolunda `switchView('packages')` veya `switchView('dashboard')` çalıştırın.
* **Beklenen Sonuç:**
  - Antrenör yalnızca `Seans Takvimi` ve `Antrenör Masası` butonlarını görür.
  - Paketler, Bakiye ve Geçmiş menüleri **kesinlikle görünmez**.
  - Konsoldan zorlansa dahi *"⛔ Antrenör hesabı bu alana erişemez."* uyarısı çıkar ve görünüm engellenir.

---

## 📋 MODÜL 2: ÜYE REZERVASYONU & KREDİ MUHASEBESİ

### TC-04: Üye Dashboard ve Kredi Bakiyesi
* **Adımlar:**
  1. Üye `+90 555 123 45 67` / `MemberPass123!` ile giriş yapın.
  2. Dashboard'daki 4 sayaç kartını inceleyin (`Toplam Ders`, `Kullanılan`, `Kalan Ders`, `Bekleyen Ders`).
* **Beklenen Sonuç:**
  - Sayaçlar veritabanındaki gerçek bakiye değerlerini gösterir (`—` veya sahte 12/4/7/1 rakamları parlamaz).
  - Dijital kartta üye adı, ref kodu ve kalan ders sayısı listelenir.

### TC-05: Dinamik At Seçimi ile Seans Rezervasyonu
* **Adımlar:**
  1. `Seans Takvimi` sekmesine geçin.
  2. Müsait yeşil bir seansa tıklayın (örn: Bugün 16:00 veya Yarın 10:00).
  3. Açılan modalda "At Tercihi" dropdown'ını açın.
  4. Bir at seçip (örn: `Poyraz` veya `Asil`) rezervasyonu onaylayın.
* **Beklenen Sonuç:**
  - Dropdown `GET /member/horses` ile veritabanındaki gerçek atları listeler.
  - Rezervasyon başarıyla oluşturulur, üyenin kalan ders bakiyesinden 1 seans düşer.
  - `credit_transactions` tablosuna çift taraflı kayıt yazılır.

### TC-06: Geçmiş Saat / Kapalı Gün Kalkanı
* **Adımlar:**
  1. Takvimde Pazartesi gününü veya geçmiş bir saati seçin.
* **Beklenen Sonuç:**
  - Seans slotu gri / kapalı görünür, tıklanamaz.
  - Zorla istek atılsa bile backend `422 Unprocessable Entity` ("Geçmiş saate randevu alınamaz") hatası döndürür.

### TC-07: Rezervasyon İptali ve Kredi İadesi (Akıllı Kalkan)
* **Adımlar:**
  1. Dashboard'daki "Aktif Rezervasyonlarım" listesinde yer alan derse gidin.
  2. Derse 2 saatten fazla süre varken "İptal Et" butonuna basın.
* **Beklenen Sonuç:**
  - Rezervasyon iptal edilir ve 1 ders kredisi üyeye anında iade edilir.
  - Kalan ders sayacı 1 artar.

---

## 📋 MODÜL 3: AİLE GRUPLARI & ORTAK KREDİ HAVUZU

### TC-08: Aile Havuzundan Ortak Kredi Düşümü
* **Adımlar:**
  1. Bir aile grubuna bağlı üye hesabı ile giriş yapın.
  2. Seans rezervasyonu yapın.
* **Beklenen Sonuç:**
  - Kredi bireysel bakiyeden değil, ailenin ana kredi havuzundan düşer.
  - Aile grubu listesinde tüm aile üyeleri kalan ortak bakiyeyi senkronize görür.

### TC-09: Aile Ana Üyesinin Devri
* **Adımlar:**
  1. Admin panelinden `Aile Grupları` sekmesine girin.
  2. Ana üyeyi gruptan çıkarın veya değiştirin.
* **Beklenen Sonuç:**
  - Sistem otomatik olarak gruptaki bir sonraki yetişkin üyeyi ana üye atar, kredi havuzu yetimsiz kalmaz.

---

## 📋 MODÜL 4: ANTRENÖR SEANS & YOKLAMA MASASI

### TC-10: Günlük Seans Listesi ve Yoklama Alma
* **Adımlar:**
  1. Antrenör hesabı ile giriş yapın (`Emre Özmen` / `3184`).
  2. "Bugün Planlanan Seanslarım" listesini kontrol edin.
  3. Derse gelen öğrenci için "Geldi (Tamamlandı)" veya "Gelmedi (No-Show)" butonuna basın.
* **Beklenen Sonuç:**
  - Seans durumu anında güncellenir.
  - Eğer "Geldi" işaretlendiyse rezervasyon `completed` statüsüne geçer.

### TC-11: Öğrenci Gelişim Notu Kaydetme
* **Adımlar:**
  1. Sağdaki "Öğrenci Gelişim & Biniş Notu Ekle" kartına gelin.
  2. Açılır listeden bugün dersi olan öğrenciyi seçin.
  3. Biniş disiplinini seçip (örn: `Tırıs & Kenter Geçişleri`) gözlem notunu yazın ve kaydedin.
* **Beklenen Sonuç:**
  - Başarılı toast mesajı çıkar.
  - Öğrenci dropdown'ında sahte isimler değil, yalnızca o günün gerçek öğrencileri listelenir.

---

## 📋 MODÜL 5: ADMİN YÖNETİM MASASI & EXCEL DIŞA AKTARMA

### TC-12: Üye Kütüğü Arama ve Filtreleme
* **Adımlar:**
  1. Yönetici olarak giriş yapın (`admin@4dbinicilik.local`).
  2. `Üyeler` sekmesine tıklayın.
  3. Arama kutusuna bir üye adı veya telefon yazın.
* **Beklenen Sonuç:**
  - 166 kayıtlı üye anında filtrelenir.
  - Filtre butonları (`Aktif`, `Pasif`, `Bitti`) sorunsuz çalışır.

### TC-13: UTF-8 BOM Destekli Excel / CSV İndirme
* **Adımlar:**
  1. `Üyeler` sekmesindeki **"📥 Excel / CSV İndir"** butonuna tıklayın.
  2. İndirilen `.csv` dosyasını Microsoft Excel'de açın.
* **Beklenen Sonuç:**
  - Dosya `angora_binicilik_uyeler_*.csv` adıyla iner.
  - `ğ, ü, ş, ı, ö, ç, İ` gibi Türkçe karakterler bozulmadan sütunlara ayrılmış olarak Excel'de açılır.

### TC-14: Manuel Kredi Yükleme / Düzeltme
* **Adımlar:**
  1. Bir üyenin satırındaki `+1 Ders` veya `+4 Ders` butonuna tıklayın.
* **Beklenen Sonuç:**
  - Üyenin kalan ders bakiyesi anında güncellenir.
  - `credit_transactions` tablosuna `admin_adjustment` açıklamasıyla ledger kaydı eklenir.

---

## 📋 MODÜL 6: GÜVENLİK, AUDIT VE EŞZAMANLILIK (CONCURRENCY)

### TC-15: Eşzamanlı Rezervasyon Çakışma Testi (Race Condition)
* **Adımlar:**
  1. İki ayrı tarayıcı penceresinde iki farklı üye ile giriş yapın.
  2. Aynı antrenörün aynı saatteki tek boş slotuna aynı saniyede tıklayıp onaylayın.
* **Beklenen Sonuç:**
  - İlk tıklayan üyenin rezervasyonu onaylanır.
  - İkinci üyeye *"Bu seans az önce başka bir üye tarafından rezerve edildi."* uyarısı verilir; çift randevu açılmaz.

### TC-16: Güvenlik & Audit Günlüğü
* **Adımlar:**
  1. Admin sekmesinden `Güvenlik & Audit` sekmesine geçin.
  2. Başarılı/başarısız oturum denemelerini ve yetkisiz rol atlama girişimlerini inceleyin.
* **Beklenen Sonuç:**
  - Olay türü (`AUTH_SUCCESS`, `BRUTE_FORCE_BLOCKED` vb.), IP adresi, rol ve açıklama gerçek zamanlı listelenir.
