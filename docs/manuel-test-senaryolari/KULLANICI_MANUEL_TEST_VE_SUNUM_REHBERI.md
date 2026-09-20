# 🏇 4D Binicilik (Angora Riding Club) — Kullanıcı Manuel Test ve Canlı Sunum Rehberi

> **Hazırlayan:** Elnur ALIYEV (QA & Core Systems Engineer)  
> **Canlı Test URL:** `http://49.13.209.98:8088/`  
> **Amaç:** Yenal Hoca, yönetim veya müşteriye sunum yaparken ya da bizzat kendin test ederken adım adım takip edebileceğin, sıfır kafa karışıklığı yaratan pratik test akışı.

---

## 🔑 Hızlı Giriş Anahtarları (Yanında Bulunsun)

| Rol | Giriş Bilgisi | Şifre / PIN | Sunumda Gösterilebilecek Özellik |
|---|---|---|---|
| 👑 **Yönetici (Admin)** | `admin@4dbinicilik.local` | Parola: `O#^_*3b:/Y-j0MwV`<br>PIN: `0001` | 166 Üye Kütüğü, Excel/CSV İndir, Aile Havuzları, +1/+4 Manuel Kredi, Audit Log |
| 🎯 **Baş Antrenör (Emre)** | Seçim: `Emre Özmen` | PIN: `3184` | Hızlı Yoklama Alma (Geldi/Gelmedi), Öğrenciye Biniş Gelişim Notu Ekleme |
| 👤 **Kulüp Üyesi (Elnur)** | `+90 555 123 45 67` | `MemberPass123!` | Dijital Kulüp Kartı, Kalan Ders Sayaçları, Seans/At Seçimi, Safari Rezervasyonu |

---

## 🚀 5 DAKİKALIK ADIM ADIM MANUEL TEST & SUNUM AKIŞI

### 📱 1. AŞAMA: ÜYE DENEYİMİ (BİNİCİ / VELİ GÖZÜNDEN)
1. `http://49.13.209.98:8088/` adresine gir.
2. Açılan lüks karşılama ekranında **"Kulüp Üyesi"** sekmesine tıkla.
3. **Telefon:** `+90 555 123 45 67` / **Şifre:** `MemberPass123!` yazıp giriş yap.
4. **Neleri Kontrol Et / Göster:**
   - 💳 **Dijital Kulüp Kartı:** Üye adı (`Elnur Aliyev`), Referans Kodu ve Aktif Paket görünüyor mu?
   - 📊 **4 Bakiye Sayacı:** Toplam Ders, Kullanılan, Kalan Ders, Bekleyen Ders sayaçlarının canlı çalıştığını gör.
   - 📅 **Seans Takvimi (Yukarıdaki menüden):** Haftalık yeşil slotlara tıkla, açılan modalda at seçimi yap (`Poyraz`, `Asil` vb.).
   - 🌲 **Safari Turu:** Orman Safari turlarını ve kontenjanları incele.
5. Sağ üstteki profil ikonuna veya çıkış butonuna tıklayarak çıkış yap.

---

### 🏇 2. AŞAMA: ANTRENÖR DENEYİMİ (SAHA & YOKLAMA GÖZÜNDEN)
1. Giriş ekranında **"Antrenör"** sekmesine tıkla.
2. Açılır listeden **"Emre Özmen — Baş Antrenör"** seç.
3. **PIN:** `3184` yazıp giriş yap.
4. **Neleri Kontrol Et / Göster:**
   - 🛡️ **Rol İzolasyonu:** Antrenörün paket satın alma, kredi yükleme gibi yetkisiz menüleri görmediğini; doğrudan **"Antrenör Masası"**na yönlendiğini gör.
   - 📋 **Yoklama Alma:** Bugünkü seanstaki öğrenci için **"Geldi (Tamamlandı)"** veya **"Gelmedi"** butonlarına bas.
   - 📝 **Gelişim Notu:** Sağdaki alandan öğrenci seçip *"Duruş dengesi çok iyi, tırıs geçişleri başarılı"* şeklinde not gir ve kaydet.
5. Sağ üstten çıkış yap.

---

### 👑 3. AŞAMA: YÖNETİCİ DENEYİMİ (KULÜP SAHİBİ & ADMİN GÖZÜNDEN)
1. Giriş ekranında **"Yönetici"** sekmesine tıkla.
2. **E-posta:** `admin@4dbinicilik.local` | **Şifre:** `O#^_*3b:/Y-j0MwV` | **PIN:** `0001` yazıp giriş yap.
3. Topbar'daki **"👑 Yönetim Paneli"** butonuna tıkla.
4. **Neleri Kontrol Et / Göster:**
   - 👥 **Üyeler Sekmesi:** 166 kayıtlı üye anında listeleniyor mu? Arama kutusuna bir isim (örn. *Aydın* veya *Beyza*) yazıp anında filtrelendiğini göster.
   - 📥 **"Excel / CSV İndir" Butonu:** Butona bas, inen CSV dosyasında Türkçe karakterlerin (`ğ, ü, ş, ı, ö, ç, İ`) bozulmadan sütun sütun geldiğini göster.
   - ➕ **Manuel Kredi Yükleme:** Bir üyenin yanındaki `+1 Ders` veya `+4 Ders` butonuna bas; bakiyenin anında güncellendiğini ve ledger kaydı oluştuğunu göster.
   - 👨‍👩‍👧‍👦 **Aile Grupları:** Ortak kredi havuzuna sahip aileleri göster.
   - 🔒 **Güvenlik & Audit Logları:** Sistemde kim ne zaman giriş yapmış, hangi IP'den hangi işlemi yapmış şeffaf log günlüğünü göster.

---

## 🏛️ SİSTEM ÇALIŞMA MİMARİSİ VE FAZLALIK / EKSİK ANALİZİ

### 1. Eski Sistem vs Yeni Sistem Karşılaştırması:
* **Eski Sistemde Ne Vardı?** 
  * Sadece 38 ham üye adı vardı, şifreli kimlik doğrulama yoktu.
  * Antrenörlerin sahada kullanabileceği hızlı PIN'li bir masa yoktu.
  * Ailelerin tek havuzdan ortak ders harcaması (Family Pool) bulunmuyordu.
  * Excel dışa aktarımı ve güvenlik denetim kayıtları (Audit Logs) yoktu.
* **Yeni Sistemde Ne Oldu?**
  * 3 katmanlı güvenlikli rol kalkanı (Admin / Trainer / Member).
  * 166 gerçek üye kütüğü ve çift taraflı bakiye muhasebe defteri.
  * Tamamen canlıya hazır, tek tıkla çalıştırılabilen modern SPA mimarisi.

### 2. Sunum Açısından Fazlalık / Kafa Karıştırıcı Unsur Var mı?
* ❌ **Gereksiz/Şişirilmiş Modül:** Yok. Sistem tamamen kulüp operasyonunun ihtiyaç duyduğu çekirdek akışlara (Üye Kayıt, Randevu, Yoklama, Bakiye, Raporlama) odaklanmıştır.
* ❌ **Yetki Çakışması:** Yok. Antrenör üyenin parasal işlerine karışamaz, üye başkasının seansını göremez, admin her şeyi denetler.
* ✅ **Sunum Konforu:** SPA yapısı sayesinde sayfa yenilenmeden sekmeler arası geçiş yapılır, takılma veya gecikme yaşanmaz.
