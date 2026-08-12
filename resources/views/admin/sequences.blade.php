@extends('admin.layout')

@section('title', 'Séquences')
@section('page-title', 'Séquences')
@section('page-subtitle', "Les séquences rythment le programme dans le temps : un chapitre n'est visible côté élève qu'une fois la date de début de sa séquence atteinte.")

@section('content')
    <div class="flex justify-end mb-4">
        <button onclick="openForm()" class="bg-primary hover:bg-primary/90 text-white text-sm font-semibold px-4 py-2 rounded-lg">
            + Nouvelle séquence
        </button>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-4 py-3">Niveau</th>
                    <th class="text-left px-4 py-3">Nom</th>
                    <th class="text-left px-4 py-3">Ordre</th>
                    <th class="text-left px-4 py-3">Début</th>
                    <th class="text-left px-4 py-3">Fin</th>
                    <th class="text-left px-4 py-3">Statut</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody id="rows" class="divide-y divide-slate-100"></tbody>
        </table>
        <p id="empty" class="hidden text-center text-slate-400 text-sm py-8">Aucune séquence pour le moment.</p>
    </div>

    <div id="modal" class="hidden fixed inset-0 bg-black/30 flex items-center justify-center z-40">
        <div class="bg-white rounded-2xl shadow-lg w-full max-w-md p-6">
            <h2 id="modal-title" class="text-lg font-bold mb-4">Nouvelle séquence</h2>
            <form id="form" class="space-y-4">
                <input type="hidden" id="f-id">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Niveau</label>
                    <select id="f-niveau" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"></select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Nom</label>
                    <input id="f-nom" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="1ère séquence">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Ordre</label>
                    <input id="f-ordre" type="number" min="1" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Date de début</label>
                        <input id="f-debut" type="date" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Date de fin (optionnel)</label>
                        <input id="f-fin" type="date" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-primary hover:bg-primary/90 text-white text-sm font-semibold py-2.5 rounded-lg">Enregistrer</button>
                    <button type="button" onclick="closeForm()" class="px-4 text-sm text-slate-500">Annuler</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
let items = [];
let niveaux = [];

async function load() {
    const [seqData, nivData] = await Promise.all([
        apiFetch('/api/sequences'),
        apiFetch('/api/niveaux'),
    ]);
    items = seqData.data || seqData;
    niveaux = (nivData.data || nivData).map(n => ({ id: n.id, label: n.nom }));
    fillSelect(document.getElementById('f-niveau'), niveaux, { placeholder: 'Choisir un niveau' });
    render();
}

function render() {
    const rows = document.getElementById('rows');
    document.getElementById('empty').classList.toggle('hidden', items.length > 0);
    rows.innerHTML = items.map(s => `
        <tr>
            <td class="px-4 py-3">${escapeHtml(s.niveau?.nom || '—')}</td>
            <td class="px-4 py-3 font-medium">${escapeHtml(s.nom)}</td>
            <td class="px-4 py-3 text-slate-500">${s.ordre}</td>
            <td class="px-4 py-3 text-slate-500">${escapeHtml(s.date_debut)}</td>
            <td class="px-4 py-3 text-slate-500">${escapeHtml(s.date_fin || '—')}</td>
            <td class="px-4 py-3">
                ${s.est_debloquee
                    ? '<span class="bg-emerald-50 text-emerald-700 text-xs font-semibold px-2 py-1 rounded-full">Débloquée</span>'
                    : '<span class="bg-slate-100 text-slate-500 text-xs font-semibold px-2 py-1 rounded-full">À venir</span>'}
            </td>
            <td class="px-4 py-3 text-right whitespace-nowrap">
                <button onclick='editItem(${JSON.stringify(s)})' class="text-primary text-xs font-semibold mr-3">Modifier</button>
                <button onclick="deleteItem(${s.id})" class="text-red-600 text-xs font-semibold">Supprimer</button>
            </td>
        </tr>
    `).join('');
}

function openForm() {
    document.getElementById('modal-title').textContent = 'Nouvelle séquence';
    document.getElementById('form').reset();
    document.getElementById('f-id').value = '';
    fillSelect(document.getElementById('f-niveau'), niveaux, { placeholder: 'Choisir un niveau' });
    document.getElementById('modal').classList.remove('hidden');
}

function editItem(s) {
    document.getElementById('modal-title').textContent = 'Modifier la séquence';
    document.getElementById('f-id').value = s.id;
    fillSelect(document.getElementById('f-niveau'), niveaux, { placeholder: 'Choisir un niveau', selected: s.niveau_id });
    document.getElementById('f-nom').value = s.nom;
    document.getElementById('f-ordre').value = s.ordre;
    document.getElementById('f-debut').value = s.date_debut;
    document.getElementById('f-fin').value = s.date_fin || '';
    document.getElementById('modal').classList.remove('hidden');
}

function closeForm() {
    document.getElementById('modal').classList.add('hidden');
}

document.getElementById('form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('f-id').value;
    const payload = {
        niveau_id: document.getElementById('f-niveau').value,
        nom: document.getElementById('f-nom').value,
        ordre: Number(document.getElementById('f-ordre').value),
        date_debut: document.getElementById('f-debut').value,
        date_fin: document.getElementById('f-fin').value || null,
    };
    try {
        if (id) {
            await apiFetch(`/api/sequences/${id}`, { method: 'PUT', body: payload });
            toast('Séquence mise à jour');
        } else {
            await apiFetch('/api/sequences', { method: 'POST', body: payload });
            toast('Séquence créée');
        }
        closeForm();
        load();
    } catch (err) {
        toastFromError(err);
    }
});

async function deleteItem(id) {
    if (!confirmAction('Supprimer cette séquence ? Les chapitres associés seront aussi supprimés.')) return;
    try {
        await apiFetch(`/api/sequences/${id}`, { method: 'DELETE' });
        toast('Séquence supprimée');
        load();
    } catch (err) {
        toastFromError(err);
    }
}

load();
</script>
@endpush