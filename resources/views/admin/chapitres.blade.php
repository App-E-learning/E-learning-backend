@extends('admin.layout')

@section('title', 'Chapitres')
@section('page-title', 'Chapitres')
@section('page-subtitle', "C'est dans un chapitre que les exercices sont rangés — c'est l'unité que l'app mobile affiche à l'élève.")

@section('content')
    <div class="flex justify-end mb-4">
        <button onclick="openForm()" class="bg-primary hover:bg-primary/90 text-white text-sm font-semibold px-4 py-2 rounded-lg">
            + Nouveau chapitre
        </button>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-4 py-3">Titre</th>
                    <th class="text-left px-4 py-3">Matière</th>
                    <th class="text-left px-4 py-3">Niveau</th>
                    <th class="text-left px-4 py-3">Séquence</th>
                    <th class="text-left px-4 py-3">Ordre</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody id="rows" class="divide-y divide-slate-100"></tbody>
        </table>
        <p id="empty" class="hidden text-center text-slate-400 text-sm py-8">Aucun chapitre pour le moment.</p>
    </div>

    <div id="modal" class="hidden fixed inset-0 bg-black/30 flex items-center justify-center z-40">
        <div class="bg-white rounded-2xl shadow-lg w-full max-w-md p-6 max-h-[90vh] overflow-y-auto">
            <h2 id="modal-title" class="text-lg font-bold mb-4">Nouveau chapitre</h2>
            <form id="form" class="space-y-4">
                <input type="hidden" id="f-id">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Titre</label>
                    <input id="f-titre" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Matière</label>
                    <select id="f-matiere" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"></select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Niveau</label>
                    <select id="f-niveau" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"></select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Séquence</label>
                    <select id="f-sequence" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"></select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Ordre dans la séquence</label>
                    <input id="f-ordre" type="number" min="0" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="0">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Description (optionnel)</label>
                    <textarea id="f-description" rows="3" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"></textarea>
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
let matieres = [], niveaux = [], sequences = [];

async function load() {
    const [chapData, matData, nivData, seqData] = await Promise.all([
        apiFetch('/api/chapitres'),
        apiFetch('/api/matieres'),
        apiFetch('/api/niveaux'),
        apiFetch('/api/sequences'),
    ]);
    items = chapData.data || chapData;
    matieres = (matData.data || matData).map(m => ({ id: m.id, label: m.nom }));
    niveaux = (nivData.data || nivData).map(n => ({ id: n.id, label: n.nom }));
    sequences = (seqData.data || seqData).map(s => ({ id: s.id, label: `${s.nom} (${s.niveau?.nom || ''})` }));
    render();
}

function render() {
    const rows = document.getElementById('rows');
    document.getElementById('empty').classList.toggle('hidden', items.length > 0);
    rows.innerHTML = items.map(c => `
        <tr>
            <td class="px-4 py-3 font-medium">${escapeHtml(c.titre)}</td>
            <td class="px-4 py-3 text-slate-500">${escapeHtml(c.matiere?.nom || '—')}</td>
            <td class="px-4 py-3 text-slate-500">${escapeHtml(c.niveau?.nom || '—')}</td>
            <td class="px-4 py-3 text-slate-500">${escapeHtml(c.sequence?.nom || '—')}</td>
            <td class="px-4 py-3 text-slate-500">${c.ordre}</td>
            <td class="px-4 py-3 text-right whitespace-nowrap">
                <button onclick='editItem(${JSON.stringify(c)})' class="text-primary text-xs font-semibold mr-3">Modifier</button>
                <button onclick="deleteItem(${c.id})" class="text-red-600 text-xs font-semibold">Supprimer</button>
            </td>
        </tr>
    `).join('');
}

function fillAllSelects(selected = {}) {
    fillSelect(document.getElementById('f-matiere'), matieres, { placeholder: 'Choisir une matière', selected: selected.matiere_id });
    fillSelect(document.getElementById('f-niveau'), niveaux, { placeholder: 'Choisir un niveau', selected: selected.niveau_id });
    fillSelect(document.getElementById('f-sequence'), sequences, { placeholder: 'Choisir une séquence', selected: selected.sequence_id });
}

function openForm() {
    document.getElementById('modal-title').textContent = 'Nouveau chapitre';
    document.getElementById('form').reset();
    document.getElementById('f-id').value = '';
    fillAllSelects();
    document.getElementById('modal').classList.remove('hidden');
}

function editItem(c) {
    document.getElementById('modal-title').textContent = 'Modifier le chapitre';
    document.getElementById('f-id').value = c.id;
    document.getElementById('f-titre').value = c.titre;
    document.getElementById('f-ordre').value = c.ordre;
    document.getElementById('f-description').value = c.description || '';
    fillAllSelects({ matiere_id: c.matiere?.id, niveau_id: c.niveau?.id, sequence_id: c.sequence?.id });
    document.getElementById('modal').classList.remove('hidden');
}

function closeForm() {
    document.getElementById('modal').classList.add('hidden');
}

document.getElementById('form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('f-id').value;
    const payload = {
        titre: document.getElementById('f-titre').value,
        matiere_id: document.getElementById('f-matiere').value,
        niveau_id: document.getElementById('f-niveau').value,
        sequence_id: document.getElementById('f-sequence').value,
        ordre: document.getElementById('f-ordre').value ? Number(document.getElementById('f-ordre').value) : 0,
        description: document.getElementById('f-description').value || null,
    };
    try {
        if (id) {
            await apiFetch(`/api/chapitres/${id}`, { method: 'PUT', body: payload });
            toast('Chapitre mis à jour');
        } else {
            await apiFetch('/api/chapitres', { method: 'POST', body: payload });
            toast('Chapitre créé');
        }
        closeForm();
        load();
    } catch (err) {
        toastFromError(err);
    }
});

async function deleteItem(id) {
    if (!confirmAction('Supprimer ce chapitre ? Tous ses exercices seront aussi supprimés.')) return;
    try {
        await apiFetch(`/api/chapitres/${id}`, { method: 'DELETE' });
        toast('Chapitre supprimé');
        load();
    } catch (err) {
        toastFromError(err);
    }
}

load();
</script>
@endpush