<?php require_once('header.php'); ?>

<?php
$search_text = isset($_REQUEST['search_text']) ? trim(strip_tags($_REQUEST['search_text'])) : '';
$category_id = isset($_REQUEST['category_id']) ? (int)$_REQUEST['category_id'] : 0;
$sort = isset($_REQUEST['sort']) ? trim(strip_tags($_REQUEST['sort'])) : 'newest';
$page = isset($_REQUEST['page']) ? (int)$_REQUEST['page'] : 1;
if ($page < 1) $page = 1;

// Fetch search banner
$statement = $pdo->prepare("SELECT banner_search FROM tbl_settings WHERE id=1");
$statement->execute();
$row_set = $statement->fetch(PDO::FETCH_ASSOC);
$banner_search = $row_set['banner_search'] ?? '';

// Category name if filtered
$category_name = 'All Departments';
if ($category_id > 0) {
    $st_c = $pdo->prepare("SELECT tcat_name FROM tbl_top_category WHERE tcat_id = ?");
    $st_c->execute([$category_id]);
    $cat_res = $st_c->fetch(PDO::FETCH_ASSOC);
    if ($cat_res && !empty($cat_res['tcat_name'])) {
        $category_name = $cat_res['tcat_name'];
    }
}

// Display title for banner
if ($search_text !== '' && $category_id > 0) {
    $banner_title = 'Results for "' . htmlspecialchars($search_text) . '" in ' . htmlspecialchars($category_name);
} elseif ($search_text !== '') {
    $banner_title = 'Results for "' . htmlspecialchars($search_text) . '"';
} elseif ($category_id > 0) {
    $banner_title = 'Department: ' . htmlspecialchars($category_name);
} else {
    $banner_title = 'All Products';
}

// Construct Query
$where = ["p.p_is_active = 1"];
$params = [];

if ($category_id > 0) {
    $st_ecat = $pdo->prepare("SELECT ec.ecat_id 
                               FROM tbl_end_category ec 
                               JOIN tbl_mid_category mc ON ec.mcat_id = mc.mcat_id 
                               WHERE mc.tcat_id = ?");
    $st_ecat->execute([$category_id]);
    $cat_ecats = $st_ecat->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($cat_ecats)) {
        $in_clause = implode(',', array_fill(0, count($cat_ecats), '?'));
        $where[] = "p.ecat_id IN ($in_clause)";
        $params = array_merge($params, $cat_ecats);
    } else {
        $where[] = "1 = 0";
    }
}

if ($search_text !== '') {
    $where[] = "(p.p_name LIKE ? OR p.p_short_description LIKE ? OR p.p_description LIKE ?)";
    $like = '%' . $search_text . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$where_sql = implode(' AND ', $where);

// Sorting
$order_by = "p.p_id DESC";
if ($sort === 'price_asc') {
    $order_by = "p.p_current_price ASC";
} elseif ($sort === 'price_desc') {
    $order_by = "p.p_current_price DESC";
} elseif ($sort === 'popular') {
    $order_by = "p.p_total_view DESC";
} elseif ($sort === 'newest') {
    $order_by = "p.p_id DESC";
}

// Count total
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_product p WHERE $where_sql");
$count_stmt->execute($params);
$total_products = (int)$count_stmt->fetchColumn();

// Pagination setup
$limit = 12;
$total_pages = ceil($total_products / $limit);
if ($total_pages < 1) $total_pages = 1;
if ($page > $total_pages) $page = $total_pages;
$start = ($page - 1) * $limit;

// Fetch products
$sql = "SELECT p.* FROM tbl_product p WHERE $where_sql ORDER BY $order_by LIMIT $start, $limit";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$query_base = 'search-result.php?' . http_build_query([
    'search_text' => $search_text,
    'category_id' => $category_id,
    'sort' => $sort
]);
?>

<div class="page-banner" style="background-image: url(assets/uploads/<?php echo htmlspecialchars($banner_search); ?>);">
    <div class="overlay"></div>
    <div class="inner">
        <h1><?php echo $banner_title; ?></h1>
    </div>
</div>

<div class="page" style="padding-top: 30px; padding-bottom: 50px;">
    <div class="container">
        <div class="row">
            <div class="col-md-12">

                <!-- Modern Toolbar -->
                <div class="modern-catalog-toolbar">
                    <div class="catalog-results-count">
                        <?php if ($total_products > 0): ?>
                            Showing <strong><?php echo $start + 1; ?>–<?php echo min($start + $limit, $total_products); ?></strong> of <strong><?php echo $total_products; ?></strong> results
                            <?php if ($search_text !== ''): ?>
                                for "<span style="color:#c45500; font-weight:700;"><?php echo htmlspecialchars($search_text); ?></span>"
                            <?php endif; ?>
                        <?php else: ?>
                            0 results found
                        <?php endif; ?>
                    </div>

                    <form method="get" action="search-result.php" class="catalog-sort-form">
                        <input type="hidden" name="search_text" value="<?php echo htmlspecialchars($search_text); ?>">
                        <input type="hidden" name="category_id" value="<?php echo $category_id; ?>">
                        <label for="sort-select"><i class="fa fa-sort-amount-desc"></i> Sort by:</label>
                        <select id="sort-select" name="sort" class="form-control" onchange="this.form.submit()">
                            <option value="newest" <?php if($sort=='newest') echo 'selected'; ?>>Newest Arrivals</option>
                            <option value="price_asc" <?php if($sort=='price_asc') echo 'selected'; ?>>Price: Low to High</option>
                            <option value="price_desc" <?php if($sort=='price_desc') echo 'selected'; ?>>Price: High to Low</option>
                            <option value="popular" <?php if($sort=='popular') echo 'selected'; ?>>Most Popular</option>
                        </select>
                    </form>
                </div>

                <!-- Products Grid -->
                <div class="row">
                    <?php if (empty($products)): ?>
                        <div class="col-md-12">
                            <div class="catalog-empty-state">
                                <div class="catalog-empty-icon"><i class="fa fa-search"></i></div>
                                <h3>No matching products found</h3>
                                <p>We couldn't find any items matching your search criteria. Try checking for typos or searching for a broader term.</p>
                                <a href="index.php" class="btn-browse-all">
                                    <i class="fa fa-shopping-bag"></i> Browse All Products
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php
                        foreach ($products as $row) {
                            render_modern_product_card($row, $pdo, 'col-md-3 col-sm-6');
                        }
                        ?>
                    <?php endif; ?>
                </div>

                <!-- Modern Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="text-center" style="margin-top: 30px;">
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li><a href="<?php echo $query_base; ?>&page=<?php echo $page - 1; ?>">&laquo; Prev</a></li>
                            <?php else: ?>
                                <li class="disabled"><span>&laquo; Prev</span></li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="<?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <a href="<?php echo $query_base; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li><a href="<?php echo $query_base; ?>&page=<?php echo $page + 1; ?>">Next &raquo;</a></li>
                            <?php else: ?>
                                <li class="disabled"><span>Next &raquo;</span></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>
