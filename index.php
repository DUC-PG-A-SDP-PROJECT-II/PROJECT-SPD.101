<?php require_once('header.php'); ?>

<?php
$statement = $pdo->prepare("SELECT * FROM tbl_settings WHERE id=1");
$statement->execute();
$result = $statement->fetchAll(PDO::FETCH_ASSOC);
foreach ($result as $row)
{
    $cta_title = $row['cta_title'];
    $cta_content = $row['cta_content'];
    $cta_read_more_text = $row['cta_read_more_text'];
    $cta_read_more_url = $row['cta_read_more_url'];
    $cta_photo = $row['cta_photo'];
    $featured_product_title = $row['featured_product_title'];
    $featured_product_subtitle = $row['featured_product_subtitle'];
    $latest_product_title = $row['latest_product_title'];
    $latest_product_subtitle = $row['latest_product_subtitle'];
    $popular_product_title = $row['popular_product_title'];
    $popular_product_subtitle = $row['popular_product_subtitle'];
    $total_featured_product_home = $row['total_featured_product_home'];
    $total_latest_product_home = $row['total_latest_product_home'];
    $total_popular_product_home = $row['total_popular_product_home'];
    $home_service_on_off = $row['home_service_on_off'];
    $home_welcome_on_off = $row['home_welcome_on_off'];
    $home_featured_product_on_off = $row['home_featured_product_on_off'];
    $home_latest_product_on_off = $row['home_latest_product_on_off'];
    $home_popular_product_on_off = $row['home_popular_product_on_off'];

}


?>

<div id="bootstrap-touch-slider" class="carousel bs-slider fade control-round indicators-line" data-ride="carousel" data-pause="hover" data-interval="false" >

    <!-- Indicators -->
    <ol class="carousel-indicators">
        <?php
        $i=0;
        $statement = $pdo->prepare("SELECT * FROM tbl_slider");
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
        foreach ($result as $row) {            
            ?>
            <li data-target="#bootstrap-touch-slider" data-slide-to="<?php echo $i; ?>" <?php if($i==0) {echo 'class="active"';} ?>></li>
            <?php
            $i++;
        }
        ?>
    </ol>

    <!-- Wrapper For Slides -->
    <div class="carousel-inner" role="listbox">

        <?php
        $i=0;
        $statement = $pdo->prepare("SELECT * FROM tbl_slider");
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
        foreach ($result as $row) {            
            ?>
            <div class="item <?php if($i==0) {echo 'active';} ?>" style="background-image:url(assets/uploads/<?php echo $row['photo']; ?>);">
                <div class="bs-slider-overlay"></div>
                <div class="container">
                    <div class="row">
                        <div class="slide-text <?php if($row['position'] == 'Left') {echo 'slide_style_left';} elseif($row['position'] == 'Center') {echo 'slide_style_center';} elseif($row['position'] == 'Right') {echo 'slide_style_right';} ?>">
                            <h1 data-animation="animated <?php if($row['position'] == 'Left') {echo 'zoomInLeft';} elseif($row['position'] == 'Center') {echo 'flipInX';} elseif($row['position'] == 'Right') {echo 'zoomInRight';} ?>"><?php echo $row['heading']; ?></h1>
                            <p data-animation="animated <?php if($row['position'] == 'Left') {echo 'fadeInLeft';} elseif($row['position'] == 'Center') {echo 'fadeInDown';} elseif($row['position'] == 'Right') {echo 'fadeInRight';} ?>"><?php echo nl2br($row['content']); ?></p>
                            <a href="<?php echo $row['button_url']; ?>" target="_blank"  class="btn btn-primary" data-animation="animated <?php if($row['position'] == 'Left') {echo 'fadeInLeft';} elseif($row['position'] == 'Center') {echo 'fadeInDown';} elseif($row['position'] == 'Right') {echo 'fadeInRight';} ?>"><?php echo $row['button_text']; ?></a>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            $i++;
        }
        ?>
    </div>

    <!-- Slider Left Control -->
    <a class="left carousel-control" href="#bootstrap-touch-slider" role="button" data-slide="prev">
        <span class="fa fa-angle-left" aria-hidden="true"></span>
        <span class="sr-only">Previous</span>
    </a>

    <!-- Slider Right Control -->
    <a class="right carousel-control" href="#bootstrap-touch-slider" role="button" data-slide="next">
        <span class="fa fa-angle-right" aria-hidden="true"></span>
        <span class="sr-only">Next</span>
    </a>

</div>




<?php if($home_featured_product_on_off == 1): ?>
<section class="product modern-home-section featured-section">
    <div class="container">
        <div class="modern-section-header">
            <div class="header-left">
                <span class="modern-section-badge badge-featured-pill"><i class="fa fa-bolt"></i> Hand-Picked Deals</span>
                <h2 class="modern-section-title"><?php echo htmlspecialchars($featured_product_title); ?></h2>
                <?php if(!empty($featured_product_subtitle)): ?>
                    <p class="modern-section-sub"><?php echo htmlspecialchars($featured_product_subtitle); ?></p>
                <?php endif; ?>
            </div>
            <div class="header-right">
                <a href="search-result.php?search_text=" class="modern-section-more-link">
                    <span>Explore All</span> <i class="fa fa-arrow-right"></i>
                </a>
            </div>
        </div>

        <div class="product-carousel owl-carousel owl-theme">
                    
            <?php
            $statement = $pdo->prepare("SELECT * FROM tbl_product WHERE p_is_featured=? AND p_is_active=? ORDER BY p_id DESC LIMIT ".$total_featured_product_home);
            $statement->execute(array(1,1));
            $result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
            foreach ($result as $row) {
                render_modern_carousel_card($row, $pdo);
            }
            ?>
        </div>
    </div>
</section>
<?php endif; ?>


<?php if($home_latest_product_on_off == 1): ?>
<section class="product modern-home-section latest-section">
    <div class="container">
        <div class="modern-section-header">
            <div class="header-left">
                <span class="modern-section-badge badge-latest-pill"><i class="fa fa-clock-o"></i> Just Arrived</span>
                <h2 class="modern-section-title"><?php echo htmlspecialchars($latest_product_title); ?></h2>
                <?php if(!empty($latest_product_subtitle)): ?>
                    <p class="modern-section-sub"><?php echo htmlspecialchars($latest_product_subtitle); ?></p>
                <?php endif; ?>
            </div>
            <div class="header-right">
                <a href="search-result.php?search_text=" class="modern-section-more-link">
                    <span>See New Releases</span> <i class="fa fa-arrow-right"></i>
                </a>
            </div>
        </div>

        <div class="product-carousel owl-carousel owl-theme">

            <?php
            $statement = $pdo->prepare("SELECT * FROM tbl_product WHERE p_is_active=? ORDER BY p_id DESC LIMIT ".$total_latest_product_home);
            $statement->execute(array(1));
            $result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
            foreach ($result as $row) {
                render_modern_carousel_card($row, $pdo);
            }
            ?>
        </div>
    </div>
</section>
<?php endif; ?>


<?php if($home_popular_product_on_off == 1): ?>
<section class="product modern-home-section popular-section">
    <div class="container">
        <div class="modern-section-header">
            <div class="header-left">
                <span class="modern-section-badge badge-popular-pill"><i class="fa fa-fire"></i> Best Sellers</span>
                <h2 class="modern-section-title"><?php echo htmlspecialchars($popular_product_title); ?></h2>
                <?php if(!empty($popular_product_subtitle)): ?>
                    <p class="modern-section-sub"><?php echo htmlspecialchars($popular_product_subtitle); ?></p>
                <?php endif; ?>
            </div>
            <div class="header-right">
                <a href="search-result.php?search_text=" class="modern-section-more-link">
                    <span>View Customer Favorites</span> <i class="fa fa-arrow-right"></i>
                </a>
            </div>
        </div>

        <div class="product-carousel owl-carousel owl-theme">

            <?php
            $statement = $pdo->prepare("SELECT * FROM tbl_product WHERE p_is_active=? ORDER BY p_total_view DESC LIMIT ".$total_popular_product_home);
            $statement->execute(array(1));
            $result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
            foreach ($result as $row) {
                render_modern_carousel_card($row, $pdo);
            }
            ?>
        </div>
    </div>
</section>
<?php endif; ?>


<?php if($home_service_on_off == 1): ?>
<!-- Modern Service / Trust & Value Proposition (Positioned Above Newsletter / Subscribe Section) -->
<section class="modern-service-section">
    <div class="container">
        <div class="modern-service-header">
            <span class="modern-service-pill"><i class="fa fa-shield"></i> Why Shop With Us</span>
            <h2 class="modern-service-title">The Confidence Behind Every Order</h2>
            <p class="modern-service-sub">Fast delivery across Cambodia, authentic quality, and dedicated customer support built for your complete peace of mind.</p>
        </div>

        <div class="row">
            <?php
            $statement = $pdo->prepare("SELECT * FROM tbl_service ORDER BY id ASC");
            $statement->execute();
            $result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
            foreach ($result as $row) {
            ?>
                <div class="col-md-4 col-sm-6 service-card-wrapper">
                    <div class="modern-service-card">
                        <div class="modern-service-icon-box">
                            <img src="assets/uploads/<?php echo htmlspecialchars($row['photo']); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>">
                        </div>
                        <h4 class="modern-service-card-title"><?php echo htmlspecialchars($row['title']); ?></h4>
                        <p class="modern-service-card-text">
                            <?php echo nl2br(htmlspecialchars($row['content'])); ?>
                        </p>
                    </div>
                </div>
            <?php
            }
            ?>
        </div>
    </div>
</section>
<?php endif; ?>




<?php require_once('footer.php'); ?>