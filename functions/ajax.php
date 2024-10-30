<?php

add_action( 'wp_ajax_trmi_update_results', 'trmi_update_results');
add_action( 'wp_ajax_nopriv_trmi_update_results', 'trmi_update_results');
add_action( 'wp_ajax_trmi_get_post_summary', 'trmi_get_post_summary');
add_action( 'wp_ajax_nopriv_trmi_get_post_summary', 'trmi_get_post_summary');

function trmi_update_results(){
  $nonce = $_POST['nonce'];
  if (!wp_verify_nonce( $nonce, 'trmi_embedded_map' )){
    wp_send_json_error( 'Nonce verification failure' );
    return;
  }
  $position = $_POST['center'];
  $position_array = trmi_filter_validate_coordinates($position) ;
  if (!$position_array){
    wp_send_json_error( 'invalid coordinates received' );
    return;
  }
  if ($_POST['post_types']){
    $post_type=$_POST['post_types'];
  }else{
    $post_type=array('any');
  }
  $atts = array('location'=>$position_array);
  if (isset ( $_POST['distance'] ) ) {
    $atts['distance']=$_POST['distance'];
  }
  ob_start();
  $map_query = new TRMI_MAP_OBJECT($atts);
  $map_query->get_map_data_section();
  $html_markers = ob_get_clean();
  $html_list = $map_query->render_list();
  wp_send_json_success( array('markers'=>$html_markers, 'list'=>$html_list));
}


function trmi_get_post_summary(){
  $nonce = $_POST['nonce'];
  if (!wp_verify_nonce( $nonce, 'trmi_embedded_map' )){
    wp_send_json_error( 'Nonce verification failure' );
    return;
  }
  if(!is_numeric($_POST['post_id']) || $_POST['post_id'] <=0){
    wp_send_json_error( 'Invalid post ID received' );
    return;
  }else{
    $id = $_POST['post_id'];
  }
  ob_start();
  ?>
  <div>
    <div class="thumbnail_container">
      <?php echo get_the_post_thumbnail($id);?>
    </div>
    <div class="post_summary_content" data-post_id="<?php echo $id;?>">
      <div class="title">
        <h3>
          <?php _e(apply_filters( 'trmi_infowindow_title',get_the_title($id),$id),'trs_mapit'); ?>
        </h3>
      </div>
      <div class="distance"></div>
      <div class="excerpt">
        <?php _e(apply_filters('trmi_infowindow_excerpt',get_the_excerpt($id),$id),'trs_mapit'); ?>
      </div>
    </div>
    <a href="<?php echo get_the_permalink( $id);?>" target="_blank">See more</a>
  </div>
  <?php
  $html=ob_get_clean();
  $html = apply_filters( 'trmi_infowindow_output', $html, $id );
  wp_send_json_success( array('html'=>$html) );
}


?>
