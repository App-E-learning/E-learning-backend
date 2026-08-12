// public/admin.js
// Helpers partagés par toutes les pages du back-office. Pas de build step :
// ce fichier est chargé tel quel en <script src="/admin.js">.

function getCookie(name) {
  const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
  return match ? decodeURIComponent(match[2]) : null;
}

/**
 * Appelle l'API JSON existante (/api/*) en réutilisant la session du
 * back-office (Sanctum "stateful" — voir EnsureFrontendRequestsAreStateful
 * dans bootstrap/app.php). Pas de token à gérer : le cookie de session
 * suffit tant que l'admin est connecté sur la même origine.
 */
async function apiFetch(url, options = {}) {
  const isFormData = options.body instanceof FormData;
  const headers = Object.assign(
    {
      Accept: 'application/json',
      'X-XSRF-TOKEN': getCookie('XSRF-TOKEN'),
    },
    isFormData ? {} : { 'Content-Type': 'application/json' },
    options.headers || {}
  );

  const res = await fetch(url, {
    credentials: 'same-origin',
    ...options,
    headers,
    body: isFormData ? options.body : options.body ? JSON.stringify(options.body) : undefined,
  });

  let data = null;
  try {
    data = await res.json();
  } catch (e) {
    data = null;
  }

  if (!res.ok) {
    const error = new Error((data && data.message) || `Erreur ${res.status}`);
    error.status = res.status;
    error.errors = data && data.errors ? data.errors : null;
    error.payload = data;
    throw error;
  }

  return data;
}

function toast(message, type = 'success') {
  const container = document.getElementById('toast-container');
  if (!container) return;

  const colors = {
    success: 'bg-emerald-600',
    error: 'bg-red-600',
    info: 'bg-slate-800',
  };

  const el = document.createElement('div');
  el.className = `${colors[type] || colors.info} text-white text-sm px-4 py-3 rounded-lg shadow-lg mb-2 transition-opacity duration-300`;
  el.textContent = message;
  container.appendChild(el);

  setTimeout(() => {
    el.classList.add('opacity-0');
    setTimeout(() => el.remove(), 300);
  }, 3500);
}

/** Affiche les erreurs de validation Laravel (422) sous forme de toast lisible. */
function toastFromError(err, fallback = 'Une erreur est survenue.') {
  if (err.errors) {
    const firstField = Object.keys(err.errors)[0];
    toast(err.errors[firstField][0], 'error');
  } else {
    toast(err.message || fallback, 'error');
  }
}

function escapeHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function confirmAction(message) {
  return window.confirm(message);
}

/** Remplit un <select> à partir d'une liste { id, label }. */
function fillSelect(select, items, { placeholder = 'Sélectionner…', selected = null } = {}) {
  select.innerHTML =
    `<option value="">${escapeHtml(placeholder)}</option>` +
    items.map((it) => `<option value="${it.id}" ${String(it.id) === String(selected) ? 'selected' : ''}>${escapeHtml(it.label)}</option>`).join('');
}