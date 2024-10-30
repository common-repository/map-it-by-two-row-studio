<?php

defined('ABSPATH') or die('Your mother was a hamster and your father smelt of elderberries!');



function trmi_insert_map($atts){
  ob_start();
  $map = new TRMI_MAP_OBJECT($atts);
  ?>
  <div class="map_block">
    <?php
    echo $map->render_map();
    ?>
  </div>
<?php
  $html=ob_get_clean();
  return $html;
}

add_shortcode( 'trmi_map', 'trmi_insert_map' );
