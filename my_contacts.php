<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

function safe($val) {
    return htmlspecialchars($val ?? '', ENT_QUOTES, 'UTF-8');
}

// Current mode: favorites or all
$is_fav_mode = isset($_GET['show']) && $_GET['show'] === 'fav';

$search_term = $_GET['search'] ?? '';
$start_date  = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$end_date    = $_GET['end_date'] ?? date('Y-m-d');
$type        = $_GET['type'] ?? 'added';
$sort_by     = $_GET['sort_by'] ?? 'name_asc';

// In your getContacts function, modify the query to include favorite status
function getContacts($pdo, $user_id, $search, $start, $end, $type, $sort, $is_fav = false) {
    $baseQuery = $is_fav 
        ? "SELECT c.* FROM contacts c JOIN fav_contacts f ON c.contact_id = f.contact_id WHERE c.user_id = ?"
        : "SELECT c.*, CASE WHEN f.contact_id IS NOT NULL THEN 1 ELSE 0 END as is_favorite FROM contacts c LEFT JOIN fav_contacts f ON c.contact_id = f.contact_id WHERE c.user_id = ?";
    
    $column = $type === 'edited' ? 'c.updated_at' : 'c.created_at';
    $order  = match($sort) {
        'name_asc'  => 'c.first_name ASC',
        'name_desc' => 'c.first_name DESC',
        'date_asc'  => "$column ASC",
        'date_desc' => "$column DESC",
        default     => 'c.first_name ASC'
    };

    $stmt = $pdo->prepare("
        $baseQuery
          AND (c.first_name LIKE ? OR c.last_name LIKE ? OR c.phone LIKE ?)
          AND $column BETWEEN ? AND ?
        ORDER BY $order
    ");
    $stmt->execute([
        $user_id,
        "%$search%", "%$search%", "%$search%",
        "$start 00:00:00", "$end 23:59:59"
    ]);
    return $stmt->fetchAll();
}

$contacts = getContacts($pdo, $user_id, $search_term, $start_date, $end_date, $type, $sort_by, $is_fav_mode);

include 'includes/header.php';
?>

<div class="max-w-6xl mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold text-green-700 mb-6">My Contacts</h1>

    <div class="flex gap-2 mb-4">
        <form method="get" class="flex flex-1 gap-2">
            <input type="text" name="search" value="<?= safe($search_term) ?>" placeholder="Search by name or phone..."
                   class="w-full border rounded p-2">
            <input type="hidden" name="show" value="<?= $is_fav_mode ? 'fav' : '' ?>">
            <button type="submit" class="bg-teal-600 text-white px-4 rounded">Search</button>
        </form>

        <a href="my_contacts.php<?= $is_fav_mode ? '' : '?show=fav' ?>"
           class="<?= $is_fav_mode ? 'bg-green-500' : 'bg-yellow-400' ?> text-black px-3 py-1 rounded hover:bg-yellow-500 transition">
            <?= $is_fav_mode ? 'All Contacts' : 'Favorites' ?>
        </a>

        <div class="relative">
            <button id="filter-btn" class="bg-gray-100 px-3 py-1 rounded hover:bg-gray-200">Filter</button>
            <div id="filter-menu" class="hidden absolute bg-white border rounded shadow-lg p-4 mt-1 right-0">
                <form method="get">
                    <input type="hidden" name="show" value="<?= $is_fav_mode ? 'fav' : '' ?>">
                    <input type="hidden" name="sort_by" value="<?= safe($sort_by) ?>">
                    <label>Start Date:</label>
                    <input type="date" name="start_date" value="<?= safe($start_date) ?>" class="border rounded mb-2 w-full">
                    <label>End Date:</label>
                    <input type="date" name="end_date" value="<?= safe($end_date) ?>" class="border rounded mb-2 w-full">
                    <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded">Apply</button>
                </form>
            </div>
        </div>

        <div class="relative">
            <button id="sort-btn" class="bg-gray-100 px-3 py-1 rounded hover:bg-gray-200">Sort</button>
            <div id="sort-menu" class="hidden absolute bg-white border rounded shadow-lg p-2 mt-1 right-0">
                <a href="?sort_by=name_asc<?= $is_fav_mode ? '&show=fav' : '' ?>" class="block px-2 py-1 hover:bg-gray-100">Name A-Z</a>
                <a href="?sort_by=name_desc<?= $is_fav_mode ? '&show=fav' : '' ?>" class="block px-2 py-1 hover:bg-gray-100">Name Z-A</a>
                <a href="?sort_by=date_asc<?= $is_fav_mode ? '&show=fav' : '' ?>" class="block px-2 py-1 hover:bg-gray-100">Date Ascending</a>
                <a href="?sort_by=date_desc<?= $is_fav_mode ? '&show=fav' : '' ?>" class="block px-2 py-1 hover:bg-gray-100">Date Descending</a>
            </div>
        </div>
    </div>
<form method="post" action="bulk_delete.php" id="bulkDeleteForm">
    <button type="button" id="deleteSelectedBtn" class="bg-red-600 text-white px-4 py-1 mb-4 rounded hidden">
        Delete Selected
    </button>

    <div class="bg-white shadow rounded">
        <?php if ($contacts): ?>
            <div class="grid grid-cols-[30px,50px,3fr,2fr,2fr,100px] gap-4 p-4 bg-gray-100 font-semibold">
                <div><input type="checkbox" id="selectAll"></div>
                <div></div>
                <div>Name</div>
                <div>Email</div>
                <div>Phone</div>
                <div>Actions</div>
            </div>

            <?php foreach ($contacts as $contact): ?>
            <div class="contact-row grid grid-cols-[30px,50px,3fr,2fr,2fr,100px] gap-4 p-4 border-t hover:bg-gray-50 cursor-pointer"
                 data-id="<?= safe($contact['contact_id']) ?>" 
                 data-name="<?= safe($contact['first_name'] . ' ' . $contact['last_name']) ?>"
                 data-phone="<?= safe($contact['phone']) ?>">

                <!-- Tick -->
                <div class="flex justify-center items-center">
                    <input type="checkbox" name="selected_contacts[]" value="<?= safe($contact['contact_id']) ?>" class="rowCheckbox">
                </div>

                <!-- Avatar -->
                <div class="flex justify-center items-center">
                    <?php if (!empty($contact['avatar_url'])): ?>
                        <img src="<?= safe($contact['avatar_url']) ?>" alt="Avatar" class="w-8 h-8 rounded-full">
                    <?php else: ?>
                        <div class="w-8 h-8 bg-gray-300 text-green-700 rounded-full flex items-center justify-center text-sm">
                            <?= safe(strtoupper(substr($contact['first_name'], 0, 1) . substr($contact['last_name'], 0, 1))) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Name -->
                <div><?= safe($contact['first_name'] . ' ' . $contact['last_name']) ?></div>

                <!-- Email -->
                <div><?= safe($contact['email']) ?></div>

                <!-- Phone -->
                <div><?= safe($contact['phone']) ?></div>

                <!-- Actions -->
                <div class="flex gap-2 actions">
                    <button type="button" class="fav-btn" data-id="<?= safe($contact['contact_id']) ?>" data-fav="<?= $is_fav_mode ? '1' : ($contact['is_favorite'] ?? '0') ?>">
                    <?= ($is_fav_mode || ($contact['is_favorite'] ?? false)) ? '⭐' : '☆' ?>
                        </button>
                    <a href="edit_contact.php?id=<?= safe($contact['contact_id']) ?>" class="text-blue-600">✏️</a>
                    <button type="button" class="delete-icon text-red-600" data-id="<?= safe($contact['contact_id']) ?>">🗑️</button>
                </div>
            </div>
            <?php endforeach; ?>

        <?php else: ?>
            <div class="p-4 text-gray-500 text-center">No contacts found.</div>
        <?php endif; ?>
    </div>
</form>

<!-- Modal -->
<div id="confirmBulkModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex justify-center items-center z-50">
    <div class="bg-white p-6 rounded shadow-lg w-96">
        <h2 class="text-lg font-bold mb-4">Confirm Deletion</h2>
        <p>The following contacts will be deleted:</p>
        <ul id="contactsList" class="my-2 text-sm text-gray-700"></ul>
        <div class="flex justify-end gap-2 mt-4">
            <button id="cancelBulkDelete" class="bg-gray-300 px-4 py-1 rounded">Cancel</button>
            <button id="confirmBulkDelete" class="bg-red-600 text-white px-4 py-1 rounded">Delete</button>
        </div>
    </div>
</div>

<div id="toast" class="fixed bottom-4 right-4 bg-green-600 text-white px-4 py-2 rounded hidden"></div>

<?php include 'includes/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.querySelector('input[name="search"]');
    const contactRows = document.querySelectorAll('.contact-row');

    // Real-time search with AJAX
    searchInput.addEventListener('input', async (e) => {
        const searchTerm = e.target.value.trim();
        const isFavMode = document.querySelector('a[href*="show=fav"]').classList.contains('bg-green-500');

        if (searchTerm.length < 4) { // Show all contacts if less than 4 characters
            contactRows.forEach(row => row.style.display = '');
            return;
        }

        try {
            const response = await fetch(`search_contacts.php?q=${encodeURIComponent(searchTerm)}&show=${isFavMode ? 'fav' : ''}`);
            const contacts = await response.json();

            // Hide all rows first
            contactRows.forEach(row => row.style.display = 'none');

            // Show only matching contacts
            contacts.forEach(contact => {
                const row = document.querySelector(`.contact-row[data-id="${contact.contact_id}"]`);
                if (row) row.style.display = '';
            });
        } catch (error) {
            console.error('Error fetching contacts:', error);
            showToast('Error loading contacts');
        }
    });

    // Filter button toggle
    document.getElementById('filter-btn').onclick = () => {
        document.getElementById('filter-menu').classList.toggle('hidden');
    };

    // Sort button toggle
    document.getElementById('sort-btn').onclick = () => {
        document.getElementById('sort-menu').classList.toggle('hidden');
    };

    // Favorite toggle
    document.querySelectorAll('.fav-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.stopPropagation();
            const id = btn.dataset.id;
            const isCurrentlyFav = btn.dataset.fav === '1';
            
            try {
                const response = await fetch('toggle_favourite.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `contact_id=${id}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    if (isCurrentlyFav) {
                        btn.textContent = '☆';
                        btn.dataset.fav = '0';
                        showToast('Removed from favorites');
                        
                        if (document.querySelector('a[href*="show=fav"]').classList.contains('bg-green-500')) {
                            btn.closest('.contact-row').remove();
                        }
                    } else {
                        btn.textContent = '⭐';
                        btn.dataset.fav = '1';
                        showToast('Added to favorites');
                    }
                } else {
                    showToast('Error: ' + (data.message || 'Operation failed'));
                }
            } catch (error) {
                showToast('Network error - please try again');
                console.error('Error:', error);
            }
        });
    });

    // Checkbox and bulk delete handling
    const deleteBtn = document.getElementById('deleteSelectedBtn');
    const checkboxes = document.querySelectorAll('.rowCheckbox');
    const selectAll = document.getElementById('selectAll');
    const confirmModal = document.getElementById('confirmBulkModal');
    const confirmBtn = document.getElementById('confirmBulkDelete');
    const cancelBtn = document.getElementById('cancelBulkDelete');
    const contactsList = document.getElementById('contactsList');
    const form = document.getElementById('bulkDeleteForm');

    function updateDeleteButtonVisibility() {
        const anyChecked = Array.from(checkboxes).some(c => c.checked);
        deleteBtn.classList.toggle('hidden', !anyChecked);
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('click', (e) => e.stopPropagation());
        cb.addEventListener('change', () => updateDeleteButtonVisibility());
    });

    selectAll.addEventListener('click', (e) => e.stopPropagation());
    selectAll.addEventListener('change', () => {
        checkboxes.forEach(cb => cb.checked = selectAll.checked);
        updateDeleteButtonVisibility();
    });

    deleteBtn.addEventListener('click', () => {
        contactsList.innerHTML = '';
        checkboxes.forEach(cb => {
            if (cb.checked) {
                const row = cb.closest('.contact-row');
                const name = row.dataset.name;
                const phone = row.dataset.phone;
                contactsList.innerHTML += `<li>📇 ${name} — ${phone}</li>`;
            }
        });
        confirmModal.classList.remove('hidden');
    });

    confirmBtn.addEventListener('click', () => form.submit());
    cancelBtn.addEventListener('click', () => confirmModal.classList.add('hidden'));

    // Single delete handling
    document.querySelectorAll('.delete-icon').forEach(btn => {
        btn.addEventListener('click', e => {
            e.stopPropagation();
            const row = btn.closest('.contact-row');
            const name = row.dataset.name;
            const phone = row.dataset.phone;
            
            contactsList.innerHTML = `<li>📇 ${name} — ${phone}</li>`;
            confirmModal.classList.remove('hidden');
            
            confirmBtn.onclick = function() {
                window.location = `delete_contact.php?id=${btn.dataset.id}`;
            };
        });
    });

    // Row click to view details
    document.querySelectorAll('.contact-row').forEach(row => {
        row.addEventListener('click', e => {
            if (e.target.closest('.actions') || e.target.closest('input[type="checkbox"]')) return;
            const id = row.dataset.id;
            window.location = 'detailed_contacts.php?id=' + id;
        });
    });
});

function showToast(msg) {
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 2000);
}
</script>