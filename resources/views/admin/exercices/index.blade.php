@extends('admin.layout')

@section('title', 'Exercices')
@section('page-title', 'Exercices')
@section('page-subtitle', "La banque d'exercices, par chapitre.")

@section('content')
    <div class="flex flex-wrap items-end gap-3 mb-4">
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Chapitre</label>
            <select id="filter-chapitre" class="border border-slate-200 rounded-lg px-3 py-2 text-sm min-w-[220px]"></select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Type</label>
            <select id="filter-type" class="border border-slate-200 rounded-lg px-3 py-2 text-sm">
                <option value="">Tous</option>
                <option value="qcm">QCM</option>
                <option value="numerique">Numérique</option>
                <option value="texte_court">Texte court</option>
            </select>
        </div>
        <button onclick="load()" class="border border-slate-300 hover:bg-slate-100 text-sm font-semibold px-4 py-2 rounded-lg">Filtrer</button>
        <a href="{{ route('admin.exercices.create') }}" class="ml-auto bg-primary hover:bg-primary/90 text-white text-sm font-semibold px-4 py-2 rounded-lg">
            + Nouvel exercice
        </a>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-4 py-3">Énoncé</th>
                    <th class="text-left px-4 py-3">Chapitre</th>
                    <th class="text-left px-4 py-3">Type</th>
                    <th class="text-left px-4 py-3">Difficulté</th>
                    <th class="text-left px-4 py-3">Année</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody id="rows" class="divide-y divide-slate-100"></tbody>
        </table>
        <p id="empty" class="hidden text-center text-slate-400 text-sm py-8">Aucun exercice pour ces filtres.</p>
    </div>
@endsection

@push('scripts')
<script>
const TYPE_LABELS = { qcm: 'QCM', numerique: 'Numérique', texte_court: 'Texte court' };

async function load() {
    const chapitreId = document.getElementById('filter-chapitre').value;
    const type = document.getElementById('filter-type').value;

    const params = new URLSearchParams({ per_page: 50 });
    if (chapitreId) params.set('chapitre_id', chapitreId);
    if (type) params.set('type', type);

    const data = await apiFetch(`/api/exercices?${params.toString()}`);
    const items = data.data || data;

    const rows = document.getElementById('rows');
    document.getElementById('empty').classList.toggle('hidden', items.length > 0);
    rows.innerHTML = items.map(ex => `
        <tr>
            <td class="px-4 py-3 max-w-md">
                <p class="line-clamp-2 text-slate-800">${escapeHtml(ex.enonce)}</p>
            </td>
            <td class="px-4 py-3 text-slate-500 whitespace-nowrap">${escapeHtml(ex.chapitre?.titre || '—')}</td>
            <td class="px-4 py-3">
                <span class="bg-slate-100 text-slate-600 text-xs font-semibold px-2 py-1 rounded-full">${TYPE_LABELS[ex.type] || ex.type}</span>
            </td>
            <td class="px-4 py-3 text-slate-500">${'★'.repeat(ex.difficulte)}${'☆'.repeat(5 - ex.difficulte)}</td>
            <td class="px-4 py-3 text-slate-500">${ex.annee_origine}</td>
            <td class="px-4 py-3 text-right whitespace-nowrap">
                <a href="/admin/exercices/${ex.id}/modifier" class="text-primary text-xs font-semibold mr-3">Modifier</a>
                <button onclick="deleteItem(${ex.id})" class="text-red-600 text-xs font-semibold">Supprimer</button>
            </td>
        </tr>
    `).join('');
}

async function deleteItem(id) {
    if (!confirmAction('Supprimer cet exercice et son corrigé ?')) return;
    try {
        await apiFetch(`/api/exercices/${id}`, { method: 'DELETE' });
        toast('Exercice supprimé');
        load();
    } catch (err) {
        toastFromError(err);
    }
}

async function init() {
    const chapData = await apiFetch('/api/chapitres');
    const chapitres = (chapData.data || chapData).map(c => ({ id: c.id, label: c.titre }));
    fillSelect(document.getElementById('filter-chapitre'), chapitres, { placeholder: 'Tous les chapitres' });
    load();
}

init();
</script>
@endpush