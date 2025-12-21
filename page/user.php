<?php
require '../_base.php';

// 分页与排序链接生成函数
function admin_sort_link($field, $current_sort, $current_dir, $search_term, $filter_term, $page) {
    $new_dir = $current_sort === $field && $current_dir === 'ASC' ? 'DESC' : 'ASC';
    $indicator = $current_sort === $field ? ($current_dir === 'ASC' ? '▲' : '▼') : '';
    
    // 核心修复：确保分页和排序链接中包含 filter 参数
    $query_parts = [
        "sort=$field",
        "dir=$new_dir",
        "page=$page"
    ];
    if ($search_term) $query_parts[] = "search=" . urlencode($search_term);
    if ($filter_term) $query_parts[] = "filter=" . urlencode($filter_term);
    
    $link = "user.php?" . implode('&', $query_parts);
    return "<a href='$link' style='color:#fff; text-decoration:none;'>$field $indicator</a>";
}

// 权限检查
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    redirect('/');
}

// 1. 获取请求参数
$search = req('search'); 
$filter = req('filter'); // 新增：获取过滤状态
$page   = req('page', 1);

// 2. 构建基础查询条件
$where = ["role = 'Member'"];
$params = [];
$href_params = '';

// 处理搜索逻辑
if ($search) {
    $where[] = "(name LIKE ? OR email LIKE ? OR phone_number LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $href_params .= 'search=' . urlencode($search) . '&';
}

// === 新增：处理状态过滤逻辑 ===
if ($filter && in_array($filter, ['Activated', 'Deactivated', 'Pending'])) {
    $where[] = "status = ?";
    $params[] = $filter;
    $href_params .= 'filter=' . urlencode($filter) . '&';
}

$where_sql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

// 3. 排序逻辑
$fields = [
    'userID'         => 'ID',
    'name'           => 'Name',
    'email'          => 'Email',
    'phone_number'   => 'Phone',
];

$sort = req('sort');
key_exists($sort, $fields) || $sort = 'userID'; 

$dir = req('dir');
in_array($dir, ['ASC', 'DESC']) || $dir = 'ASC'; 

// 4. 分页处理
$page_size = 10;
require_once '../lib/SimplePager.php'; 

$sql = "SELECT userID, name, email, phone_number, role, Profile_Photo, status 
        FROM user 
        $where_sql 
        ORDER BY $sort $dir";

$p = new SimplePager($sql, $params, $page_size, $page);
$arr = $p->result; 

// 构建用于分页 HTML 的参数字符串
$pager_href = $href_params . 'sort=' . $sort . '&dir=' . $dir;

$_title = 'User Management - N°9 Perfume';
include '../_head.php';
?>

<div class="admin-container">
    
    <div class="admin-toolbar">
        <h2>Member Management</h2>
        
        <form method="get" class="admin-search-form">
            <select name="filter" class="admin-input-search" onchange="this.form.submit()" style="min-width: 150px;">
                <option value="">- All Status -</option>
                <option value="Activated" <?= $filter == 'Activated' ? 'selected' : '' ?>>Activated Only</option>
                <option value="Deactivated" <?= $filter == 'Deactivated' ? 'selected' : '' ?>>Deactivated Only</option>
                <option value="Pending" <?= $filter == 'Pending' ? 'selected' : '' ?>>Pending Only</option>
            </select>

            <input type="text" name="search" class="admin-input-search" 
                   placeholder="Search Name/Email/Phone" 
                   value="<?= htmlspecialchars($search ?? '') ?>">
            
            <button type="submit" class="admin-btn-search">Search</button>
            
            <?php if ($search || $filter): ?>
                <a href="user.php" class="admin-link-clear">Clear All</a>
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
                <th><?= admin_sort_link('userID', $sort, $dir, $search, $filter, $p->page) ?></th>
                <th><?= admin_sort_link('name', $sort, $dir, $search, $filter, $p->page) ?></th>
                <th><?= admin_sort_link('email', $sort, $dir, $search, $filter, $p->page) ?></th>
                <th><?= admin_sort_link('phone_number', $sort, $dir, $search, $filter, $p->page) ?></th>
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
                        <?= htmlspecialchars($u->status) ?>
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

    <div class="admin-pager">
        <?php $p->html($pager_href); // 调用 SimplePager 生成分页链接 ?>
    </div>
    
</div>

<?php include '../_foot.php'; ?>