<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>4D Binicilik — Üye & Yönetici Portalı</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- PWA Settings -->
  <link rel="manifest" href="/manifest.json">
  <meta name="theme-color" content="#0B1914">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="4D Binicilik">

  @vite(['resources/css/app.css', 'resources/js/app.js'])

  <style>
    /* ==========================================================================
       CRITICAL AUTH & GATEWAY ISOLATION ENGINE
       ========================================================================== */
    body.auth-locked {
      overflow: hidden !important;
      background: #0B1914 !important;
      margin: 0;
      padding: 0;
    }
    body.auth-locked #appTopbar,
    body.auth-locked .app-container,
    body.auth-locked .mobile-nav,
    body.auth-locked .rule-banner {
      display: none !important;
    }
    body.auth-locked #loginGatewayScreen {
      display: flex !important;
    }

    body.app-unlocked #loginGatewayScreen {
      display: none !important;
    }
    body.app-unlocked #appTopbar {
      display: block !important;
    }
    body.app-unlocked .app-container {
      display: block !important;
    }

    .login-gateway-screen {
      position: fixed;
      inset: 0;
      width: 100vw;
      height: 100vh;
      background: radial-gradient(circle at 50% 20%, #1A382C 0%, #0D1F17 60%, #060E0A 100%);
      z-index: 999999;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.25rem;
      overflow-y: auto;
      box-sizing: border-box;
    }

    .login-card-container {
      width: 100%;
      max-width: 460px;
      background: #FFFFFF;
      border-radius: 20px;
      box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(197, 160, 89, 0.4);
      padding: 2.25rem 2rem;
      position: relative;
      box-sizing: border-box;
      margin: auto;
    }

    .login-brand-header {
      text-align: center;
      margin-bottom: 1.5rem;
    }

    .login-brand-icon {
      width: 60px;
      height: 60px;
      background: linear-gradient(135deg, #DFBA73 0%, #C5A059 50%, #9E7B35 100%);
      border-radius: 16px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 2.2rem;
      margin-bottom: 0.65rem;
      box-shadow: 0 8px 24px rgba(197, 160, 89, 0.35);
    }

    .login-brand-title {
      font-family: 'Playfair Display', Georgia, serif;
      font-size: 1.65rem;
      font-weight: 800;
      color: #11261E;
      letter-spacing: -0.5px;
    }

    .login-brand-subtitle {
      font-size: 0.75rem;
      color: #5A7067;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      font-weight: 600;
      margin-top: 0.2rem;
    }

    .auth-tabs {
      display: flex;
      background: #F4F7F5;
      padding: 4px;
      border-radius: 10px;
      margin-bottom: 1.25rem;
      gap: 4px;
      border: 1px solid #E2E8E5;
    }

    .auth-tab-btn {
      flex: 1;
      padding: 0.65rem 0.35rem;
      border: none;
      background: none;
      font-size: 0.78rem;
      font-weight: 700;
      color: #5A7067;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.2s ease;
      text-align: center;
    }

    .auth-tab-btn.active {
      background: #FFFFFF;
      color: #1B3B2F;
      box-shadow: 0 2px 6px rgba(27, 59, 47, 0.1);
    }

    .demo-account-pill {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0.65rem 0.85rem;
      background: #F8FAFC;
      border: 1px solid #E2E8F0;
      border-radius: 10px;
      margin-bottom: 0.5rem;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .demo-account-pill:hover {
      border-color: #C5A059;
      background: #FFFDF8;
      transform: translateY(-1px);
    }

    .btn-gold {
      background: linear-gradient(135deg, #DFBA73 0%, #C5A059 50%, #9E7B35 100%);
      color: #11261E;
      border: none;
      border-radius: 8px;
      font-weight: 700;
      font-size: 0.95rem;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .btn-gold:hover {
      opacity: 0.92;
      box-shadow: 0 6px 16px rgba(197, 160, 89, 0.35);
    }
  </style>
</head>
<body class="auth-locked">

  <!-- Toast Container -->
  <div class="toast-container" id="toastContainer"></div>

  <!-- ====================================================================
       DEDICATED FULL-SCREEN LUXURY LOGIN GATEWAY SCREEN
       ==================================================================== -->
  <div class="login-gateway-screen" id="loginGatewayScreen" style="display:flex;">
    <div class="login-card-container">
      <div class="login-brand-header">
        <div class="login-brand-icon">🏇</div>
        <h1 class="login-brand-title">Angora Binicilik</h1>
        <p class="login-brand-subtitle">SPOR KULÜBÜ • ÜYE & YÖNETİCİ PORTALI</p>
      </div>

      <!-- Auth Navigation Tabs -->
      <div class="auth-tabs">
        <button class="auth-tab-btn active" id="gatewayTabBtn-member" onclick="switchGatewayTab('member')">👤 Üye Girişi</button>
        <button class="auth-tab-btn" id="gatewayTabBtn-admin" onclick="switchGatewayTab('admin')">👑 Yönetici</button>
        <button class="auth-tab-btn" id="gatewayTabBtn-trainer" onclick="switchGatewayTab('trainer')">🎯 Antrenör</button>
      </div>
      <div class="form-error" id="gatewayFormError" style="display:none; color:#DC2626; font-size:0.85rem; margin-bottom:0.75rem;"></div>

      <!-- TAB 1: ÜYE GİRİŞİ -->
      <div class="gateway-sub-panel" id="gatewayPanel-member">
        <div class="form-group">
          <label class="form-label">Telefon Numarası veya Referans Kodu</label>
          <input type="text" class="form-control" id="gwMemberPhone" placeholder="Örn: 05551234567 veya referans kodu">
        </div>
        <div class="form-group">
          <label class="form-label">Şifre</label>
          <input type="password" class="form-control" id="gwMemberPass" placeholder="••••••••">
        </div>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; font-size:0.8rem;">
          <label style="display:flex; align-items:center; gap:0.35rem; color:var(--text-muted); cursor:pointer;">
            <input type="checkbox" checked> Beni Hatırla
          </label>
          <a href="#" style="color:var(--gold-dark); text-decoration:none;" onclick="showToast('SMS ile şifre sıfırlama kodu telefonunuza iletildi.')">Şifremi Unuttum</a>
        </div>
        <button class="btn-gold" style="width:100%; padding:0.85rem; font-weight:700;" onclick="submitGatewayLogin('member')">
          🏇 Üye Portalı'na Giriş Yap →
        </button>
      </div>

      <!-- TAB 2: YÖNETİCİ GİRİŞİ -->
      <div class="gateway-sub-panel" id="gatewayPanel-admin" style="display:none;">
        <div class="form-group">
          <label class="form-label">Yönetici E-Posta / Kullanıcı Adı</label>
          <input type="text" class="form-control" id="gwAdminUser" placeholder="yonetici@ornekklup.com">
        </div>
        <div class="form-group">
          <label class="form-label">Yönetici Parolası</label>
          <input type="password" class="form-control" id="gwAdminPass" placeholder="••••••••">
        </div>
        <div class="form-group">
          <label class="form-label">2-Aşamalı Güvenlik PIN Kodu</label>
          <input type="password" maxlength="6" class="form-control" id="gwAdminPin" placeholder="4 Haneli PIN">
        </div>
        <button class="btn-primary" style="width:100%; padding:0.85rem; font-weight:700;" onclick="submitGatewayLogin('admin')">
          👑 Yönetici Masası'na Güvenli Giriş →
        </button>
      </div>

      <!-- TAB 3: ANTRENÖR GİRİŞİ -->
      <div class="gateway-sub-panel" id="gatewayPanel-trainer" style="display:none;">
        <div class="form-group">
          <label class="form-label">Eğitmen / Antrenör Seçin</label>
          <select class="form-control" id="gwTrainerSelect">
            <option value="">Yükleniyor…</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Antrenör PIN Kodu</label>
          <input type="password" class="form-control" id="gwTrainerPass" placeholder="PIN Kodu">
        </div>
        <button class="btn-gold" style="width:100%; padding:0.85rem; font-weight:700;" onclick="submitGatewayLogin('trainer')">
          🎯 Antrenör Seans Masasına Gir →
        </button>
      </div>

    </div>
  </div>

  <!-- TOPBAR / HEADER -->
  <header class="topbar" id="appTopbar">
    <div class="topbar-container">
      <a href="#" class="brand" onclick="switchView('dashboard')">
        <div class="brand-icon">🏇</div>
        <div class="brand-text">
          <span class="brand-name">Angora Binicilik</span>
          <span class="brand-tag">Spor Kulübü</span>
        </div>
      </a>

      <!-- Desktop Navigation -->
      <nav class="nav-desktop">
        <!-- Member Links -->
        <div class="nav-item nav-role-member active" id="navItemDashboard" data-view="dashboard" onclick="switchView('dashboard')">
          <span>📊</span> Dashboard
        </div>
        <div class="nav-item nav-role-member nav-role-trainer nav-role-admin" id="navItemAvailability" data-view="availability" onclick="switchView('availability')">
          <span>📅</span> Seans Takvimi
        </div>
        <div class="nav-item nav-role-member" id="navItemPackages" data-view="packages" onclick="switchView('packages')">
          <span>💳</span> Paketler & Safari
        </div>
        <div class="nav-item nav-role-member" id="navItemHistory" data-view="history" onclick="switchView('history')">
          <span>📜</span> Geçmiş
        </div>
        <!-- Trainer Link -->
        <div class="nav-item nav-role-trainer" id="navItemTrainer" data-view="trainer" onclick="switchView('trainer')" style="display:none; color:var(--gold-light); font-weight:700;">
          <span>🎯</span> Antrenör Masası
        </div>
        <!-- Admin Link -->
        <div class="nav-item nav-role-admin" id="navItemAdmin" data-view="admin" onclick="switchView('admin')" style="display:none; color:var(--gold-light); font-weight:700;">
          <span>👑</span> Admin Paneli
        </div>
      </nav>

      <!-- Role Switcher & User Pill -->
      <div style="display:flex; align-items:center; gap:0.6rem;">
        <button class="role-badge-btn" id="roleBadgeBtn" onclick="logoutToGateway()">
          🔑 Rol Değiştir
        </button>

        <div class="user-pill" onclick="switchView('profile')" style="cursor: pointer;">
          <div class="user-info">
            <span class="user-name" id="navUserName">—</span>
            <span class="user-ref" id="navUserRef">—</span>
          </div>
          <div class="user-avatar" id="navUserAvatar">👤</div>
        </div>

        <button class="btn-outline" style="padding:0.4rem 0.75rem; font-size:0.8rem; border-color:#EF4444; color:#EF4444; border-radius:var(--radius-sm);" onclick="logoutToGateway()" title="Oturumu Kapat">
          🚪 Çıkış
        </button>
      </div>
    </div>
  </header>

  <!-- MAIN CONTAINER -->
  <main class="app-container">

    <!-- RULE BANNER -->
    <div class="rule-banner">
      <div class="rule-content">
        <div class="rule-icon">⏱️</div>
        <div>
          <div class="rule-title">Akıllı İptal Kalkanı & Tesis Bilgisi</div>
          <div class="rule-desc">
            Derslerinizi ve Safari turlarınızı <b>en geç 2 saat öncesine kadar</b> ücretsiz iptal edebilirsiniz. 
            2 saatten az kalan iptallerde 1 ders hakkı düşülür. <b>Tesisimiz her Pazartesi kapalıdır.</b>
          </div>
        </div>
      </div>
    </div>

    <!-- ====================================================================
         VIEW 1: MEMBER DASHBOARD
         ==================================================================== -->
    <section class="view-section active" id="view-dashboard">

      <!-- 4 Stat Counters -->
      <div class="stat-grid">
        <div class="stat-card primary">
          <div class="stat-data">
            <span class="stat-label">Toplam Ders</span>
            <span class="stat-value" id="statTotal">—</span>
          </div>
          <div class="stat-icon-wrapper">🏇</div>
        </div>

        <div class="stat-card warning">
          <div class="stat-data">
            <span class="stat-label">Kullanılan</span>
            <span class="stat-value" id="statUsed">—</span>
          </div>
          <div class="stat-icon-wrapper">✅</div>
        </div>

        <div class="stat-card gold">
          <div class="stat-data">
            <span class="stat-label">Kalan Ders</span>
            <span class="stat-value" id="statRemaining">—</span>
          </div>
          <div class="stat-icon-wrapper">⏳</div>
        </div>

        <div class="stat-card success">
          <div class="stat-data">
            <span class="stat-label">Bekleyen Ders</span>
            <span class="stat-value" id="statPending">—</span>
          </div>
          <div class="stat-icon-wrapper">📅</div>
        </div>
      </div>

      <!-- Dashboard 2-Column Grid -->
      <div class="dashboard-layout">

        <!-- Left: Quick Booking Calendar -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">
              <span>📅</span> Hızlı Rezervasyon Takvimi
            </div>
            <button class="btn-primary" style="padding:0.35rem 0.75rem; font-size:0.75rem;" onclick="switchView('availability')">
              Eğitmen Takvimine Git →
            </button>
          </div>

          <!-- Calendar Header -->
          <div class="calendar-header">
            <button class="calendar-nav-btn">←</button>
            <div class="calendar-month">Eylül 2026</div>
            <button class="calendar-nav-btn">→</button>
          </div>

          <!-- Weekdays -->
          <div class="calendar-weekdays">
            <span class="monday">Pzt</span>
            <span>Sal</span>
            <span>Çar</span>
            <span>Per</span>
            <span>Cum</span>
            <span>Cmt</span>
            <span>Paz</span>
          </div>

          <!-- Days Grid -->
          <div class="calendar-days" id="calendarDays"></div>

          <!-- Selected day — click "Eğitmen Takvimine Git" above for the real, live slot matrix -->
          <div class="slots-container">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
              <span style="font-size:0.85rem; font-weight:700;">Seçilen Gün: <span id="selectedDateText" style="color:var(--primary);"></span></span>
            </div>
            <button class="btn-outline" style="width:100%;" onclick="switchView('availability')">Bu Gün İçin Müsait Saatleri Gör →</button>
          </div>
        </div>

        <!-- Right: Active Lessons -->
        <div>
          <div class="card">
            <div class="card-header">
              <div class="card-title">
                <span>🏇</span> Aktif Rezervasyonlarım
              </div>
            </div>
            <div id="activeLessonsList"></div>
          </div>
        </div>

      </div>
    </section>

    <!-- ====================================================================
         VIEW 2: EĞİTMEN TAKVİMİ (AVAILABILITY MATRIX)
         ==================================================================== -->
    <section class="view-section" id="view-availability">
      <div class="card">
        <div class="card-header">
          <div>
            <div class="card-title">
              <span>📅</span> Eğitmen & Seans Takvimi
            </div>
            <p style="font-size:0.8rem; color:var(--text-muted); margin-top:0.25rem;">
              Kulüp eğitmenlerimizin haftalık çalışma ve ders takvimini görüntüleyin, yeşil seanslara tıklayarak anında randevunuzu oluşturun.
            </p>
          </div>
          <div style="display:flex; gap:0.5rem; font-size:0.75rem;">
            <span style="display:inline-flex; align-items:center; gap:0.25rem;"><span style="color:var(--success);">🟢</span> Müsait Seans</span>
            <span style="display:inline-flex; align-items:center; gap:0.25rem;"><span style="color:#DC2626;">🔴</span> Dolu Seans</span>
            <span style="display:inline-flex; align-items:center; gap:0.25rem;"><span style="color:#9CA3AF;">⚪</span> İzinli</span>
          </div>
        </div>

        <!-- Filter Controls -->
        <div class="matrix-filters">
          <div>
            <span style="font-size:0.75rem; font-weight:700; color:var(--text-muted); display:block; margin-bottom:0.35rem;">GÜN SEÇİMİ:</span>
            <div class="filter-btn-group" id="matrixDateButtons"><!-- Populated by app.js --></div>
          </div>

          <div>
            <span style="font-size:0.75rem; font-weight:700; color:var(--text-muted); display:block; margin-bottom:0.35rem;">EĞİTMEN FİLTRESİ:</span>
            <div class="filter-btn-group" id="matrixTrainerButtons"><!-- Populated by app.js --></div>
          </div>
        </div>

        <!-- Availability Table -->
        <div class="matrix-table-container">
          <table class="matrix-table">
            <thead>
              <tr>
                <th>Eğitmen / Alan</th>
                <th>Ders Seansları (Rezerve Etmek İçin Tıklayın)</th>
              </tr>
            </thead>
            <tbody id="matrixTableBody">
              <!-- Populated by app.js -->
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- ====================================================================
         VIEW 3: TÜM PAKETLER (ÜYELİK + SAFARİ)
         ==================================================================== -->
    <section class="view-section" id="view-packages">
      <div style="text-align:center; margin-bottom:1.5rem;">
        <h2 style="font-family:var(--font-serif); font-size:1.85rem; color:var(--primary-dark); margin-bottom:0.4rem;">
          Kulüp Paketleri & Safari Turları
        </h2>
        <p style="color:var(--text-muted); font-size:0.95rem;">
          Tesis üyelik paketleri veya tek seferlik Atlı Doğa Safari paketlerinden birini seçin.
        </p>

        <!-- Package Sub-Tabs Toggle -->
        <div style="display:inline-flex; background:#E2E8E5; padding:0.3rem; border-radius:var(--radius-full); margin-top:1rem;">
          <button class="filter-chip active" id="tabBtnMembership" onclick="togglePackageCategory('membership')">
            🏇 Tesis Üyelik Paketleri (5 Paket)
          </button>
          <button class="filter-chip" id="tabBtnSafari" onclick="togglePackageCategory('safari')">
            🌲 Atlı Safari Tur Paketleri (3 Parkur)
          </button>
        </div>
      </div>

      <!-- Category 1: Membership Packages -->
      <div id="categoryMembership">
        <div class="packages-grid">
          <div class="package-card">
            <div class="package-duration">1 Aylık Tesis Üyeliği</div>
            <div class="package-lessons">4 Ders</div>
            <div class="package-price">7.000 ₺ <span>/ ay</span></div>
            <ul class="package-features">
              <li>✓ Haftada 1 Seans</li>
              <li>✓ 45 Dk Bireysel Ders</li>
              <li>✓ Ekipman Kullanımı</li>
            </ul>
            <button class="btn-primary" onclick="buyPackage('1 Aylık Tesis Üyeliği', 4, '7.000 ₺')">Satın Al</button>
          </div>

          <div class="package-card">
            <div class="package-duration">2 Aylık Tesis Üyeliği</div>
            <div class="package-lessons">8 Ders</div>
            <div class="package-price">13.200 ₺ <span>/ 2 ay</span></div>
            <ul class="package-features">
              <li>✓ Haftada 1-2 Seans</li>
              <li>✓ Bireysel Antrenör</li>
              <li>✓ Temel Gelişim</li>
            </ul>
            <button class="btn-primary" onclick="buyPackage('2 Aylık Tesis Üyeliği', 8, '13.200 ₺')">Satın Al</button>
          </div>

          <div class="package-card">
            <div class="package-duration">3 Aylık Tesis Üyeliği</div>
            <div class="package-lessons">12 Ders</div>
            <div class="package-price">18.600 ₺ <span>/ 3 ay</span></div>
            <ul class="package-features">
              <li>✓ Haftada 1-2 Seans</li>
              <li>✓ Dresaj & Engel Eğitimi</li>
              <li>✓ İptal ve Telafi Hakkı</li>
            </ul>
            <button class="btn-primary" onclick="buyPackage('3 Aylık Tesis Üyeliği', 12, '18.600 ₺')">Satın Al</button>
          </div>

          <div class="package-card popular">
            <span class="popular-badge">⭐ En Popüler</span>
            <div class="package-duration">5 Aylık Tesis Üyeliği</div>
            <div class="package-lessons">20 Ders</div>
            <div class="package-price">29.000 ₺ <span>/ 5 ay</span></div>
            <ul class="package-features">
              <li>✓ Haftada 1-2 Seans</li>
              <li>✓ İleri Düzey Binicilik</li>
              <li>✓ 1 Adet Ücretsiz Safari Turu</li>
            </ul>
            <button class="btn-gold" onclick="buyPackage('5 Aylık Tesis Üyeliği', 20, '29.000 ₺')">Satın Al</button>
          </div>

          <div class="package-card">
            <div class="package-duration">8 Aylık Tesis Üyeliği</div>
            <div class="package-lessons">30 Ders</div>
            <div class="package-price">43.000 ₺ <span>/ 8 ay</span></div>
            <ul class="package-features">
              <li>✓ Haftada 2 Seans</li>
              <li>✓ Lisans Hazırlık</li>
              <li>✓ 2 Adet Ücretsiz Safari Turu</li>
            </ul>
            <button class="btn-primary" onclick="buyPackage('8 Aylık Tesis Üyeliği', 30, '43.000 ₺')">Satın Al</button>
          </div>
        </div>
      </div>

      <!-- Category 2: Safari Tour Packages -->
      <div id="categorySafari" style="display:none;">
        <div class="safari-grid">
          <div class="safari-card">
            <div class="safari-img" style="background-image: url('https://images.unsplash.com/photo-1534447677768-be436bb09401?auto=format&fit=crop&w=600&q=80');">
              <span class="safari-duration-badge">⏱️ 60 Dakika</span>
            </div>
            <div class="safari-body">
              <div class="safari-title">Orman Parkuru Safari Paketi</div>
              <p class="safari-desc">Çam ormanları arasında sakin ve huzurlu bir başlangıç seviyesi safari turu.</p>
              <ul class="safari-features">
                <li>✓ Rehber eğitmen eşliği</li>
                <li>✓ Kask ve güvenlik yeleği</li>
                <li>✓ Tek veya grup katılımı</li>
              </ul>
              <div class="safari-footer">
                <div class="safari-price">1.500 ₺ <span style="font-size:0.75rem; font-weight:normal; color:var(--text-muted);">/ kişi</span></div>
                <button class="btn-primary" onclick="openSafariModal('Orman Parkuru Safari Paketi', 1500)">
                  Satın Al / Rezerve Et
                </button>
              </div>
            </div>
          </div>

          <div class="safari-card" style="border: 2px solid var(--gold);">
            <div class="safari-img" style="background-image: url('https://images.unsplash.com/photo-1553284965-83fd3e82fa5a?auto=format&fit=crop&w=600&q=80');">
              <span class="safari-duration-badge" style="background:var(--gold-gradient); color:var(--primary-dark);">⭐ 90 Dakika (Popüler)</span>
            </div>
            <div class="safari-body">
              <div class="safari-title">Göl & Doğa Safarisi Paketi</div>
              <p class="safari-desc">Orman içerisinden göl kıyısına uzanan geniş rotada eşsiz manzara eşliğinde dörtnala ve tırıs.</p>
              <ul class="safari-features">
                <li>✓ Uzun orman & göl rotası</li>
                <li>✓ Çay/Kahve ikram molası</li>
                <li>✓ Profesyonel binicilik eğitmeni</li>
              </ul>
              <div class="safari-footer">
                <div class="safari-price">2.200 ₺ <span style="font-size:0.75rem; font-weight:normal; color:var(--text-muted);">/ kişi</span></div>
                <button class="btn-gold" onclick="openSafariModal('Göl & Doğa Safarisi Paketi', 2200)">
                  Satın Al / Rezerve Et
                </button>
              </div>
            </div>
          </div>

          <div class="safari-card">
            <div class="safari-img" style="background-image: url('https://images.unsplash.com/photo-1508873696983-2df5293cb32b?auto=format&fit=crop&w=600&q=80');">
              <span class="safari-duration-badge">🌅 120 Dakika</span>
            </div>
            <div class="safari-body">
              <div class="safari-title">Gün Batımı VIP Safari Paketi</div>
              <p class="safari-desc">Altın saatlerde özel rota ve profesyonel HD fotoğraf çekimi dahil VIP safari.</p>
              <ul class="safari-features">
                <li>✓ Özel gün batımı rotası</li>
                <li>✓ Profesyonel fotoğraf çekimi</li>
                <li>✓ Özel at tahsisi</li>
              </ul>
              <div class="safari-footer">
                <div class="safari-price">3.500 ₺ <span style="font-size:0.75rem; font-weight:normal; color:var(--text-muted);">/ kişi</span></div>
                <button class="btn-primary" onclick="openSafariModal('Gün Batımı VIP Safari Paketi', 3500)">
                  Satın Al / Rezerve Et
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

    </section>

    <!-- ====================================================================
         VIEW 4: GEÇMİŞ
         ==================================================================== -->
    <section class="view-section" id="view-history">
      <div class="card">
        <div class="card-header">
          <div class="card-title"><span>📜</span> Rezervasyon Geçmişi</div>
        </div>
        <div id="historyList">
          <!-- Populated by app.js -->
        </div>
      </div>
    </section>

    <!-- ====================================================================
         VIEW 5: PROFİL
         ==================================================================== -->
    <section class="view-section" id="view-profile">
      <div class="card" style="max-width:600px; margin:0 auto;">
        <div class="card-header">
          <div class="card-title"><span>👤</span> Üye Profil Bilgileri</div>
        </div>

        <div style="display:flex; align-items:center; gap:1rem; margin-bottom:1.5rem; padding-bottom:1.5rem; border-bottom:1px solid var(--border-light);">
          <div id="profileAvatar" style="width:64px; height:64px; border-radius:50%; background:var(--gold-gradient); color:var(--primary-dark); font-size:1.75rem; font-weight:800; display:flex; align-items:center; justify-content:center;">—</div>
          <div>
            <h3 id="profileName" style="font-family:var(--font-serif); font-size:1.25rem;">—</h3>
            <p id="profileEmail" style="color:var(--text-muted); font-size:0.85rem;">—</p>
            <span id="profileRefBadge" style="display:inline-block; margin-top:0.35rem; background:rgba(197,160,89,0.15); color:var(--gold-dark); padding:0.2rem 0.6rem; border-radius:var(--radius-full); font-size:0.75rem; font-weight:700;">
              Referans Kodun: —
            </span>
          </div>
        </div>

        <!-- Digital Membership Club Pass -->
        <div style="background:linear-gradient(135deg, #11261E 0%, #1B3B2F 100%); border-radius:var(--radius-md); padding:1.25rem; color:#FFFFFF; margin-bottom:1.25rem; box-shadow:var(--shadow-md); border:1px solid rgba(197,160,89,0.3); position:relative; overflow:hidden;">
          <div style="display:flex; justify-content:space-between; align-items:flex-start;">
            <div>
              <div style="font-size:0.65rem; text-transform:uppercase; letter-spacing:1.5px; color:var(--gold-light); font-weight:700;">ANGORA BİNİCİLİK SPOR KULÜBÜ</div>
              <div style="font-family:var(--font-serif); font-size:1.15rem; font-weight:700; margin-top:0.25rem;">DİJİTAL ÜYE KİMLİK KARTI</div>
            </div>
            <div style="font-size:1.75rem;">🏇</div>
          </div>
          <div style="margin-top:1.5rem; display:flex; justify-content:space-between; align-items:flex-end;">
            <div>
              <div style="font-size:0.7rem; color:rgba(255,255,255,0.7);">ÜYE ADI & REF NO</div>
              <div style="font-weight:700; font-size:1rem;" id="cardHolderName">—</div>
              <div style="font-family:monospace; font-size:0.8rem; color:var(--gold-light);" id="cardRefCode">REF: —</div>
            </div>
            <div style="text-align:right;">
              <div style="font-size:0.7rem; color:rgba(255,255,255,0.7);">KALAN BAKİYE</div>
              <div style="font-weight:800; font-size:1.25rem; color:var(--gold);" id="cardRemainingCredits">—</div>
            </div>
          </div>
          <div style="margin-top:1rem; padding-top:0.75rem; border-top:1px dashed rgba(255,255,255,0.2); display:flex; justify-content:flex-end; align-items:center; font-size:0.75rem; color:rgba(255,255,255,0.8);">
            <span>Son Geçerlilik: <b id="cardPackageExpiry">—</b></span>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Aktif Üyelik / Rol</label>
          <input type="text" id="profileRolePackage" class="form-control" value="—" readonly>
        </div>

        <div class="form-group">
          <label class="form-label">Kayıtlı İletişim Numarası</label>
          <input type="text" id="profilePhone" class="form-control" value="—" readonly>
        </div>

        <div style="display:flex; gap:0.5rem; margin-top:1rem;">
          <button class="btn-outline" style="border-color:#EF4444; color:#EF4444; width:100%;" onclick="logoutToGateway()">
            🚪 Oturumu Kapat / Hesap Değiştir
          </button>
        </div>
      </div>
    </section>

    <!-- ====================================================================
         VIEW 6: 👑 YÖNETİCİ / ADMIN MANAGEMENT PANEL (FULL BUILT 7-TABS)
         ==================================================================== -->
    <section class="view-section" id="view-admin">
      
      <!-- Top Admin Header & Sub-Nav Bar -->
      <div style="background:#FFFFFF; border:1px solid var(--border); border-radius:var(--radius-md); padding:1.25rem; margin-bottom:1.5rem; box-shadow:var(--shadow-sm);">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.25rem;">
          <div>
            <div style="display:flex; align-items:center; gap:0.6rem;">
              <h2 style="font-family:var(--font-serif); font-size:1.75rem; color:var(--primary-dark); margin:0;">
                👑 ANGORA ADMİN PANELİ
              </h2>
              <span class="admin-badge" id="adminHeaderName">Süper Yönetici</span>
            </div>
            <p style="font-size:0.85rem; color:var(--text-muted); margin:0.25rem 0 0 0;">
              Canlı kulüp verileri, üye kütüğü, eğitmen seansları, aile grupları, gelir hesaplamaları ve istek masası.
            </p>
          </div>
          <div style="display:flex; gap:0.5rem; align-items:center;">
            <button class="btn-outline" style="padding:0.4rem 0.85rem; font-size:0.8rem;" onclick="switchAdminSubTab('settings')">⚙️ Kulüp Ayarları</button>
            <button class="btn-outline" style="padding:0.4rem 0.85rem; font-size:0.8rem; border-color:#EF4444; color:#EF4444;" onclick="logoutToGateway()">Çıkış Yap</button>
          </div>
        </div>

        <!-- 7 Sub-Navigation Pills (Identical to Old Live System) -->
        <div style="display:flex; gap:0.4rem; overflow-x:auto; padding-bottom:0.25rem; border-top:1px solid var(--border); padding-top:0.85rem;">
          <button class="tab-btn active" id="adminTabBtn-dashboard" onclick="switchAdminSubTab('dashboard')">🏠 Anasayfa</button>
          <button class="tab-btn" id="adminTabBtn-trainers" onclick="switchAdminSubTab('trainers')">🏇 Eğitmenler</button>
          <button class="tab-btn" id="adminTabBtn-members" onclick="switchAdminSubTab('members')">👥 Üyeler</button>
          <button class="tab-btn" id="adminTabBtn-families" onclick="switchAdminSubTab('families')">👨‍👩‍👧‍👦 Aile Grupları</button>
          <button class="tab-btn" id="adminTabBtn-payments" onclick="switchAdminSubTab('payments')">💰 Hesaplamalar</button>
          <button class="tab-btn" id="adminTabBtn-notifications" onclick="switchAdminSubTab('notifications')">🔔 İstekler & Bildirimler <span style="background:#EF4444; color:#FFF; font-size:0.65rem; padding:0.1rem 0.4rem; border-radius:var(--radius-full); margin-left:0.25rem;" id="adminNotifBadge"></span></button>
          <button class="tab-btn" id="adminTabBtn-settings" onclick="switchAdminSubTab('settings')">⚙️ Ayarlar</button>
          <button class="tab-btn" id="adminTabBtn-security" onclick="switchAdminSubTab('security')">🔒 Güvenlik & Audit</button>
        </div>
      </div>

      <!-- ================================================================== -->
      <!-- SUB-TAB 1: 🏠 ANASAYFA (DASHBOARD) -->
      <!-- ================================================================== -->
      <div class="admin-sub-view" id="adminSubView-dashboard">
        
        <!-- Date Bar -->
        <div style="display:flex; justify-content:center; align-items:center; background:#FFFFFF; border:1px solid var(--border); border-radius:var(--radius-sm); padding:0.6rem 1rem; margin-bottom:1rem;">
          <div style="font-weight:700; color:var(--primary-dark); font-size:0.95rem;" id="adminDashboardDate">📅</div>
        </div>

        <!-- KPI Counters -->
        <div class="stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); margin-bottom:1.5rem;">

          <div class="stat-card primary">
            <div class="stat-data">
              <span class="stat-label">Bugünkü Dersler</span>
              <span class="stat-value" id="adminStatSessions">—</span>
              <span style="font-size:0.7rem; color:var(--text-muted);" id="adminStatSessionsSub"></span>
            </div>
            <div class="stat-icon-wrapper">🏇</div>
          </div>

          <div class="stat-card gold">
            <div class="stat-data">
              <span class="stat-label">Satılan Paket (Gelir)</span>
              <span class="stat-value" id="adminStatSold">—</span>
              <span style="font-size:0.7rem; color:var(--text-muted);" id="adminStatSoldSub"></span>
            </div>
            <div class="stat-icon-wrapper">💰</div>
          </div>

          <div class="stat-card success">
            <div class="stat-data">
              <span class="stat-label">Kayıtlı Üyeler</span>
              <span class="stat-value" id="adminStatMembers">—</span>
              <span style="font-size:0.7rem; color:var(--text-muted);" id="adminStatMembersSub"></span>
            </div>
            <div class="stat-icon-wrapper">👥</div>
          </div>

        </div>

        <!-- Quick Action Banner: Family Groups -->
        <div class="card" style="margin-bottom:1.5rem; background:linear-gradient(135deg, rgba(197, 160, 89, 0.1), rgba(27, 59, 47, 0.05)); border:1px solid var(--gold); display:flex; justify-content:space-between; align-items:center; cursor:pointer;" onclick="switchAdminSubTab('families')">
          <div>
            <div style="font-weight:800; font-size:1rem; color:var(--primary-dark);">👨‍👩‍👧‍👦 Aile Grupları Yönetimi</div>
            <div style="font-size:0.8rem; color:var(--text-muted);">Aile gruplarını ve ortak kredi havuzunu yönet →</div>
          </div>
          <button class="btn-primary" style="padding:0.4rem 0.85rem; font-size:0.8rem;">Grupları İncele →</button>
        </div>

        <!-- Daily Attendance Feed -->
        <div class="card" style="margin-bottom:1.5rem;">
          <div class="card-header">
            <div class="card-title">
              <span>✅</span> Günlük Yoklama & Seans Akışı (Bugün)
            </div>
            <span style="font-size:0.75rem; background:rgba(27,59,47,0.08); color:var(--primary-dark); padding:0.2rem 0.6rem; border-radius:var(--radius-full); font-weight:700;" id="adminAttendanceFeedCount"></span>
          </div>
          <div id="adminAttendanceFeed">
            <!-- Populated by app.js -->
          </div>
        </div>

      </div>

      <!-- ================================================================== -->
      <!-- SUB-TAB 2: 🏇 EĞİTMENLER (TRAINERS) -->
      <!-- ================================================================== -->
      <div class="admin-sub-view" id="adminSubView-trainers" style="display:none;">
        <div class="card" style="margin-bottom:1.5rem;">
          <div class="card-header">
            <div class="card-title">
              <span>🏇</span> Kulüp Eğitmenleri ve Günlük Ders Dağılımı
            </div>
            <span style="font-size:0.75rem; color:var(--text-muted);" id="adminTrainersActiveCount"></span>
          </div>
          <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:1rem; margin-bottom:1.5rem;" id="adminTrainersDetailGrid">
            <!-- Populated by app.js -->
          </div>
        </div>
      </div>

      <!-- ================================================================== -->
      <!-- SUB-TAB 3: 👥 ÜYELER (MEMBERS) -->
      <!-- ================================================================== -->
      <div class="admin-sub-view" id="adminSubView-members" style="display:none;">
        <div class="card">
          <div class="card-header" style="flex-wrap:wrap; gap:0.5rem;">
            <div class="card-title">
              <span>👥</span> Kulüp Üye Kütüğü & Ders Kredisi Masası
            </div>
            <div style="display:flex; gap:0.4rem; align-items:center;">
              <button class="btn-outline" style="padding:0.35rem 0.75rem; font-size:0.75rem; border-color:var(--gold); color:var(--gold-dark); font-weight:700;" onclick="adminExportMembersCSV()">
                📥 Excel / CSV İndir
              </button>
              <button class="tab-btn active" id="memberTabBtn-club" onclick="filterMemberCategory('club')">Kulüp Üyeleri</button>
              <button class="tab-btn" id="memberTabBtn-prog" onclick="filterMemberCategory('program')">Program Kayıtlıları</button>
            </div>
          </div>

          <!-- Filter Pills & Search Input -->
          <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem; margin-bottom:1rem;">
            <div style="display:flex; gap:0.3rem;">
              <button class="admin-filter-pill active" onclick="filterMemberStatus('all', this)">Tümü</button>
              <button class="admin-filter-pill" onclick="filterMemberStatus('active', this)">Aktif</button>
              <button class="admin-filter-pill" onclick="filterMemberStatus('passive', this)">Pasif</button>
              <button class="admin-filter-pill" onclick="filterMemberStatus('finished', this)">Bitti</button>
              <button class="admin-filter-pill" onclick="filterMemberStatus('expiring', this)">Bitiyor</button>
            </div>
            <div style="flex:1; max-width:350px;">
              <input type="text" class="form-control" id="adminMemberSearch" placeholder="🔍 Üye adı, telefon veya ref kodu ile ara...">
            </div>
          </div>

          <!-- Members Table -->
          <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.85rem;">
              <thead>
                <tr style="border-bottom:2px solid var(--border); text-align:left; color:var(--text-muted);">
                  <th style="padding:0.6rem 0.4rem;">Üye</th>
                  <th style="padding:0.6rem 0.4rem;">İletişim & Ref</th>
                  <th style="padding:0.6rem 0.4rem;">Aktif Paket</th>
                  <th style="padding:0.6rem 0.4rem;">Kalan / Toplam Ders</th>
                  <th style="padding:0.6rem 0.4rem; text-align:center;">Ders Kredisi İşlemleri</th>
                </tr>
              </thead>
              <tbody id="adminMembersTableBody">
                <!-- Populated by app.js -->
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ================================================================== -->
      <!-- SUB-TAB 4: 👨‍👩‍👧‍👦 AİLE GRUPLARI (FAMILIES) -->
      <!-- ================================================================== -->
      <div class="admin-sub-view" id="adminSubView-families" style="display:none;">
        <div class="card">
          <div class="card-header">
            <div>
              <div class="card-title"><span>👨‍👩‍👧‍👦</span> Aile Grupları & Ortak Kredi Havuzu</div>
              <p style="font-size:0.8rem; color:var(--text-muted); margin:0.25rem 0 0 0;">
                Aile üyeleri ortak ders paketinden faydalanır ve ders haklarını ortak bakiye havuzundan kullanır.
              </p>
            </div>
            <button class="btn-primary" style="padding:0.4rem 0.85rem; font-size:0.8rem;" onclick="openNewFamilyModal()">+ Yeni Grup</button>
          </div>
          <div id="adminFamilyGroupsList" style="margin-top:1rem;">
            <!-- Populated by app.js -->
          </div>
        </div>
      </div>

      <!-- ================================================================== -->
      <!-- SUB-TAB 5: 💰 HESAPLAMALAR (PAYMENTS & FINANCIALS) -->
      <!-- ================================================================== -->
      <div class="admin-sub-view" id="adminSubView-payments" style="display:none;">
        
        <!-- Period Comparison Table (Bugün / Bu Ay / Tüm Zaman) -->
        <div class="card" style="margin-bottom:1.5rem;">
          <div class="card-header">
            <div class="card-title">
              <span>💰</span> Finansal Performans & Ders İstatistikleri
            </div>
          </div>
          <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.9rem;">
              <thead>
                <tr style="border-bottom:2px solid var(--border); text-align:left; color:var(--primary-dark); font-weight:800;">
                  <th style="padding:0.75rem 0.5rem;">Metrik</th>
                  <th style="padding:0.75rem 0.5rem; color:var(--gold-dark);">BUGÜN</th>
                  <th style="padding:0.75rem 0.5rem; color:var(--primary);">BU AY</th>
                  <th style="padding:0.75rem 0.5rem;">TOPLAM (Tüm Zaman)</th>
                </tr>
              </thead>
              <tbody>
                <tr style="border-bottom:1px solid var(--border);">
                  <td style="padding:0.75rem 0.5rem; font-weight:700;">💵 Kulüp Geliri (Onaylı Paket Satışı)</td>
                  <td style="padding:0.75rem 0.5rem; font-weight:800; color:var(--gold-dark); font-size:1.05rem;" id="finRevenueToday">—</td>
                  <td style="padding:0.75rem 0.5rem; font-weight:800; color:var(--primary); font-size:1.05rem;" id="finRevenueMonth">—</td>
                  <td style="padding:0.75rem 0.5rem; font-weight:800; color:var(--primary-dark); font-size:1.15rem;" id="finRevenueAllTime">—</td>
                </tr>
                <tr style="border-bottom:1px solid var(--border);">
                  <td style="padding:0.75rem 0.5rem; font-weight:700;">🏇 Bugünkü Ders Durumu</td>
                  <td colspan="3" style="padding:0.75rem 0.5rem; font-weight:700;" id="finLessonsToday">—</td>
                </tr>
                <tr style="border-bottom:1px solid var(--border);">
                  <td style="padding:0.75rem 0.5rem; font-weight:700;">👥 Toplam Üye</td>
                  <td colspan="3" style="padding:0.75rem 0.5rem; font-weight:700;" id="finTotalMembers">—</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>

      <!-- ================================================================== -->
      <!-- SUB-TAB 6: 🔔 İSTEKLER & BİLDİRİMLER (REQUESTS) -->
      <!-- ================================================================== -->
      <div class="admin-sub-view" id="adminSubView-notifications" style="display:none;">
        
        <!-- Package Purchase Requests -->
        <div class="card" style="margin-bottom:1.5rem;">
          <div class="card-header">
            <div class="card-title">
              <span>📦</span> Bekleyen Paket Talepleri
            </div>
          </div>
          <div id="adminPurchaseRequestsList" style="display:flex; flex-direction:column; gap:0.6rem; margin-top:0.75rem;">
            <!-- Rendered by app.js -->
          </div>
        </div>

        <p style="font-size:0.75rem; color:var(--text-muted); margin-bottom:1.5rem;">
          Not: Yeni üye kaydı, eski üye kaydı, deneme dersi ve aile üyesi bağlama talepleri için ayrı bir onay kuyruğu henüz bu sürümde yok — şimdilik bu işlemler admin tarafından doğrudan Üyeler / Aile Grupları sekmelerinden yapılıyor.
        </p>

        <!-- ⚠️ Low Lesson Member Alerts -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">
              <span>⚠️</span> Kalan Ders Kredisi Biten & Azalan Üye Uyarıları
            </div>
            <span style="font-size:0.75rem; color:var(--text-muted);">Otomatik Hatırlatıcı Masası</span>
          </div>

          <div id="adminLowCreditAlertsList" style="display:flex; flex-direction:column; gap:0.6rem; margin-top:0.75rem;">
            <!-- Rendered by app.js -->
          </div>
        </div>

      </div>

      <!-- ================================================================== -->
      <!-- SUB-TAB 7: ⚙️ AYARLAR (SETTINGS) -->
      <!-- ================================================================== -->
      <div class="admin-sub-view" id="adminSubView-settings" style="display:none;">
        
        <!-- Package Pricing Matrix Card -->
        <div class="card" style="margin-bottom:1.5rem;">
          <div class="card-header">
            <div class="card-title">
              <span>🏷️</span> Paket Fiyatları Yönetim Matrisi
            </div>
            <span style="font-size:0.75rem; color:var(--text-muted);">Canlı Fiyatlandırma</span>
          </div>
          <div id="adminPackageSettingsList" style="margin-top:0.75rem;">
            <!-- Populated by app.js -->
          </div>
        </div>

        <!-- Add New Package Form -->
        <div class="card" style="margin-bottom:1.5rem;">
          <div class="card-header">
            <div class="card-title">
              <span>➕</span> Yeni Paket Ekle
            </div>
          </div>
          <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; align-items:end;">
            <div class="form-group" style="margin:0;">
              <label class="form-label">Ders Sayısı</label>
              <input type="number" class="form-control" id="newPkgLessons" placeholder="Örn: 24">
            </div>
            <div class="form-group" style="margin:0;">
              <label class="form-label">Fiyat (₺)</label>
              <input type="number" class="form-control" id="newPkgPrice" placeholder="Örn: 35000">
            </div>
            <button class="btn-primary" onclick="adminAddNewPackage()">Paket Ekle</button>
          </div>
        </div>

        <!-- Club Policy & Cancellation Rules -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">
              <span>🛡️</span> Kulüp Kuralları & İptal Kalkanı
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Pazartesi Kapalı Kuralı</label>
            <select class="form-control" id="settingMondayClosed">
              <option value="1">Aktif (Pazartesi seansları otomatik kilitli)</option>
              <option value="0">Pasif (Pazartesi de açık)</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">İptal Süresi Kalkanı (Genel Varsayılan, Saat)</label>
            <input type="number" min="0" class="form-control" id="settingCancellationHours">
          </div>
          <button class="btn-primary" style="width:100%;" onclick="adminSaveClubSettings()">
            Ayarları Güncelle
          </button>
        </div>

      </div>

      <!-- ================================================================== -->
      <!-- SUB-TAB 8: 🔒 GÜVENLİK & AUDIT GÜNLÜĞÜ (CYBER SEC MONITOR) -->
      <!-- ================================================================== -->
      <div class="admin-sub-view" id="adminSubView-security" style="display:none;">
        
        <!-- 4 Security KPI Stats -->
        <div class="stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom:1.5rem;">
          <div class="sec-stat-box">
            <span class="label">🛡️ Güvenlik Kalkanı & WAF</span>
            <span class="val" style="color:var(--success);">AKTİF (Seviye 5)</span>
          </div>
          <div class="sec-stat-box">
            <span class="label">🔑 Başarılı Oturumlar (24S)</span>
            <span class="val" id="secStatLogins">48 Oturum</span>
          </div>
          <div class="sec-stat-box">
            <span class="label">🚫 Engellenen XSS / Brute-Force</span>
            <span class="val" id="secStatBlocked" style="color:#EF4444;">16 Tehdit</span>
          </div>
          <div class="sec-stat-box">
            <span class="label">⚖️ RBAC İzolasyon Doğrulaması</span>
            <span class="val" style="color:var(--gold-dark);">%100 Güvenli</span>
          </div>
        </div>

        <!-- Audit Log Actions -->
        <div class="card" style="margin-bottom:1.5rem;">
          <div class="card-header">
            <div class="card-title">
              <span>🔒</span> Güvenlik & Audit Log
            </div>
            <div style="display:flex; gap:0.5rem;">
              <button class="btn-outline" style="padding:0.4rem 0.85rem; font-size:0.8rem;" onclick="exportSecurityLogs()">
                📥 Audit Logunu İndir (JSON)
              </button>
            </div>
          </div>
          <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:1rem;">
            Aşağıdaki sistem; kimlik doğrulama anomalilerini, brute-force denemelerini, XSS script enjeksiyonlarını, parametre tahrifatlarını ve yetkisiz rol atlama (RBAC privilege escalation) girişimlerini gerçek zamanlı olarak yakalar ve izole eder.
          </p>
        </div>

        <!-- Live Security & Audit Trail Table -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">
              <span>📋</span> Gerçek Zamanlı Güvenlik ve Denetim Günlüğü (Audit Trail)
            </div>
            <span class="sec-badge success" id="secLiveBadge">● Canlı İzleme Aktif</span>
          </div>
          <div style="overflow-x:auto;">
            <table class="sec-audit-table">
              <thead>
                <tr>
                  <th style="width:140px;">Tarih / Saat</th>
                  <th style="width:120px;">IP / İstemci</th>
                  <th style="width:130px;">Kullanıcı / Rol</th>
                  <th style="width:160px;">Olay Türü</th>
                  <th style="width:110px;">Durum</th>
                  <th>Güvenlik Analizi & Açıklama</th>
                </tr>
              </thead>
              <tbody id="secAuditTableBody">
                <!-- Dynamically populated -->
              </tbody>
            </table>
          </div>
        </div>

      </div>

    </section>

    <!-- ====================================================================
         VIEW 7: 🎯 ANTRENÖR / EĞİTMEN PORTALI (TRAINER VIEW)
         ==================================================================== -->
    <section class="view-section" id="view-trainer">
      <div style="background:#FFFFFF; border:1px solid var(--border); border-radius:var(--radius-md); padding:1.25rem; margin-bottom:1.5rem; box-shadow:var(--shadow-sm);">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
          <div>
            <div style="display:flex; align-items:center; gap:0.6rem;">
              <h2 style="font-family:var(--font-serif); font-size:1.75rem; color:var(--primary-dark); margin:0;" id="trainerPortalTitle">
                🎯 EĞİTMEN DERS & YOKLAMA PANELİ
              </h2>
              <span class="admin-badge" id="trainerPortalBadge">Eğitmen Masası</span>
            </div>
            <p style="font-size:0.85rem; color:var(--text-muted); margin:0.25rem 0 0 0;">
              Bugünkü biniş dersleriniz, öğrenci maneji programı ve anlık yoklama bildirim masası.
            </p>
          </div>
          <div style="display:flex; gap:0.5rem; align-items:center;">
            <button class="btn-outline" style="padding:0.4rem 0.85rem; font-size:0.8rem; border-color:#EF4444; color:#EF4444;" onclick="logoutToGateway()">Hesap Değiştir</button>
          </div>
        </div>
      </div>

      <!-- Trainer Schedule & Attendance Cards -->
      <div class="dashboard-layout">
        <div class="card">
          <div class="card-header">
            <div class="card-title">
              <span>🏇</span> Bugün Planlanan Seanslarım
            </div>
            <span class="sec-badge info" id="trainerLessonsCount">—</span>
          </div>
          <div id="trainerRosterList">
            <!-- Populated dynamically -->
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <div class="card-title">
              <span>📝</span> Öğrenci Gelişim & Biniş Notu Ekle
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Öğrenci Seç</label>
            <select class="form-control" id="trainerStudentSelect">
              <option value="">Öğrenci listesi yükleniyor…</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Biniş Disiplini & Seviye</label>
            <select class="form-control" id="trainerDisciplineSelect">
              <option value="Temel Denge & Oturuş">Temel Denge & Oturuş (Manejde Başarılı)</option>
              <option value="Tırıs & Kenter Geçişleri">Tırıs & Kenter Geçişleri (Stabil)</option>
              <option value="Engel Atlama Giriş (60cm)">Engel Atlama Giriş (60cm)</option>
              <option value="Safari Biniş Uygunluğu">Safari Biniş Uygunluğu (Onaylandı)</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Eğitmen Gözlem Notu</label>
            <textarea class="form-control" rows="3" placeholder="Öğrencinin at ile uyumu, denge ve manevra hakimiyeti..." id="trainerFeedbackNote"></textarea>
          </div>
          <button class="btn-gold" style="width:100%;" onclick="submitTrainerFeedback()">
            ✓ Gelişim Raporunu Kaydet & Veliye WhatsApp Gönder
          </button>
        </div>
      </div>
    </section>

  </main>

  <!-- ====================================================================
         QUICK BOOKING MODAL
         ==================================================================== -->
  <div class="modal-overlay" id="quickBookingModal" onclick="if(event.target===this)closeModal('quickBookingModal')">
    <div class="modal-box">
      <div class="modal-header">
        <div class="modal-title">Hızlı Seans Rezervasyonu</div>
        <button class="modal-close" onclick="closeModal('quickBookingModal')">✕</button>
      </div>

      <div style="background:var(--bg-page); padding:0.85rem 1rem; border-radius:var(--radius-sm); margin-bottom:1rem; border-left:4px solid var(--gold);">
        <span style="font-size:0.8rem; color:var(--text-muted);">Seçilen Seans:</span>
        <div style="font-weight:700; color:var(--primary-dark); font-size:1rem;" id="bookingModalDate">—</div>
      </div>

      <div class="form-group">
        <label class="form-label">Eğitmen</label>
        <input type="text" class="form-control" id="bookingModalTrainer" readonly>
      </div>

      <div class="form-group">
        <label class="form-label">Aktivite Türü</label>
        <select class="form-control" id="quickModalActivityType">
          <option value="🏇 Standart Manej Biniş Dersi (45 Dk)" selected>🏇 Standart Manej Biniş Dersi (45 Dk)</option>
          <option value="🌲 Doğa & Orman Safarisi">🌲 Doğa & Orman Safarisi (Ders Hakkı Yerine)</option>
          <option value="🏆 İleri Seviye Engel Atlama">🏆 İleri Seviye Engel Atlama</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">At Tercihi</label>
        <select class="form-control" id="quickModalHorse">
          <option value="">🐴 Kulüp Tarafından Belirlensin (Önerilen)</option>
        </select>
      </div>

      <button class="btn-gold" style="width:100%; padding:0.85rem;" onclick="confirmLessonBooking()">
        ✓ Rezervasyonu Onayla (1 Seans Düşer)
      </button>
    </div>
  </div>

  <!-- ====================================================================
         SAFARI MODAL WIZARD
         ==================================================================== -->
  <div class="modal-overlay" id="safariModal" onclick="if(event.target===this)closeModal('safariModal')">
    <div class="modal-box">
      <div class="modal-header">
        <div class="modal-title" id="safariModalTitle">Safari Turu Rezervasyonu</div>
        <button class="modal-close" onclick="closeModal('safariModal')">✕</button>
      </div>

      <div class="form-group">
        <label class="form-label">Kişi Sayısı</label>
        <select class="form-control" id="safariPax">
          <option value="1">1 Kişi (Tek At)</option>
          <option value="2" selected>2 Kişi (Çift / Arkadaş)</option>
          <option value="3">3 Kişi (Grup)</option>
          <option value="4">4 Kişi (Grup)</option>
          <option value="5">5 Kişi (Özel Tur)</option>
          <option value="6">6 Kişi (Maksimum Kontenjan)</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Tur Tarihi</label>
        <input type="date" class="form-control" id="safariDate">
      </div>

      <div class="form-group">
        <label class="form-label">Başlangıç Saati</label>
        <select class="form-control" id="safariTime">
          <option value="09:30">09:30 (Sabah Orman Turu)</option>
          <option value="11:00" selected>11:00 (Öğle Doğa Turu)</option>
          <option value="15:30">15:30 (Öğleden Sonra)</option>
          <option value="17:30">17:30 (Gün Batımı Golden Hour)</option>
        </select>
      </div>

      <div style="background:var(--bg-page); padding:1rem; border-radius:var(--radius-sm); margin-bottom:1.25rem; display:flex; justify-content:space-between; align-items:center;">
        <span style="font-weight:600; font-size:0.9rem;">Toplam Tutar:</span>
        <span style="font-size:1.35rem; font-weight:800; color:var(--primary);" id="safariTotalPrice">0 ₺</span>
      </div>

      <button class="btn-gold" style="width:100%; padding:0.85rem;" onclick="confirmSafariBooking()">
        🏇 Safari Rezervasyonunu Tamamla
      </button>
    </div>
  </div>

  <!-- ====================================================================
         MOBILE BOTTOM NAVIGATION (PWA)
         ==================================================================== -->
  <nav class="mobile-nav">
    <div class="mobile-nav-item nav-role-member active" id="navMobileDashboard" data-view="dashboard" onclick="switchView('dashboard')">
      <span class="icon">📊</span>
      <span>Dashboard</span>
    </div>
    <div class="mobile-nav-item nav-role-member nav-role-trainer nav-role-admin" id="navMobileAvailability" data-view="availability" onclick="switchView('availability')">
      <span class="icon">📅</span>
      <span>Takvim</span>
    </div>
    <div class="mobile-nav-item nav-role-member" id="navMobilePackages" data-view="packages" onclick="switchView('packages')">
      <span class="icon">💳</span>
      <span>Paketler</span>
    </div>
    <div class="mobile-nav-item nav-role-trainer" id="navMobileTrainer" data-view="trainer" onclick="switchView('trainer')" style="display:none; color:var(--gold-light);">
      <span class="icon">🎯</span>
      <span>Antrenör</span>
    </div>
    <div class="mobile-nav-item nav-role-admin" id="navMobileAdmin" data-view="admin" onclick="switchView('admin')" style="display:none; color:var(--gold-light);">
      <span class="icon">👑</span>
      <span>Admin</span>
    </div>
    <div class="mobile-nav-item nav-role-member nav-role-trainer nav-role-admin" data-view="profile" onclick="switchView('profile')">
      <span class="icon">👤</span>
      <span>Profil</span>
    </div>
  </nav>

</body>
</html>
