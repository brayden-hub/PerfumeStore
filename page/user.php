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

$sql = "SELECT userID, name, email, phone_number, role, Profile_Photo 
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

<div class="container" style="margin-top: 30px;">
    
    <div class="admin-header">
        <h2>Member Management</h2>
        
        <form method="get" style="display:inline-flex; gap:10px; margin-left: auto;">
            <input type="text" name="search" placeholder="Search Name/Email/Phone" 
                   value="<?= htmlspecialchars($search ?? '') ?>"
                   style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            <button type="submit" style="padding: 10px 15px; background: #000; color: #D4AF37; border: none; border-radius: 4px; cursor: pointer;">
                Search
            </button>
            <?php if ($search): ?>
            <a href="user.php" style="padding: 8px 15px; color: #666; text-decoration: none;">Clear</a>
            <?php endif; ?>
            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
            <input type="hidden" name="dir" value="<?= htmlspecialchars($dir) ?>">
        </form>
    </div>

    <p>
        <?= $p->count ?> of <?= $p->item_count ?> record(s) |
        Page <?= $p->page ?> of <?= $p->page_count ?>
    </p>

    <table class="product-table">
        <thead>
            <tr>
                <?php
                    // Arguments: fields, current_sort, current_dir, additional_params (without 'page')
                    $header_params = rtrim($href_params . 'dir=' . $dir, '&');
                ?>
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
            <?php 
                $avatar = $u->Profile_Photo ?: 'default1.jpg';
            ?>
            <tr>
                <td><?= htmlspecialchars($u->userID) ?></td>
                <td><?= htmlspecialchars($u->name) ?></td>
                <td><?= htmlspecialchars($u->email) ?></td>
                <td><?= htmlspecialchars($u->phone_number ?: '-') ?></td>
                <td>
                    <img src="../images/avatars/<?= htmlspecialchars($avatar) ?>" 
                         class="thumb-img" 
                         alt="<?= htmlspecialchars($u->name) ?>"
                         style="border-radius:50%; width:60px; height:60px;">
                </td>
                <td>
                    <button data-get="user_detail.php?userID=<?= $u->userID ?>" class="action-btn btn-edit">View</button>
                    <button data-post="user_delete.php?userID=<?= $u->userID ?>" 
                            data-confirm="Are you sure you want to delete member <?= htmlspecialchars($u->name) ?>?"
                            class="action-btn btn-delete">
                        Delete
                    </button>
                </td>
            </tr>
            <?php endforeach ?>
            
            <?php if(count($arr) == 0): ?>
                <tr><td colspan="6" style="text-align:center;">No members found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <br>
    
    <?php if (isset($p) && method_exists($p, 'html')): ?>
        <?= $p->html($pager_href) ?>
    <?php else: ?>
        <div style="text-align:center; margin-top:20px;">
            <?php for($i = 1; $i <= $p->page_count; $i++): 
                $link = "user.php?page=$i&$pager_href";
                $style = $i == $p->page ? 'background-color:#000; color:#fff; border-color:#000;' : 'background-color:#f0f0f0; color:#000;';
            ?>
                <a href="<?= $link ?>" 
                   style="padding: 8px 12px; margin: 0 5px; border: 1px solid #ccc; border-radius: 4px; text-decoration: none; <?= $style ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
    
</div>

<?php include '../_foot.php'; ?>