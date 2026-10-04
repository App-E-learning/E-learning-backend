@extends('admin.layout')

@section('title', 'Chapitres')
@section('page-title', 'Chapitres')
@section('page-subtitle', "C'est dans un chapitre que les exercices sont rangés — c'est l'unité que l'app mobile affiche à l'élève.")

@section('content')
    {{-- Barre de filtres + bouton --}}
    <div class="flex flex-wrap items-end justify-between gap-3 mb-4">
        <div class="flex flex-wrap gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Niveau</label>
                <select id="flt-niveau" class="border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white min-w-[160px]"></select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Séquence</label>
                <select id="flt-sequence" class="border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white min-w-[160px]"></select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Matière</label>
                <select id="flt-matiere" class="border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white min-w-[160px]"></select>
            </div>
            <div class="flex items-end">
                <button type="button" onclick="resetFilters()" class="text-xs text-slate-500 hover:text-slate-700 py-2.5">Réinitialiser</button>
            </div>
        </div>
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
                    <th class="text-left px-4 py-3">Ordre</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody id="rows"></tbody>
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
                    <select id="f-sequence" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm disabled:bg-slate-50 disabled:text-slate-400"></select>
                    <p class="text-[11px] text-slate-400 mt-1">Seules les séquences du niveau choisi sont proposées.</p>
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
const filters = { niveau: '', sequence: '', matiere: '' };

const $ = (id) => document.getElementById(id);

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
    sequences = (seqData.data || seqData).map(s => ({
        id: s.id,
        label: s.nom,
        niveau_id: s.niveau_id ?? s.niveau?.id ?? null,
        niveau_nom: s.niveau?.nom || '',
    }));
    renderFilters();
    render();
}

/* ---------- Filtres ---------- */

function renderFilters() {
    fillSelect($('flt-niveau'), niveaux, { placeholder: 'Tous les niveaux', selected: filters.niveau });
    fillSelect($('flt-matiere'), matieres, { placeholder: 'Toutes les matières', selected: filters.matiere });
    renderSequenceFilter();
}

function renderSequenceFilter() {
    const list = filters.niveau
        ? sequences.filter(s => String(s.niveau_id) === String(filters.niveau))
        : sequences.map(s => ({ ...s, label: `${s.label} (${s.niveau_nom})` }));
    // Si la séquence sélectionnée n'appartient plus au niveau filtré, on l'enlève
    if (filters.sequence && !list.some(s => String(s.id) === String(filters.sequence))) {
        filters.sequence = '';
    }
    fillSelect($('flt-sequence'), list, { placeholder: 'Toutes les séquences', selected: filters.sequence });
}

$('flt-niveau').addEventListener('change', (e) => {
    filters.niveau = e.target.value;
    filters.sequence = '';
    renderSequenceFilter();
    render();
});
$('flt-sequence').addEventListener('change', (e) => {
    filters.sequence = e.target.value;
    render();
});
$('flt-matiere').addEventListener('change', (e) => {
    filters.matiere = e.target.value;
    render();
});

function resetFilters() {
    filters.niveau = filters.sequence = filters.matiere = '';
    renderFilters();
    render();
}

/* ---------- Tableau groupé par niveau / séquence ---------- */

function render() {
    const filtered = items.filter(c =>
        (!filters.niveau   || String(c.niveau?.id)   === String(filters.niveau)) &&
        (!filters.sequence || String(c.sequence?.id) === String(filters.sequence)) &&
        (!filters.matiere  || String(c.matiere?.id)  === String(filters.matiere))
    );

    // Regroupement par séquence
    const groups = new Map();
    filtered.forEach(c => {
        const key = c.sequence?.id ?? 'none';
        if (!groups.has(key)) groups.set(key, { sequenceId: key, chapitres: [] });
        groups.get(key).chapitres.push(c);
    });

    // Ordre des groupes : ordre des séquences renvoyé par l'API (par niveau puis séquence)
    const seqIndex = (id) => {
        const i = sequences.findIndex(s => s.id === id);
        return i === -1 ? 9999 : i;
    };
    const sortedGroups = [...groups.values()].sort((a, b) => seqIndex(a.sequenceId) - seqIndex(b.sequenceId));

    $('empty').textContent = items.length === 0
        ? 'Aucun chapitre pour le moment.'
        : 'Aucun chapitre ne correspond à ces filtres.';
    $('empty').classList.toggle('hidden', filtered.length > 0);

    $('rows').innerHTML = sortedGroups.map(g => {
        const first = g.chapitres[0];
        const titreGroupe = `${escapeHtml(first.niveau?.nom || '—')} · ${escapeHtml(first.sequence?.nom || 'Sans séquence')}`;
        const lignes = g.chapitres
            .sort((a, b) => (a.ordre ?? 0) - (b.ordre ?? 0))
            .map(c => `
                <tr class="border-t border-slate-100">
                    <td class="px-4 py-3 font-medium">${escapeHtml(c.titre)}</td>
                    <td class="px-4 py-3 text-slate-500">${escapeHtml(c.matiere?.nom || '—')}</td>
                    <td class="px-4 py-3 text-slate-500">${c.ordre}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <button onclick='editItem(${JSON.stringify(c)})' class="text-primary text-xs font-semibold mr-3">Modifier</button>
                        <button onclick="deleteItem(${c.id})" class="text-red-600 text-xs font-semibold">Supprimer</button>
                    </td>
                </tr>
            `).join('');
        return `
            <tr class="bg-slate-50 border-t border-slate-200">
                <td colspan="4" class="px-4 py-2 text-xs font-semibold text-slate-600 uppercase tracking-wide">
                    ${titreGroupe}
                    <span class="ml-2 font-normal normal-case text-slate-400">${g.chapitres.length} chapitre${g.chapitres.length > 1 ? 's' : ''}</span>
                </td>
            </tr>
            ${lignes}
        `;
    }).join('');
}

/* ---------- Formulaire ---------- */

// Remplit la liste des séquences en fonction du niveau choisi
function fillSequenceSelect(niveauId, selectedId = '') {
    const select = $('f-sequence');
    if (!niveauId) {
        fillSelect(select, [], { placeholder: "Choisir d'abord un niveau", selected: '' });
        select.disabled = true;
        return;
    }
    const list = sequences.filter(s => String(s.niveau_id) === String(niveauId));
    fillSelect(select, list, { placeholder: 'Choisir une séquence', selected: selectedId });
    select.disabled = false;
}

function fillAllSelects(selected = {}) {
    fillSelect($('f-matiere'), matieres, { placeholder: 'Choisir une matière', selected: selected.matiere_id });
    fillSelect($('f-niveau'), niveaux, { placeholder: 'Choisir un niveau', selected: selected.niveau_id });
    fillSequenceSelect(selected.niveau_id, selected.sequence_id);
}

// Quand le niveau change dans le formulaire, on recharge les séquences correspondantes
$('f-niveau').addEventListener('change', (e) => {
    fillSequenceSelect(e.target.value);
});

function openForm() {
    $('modal-title').textContent = 'Nouveau chapitre';
    $('form').reset();
    $('f-id').value = '';
    // Pré-remplissage avec les filtres actifs
    fillAllSelects({
        matiere_id: filters.matiere || undefined,
        niveau_id: filters.niveau || undefined,
        sequence_id: filters.sequence || undefined,
    });
    $('modal').classList.remove('hidden');
}

function editItem(c) {
    $('modal-title').textContent = 'Modifier le chapitre';
    $('f-id').value = c.id;
    $('f-titre').value = c.titre;
    $('f-ordre').value = c.ordre;
    $('f-description').value = c.description || '';
    fillAllSelects({ matiere_id: c.matiere?.id, niveau_id: c.niveau?.id, sequence_id: c.sequence?.id });
    $('modal').classList.remove('hidden');
}

function closeForm() {
    $('modal').classList.add('hidden');
}

$('form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = $('f-id').value;
    const payload = {
        titre: $('f-titre').value,
        matiere_id: $('f-matiere').value,
        niveau_id: $('f-niveau').value,
        sequence_id: $('f-sequence').value,
        ordre: $('f-ordre').value ? Number($('f-ordre').value) : 0,
        description: $('f-description').value || null,
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