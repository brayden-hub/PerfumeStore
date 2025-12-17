<?php
require '../_base.php';
// We need to define the function sort_link() which is missing from the provided _base.php context.
// In a real application, this would be defined in _base.php or a helper file.
// For this script, we'll redefine it based on the table_headers logic.
function admin_sort_link($field, $current_sort, $current_dir, $search_term, $page) {
    $new_dir = $current_sort === $field && $current_dir === 'ASC' ? 'DESC' : 'ASC';
    $indicator = $current_sort === $field ? ($current_dir === 'ASC' ? '▲' : '▼') : '';
    // Preserve search term and page (if page is 1, it might be omitted, but keeping it makes the URL robust)
    $search_query = $search_term ? "&search=" . urlencode($search_term) : '';
    $link = "user.php?sort=$field&dir=$new_dir" . $search_query . "&page=$page";
    return "<a href='$link' style='color:#fff; text-decoration:none;'>$field $indicator</a>";
}

// Security Check: Only Admin can access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    redirect('/');
}

// --- (0) Filtering/Searching ---
$search = req('search'); // Used for Name/Email/Phone
$page   = req('page', 1);

$where = ["role = 'Member'"];
$params = [];
$href_params = ''; // URL parameters to keep during sorting/paging

if ($search) {
    // Search by name, email, or phone
    $where[] = "(name LIKE ? OR email LIKE ? OR phone_number LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $href_params .= 'search=' . urlencode($search) . '&';
}
// Note: We don't include 'page' in $href_params because SimplePager manages it.

$where_sql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';


// --- (1) Sorting ---
$fields = [
    'userID'         => 'ID',
    'name'           => 'Name',
    'email'          => 'Email',
    'phone_number'   => 'Phone',
];

$sort = req('sort');
key_exists($sort, $fields) || $sort = 'userID'; // Default to userID

$dir = req('dir');
in_array($dir, ['ASC', 'DESC']) || $dir = 'ASC'; // Use uppercase for SQL


// --- (2) Paging and Combined Query ---
$page_size = 10;
// Option A: If SimplePager.php is in PerfumeStore/lib/
require_once '../lib/SimplePager.php'; // Assuming SimplePager exists in this location

// user.php (Around Line 64 - FIXED)
$sql = "SELECT userID, name, email, phone_number, role, Profile_Photo, status 
        FROM user 
        $where_sql 
        ORDER BY $sort $dir";

// Using SimplePager to handle the LIMIT/OFFSET
$p = new SimplePager($sql, $params, $page_size, $page);
$arr = $p->result; 

// Parameters for Pager links
$pager_href = $href_params . 'sort=' . $sort . '&dir=' . $dir;

// --- HTML Output ---
$_title = 'User Management - N°9 Perfume';
include '../_head.php';
?>

<div class="admin-container">
    
    <div class="admin-toolbar">
        <h2>Member Management</h2>
        
        <form method="get" class="admin-search-form">
            <input type="text" name="search" class="admin-input-search" 
                   placeholder="Search Name/Email/Phone" 
                   value="<?= htmlspecialchars($search ?? '') ?>">
            
            <button type="submit" class="admin-btn-search">Search</button>
            
            <?php if ($search): ?>
                <a href="user.php" class="admin-link-clear">Clear</a>
            <?php endif; ?>
            
            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
            <input type="hidden" name="dir" value="<?= htmlspecialchars($dir) ?>">
        </form>
    </div>

    <p style="color: #666; font-size: 0.9rem;">
        <?= $p->count ?> of <?= $p->item_count ?> record(s) |
        Page <?= $p->page ?> of <?= $p->page_count ?>
    </p>

    <table class="product-table">
        <thead>
            <tr>
                <th><?= admin_sort_link('userID', $sort, $dir, $search, $p->page) ?></th>
                <th><?= admin_sort_link('name', $sort, $dir, $search, $p->page) ?></th>
                <th><?= admin_sort_link('email', $sort, $dir, $search, $p->page) ?></th>
                <th><?= admin_sort_link('phone_number', $sort, $dir, $search, $p->page) ?></th>
                <th>Image</th>
                <th>Actions</th>
            </tr>
        </thead>
        
        <tbody>
            <?php foreach ($arr as $u): ?>
            <?php $avatar = $u->Profile_Photo ?: 'default1.jpg'; ?>
            <tr>
                <td><?= htmlspecialchars($u->userID) ?></td>
                <td><?= htmlspecialchars($u->name) ?></td>
                <td><?= htmlspecialchars($u->email) ?></td>
                <td><?= htmlspecialchars($u->phone_number ?: '-') ?></td>
                
                <td>
                    <img src="../images/avatars/<?= htmlspecialchars($avatar) ?>" 
                         class="admin-user-avatar" 
                         alt="<?= htmlspecialchars($u->name) ?>">
                </td>
                
                <td class="admin-table-actions">
                    <span class="admin-status-text <?= $u->status === 'Activated' ? 'admin-status-active' : 'admin-status-inactive' ?>">
                        <?= $u->status ?>
                    </span>
                    <hr class="admin-divider">
                    
                    <?php if ($u->status === 'Activated'): ?>
                        <button data-post="user_deactivate.php?userID=<?= $u->userID ?>&status=Deactivated" 
                                data-confirm="Are you sure you want to DEACTIVATE member <?= htmlspecialchars($u->name) ?>?"
                                class="action-btn btn-delete">
                            Deactivate
                        </button>
                    <?php else: ?>
                        <button data-post="user_deactivate.php?userID=<?= $u->userID ?>&status=Activated" 
                                data-confirm="Are you sure you want to ACTIVATE member <?= htmlspecialchars($u->name) ?>?"
                                class="action-btn btn-edit"> 
                            Activate
                        </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach ?>
        </tbody>
    </table>
    
    </div>
    
</div>

<?php include '../_foot.php'; ?>