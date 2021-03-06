<div class="col-sm-<?php echo $content_width; ?> cm-i-card-products">
  <h4><?php echo sprintf(MODULE_CONTENT_CARD_PRODUCTS_HEADING, strftime('%B')); ?></h4>

  <div class="<?php echo $card_layout; ?>">
    <?php
    while ($card_products = tep_db_fetch_array($card_products_query)) {
      ?>
      <div class="col mb-2">
        <div class="card h-100 is-product" data-is-special="<?php echo (int)$card_products['is_special']; ?>" data-product-price="<?php echo $currencies->display_raw($card_products['final_price'], tep_get_tax_rate($card_products['products_tax_class_id'])); ?>" data-product-manufacturer="<?php echo max(0, (int)$card_products['manufacturers_id']); ?>">
          <a href="<?php echo tep_href_link('product_info.php', 'products_id=' . (int)$card_products['products_id']); ?>"><?php echo tep_image('images/' . $card_products['products_image'], htmlspecialchars($card_products['products_name']), SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT, '', true, 'card-img-top'); ?></a>
          <div class="card-body">         
            <h5 class="card-title">
              <a href="<?php echo tep_href_link('product_info.php', 'products_id=' . (int)$card_products['products_id']); ?>"><?php echo $card_products['products_name']; ?></a>
            </h5>
            <h6 class="card-subtitle mb-2 text-muted">
              <?php
              if ($card_products['is_special'] == 1) {
                echo sprintf(IS_PRODUCT_SHOW_PRICE_SPECIAL, $currencies->display_price($card_products['products_price'], tep_get_tax_rate($card_products['products_tax_class_id'])), $currencies->display_price($card_products['specials_new_products_price'], tep_get_tax_rate($card_products['products_tax_class_id'])));
              }
              else {
                echo sprintf(IS_PRODUCT_SHOW_PRICE, $currencies->display_price($card_products['products_price'], tep_get_tax_rate($card_products['products_tax_class_id'])));
              }
              ?>
            </h6>          
          </div>
          <div class="card-footer bg-white pt-0 border-0">
            <div class="btn-group" role="group">
              <?php
              echo tep_draw_button(IS_PRODUCT_BUTTON_VIEW, '', tep_href_link('product_info.php', tep_get_all_get_params(array('action', 'products_id', 'sort', 'cPath')) . 'products_id=' . (int)$card_products['products_id']), NULL, NULL, 'btn-info btn-product-listing btn-view') . PHP_EOL;
              $has_attributes = (tep_has_product_attributes((int)$card_products['products_id']) === true) ? '1' : '0';
              if ($has_attributes == 0) echo tep_draw_button(IS_PRODUCT_BUTTON_BUY, '', tep_href_link(basename($PHP_SELF), tep_get_all_get_params(array('action', 'products_id', 'sort', 'cPath')) . 'action=buy_now&products_id=' . (int)$card_products['products_id']), NULL, array('params' => 'data-has-attributes="' . $has_attributes . '" data-in-stock="' . (int)$card_products['in_stock'] . '" data-product-id="' . (int)$card_products['products_id'] . '"'), 'btn-light btn-product-listing btn-buy') . PHP_EOL;
              ?>
            </div>
          </div>
        </div>
      </div>
      <?php
    }
    ?>
  </div> 
</div>

<?php
/*
  $Id$

  CE Phoenix, E-Commerce made Easy
  https://phoenixcart.org

  Copyright (c) 2021 Phoenix Cart

  Released under the GNU General Public License
*/
?>
