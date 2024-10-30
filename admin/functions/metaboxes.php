<?php
defined('ABSPATH') or die('Your mother was a hamster and your father smelt of elderberries!');

abstract class TRMI_Metabox {

  public static function add(){
    if(!TRS_Map_it::google_check()){
      return;
    }
    $screens = get_option( 'trmi_active_post_types' );
    foreach ($screens as $screen=>$status){
      if($status=='on'){
        add_meta_box(
          'trmi_post_settings',
          __('Map This Post','trs_mapit'), //Title
          array(self::class,'html'), // callback
          $screen,
          $context = 'normal', // location for display
          $priority = 'high' //position within location
        );
      }
    }
  }

  public static function save($post_id, $post=null, $update=true){
    $nonce = wp_verify_nonce( $_POST['mapit_nonce'], 'edit_position' );
    if($nonce){
      $coords = trmi_filter_validate_coordinates(array(
          floatval($_POST['_trmi_longitude']),
          floatval($_POST['_trmi_latitude']),
        )
      );
      $inputs = ($coords)? array('_trmi_longitude'=>$coords[0],'_trmi_latitude'=>$coords[1]) : array();
      $inputs = apply_filters('trmi_additional_metafields', $inputs,$_POST);
      foreach ($inputs as $field => $val){
        $current = get_post_meta( $post_id,$field, true );
        if($val=='' || !$val){
          // error_log('Deleting Entry for '.$field);
          delete_post_meta( $post_id, $field );
        }elseif(!$current && $current!='') {
            // error_log('Adding '.$field.': '.$val);
            add_post_meta(
              $post_id,
              $field,
              $val
          );
      }else{
        // error_log('Updating '.$field.': '.$val);
         update_post_meta(
            $post_id,
            $field,
            $val
        );
      }
    }
  }
}

  public static function html(){
    global $post_id;
    $long = $lat =  0;
    $long = get_post_meta( $post_id, $key = '_trmi_longitude', $single = true );
    $lat =  get_post_meta( $post_id, $key = '_trmi_latitude', $single = true );
    ?>
    <form>
      <?php
      wp_nonce_field( 'edit_position', $name = "mapit_nonce", $referer = true, $echo = true );
      ?>
      <label>Longitude</label>
      <input id="_trmi_longitude" type="text" name="_trmi_longitude" value="<?php echo $long; ?>" />
      <label>Latitude</label>
      <input id="_trmi_latitude" type="text" name="_trmi_latitude" value="<?php echo $lat; ?>" />
    </form>
    <?php
    do_action('trmi_after_coords_form');
    echo do_shortcode( '[trmi_map height=400px width=600px this_post=true zoom=13]' );
    do_action('trmi_after_metabox_map');
  }

}


add_action('add_meta_boxes', array('TRMI_Metabox', 'add'));
add_action('save_post', array('TRMI_Metabox', 'save'),10);
add_action('edit_attachment', array('TRMI_Metabox', 'save'),10);
