@extends('admin.layout')

@section('title', 'Niveaux')
@section('page-title', 'Niveaux')
@section('page-subtitle', 'Les niveaux scolaires (ex. Terminale C).')

@section('content')
    <div class="flex justify-end mb-4">
        <button onclick="openForm()" class="bg-primary hover:bg-primary/90 text-white text-sm font-semibold px-4 py-2 rounded-lg">
            + Nouveau niveau
        </button>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-4 py-3">Nom</th>
                    <th class="text-left px-4 py-3">Code</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody id="rows" class="divide-y divide-slate-100"></tbody>
        </table>
        <p id="empty" class="hidden text-center text-slate-400 text-sm py-8">Aucun niveau pour le moment.</p>
    </div>

    <div id="modal" class="hidden fixed inset-0 bg-black/30 flex items-center justify-center z-40">
        <div class="bg-white rounded-2xl shadow-lg w-full max-w-md p-6">
            <h2 id="modal-title" class="text-lg font-bold mb-4">Nouveau niveau</h2>
            <form id="form" class="space-y-4">
                <input type="hidden" id="f-id">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Nom</label>
                    <input id="f-nom" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="Terminale C">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Code</label>
                    <input id="f-code" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="TC">
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

async function load() {
    const data = await apiFetch('/api/niveaux');
    items = data.data || data;
    render();
}

function render() {
    const rows = document.getElementById('rows');
    document.getElementById('empty').classList.toggle('hidden', items.length > 0);
    rows.innerHTML = items.map(n => `
        <tr>
            <td class="px-4 py-3 font-medium">${escapeHtml(n.nom)}</td>
            <td class="px-4 py-3 text-slate-500">${escapeHtml(n.code)}</td>
            <td class="px-4 py-3 text-right whitespace-nowrap">
                <button onclick='editItem(${JSON.stringify(n)})' class="text-primary text-xs font-semibold mr-3">Modifier</button>
                <button onclick="deleteItem(${n.id})" class="text-red-600 text-xs font-semibold">Supprimer</button>
            </td>
        </tr>
    `).join('');
}

function openForm() {
    document.getElementById('modal-title').textContent = 'Nouveau niveau';
    document.getElementById('form').reset();
    document.getElementById('f-id').value = '';
    document.getElementById('modal').classList.remove('hidden');
}

function editItem(n) {
    document.getElementById('modal-title').textContent = 'Modifier le niveau';
    document.getElementById('f-id').value = n.id;
    document.getElementById('f-nom').value = n.nom;
    document.getElementById('f-code').value = n.code;
    document.getElementById('modal').classList.remove('hidden');
}

function closeForm() {
    document.getElementById('modal').classList.add('hidden');
}

document.getElementById('form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('f-id').value;
    const payload = {
        nom: document.getElementById('f-nom').value,
        code: document.getElementById('f-code').value,
    };
    try {
        if (id) {
            await apiFetch(`/api/niveaux/${id}`, { method: 'PUT', body: payload });
            toast('Niveau mis à jour');
        } else {
            await apiFetch('/api/niveaux', { method: 'POST', body: payload });
            toast('Niveau créé');
        }
        closeForm();
        load();
    } catch (err) {
        toastFromError(err);
    }
});

async function deleteItem(id) {
    if (!confirmAction('Supprimer ce niveau ? Cette action est irréversible.')) return;
    try {
        await apiFetch(`/api/niveaux/${id}`, { method: 'DELETE' });
        toast('Niveau supprimé');
        load();
    } catch (err) {
        toastFromError(err);
    }
}

load();
</script>
@endpush