<?php
define("ALL", "All");

add_action('wp_enqueue_scripts', 'themeNameScripts');

function themeNameScripts()
{
    wp_enqueue_style('style', get_template_directory_uri() . '/assets/css/style.css');
    wp_enqueue_style('bootstrap', get_template_directory_uri() . '/assets/css/bootstrap.css');
    wp_enqueue_style('jquery-ui', get_template_directory_uri() . '/assets/css/jquery-ui.css');

    wp_enqueue_script('jquery-script', get_template_directory_uri() . '/assets/js/jquery-3.5.1.js');
    wp_enqueue_script('jquery-ui', get_template_directory_uri() . '/assets/js/jquery-ui.js');
    wp_enqueue_script('script', get_template_directory_uri() . '/assets/js/script.js');
    wp_enqueue_script('font-awesome', 'https://kit.fontawesome.com/7744ceb262.js');


    $admin_ajax = admin_url('admin-ajax.php');
    $checkout_url = wc_get_checkout_url();

    $categories = getCategories();

    $data = [];

    foreach ($categories as $category) {
        $data [$category] = [
            'title'    => $category,
            'limit'    => getMaxProductQuantity($category),
            'required' => getRequiredProductOption($category),
            'items'    => [],
        ];
    }

    wp_localize_script('script', 'start_data',
        [
            'url'          => $admin_ajax,
            'checkout_url' => $checkout_url,
            'categories'   => $data,
            'max_price'    => getMaxPrice(),
            'min_price'    => getMinPrice(),
        ]);

}

function getMaxPrice()
{
    global $wpdb;
    $newtable = $wpdb->get_results(" 
                                        SELECT MAX(CAST(meta_value AS DECIMAL(10))) AS max
                                        FROM `wp_postmeta` 
                                        WHERE `meta_key`='_price'
");
    return $newtable[0]->max;
}


function getMinPrice()
{
    global $wpdb;
    $newtable = $wpdb->get_results(" 
                                        SELECT MIN(CAST(meta_value AS DECIMAL(10))) AS min
                                        FROM `wp_postmeta` 
                                        WHERE `meta_key`='_price'
");
    return $newtable[0]->min;
}

function getCategories()
{
    $categories = get_categories([
        'type'     => 'product',
        'taxonomy' => 'product_cat',
        'exclude'  => '15',
    ]);
    $result = [];

    foreach ($categories as $category) {
        $result [] = $category->slug;
    }

    return $result;
}

function getMaxProductQuantity($product_name)
{
    $tag = get_term_by('name', $product_name, 'product_cat');
    $id = $tag->term_id;
    $maxProductQuantity = get_term_meta($id, 'maxvalue', 'true');
    return $maxProductQuantity;
}

function getRequiredProductOption($product_name)
{
    $tag = get_term_by('name', $product_name, 'product_cat');
    $id = $tag->term_id;
    $requiredOption = get_term_meta($id, 'required', 'true');
    return $requiredOption;
}

add_theme_support('menus');
add_theme_support('widgets');
add_theme_support('post-thumbnails');

add_action('wp_ajax_function', 'ajaxFunction');
add_action('wp_ajax_nopriv_function', 'ajaxFunction');

add_action('wp_ajax_myfilter', 'filterFunction');
add_action('wp_ajax_nopriv_myfilter', 'filterFunction');

function ajaxFunction()
{

    $all_categories = getCategories();
    $check_categories = [];
    $product_categories = [];

    if (!isset($_POST['param'])) {
        return;
    }

    $result = $_POST['param'];

    global $woocommerce;
    $woocommerce->cart->empty_cart($clear_persistent_cart = true);

    foreach ($result as $item) {
        WC()->cart->add_to_cart($item);

        $category = array_shift(get_the_terms($item, 'product_cat'))->slug;
        $product_categories [] = $category;
    }

    foreach ($all_categories as $cat) {
        if (getRequiredProductOption($cat)) {
            $check_categories[] = $cat;
        }
    }

    $diff_categories = array_diff($check_categories, $product_categories);

    $result = [];

    if (sizeof($diff_categories) !== 0) {
        $result ['response_code'] = 400;
        $result ['required_categories'] = $diff_categories;
    } else {
        $result ['response_code'] = 200;
    }

    wp_send_json($result);

    die();
}

function filterFunction()
{

    $min = $_POST['min'] ? $_POST['min'] : getMinPrice();
    $max = $_POST['max'] ? $_POST['max'] : getMaxPrice();

    if (isset($_POST['categoryfilter'])) {
        $categoryfilter = $_POST['categoryfilter'];

        if ($_POST['categoryfilter'] === ALL) {
            $categoryfilter = getCategories();
        }
    }


    $args = [
        'numberposts' => -1,
        'post_type'   => 'product',
        'tax_query'   => [
            [
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $categoryfilter,
            ],
        ],
        'meta_query'  => [
            [
                'key'     => '_price',
                'value'   => [$min, $max],
                'compare' => 'BETWEEN',
                'type'    => 'NUMERIC',
            ],
        ],

    ];

    $products = get_posts($args);
    $filtered_products = [];

    foreach ($products as $item) {
        $filtered_products[] = $item->ID;
    }

    $result ['result'] = $filtered_products;
    wp_send_json($result);
}
