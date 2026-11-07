<?php
ob_start();
session_start();
// Product Quick View Modal API
require_once('admin/inc/config.php');
require_once('admin/inc/functions.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo '<div class="alert alert-danger">Invalid product ID</div>';
    exit;
}

$stmt = $pdo->prepare("SELECT p.*, e.ecat_name, m.mcat_name, t.tcat_name
                       FROM tbl_product p
                       LEFT JOIN tbl_end_category e ON p.ecat_id = e.ecat_id
                       LEFT JOIN tbl_mid_category m ON e.mcat_id = m.mcat_id
                       LEFT JOIN tbl_top_category t ON m.tcat_id = t.tcat_id
                       WHERE p.p_id = ? AND p.p_is_active = 1");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo '<div class="alert alert-danger">Product not found</div>';
    exit;
}

// Fetch sizes
$sizes = [];
$stmt_size = $pdo->prepare("SELECT s.size_id, s.size_name 
                            FROM tbl_product_size ps 
                            JOIN tbl_size s ON ps.size_id = s.size_id 
                            WHERE ps.p_id = ?");
$stmt_size->execute([$id]);
$sizes = $stmt_size->fetchAll(PDO::FETCH_ASSOC);

// Fetch colors
$colors = [];
$stmt_color = $pdo->prepare("SELECT c.color_id, c.color_name 
                             FROM tbl_product_color pc 
                             JOIN tbl_color c ON pc.color_id = c.color_id 
                             WHERE pc.p_id = ?");
$stmt_color->execute([$id]);
$colors = $stmt_color->fetchAll(PDO::FETCH_ASSOC);

// Rating
$stmt_rating = $pdo->prepare("SELECT AVG(rating) as avg_r, COUNT(*) as cnt FROM tbl_rating WHERE p_id = ?");
$stmt_rating->execute([$id]);
$rating_data = $stmt_rating->fetch(PDO::FETCH_ASSOC);
$avg_rating = round((float)($rating_data['avg_r'] ?? 0), 1);
$rating_count = (int)($rating_data['cnt'] ?? 0);
?>

<div class="row">
    <div class="col-md-6 text-center">
        <div style="background: #fafafa; border: 1px solid #eee; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
            <img src="assets/uploads/<?php echo htmlspecialchars($product['p_featured_photo']); ?>" alt="<?php echo htmlspecialchars($product['p_name']); ?>" style="max-width: 100%; max-height: 320px; object-fit: contain; border-radius: 6px;">
        </div>
        <p class="text-muted" style="font-size: 12px;">
            <i class="fa fa-info-circle"></i> Click "View Full Details" to see multiple high-resolution photos
        </p>
    </div>
    <div class="col-md-6">
        <span class="text-muted" style="font-size: 12px; text-transform: uppercase; font-weight: 600; color: #e4144d;">
            <?php echo htmlspecialchars($product['ecat_name'] ?? 'Product'); ?>
        </span>
        <h3 style="margin-top: 5px; margin-bottom: 10px; font-weight: 700; font-size: 20px; color: #111;">
            <?php echo htmlspecialchars($product['p_name']); ?>
        </h3>

        <!-- Rating -->
        <div style="margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
            <div style="color: #ffa41c; font-size: 14px;">
                <?php
                for ($i = 1; $i <= 5; $i++) {
                    if ($avg_rating >= $i) {
                        echo '<i class="fa fa-star"></i> ';
                    } elseif ($avg_rating >= ($i - 0.5)) {
                        echo '<i class="fa fa-star-half-o"></i> ';
                    } else {
                        echo '<i class="fa fa-star-o"></i> ';
                    }
                }
                ?>
            </div>
            <span style="font-size: 13px; color: #007185;">
                <?php echo $rating_count > 0 ? "($rating_count customer reviews)" : "(No reviews yet)"; ?>
            </span>
        </div>

        <!-- Price -->
        <div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #eee;">
            <span style="font-size: 26px; font-weight: 700; color: #b12704;">
                <?php echo format_price($product['p_current_price']); ?>
            </span>
            <?php if (get_current_currency() === 'KHR'): ?>
                <span style="font-size: 13px; color: #565959; margin-left: 6px;">($<?php echo number_format($product['p_current_price'], 2); ?> USD)</span>
            <?php else: ?>
                <span style="font-size: 13px; color: #565959; margin-left: 6px;">(≈ <?php echo format_price_khr($product['p_current_price']); ?>)</span>
            <?php endif; ?>
            <?php if (!empty($product['p_old_price'])): ?>
                <del style="color: #777; font-size: 15px; margin-left: 10px;">
                    <?php echo format_price($product['p_old_price']); ?>
                </del>
                <?php 
                $savings = $product['p_old_price'] - $product['p_current_price'];
                $percent = round(($savings / $product['p_old_price']) * 100);
                if ($percent > 0): ?>
                    <span class="label label-danger" style="margin-left: 8px; font-size: 12px; padding: 3px 6px;">
                        Save <?php echo $percent; ?>%
                    </span>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Stock Status -->
        <div style="margin-bottom: 15px;">
            <?php if ($product['p_qty'] > 0): ?>
                <span style="color: #007600; font-weight: 600; font-size: 14px;">
                    <i class="fa fa-check-circle"></i> In Stock (<?php echo $product['p_qty']; ?> available)
                </span>
            <?php else: ?>
                <span style="color: #b12704; font-weight: 600; font-size: 14px;">
                    <i class="fa fa-times-circle"></i> Currently Out of Stock
                </span>
            <?php endif; ?>
        </div>

        <!-- Short description -->
        <?php if (!empty($product['p_short_description'])): ?>
            <div style="font-size: 13px; color: #555; margin-bottom: 20px; line-height: 1.6;">
                <?php echo nl2br(htmlspecialchars(substr(strip_tags($product['p_short_description']), 0, 180))) . '...'; ?>
            </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="product.php?id=<?php echo $product['p_id']; ?>" class="btn btn-warning btn-block" style="background: #ffd814; border-color: #fcd200; color: #0f1111; font-weight: 700; padding: 10px 20px; border-radius: 20px; font-size: 15px;">
                <i class="fa fa-shopping-cart"></i> View Details & Buy Now
            </a>
            <button type="button" class="btn btn-default btn-block btn-wishlist-toggle" data-id="<?php echo $product['p_id']; ?>" style="border-radius: 20px; font-size: 13px; font-weight: 600;">
                <i class="fa fa-heart" style="color: #e4144d;"></i> Add to Wishlist
            </button>
        </div>
    </div>
</div>

