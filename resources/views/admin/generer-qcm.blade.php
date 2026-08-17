@extends('admin.layout')

@section('title', 'Générer des QCM (IA)')
@section('page-title', 'Générer des QCM par IA')
@section('page-subtitle', "L'IA crée un lot de QCM inédits sur le thème du chapitre choisi, avec une difficulté croissante d'une question à l'autre.")

@section('content')
    <form id="form" class="max-w-lg bg-white border border-slate-200 rounded-xl p-6 space-y-4">
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Chapitre</label>
            <select id="f-chapitre" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"></select>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Nombre de questions</label>
                <input id="f-nombre" type="number" min="1" max="15" value="5" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Difficulté de départ (1 à 5)</label>
                <input id="f-difficulte" type="number" min="1" max="5" value="1" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
            </div>
        </div>
        <p class="text-xs text-slate-500 -mt-2">La difficulté monte progressivement jusqu'à 5 sur la dernière question du lot.</p>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input id="f-auto-valider" type="checkbox" checked>
            Publier immédiatement aux élèves (sinon : créés en brouillon, à relire dans "Brouillons")
        </label>

        <button type="submit" id="submit-btn" class="w-full bg-primary hover:bg-primary/90 text-white text-sm font-semibold py-2.5 rounded-lg">
            🤖 Générer
        </button>
    </form>

    <div id="result" class="hidden max-w-2xl mt-6 space-y-3"></div>
@endsection

@push('scripts')
<script>
const TYPE_ICON = '📝';

async function loadChapitres() {
    const data = await apiFetch('/api/chapitres');
    const chapitres = (data.data || data).map(c => ({ id: c.id, label: `${c.titre} — ${c.matiere?.nom || ''}` }));
    fillSelect(document.getElementById('f-chapitre'), chapitres, { placeholder: 'Choisir un chapitre' });
}

function renderResultat(message, questions, autoValider) {
    const wrap = document.getElementById('result');
    wrap.classList.remove('hidden');

    const bannerColor = autoValider ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-amber-50 border-amber-200 text-amber-900';
    const banner = `
        <div class="${bannerColor} border rounded-xl p-4">
            <p class="font-semibold">${escapeHtml(message)}</p>
            ${autoValider
                ? '<p class="text-sm mt-1">Déjà visibles par les élèves.</p>'
                : '<p class="text-sm mt-1">En attente de relecture — <a href="' + '{{ route('admin.brouillons') }}' + '" class="underline font-semibold">voir les brouillons</a>.</p>'}
        </div>
    `;

    const cards = questions.map(q => `
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-slate-500">${TYPE_ICON} QCM</span>
                <span class="text-xs text-slate-400">${'★'.repeat(q.difficulte)}${'☆'.repeat(5 - q.difficulte)}</span>
            </div>
            <p class="font-medium text-slate-800 mb-3">${escapeHtml(q.enonce)}</p>
            <div class="space-y-1 mb-3">
                ${q.options.map(opt => {
                    const correct = q.reponses_correctes.includes(opt);
                    return `<p class="text-sm ${correct ? 'text-emerald-700 font-semibold' : 'text-slate-500'}">${correct ? '✓' : '•'} ${escapeHtml(opt)}</p>`;
                }).join('')}
            </div>
            <p class="text-xs text-slate-500 italic border-t border-slate-100 pt-3">${escapeHtml(q.explication_officielle)}</p>
            <a href="/admin/exercices/${q.id}/modifier" class="inline-block mt-3 text-primary text-xs font-semibold">Modifier cette question →</a>
        </div>
    `).join('');

    wrap.innerHTML = banner + cards;
    wrap.scrollIntoView({ behavior: 'smooth' });
}

document.getElementById('form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const chapitreId = document.getElementById('f-chapitre').value;
    if (!chapitreId) {
        toast('Choisis un chapitre.', 'error');
        return;
    }

    const autoValider = document.getElementById('f-auto-valider').checked;
    const btn = document.getElementById('submit-btn');
    btn.disabled = true;
    btn.textContent = '🤖 Génération en cours…';
    document.getElementById('result').classList.add('hidden');

    try {
        const data = await apiFetch(`/api/admin/chapitres/${chapitreId}/generer-qcm`, {
            method: 'POST',
            body: {
                nombre: Number(document.getElementById('f-nombre').value) || 5,
                difficulte_depart: Number(document.getElementById('f-difficulte').value) || 1,
                auto_valider: autoValider,
            },
        });
        renderResultat(data.message, data.data, autoValider);
        toast(data.message);
    } catch (err) {
        toastFromError(err, "La génération a échoué.");
    } finally {
        btn.disabled = false;
        btn.textContent = '🤖 Générer';
    }
});

loadChapitres();
</script>
@endpush