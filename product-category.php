<?php require_once('header.php'); ?>

<?php
$statement = $pdo->prepare("SELECT * FROM tbl_settings WHERE id=1");
$statement->execute();
$result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
foreach ($result as $row) {
    $banner_product_category = $row['banner_product_category'];
}
?>

<?php
if( !isset($_REQUEST['id']) || !isset($_REQUEST['type']) ) {
    header('location: index.php');
    exit;
} else {

    if( ($_REQUEST['type'] != 'top-category') && ($_REQUEST['type'] != 'mid-category') && ($_REQUEST['type'] != 'end-category') ) {
        header('location: index.php');
        exit;
    } else {

        $top = array();
        $top1 = array();
        $statement = $pdo->prepare("SELECT * FROM tbl_top_category");
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
        foreach ($result as $row) {
            $top[] = $row['tcat_id'];
            $top1[] = $row['tcat_name'];
        }

        $mid = array();
        $mid1 = array();
        $mid2 = array();
        $statement = $pdo->prepare("SELECT * FROM tbl_mid_category");
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
        foreach ($result as $row) {
            $mid[] = $row['mcat_id'];
            $mid1[] = $row['mcat_name'];
            $mid2[] = $row['tcat_id'];
        }

        $end = array();
        $end1 = array();
        $end2 = array();
        $statement = $pdo->prepare("SELECT * FROM tbl_end_category");
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
        foreach ($result as $row) {
            $end[] = $row['ecat_id'];
            $end1[] = $row['ecat_name'];
            $end2[] = $row['mcat_id'];
        }

        $title = '';
        $final_ecat_ids = array();

        if($_REQUEST['type'] == 'top-category') {
            if(!in_array($_REQUEST['id'],$top)) {
                header('location: index.php');
                exit;
            } else {
                for ($i=0; $i < count($top); $i++) { 
                    if($top[$i] == $_REQUEST['id']) {
                        $title = $top1[$i];
                        break;
                    }
                }
                $arr1 = array();
                $arr2 = array();
                for ($i=0; $i < count($mid); $i++) { 
                    if($mid2[$i] == $_REQUEST['id']) {
                        $arr1[] = $mid[$i];
                    }
                }
                for ($j=0; $j < count($arr1); $j++) {
                    for ($i=0; $i < count($end); $i++) { 
                        if($end2[$i] == $arr1[$j]) {
                            $arr2[] = $end[$i];
                        }
                    }   
                }
                $final_ecat_ids = $arr2;
            }   
        }

        if($_REQUEST['type'] == 'mid-category') {
            if(!in_array($_REQUEST['id'],$mid)) {
                header('location: index.php');
                exit;
            } else {
                for ($i=0; $i < count($mid); $i++) { 
                    if($mid[$i] == $_REQUEST['id']) {
                        $title = $mid1[$i];
                        break;
                    }
                }
                $arr2 = array();        
                for ($i=0; $i < count($end); $i++) { 
                    if($end2[$i] == $_REQUEST['id']) {
                        $arr2[] = $end[$i];
                    }
                }
                $final_ecat_ids = $arr2;
            }
        }

        if($_REQUEST['type'] == 'end-category') {
            if(!in_array($_REQUEST['id'],$end)) {
                header('location: index.php');
                exit;
            } else {
                for ($i=0; $i < count($end); $i++) { 
                    if($end[$i] == $_REQUEST['id']) {
                        $title = $end1[$i];
                        break;
                    }
                }
                $final_ecat_ids = array($_REQUEST['id']);
            }
        }
        
    }   
}

$sort = isset($_GET['sort']) ? trim(strip_tags($_GET['sort'])) : 'newest';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// Sorting definition
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

$total_products = 0;
$products = array();

if (!empty($final_ecat_ids)) {
    $placeholders = implode(',', array_fill(0, count($final_ecat_ids), '?'));
    
    // Count total products
    $c_stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_product p WHERE p.ecat_id IN ($placeholders) AND p.p_is_active = 1");
    $c_stmt->execute($final_ecat_ids);
    $total_products = (int)$c_stmt->fetchColumn();

    // Pagination
    $limit = 9;
    $total_pages = ceil($total_products / $limit);
    if ($total_pages < 1) $total_pages = 1;
    if ($page > $total_pages) $page = $total_pages;
    $start = ($page - 1) * $limit;

    // Fetch products
    $sql = "SELECT p.* FROM tbl_product p WHERE p.ecat_id IN ($placeholders) AND p.p_is_active = 1 ORDER BY $order_by LIMIT $start, $limit";
    $p_stmt = $pdo->prepare($sql);
    $p_stmt->execute($final_ecat_ids);
    $products = $p_stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $total_pages = 1;
}

$query_base = 'product-category.php?' . http_build_query([
    'id' => $_REQUEST['id'],
    'type' => $_REQUEST['type'],
    'sort' => $sort
]);
?>

<div class="page-banner" style="background-image: url(assets/uploads/<?php echo htmlspecialchars($banner_product_category); ?>)">
    <div class="inner">
        <h1><?php echo LANG_VALUE_50; ?> <?php echo htmlspecialchars($title); ?></h1>
    </div>
</div>

<div class="page" style="padding-top: 30px; padding-bottom: 60px;">
    <div class="container">
        <div class="row">
            <div class="col-md-3">
                <?php require_once('sidebar-category.php'); ?>
            </div>
            <div class="col-md-9">

                <!-- Modern Toolbar -->
                <div class="modern-catalog-toolbar">
                    <div class="catalog-results-count">
                        <?php if ($total_products > 0): ?>
                            Showing <strong><?php echo $start + 1; ?>–<?php echo min($start + $limit, $total_products); ?></strong> of <strong><?php echo $total_products; ?></strong> products in <strong><?php echo htmlspecialchars($title); ?></strong>
                        <?php else: ?>
                            0 products found
                        <?php endif; ?>
                    </div>

                    <form method="get" action="product-category.php" class="catalog-sort-form">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($_REQUEST['id']); ?>">
                        <input type="hidden" name="type" value="<?php echo htmlspecialchars($_REQUEST['type']); ?>">
                        <label for="sort-select"><i class="fa fa-sort-amount-desc"></i> Sort:</label>
                        <select id="sort-select" name="sort" class="form-control" onchange="this.form.submit()">
                            <option value="newest" <?php if($sort=='newest') echo 'selected'; ?>>Newest Arrivals</option>
                            <option value="price_asc" <?php if($sort=='price_asc') echo 'selected'; ?>>Price: Low to High</option>
                            <option value="price_desc" <?php if($sort=='price_desc') echo 'selected'; ?>>Price: High to Low</option>
                            <option value="popular" <?php if($sort=='popular') echo 'selected'; ?>>Most Popular</option>
                        </select>
                    </form>
                </div>

                <div class="product product-cat">
                    <div class="row">
                        <?php if (empty($products)): ?>
                            <div class="col-md-12">
                                <div class="catalog-empty-state">
                                    <div class="catalog-empty-icon"><i class="fa fa-folder-open-o"></i></div>
                                    <h3>No products currently in "<?php echo htmlspecialchars($title); ?>"</h3>
                                    <p>We're restocking this department soon. Please explore other categories or check back later!</p>
                                    <a href="index.php" class="btn-browse-all">
                                        <i class="fa fa-shopping-bag"></i> Browse All Departments
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php
                            foreach ($products as $row) {
                                render_modern_product_card($row, $pdo, 'col-md-4 col-sm-6');
                            }
                            ?>
                        <?php endif; ?>
                    </div>

                    <!-- Pagination -->
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
</div>

<?php require_once('footer.php'); ?>
