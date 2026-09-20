const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const ROOT_DIR = '/Users/aliyev/Documents/UNI/Staj';
const APP_DIR = '/Users/aliyev/Documents/UNI/Staj/angora-riding-club-app';
const ARTIFACTS_DIR = '/Users/aliyev/.gemini/antigravity-ide/brain/7aa30d84-4b82-4554-846e-ea58007aedb8';

async function generateCustomerDocs() {
  console.log('🎨 Launching Playwright to generate Corporate Brochure & User Manual PDFs...');
  const browser = await chromium.launch({ headless: true });

  // --------------------------------------------------------------------------------------------------
  // 1. TANITIM BROŞÜRÜ (PRODUCT BROCHURE) - 2 SAYFA LÜKS CORPORATE
  // --------------------------------------------------------------------------------------------------
  console.log('📄 [1/2] Generating Corporate Product Brochure (Tanıtım Broşürü)...');
  const brochureHtml = `
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>4D Binicilik — Kurumsal Ürün Tanıtım Broşürü</title>
<style>
  @import url("https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap");
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: "Plus Jakarta Sans", sans-serif;
    color: #0f172a;
    background: #ffffff;
    line-height: 1.35;
    font-size: 8.8px;
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
  .header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2.5px solid #d97706;
    padding-bottom: 10px;
    margin-bottom: 12px;
  }
  .brand-title {
    font-size: 18px;
    font-weight: 900;
    color: #b45309;
    letter-spacing: -0.02em;
  }
  .brand-sub {
    font-size: 10.5px;
    font-weight: 700;
    color: #d97706;
  }
  .header-tag {
    background: #fffbeb;
    border: 1px solid #fde68a;
    color: #b45309;
    font-size: 8px;
    font-weight: 800;
    padding: 4px 10px;
    border-radius: 20px;
    text-transform: uppercase;
  }
  .hero-box {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: #ffffff;
    border-radius: 8px;
    padding: 14px 18px;
    margin-bottom: 14px;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.12);
  }
  .hero-box h2 {
    font-size: 13.5px;
    font-weight: 800;
    color: #fbbf24;
    margin-bottom: 4px;
  }
  .hero-box p {
    font-size: 8.5px;
    color: #cbd5e1;
    line-height: 1.4;
  }
  .grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 12px;
  }
  .grid-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 8px;
    margin-bottom: 12px;
  }
  .card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 10px 12px;
  }
  .card.highlight {
    background: #fffbeb;
    border-color: #fde68a;
  }
  .card h3 {
    font-size: 10px;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 4px;
  }
  .card.highlight h3 { color: #b45309; }
  .card p {
    font-size: 8px;
    color: #475569;
    line-height: 1.35;
  }
  .feature-list {
    list-style: none;
    margin-top: 5px;
  }
  .feature-list li {
    font-size: 7.8px;
    color: #334155;
    margin-bottom: 3.5px;
    display: flex;
    align-items: flex-start;
    gap: 4px;
  }
  .feature-list li strong { color: #0f172a; }
  .icon-check { color: #d97706; font-weight: 900; }
  .section-title {
    font-size: 11px;
    font-weight: 800;
    color: #b45309;
    border-left: 3.5px solid #d97706;
    padding-left: 6px;
    margin-bottom: 8px;
    text-transform: uppercase;
  }
  .kpi-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
    margin-bottom: 12px;
  }
  .kpi {
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 8px 6px;
    text-align: center;
  }
  .kpi .num {
    font-size: 15px;
    font-weight: 900;
    color: #b45309;
  }
  .kpi .lbl {
    font-size: 7.5px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
  }
  .contact-footer {
    position: absolute;
    bottom: 20px;
    left: 30px;
    right: 30px;
    background: #0f172a;
    color: #ffffff;
    border-radius: 6px;
    padding: 10px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 8px;
  }
  .contact-footer .info { color: #cbd5e1; }
  .contact-footer .btn-cta {
    background: #d97706;
    color: #ffffff;
    font-weight: 800;
    padding: 5px 12px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 8.5px;
  }
</style>
</head>
<body>

  <!-- SAYFA 1: VİZYON VE ANA MODÜLLER -->
  <div class="page">
    <div class="header">
      <div>
        <div class="brand-title">🏇 4D BİNİCİLİK & ANGORA RIDING CLUB</div>
        <div class="brand-sub">Yeni Nesil B2B Bulut Tabanlı Kulüp & Tesis Yönetim Platformu</div>
      </div>
      <div class="header-tag">Kurumsal Çözüm</div>
    </div>

    <div class="hero-box">
      <h2>Binicilik Kulüplerinde Dijital Dönüşüm ve Sıfır Hata Çağı</h2>
      <p>Manuel defter tutma çilesine, çakışan seans randevularına, kaybolan ders haklarına ve takipsiz at yorgunluklarına son! 4D Binicilik; binici, antrenör ve kulüp yönetimini tek bir yüksek güvenlikli bulut mimarisinde birleştirir.</p>
    </div>

    <div class="kpi-row">
      <div class="kpi">
        <div class="num">%100</div>
        <div class="lbl">Çift Bakiye Mutabakatı</div>
      </div>
      <div class="kpi">
        <div class="num">0 Sn</div>
        <div class="lbl">Hızlı Antrenör Yoklama</div>
      </div>
      <div class="kpi">
        <div class="num">7/24</div>
        <div class="lbl">Canlı Üye Rezervasyonu</div>
      </div>
      <div class="kpi">
        <div class="num">KVKK / ISO</div>
        <div class="lbl">Kurumsal Güvenlik</div>
      </div>
    </div>

    <div class="section-title">🌟 Üç Katmanlı Çözüm Mimarisi</div>
    <div class="grid-3">
      <div class="card highlight">
        <h3>👑 1. Yönetici Konsolu</h3>
        <p>Kulüp sahibi ve yöneticiler için 360 derece denetim:</p>
        <ul class="feature-list">
          <li><span class="icon-check">✓</span> <strong>166+ Üye Kütüğü:</strong> Canlı arama, aktif/pasif filtreleme.</li>
          <li><span class="icon-check">✓</span> <strong>Excel/CSV İndir:</strong> UTF-8 destekli anlık rapor alma.</li>
          <li><span class="icon-check">✓</span> <strong>Çift Defter (Ledger):</strong> Her kredi hareketi kayıt altında.</li>
          <li><span class="icon-check">✓</span> <strong>Manuel Kredi:</strong> +1/+4 ders anında bakiye yükleme.</li>
        </ul>
      </div>

      <div class="card">
        <h3>🎯 2. Antrenör Masası</h3>
        <p>Sahada tablet ve telefondan tek tıkla operasyon:</p>
        <ul class="feature-list">
          <li><span class="icon-check">✓</span> <strong>4 Haneli PIN Girişi:</strong> Hızlı ve şifresiz sahadan erişim.</li>
          <li><span class="icon-check">✓</span> <strong>Canlı Yoklama:</strong> Geldi / Gelmedi tek dokunuşla seans tamamlama.</li>
          <li><span class="icon-check">✓</span> <strong>Gelişim Notu:</strong> Binicinin biniş seviyesi ve gelişim takibi.</li>
          <li><span class="icon-check">✓</span> <strong>Rol İzolasyonu:</strong> Parasal yetkisiz alanlara tam kilit.</li>
        </ul>
      </div>

      <div class="card">
        <h3>👤 3. Üye & Veli Portalı</h3>
        <p>Biniciler ve aileler için lüks mobil deneyim:</p>
        <ul class="feature-list">
          <li><span class="icon-check">✓</span> <strong>Dijital Kulüp Kartı:</strong> Referans no ve kalan ders sayacı.</li>
          <li><span class="icon-check">✓</span> <strong>At Seçimi:</strong> Müsait slotta istenilen atı seçip ayırtma.</li>
          <li><span class="icon-check">✓</span> <strong>Aile Havuzu:</strong> Eş ve çocuklar tek krediden faydalanır.</li>
          <li><span class="icon-check">✓</span> <strong>Orman Safari Turu:</strong> Safari turlarına tek tıkla kayıt.</li>
        </ul>
      </div>
    </div>

    <div class="section-title">🛡️ Kurumsal Güvenlik ve Altyapı Standartları</div>
    <div class="grid-2">
      <div class="card">
        <h3>🔒 Çakışma Önleyici Kilit (Concurrency Lock)</h3>
        <p>Aynı seansa veya aynı ata aynı anda iki binicinin rezervasyon yapmasını engelleyen <strong>Atomic Database Lock</strong> kalkanı sayesinde mükerrer kayıtlar sıfıra iner.</p>
      </div>
      <div class="card">
        <h3>📜 Şeffaf Audit ve Güvenlik Günlüğü</h3>
        <p>Sistemdeki tüm bakiye değişiklikleri, oturum açma girişimleri ve yönetici müdahaleleri IP ve zaman damgasıyla şeffaf olarak kayıt altına alınır.</p>
      </div>
    </div>

    <div class="contact-footer">
      <div>
        <div style="font-weight: 800; font-size: 9px; color: #fbbf24;">4Dimension Bilişim Teknolojileri A.Ş.</div>
        <div class="info">Teknopark Ankara B Blok Yenimahalle / ANKARA • Tel: 0850 888 48 80 • info@4dimension.com.tr</div>
      </div>
      <a href="http://49.13.209.98:8088" class="btn-cta">Canlı Demoyu İncele →</a>
    </div>
  </div>

</body>
</html>
`;

  const brochurePage = await browser.newPage();
  await brochurePage.setContent(brochureHtml, { waitUntil: 'networkidle' });

  const brochurePdfPath = path.join(ROOT_DIR, '4D_Binicilik_Kurumsal_Tanitim_Brosuru.pdf');
  const brochureAppPath = path.join(APP_DIR, 'docs/manuel-test-senaryolari/4D_Binicilik_Kurumsal_Tanitim_Brosuru.pdf');
  const brochureArtifactPath = path.join(ARTIFACTS_DIR, '4D_Binicilik_Kurumsal_Tanitim_Brosuru.pdf');

  await brochurePage.pdf({
    path: brochurePdfPath,
    format: 'A4',
    printBackground: true,
    margin: { top: '0mm', right: '0mm', bottom: '0mm', left: '0mm' }
  });
  fs.copyFileSync(brochurePdfPath, brochureAppPath);
  fs.copyFileSync(brochurePdfPath, brochureArtifactPath);
  await brochurePage.close();
  console.log(`✅ [1/2] Tanıtım Broşürü PDF oluşturuldu: ${brochurePdfPath}`);


  // --------------------------------------------------------------------------------------------------
  // 2. KULLANIM KILAVUZU (USER MANUAL & QUICK START GUIDE) - 2 SAYFA ADIM ADIM REHBER
  // --------------------------------------------------------------------------------------------------
  console.log('\n📄 [2/2] Generating User Manual & Quick Start Guide (Kullanım Kılavuzu)...');
  const manualHtml = `
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>4D Binicilik — Kullanım Kılavuzu ve Hızlı Başlangıç Rehberi</title>
<style>
  @import url("https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap");
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: "Plus Jakarta Sans", sans-serif;
    color: #0f172a;
    background: #ffffff;
    line-height: 1.35;
    font-size: 8.8px;
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
  .header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2.5px solid #d97706;
    padding-bottom: 8px;
    margin-bottom: 10px;
  }
  .brand-title {
    font-size: 16px;
    font-weight: 900;
    color: #b45309;
    letter-spacing: -0.02em;
  }
  .brand-sub {
    font-size: 10px;
    font-weight: 700;
    color: #d97706;
  }
  .header-tag {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #15803d;
    font-size: 7.5px;
    font-weight: 800;
    padding: 3px 8px;
    border-radius: 12px;
  }
  .section-title {
    font-size: 10.5px;
    font-weight: 800;
    color: #b45309;
    border-left: 3.5px solid #d97706;
    padding-left: 6px;
    margin-top: 10px;
    margin-bottom: 6px;
    text-transform: uppercase;
  }
  .step-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 8px 12px;
    margin-bottom: 7px;
  }
  .step-box.gold {
    background: #fffbeb;
    border-color: #fde68a;
  }
  .step-header {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 4px;
  }
  .step-badge {
    background: #d97706;
    color: #ffffff;
    font-size: 8px;
    font-weight: 900;
    padding: 2px 6px;
    border-radius: 4px;
  }
  .step-title {
    font-size: 9.5px;
    font-weight: 800;
    color: #0f172a;
  }
  .step-box p {
    font-size: 8px;
    color: #475569;
    line-height: 1.35;
    margin-bottom: 3px;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 8px;
    font-size: 8px;
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
  .faq-card {
    background: #f8fafc;
    border-left: 3px solid #0284c7;
    padding: 6px 10px;
    margin-bottom: 6px;
    border-radius: 0 4px 4px 0;
  }
  .faq-q { font-weight: 800; color: #0369a1; font-size: 8.5px; margin-bottom: 2px; }
  .faq-a { color: #334155; font-size: 8px; }
  .footer {
    position: absolute;
    bottom: 15px;
    left: 30px;
    right: 30px;
    display: flex;
    justify-content: space-between;
    font-size: 7.5px;
    color: #94a3b8;
    border-top: 1px solid #e2e8f0;
    padding-top: 4px;
  }
</style>
</head>
<body>

  <!-- SAYFA 1: ADMİN VE ANTRENÖR KILAVUZU -->
  <div class="page">
    <div class="header">
      <div>
        <div class="brand-title">📖 4D BİNİCİLİK KULLANIM KILAVUZU</div>
        <div class="brand-sub">Adım Adım Hızlı Başlangıç & Operasyon Rehberi</div>
      </div>
      <div class="header-tag">Versiyon 2026.1</div>
    </div>

    <div class="section-title">🔑 Sisteme Giriş Gateway'i</div>
    <p style="font-size: 8px; color: #475569; margin-bottom: 6px;">Web tarayıcınızdan <strong>http://49.13.209.98:8088</strong> adresine girdiğinizde ekranınızda 3 sekmeli güvenli giriş kapısı açılır. Kendi rolünüze uygun sekmeyi seçiniz:</p>
    
    <table>
      <thead>
        <tr>
          <th>Rol</th>
          <th>Giriş Yöntemi</th>
          <th>Gereken Bilgiler</th>
          <th>Giriş Sonrası Açılan Ekran</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><strong>👑 Kulüp Yöneticisi</strong></td>
          <td>Yönetici Sekmesi</td>
          <td>E-posta + Parola + 4 Haneli Güvenlik PIN Kodu</td>
          <td>👑 Yönetim Konsolu & 166 Üye Kütüğü</td>
        </tr>
        <tr>
          <td><strong>🎯 Antrenör / Eğitmen</strong></td>
          <td>Antrenör Sekmesi</td>
          <td>İsim Seçimi + 4 Haneli Hızlı Saha PIN'i</td>
          <td>📋 Antrenör Masası & Günlük Seans Yoklaması</td>
        </tr>
        <tr>
          <td><strong>👤 Kulüp Üyesi / Binici</strong></td>
          <td>Kulüp Üyesi Sekmesi</td>
          <td>Telefon Numarası + Hesap Şifresi</td>
          <td>💳 Üye Dashboard, Seans Takvimi & Kredi Bakiyesi</td>
        </tr>
      </tbody>
    </table>

    <div class="section-title">👑 BÖLÜM 1: KULÜP YÖNETİCİSİ (ADMİN) REHBERİ</div>
    
    <div class="step-box gold">
      <div class="step-header">
        <span class="step-badge">ADIM 1</span>
        <span class="step-title">Üye Kütüğü ve Canlı Arama</span>
      </div>
      <p>Yönetim paneline girdikten sonra üstteki <strong>"Üyeler"</strong> sekmesine tıklayın. 166 kayıtlı üye listelenir. Arama kutusuna binicinin adını veya telefonunu yazarak anında bulunuz.</p>
    </div>

    <div class="step-box">
      <div class="step-header">
        <span class="step-badge">ADIM 2</span>
        <span class="step-title">Manuel Kredi Yükleme & Düzeltme</span>
      </div>
      <p>Nakit/havale ile paket alan veya telafi dersi kazanan üyelerin satırındaki <strong>"+1 Ders"</strong> veya <strong>"+4 Ders"</strong> butonuna basarak krediyi tek tıkla yükleyin. Sistem çift taraflı muhasebe kaydını otomatik işler.</p>
    </div>

    <div class="step-box">
      <div class="step-header">
        <span class="step-badge">ADIM 3</span>
        <span class="step-title">Excel / CSV Dışa Aktarma</span>
      </div>
      <p>Muhasebe ve resmi bildirimler için sağ üstteki <strong>"📥 Excel / CSV İndir"</strong> butonuna basınız. İnen dosya Türkçe karakterleri (ş, ğ, ü, ö, ç, İ) bozmadan sütunlara ayrılmış olarak Excel'de açılır.</p>
    </div>

    <div class="step-box">
      <div class="step-header">
        <span class="step-badge">ADIM 4</span>
        <span class="step-title">Aile Grupları ve Ortak Havuz Yönetimi</span>
      </div>
      <p><strong>"Aile Grupları"</strong> sekmesinden aile havuzu açabilir, anne-baba ve çocukları tek bir ortak ders havuzuna bağlayabilirsiniz.</p>
    </div>

    <div class="footer">
      <span>4Dimension Bilişim — 4D Binicilik Kullanım Kılavuzu</span>
      <span>Sayfa 1 / 2</span>
    </div>
  </div>

  <!-- SAYFA 2: ANTRENÖR VE ÜYE KILAVUZU + SSS -->
  <div class="page">
    <div class="header">
      <div>
        <div class="brand-title">🎯 BÖLÜM 2: ANTRENÖR & ÜYE REHBERİ</div>
        <div class="brand-sub">Saha Yoklama, Seans Rezervasyonu & Sıkça Sorulan Sorular</div>
      </div>
      <div class="header-tag">Kullanıcı Rehberi</div>
    </div>

    <div class="section-title">🎯 ANTRENÖR REHBERİ (SAHA & TABLET OPERASYONU)</div>
    
    <div class="step-box gold">
      <div class="step-header">
        <span class="step-badge">1. HIZLI GİRİŞ</span>
        <span class="step-title">Antrenör Masasına 5 Saniyede Bağlanma</span>
      </div>
      <p>Giriş ekranında <strong>Antrenör</strong> sekmesine geçin. Adınızı seçip kulüp tarafından verilen <strong>4 haneli PIN kodunuzu</strong> (örn. Emre Hoca: 3184, İrem Hoca: 3406) girerek oturum açın.</p>
    </div>

    <div class="step-box">
      <div class="step-header">
        <span class="step-badge">2. YOKLAMA</span>
        <span class="step-title">Ders Yoklaması Alma & Tamamlama</span>
      </div>
      <p>Seans saati geldiğinde derse katılan binici için <strong>"Geldi (Tamamlandı)"</strong> butonuna basınız. Gelemeyen binici için <strong>"Gelmedi (No-Show)"</strong> seçerek seansı kapatınız.</p>
    </div>

    <div class="step-box">
      <div class="step-header">
        <span class="step-badge">3. GELİŞİM NOTU</span>
        <span class="step-title">Binici Gelişim & Biniş Notu Ekleme</span>
      </div>
      <p>Sağdaki gelişim panelinden öğrencinizi seçip (Lonj, Tırıs, Kenter, Engel Atlama) disiplinini belirleyerek günün biniş notunu kaydedin. Bu not öğrencinin profil geçmişine işlenir.</p>
    </div>

    <div class="section-title">👤 ÜYE REHBERİ (BİNİCİ & VELİ KULLANIMI)</div>
    
    <div class="step-box">
      <div class="step-header">
        <span class="step-badge">REZERVASYON</span>
        <span class="step-title">Takvimden Seans ve At Seçimi</span>
      </div>
      <p><strong>"Seans Takvimi"</strong> sekmesine tıklayınız. Haftanın müsait yeşil kutularına tıklayıp açılan pencerede tercih ettiğiniz atı (Poyraz, Asil vb.) seçerek randevunuzu onaylayınız. Kalan ders bakiyenizden 1 hak düşer.</p>
    </div>

    <div class="step-box">
      <div class="step-header">
        <span class="step-badge">İPTAL KURALI</span>
        <span class="step-title">2 Saat Öncesine Kadar Ücretsiz İptal</span>
      </div>
      <p>Dersinize 2 saatten fazla süre varken dashboard'dan <strong>"İptal Et"</strong>e basarsanız krediniz anında hesabınıza iade edilir. 2 saatten az kala yapılan iptallerde ders hakkı yanar.</p>
    </div>

    <div class="section-title">❓ SIKÇA SORULAN SORULAR (SSS)</div>
    <div class="faq-card">
      <div class="faq-q">Soru: Kalan ders bakiyemi nereden görebilirim?</div>
      <div class="faq-a">Cevap: Üye girişi yaptıktan sonra ana ekrandaki <strong>Dijital Kulüp Kartınızda</strong> ve <strong>"Kalan Ders"</strong> sayacında net olarak görüntüleyebilirsiniz.</div>
    </div>
    <div class="faq-card">
      <div class="faq-q">Soru: Aile üyelerimiz aynı krediyi nasıl harcar?</div>
      <div class="faq-a">Cevap: Yönetici tarafından oluşturulan Aile Havuzu sayesinde eşiniz veya çocuğunuz kendi hesabından rezervasyon yaptığında ortak aile kredisinden otomatik düşer.</div>
    </div>

    <div class="footer">
      <span>Teknik Destek: 0850 888 48 80 • E-posta: info@4dimension.com.tr • 4Dimension Bilişim</span>
      <span>Sayfa 2 / 2</span>
    </div>
  </div>

</body>
</html>
`;

  const manualPage = await browser.newPage();
  await manualPage.setContent(manualHtml, { waitUntil: 'networkidle' });

  const manualPdfPath = path.join(ROOT_DIR, '4D_Binicilik_Kullanim_Kilavuzu.pdf');
  const manualAppPath = path.join(APP_DIR, 'docs/manuel-test-senaryolari/4D_Binicilik_Kullanim_Kilavuzu.pdf');
  const manualArtifactPath = path.join(ARTIFACTS_DIR, '4D_Binicilik_Kullanim_Kilavuzu.pdf');

  await manualPage.pdf({
    path: manualPdfPath,
    format: 'A4',
    printBackground: true,
    margin: { top: '0mm', right: '0mm', bottom: '0mm', left: '0mm' }
  });
  fs.copyFileSync(manualPdfPath, manualAppPath);
  fs.copyFileSync(manualPdfPath, manualArtifactPath);
  await manualPage.close();
  console.log(`✅ [2/2] Kullanım Kılavuzu PDF oluşturuldu: ${manualPdfPath}`);

  await browser.close();
  console.log('\n🎉 Both Corporate PDFs successfully created and ready to send to clients!');
}

generateCustomerDocs().catch(console.error);
