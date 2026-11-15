<?php
function get_ext($pdo,$fname)
{

	$up_filename=$_FILES[$fname]["name"];
	$file_basename = substr($up_filename, 0, strripos($up_filename, '.')); // strip extention
	$file_ext = substr($up_filename, strripos($up_filename, '.')); // strip name
	return $file_ext;
}

function ext_check($pdo,$allowed_ext,$my_ext) 
{

	$arr1 = array();
	$arr1 = explode("|",$allowed_ext);	
	$count_arr1 = count(explode("|",$allowed_ext));	

	for($i=0;$i<$count_arr1;$i++)
	{
		$arr1[$i] = '.'.$arr1[$i];
	}
	

	$str = '';
	$stat = 0;
	for($i=0;$i<$count_arr1;$i++)
	{
		if($my_ext == $arr1[$i])
		{
			$stat = 1;
			break;
		}
	}

	if($stat == 1)
		return true; // file extension match
	else
		return false; // file extension not match
}

function get_current_currency() {
	if (!isset($_SESSION['currency']) || !in_array($_SESSION['currency'], ['USD', 'KHR'])) {
		return 'USD';
	}
	return $_SESSION['currency'];
}

function get_current_language() {
	if (!isset($_SESSION['site_lang']) || !in_array($_SESSION['site_lang'], ['EN', 'KH'])) {
		return 'EN';
	}
	return $_SESSION['site_lang'];
}

function get_usd_to_khr_rate() {
	static $cached_rate = null;
	if ($cached_rate !== null) {
		return $cached_rate;
	}
	global $pdo;
	if (isset($pdo) && $pdo instanceof PDO) {
		try {
			$stmt = $pdo->query("SELECT usd_to_khr_rate FROM tbl_settings WHERE id = 1 LIMIT 1");
			if ($stmt) {
				$val = $stmt->fetchColumn();
				if ($val && (int)$val > 0) {
					$cached_rate = (int)$val;
					return $cached_rate;
				}
			}
		} catch (Exception $e) {}
	}
	$cached_rate = 4100;
	return $cached_rate;
}

function format_price($price_in_usd, $with_symbol = true) {
	$price_in_usd = (float)$price_in_usd;
	$curr = get_current_currency();
	
	if ($curr === 'KHR') {
		$rate = get_usd_to_khr_rate();
		// Round to nearest 100 Riels (standard in Cambodian transactions)
		$khr = round($price_in_usd * $rate, -2);
		$formatted = number_format($khr, 0);
		return $with_symbol ? $formatted . ' ៛' : $formatted;
	} else {
		$formatted = number_format($price_in_usd, 2);
		return $with_symbol ? '$' . $formatted : $formatted;
	}
}

function format_price_khr($price_in_usd) {
	$price_in_usd = (float)$price_in_usd;
	$khr = round($price_in_usd * get_usd_to_khr_rate(), -2);
	return number_format($khr, 0) . ' ៛';
}

function format_price_usd($price_in_usd) {
	return '$' . number_format((float)$price_in_usd, 2);
}

function render_flag_svg($country = 'KH') {
	if ($country === 'KH') {
		return '<svg class="flag-icon-svg" viewBox="0 0 640 480" width="20" height="14" style="border-radius:2px; vertical-align:middle; display:inline-block; box-shadow:0 1px 2px rgba(0,0,0,0.25);"><g fill-rule="evenodd"><path fill="#032ea1" d="M0 0h640v480H0z"/><path fill="#e00025" d="M0 120h640v240H0z"/><g fill="#ffffff"><path d="M320 160l-8 16h-16l-8 16h-16v40h96v-40h-16l-8-16h-16l-8-16zm-52 46l-6 12h-12v14h36v-14h-12l-6-12zm104 0l-6 12h-12v14h36v-14h-12l-6-12z"/><rect x="228" y="272" width="184" height="16" rx="2"/><rect x="244" y="252" width="152" height="16"/></g></g></svg>';
	}
	return '<svg class="flag-icon-svg" viewBox="0 0 640 480" width="20" height="14" style="border-radius:2px; vertical-align:middle; display:inline-block; box-shadow:0 1px 2px rgba(0,0,0,0.25);"><path fill="#bd3d44" d="M0 0h640v480H0z"/><path stroke="#fff" stroke-width="37" d="M0 55.4h640M0 129.2h640M0 203.1h640M0 276.9h640M0 350.8h640M0 424.6h640"/><path fill="#192f5d" d="M0 0h260v258.5H0z"/><circle cx="50" cy="50" r="9" fill="#fff"/><circle cx="130" cy="50" r="9" fill="#fff"/><circle cx="210" cy="50" r="9" fill="#fff"/><circle cx="90" cy="90" r="9" fill="#fff"/><circle cx="170" cy="90" r="9" fill="#fff"/><circle cx="50" cy="130" r="9" fill="#fff"/><circle cx="130" cy="130" r="9" fill="#fff"/><circle cx="210" cy="130" r="9" fill="#fff"/><circle cx="90" cy="170" r="9" fill="#fff"/><circle cx="170" cy="170" r="9" fill="#fff"/><circle cx="50" cy="210" r="9" fill="#fff"/><circle cx="130" cy="210" r="9" fill="#fff"/><circle cx="210" cy="210" r="9" fill="#fff"/></svg>';
}


function get_ai_id($pdo,$tbl_name) 
{
	$statement = $pdo->prepare("SHOW TABLE STATUS LIKE '$tbl_name'");
	$statement->execute();
	$result = $statement->fetchAll(PDO::FETCH_ASSOC);
	foreach($result as $row)
	{
		$next_id = $row['Auto_increment'];
	}
	return $next_id;
}

function render_modern_product_card($row, $pdo, $col_class = 'col-md-4 col-sm-6') {
    $p_id = (int)$row['p_id'];
    $p_name = htmlspecialchars($row['p_name']);
    $p_photo = htmlspecialchars($row['p_featured_photo']);
    $p_price = (float)$row['p_current_price'];
    $p_old_price = !empty($row['p_old_price']) ? (float)$row['p_old_price'] : 0;
    $p_qty = (int)$row['p_qty'];
    
    $discount = 0;
    if ($p_old_price > $p_price) {
        $discount = round((($p_old_price - $p_price) / $p_old_price) * 100);
    }

    $t_rating = 0;
    $stmt1 = $pdo->prepare("SELECT AVG(rating) as avg_r, COUNT(*) as cnt FROM tbl_rating WHERE p_id = ?");
    $stmt1->execute([$p_id]);
    $r_data = $stmt1->fetch(PDO::FETCH_ASSOC);
    $avg_rating = round((float)($r_data['avg_r'] ?? 0), 1);
    $rating_cnt = (int)($r_data['cnt'] ?? 0);

    $is_wishlist = isset($_SESSION['wishlist']) && in_array($p_id, $_SESSION['wishlist']);

    $cat_name = 'Featured';
    if (!empty($row['ecat_id'])) {
        $st_c = $pdo->prepare("SELECT ecat_name FROM tbl_end_category WHERE ecat_id = ?");
        $st_c->execute([$row['ecat_id']]);
        $c_res = $st_c->fetch(PDO::FETCH_ASSOC);
        if ($c_res && !empty($c_res['ecat_name'])) {
            $cat_name = $c_res['ecat_name'];
        }
    }
    ?>
    <div class="<?php echo $col_class; ?> mb_30">
        <div class="modern-product-card">
            <div class="card-thumb">
                <div class="card-badge-container">
                    <?php if ($discount > 0): ?>
                        <span class="badge-discount">-<?php echo $discount; ?>% OFF</span>
                    <?php endif; ?>
                    <?php if (!empty($row['p_is_featured']) && $row['p_is_featured'] == 1): ?>
                        <span class="badge-featured">BESTSELLER</span>
                    <?php endif; ?>
                </div>

                <div class="card-actions-hover">
                    <button type="button" class="card-action-btn btn-quickview" data-id="<?php echo $p_id; ?>" title="Quick View">
                        <i class="fa fa-eye"></i>
                    </button>
                    <button type="button" class="card-action-btn btn-wishlist-toggle <?php echo $is_wishlist ? 'active' : ''; ?>" data-id="<?php echo $p_id; ?>" title="Add to Wishlist">
                        <i class="fa <?php echo $is_wishlist ? 'fa-heart' : 'fa-heart-o'; ?>"></i>
                    </button>
                </div>

                <a href="product.php?id=<?php echo $p_id; ?>">
                    <img src="assets/uploads/<?php echo $p_photo; ?>" alt="<?php echo $p_name; ?>">
                </a>
            </div>

            <div class="card-body-content">
                <span class="card-category-name"><?php echo htmlspecialchars($cat_name); ?></span>
                <h4 class="card-product-title">
                    <a href="product.php?id=<?php echo $p_id; ?>" title="<?php echo $p_name; ?>">
                        <?php echo $p_name; ?>
                    </a>
                </h4>

                <div class="card-rating-wrapper">
                    <div class="card-rating-stars">
                        <?php
                        for ($i = 1; $i <= 5; $i++) {
                            if ($avg_rating >= $i) {
                                echo '<i class="fa fa-star"></i>';
                            } elseif ($avg_rating >= ($i - 0.5)) {
                                echo '<i class="fa fa-star-half-o"></i>';
                            } else {
                                echo '<i class="fa fa-star-o"></i>';
                            }
                        }
                        ?>
                    </div>
                    <span class="card-rating-count">(<?php echo $rating_cnt; ?>)</span>
                </div>

                <div class="card-price-section">
                    <span class="card-current-price">
                        <?php echo format_price($p_price); ?>
                    </span>
                    <?php if ($p_old_price > 0): ?>
                        <span class="card-old-price"><?php echo format_price($p_old_price); ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($p_qty <= 0): ?>
                    <div class="text-center" style="margin-top: 10px; padding: 6px; background: #fff0f3; color: #c00; font-weight: 600; font-size: 12px; border-radius: 4px;">
                        <i class="fa fa-times-circle"></i> Out of Stock
                    </div>
                <?php else: ?>
                    <a href="product.php?id=<?php echo $p_id; ?>" class="btn-modern-cart">
                        <i class="fa fa-shopping-cart"></i> Add to Cart
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}

function render_modern_carousel_card($row, $pdo) {
    $p_id = (int)$row['p_id'];
    $p_name = htmlspecialchars($row['p_name']);
    $p_photo = htmlspecialchars($row['p_featured_photo']);
    $p_price = (float)$row['p_current_price'];
    $p_old_price = !empty($row['p_old_price']) ? (float)$row['p_old_price'] : 0;
    $p_qty = (int)$row['p_qty'];
    
    $discount = 0;
    if ($p_old_price > $p_price) {
        $discount = round((($p_old_price - $p_price) / $p_old_price) * 100);
    }

    $stmt1 = $pdo->prepare("SELECT AVG(rating) as avg_r, COUNT(*) as cnt FROM tbl_rating WHERE p_id = ?");
    $stmt1->execute([$p_id]);
    $r_data = $stmt1->fetch(PDO::FETCH_ASSOC);
    $avg_rating = round((float)($r_data['avg_r'] ?? 0), 1);
    $rating_cnt = (int)($r_data['cnt'] ?? 0);

    $is_wishlist = isset($_SESSION['wishlist']) && in_array($p_id, $_SESSION['wishlist']);

    $cat_name = 'Featured';
    if (!empty($row['ecat_id'])) {
        $st_c = $pdo->prepare("SELECT ecat_name FROM tbl_end_category WHERE ecat_id = ?");
        $st_c->execute([$row['ecat_id']]);
        $c_res = $st_c->fetch(PDO::FETCH_ASSOC);
        if ($c_res && !empty($c_res['ecat_name'])) {
            $cat_name = $c_res['ecat_name'];
        }
    }
    ?>
    <div class="item">
        <div class="modern-product-card" style="margin-bottom: 0;">
            <div class="card-thumb">
                <div class="card-badge-container">
                    <?php if ($discount > 0): ?>
                        <span class="badge-discount">-<?php echo $discount; ?>% OFF</span>
                    <?php endif; ?>
                    <?php if (!empty($row['p_is_featured']) && $row['p_is_featured'] == 1): ?>
                        <span class="badge-featured">BESTSELLER</span>
                    <?php endif; ?>
                </div>

                <div class="card-actions-hover">
                    <button type="button" class="card-action-btn btn-quickview" data-id="<?php echo $p_id; ?>" title="Quick View">
                        <i class="fa fa-eye"></i>
                    </button>
                    <button type="button" class="card-action-btn btn-wishlist-toggle <?php echo $is_wishlist ? 'active' : ''; ?>" data-id="<?php echo $p_id; ?>" title="Add to Wishlist">
                        <i class="fa <?php echo $is_wishlist ? 'fa-heart' : 'fa-heart-o'; ?>"></i>
                    </button>
                </div>

                <a href="product.php?id=<?php echo $p_id; ?>" class="card-img-link">
                    <img src="assets/uploads/<?php echo $p_photo; ?>" alt="<?php echo $p_name; ?>">
                </a>
            </div>

            <div class="card-body-content">
                <span class="card-category-name"><?php echo htmlspecialchars($cat_name); ?></span>
                <h4 class="card-product-title">
                    <a href="product.php?id=<?php echo $p_id; ?>" title="<?php echo $p_name; ?>">
                        <?php echo $p_name; ?>
                    </a>
                </h4>

                <div class="card-rating-wrapper">
                    <div class="card-rating-stars">
                        <?php
                        for ($i = 1; $i <= 5; $i++) {
                            if ($avg_rating >= $i) {
                                echo '<i class="fa fa-star"></i>';
                            } elseif ($avg_rating >= ($i - 0.5)) {
                                echo '<i class="fa fa-star-half-o"></i>';
                            } else {
                                echo '<i class="fa fa-star-o"></i>';
                            }
                        }
                        ?>
                    </div>
                    <span class="card-rating-count">(<?php echo $rating_cnt; ?>)</span>
                </div>

                <div class="card-price-section">
                    <span class="card-current-price">
                        <?php echo format_price($p_price); ?>
                    </span>
                    <?php if ($p_old_price > 0): ?>
                        <span class="card-old-price"><?php echo format_price($p_old_price); ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($p_qty <= 0): ?>
                    <div class="text-center" style="margin-top: 10px; padding: 6px; background: #fff0f3; color: #c00; font-weight: 600; font-size: 12px; border-radius: 4px;">
                        <i class="fa fa-times-circle"></i> Out of Stock
                    </div>
                <?php else: ?>
                    <a href="product.php?id=<?php echo $p_id; ?>" class="btn-modern-cart">
                        <i class="fa fa-shopping-cart"></i> Add to Cart
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}