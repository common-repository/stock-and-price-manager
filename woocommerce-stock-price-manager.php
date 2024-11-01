<?php

/**
 * Stock and Price Manager
 *
 * Plugin Name: Stock and Price Manager
 * Plugin URI: https://slimbold.com/en/stock-and-price-management-plugin-for-woocommerce-wordpress/
 * Description: A plugin that makes it easy to manage prices and stocks in your WooCommerce store.
 * Version: 1.0.2
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * Tested up to: 6.6
 * Stable tag: 1.0.2
 * Author: Slim Bold
 * Author URI: https://slimbold.com/
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Requires Plugins: woocommerce
 * Text Domain: stock-and-price-manager
 */




if ( !defined('ABSPATH') ) exit; // Exit if accessed directly




/*
 * This function is necessary for the Stock Management extension to work
 *
 * This function inserts scripts (css, javascript) to the admin pages
 */
function sapmpwp_add_admin_scripts() {
  wp_enqueue_style('admin-styles', plugin_dir_url(__FILE__) . 'css/main.min.css', array(), '1.0.0');
  wp_enqueue_script('jquery');
  wp_enqueue_script('admin-scripts', plugin_dir_url(__FILE__) . 'js/main.js', array('jquery'), true, true);
}
add_action('admin_enqueue_scripts', 'sapmpwp_add_admin_scripts');




/*
 * Function to create a submenu page under the WooCommerce menu -> "Manage Stocks"
 */
function sapmpwp_manage_stocks_submenu_page() {
  $parent_slug = 'woocommerce'; // Parent menu slug
  $page_title = 'Manage Prices and Stocks'; // Title displayed in the browser window/tab
  $menu_title = 'Manage Prices and Stocks'; // Title displayed in the menu
  $capability = 'manage_options'; // User permission required to access this page
  $menu_slug = 'manage-stocks'; // Slug used in the URL
  $callback = 'sapmpwp_manage_stocks_page_content'; // Render the page content

  add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback);
}
add_action('admin_menu', 'sapmpwp_manage_stocks_submenu_page');




// Page Content - 'Manage Stock'
function sapmpwp_manage_stocks_page_content() {

  // Query WooCommerce for products
  $products = new WP_Query(array(
    'post_type' => 'product',
    'posts_per_page' => -1,
  ));

  // Get product categories
  $categories = get_categories(array(
    'taxonomy' => 'product_cat',
    'hide_empty' => true,
  ));

  // Initialize category filter
  $category_filter = '<div id="filter_container"><select id="product_category" name="product_category"><option value="">' . esc_html__('All Categories', 'stock-and-price-manager') . '</option>';

  // Loop through categories
  foreach ($categories as $category) {
    $category_filter .= '<option value="' . esc_attr($category->slug) . '">' . esc_html($category->name) . '</option>';
  }

  $category_filter .= '</select>';

  // Product search input
  $product_search = '<input id="product_search" type="text" placeholder="' . esc_attr__('Search Product...', 'stock-and-price-manager') . '"><button id="btn_clear_filter_search">' . esc_html__('Clear', 'stock-and-price-manager') . '</button></div>';

  // Initialize products table
  $products_table = '<h1>' . esc_html__('Stock Management', 'stock-and-price-manager') . '</h1>' . $category_filter . $product_search;

  // Check if there are any products
  if ($products->have_posts()) {
    // Open form tag with proper action pointing to admin-post.php for processing
    $products_table .= '<form method="post" id="form_product_list" action="' . esc_url(admin_url('admin-post.php')) . '">
      <input type="hidden" name="action" value="sapmpwp_handle_stock_submission">';

    // Output nonce field correctly
    $products_table .= wp_nonce_field('sapmpwp_action_stock_list', 'sapmpwp_form_nonce_field', true, false);

    $products_table .= '<table><thead><tr><th>' . esc_html__('Product Name', 'stock-and-price-manager') . '</th><th>' . esc_html__('Category', 'stock-and-price-manager') . '</th><th>' . esc_html__('Regular Price', 'stock-and-price-manager') . '</th><th>' . esc_html__('Current Stock', 'stock-and-price-manager') . '</th><th>' . esc_html__('Stock Change (+/-)', 'stock-and-price-manager') . '</th></tr></thead><tbody id="product_list">';

    // Loop through each product
    while ($products->have_posts()) {
      $products->the_post();

      // Get product object
      $product = wc_get_product(get_the_ID());

      // Get product categories
      $product_categories = get_the_terms(get_the_ID(), 'product_cat');
      $category_names = '';

      // Check if categories were found
      if (!empty($product_categories) && !is_wp_error($product_categories)) {
        foreach ($product_categories as $category) {
          $category_names .= esc_html($category->name) . ', ';
        }
        $category_names = rtrim($category_names, ', ');
      } else {
        $category_names = esc_html__('Uncategorized', 'stock-and-price-manager');
      }

      // Populate table rows with escaped data
      $products_table .= '<tr data-id="' . esc_attr($category->slug) . '">
              <td><a href="' . esc_url(get_permalink()) . '" target="_blank">' . esc_html(get_the_title()) . '</a></td>
              <td>' . esc_html($category_names) . '</td>
              <td><input type="number" name="regular_price[' . esc_attr($product->get_id()) . ']" value="' . esc_attr($product->get_regular_price()) . '" step="0.0001" class="regular_price"></td>
              <td><input type="number" name="current_stock[' . esc_attr($product->get_id()) . ']" value="' . esc_attr($product->get_stock_quantity()) . '" class="current_stock"></td>
              <td><input type="number" name="stock_change[' . esc_attr($product->get_id()) . ']" class="stock_change"></td>
            </tr>';
    }

    // Close table and form
    $products_table .= '</tbody></table>
      <button id="btn_update_stocks_data" type="submit" name="update_stocks_list">' . esc_html__('Update', 'stock-and-price-manager') . '</button>
      </form>';
  } else {
    $products_table .= '<h3>' . esc_html__('No products found!', 'stock-and-price-manager') . '</h3>';
  }

  // Output the table
  echo $products_table;
}




// Handle the form submission
function sapmpwp_handle_stock_submission() {
  // Ensure the user is logged in
  if ( !is_user_logged_in() ) {
    wp_die( 'You need to be logged in to submit this form.' );
  }

  // Ensure the form is submitted via POST and check the nonce
  if ( !empty($_POST) && check_admin_referer('sapmpwp_action_stock_list', 'sapmpwp_form_nonce_field') ) {

    // Check if the 'update_stocks_list' form field is present
    if ( isset($_POST['update_stocks_list']) ) {
      // Process the form data, sanitize inputs, etc.
      $current_stock = array_map('intval', $_POST['current_stock']);
      $stock_change = array_map('intval', $_POST['stock_change']);
      $regular_price = array_map('floatval', $_POST['regular_price']);

      global $wpdb;

      // Loop through each product and update the stock and price
      foreach ( $current_stock as $key_changed => $current_value ) {
        $current_value = intval( $current_value );
        $changed_value = intval( $stock_change[$key_changed] );
        $current_regular_price = floatval( $regular_price[$key_changed] );

        // Calculate the new stock quantity
        $newNumber = $current_value + $changed_value;

        // Format the price to 4 decimal places
        $set_product_meta_price = number_format( $current_regular_price, 4, '.', '' );

        // Update the stock quantity and price in the database
        $wpdb->update(
          $wpdb->prefix . 'wc_product_meta_lookup',
          array(
            'min_price'      => sanitize_text_field( $set_product_meta_price ),
            'max_price'      => sanitize_text_field( $set_product_meta_price ),
            'stock_quantity' => intval( $newNumber )
          ),
          array('product_id' => intval( $key_changed ))
        );

        // Update the stock quantity in the postmeta table
        $wpdb->update(
          $wpdb->prefix . 'postmeta',
          array('meta_value' => intval( $newNumber )),
          array(
            'post_id'  => intval( $key_changed ),
            'meta_key' => '_stock'
          )
        );

        // Update the regular price in the postmeta table
        $wpdb->update(
          $wpdb->prefix . 'postmeta',
          array('meta_value' => $set_product_meta_price),
          array(
            'post_id'  => intval( $key_changed ),
            'meta_key' => '_regular_price'
          )
        );
      }

      // After processing, redirect back to the manage stock page or show a success message
      wp_redirect( add_query_arg('success', '1', wp_get_referer()) );
      exit;

    } else {
      wp_die( 'Invalid form submission.' );
    }

  } else {
    wp_die( 'Form not submitted or verification failed.' );
  }
}
add_action('admin_post_sapmpwp_handle_stock_submission', 'sapmpwp_handle_stock_submission');


?>
