@extends('admin.layout')

@section('title', 'Importer (OCR)')
@section('page-title', 'Importer un exercice scanné')
@section('page-subtitle', "L'image ou le PDF est passé à l'OCR, puis un exercice est créé — jamais visible par les élèves tant qu'il n'est pas relu et validé.")

@section('content')
    <div class="flex gap-2 mb-6 max-w-lg">
        <button type="button" onclick="switchMode('simple')" id="tab-simple"
                class="flex-1 text-sm font-semibold px-4 py-2 rounded-lg border">
            Un seul exercice
        </button>
        <button type="button" onclick="switchMode('auto')" id="tab-auto"
                class="flex-1 text-sm font-semibold px-4 py-2 rounded-lg border">
            Page entière (auto)
        </button>
    </div>

    <!-- Mode simple : un exercice, type imposé, PAS de corrigé (brouillon à compléter) -->
    <form id="form-simple" class="max-w-lg bg-white border border-slate-200 rounded-xl p-6 space-y-4" enctype="multipart/form-data">
        <p class="text-xs text-slate-500 -mt-1 mb-1">
            Pour une page qui ne contient QU'UN SEUL exercice. Le corrigé reste à compléter manuellement
            (ou avec l'aide de l'IA) ensuite, dans la fiche de l'exercice.
        </p>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Fichier (image ou PDF, 20 Mo max)</label>
            <input id="f-fichier" type="file" accept=".jpg,.jpeg,.png,.pdf" required
                   class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white">
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Chapitre</label>
            <select id="f-chapitre" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"></select>
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
                <label class="block text-xs font-medium text-slate-600 mb-1">Difficulté</label>
                <input id="f-difficulte" type="number" min="1" max="5" value="3" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Année</label>
                <input id="f-annee" type="number" min="2000" value="{{ date('Y') }}" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input id="f-vision" type="checkbox">
            Forcer le passage par la vision IA (scan de mauvaise qualité, écriture manuscrite…)
        </label>

        <button type="submit" id="submit-btn-simple" class="w-full bg-primary hover:bg-primary/90 text-white text-sm font-semibold py-2.5 rounded-lg">
            Importer
        </button>
    </form>

    <!-- Mode auto : la page peut contenir plusieurs exercices, l'IA découpe ET propose un corrigé pour chacun -->
    <form id="form-auto" class="hidden max-w-lg bg-white border border-slate-200 rounded-xl p-6 space-y-4" enctype="multipart/form-data">
        <p class="text-xs text-slate-500 -mt-1 mb-1">
            Pour une page d'examen avec plusieurs exercices numérotés. L'IA détecte chaque exercice
            <strong>et</strong> propose un corrigé complet pour chacun — à relire avant publication.
        </p>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Fichier (image ou PDF, 20 Mo max)</label>
            <input id="fa-fichier" type="file" accept=".jpg,.jpeg,.png,.pdf" required
                   class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white">
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Chapitre</label>
            <select id="fa-chapitre" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"></select>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Difficulté (par défaut)</label>
                <input id="fa-difficulte" type="number" min="1" max="5" value="2" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Année</label>
                <input id="fa-annee" type="number" min="2000" value="{{ date('Y') }}" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input id="fa-vision" type="checkbox">
            Forcer le passage par la vision IA
        </label>
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input id="fa-auto-valider" type="checkbox">
            Publier immédiatement (déconseillé : relire d'abord est plus sûr)
        </label>

        <button type="submit" id="submit-btn-auto" class="w-full bg-primary hover:bg-primary/90 text-white text-sm font-semibold py-2.5 rounded-lg">
            🤖 Importer et détecter automatiquement
        </button>
    </form>

    <div id="result-simple" class="hidden max-w-lg mt-5 bg-emerald-50 border border-emerald-200 rounded-xl p-5">
        <p class="font-semibold text-emerald-900 mb-1">Exercice importé en brouillon</p>
        <p class="text-sm text-emerald-700 mb-3">Relis l'énoncé extrait par l'OCR et complète le corrigé avant de le valider.</p>
        <a id="result-link" href="#" class="inline-block bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">
            Compléter le corrigé maintenant
        </a>
    </div>

    <div id="result-auto" class="hidden max-w-2xl mt-6 space-y-3"></div>
@endsection

@push('scripts')
<script>
function switchMode(mode) {
    document.getElementById('form-simple').classList.toggle('hidden', mode !== 'simple');
    document.getElementById('form-auto').classList.toggle('hidden', mode !== 'auto');
    document.getElementById('tab-simple').className = `flex-1 text-sm font-semibold px-4 py-2 rounded-lg border ${mode === 'simple' ? 'bg-primary text-white border-primary' : 'border-slate-300 text-slate-600 hover:bg-slate-100'}`;
    document.getElementById('tab-auto').className = `flex-1 text-sm font-semibold px-4 py-2 rounded-lg border ${mode === 'auto' ? 'bg-primary text-white border-primary' : 'border-slate-300 text-slate-600 hover:bg-slate-100'}`;
    document.getElementById('result-simple').classList.add('hidden');
    document.getElementById('result-auto').classList.add('hidden');
}

async function loadChapitresInto(selectId) {
    const data = await apiFetch('/api/chapitres');
    const chapitres = (data.data || data).map(c => ({ id: c.id, label: `${c.titre} — ${c.matiere?.nom || ''}` }));
    fillSelect(document.getElementById(selectId), chapitres, { placeholder: 'Choisir un chapitre' });
}

// --- Mode simple (inchangé) ---------------------------------------------
document.getElementById('form-simple').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('submit-btn-simple');
    const fichier = document.getElementById('f-fichier').files[0];
    if (!fichier) return;

    const formData = new FormData();
    formData.append('fichier', fichier);
    formData.append('chapitre_id', document.getElementById('f-chapitre').value);
    formData.append('type', document.getElementById('f-type').value);
    formData.append('difficulte', document.getElementById('f-difficulte').value);
    formData.append('annee_origine', document.getElementById('f-annee').value);
    formData.append('forcer_vision_ia', document.getElementById('f-vision').checked ? '1' : '0');

    btn.disabled = true;
    btn.textContent = 'Import en cours… (OCR)';

    try {
        const data = await apiFetch('/api/admin/exercices/import', { method: 'POST', body: formData });
        const ex = data.data || data;
        toast('Exercice importé en brouillon');
        document.getElementById('result-simple').classList.remove('hidden');
        document.getElementById('result-link').href = `/admin/exercices/${ex.id}/modifier`;
        document.getElementById('form-simple').reset();
        await loadChapitresInto('f-chapitre');
    } catch (err) {
        toastFromError(err, "L'import a échoué.");
    } finally {
        btn.disabled = false;
        btn.textContent = 'Importer';
    }
});

// --- Mode auto : OCR + découpage IA + proposition de corrigé, en un seul appel ---
document.getElementById('form-auto').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('submit-btn-auto');
    const fichier = document.getElementById('fa-fichier').files[0];
    if (!fichier) return;

    const formData = new FormData();
    formData.append('fichier', fichier);
    formData.append('chapitre_id', document.getElementById('fa-chapitre').value);
    formData.append('annee_origine', document.getElementById('fa-annee').value);
    formData.append('difficulte', document.getElementById('fa-difficulte').value);
    formData.append('forcer_vision_ia', document.getElementById('fa-vision').checked ? '1' : '0');
    formData.append('auto_valider', document.getElementById('fa-auto-valider').checked ? '1' : '0');

    btn.disabled = true;
    btn.textContent = '🤖 Analyse en cours… (OCR + IA)';
    document.getElementById('result-auto').classList.add('hidden');

    try {
        const data = await apiFetch('/api/admin/exercices/import-auto', { method: 'POST', body: formData });
        renderResultAuto(data);
        toast(data.message);
        document.getElementById('form-auto').reset();
        await loadChapitresInto('fa-chapitre');
    } catch (err) {
        toastFromError(err, "L'import automatique a échoué.");
    } finally {
        btn.disabled = false;
        btn.textContent = '🤖 Importer et détecter automatiquement';
    }
});

function renderResultAuto(data) {
    const wrap = document.getElementById('result-auto');
    wrap.classList.remove('hidden');

    const banner = `
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4">
            <p class="font-semibold text-emerald-900">${escapeHtml(data.message)}</p>
            <p class="text-sm text-emerald-700 mt-1">Méthode OCR utilisée : ${escapeHtml(data.ocr_methode)}</p>
        </div>
    `;

    const cards = data.data.map(ex => `
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <div class="flex items-center justify-between mb-2">
                <span class="bg-slate-100 text-slate-600 text-xs font-semibold px-2 py-1 rounded-full">${ex.type}</span>
                <span class="text-xs ${ex.statut === 'valide' ? 'text-emerald-600' : 'text-amber-600'} font-semibold">${ex.statut === 'valide' ? 'Publié' : 'Brouillon'}</span>
            </div>
            <p class="font-medium text-slate-800 mb-3">${escapeHtml(ex.enonce)}</p>
            <p class="text-sm text-emerald-700 font-semibold mb-1">Réponse proposée : ${escapeHtml((ex.reponses_correctes || []).join(', '))}</p>
            <p class="text-xs text-slate-500 italic border-t border-slate-100 pt-3 mt-2">${escapeHtml(ex.explication_officielle || '')}</p>
            <a href="/admin/exercices/${ex.id}/modifier" class="inline-block mt-3 text-primary text-xs font-semibold">Relire / corriger →</a>
        </div>
    `).join('');

    wrap.innerHTML = banner + cards;
    wrap.scrollIntoView({ behavior: 'smooth' });
}

loadChapitresInto('f-chapitre');
loadChapitresInto('fa-chapitre');
switchMode('simple');
</script>
@endpush