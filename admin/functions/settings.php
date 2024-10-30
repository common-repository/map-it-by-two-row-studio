<?php
defined('ABSPATH') or die('Your mother was a hamster and your father smelt of elderberries!');

function create_trmi_settings_menu(){
  add_menu_page(
    __('Map it!'),
    __('Map it!'),
    'edit_others_posts',
    'trmi_options',
    'build_trmi_settings_page',
    '',
    7.85 );
}

function build_trmi_settings_page(){
  // check user capabilities
  if ( !current_user_can( 'manage_options' ) ) {
    wp_die( __( 'You do not have sufficient permission to access this page.') );
  }

  // add error/update messages

  // check if the user have submitted the settings
  // wordpress will add the "settings-updated" $_GET parameter to the url
  if ( isset( $_GET['settings-updated'] ) ) {
  // add settings saved message with the class of "updated"
  add_settings_error( 'trmi_settings_messages', 'trmi_settings_message', __( 'Settings Saved', 'trs_mapit' ), 'updated' );
  }

  // show error/update messages
  settings_errors( 'trmi_settings_messages' );

  // build page content
  ?>
  <div class="admin_page_content">
    <h2>Map it! by <a href="https://tworowstudio.com" target="_blank">Two Row Studio</a></h2>
    <p>Version <?php echo get_option('_trmi_version');?></p>
    <p>Database ver. <?php echo get_option('_trmi_db_version');?></p>
    <form method="post" action="options.php">
      <?php
        settings_fields( 'trmi' );
        do_settings_sections( 'trmi');
        submit_button( 'Save Settings');
      ?>
    </form>
  </div>
  <?php
}

/**
* Setup function for Map it! options
*
* @since 1.0.0
*/

function register_trmi_options(){
  // set up options record in database
  register_setting('trmi','trmi_options');
  register_setting('trmi','trmi_active_post_types');
  //add section for Google API key and settings
  add_settings_section(
    'trmi_google_options',
    __('Your Google API Settings','trs_mapit'),
    'trmi_google_options_cb',
    'trmi'
  );
  //add section for post type selection
  add_settings_section(
      'trmi_post_options',
      __('Posts you want to map:','trs_mapit'),
      'trmi_post_options_cb',
      'trmi'
    );

    //add fields for each section
    add_settings_field(
      'trmi_google_api_key',
      __('Google Maps API Key for Front-end maps','trs_mapit'),
      'trmi_option_text_field',
      'trmi',
      'trmi_google_options',
      $args = array(
        'label_for'=>'trmi_google_api_key',
        'class' => 'long_text_input',
        'placeholder'=>'Enter your Google Maps API Key here'
      )
    );
    add_settings_field(
      'trmi_server_google_api_key',
      __('Google Maps API Key for Server to Server Functions','trs_mapit'),
      'trmi_option_text_field',
      'trmi',
      'trmi_google_options',
      $args = array(
        'label_for'=>'trmi_server_google_api_key',
        'class' => 'long_text_input',
        'placeholder'=>'Enter your Google Maps Server API Key here'
      )
    );
    add_settings_field(
      'trmi_default_longitude',
      __('Default Longitude','trs_mapit'),
      'trmi_option_text_field',
      'trmi',
      'trmi_google_options',
      $args = array(
        'label_for'=>'trmi_default_longitude',
        'class' => 'long_text_input',
        'placeholder'=>'Enter your default Longitude'
      )
    );
    add_settings_field(
      'trmi_default_latitude',
      __('Default Latitude','trs_mapit'),
      'trmi_option_text_field',
      'trmi',
      'trmi_google_options',
      $args = array(
        'label_for'=>__('trmi_default_latitude','trs_mapit'),
        'class' => __('long_text_input','trs_mapit'),
        'placeholder'=>__('Enter your default latitude','trs_mapit'),
      )
    );
    add_settings_field(
      'trmi_default_distance',
      __('Default Distance','trs_mapit'),
      'trmi_option_text_field',
      'trmi',
      'trmi_google_options',
      $args = array(
        'label_for'=>'trmi_default_distance',
        'class' => 'long_text_input',
        'placeholder'=>__('Enter your default search distance (unlimited if not set)','trs_mapit'),
      )
    );
    add_settings_field(
      'trmi_units',
      __('Units for Map Measurements','trs_mapit'),
      'trmi_option_radio_field',
      'trmi',
      'trmi_google_options',
      $args = array(
        'label_for'=>'trmi_units',
        'class' => 'radio',
        'radio_options'=>array(
          'miles'=>array(
            'label'=>__('Miles','trs_mapit'),
            'value'=>'miles',
          ),
          'km'=>array(
            'label'=>__('Kilometers','trs_mapit'),
            'value'=>'km',
          )
        ),
        'default_slug'=>'miles',
      )
    );
    add_settings_field(
      'trmi_hide_google_markers',
      __('Hide Google POI icons on base maps','trs_mapit'),
      'trmi_option_radio_field',
      'trmi',
      'trmi_google_options',
      $args = array(
        'label_for'=>'trmi_hide_google_markers',
        'class' => 'radio',
        'radio_options'=>array(
          'true'=>array(
            'label'=>__('Yes','trs_mapit'),
            'value'=>'true',
          ),
          'false'=>array(
            'label'=>__('No','trs_mapit'),
            'value'=>'false',
          )
        ),
        'default_slug'=>'false',
      )
    );
    add_settings_field(
      'trmi_active_post_types',
      __('Mapped Post Types','trs_mapit'),
      'trmi_active_post_types_cb',
      'trmi',
      'trmi_post_options',
      $args = array(
        'base_label_for'=>'trmi_active_post_types',
      )
    );

}

function trmi_google_options_cb(){
  ?>
  <div class="intructions">
    <p>
      <?php _e('To use the plugin, you will need to obtain a Google Maps API key. You can','trs_mapit')?> <a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank"> <?php  _e('see Google\'s instructions','trs_mapit');?></a> <?php _e('on how to do this and enter the key in the field below. You will only need one, but if you use any extensions that do lookups directly from your webserver, it is recommended that you get a second key that you can restrict for requests only from your server.','trs_mapit');?>
    </p>
    <p>
      <?php _e('You can also customize some basic features of your maps with the defaults listed here.','trs_mapit');?>
    </p>
  </div>
  <?php
}

/**
* Build section on page for post Type  settings
*
* @param array $args
*         'title'
*         'id'
*         'callback'
*/
function trmi_post_options_cb($args){
  ?>
  <br>
  <!-- <h3><?php echo $args['title'];?></h3> -->
  <p><?php __('Any of the posts selected here will display the settings for mapping so that mapping options will be enabled:','trs_mapit')?></p>
  <?php
}

/**
* Create form fields on page for Text Field settings
*
* @param array $args
*         'label_for'
*         'class'
*         'placeholder'
*/


function trmi_option_text_field($args){
  $options = get_option( 'trmi_options' );
  $val='';
  if(isset($options[$args['label_for']])){
    $val = $options[$args['label_for']];
  }
  ?>
  <input id="<?php echo esc_attr($args['label_for']); ?>" name="trmi_options[<?php echo esc_attr($args['label_for']); ?>]" type="text" placeholder="<?php _e($args['placeholder'],'trs_mapit'); ?>" class = "<?php echo esc_attr($args['class']); ?>" value="<?php echo $val;?>">
  <?php
}

/**
* Create form fields on page for Text Field settings
*
* @param array $args
*         'label_for'
*         'class'
*         'radio_options' as array('option_id'=>array('label','value'))
*         'default_slug'
*/

function trmi_option_radio_field($args){
  $options = get_option( 'trmi_options' );
  $chosen=$args['default_slug'];
  if(isset($options[$args['label_for']])){
    $chosen = $options[$args['label_for']];
  }
  foreach($args['radio_options'] as $choice=>$data){
    $selected = '';
    if ($data['value'] == $chosen){
      $selected = ' checked';
    }
    ?>
    <input id="<?php echo esc_attr($choice); ?>" name="trmi_options[<?php echo esc_attr($args['label_for']); ?>]" type="radio" class = "<?php echo esc_attr($args['class']); ?>"<?php echo $selected;?> value="<?php echo esc_attr($choice); ?>"> <?php _e($data['label'],'trs_mapit');?>
    <?php
  }
  ?>
  <br>
  <?php
}




/**
* Build section on page for post Type  settings
*
* @param array $args
*         'title'
*         'id'
*         'callback'
*/

function trmi_active_post_types_cb($args){
  global $TRMI;
  if(!$TRMI->google_check()){
    ?>
      <div class="trmi_col">
        You must acquire a Google API Key to enable the functions of this plugin for you posts.
      </div>
    <?php
    return;
  }
  $options = get_option( 'trmi_active_post_types' );
  $post_types= get_post_types(array('public'=>true),'objects');
  foreach($post_types as $post_type){
    $set_option = (isset($options[$post_type->name]))?$options[$post_type->name]:false;
    ?>
    <div class="trmi_col trmi_col_2 trmi_xs_col_12">
      <input id = "<?php echo esc_attr($args['base_label_for'].'_'.$post_type->name);?>" name = "trmi_active_post_types[<?php echo esc_attr($post_type->name);?>]" type="checkbox" <?php checked('on'==$set_option,1); ?>
        ><?php _e($post_type->labels->name,'trs_mapit');?>
    </div>
    <?php
  }
  $js_script = apply_filters('trmi_settings_page_js','function init_map(){}');
  ?>
  <script><?php echo $js_script;?></script> <!-- init_map() required to quiet maps API error on page load -->
  <div class="clearfix"></div>
  <?php
}



?>
