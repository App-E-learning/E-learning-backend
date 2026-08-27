@extends('admin.layout')

@section('title', 'Brouillons')
@section('page-title', 'Brouillons à valider')
@section('page-subtitle', "Exercices importés par OCR, en attente de relecture. Invisibles pour les élèves tant qu'ils ne sont pas validés.")

@section('content')
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-4 py-3">Énoncé (extrait OCR)</th>
                    <th class="text-left px-4 py-3">Chapitre</th>
                    <th class="text-left px-4 py-3">Type</th>
                    <th class="text-left px-4 py-3">Corrigé</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody id="rows" class="divide-y divide-slate-100"></tbody>
        </table>
        <p id="empty" class="hidden text-center text-slate-400 text-sm py-8">Aucun brouillon en attente — tout est à jour 🎉</p>
    </div>
@endsection

@push('scripts')
<script>
const TYPE_LABELS = { qcm: 'QCM', numerique: 'Numérique', texte_court: 'Texte court' };

async function load() {
    const data = await apiFetch('/api/admin/exercices/brouillons');
    const items = data.data || data;

    const rows = document.getElementById('rows');
    document.getElementById('empty').classList.toggle('hidden', items.length > 0);
    rows.innerHTML = items.map(ex => {
        // La liste des brouillons ne renvoie pas le corrigé complet (voir
        // ExerciceImportController::brouillons) — on sait seulement s'il
        // existe et contient au moins une réponse, pas son contenu.
        const aCorrige = ex.corrige_pret === true;
        return `
        <tr id="row-${ex.id}">
            <td class="px-4 py-3 max-w-md">
                <p class="line-clamp-2 text-slate-800">${escapeHtml(ex.enonce)}</p>
            </td>
            <td class="px-4 py-3 text-slate-500 whitespace-nowrap">${escapeHtml(ex.chapitre?.titre || '—')}</td>
            <td class="px-4 py-3">
                <span class="bg-slate-100 text-slate-600 text-xs font-semibold px-2 py-1 rounded-full">${TYPE_LABELS[ex.type] || ex.type}</span>
            </td>
            <td class="px-4 py-3">
                ${aCorrige
                    ? '<span class="text-emerald-600 text-xs font-semibold">✓ prêt</span>'
                    : '<span class="text-red-500 text-xs font-semibold">✗ manquant</span>'}
            </td>
            <td class="px-4 py-3 text-right whitespace-nowrap space-x-3">
                <a href="/admin/exercices/${ex.id}/modifier" class="text-primary text-xs font-semibold">Relire / compléter →</a>
                <button
                    onclick="valider(${ex.id})"
                    id="valider-btn-${ex.id}"
                    class="text-xs font-semibold px-3 py-1.5 rounded-lg ${aCorrige ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-slate-100 text-slate-400 cursor-not-allowed'}"
                    ${aCorrige ? '' : 'disabled title="Aucune réponse enregistrée — complète le corrigé d\'abord"'}
                >
                    ✓ Valider
                </button>
            </td>
        </tr>
    `;
    }).join('');
}

async function valider(id) {
    const btn = document.getElementById(`valider-btn-${id}`);
    btn.disabled = true;
    btn.textContent = '…';

    try {
        await apiFetch(`/api/admin/exercices/${id}/valider`, { method: 'POST' });
        toast('Exercice publié aux élèves');
        document.getElementById(`row-${id}`).remove();
        if (document.getElementById('rows').children.length === 0) {
            document.getElementById('empty').classList.remove('hidden');
        }
    } catch (err) {
        toastFromError(err, "Impossible de valider — le corrigé est peut-être vide.");
        btn.disabled = false;
        btn.textContent = '✓ Valider';
    }
}

load();
</script>
@endpush