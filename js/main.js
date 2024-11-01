/*
 * IMPORTANT!
 * ----------
 * If you want to use jQuery in the WordPress admin area, you should always use 'jQuery' instead of '$', as '$' very often leads to problems.
 * ----------
 */
jQuery(document).ready(function($) {

  // Product Category Filter in Stock Management
  jQuery(document).on('change', '#product_category', function() {
    let selectedCategory = jQuery(this).val();

    if (selectedCategory === "") {
      jQuery('#product_list tr').show();
    } else {
      jQuery('#product_list tr').hide();
      jQuery('#product_list tr[data-id="' + selectedCategory + '"]').show();
    }
  });



  // Search Product in Stock Management
  jQuery(document).on("keyup", '#product_search', function() {
    let value = jQuery(this).val().toLowerCase();

    jQuery("#product_list tr").filter(function() {
      jQuery(this).toggle(jQuery(this).text().toLowerCase().indexOf(value) > -1)
    });
  });



  // Clear "Category Filter" and "Search Field" in Stock Management
  jQuery(document).on("click", "#btn_clear_filter_search", function() {
    jQuery('#product_category').val('');
    jQuery('#product_search').val('');
    jQuery('#product_list tr').show();
  });

});
