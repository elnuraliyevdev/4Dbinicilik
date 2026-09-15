<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hesap Kurulumu — Angora Binicilik</title>
  @vite(['resources/css/app.css'])
</head>
<body style="align-items:center; justify-content:center; min-height:100vh; padding:1.25rem;">

  <div class="login-card-container" style="max-width:440px;">
    <div class="login-brand-header">
      <div class="login-brand-icon">🏇</div>
      <div class="login-brand-title">Angora Binicilik</div>
      <div class="login-brand-subtitle">HESAP KURULUMU</div>
    </div>

    @if($alreadyClaimed)
      <div style="text-align:center; padding:1rem 0;">
        <p style="color:var(--text-main); margin-bottom:1.25rem;">Bu hesap için şifre zaten oluşturulmuş. Doğrudan giriş yapabilirsiniz.</p>
        <a href="/" class="btn-gold" style="display:inline-block; text-decoration:none; padding:0.75rem 1.5rem;">Giriş Sayfasına Git →</a>
      </div>
    @else
      <p style="color:var(--text-muted); text-align:center; margin-bottom:1.5rem;">
        Merhaba <strong style="color:var(--text-main);">{{ $user->name }}</strong>, kulüp üyeliğinizi aktifleştirmek için bir şifre belirleyin.
      </p>

      <div id="claimFormError" style="display:none; color:#DC2626; font-size:0.85rem; margin-bottom:0.75rem;"></div>
      <div id="claimFormSuccess" style="display:none; text-align:center; padding:1rem 0;">
        <p style="color:var(--text-main); margin-bottom:1.25rem;">✓ Hesabınız aktifleşti, şimdi giriş yapabilirsiniz.</p>
        <a href="/" class="btn-gold" style="display:inline-block; text-decoration:none; padding:0.75rem 1.5rem;">Giriş Sayfasına Git →</a>
      </div>

      <form id="claimForm">
        @if($needsContactInfo)
          <div class="form-group">
            <label class="form-label">Telefon Numarası</label>
            <input type="text" class="form-control" id="claimPhone" placeholder="05551234567">
          </div>
          <div class="form-group">
            <label class="form-label">E-Posta (opsiyonel)</label>
            <input type="email" class="form-control" id="claimEmail" placeholder="ornek@eposta.com">
          </div>
        @endif

        <div class="form-group">
          <label class="form-label">Şifre</label>
          <input type="password" class="form-control" id="claimPassword" placeholder="En az 8 karakter" required>
        </div>
        <div class="form-group">
          <label class="form-label">Şifre (Tekrar)</label>
          <input type="password" class="form-control" id="claimPasswordConfirm" placeholder="Şifrenizi tekrar girin" required>
        </div>

        <button type="submit" class="btn-gold" style="width:100%; padding:0.85rem;">Hesabımı Aktifleştir</button>
      </form>
    @endif
  </div>

  <script>
    document.getElementById('claimForm')?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const errorBox = document.getElementById('claimFormError');
      errorBox.style.display = 'none';

      const password = document.getElementById('claimPassword').value;
      const passwordConfirm = document.getElementById('claimPasswordConfirm').value;
      if (password.length < 8) {
        errorBox.textContent = 'Şifre en az 8 karakter olmalıdır.';
        errorBox.style.display = 'block';
        return;
      }
      if (password !== passwordConfirm) {
        errorBox.textContent = 'Şifreler eşleşmiyor.';
        errorBox.style.display = 'block';
        return;
      }

      const body = { password, password_confirmation: passwordConfirm };
      const phoneEl = document.getElementById('claimPhone');
      const emailEl = document.getElementById('claimEmail');
      if (phoneEl?.value) body.phone = phoneEl.value;
      if (emailEl?.value) body.email = emailEl.value;

      try {
        const res = await fetch(window.location.pathname + window.location.search, {
          method: 'POST',
          headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
          body: JSON.stringify(body),
        });
        const data = await res.json().catch(() => null);
        if (!res.ok) {
          const firstError = data?.errors ? Object.values(data.errors)[0]?.[0] : null;
          throw new Error(firstError || data?.message || 'Bir hata oluştu.');
        }
        document.getElementById('claimForm').style.display = 'none';
        document.getElementById('claimFormSuccess').style.display = 'block';
      } catch (err) {
        errorBox.textContent = err.message;
        errorBox.style.display = 'block';
      }
    });
  </script>
</body>
</html>
