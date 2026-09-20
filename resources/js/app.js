/**
 * 4D BİNİCİLİK — FRONTEND (API-BACKED)
 *
 * This file replaces the old localStorage/DEFAULT_STATE prototype. Every
 * piece of data shown here comes from the real Laravel backend (Phases 1-4)
 * over same-origin fetch() calls with the session cookie + CSRF token — there
 * is no client-side "auth" or fake data left; the server is authoritative for
 * everything (who you are, what you can see, what a booking/cancellation
 * actually does to your credit balance).
 */

// ─── API CLIENT ───────────────────────────────────────────────────────────────
const API = {
  csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
  },
  async request(method, url, body) {
    const opts = {
      method,
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': this.csrfToken(),
      },
    };
    if (body !== undefined) {
      opts.headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(body);
    }

    const res = await fetch(url, opts);
    let data = null;
    try {
      data = await res.json();
    } catch (e) {
      /* empty body, e.g. some 204s */
    }

    if (data && data.csrf_token) {
      const meta = document.querySelector('meta[name="csrf-token"]');
      if (meta) meta.content = data.csrf_token;
    }

    if (!res.ok) {
      const err = new Error(data?.message || 'İstek başarısız oldu.');
      err.status = res.status;
      err.data = data;
      throw err;
    }

    return data;
  },
  get(url) {
    return this.request('GET', url);
  },
  post(url, body) {
    return this.request('POST', url, body ?? {});
  },
  patch(url, body) {
    return this.request('PATCH', url, body ?? {});
  },
  put(url, body) {
    return this.request('PUT', url, body ?? {});
  },
  delete(url) {
    return this.request('DELETE', url);
  },
};

// Club WhatsApp number — loaded from /me on boot, falls back until then.
let CLUB_WHATSAPP_NUMBER = '905551234567';

// Current session snapshot — refreshed via bootstrapSession(); never persisted
// to localStorage (the server session cookie is the actual source of truth).
let session = { authenticated: false, user: null, club: null };

// ─── SESSION / AUTH ────────────────────────────────────────────────────────────
async function bootstrapSession() {
  try {
    const data = await API.get('/me');
    session = data;
    if (data.club?.whatsapp_number) CLUB_WHATSAPP_NUMBER = data.club.whatsapp_number;
  } catch (e) {
    session = { authenticated: false, user: null, club: null };
  }

  checkGatewayAuth();

  if (session.authenticated) {
    updateRoleUI();
    renderUserProfile();
    await loadCurrentView();
  }
}

function checkGatewayAuth() {
  const gwScreen = document.getElementById('loginGatewayScreen');
  if (session.authenticated) {
    document.body.classList.remove('auth-locked');
    document.body.classList.add('app-unlocked');
    if (gwScreen) gwScreen.style.display = 'none';
  } else {
    document.body.classList.add('auth-locked');
    document.body.classList.remove('app-unlocked');
    if (gwScreen) gwScreen.style.display = 'flex';
  }
}

function switchGatewayTab(tabName) {
  document.querySelectorAll('[id^="gatewayTabBtn-"]').forEach((btn) => btn.classList.remove('active'));
  document.querySelectorAll('[id^="gatewayPanel-"]').forEach((panel) => (panel.style.display = 'none'));

  document.getElementById(`gatewayTabBtn-${tabName}`)?.classList.add('active');
  const panel = document.getElementById(`gatewayPanel-${tabName}`);
  if (panel) panel.style.display = 'block';
}

function showGatewayError(message) {
  const el = document.getElementById('gatewayFormError');
  if (!el) return;
  el.textContent = message;
  el.style.display = message ? 'block' : 'none';
}

async function submitGatewayLogin(role) {
  showGatewayError('');

  try {
    if (role === 'member') {
      const identifier = (document.getElementById('gwMemberPhone').value || '').trim();
      const password = document.getElementById('gwMemberPass').value || '';
      await API.post('/login/member', { identifier, password });
    } else if (role === 'admin') {
      const email = (document.getElementById('gwAdminUser').value || '').trim();
      const password = document.getElementById('gwAdminPass').value || '';
      const pin = document.getElementById('gwAdminPin').value || '';
      await API.post('/login/admin', { email, password, pin });
    } else if (role === 'trainer') {
      const trainer_id = document.getElementById('gwTrainerSelect').value;
      const pin = document.getElementById('gwTrainerPass').value || '';
      await API.post('/login/trainer', { trainer_id, pin });
    }
  } catch (e) {
    showGatewayError(e.message);
    return;
  }

  await bootstrapSession();
  showToast(`🏇 Hoş geldiniz, ${session.user?.name || ''}!`);
  const defaultView = session.user?.role === 'admin' ? 'admin' : session.user?.role === 'trainer' ? 'trainer' : 'dashboard';
  switchView(defaultView);
}

async function logoutToGateway() {
  closeModal('quickBookingModal');
  closeModal('safariModal');
  try {
    await API.post('/logout');
  } catch (e) {
    /* best-effort — still lock the UI client-side even if the request fails */
  }
  session = { authenticated: false, user: null, club: null };
  checkGatewayAuth();
  showToast('🚪 Oturumunuz güvenli şekilde kapatıldı.');
}

async function loadTrainerLoginOptions() {
  const select = document.getElementById('gwTrainerSelect');
  if (!select) return;
  try {
    const data = await API.get('/trainer-options');
    select.innerHTML = data.trainers
      .map((t) => `<option value="${t.id}">${escapeHtml(t.name)}${t.title ? ' — ' + escapeHtml(t.title) : ''}</option>`)
      .join('');
  } catch (e) {
    select.innerHTML = '<option value="">Antrenör listesi yüklenemedi</option>';
  }
}

// ─── ROLE / NAV UI ─────────────────────────────────────────────────────────────
function updateRoleUI() {
  const badgeBtn = document.getElementById('roleBadgeBtn');
  const role = session.user?.role || 'member';

  // Toggle role-scoped navigation elements (both desktop and mobile)
  document.querySelectorAll('.nav-role-member').forEach((el) => {
    el.style.display = role === 'member' ? 'flex' : 'none';
  });
  document.querySelectorAll('.nav-role-trainer').forEach((el) => {
    el.style.display = role === 'trainer' ? 'flex' : 'none';
  });
  document.querySelectorAll('.nav-role-admin').forEach((el) => {
    el.style.display = role === 'admin' ? 'flex' : 'none';
  });

  // Shared elements (e.g. availability calendar and profile are accessible)
  const availDesktop = document.getElementById('navItemAvailability');
  const availMobile = document.getElementById('navMobileAvailability');
  if (availDesktop) availDesktop.style.display = 'flex';
  if (availMobile) availMobile.style.display = 'flex';

  if (role === 'admin') {
    if (badgeBtn) { badgeBtn.innerHTML = '👑 Admin Modu'; badgeBtn.style.background = '#FEF3C7'; badgeBtn.style.color = '#B45309'; }
    if (currentView === 'dashboard' || currentView === 'trainer') {
      switchView('admin');
    }
  } else if (role === 'trainer') {
    if (badgeBtn) { badgeBtn.innerHTML = '🎯 Eğitmen Modu'; badgeBtn.style.background = '#EFF6FF'; badgeBtn.style.color = '#1D4ED8'; }
    if (currentView === 'dashboard' || currentView === 'admin' || currentView === 'packages' || currentView === 'history') {
      switchView('trainer');
    }
  } else {
    if (badgeBtn) { badgeBtn.innerHTML = '👤 Üye Portalı'; badgeBtn.style.background = 'var(--gold-gradient)'; badgeBtn.style.color = 'var(--primary-dark)'; }
    if (currentView === 'admin' || currentView === 'trainer') {
      switchView('dashboard');
    }
  }
}

// ─── VIEW NAVIGATION ───────────────────────────────────────────────────────────
let currentView = 'dashboard';

function switchView(viewName) {
  const role = session.user?.role || 'member';

  // Strict RBAC View Guards
  if (viewName === 'admin' && role !== 'admin') {
    showToast('⛔ Bu panel için yönetici yetkisi gereklidir.', 'danger');
    return;
  }
  if (viewName === 'trainer' && !['trainer', 'admin'].includes(role)) {
    showToast('⛔ Bu panel yalnızca antrenörlere açıktır.', 'danger');
    return;
  }
  if (role === 'trainer' && ['packages', 'dashboard', 'history'].includes(viewName)) {
    showToast('⛔ Antrenör hesabı bu alana erişemez.', 'warning');
    return;
  }

  currentView = viewName;

  document.querySelectorAll('.view-section').forEach((el) => el.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach((el) => el.classList.remove('active'));
  document.querySelectorAll('.mobile-nav-item').forEach((el) => el.classList.remove('active'));

  document.getElementById(`view-${viewName}`)?.classList.add('active');
  document.querySelectorAll(`.nav-item[data-view="${viewName}"]`).forEach((el) => el.classList.add('active'));
  document.querySelectorAll(`.mobile-nav-item[data-view="${viewName}"]`).forEach((el) => el.classList.add('active'));

  window.scrollTo({ top: 0, behavior: 'smooth' });
  loadCurrentView();
}

function loadCurrentView() {
  if (!session.authenticated) return;

  switch (currentView) {
    case 'dashboard':
      return loadDashboard();
    case 'availability':
      return loadAvailability(selectedDate);
    case 'packages':
      return loadPackagesAndSafari();
    case 'history':
      return loadHistory();
    case 'profile':
      return renderUserProfile();
    case 'admin':
      return loadAdminSubTab(currentAdminSubTab);
    case 'trainer':
      return loadTrainerSchedule();
    default:
      return null;
  }
}

// ─── MEMBER: DASHBOARD ──────────────────────────────────────────────────────────
let cachedReservations = [];

async function loadDashboard() {
  renderStats();
  try {
    const data = await API.get('/member/reservations');
    cachedReservations = data.reservations || [];
  } catch (e) {
    cachedReservations = [];
  }
  renderActiveLessons();
  renderCalendar();
}

function renderStats() {
  const u = session.user || {};
  const setText = (id, val) => {
    const el = document.getElementById(id);
    if (el) el.innerText = val;
  };
  setText('statTotal', u.total_lessons ?? 0);
  setText('statUsed', u.used_lessons ?? 0);
  setText('statRemaining', u.remaining_lessons ?? 0);
  setText('statPending', u.pending_lessons ?? 0);
}

function renderActiveLessons() {
  const container = document.getElementById('activeLessonsList');
  if (!container) return;

  const activeItems = cachedReservations.filter((r) => r.status === 'confirmed');

  if (activeItems.length === 0) {
    container.innerHTML = `
      <div style="text-align:center; padding: 2rem; color: var(--text-muted);">
        <p style="font-size: 2rem; margin-bottom: 0.5rem;">🏇</p>
        <p>Aktif bir rezervasyonunuz bulunmuyor.</p>
        <button class="btn-primary" style="margin-top: 1rem;" onclick="switchView('availability')">Eğitmen Takviminden Randevu Al</button>
      </div>
    `;
    return;
  }

  container.innerHTML = activeItems
    .map((item) => {
      const trainerName = item.trainer?.user?.name || item.safari_tour?.name || '—';
      return `
      <div class="lesson-card">
        <div class="lesson-header">
          <div class="lesson-title">${escapeHtml(item.activity_label)}</div>
          <span class="lesson-badge badge-approved">Onaylandı</span>
        </div>
        <div class="lesson-details">
          <div class="lesson-detail-item">📅 <b>Tarih:</b> ${item.date} (${item.time})</div>
          <div class="lesson-detail-item">👤 <b>Antrenör:</b> ${escapeHtml(trainerName)}</div>
        </div>
        <div class="cancel-action-bar">
          <div style="display:flex; gap:0.4rem; align-items:center;">
            <button onclick="sendWhatsAppNotification('cancel_query', '${escapeHtml(item.activity_label)}', '${item.date}', '${item.time}', '${escapeHtml(trainerName)}')" style="background:#25D366; color:#fff; border:none; padding:0.3rem 0.6rem; border-radius:var(--radius-sm); font-size:0.75rem; font-weight:600; cursor:pointer;">
              💬 WhatsApp
            </button>
            <button class="btn-cancel" onclick="handleFreeCancellation(${item.id})">Ücretsiz İptal Dene</button>
            <button class="btn-cancel" style="background:#FEE2E2; color:#B91C1C;" onclick="handleLateCancellation(${item.id})">Geç İptal</button>
          </div>
        </div>
      </div>
    `;
    })
    .join('');
}

async function handleFreeCancellation(id) {
  if (!confirm('Bu dersi ücretsiz iptal etmek istediğinize emin misiniz?')) return;

  try {
    const res = await API.delete(`/member/reservations/${id}`);
    showToast(res.message);
    await bootstrapSession();
    loadDashboard();
  } catch (e) {
    if (e.data?.late_cancel_required) {
      showToast(e.message + ' "Geç İptal" butonunu kullanın.', 'danger');
    } else {
      showToast(e.message, 'danger');
    }
  }
}

async function handleLateCancellation(id) {
  if (!confirm('DİKKAT: Bu iptal geç iptal kuralına tabi, ders hakkınız iade edilmeyecek. Onaylıyor musunuz?')) return;

  try {
    const res = await API.post(`/member/reservations/${id}/late-cancel`);
    showToast(res.message, 'danger');
    await bootstrapSession();
    loadDashboard();
  } catch (e) {
    showToast(e.message, 'danger');
  }
}

// ─── DATE HELPERS (TIMEZONE-SAFE EUROPE/ISTANBUL) ──────────────────────────────
function getLocalTodayString() {
  const d = new Date();
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function formatLocalDate(d) {
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

// ─── MEMBER: CALENDAR & AVAILABILITY ────────────────────────────────────────────
let selectedDate = getLocalTodayString();
let selectedSlot = null;
let selectedTrainerId = null;
let selectedTrainerName = null;

function renderCalendar() {
  const container = document.getElementById('calendarDays');
  if (!container) return;

  const base = new Date();
  let html = '';

  // Calculate day-of-week offset (0: Monday, 6: Sunday) for the starting date
  const startDayOfWeek = (base.getDay() + 6) % 7;
  for (let pad = 0; pad < startDayOfWeek; pad++) {
    html += `<div class="day-cell disabled empty" style="opacity:0.2; pointer-events:none;"></div>`;
  }

  for (let d = 0; d < 30; d++) {
    const date = new Date(base);
    date.setDate(base.getDate() + d);
    const dateStr = formatLocalDate(date);
    const isMonday = date.getDay() === 1;
    const isSelected = dateStr === selectedDate;

    let classes = 'day-cell';
    if (isMonday) classes += ' closed-monday disabled';
    else if (isSelected) classes += ' selected';

    html += `
      <div class="${classes}" onclick="${isMonday ? '' : `selectDate('${dateStr}')`}">
        <span>${date.getDate()}</span>
        ${isMonday ? '<span style="font-size:0.55rem; color:#EF4444;">Kapalı</span>' : ''}
      </div>
    `;
  }
  container.innerHTML = html;
}

function selectDate(dateStr) {
  selectedDate = dateStr;
  const el = document.getElementById('selectedDateText');
  if (el) el.innerText = dateStr;
  renderCalendar();
  loadAvailability(dateStr);
}

let selectedMatrixTrainerFilter = 'all';
let cachedTrainerOptions = [];

async function loadAvailability(dateStr) {
  const container = document.getElementById('matrixTableBody');
  if (!container) return;

  renderMatrixDateButtons();
  if (cachedTrainerOptions.length === 0) {
    try {
      const opts = await API.get('/trainer-options');
      cachedTrainerOptions = opts.trainers || [];
    } catch (e) {
      /* filter list just won't populate — matrix itself still works */
    }
  }
  renderMatrixTrainerButtons();

  const params = new URLSearchParams({ date: dateStr });
  if (selectedMatrixTrainerFilter !== 'all') params.set('trainer_id', selectedMatrixTrainerFilter);

  try {
    const data = await API.get(`/member/availability?${params.toString()}`);
    renderTrainerMatrix(data);
  } catch (e) {
    container.innerHTML = `<tr><td colspan="2" style="text-align:center; padding:1rem; color:var(--text-muted);">${escapeHtml(e.message)}</td></tr>`;
  }
}

function renderMatrixDateButtons() {
  const container = document.getElementById('matrixDateButtons');
  if (!container) return;

  const labels = ['Bugün', 'Yarın', 'Sonraki Gün'];
  let html = '';
  for (let d = 0; d < 3; d++) {
    const date = new Date();
    date.setDate(date.getDate() + d);
    const dateStr = formatLocalDate(date);
    const dayLabel = date.toLocaleDateString('tr-TR', { day: '2-digit', month: 'short' });
    const active = dateStr === selectedDate ? ' active' : '';
    html += `<button class="filter-chip matrix-date-btn${active}" onclick="setMatrixDate('${dateStr}', this)">${labels[d]} (${dayLabel})</button>`;
  }
  container.innerHTML = html;
}

function renderMatrixTrainerButtons() {
  const container = document.getElementById('matrixTrainerButtons');
  if (!container) return;

  const allActive = selectedMatrixTrainerFilter === 'all' ? ' active' : '';
  let html = `<button class="filter-chip matrix-trainer-btn${allActive}" onclick="setMatrixTrainerFilter('all', this)">Tüm Eğitmenler</button>`;
  html += cachedTrainerOptions
    .map((t) => {
      const active = String(selectedMatrixTrainerFilter) === String(t.id) ? ' active' : '';
      return `<button class="filter-chip matrix-trainer-btn${active}" onclick="setMatrixTrainerFilter(${t.id}, this)">${escapeHtml(t.name)}</button>`;
    })
    .join('');
  container.innerHTML = html;
}

function setMatrixDate(dateStr, btn) {
  selectedDate = dateStr;
  document.querySelectorAll('.matrix-date-btn').forEach((b) => b.classList.remove('active'));
  if (btn) btn.classList.add('active');
  loadAvailability(dateStr);
}

function setMatrixTrainerFilter(trainerId, btn) {
  selectedMatrixTrainerFilter = trainerId;
  document.querySelectorAll('.matrix-trainer-btn').forEach((b) => b.classList.remove('active'));
  if (btn) btn.classList.add('active');
  loadAvailability(selectedDate);
}

function renderTrainerMatrix(data) {
  const container = document.getElementById('matrixTableBody');
  if (!container) return;

  if (data.closed) {
    container.innerHTML = `<tr><td colspan="2" style="text-align:center; padding:1rem; color:var(--text-muted);">${escapeHtml(data.reason || 'Kulüp bugün kapalı.')}</td></tr>`;
    return;
  }

  container.innerHTML = data.trainers
    .map((trainer) => {
      const slotBadges = trainer.slots
        .map((slot) => {
          if (slot.status === 'available') {
            return `<span class="slot-tag available" onclick="quickBookSlot(${trainer.trainer_id}, '${escapeHtml(trainer.name)}', '${data.date}', '${slot.time}')" title="Rezervasyon Yapmak İçin Tıkla">🟢 ${slot.time} (Müsait)</span>`;
          } else if (slot.status === 'busy') {
            return `<span class="slot-tag busy" title="Dolu">🔴 ${slot.time} (Dolu)</span>`;
          }
          return `<span class="slot-tag off" title="Müsait Değil">⚪ ${slot.time} (Kapalı)</span>`;
        })
        .join('');

      return `
      <tr>
        <td style="width: 220px;">
          <div class="trainer-cell">
            <div class="trainer-avatar">${escapeHtml(trainer.avatar_letter || trainer.name.charAt(0))}</div>
            <div>
              <div style="font-weight:700; color:var(--text-main);">${escapeHtml(trainer.name)}</div>
              <div style="font-size:0.75rem; color:var(--text-muted);">${escapeHtml(trainer.title || '')}</div>
            </div>
          </div>
        </td>
        <td><div class="slot-badge-grid">${slotBadges}</div></td>
      </tr>
    `;
    })
    .join('');
}

let cachedHorses = null;

function quickBookSlot(trainerId, trainerName, date, time) {
  selectedTrainerId = trainerId;
  selectedTrainerName = trainerName;
  selectedDate = date;
  selectedSlot = time;
  document.getElementById('bookingModalTrainer').value = trainerName;
  document.getElementById('bookingModalDate').innerText = `${date} saat ${time}`;
  populateHorseOptions();
  openModal('quickBookingModal');
}

async function populateHorseOptions() {
  const select = document.getElementById('quickModalHorse');
  if (!select) return;

  if (!cachedHorses) {
    try {
      cachedHorses = (await API.get('/member/horses')).horses || [];
    } catch (e) {
      cachedHorses = [];
    }
  }

  const placeholder = '<option value="">🐴 Kulüp Tarafından Belirlensin (Önerilen)</option>';
  select.innerHTML =
    placeholder +
    cachedHorses.map((h) => `<option value="${h.id}">${escapeHtml(h.name)}${h.breed ? ' (' + escapeHtml(h.breed) + ')' : ''}</option>`).join('');
}

async function confirmLessonBooking() {
  if ((session.user?.remaining_lessons ?? 0) <= 0) {
    // The server is authoritative anyway (family-pool fallback may still allow
    // this) — this is just an early, friendlier heads-up before the request.
    if (!confirm('Bireysel ders krediniz görünmüyor, aile havuzunuz varsa oradan düşülecek. Devam edilsin mi?')) return;
  }

  const activityLabel = document.getElementById('quickModalActivityType')?.value || '🏇 Standart Manej Biniş Dersi (45 Dk)';
  const horseId = document.getElementById('quickModalHorse')?.value || null;
  const submitBtn = document.querySelector('#quickBookingModal button.btn-gold');
  if (submitBtn) { submitBtn.disabled = true; submitBtn.innerText = 'İşleniyor...'; }

  try {
    const res = await API.post('/member/reservations', {
      trainer_id: selectedTrainerId,
      date: selectedDate,
      time: selectedSlot,
      activity_label: activityLabel,
      horse_id: horseId ? parseInt(horseId) : null,
    });
    closeModal('quickBookingModal');
    showToast(`🎉 Rezervasyonunuz (${selectedDate} ${selectedSlot}) oluşturuldu!`);
    await bootstrapSession();

    if (confirm('Kulübe otomatik WhatsApp rezervasyon teyit mesajı göndermek ister misiniz?')) {
      sendWhatsAppNotification('booked', res.reservation.activity_label, selectedDate, selectedSlot, selectedTrainerName);
    }
    switchView('dashboard');
  } catch (e) {
    showToast(e.message, 'danger');
  } finally {
    if (submitBtn) { submitBtn.disabled = false; submitBtn.innerText = 'Rezervasyonu Onayla'; }
  }
}

// ─── MEMBER: PACKAGES & SAFARI ───────────────────────────────────────────────────
let cachedSafariTours = [];
let selectedSafariTour = null;

async function loadPackagesAndSafari() {
  try {
    const [pkgData, safariData] = await Promise.all([API.get('/member/packages'), API.get('/member/safari-tours')]);
    renderPackages(pkgData.packages || []);
    cachedSafariTours = safariData.tours || [];
    renderSafariTours(cachedSafariTours);
  } catch (e) {
    showToast('Paketler yüklenemedi: ' + e.message, 'danger');
  }
}

function renderPackages(packages) {
  const container = document.getElementById('categoryMembership');
  if (!container) return;

  container.innerHTML = `
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem;">
      ${packages
        .map(
          (p) => `
        <div class="card" style="padding:1rem;">
          <div style="font-weight:800; font-size:1.1rem; color:var(--primary-dark);">${p.lesson_count} Ders</div>
          ${p.badge_label ? `<span class="sec-badge warning">${escapeHtml(p.badge_label)}</span>` : ''}
          <div style="font-size:1.3rem; font-weight:800; color:var(--gold-dark); margin:0.5rem 0;">${formatTry(p.price_try)}</div>
          <button class="btn-gold" style="width:100%;" onclick="buyPackage(${p.id})">Talep Oluştur</button>
        </div>
      `
        )
        .join('')}
    </div>
  `;
}

function renderSafariTours(tours) {
  const container = document.getElementById('categorySafari');
  if (!container) return;

  container.innerHTML = `
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem;">
      ${tours
        .map(
          (t) => `
        <div class="card" style="padding:1rem;">
          <div style="font-weight:800; font-size:1.05rem; color:var(--primary-dark);">${escapeHtml(t.name)}</div>
          <div style="font-size:0.8rem; color:var(--text-muted); margin:0.4rem 0;">${escapeHtml(t.description || '')}</div>
          <div style="font-size:1.1rem; font-weight:800; color:var(--gold-dark); margin-bottom:0.5rem;">${formatTry(t.price_per_person)} / kişi</div>
          <button class="btn-primary" style="width:100%;" onclick="openSafariModal(${t.id})">Rezervasyon Yap</button>
        </div>
      `
        )
        .join('')}
    </div>
  `;
}

async function buyPackage(packageId) {
  if (!confirm('Bu paket için kulübe satın alma talebi oluşturmak istiyor musunuz? (Ödeme kulüpte yapılır)')) return;

  try {
    await API.post('/member/packages/request', { package_id: packageId });
    showToast('✓ Paket talebiniz oluşturuldu, admin onayı bekleniyor.');

    const userName = session.user?.name || 'Kulüp Üyesi';
    const msg = `Merhaba, bir paket satın alma talebi oluşturdum, kulüpte ödeme yapacağım. (Üye: ${userName})`;
    window.open(`https://wa.me/${CLUB_WHATSAPP_NUMBER}?text=${encodeURIComponent(msg)}`, '_blank');
  } catch (e) {
    showToast(e.message, 'danger');
  }
}

function openSafariModal(tourId) {
  selectedSafariTour = cachedSafariTours.find((t) => t.id === tourId);
  if (!selectedSafariTour) return;
  document.getElementById('safariModalTitle').innerText = selectedSafariTour.name;
  const dateInput = document.getElementById('safariDate');
  if (dateInput) dateInput.value = getLocalTodayString();
  updateSafariPrice();
  openModal('safariModal');
}

function updateSafariPrice() {
  const count = parseInt(document.getElementById('safariPax')?.value) || 1;
  const total = count * parseFloat(selectedSafariTour?.price_per_person || 0);
  const el = document.getElementById('safariTotalPrice');
  if (el) el.innerText = formatTry(total);
}

async function confirmSafariBooking() {
  const count = parseInt(document.getElementById('safariPax')?.value) || 1;
  const date = document.getElementById('safariDate')?.value;
  const time = document.getElementById('safariTime')?.value;

  if (!date || !time) {
    alert('Lütfen safari tarihi ve saati seçiniz.');
    return;
  }

  const submitBtn = document.querySelector('#safariModal button.btn-gold');
  if (submitBtn) { submitBtn.disabled = true; submitBtn.innerText = 'İşleniyor...'; }

  try {
    const res = await API.post('/member/safari-tours/book', {
      safari_tour_id: selectedSafariTour.id,
      date,
      time,
      participants: count,
    });
    closeModal('safariModal');
    showToast(`🏇 Safari Turu talebiniz oluşturuldu! (${count} Kişi — ${formatTry(res.reservation.price_try)})`);

    if (confirm('Kulübe WhatsApp ile safari rezervasyon teyit mesajı göndermek ister misiniz?')) {
      sendWhatsAppNotification('booked', selectedSafariTour.name, date, time, 'Safari Rehberi');
    }
    switchView('dashboard');
  } catch (e) {
    showToast(e.message, 'danger');
  } finally {
    if (submitBtn) { submitBtn.disabled = false; submitBtn.innerText = 'Rezervasyonu Onayla'; }
  }
}

// ─── MEMBER: HISTORY ─────────────────────────────────────────────────────────────
async function loadHistory() {
  const container = document.getElementById('historyList');
  if (!container) return;

  try {
    const data = await API.get('/member/reservations');
    const past = (data.reservations || []).filter((r) => r.status !== 'confirmed');
    if (past.length === 0) {
      container.innerHTML = `<p style="color:var(--text-muted); text-align:center; padding:2rem;">Geçmiş kaydınız bulunmuyor.</p>`;
      return;
    }
    container.innerHTML = past
      .map(
        (r) => `
      <div class="lesson-card">
        <div class="lesson-header">
          <div class="lesson-title">${escapeHtml(r.activity_label)}</div>
          <span class="lesson-badge">${statusLabel(r.status)}</span>
        </div>
        <div class="lesson-details">
          <div class="lesson-detail-item">📅 ${r.date} (${r.time})</div>
        </div>
      </div>
    `
      )
      .join('');
  } catch (e) {
    container.innerHTML = `<p style="color:var(--text-muted);">${escapeHtml(e.message)}</p>`;
  }
}

function statusLabel(status) {
  return { completed: 'Tamamlandı', cancelled: 'İptal Edildi', late_cancelled: 'Geç İptal', no_show: 'Gelmedi' }[status] || status;
}

// ─── PROFILE ──────────────────────────────────────────────────────────────────
function renderUserProfile() {
  const u = session.user;
  if (!u) return;

  const set = (id, val) => { const el = document.getElementById(id); if (el) el.innerText = val; };
  const safeName = u.name || 'Misafir';
  const initial = (safeName.trim().charAt(0) || '?').toUpperCase();

  set('profileName', safeName);
  set('profileEmail', u.email || '—');
  set('profileAvatar', initial);
  set('cardHolderName', safeName);
  set('cardRefCode', `REF: ${u.ref_code || '—'}`);
  set('cardRemainingCredits', `${u.remaining_lessons || 0} Ders`);
  set(
    'cardPackageExpiry',
    !u.active_package_id
      ? 'Aktif paket yok'
      : u.package_expires_at
        ? new Date(u.package_expires_at).toLocaleDateString('tr-TR')
        : 'Süresiz (Ders Bazlı Paket)'
  );
  set('navUserName', safeName.split(' ')[0] || safeName);
  set('navUserRef', u.role === 'admin' ? '👑 Admin' : `Ref: ${u.ref_code || '—'}`);
  set('navUserAvatar', initial);

  const badgeEl = document.getElementById('profileRefBadge');
  if (badgeEl) badgeEl.innerText = `Referans Kodun: ${u.ref_code || '—'}`;

  const roleText = u.role === 'admin' 
    ? '👑 Kulüp Yöneticisi' 
    : (u.active_package?.name ? `🏇 ${u.active_package.name}` : '👤 Kulüp Üyesi');
  const roleInput = document.getElementById('profileRolePackage');
  if (roleInput) roleInput.value = roleText;

  const phoneInput = document.getElementById('profilePhone');
  if (phoneInput) phoneInput.value = u.phone || '—';
}

// ─── TRAINER PORTAL ───────────────────────────────────────────────────────────
let cachedTrainerReservations = [];

async function loadTrainerSchedule() {
  try {
    const data = await API.get(`/trainer/schedule?date=${getLocalTodayString()}`);
    cachedTrainerReservations = data.reservations || [];

    const set = (id, val) => { const el = document.getElementById(id); if (el) el.innerText = val; };
    set('trainerPortalTitle', `🎯 ANTRENÖR MASASI: ${data.trainer.name.toUpperCase()}`);
    set('trainerPortalBadge', data.trainer.title || '');
    set('trainerLessonsCount', `Bugün ${cachedTrainerReservations.length} Ders Planlandı`);

    renderTrainerRoster();
    populateTrainerStudentSelect();
  } catch (e) {
    showToast('Program yüklenemedi: ' + e.message, 'danger');
  }
}

function renderTrainerRoster() {
  const container = document.getElementById('trainerRosterList');
  if (!container) return;

  if (cachedTrainerReservations.length === 0) {
    container.innerHTML = `<p style="color:var(--text-muted); font-size:0.85rem;">Bugün için planlanan ders yok.</p>`;
    return;
  }

  container.innerHTML = cachedTrainerReservations
    .map((r) => {
      if (r.status !== 'confirmed') {
        return `
        <div style="background:var(--bg-page); border:1px dashed var(--border); border-radius:var(--radius-sm); padding:0.6rem 1rem; margin-bottom:0.5rem; opacity:0.8;">
          ⏰ ${r.time} — ${escapeHtml(r.user?.name || 'Silinmiş Üye')} <span class="sec-badge ${r.status === 'completed' ? 'success' : 'danger'}">${statusLabel(r.status)}</span>
        </div>`;
      }
      return `
        <div style="background:#FFFFFF; border:1px solid var(--border); border-radius:var(--radius-sm); padding:0.85rem 1rem; margin-bottom:0.6rem; display:flex; justify-content:space-between; align-items:center;">
          <div>
            <div style="font-weight:700; font-size:0.95rem; color:var(--primary-dark);">⏰ ${r.time} — <span style="color:var(--primary);">${escapeHtml(r.user?.name || 'Silinmiş Üye')}</span></div>
            <div style="font-size:0.75rem; color:var(--text-muted);">${escapeHtml(r.activity_label)}${r.horse ? ' • At: ' + escapeHtml(r.horse.name) : ''}</div>
          </div>
          <div style="display:flex; gap:0.4rem;">
            <button class="admin-action-btn success" onclick="trainerMarkAttendance(${r.id}, 'completed')">✅ Geldi</button>
            <button class="admin-action-btn danger" onclick="trainerMarkAttendance(${r.id}, 'no_show')">❌ Gelmedi</button>
          </div>
        </div>
      `;
    })
    .join('');
}

function populateTrainerStudentSelect() {
  const select = document.getElementById('trainerStudentSelect');
  if (!select) return;
  // Not just 'confirmed' — a trainer marking a lesson completed/no_show is
  // exactly when they're most likely to want to leave a note about it, and
  // that status change (via trainerMarkAttendance -> loadTrainerSchedule)
  // used to make the student vanish from this list right as they clicked in.
  const students = cachedTrainerReservations.filter((r) => ['confirmed', 'completed', 'no_show'].includes(r.status));
  select.innerHTML = students.length
    ? students.map((r) => `<option value="${r.user?.id ?? ''}" data-reservation="${r.id}">${escapeHtml(r.user?.name || 'Silinmiş Üye')}</option>`).join('')
    : '<option value="">Bugün ders alan öğrenci yok</option>';
}

async function trainerMarkAttendance(reservationId, status) {
  try {
    const res = await API.post(`/trainer/reservations/${reservationId}/attendance`, { status });
    showToast(res.message);
    loadTrainerSchedule();
  } catch (e) {
    showToast(e.message, 'danger');
  }
}

async function submitTrainerFeedback() {
  const select = document.getElementById('trainerStudentSelect');
  const studentUserId = select?.value;
  const reservationId = select?.selectedOptions?.[0]?.dataset?.reservation || null;
  const disciplineSelect = document.querySelectorAll('#view-trainer select.form-control')[1];
  const note = document.getElementById('trainerFeedbackNote').value;

  if (!studentUserId) {
    showToast('Lütfen bir öğrenci seçin.', 'warning');
    return;
  }
  if (!note || note.trim().length === 0) {
    showToast('Lütfen öğrenci için bir gelişim gözlem notu girin.', 'warning');
    return;
  }

  try {
    await API.post('/trainer/feedback', {
      student_user_id: studentUserId,
      reservation_id: reservationId,
      discipline_level: disciplineSelect?.value || null,
      note,
    });
    showToast('✓ Gelişim raporu kaydedildi.');
    document.getElementById('trainerFeedbackNote').value = '';
  } catch (e) {
    showToast(e.message, 'danger');
  }
}

// ─── ADMIN PANEL ──────────────────────────────────────────────────────────────
let currentAdminSubTab = 'dashboard';
let currentMemberCategory = 'club';
let currentMemberStatusFilter = 'all';
let cachedAdminMembers = [];
let cachedAdminFamilies = [];

function switchAdminSubTab(tabName) {
  currentAdminSubTab = tabName;

  document.querySelectorAll('[id^="adminTabBtn-"]').forEach((btn) => btn.classList.remove('active'));
  document.getElementById(`adminTabBtn-${tabName}`)?.classList.add('active');

  document.querySelectorAll('.admin-sub-view').forEach((view) => (view.style.display = 'none'));
  const activeView = document.getElementById(`adminSubView-${tabName}`);
  if (activeView) activeView.style.display = 'block';

  loadAdminSubTab(tabName);
}

function loadAdminSubTab(tabName) {
  switch (tabName) {
    case 'dashboard':
      return loadAdminDashboard();
    case 'trainers':
      return loadAdminTrainers();
    case 'members':
      return loadAdminMembers();
    case 'families':
      return loadAdminFamilies();
    case 'payments':
      return loadAdminDashboard(); // same aggregates power both views
    case 'notifications':
      return loadAdminNotifications();
    case 'settings':
      return loadAdminSettings();
    case 'security':
      return loadAdminAuditLog();
    default:
      return null;
  }
}

async function loadAdminDashboard() {
  const set = (id, val) => { const el = document.getElementById(id); if (el) el.innerText = val; };

  set('adminHeaderName', `Süper Yönetici: ${session.user?.name || ''}`);
  set('adminDashboardDate', '📅 ' + new Date().toLocaleDateString('tr-TR', { weekday: 'long', day: 'numeric', month: 'long' }));

  try {
    const data = await API.get('/admin/dashboard');

    set('finRevenueToday', formatTry(data.revenue.today));
    set('finRevenueMonth', formatTry(data.revenue.month));
    set('finRevenueAllTime', formatTry(data.revenue.all_time));
    set('finLessonsToday', `${data.lessons_today.total} toplam • ${data.lessons_today.completed} tamamlandı • ${data.lessons_today.pending} bekliyor`);
    set('finTotalMembers', `${data.total_members} üye (${data.members_with_contact_info} iletişim bilgisi tam)`);

    set('adminStatSessions', `${data.lessons_today.total} Ders`);
    set('adminStatSessionsSub', `${data.lessons_today.completed} Tamamlandı • ${data.lessons_today.pending} Bekleyen`);
    set('adminStatSold', formatTry(data.revenue.today));
    set('adminStatSoldSub', `Bu Ay: ${formatTry(data.revenue.month)}`);
    set('adminStatMembers', `${data.total_members} Üye`);
    set('adminStatMembersSub', `${data.new_registrations.year} Yeni Kayıt (${new Date().getFullYear()})`);

    renderLowCreditAlerts(data.low_credit_members || []);
    if (document.getElementById('adminNotifBadge')) {
      const badge = document.getElementById('adminNotifBadge');
      badge.innerText = data.low_credit_members?.length || '';
      badge.style.display = data.low_credit_members?.length ? 'inline' : 'none';
    }
  } catch (e) {
    showToast('Dashboard yüklenemedi: ' + e.message, 'danger');
  }

  loadAdminAttendanceFeed();
}

async function loadAdminAttendanceFeed() {
  const container = document.getElementById('adminAttendanceFeed');
  if (!container) return;

  try {
    const data = await API.get('/admin/reservations/today');
    const reservations = data.reservations || [];
    document.getElementById('adminAttendanceFeedCount').innerText = `${reservations.length} Seans Planlı`;

    if (reservations.length === 0) {
      container.innerHTML = `<p style="color:var(--text-muted); font-size:0.85rem;">Bugün için kayıtlı seans bulunmuyor.</p>`;
      return;
    }

    container.innerHTML = reservations
      .map(
        (res) => `
      <div style="background:#FFFFFF; border:1px solid var(--border); border-radius:var(--radius-sm); padding:0.75rem 1rem; margin-bottom:0.6rem; display:flex; justify-content:space-between; align-items:center;">
        <div>
          <div style="font-weight:700; font-size:0.9rem;">${escapeHtml(res.activity_label)} — <span style="color:var(--primary);">${escapeHtml(res.user?.name || 'Silinmiş Üye')}</span></div>
          <div style="font-size:0.75rem; color:var(--text-muted);">📅 ${res.date} (${res.time})${res.trainer ? ' • 👤 ' + escapeHtml(res.trainer.user?.name || 'Silinmiş Eğitmen') : ''}${res.horse ? ' • 🐴 ' + escapeHtml(res.horse.name) : ''}</div>
        </div>
        <div style="display:flex; gap:0.4rem; align-items:center;">
          <span class="lesson-badge ${res.status === 'confirmed' ? 'badge-approved' : 'badge-pending'}">${statusLabel(res.status)}</span>
          ${
            res.status === 'confirmed'
              ? `<button class="admin-action-btn success" onclick="adminMarkAttendance(${res.id}, 'completed')">✅ Geldi</button>
                 <button class="admin-action-btn danger" onclick="adminMarkAttendance(${res.id}, 'no_show')">❌ Gelmedi</button>`
              : ''
          }
        </div>
      </div>
    `
      )
      .join('');
  } catch (e) {
    container.innerHTML = `<p style="color:var(--text-muted);">${escapeHtml(e.message)}</p>`;
  }
}

async function adminMarkAttendance(reservationId, status) {
  try {
    const res = await API.post(`/admin/reservations/${reservationId}/attendance`, { status });
    showToast(res.message);
    loadAdminAttendanceFeed();
  } catch (e) {
    showToast(e.message, 'danger');
  }
}

function renderLowCreditAlerts(alerts) {
  const container = document.getElementById('adminLowCreditAlertsList');
  if (!container) return;

  if (alerts.length === 0) {
    container.innerHTML = `<p style="color:var(--text-muted); font-size:0.85rem;">Düşük kredili üye bulunmuyor.</p>`;
    return;
  }

  container.innerHTML = alerts
    .map(
      (al) => `
    <div style="background:#FFFBEB; border:1px solid #FDE68A; border-radius:var(--radius-sm); padding:0.75rem 1rem; display:flex; justify-content:space-between; align-items:center;">
      <div style="display:flex; align-items:center; gap:0.6rem;">
        <span style="font-size:1.2rem;">⚠️</span>
        <div>
          <div style="font-weight:700; color:#92400E; font-size:0.9rem;"><b>${escapeHtml(al.name)}</b> adlı üyenin <b>${al.remaining_lessons} dersi</b> kaldı.</div>
          <div style="font-size:0.75rem; color:#B45309;">${escapeHtml(al.phone || al.email || '')}</div>
        </div>
      </div>
      <button class="admin-action-btn success" onclick="sendWhatsAppToMember('${al.phone || ''}', '${escapeHtml(al.name)}')">💬</button>
    </div>
  `
    )
    .join('');
}

async function loadAdminTrainers() {
  const container = document.getElementById('adminTrainersDetailGrid');
  if (!container) return;

  try {
    const data = await API.get(`/member/availability?date=${getLocalTodayString()}`);
    const trainers = data.trainers || [];
    document.getElementById('adminTrainersActiveCount').innerText = `${trainers.length} Eğitmen Aktif`;

    container.innerHTML = trainers
      .map((t) => {
        const busyCount = t.slots.filter((s) => s.status === 'busy').length;
        return `
        <div style="background:#FFFFFF; border:1px solid var(--border); border-radius:var(--radius-md); padding:1rem;">
          <div style="display:flex; align-items:center; gap:0.6rem; margin-bottom:0.75rem;">
            <div style="width:40px; height:40px; border-radius:50%; background:var(--primary-dark); color:var(--gold-light); font-weight:800; display:flex; align-items:center; justify-content:center;">${escapeHtml(t.avatar_letter || t.name.charAt(0))}</div>
            <div>
              <div style="font-weight:800; color:var(--primary-dark);">${escapeHtml(t.name)}</div>
              <div style="font-size:0.75rem; color:var(--text-muted);">${escapeHtml(t.title || '')} — Bugün ${busyCount} Ders</div>
            </div>
          </div>
          <div style="display:flex; flex-wrap:wrap; gap:0.4rem;">
            ${t.slots
              .map(
                (s) => `<span style="font-size:0.7rem; padding:0.2rem 0.45rem; border-radius:var(--radius-sm); border:1px solid ${s.status === 'busy' ? '#EF4444' : '#10B981'}; background:${s.status === 'busy' ? '#FEF2F2' : '#ECFDF5'}; color:${s.status === 'busy' ? '#991B1B' : '#065F46'}; font-weight:700;">${s.time} ${s.status === 'busy' ? '(Dolu)' : '✓ Boş'}</span>`
              )
              .join('')}
          </div>
        </div>
      `;
      })
      .join('');
  } catch (e) {
    container.innerHTML = `<p style="color:var(--text-muted);">${escapeHtml(e.message)}</p>`;
  }
}

function filterMemberCategory(cat) {
  currentMemberCategory = cat;
  document.getElementById('memberTabBtn-club')?.classList.toggle('active', cat === 'club');
  document.getElementById('memberTabBtn-prog')?.classList.toggle('active', cat === 'program');
  loadAdminMembers();
}

function filterMemberStatus(status, btnEl) {
  currentMemberStatusFilter = status;
  document.querySelectorAll('.admin-filter-pill').forEach((p) => p.classList.remove('active'));
  if (btnEl) btnEl.classList.add('active');
  loadAdminMembers();
}

async function loadAdminMembers() {
  const search = document.getElementById('adminMemberSearch')?.value || '';
  const statusParam = currentMemberStatusFilter === 'finished' ? 'passive' : currentMemberStatusFilter;
  const params = new URLSearchParams();
  if (search) params.set('search', search);
  if (statusParam !== 'all') params.set('status', statusParam);
  if (currentMemberCategory === 'program') params.set('scope', 'program');

  try {
    const data = await API.get(`/admin/members?${params.toString()}`);
    cachedAdminMembers = data.members.data || [];
    renderAdminMembersTable();

    const clubBtn = document.getElementById('memberTabBtn-club');
    const progBtn = document.getElementById('memberTabBtn-prog');
    if (clubBtn) clubBtn.innerText = `Kulüp Üyeleri (${data.counts.club_members})`;
    if (progBtn) progBtn.innerText = `Program Kayıtlıları (${data.counts.program_enrolled})`;
  } catch (e) {
    showToast('Üye listesi yüklenemedi: ' + e.message, 'danger');
  }
}

function renderAdminMembersTable() {
  const tbody = document.getElementById('adminMembersTableBody');
  if (!tbody) return;

  if (cachedAdminMembers.length === 0) {
    tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:1rem; color:var(--text-muted);">Kayıt bulunamadı.</td></tr>`;
    return;
  }

  tbody.innerHTML = cachedAdminMembers
    .map(
      (m) => `
    <tr style="border-bottom:1px solid var(--border);">
      <td style="padding:0.6rem 0.4rem;">
        <div style="font-weight:700; color:var(--primary-dark); font-size:0.9rem;">${escapeHtml(m.name)}</div>
        <div style="font-size:0.75rem; color:var(--text-muted);">${escapeHtml(m.email || '—')}</div>
      </td>
      <td style="padding:0.6rem 0.4rem;">
        <div style="font-size:0.8rem;">${escapeHtml(m.phone || 'İletişim bilgisi yok')}</div>
        <span style="background:rgba(197,160,89,0.15); color:var(--gold-dark); padding:0.1rem 0.4rem; border-radius:var(--radius-full); font-weight:700; font-size:0.7rem;">Ref: ${escapeHtml(m.ref_code || '—')}</span>
      </td>
      <td style="padding:0.6rem 0.4rem;"><span style="font-size:0.8rem; font-weight:600; color:#334155;">${m.active_package_id ? 'Aktif Paket #' + m.active_package_id : '—'}</span></td>
      <td style="padding:0.6rem 0.4rem;">
        <b style="color:${m.remaining_lessons === 0 ? '#EF4444' : m.remaining_lessons <= 2 ? '#F59E0B' : 'var(--primary)'}; font-size:1.05rem;">${m.remaining_lessons}</b>
        <span style="font-size:0.8rem; color:var(--text-muted);"> / ${m.total_lessons} Ders</span>
      </td>
      <td style="padding:0.6rem 0.4rem; text-align:center;">
        <div style="display:flex; gap:0.3rem; justify-content:center;">
          <button class="admin-action-btn success" onclick="adminAddCredits(${m.id}, 1)">+1 Ders</button>
          <button class="admin-action-btn success" onclick="adminAddCredits(${m.id}, 4)">+4 Ders</button>
          ${m.phone ? `<button class="admin-action-btn" onclick="sendWhatsAppToMember('${m.phone}', '${escapeHtml(m.name)}')">💬</button>` : `<button class="admin-action-btn" onclick="adminSendClaimLink(${m.id})" title="Hesap kurulum linki üret">🔗</button>`}
        </div>
      </td>
    </tr>
  `
    )
    .join('');
}

function adminExportMembersCSV() {
  if (!cachedAdminMembers || cachedAdminMembers.length === 0) {
    showToast('Dışa aktarılacak üye bulunamadı.', 'warning');
    return;
  }

  const headers = ['Üye Adı', 'E-posta', 'Telefon', 'Referans Kodu', 'Kalan Ders', 'Toplam Ders', 'Kayıt Tarihi'];
  const rows = cachedAdminMembers.map((m) => [
    `"${(m.name || '').replace(/"/g, '""')}"`,
    `"${(m.email || '').replace(/"/g, '""')}"`,
    `"${(m.phone || '').replace(/"/g, '""')}"`,
    `"${(m.ref_code || '').replace(/"/g, '""')}"`,
    m.remaining_lessons ?? 0,
    m.total_lessons ?? 0,
    `"${(m.created_at || new Date().toLocaleDateString('tr-TR')).replace(/"/g, '""')}"`,
  ]);

  const csvContent = '\uFEFF' + [headers.join(';'), ...rows.map((r) => r.join(';'))].join('\r\n');
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.setAttribute('href', url);
  link.setAttribute('download', `4D_Binicilik_Uye_Kutugu_${new Date().toISOString().slice(0, 10)}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  showToast('✓ Üye kütüğü CSV (Excel uyumlu) olarak indirildi!');
}

async function adminAddCredits(memberId, count) {
  try {
    await API.post(`/admin/members/${memberId}/credits`, { delta: count });
    showToast(`✓ ${count} ders kredisi eklendi.`);
    loadAdminMembers();
  } catch (e) {
    showToast(e.message, 'danger');
  }
}

async function adminSendClaimLink(memberId) {
  try {
    const res = await API.post(`/admin/members/${memberId}/claim-link`);
    prompt('Bu bağlantıyı üyeye iletin (7 gün geçerli):', res.claim_url);
  } catch (e) {
    showToast(e.message, 'danger');
  }
}

function sendWhatsAppToMember(phone, name) {
  const cleanPhone = (phone || '').replace(/[^0-9]/g, '');
  if (!cleanPhone) {
    showToast('Bu üye için telefon numarası kayıtlı değil.', 'danger');
    return;
  }
  const msg = `Merhaba ${name}, kulüp yönetiminden ulaşıyoruz.`;
  window.open(`https://wa.me/${cleanPhone}?text=${encodeURIComponent(msg)}`, '_blank');
}

async function loadAdminFamilies() {
  try {
    const data = await API.get('/admin/families');
    cachedAdminFamilies = data.families || [];
    renderAdminFamilyGroups();
  } catch (e) {
    showToast('Aile grupları yüklenemedi: ' + e.message, 'danger');
  }
}

function renderAdminFamilyGroups() {
  const container = document.getElementById('adminFamilyGroupsList');
  if (!container) return;

  if (cachedAdminFamilies.length === 0) {
    container.innerHTML = `<p style="color:var(--text-muted);">Henüz aile grubu yok.</p>`;
    return;
  }

  container.innerHTML = cachedAdminFamilies
    .map(
      (f) => `
    <div style="background:#FFFFFF; border:1px solid var(--border); border-radius:var(--radius-md); padding:1rem; margin-bottom:0.85rem;">
      <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.5rem;">
        <span style="font-weight:800; font-size:1.05rem; color:var(--primary-dark);">${escapeHtml(f.name)}</span>
        <div style="display:flex; align-items:center; gap:0.5rem;">
          <span style="background:rgba(16,185,129,0.12); color:#047857; font-weight:800; font-size:0.8rem; padding:0.2rem 0.6rem; border-radius:var(--radius-full);">${f.remaining_lessons} Ders Kaldı</span>
          <button class="btn-outline" style="padding:0.2rem 0.5rem; font-size:0.75rem;" onclick="adminAddFamilyMemberPrompt(${f.id})">+ Üye Ekle</button>
        </div>
      </div>
      <div style="font-size:0.8rem; color:var(--text-muted); margin-bottom:0.6rem;">Paket: <b>${f.package_total} Ders</b> • Kullanıldı: ${f.used_lessons} • Rezerve: ${f.reserved_lessons}</div>
      <div style="display:flex; flex-wrap:wrap; gap:0.4rem;">
        ${(f.members || [])
          .map(
            (fm) => `
          <span style="background:#F8FAFC; color:#334155; font-size:0.75rem; padding:0.25rem 0.6rem; border-radius:var(--radius-sm); border:1px solid #E2E8F0; display:flex; align-items:center; gap:0.4rem;">
            👤 ${escapeHtml(fm.user?.name || fm.display_name || '—')}${fm.is_primary ? ' (Ana Üye)' : ''}
            ${!fm.is_primary ? `<button onclick="adminRemoveFamilyMember(${f.id}, ${fm.id})" style="background:none; border:none; color:#EF4444; cursor:pointer; font-weight:700;" title="Gruptan Çıkar">✕</button>` : ''}
          </span>
        `
          )
          .join('')}
      </div>
    </div>
  `
    )
    .join('');
}

async function adminAddFamilyMemberPrompt(familyId) {
  const name = prompt('Eklenecek üyenin adını girin (kayıtlı üyelerle eşleşirse otomatik bağlanır):');
  if (!name) return;

  const match = cachedAdminMembers.find((m) => m.name.toLowerCase() === name.trim().toLowerCase());

  try {
    await API.post(`/admin/families/${familyId}/members`, match ? { user_id: match.id } : { display_name: name.trim() });
    showToast(`✓ ${name} gruba eklendi.`);
    loadAdminFamilies();
  } catch (e) {
    showToast(e.message, 'danger');
  }
}

async function adminRemoveFamilyMember(familyId, familyMemberId) {
  if (!confirm('Bu üyeyi aile grubundan çıkarmak istediğinize emin misiniz?')) return;
  try {
    await API.delete(`/admin/families/${familyId}/members/${familyMemberId}`);
    showToast('✓ Üye gruptan çıkarıldı.');
    loadAdminFamilies();
  } catch (e) {
    showToast(e.message, 'danger');
  }
}

async function openNewFamilyModal() {
  const famName = prompt('Yeni Aile Grubu Adı (Örn: Yılmaz Ailesi):');
  if (!famName) return;
  try {
    await API.post('/admin/families', { name: famName.trim() });
    showToast(`✓ ${famName} başarıyla oluşturuldu!`);
    loadAdminFamilies();
  } catch (e) {
    showToast(e.message, 'danger');
  }
}

async function loadAdminNotifications() {
  try {
    const [reqData, dashData] = await Promise.all([API.get('/admin/purchase-requests'), API.get('/admin/dashboard')]);
    renderPurchaseRequests(reqData.requests || []);
    renderLowCreditAlerts(dashData.low_credit_members || []);
  } catch (e) {
    showToast('İstekler yüklenemedi: ' + e.message, 'danger');
  }
}

function renderPurchaseRequests(requests) {
  const container = document.getElementById('adminPurchaseRequestsList');
  if (!container) return;

  if (requests.length === 0) {
    container.innerHTML = `<p style="color:var(--text-muted); font-size:0.85rem;">Bekleyen paket talebi yok.</p>`;
    return;
  }

  container.innerHTML = requests
    .map(
      (r) => `
    <div style="background:#FFFFFF; border:1px solid var(--border); border-radius:var(--radius-sm); padding:0.75rem 1rem; display:flex; justify-content:space-between; align-items:center;">
      <div>
        <div style="font-weight:700; font-size:0.9rem;">${escapeHtml(r.user?.name || 'Silinmiş Üye')} — ${r.package ? r.package.lesson_count + ' Ders' : 'Paket silinmiş'}</div>
        <div style="font-size:0.75rem; color:var(--text-muted);">Durum: ${r.status}${r.package ? ' • ' + formatTry(r.package.price_try) : ''}</div>
      </div>
      ${
        r.status === 'pending'
          ? `<div style="display:flex; gap:0.4rem;">
              <button class="admin-action-btn success" onclick="adminApprovePurchaseRequest(${r.id})">✅ Onayla</button>
              <button class="admin-action-btn danger" onclick="adminRejectPurchaseRequest(${r.id})">❌ Reddet</button>
            </div>`
          : `<span class="sec-badge ${r.status === 'approved' ? 'success' : 'danger'}">${r.status}</span>`
      }
    </div>
  `
    )
    .join('');
}

async function adminApprovePurchaseRequest(id) {
  try {
    await API.post(`/admin/purchase-requests/${id}/approve`);
    showToast('✓ Talep onaylandı, kredi yüklendi.');
    loadAdminNotifications();
  } catch (e) {
    showToast(e.message, 'danger');
  }
}

async function adminRejectPurchaseRequest(id) {
  try {
    await API.post(`/admin/purchase-requests/${id}/reject`);
    showToast('Talep reddedildi.');
    loadAdminNotifications();
  } catch (e) {
    showToast(e.message, 'danger');
  }
}

let cachedAdminPackages = [];

async function loadAdminSettings() {
  try {
    const [pkgData, settingsData] = await Promise.all([API.get('/admin/packages'), API.get('/admin/settings')]);
    cachedAdminPackages = pkgData.packages || [];
    renderAdminPackageSettings();

    const settings = Object.fromEntries((settingsData.settings || []).map((s) => [s.key, s.value]));
    const mondayEl = document.getElementById('settingMondayClosed');
    const cancelEl = document.getElementById('settingCancellationHours');
    if (mondayEl) mondayEl.value = settings.monday_closed ?? '1';
    if (cancelEl) cancelEl.value = settings.cancellation_window_hours ?? '2';
  } catch (e) {
    showToast('Ayarlar yüklenemedi: ' + e.message, 'danger');
  }
}

function renderAdminPackageSettings() {
  const container = document.getElementById('adminPackageSettingsList');
  if (!container) return;

  container.innerHTML = `
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:0.75rem;">
      ${cachedAdminPackages
        .map(
          (pkg) => `
        <div style="background:#FFFFFF; border:1px solid var(--border); border-radius:var(--radius-sm); padding:0.75rem; display:flex; flex-direction:column; gap:0.4rem;">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <span style="font-weight:800; font-size:0.95rem; color:var(--primary-dark);">${pkg.lesson_count} Ders</span>
            <span style="background:rgba(16,185,129,0.1); color:#047857; font-size:0.7rem; font-weight:700; padding:0.1rem 0.4rem; border-radius:var(--radius-full);">${pkg.is_active ? 'Aktif' : 'Pasif'}</span>
          </div>
          <div style="display:flex; gap:0.4rem; align-items:center; margin-top:0.25rem;">
            <input type="number" class="form-control" id="pkgPriceInput-${pkg.id}" value="${pkg.price_try}" style="font-size:0.85rem; padding:0.35rem 0.5rem;">
            <button class="btn-outline" style="padding:0.35rem 0.65rem; font-size:0.75rem;" onclick="adminSavePackagePrice(${pkg.id})">Kaydet</button>
          </div>
        </div>
      `
        )
        .join('')}
    </div>
  `;
}

async function adminSavePackagePrice(packageId) {
  const input = document.getElementById(`pkgPriceInput-${packageId}`);
  if (!input) return;
  try {
    await API.patch(`/admin/packages/${packageId}`, { price_try: parseFloat(input.value) });
    showToast('✓ Paket fiyatı güncellendi.');
    loadAdminSettings();
  } catch (e) {
    showToast(e.message, 'danger');
  }
}

async function adminAddNewPackage() {
  const lessons = parseInt(document.getElementById('newPkgLessons')?.value);
  const price = parseFloat(document.getElementById('newPkgPrice')?.value);

  if (!lessons || !price) {
    alert('Lütfen ders sayısı ve fiyat giriniz.');
    return;
  }

  try {
    await API.post('/admin/packages', { lesson_count: lessons, price_try: price });
    document.getElementById('newPkgLessons').value = '';
    document.getElementById('newPkgPrice').value = '';
    showToast('✓ Yeni paket eklendi.');
    loadAdminSettings();
  } catch (e) {
    showToast(e.message, 'danger');
  }
}

async function adminSaveClubSettings() {
  const monday = document.getElementById('settingMondayClosed')?.value;
  const hours = document.getElementById('settingCancellationHours')?.value;

  try {
    await Promise.all([
      API.put('/admin/settings/monday_closed', { value: monday }),
      API.put('/admin/settings/cancellation_window_hours', { value: hours }),
    ]);
    showToast('✓ Kulüp ayarları güncellendi.');
  } catch (e) {
    showToast(e.message, 'danger');
  }
}

async function loadAdminAuditLog() {
  const container = document.getElementById('secAuditTableBody');
  if (!container) return;

  try {
    const data = await API.get('/admin/audit-log');
    const events = data.events.data || [];

    document.getElementById('secStatLogins').innerText = `${events.filter((e) => e.event_type === 'AUTH_SUCCESS').length} Oturum`;
    document.getElementById('secStatBlocked').innerText = `${events.filter((e) => e.severity === 'danger').length} Kayıt`;

    if (events.length === 0) {
      container.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:1.5rem; color:var(--text-muted);">Kayıtlı güvenlik olayı bulunamadı.</td></tr>`;
      return;
    }

    container.innerHTML = events
      .map((l) => {
        const badge = { success: ['success', 'BAŞARILI'], warning: ['warning', 'UYARI'], danger: ['danger', 'TEHDİT'] }[l.severity] || ['info', 'BİLGİ'];
        return `
        <tr>
          <td style="font-family:monospace; font-size:0.75rem; color:var(--text-muted);">${new Date(l.created_at).toLocaleString('tr-TR')}</td>
          <td style="font-family:monospace; font-size:0.75rem;">${escapeHtml(l.ip_address || '—')}</td>
          <td><div style="font-size:0.7rem; color:var(--text-muted); text-transform:uppercase;">${escapeHtml(l.role || '—')}</div></td>
          <td><span style="font-family:monospace; font-weight:700; font-size:0.75rem; color:var(--primary);">${escapeHtml(l.event_type)}</span></td>
          <td><span class="sec-badge ${badge[0]}">${badge[1]}</span></td>
          <td style="font-size:0.8rem;">${escapeHtml(l.description || '')}</td>
        </tr>
      `;
      })
      .join('');
  } catch (e) {
    container.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:1rem; color:var(--text-muted);">${escapeHtml(e.message)}</td></tr>`;
  }
}

function exportSecurityLogs() {
  window.open('/admin/audit-log/export', '_blank');
}

// ─── WHATSAPP HELPER (pure client-side, no API needed) ─────────────────────────
function sendWhatsAppNotification(actionType, title, date, time, trainer) {
  const userName = session.user?.name || '';
  const refCode = session.user?.ref_code || '';

  let msg = '';
  if (actionType === 'booked') {
    msg = `🏇 *REZERVASYON BİLDİRİMİ*\n\nMerhaba, ben ${userName} (Ref: ${refCode}).\n\n📌 *Aktivite:* ${title}\n📅 *Tarih:* ${date} saat ${time}\n👤 *Eğitmen:* ${trainer}\n\nRandevum başarıyla oluşturulmuştur.`;
  } else if (actionType === 'cancel_query') {
    msg = `Merhaba, ${date} saat ${time} tarihindeki ${title} randevum hakkında bilgi almak istiyorum. (Üye: ${userName})`;
  } else {
    msg = `Merhaba, ${date} saat ${time} tarihindeki ${title} randevum hakkında bilgi almak istiyorum. (Üye: ${userName})`;
  }

  window.open(`https://wa.me/${CLUB_WHATSAPP_NUMBER}?text=${encodeURIComponent(msg)}`, '_blank');
}

// ─── MODALS / TOASTS / UTIL ───────────────────────────────────────────────────
function openModal(id) {
  document.getElementById(id)?.classList.add('open');
}

function closeModal(id) {
  document.getElementById(id)?.classList.remove('open');
}

function showToast(msg, type = 'success') {
  const container = document.getElementById('toastContainer');
  if (!container) return;

  const toast = document.createElement('div');
  toast.className = 'toast';
  if (type === 'danger') toast.style.borderLeftColor = '#EF4444';
  if (type === 'warning') toast.style.borderLeftColor = '#F59E0B';
  toast.style.cursor = 'pointer';
  toast.title = 'Kapatmak için tıklayın';
  toast.innerText = msg;

  toast.addEventListener('click', () => {
    toast.style.opacity = '0';
    setTimeout(() => toast.remove(), 200);
  });

  container.appendChild(toast);
  setTimeout(() => {
    if (toast.parentElement) {
      toast.style.opacity = '0';
      setTimeout(() => toast.remove(), 200);
    }
  }, 4000);
}

function debounce(fn, delay = 250) {
  let timer = null;
  return function (...args) {
    clearTimeout(timer);
    timer = setTimeout(() => fn.apply(this, args), delay);
  };
}

function formatTry(amount) {
  return Number(amount || 0).toLocaleString('tr-TR') + ' ₺';
}

function escapeHtml(str) {
  if (typeof str !== 'string') return str ?? '';
  return str
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#x27;');
}

function togglePackageCategory(cat) {
  const memBox = document.getElementById('categoryMembership');
  const safBox = document.getElementById('categorySafari');
  const memBtn = document.getElementById('tabBtnMembership');
  const safBtn = document.getElementById('tabBtnSafari');

  if (cat === 'membership') {
    memBox.style.display = 'block';
    safBox.style.display = 'none';
    memBtn.classList.add('active');
    safBtn.classList.remove('active');
  } else {
    memBox.style.display = 'none';
    safBox.style.display = 'block';
    safBtn.classList.add('active');
    memBtn.classList.remove('active');
  }
}

// ─── GLOBAL EXPOSURE ──────────────────────────────────────────────────────────
// Vite builds this as an ES module, so top-level declarations are NOT
// automatically attached to `window` the way a plain <script> would — every
// function referenced from an inline onclick="" attribute in the Blade view
// (or generated inside a template string here) needs an explicit assignment.
Object.assign(window, {
  switchGatewayTab,
  submitGatewayLogin,
  logoutToGateway,
  switchView,
  handleFreeCancellation,
  handleLateCancellation,
  selectDate,
  setMatrixDate,
  setMatrixTrainerFilter,
  quickBookSlot,
  confirmLessonBooking,
  openSafariModal,
  updateSafariPrice,
  confirmSafariBooking,
  buyPackage,
  togglePackageCategory,
  filterMemberCategory,
  filterMemberStatus,
  adminExportMembersCSV,
  adminAddCredits,
  adminSendClaimLink,
  sendWhatsAppToMember,
  openNewFamilyModal,
  adminAddFamilyMemberPrompt,
  adminRemoveFamilyMember,
  switchAdminSubTab,
  adminApprovePurchaseRequest,
  adminRejectPurchaseRequest,
  adminSavePackagePrice,
  adminAddNewPackage,
  adminSaveClubSettings,
  exportSecurityLogs,
  trainerMarkAttendance,
  adminMarkAttendance,
  submitTrainerFeedback,
  sendWhatsAppNotification,
  openModal,
  closeModal,
  showToast,
});

// ─── PRELOADER & SPLASH CONTROLLER ───────────────────────────────────────────
function initPreloader() {
  const preloader = document.getElementById('appPreloader');
  if (!preloader) return;

  // Let the splash play for 800ms, then smoothly fade out
  setTimeout(() => {
    preloader.classList.add('fade-out');
    setTimeout(() => {
      preloader.style.display = 'none';
      // Trigger cascade entrance
      document.querySelectorAll('.login-card-container, .main-layout').forEach(el => {
        el.classList.add('stagger-in');
      });
    }, 600);
  }, 850);
}

// ─── BOOT ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  initPreloader();
  bootstrapSession();
  loadTrainerLoginOptions();

  const safariPaxEl = document.getElementById('safariPax');
  if (safariPaxEl) {
    safariPaxEl.addEventListener('change', updateSafariPrice);
    safariPaxEl.addEventListener('input', updateSafariPrice);
  }

  const memberSearchEl = document.getElementById('adminMemberSearch');
  if (memberSearchEl) {
    memberSearchEl.addEventListener('input', debounce(() => loadAdminMembers(), 250));
  }

  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js').catch(() => {});
  }
});
