<?php
get_header();
?>

<?php
$products = get_posts([
    'numberposts' => -1,
    'post_type'   => 'product',
    'order' => 'ASC'

]);

$categories = get_categories([
    'type'     => 'product',
    'taxonomy' => 'product_cat',
    'exclude'  => '15',
]);
?>

<div class="container-fluid position-r main-container">
    <div class="row">
        <div class="col">
            <form id="form">
                Choose category:
                <div class="form-group">
                    <?php

                    if ($categories) :
                        echo '<select class="form-control custom-select" name="categoryfilter"><option>All</option>';
                        foreach ($categories as $category) :
                            echo '<option value="' . $category->slug . '">' . $category->name . '</option>';
                        endforeach;
                        echo '</select>';
                    endif;

                    ?>
                </div>
                <div id="slider-range"></div>
                <p> From:
                    <input type="text" id="min" value="0" readonly name="min" style="border:0;">
                    To:
                    <input type="text" id="max" name="max" style="border:0;">
                </p>
                <input type="hidden" name="action" value="myfilter">
                <button type="button" id="filter" class="btn btn-success filter-btn">Filter</button>
            </form>

            <div class="max-count d-flex f-col">
                <p>Max count of products from each category: </p>
                <?php

                foreach ($categories as $category) {
                    if(getMaxProductQuantity($category->slug) !== "") {
                        ?>
                        <p><?php
                            echo $category->name.": ";
                            echo getMaxProductQuantity($category->slug);
                            ?>
                            peaces
                        </p>
                        <?php
                    }
                }
                ?>
            </div>

            <div class="d-flex f-col required-categories">
                <p>You need to order products from these required categories: </p>
                <?php

                foreach ($categories as $category) {
                    if(getRequiredProductOption($category->slug)) {
                        ?>
                            <p><?php echo $category->slug; ?></p>
                        <?php
                    }
                }
                ?>
            </div>
        </div>
        <div class="col-9">
            <div class="w-100 h-100">
                <div class="filter d-flex w-100">
                    <?php

                    foreach ($products as $product) {

                        $price = wc_get_product($product->ID);
                        $price = $price->get_price();

                        $term = get_the_terms ( $product->ID, 'product_cat' );

                        echo "<div id='$product->ID' data-check = '$product->ID' data-count='0' style=\"width: 18rem;\" class='product position-r {$term[0]->slug} w-100 h-100 pointer f-col items-center'>";

                        echo "<p   class='product__title m-0 w-100'>$product->post_title</p>";
                        echo get_the_post_thumbnail( $product->ID, 'thumbnail', ['class' => 'card-img-top'] );

                        echo "<p class='product__price m-0 position-a w-100'>$$price</p>";

                        echo "</div>";
                    }

                    ?>
                    <section class="loader">
                        <span class="load"></span>
                    </section>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-secondary" role="alert">
        <p></p>
        <button class="btn btn-danger alert-close">OK</button>
    </div>

    <button class="btn btn-info cart">Cart</button>

    <div class="modal-cart">
        <div class="col">
            <div class="modal-cart__content w-100 h-100 position-r">
                <div class="content__title">Cart</div>

            </div>
        </div>
        <button class="btn btn-success position-a modal-cart__button get-burger" type="button">Add to cart</button>
    </div>
</div>

<?php

get_footer();


