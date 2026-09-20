const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const ROOT_DIR = '/Users/aliyev/Documents/UNI/Staj';
const APP_DIR = '/Users/aliyev/Documents/UNI/Staj/angora-riding-club-app';
const EVIDENCE_DIR = path.join(APP_DIR, 'docs/evidence_4d_binicilik');
const ARTIFACTS_DIR = '/Users/aliyev/.gemini/antigravity-ide/brain/7aa30d84-4b82-4554-846e-ea58007aedb8';

function getBase64Image(filename) {
  const filePath = path.join(EVIDENCE_DIR, filename);
  if (fs.existsSync(filePath)) {
    const data = fs.readFileSync(filePath).toString('base64');
    return `data:image/png;base64,${data}`;
  }
  return '';
}

async function generateUltraLuxuryDocs() {
  console.log('💎 Starting Generation of Ultra-Luxury Corporate Pitch Deck & Master User Guide...');
  const browser = await chromium.launch({ headless: true });

  const imgAuth = getBase64Image('tc01_auth_lockdown.png');
  const imgTrainer = getBase64Image('tc03_trainer_portal.png');
  const imgMember = getBase64Image('tc04_member_dashboard.png');
  const imgCalendar = getBase64Image('tc05_calendar_schedule.png');
  const imgAdmin = getBase64Image('tc12_admin_dashboard.png');
  const imgMembersTable = getBase64Image('tc13_admin_members_csv.png');
  const imgFamilies = getBase64Image('tc08_admin_families.png');
  const imgAudit = getBase64Image('tc16_admin_audit_logs.png');

  // ==========================================================================================
  // DOCUMENT 1: ULTRA-LUXURY EXECUTIVE PRODUCT CATALOG (4 PAGES)
  // ==========================================================================================
  console.log('🎨 [1/2] Rendering Ultra-Luxury Product Catalog & Pitch Deck (4 Pages)...');
  const catalogHtml = `
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>4D Binicilik — Ultra Lüks Kurumsal Tanıtım Kataloğu</title>
<style>
  @import url("https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap");
  
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: "Plus Jakarta Sans", sans-serif;
    color: #0f172a;
    background: #ffffff;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
  .page {
    width: 210mm;
    height: 297mm;
    padding: 26px 32px;
    position: relative;
    page-break-after: always;
    background: #ffffff;
    overflow: hidden;
  }
  
  /* COVER PAGE (DARK LUXURY GOLD) */
  .page-cover {
    background: radial-gradient(circle at 80% 20%, #1e293b 0%, #090d16 100%);
    color: #ffffff;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 45px 42px;
  }
  .cover-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid rgba(217, 119, 6, 0.3);
    padding-bottom: 16px;
  }
  .cover-brand {
    font-family: "Cinzel", serif;
    font-size: 20px;
    font-weight: 800;
    letter-spacing: 0.15em;
    color: #f59e0b;
    text-transform: uppercase;
  }
  .cover-badge {
    border: 1px solid #f59e0b;
    background: rgba(245, 158, 11, 0.1);
    color: #fbbf24;
    font-size: 8.5px;
    font-weight: 800;
    padding: 5px 14px;
    border-radius: 20px;
    letter-spacing: 0.1em;
    text-transform: uppercase;
  }
  .cover-center {
    margin-top: 40px;
    margin-bottom: 30px;
  }
  .cover-tagline {
    font-size: 11px;
    font-weight: 700;
    color: #f59e0b;
    letter-spacing: 0.25em;
    text-transform: uppercase;
    margin-bottom: 12px;
  }
  .cover-title {
    font-family: "Cinzel", serif;
    font-size: 34px;
    font-weight: 900;
    line-height: 1.15;
    color: #ffffff;
    letter-spacing: -0.01em;
    margin-bottom: 16px;
  }
  .cover-title span {
    background: linear-gradient(135deg, #fcd34d 0%, #d97706 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }
  .cover-desc {
    font-size: 11px;
    color: #94a3b8;
    line-height: 1.6;
    max-width: 520px;
  }
  .cover-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    background: rgba(15, 23, 42, 0.7);
    border: 1px solid rgba(245, 158, 11, 0.25);
    border-radius: 10px;
    padding: 14px 18px;
    backdrop-filter: blur(10px);
  }
  .c-stat-num {
    font-size: 18px;
    font-weight: 900;
    color: #fbbf24;
    font-family: "Cinzel", serif;
  }
  .c-stat-lbl {
    font-size: 7.5px;
    font-weight: 700;
    color: #cbd5e1;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-top: 2px;
  }
  .cover-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding-top: 14px;
    font-size: 8.5px;
    color: #64748b;
  }
  .cover-bottom strong { color: #f59e0b; }

  /* INNER PAGES HEADER & STRUCTURE */
  .inner-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    border-bottom: 2px solid #b45309;
    padding-bottom: 8px;
    margin-bottom: 14px;
  }
  .inner-header h2 {
    font-family: "Cinzel", serif;
    font-size: 17px;
    font-weight: 800;
    color: #92400e;
    letter-spacing: -0.01em;
  }
  .inner-header .sub {
    font-size: 9px;
    font-weight: 700;
    color: #d97706;
  }
  .inner-header .page-num {
    font-size: 8.5px;
    font-weight: 800;
    color: #64748b;
  }
  
  .section-h {
    font-size: 10.5px;
    font-weight: 800;
    color: #92400e;
    border-left: 3.5px solid #d97706;
    padding-left: 6px;
    margin-top: 12px;
    margin-bottom: 7px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
  }

  .grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
  }
  .grid-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 10px;
  }

  .glass-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 10px 12px;
    position: relative;
  }
  .glass-card.gold-border {
    background: #fffdf7;
    border: 1px solid #fcd34d;
    box-shadow: 0 2px 8px rgba(217, 119, 6, 0.06);
  }
  .glass-card h3 {
    font-size: 10px;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 5px;
  }
  .glass-card.gold-border h3 { color: #92400e; }
  .glass-card p {
    font-size: 8px;
    color: #475569;
    line-height: 1.4;
  }

  .ui-showcase-box {
    background: #0f172a;
    border-radius: 8px;
    padding: 7px;
    border: 1px solid #334155;
    margin-top: 6px;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.15);
  }
  .ui-showcase-box img {
    width: 100%;
    height: auto;
    border-radius: 5px;
    display: block;
  }
  .ui-caption {
    font-size: 7.2px;
    color: #94a3b8;
    text-align: center;
    margin-top: 4px;
    font-weight: 600;
  }

  .feature-ul {
    list-style: none;
    margin-top: 5px;
  }
  .feature-ul li {
    font-size: 8px;
    color: #334155;
    margin-bottom: 4px;
    display: flex;
    align-items: flex-start;
    gap: 4px;
    line-height: 1.35;
  }
  .feature-ul li strong { color: #0f172a; }
  .f-check { color: #d97706; font-weight: 900; }

  .quote-box {
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    border-left: 4px solid #d97706;
    border-radius: 0 8px 8px 0;
    padding: 8px 12px;
    margin-bottom: 12px;
    font-size: 8.5px;
    color: #78350f;
    line-height: 1.4;
  }
  .quote-box strong { color: #92400e; }

  .footer-bar {
    position: absolute;
    bottom: 16px;
    left: 32px;
    right: 32px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid #e2e8f0;
    padding-top: 6px;
    font-size: 7.5px;
    color: #94a3b8;
  }
  
  .back-cover {
    background: #090d16;
    color: #ffffff;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 40px 42px;
  }
  .back-cta-box {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border: 1px solid rgba(245, 158, 11, 0.35);
    border-radius: 12px;
    padding: 20px 24px;
    text-align: center;
    margin: 40px 0;
  }
  .back-cta-box h3 {
    font-family: "Cinzel", serif;
    font-size: 18px;
    color: #fbbf24;
    margin-bottom: 8px;
  }
  .back-cta-box p {
    font-size: 9.5px;
    color: #cbd5e1;
    line-height: 1.5;
    max-width: 480px;
    margin: 0 auto 16px auto;
  }
  .btn-gold-large {
    display: inline-block;
    background: linear-gradient(135deg, #f59e0b 0%, #b45309 100%);
    color: #ffffff;
    font-weight: 800;
    font-size: 10px;
    padding: 8px 22px;
    border-radius: 6px;
    text-decoration: none;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    box-shadow: 0 4px 14px rgba(217, 119, 6, 0.4);
  }
</style>
</head>
<body>

  <!-- SAYFA 1: ULTRA LÜKS KAPAK (COVER) -->
  <div class="page page-cover">
    <div class="cover-top">
      <div class="cover-brand">4D BİNİCİLİK</div>
      <div class="cover-badge">Executive SaaS Platform 2026</div>
    </div>

    <div class="cover-center">
      <div class="cover-tagline">ANGORA RIDING CLUB & B2B ENTERPRISE</div>
      <h1 class="cover-title">
        Prestijli Binicilik Kulüpleri İçin<br>
        <span>Kusursuz Dijital Yönetim</span>
      </h1>
      <p class="cover-desc">
        Randevu karmaşasına, kaybolan ders haklarına, manuel defter risklerine ve at yorgunluğuna son veren; yönetici, eğitmen ve biniciyi tek ekosistemde buluşturan yeni nesil kulüp işletim platformu.
      </p>
    </div>

    <div class="cover-stats">
      <div class="c-stat">
        <div class="c-stat-num">%100</div>
        <div class="c-stat-lbl">Kredi Mutabakatı</div>
      </div>
      <div class="c-stat">
        <div class="c-stat-num">0 Sn</div>
        <div class="c-stat-lbl">Saha Yoklama Hızı</div>
      </div>
      <div class="c-stat">
        <div class="c-stat-num">166+</div>
        <div class="c-stat-lbl">Aktif Üye Portföyü</div>
      </div>
      <div class="c-stat">
        <div class="c-stat-num">KVKK</div>
        <div class="c-stat-lbl">Banka Seviyesi Güvenlik</div>
      </div>
    </div>

    <div class="cover-bottom">
      <div><strong>Geliştirici:</strong> 4Dimension Bilişim Teknolojileri A.Ş.</div>
      <div><strong>Tesis:</strong> Angora Riding Club • <strong>Erişim:</strong> http://49.13.209.98:8088</div>
    </div>
  </div>

  <!-- SAYFA 2: YÖNETİCİ KONSOLU & FİNANSAL GÜÇ -->
  <div class="page">
    <div class="inner-header">
      <div>
        <h2>👑 MODÜL 1: KULÜP YÖNETİMİ & FİNANSAL LEDGER</h2>
        <div class="sub">360 Derece Üye Takibi, Çift Taraflı Kredi Defteri ve Excel İhracı</div>
      </div>
      <div class="page-num">Sayfa 02 / 04</div>
    </div>

    <div class="quote-box">
      <strong>Kulüp Sahibi ve Yöneticisi İçin Tam Hakimiyet:</strong> 4D Binicilik, kulübün tüm üye kütüğünü, paket satışlarını, borç-alacak dengesini ve at kapasitelerini gerçek zamanlı verilerle tek bir yönetim kokpitinde toplar.
    </div>

    <div class="grid-2">
      <div>
        <div class="glass-card gold-border">
          <h3>👥 166+ Üye Kütüğü & Canlı Arama</h3>
          <p>Tüm binici ve veli bilgilerini tek ekranda listeleyin. İsim, telefon veya referans koduyla milisaniyeler içinde arama yapın, aktif/pasif filtreleri uygulayın.</p>
          <ul class="feature-ul">
            <li><span class="f-check">✓</span> <strong>Tek Tıkla +1 / +4 Kredi:</strong> Nakit veya telafi derslerini anında üyenin bakiyesine yükleyin.</li>
            <li><span class="f-check">✓</span> <strong>UTF-8 Excel / CSV Dışa Aktarım:</strong> Türkçe karakterleri bozulmadan resmi muhasebe listelerini anında indirin.</li>
            <li><span class="f-check">✓</span> <strong>Dijital Kart & Ref Kodu:</strong> Her üyeye özel dijital referans anahtarı.</li>
          </ul>
        </div>

        <div class="ui-showcase-box">
          <img src="${imgMembersTable}" alt="Üye Kütüğü ve Excel İndirme Ekranı">
          <div class="ui-caption">Canlı Ekran: 166 Üye Kütüğü, Arama Filtreleri ve Excel/CSV Dışa Aktarma Masası</div>
        </div>
      </div>

      <div>
        <div class="glass-card gold-border">
          <h3>👨‍👩‍👧‍👦 Aile Grupları & Ortak Kredi Havuzu</h3>
          <p>Binicilik kulüplerine özel geliştirilen <strong>Family Credit Pool</strong> motoru sayesinde anne, baba ve çocuklar tek bir ortak ders paketinden ders hakkı kullanır.</p>
          <ul class="feature-ul">
            <li><span class="f-check">✓</span> <strong>Yetimsiz Havuz Koruması:</strong> Ana üye ayrılsa bile haklar korunur.</li>
            <li><span class="f-check">✓</span> <strong>Eşzamanlı Bakiye Düşümü:</strong> Aile fertleri rezervasyon yaptığında ortak bakiye anında senkronize olur.</li>
          </ul>
        </div>

        <div class="ui-showcase-box">
          <img src="${imgAdmin}" alt="Admin Yönetim Kokpiti">
          <div class="ui-caption">Canlı Ekran: Genel Yönetim Masası, Gelir/Gider ve Seans İstatistikleri</div>
        </div>
      </div>
    </div>

    <div class="footer-bar">
      <span>4D Binicilik — Kurumsal Ürün Kataloğu</span>
      <span>4Dimension Bilişim Teknolojileri • 0850 888 48 80</span>
    </div>
  </div>

  <!-- SAYFA 3: SAHA EĞİTMEN MASASI & ÜYE DENEYİMİ -->
  <div class="page">
    <div class="inner-header">
      <div>
        <h2>🏇 MODÜL 2: SAHA ANTRENÖR MASASI & ÜYE MOBİL PORTALI</h2>
        <div class="sub">Hızlı PIN ile Saha Operasyonu, At Tercihi ve Dijital Kulüp Kartı</div>
      </div>
      <div class="page-num">Sayfa 03 / 04</div>
    </div>

    <div class="grid-2">
      <!-- SAĞ SOL BÖLÜM -->
      <div>
        <div class="glass-card gold-border">
          <h3>🎯 Antrenör Masası (Saha & Tablet)</h3>
          <p>Eğitmenler sahada at üzerindeyken veya manej kenarındayken karmaşık menülerle uğraşmaz. 4 haneli PIN koduyla 3 saniyede bağlanır.</p>
          <ul class="feature-ul">
            <li><span class="f-check">✓</span> <strong>Tek Dokunuşla Yoklama:</strong> "Geldi" veya "No-Show" ile seansı anında tamamlar.</li>
            <li><span class="f-check">✓</span> <strong>Gelişim & Biniş Notu:</strong> Öğrencinin biniş tekniğini (Lonj, Tırıs, Kenter, Engel) not düşer.</li>
            <li><span class="f-check">✓</span> <strong>RBAC İzolasyonu:</strong> Eğitmen yalnızca kendi seanslarını görür; finansal alanlara erişemez.</li>
          </ul>
        </div>

        <div class="ui-showcase-box">
          <img src="${imgTrainer}" alt="Antrenör Masası ve Yoklama Ekranı">
          <div class="ui-caption">Canlı Ekran: PIN ile Açılan Antrenör Masası & Gelişim Notu Modülü</div>
        </div>
      </div>

      <div>
        <div class="glass-card gold-border">
          <h3>👤 Üye Portalı (Lüks Binici Deneyimi)</h3>
          <p>Biniciler ve veliler 7/24 randevu alabilir, kalan ders sayaçlarını takip edebilir ve orman safari turlarına katılabilir.</p>
          <ul class="feature-ul">
            <li><span class="f-check">✓</span> <strong>Dinamik At Seçimi:</strong> Takvimden seansı seçerken tercih ettiği atı (Poyraz, Asil vb.) belirler.</li>
            <li><span class="f-check">✓</span> <strong>Canlı Sayaçlar:</strong> Toplam Ders, Kullanılan ve Kalan bakiye net gösterilir.</li>
            <li><span class="f-check">✓</span> <strong>Akıllı İptal Kalkanı:</strong> Derse 2 saat kala iptal edilirse kredi anında iade edilir.</li>
          </ul>
        </div>

        <div class="ui-showcase-box">
          <img src="${imgMember}" alt="Üye Dashboard Ekranı">
          <div class="ui-caption">Canlı Ekran: Dijital Kulüp Kartı, Kredi Sayaçları ve Aktif Rezervasyonlar</div>
        </div>
      </div>
    </div>

    <div class="footer-bar">
      <span>4D Binicilik — Kurumsal Ürün Kataloğu</span>
      <span>4Dimension Bilişim Teknolojileri • 0850 888 48 80</span>
    </div>
  </div>

  <!-- SAYFA 4: GÜVENLİK, KURUMSAL DEĞER & İLETİŞİM -->
  <div class="page back-cover">
    <div class="cover-top" style="border-bottom: 1px solid rgba(255,255,255,0.15);">
      <div class="cover-brand" style="font-size: 18px;">4D BİNİCİLİK</div>
      <div class="cover-badge" style="border-color: #f59e0b; color: #fbbf24;">GÜVENLİK & YATIRIM DEĞERİ</div>
    </div>

    <div class="grid-2" style="margin-top: 20px;">
      <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 12px;">
        <h4 style="color: #fbbf24; font-size: 11px; margin-bottom: 6px; font-weight: 800;">🛡️ Atomic Concurrency Lock</h4>
        <p style="font-size: 8px; color: #cbd5e1; line-height: 1.4;">Aynı seansa veya aynı ata aynı saniyede iki binicinin tıklamasını engelleyen veritabanı atomik kilit mekanizması sayesinde mükerrer randevu riski %0'a indirilir.</p>
      </div>

      <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 12px;">
        <h4 style="color: #fbbf24; font-size: 11px; margin-bottom: 6px; font-weight: 800;">📜 Şeffaf Audit Günlüğü</h4>
        <p style="font-size: 8px; color: #cbd5e1; line-height: 1.4;">Tüm bakiye güncellemeleri, rol değişiklikleri, giriş-çıkış denemeleri IP adresi ve zaman damgasıyla kriptografik hassasiyette loglanır.</p>
      </div>
    </div>

    <div class="back-cta-box">
      <h3>Kulübünüzü Geleceğin Yönetim Standardına Taşıyın</h3>
      <p>Manuel süreçleri geride bırakın, müşteri memnuniyetini zirveye çıkarın ve tüm tesisinizi tek bir güvenli ekrandan yönetin.</p>
      <a href="http://49.13.209.98:8088" class="btn-gold-large">CANLI DEMOYA HEMEN BAĞLANIN →</a>
    </div>

    <div style="border-top: 1px solid rgba(255,255,255,0.15); padding-top: 14px; display: flex; justify-content: space-between; align-items: center; font-size: 8px; color: #94a3b8;">
      <div>
        <strong style="color: #fbbf24; font-size: 9px;">4Dimension Bilişim Teknolojileri A.Ş.</strong><br>
        İvedik OSB Mah. 2224. Cad No:1/116 Teknopark Ankara B Blok Yenimahalle / ANKARA
      </div>
      <div style="text-align: right;">
        <strong>Telefon:</strong> 0850 888 48 80 / +90 312 210 14 50<br>
        <strong>E-posta:</strong> info@4dimension.com.tr • <strong>Web:</strong> www.4dimension.com.tr
      </div>
    </div>
  </div>

</body>
</html>
`;

  const catalogPage = await browser.newPage();
  await catalogPage.setContent(catalogHtml, { waitUntil: 'networkidle' });

  const catalogPdfPath = path.join(ROOT_DIR, '4D_Binicilik_Ultra_Luks_Tanitim_Katalogu.pdf');
  const catalogAppPath = path.join(APP_DIR, 'docs/manuel-test-senaryolari/4D_Binicilik_Ultra_Luks_Tanitim_Katalogu.pdf');
  const catalogArtifactPath = path.join(ARTIFACTS_DIR, '4D_Binicilik_Ultra_Luks_Tanitim_Katalogu.pdf');

  await catalogPage.pdf({
    path: catalogPdfPath,
    format: 'A4',
    printBackground: true,
    margin: { top: '0mm', right: '0mm', bottom: '0mm', left: '0mm' }
  });
  fs.copyFileSync(catalogPdfPath, catalogAppPath);
  fs.copyFileSync(catalogPdfPath, catalogArtifactPath);
  await catalogPage.close();
  console.log(`✅ [1/2] Ultra-Lüks Ürün Kataloğu PDF oluşturuldu: ${catalogPdfPath}`);


  // ==========================================================================================
  // DOCUMENT 2: MASTER USER & QUICK START GUIDE (3 PAGES WITH SCREENSHOTS & WORKFLOWS)
  // ==========================================================================================
  console.log('\n📖 [2/2] Rendering Master Executive User Guide (3 Pages)...');
  const masterManualHtml = `
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>4D Binicilik — Master Kullanım Kılavuzu</title>
<style>
  @import url("https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap");
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: "Plus Jakarta Sans", sans-serif;
    color: #0f172a;
    background: #ffffff;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
    font-size: 8.5px;
  }
  .page {
    width: 210mm;
    height: 297mm;
    padding: 24px 30px;
    position: relative;
    page-break-after: always;
    background: #ffffff;
    overflow: hidden;
  }
  .m-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    border-bottom: 2px solid #b45309;
    padding-bottom: 8px;
    margin-bottom: 10px;
  }
  .m-header h1 {
    font-family: "Cinzel", serif;
    font-size: 16px;
    font-weight: 800;
    color: #92400e;
  }
  .m-header .sub {
    font-size: 9px;
    font-weight: 700;
    color: #d97706;
  }
  .m-header .badge {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #15803d;
    font-size: 7.5px;
    font-weight: 800;
    padding: 3px 8px;
    border-radius: 12px;
  }

  .step-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 8px 10px;
    margin-bottom: 6px;
  }
  .step-card.gold {
    background: #fffdf7;
    border: 1px solid #fde68a;
  }
  .step-card-header {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 3px;
  }
  .step-num {
    background: #d97706;
    color: #ffffff;
    font-size: 7.5px;
    font-weight: 900;
    padding: 2px 6px;
    border-radius: 4px;
  }
  .step-card-title {
    font-size: 9px;
    font-weight: 800;
    color: #0f172a;
  }
  .step-card p {
    font-size: 7.8px;
    color: #475569;
    line-height: 1.35;
  }

  .grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
  }
  .img-box {
    background: #0f172a;
    border-radius: 6px;
    padding: 5px;
    border: 1px solid #334155;
    margin-top: 4px;
  }
  .img-box img {
    width: 100%;
    height: auto;
    border-radius: 4px;
    display: block;
  }
  .caption {
    font-size: 7px;
    color: #94a3b8;
    text-align: center;
    margin-top: 3px;
  }
  
  table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 8px;
    font-size: 7.8px;
  }
  th {
    background: #f1f5f9;
    color: #334155;
    font-weight: 700;
    text-align: left;
    padding: 4px 6px;
    border: 1px solid #cbd5e1;
  }
  td {
    padding: 3.5px 6px;
    border: 1px solid #e2e8f0;
    color: #1e293b;
  }
  tr:nth-child(even) { background: #f8fafc; }

  .footer-bar {
    position: absolute;
    bottom: 12px;
    left: 30px;
    right: 30px;
    display: flex;
    justify-content: space-between;
    font-size: 7.2px;
    color: #94a3b8;
    border-top: 1px solid #e2e8f0;
    padding-top: 3px;
  }
</style>
</head>
<body>

  <!-- SAYFA 1: GİRİŞ VE YÖNETİCİ REHBERİ -->
  <div class="page">
    <div class="m-header">
      <div>
        <h1>📖 4D BİNİCİLİK MASTER KULLANIM KILAVUZU</h1>
        <div class="sub">Bölüm 1: Güvenli Giriş Kapısı & Kulüp Yöneticisi Rehberi</div>
      </div>
      <div class="badge">Rehber Sayfa 1 / 3</div>
    </div>

    <p style="font-size: 8px; color: #475569; margin-bottom: 6px;">Web tarayıcınızdan <strong>http://49.13.209.98:8088</strong> adresine girdiğinizde ekranınızda 3 sekmeli güvenli giriş kapısı açılır:</p>
    
    <table>
      <thead>
        <tr>
          <th>Rol</th>
          <th>Giriş Bilgisi</th>
          <th>Şifre / PIN</th>
          <th>Yetki ve Erişim Alanı</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><strong>👑 Kulüp Yöneticisi</strong></td>
          <td><code>admin@4dbinicilik.local</code></td>
          <td>Parola: <code>O#^_*3b:/Y-j0MwV</code> • PIN: <code>0001</code></td>
          <td>166 Üye Kütüğü, Excel/CSV İndir, Kredi Yükleme, Aile Havuzları, Audit</td>
        </tr>
        <tr>
          <td><strong>🎯 Baş Antrenör</strong></td>
          <td>Seçim: <code>Emre Özmen</code></td>
          <td>PIN: <code>3184</code></td>
          <td>Saha Yoklama Masası (Geldi/Yok), Öğrenci Gelişim Notu Ekleme</td>
        </tr>
        <tr>
          <td><strong>👤 Kulüp Üyesi</strong></td>
          <td><code>+90 555 123 45 67</code></td>
          <td><code>MemberPass123!</code></td>
          <td>Dijital Kart, Seans Takvimi, At Tercihi, Bakiye Sayaçları, Safari Turu</td>
        </tr>
      </tbody>
    </table>

    <div class="grid-2">
      <div>
        <div class="step-card gold">
          <div class="step-card-header">
            <span class="step-num">ADIM 1</span>
            <span class="step-card-title">Üye Kütüğünü Yönetme & Arama</span>
          </div>
          <p>Yönetim konsolunda <strong>"Üyeler"</strong> sekmesine geçin. 166 kayıtlı üye listelenir. Arama kutusuna üyenin adını veya telefonunu yazarak anında bulun.</p>
        </div>

        <div class="step-card">
          <div class="step-card-header">
            <span class="step-num">ADIM 2</span>
            <span class="step-card-title">Manuel +1 / +4 Kredi Yükleme</span>
          </div>
          <p>Nakit ödeme yapan veya telafi dersi hak eden üyelerin satırındaki <strong>"+1 Ders"</strong> veya <strong>"+4 Ders"</strong> butonuna basınız. Bakiye anında güncellenir ve ledger'a işlenir.</p>
        </div>

        <div class="step-card">
          <div class="step-card-header">
            <span class="step-num">ADIM 3</span>
            <span class="step-card-title">UTF-8 Excel / CSV Dışa Aktarma</span>
          </div>
          <p>Muhasebe ve resmi listeler için <strong>"📥 Excel / CSV İndir"</strong> butonuna tıklayın. Türkçe karakterler bozulmadan Excel tablosu olarak açılır.</p>
        </div>
      </div>

      <div>
        <div class="img-box">
          <img src="${imgAdmin}" alt="Admin Paneli">
          <div class="caption">Yönetici Paneli: Üyeler, Paketler, Aile Havuzları ve Finans İstatistikleri</div>
        </div>
        <div class="img-box" style="margin-top: 6px;">
          <img src="${imgAuth}" alt="Giriş Kapısı">
          <div class="caption">Güvenli Giriş Kapısı (Yönetici, Antrenör ve Üye İzolasyonu)</div>
        </div>
      </div>
    </div>

    <div class="footer-bar">
      <span>4D Binicilik — Master Kullanım Kılavuzu</span>
      <span>4Dimension Bilişim • 0850 888 48 80</span>
    </div>
  </div>

  <!-- SAYFA 2: ANTRENÖR REHBERİ -->
  <div class="page">
    <div class="m-header">
      <div>
        <h1>🎯 BÖLÜM 2: SAHA ANTRENÖRÜ & YOKLAMA REHBERİ</h1>
        <div class="sub">PIN ile Giriş, Tek Dokunuşla Yoklama ve Binici Gelişim Takibi</div>
      </div>
      <div class="badge">Rehber Sayfa 2 / 3</div>
    </div>

    <div class="grid-2">
      <div>
        <div class="step-card gold">
          <div class="step-card-header">
            <span class="step-num">1</span>
            <span class="step-card-title">4 Haneli PIN İle Anında Giriş</span>
          </div>
          <p>Giriş ekranında <strong>Antrenör</strong> sekmesine tıklayın. İsminizi seçip 4 haneli PIN kodunuzu (örn: Emre Hoca için <code>3184</code>) girin. Sistem sizi doğrudan <strong>Antrenör Masası</strong>na yönlendirir.</p>
        </div>

        <div class="step-card">
          <div class="step-card-header">
            <span class="step-num">2</span>
            <span class="step-card-title">Günlük Seans Listesi & Yoklama</span>
          </div>
          <p>Bugün planlanan dersler ekranda saat sırasıyla listelenir. Derse katılan binici için <strong>"Geldi (Tamamlandı)"</strong> butonuna basınız. Gelemeyen binici için <strong>"Gelmedi (No-Show)"</strong> seçiniz.</p>
        </div>

        <div class="step-card">
          <div class="step-card-header">
            <span class="step-num">3</span>
            <span class="step-card-title">Biniş Disiplini & Gelişim Notu</span>
          </div>
          <p>Sağdaki gelişim panelinden öğrencinizi seçin. Disiplin türünü (Lonj, Tırıs & Kenter, Engel Atlama vb.) belirleyip gelişim gözleminizi yazarak <strong>"Notu Kaydet"</strong>e basınız.</p>
        </div>
      </div>

      <div>
        <div class="img-box">
          <img src="${imgTrainer}" alt="Antrenör Masası">
          <div class="caption">Antrenör Masası: Günlük Yoklama Listesi ve Öğrenci Gelişim Notu Modülü</div>
        </div>
      </div>
    </div>

    <div class="footer-bar">
      <span>4D Binicilik — Master Kullanım Kılavuzu</span>
      <span>4Dimension Bilişim • 0850 888 48 80</span>
    </div>
  </div>

  <!-- SAYFA 3: ÜYE REHBERİ VE SSS -->
  <div class="page">
    <div class="m-header">
      <div>
        <h1>👤 BÖLÜM 3: ÜYE / BİNİCİ REHBERİ & SSS</h1>
        <div class="sub">Seans Rezervasyonu, At Tercihi, Kredi Bakiyesi ve İptal Kuralları</div>
      </div>
      <div class="badge">Rehber Sayfa 3 / 3</div>
    </div>

    <div class="grid-2">
      <div>
        <div class="step-card gold">
          <div class="step-card-header">
            <span class="step-num">REZERVASYON</span>
            <span class="step-card-title">Takvimden Seans ve At Seçimi</span>
          </div>
          <p>Üst menüden <strong>"Seans Takvimi"</strong> sekmesine geçin. Haftanın müsait yeşil slotlarına tıklayın. Açılan pencerede tercih ettiğiniz atı (Poyraz, Asil vb.) seçerek randevunuzu onaylayın. Kalan ders bakiyenizden 1 hak düşer.</p>
        </div>

        <div class="step-card">
          <div class="step-card-header">
            <span class="step-num">İPTAL KURALI</span>
            <span class="step-card-title">2 Saat Öncesine Kadar Ücretsiz İptal</span>
          </div>
          <p>Ders saatinize 2 saatten fazla süre varken dashboard'dan <strong>"İptal Et"</strong> butonuna basarsanız krediniz anında hesabınıza iade edilir. 2 saatten az kala iptallerde ders hakkı yanar.</p>
        </div>

        <div class="step-card">
          <div class="step-card-header">
            <span class="step-num">SAFARİ TURU</span>
            <span class="step-card-title">Orman Safari Turu Rezervasyonu</span>
          </div>
          <p><strong>"Safari Turu"</strong> sekmesine giderek hafta sonu orman biniş gruplarına tek tıkla kayıt oluşturabilirsiniz.</p>
        </div>
      </div>

      <div>
        <div class="img-box">
          <img src="${imgCalendar}" alt="Takvim ve At Seçimi">
          <div class="caption">Haftalık Seans Takvimi ve Dinamik Müsaitlik Ekranı</div>
        </div>
        <div class="img-box" style="margin-top: 6px;">
          <img src="${imgFamilies}" alt="Aile Havuzu">
          <div class="caption">Aile Ortak Kredi Havuzu ve Senkronize Ders Yönetimi</div>
        </div>
      </div>
    </div>

    <div style="background: #f8fafc; border-left: 3px solid #0284c7; padding: 6px 10px; border-radius: 0 4px 4px 0; margin-top: 8px;">
      <div style="font-weight: 800; color: #0369a1; font-size: 8px;">💡 Soru: Bakiyem bittiğinde nasıl yeni paket alabilirim?</div>
      <div style="font-size: 7.5px; color: #334155;">Cevap: Menüdeki <strong>"Paketler"</strong> sekmesinden 10'lu veya 20'li ders paketini seçip kulüp yönetimine talep gönderebilir veya resepsiyondan hemen tanımlatabilirsiniz.</div>
    </div>

    <div class="footer-bar">
      <span>Teknik Destek: 0850 888 48 80 • info@4dimension.com.tr • 4Dimension Bilişim</span>
      <span>Sayfa 3 / 3</span>
    </div>
  </div>

</body>
</html>
`;

  const masterManualPage = await browser.newPage();
  await masterManualPage.setContent(masterManualHtml, { waitUntil: 'networkidle' });

  const manualPdfPath = path.join(ROOT_DIR, '4D_Binicilik_Ultra_Luks_Kullanim_Rehberi.pdf');
  const manualAppPath = path.join(APP_DIR, 'docs/manuel-test-senaryolari/4D_Binicilik_Ultra_Luks_Kullanim_Rehberi.pdf');
  const manualArtifactPath = path.join(ARTIFACTS_DIR, '4D_Binicilik_Ultra_Luks_Kullanim_Rehberi.pdf');

  await masterManualPage.pdf({
    path: manualPdfPath,
    format: 'A4',
    printBackground: true,
    margin: { top: '0mm', right: '0mm', bottom: '0mm', left: '0mm' }
  });
  fs.copyFileSync(manualPdfPath, manualAppPath);
  fs.copyFileSync(manualPdfPath, manualArtifactPath);
  await masterManualPage.close();
  console.log(`✅ [2/2] Master Executive Kullanım Rehberi PDF oluşturuldu: ${manualPdfPath}`);

  await browser.close();
  console.log('\n🎉 ALL ULTRA-LUXURY EXECUTIVE CLIENT ARTIFACTS SUCCESSFULLY GENERATED!');
}

generateUltraLuxuryDocs().catch(console.error);
