<?php require_once('header.php'); ?>

<?php
// Handle POST action (e.g. remove or move to cart)
if (isset($_POST['remove_wishlist'])) {
    $remove_id = (int)$_POST['remove_wishlist'];
    if (isset($_SESSION['wishlist']) && ($key = array_search($remove_id, $_SESSION['wishlist'])) !== false) {
        unset($_SESSION['wishlist'][$key]);
        $_SESSION['wishlist'] = array_values($_SESSION['wishlist']);
    }
    header('location: wishlist.php');
    exit;
}

if (isset($_POST['clear_wishlist'])) {
    $_SESSION['wishlist'] = [];
    header('location: wishlist.php');
    exit;
}

// Fetch all wishlist products
$wishlist_items = [];
if (!empty($_SESSION['wishlist'])) {
    $placeholders = implode(',', array_fill(0, count($_SESSION['wishlist']), '?'));
    $stmt = $pdo->prepare("SELECT p.*, e.ecat_name 
                           FROM tbl_product p 
                           LEFT JOIN tbl_end_category e ON p.ecat_id = e.ecat_id 
                           WHERE p.p_id IN ($placeholders) AND p.p_is_active = 1");
    $stmt->execute($_SESSION['wishlist']);
    $wishlist_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="page-banner" style="background: #131921; padding: 40px 0; border-bottom: 3px solid #febd69;">
    <div class="container">
        <div class="row">
            <div class="col-md-12 text-center">
                <h1 style="color: #fff; margin: 0; font-size: 28px; font-weight: 700;">
                    <i class="fa fa-heart" style="color: #e4144d; margin-right: 10px;"></i> My Wishlist
                </h1>
                <p style="color: #bbb; margin-top: 8px; font-size: 14px;">
                    Saved products you want to buy later
                </p>
            </div>
        </div>
    </div>
</div>

<div class="page" style="padding: 40px 0; background: #f8f9fa;">
    <div class="container">
        <div class="row">
            <div class="col-md-12">

                <?php if (empty($wishlist_items)): ?>
                    <div style="background: #fff; border: 1px solid #e5e5e5; border-radius: 8px; padding: 60px 20px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                        <div style="width: 100px; height: 100px; line-height: 100px; border-radius: 50%; background: #fdf2f4; color: #e4144d; font-size: 45px; margin: 0 auto 20px;">
                            <i class="fa fa-heart-o"></i>
                        </div>
                        <h3 style="font-weight: 700; color: #333; margin-bottom: 10px;">Your Wishlist is Empty</h3>
                        <p style="color: #777; font-size: 15px; max-width: 480px; margin: 0 auto 25px;">
                            Explore our store to find great items and click the heart icon on any product to save it here for later.
                        </p>
                        <a href="index.php" class="btn btn-warning btn-lg" style="background: #febd69; border-color: #a88734 #9c7e31 #846a29; color: #111; font-weight: 600; padding: 12px 35px; border-radius: 6px;">
                            <i class="fa fa-shopping-bag"></i> Continue Shopping
                        </a>
                    </div>
                <?php else: ?>
                    <div style="background: #fff; border: 1px solid #e5e5e5; border-radius: 8px; padding: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px; margin-bottom: 20px; flex-wrap: wrap;">
                            <h3 style="margin: 0; font-size: 20px; font-weight: 700; color: #333;">
                                Saved Items (<?php echo count($wishlist_items); ?>)
                            </h3>
                            <div>
                                <form action="" method="post" style="display: inline-block;">
                                    <button type="submit" name="clear_wishlist" value="1" class="btn btn-default btn-sm" onclick="return confirm('Are you sure you want to clear your entire wishlist?');">
                                        <i class="fa fa-trash-o"></i> Clear Wishlist
                                    </button>
                                </form>
                                <a href="index.php" class="btn btn-sm btn-warning" style="background: #febd69; border-color: #fcd200; color: #111; font-weight: 600; margin-left: 8px;">
                                    <i class="fa fa-plus"></i> Add More Items
                                </a>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover" style="vertical-align: middle;">
                                <thead>
                                    <tr style="background: #fdfdfd; font-size: 13px; text-transform: uppercase; color: #666; letter-spacing: 0.5px;">
                                        <th style="width: 100px;">Product</th>
                                        <th>Details</th>
                                        <th>Price</th>
                                        <th>Stock Status</th>
                                        <th style="width: 220px; text-align: right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($wishlist_items as $item): ?>
                                        <tr style="border-bottom: 1px solid #eee;">
                                            <td>
                                                <a href="product.php?id=<?php echo $item['p_id']; ?>">
                                                    <img src="assets/uploads/<?php echo htmlspecialchars($item['p_featured_photo']); ?>" alt="<?php echo htmlspecialchars($item['p_name']); ?>" style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px; border: 1px solid #e1e1e1;">
                                                </a>
                                            </td>
                                            <td style="vertical-align: middle;">
                                                <span class="text-muted" style="font-size: 12px; display: block; text-transform: uppercase;">
                                                    <?php echo htmlspecialchars($item['ecat_name'] ?? 'Product'); ?>
                                                </span>
                                                <h4 style="margin: 3px 0 6px; font-size: 16px; font-weight: 600;">
                                                    <a href="product.php?id=<?php echo $item['p_id']; ?>" style="color: #007185; text-decoration: none;">
                                                        <?php echo htmlspecialchars($item['p_name']); ?>
                                                    </a>
                                                </h4>
                                                <div style="font-size: 12px; color: #888;">
                                                    Item #<?php echo $item['p_id']; ?>
                                                </div>
                                            </td>
                                            <td style="vertical-align: middle;">
                                                <span style="font-size: 18px; font-weight: 700; color: #b12704;">
                                                    <?php echo format_price($item['p_current_price']); ?>
                                                </span>
                                                <?php if (!empty($item['p_old_price'])): ?>
                                                    <del style="color: #777; font-size: 13px; margin-left: 6px;">
                                                        <?php echo format_price($item['p_old_price']); ?>
                                                    </del>
                                                <?php endif; ?>
                                            </td>
                                            <td style="vertical-align: middle;">
                                                <?php if ($item['p_qty'] > 0): ?>
                                                    <span class="label label-success" style="font-size: 12px; padding: 4px 8px; font-weight: normal;">
                                                        <i class="fa fa-check"></i> In Stock (<?php echo $item['p_qty']; ?>)
                                                    </span>
                                                <?php else: ?>
                                                    <span class="label label-danger" style="font-size: 12px; padding: 4px 8px; font-weight: normal;">
                                                        <i class="fa fa-times"></i> Out of Stock
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="vertical-align: middle; text-align: right;">
                                                <div style="display: inline-flex; align-items: center; gap: 8px;">
                                                    <?php if ($item['p_qty'] > 0): ?>
                                                        <a href="product.php?id=<?php echo $item['p_id']; ?>" class="btn btn-warning btn-sm" style="background: #ffd814; border-color: #fcd200; color: #0f1111; font-weight: 600; border-radius: 20px; padding: 6px 14px;">
                                                            <i class="fa fa-shopping-cart"></i> View / Buy
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <form action="" method="post" style="display: inline;">
                                                        <input type="hidden" name="remove_wishlist" value="<?php echo $item['p_id']; ?>">
                                                        <button type="submit" class="btn btn-default btn-sm" title="Remove from wishlist" style="border-radius: 50%; width: 32px; height: 32px; padding: 0; line-height: 30px; text-align: center; color: #c00;">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>

