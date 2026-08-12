@extends('admin.layout')

@section('title', $exerciceId ? 'Modifier un exercice' : 'Nouvel exercice')
@section('page-title', $exerciceId ? 'Modifier un exercice' : 'Nouvel exercice')
@section('page-subtitle', "L'énoncé et le corrigé sont enregistrés ensemble — un exercice sans corrigé ne peut jamais être validé pour les élèves.")

@section('content')
    <div id="draft-banner" class="hidden mb-5 bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-center justify-between">
        <div>
            <p class="font-semibold text-amber-900">Brouillon importé par OCR</p>
            <p class="text-sm text-amber-700">Relis et corrige l'énoncé ci-dessous, complète le corrigé, puis valide-le pour le rendre visible aux élèves.</p>
        </div>
        <button onclick="validerBrouillon()" class="bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold px-4 py-2 rounded-lg whitespace-nowrap">
            ✓ Valider cet exercice
        </button>
    </div>

    <div id="ocr-raw-wrap" class="hidden mb-5">
        <label class="block text-xs font-medium text-slate-500 mb-1">Texte brut extrait par l'OCR (référence, non modifiable)</label>
        <div id="ocr-raw" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-500 whitespace-pre-wrap max-h-32 overflow-y-auto"></div>
    </div>

    <form id="form" class="max-w-2xl space-y-6">
        <div class="bg-white border border-slate-200 rounded-xl p-5 space-y-4">
            <h2 class="font-semibold text-slate-800">Énoncé</h2>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Chapitre</label>
                <select id="f-chapitre" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"></select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Énoncé</label>
                <textarea id="f-enonce" rows="4" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"></textarea>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Image (URL, optionnel)</label>
                <input id="f-image" type="url" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="https://…">
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Type</label>
                    <select id="f-type" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                        <option value="qcm">QCM</option>
                        <option value="numerique">Numérique</option>
                        <option value="texte_court">Texte court</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Difficulté (1 à 5)</label>
                    <input id="f-difficulte" type="number" min="1" max="5" required value="3" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Année d'origine</label>
                    <input id="f-annee" type="number" min="2000" required value="{{ date('Y') }}" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <div id="options-wrap">
                <label class="block text-xs font-medium text-slate-600 mb-1">Options (QCM)</label>
                <div id="options-list" class="space-y-2"></div>
                <button type="button" onclick="addOption()" class="text-primary text-xs font-semibold mt-2">+ Ajouter une option</button>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-5 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-slate-800">Corrigé</h2>
                <button type="button" onclick="proposerCorrectionIa()" id="ia-btn"
                        class="text-xs font-semibold bg-primary/10 text-primary px-3 py-1.5 rounded-lg hover:bg-primary/20">
                    🤖 Proposer une correction avec l'IA
                </button>
            </div>

            <p id="ia-warning" class="hidden text-xs bg-amber-50 text-amber-800 border border-amber-200 rounded-lg px-3 py-2">
                Proposition générée par l'IA — relis-la avant d'enregistrer, elle peut se tromper.
            </p>

            <div id="reponses-qcm-wrap">
                <label class="block text-xs font-medium text-slate-600 mb-2">Réponse(s) correcte(s) — coche parmi les options ci-dessus</label>
                <div id="reponses-qcm-list" class="space-y-1 text-sm"></div>
            </div>

            <div id="reponses-libres-wrap" class="hidden">
                <label class="block text-xs font-medium text-slate-600 mb-1">Réponse(s) correcte(s) acceptée(s)</label>
                <div id="reponses-libres-list" class="space-y-2"></div>
                <button type="button" onclick="addReponseLibre()" class="text-primary text-xs font-semibold mt-2">+ Ajouter une réponse acceptée</button>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Explication officielle (optionnel)</label>
                <textarea id="f-explication" rows="3" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"></textarea>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Barème (optionnel)</label>
                <input id="f-bareme" type="number" step="0.01" min="0" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm max-w-[140px]">
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-primary hover:bg-primary/90 text-white text-sm font-semibold px-5 py-2.5 rounded-lg">
                Enregistrer
            </button>
            <a href="{{ route('admin.exercices') }}" class="text-sm text-slate-500 px-2 py-2.5">Annuler</a>
        </div>
    </form>
@endsection

@push('scripts')
<script>
const exerciceId = @json($exerciceId);
let currentStatut = 'valide';

function optionRow(value = '') {
    const row = document.createElement('div');
    row.className = 'flex gap-2';
    row.innerHTML = `
        <input type="text" value="${escapeHtml(value)}" class="opt-input flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm">
        <button type="button" onclick="this.parentElement.remove(); syncReponsesQcm();" class="text-red-500 text-xs px-2">✕</button>
    `;
    row.querySelector('.opt-input').addEventListener('input', syncReponsesQcm);
    return row;
}

function addOption(value = '') {
    document.getElementById('options-list').appendChild(optionRow(value));
    syncReponsesQcm();
}

function getOptions() {
    return [...document.querySelectorAll('#options-list .opt-input')].map(i => i.value.trim()).filter(Boolean);
}

function syncReponsesQcm() {
    const options = getOptions();
    const checked = new Set(getReponsesCorrectesQcm());
    const wrap = document.getElementById('reponses-qcm-list');
    wrap.innerHTML = options.map(opt => `
        <label class="flex items-center gap-2">
            <input type="checkbox" class="reponse-qcm-check" value="${escapeHtml(opt)}" ${checked.has(opt) ? 'checked' : ''}>
            <span>${escapeHtml(opt)}</span>
        </label>
    `).join('') || '<p class="text-xs text-slate-400">Ajoute d\'abord des options ci-dessus.</p>';
}

function getReponsesCorrectesQcm() {
    return [...document.querySelectorAll('.reponse-qcm-check:checked')].map(c => c.value);
}

function reponseLibreRow(value = '') {
    const row = document.createElement('div');
    row.className = 'flex gap-2';
    row.innerHTML = `
        <input type="text" value="${escapeHtml(value)}" class="reponse-libre-input flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm">
        <button type="button" onclick="this.parentElement.remove()" class="text-red-500 text-xs px-2">✕</button>
    `;
    return row;
}

function addReponseLibre(value = '') {
    document.getElementById('reponses-libres-list').appendChild(reponseLibreRow(value));
}

function getReponsesLibres() {
    return [...document.querySelectorAll('.reponse-libre-input')].map(i => i.value.trim()).filter(Boolean);
}

function toggleTypeUi() {
    const type = document.getElementById('f-type').value;
    const isQcm = type === 'qcm';
    document.getElementById('options-wrap').classList.toggle('hidden', !isQcm);
    document.getElementById('reponses-qcm-wrap').classList.toggle('hidden', !isQcm);
    document.getElementById('reponses-libres-wrap').classList.toggle('hidden', isQcm);
}
document.getElementById('f-type').addEventListener('change', toggleTypeUi);

async function loadChapitres(selected = null) {
    const data = await apiFetch('/api/chapitres');
    const chapitres = (data.data || data).map(c => ({ id: c.id, label: `${c.titre} — ${c.matiere?.nom || ''}` }));
    fillSelect(document.getElementById('f-chapitre'), chapitres, { placeholder: 'Choisir un chapitre', selected });
}

async function loadExisting() {
    const data = await apiFetch(`/api/exercices/${exerciceId}?with_corrige=1`);
    const ex = data.data || data;
    currentStatut = ex.statut || 'valide';

    if (ex.origine === 'import_ocr') {
        document.getElementById('draft-banner').classList.toggle('hidden', currentStatut !== 'brouillon');
        if (ex.texte_ocr_brut) {
            document.getElementById('ocr-raw-wrap').classList.remove('hidden');
            document.getElementById('ocr-raw').textContent = ex.texte_ocr_brut;
        }
    }

    await loadChapitres(ex.chapitre_id);
    document.getElementById('f-enonce').value = ex.enonce || '';
    document.getElementById('f-image').value = ex.image_url || '';
    document.getElementById('f-type').value = ex.type;
    document.getElementById('f-difficulte').value = ex.difficulte;
    document.getElementById('f-annee').value = ex.annee_origine;

    document.getElementById('options-list').innerHTML = '';
    (ex.options || []).forEach(o => addOption(o));
    toggleTypeUi();

    const reponses = ex.corrige?.reponses_correctes || [];
    if (ex.type === 'qcm') {
        syncReponsesQcm();
        reponses.forEach(r => {
            const cb = [...document.querySelectorAll('.reponse-qcm-check')].find(c => c.value === r);
            if (cb) cb.checked = true;
        });
    } else {
        document.getElementById('reponses-libres-list').innerHTML = '';
        reponses.forEach(r => addReponseLibre(r));
    }

    document.getElementById('f-explication').value = ex.corrige?.explication_officielle || '';
    document.getElementById('f-bareme').value = ex.corrige?.bareme ?? '';
}

async function validerBrouillon() {
    try {
        await apiFetch(`/api/admin/exercices/${exerciceId}/valider`, { method: 'POST' });
        toast('Exercice validé — visible par les élèves');
        document.getElementById('draft-banner').classList.add('hidden');
        currentStatut = 'valide';
    } catch (err) {
        toastFromError(err, "Impossible de valider : complète d'abord le corrigé puis enregistre.");
    }
}

async function proposerCorrectionIa() {
    const enonce = document.getElementById('f-enonce').value.trim();
    if (!enonce) {
        toast("Renseigne d'abord l'énoncé.", 'error');
        return;
    }
    const type = document.getElementById('f-type').value;
    const btn = document.getElementById('ia-btn');
    btn.disabled = true;
    btn.textContent = '🤖 Réflexion en cours…';

    try {
        const data = await apiFetch('/api/admin/ia/proposer-correction', {
            method: 'POST',
            body: { enonce, type, options: getOptions() },
        });
        const proposition = data.data || data;

        // Si peu d'options existaient pour un QCM, l'IA peut en proposer 4.
        if (type === 'qcm' && proposition.options_proposees.length > 0 && getOptions().length < 4) {
            document.getElementById('options-list').innerHTML = '';
            proposition.options_proposees.forEach(o => addOption(o));
        }

        if (type === 'qcm') {
            syncReponsesQcm();
            proposition.reponses_correctes.forEach(r => {
                const cb = [...document.querySelectorAll('.reponse-qcm-check')].find(c => c.value === r);
                if (cb) cb.checked = true;
            });
        } else {
            document.getElementById('reponses-libres-list').innerHTML = '';
            proposition.reponses_correctes.forEach(r => addReponseLibre(r));
        }

        document.getElementById('f-explication').value = proposition.explication_officielle;
        document.getElementById('ia-warning').classList.remove('hidden');
        toast('Proposition générée — relis-la avant d\'enregistrer');
    } catch (err) {
        toastFromError(err, "L'IA n'a pas pu proposer de correction.");
    } finally {
        btn.disabled = false;
        btn.textContent = "🤖 Proposer une correction avec l'IA";
    }
}

document.getElementById('form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const type = document.getElementById('f-type').value;

    const payload = {
        chapitre_id: document.getElementById('f-chapitre').value,
        enonce: document.getElementById('f-enonce').value,
        image_url: document.getElementById('f-image').value || null,
        type,
        difficulte: Number(document.getElementById('f-difficulte').value),
        annee_origine: Number(document.getElementById('f-annee').value),
        corrige: {
            reponses_correctes: type === 'qcm' ? getReponsesCorrectesQcm() : getReponsesLibres(),
            explication_officielle: document.getElementById('f-explication').value || null,
            bareme: document.getElementById('f-bareme').value ? Number(document.getElementById('f-bareme').value) : null,
        },
    };
    if (type === 'qcm') payload.options = getOptions();

    try {
        if (exerciceId) {
            await apiFetch(`/api/exercices/${exerciceId}`, { method: 'PUT', body: payload });
            toast('Exercice mis à jour');
        } else {
            const created = await apiFetch('/api/exercices', { method: 'POST', body: payload });
            toast('Exercice créé');
            const newId = (created.data || created).id;
            window.location.href = `/admin/exercices/${newId}/modifier`;
            return;
        }
    } catch (err) {
        toastFromError(err);
    }
});

(async function init() {
    if (exerciceId) {
        await loadExisting();
    } else {
        await loadChapitres();
        addOption('');
        addOption('');
        toggleTypeUi();
        syncReponsesQcm();
    }
})();
</script>
@endpush