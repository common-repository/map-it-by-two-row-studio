<?php

defined('ABSPATH') or die('Your mother was a hamster and your father smelt of elderberries!');


if (! class_exists('TRS_Map_it')){
  /** Main Plugin Class **/
  class TRS_Map_it{
    /**
      * TRS_Map_it Instance.
      *
      * @param TRS_Map_it() the TRS_Map_it instance
      */
    protected $_instance;
    static public $options;
    protected static $_google_key;
    protected static $_google_server_key;
    protected static $_ready;
    public $active_post_types;
    static public $hide_map_markers;

    /**
      * TRS_Map_it notices object
      *
      * @param object
      */
    protected $notices;

    /**
     * Main TRS_Map_it Instance.
     *
     * Ensures that only one instance of plugin object exists in memory at any one
     * time. Also prevents needing to define globals all over the place.
     *
     * @return TRS_Map_it
     */
    public function instance(){
      if( is_null( SELF::$_instance )){
        SELF::$_instance = new self();
      }
      return SELF::$_instance;
    }

    /**
      * TRS_Map_it::__construct()
      *
      * Plugin initialization and setup
      */

    public function __construct(){
      SELF::$options = get_option('trmi_options');
      SELF::$hide_map_markers = SELF::$options['trmi_hide_google_markers'];
      $this->includes();
      $this->active_post_types = $this->set_active_post_types();
      $this->compatibility_check();
      $this->google_check();

      add_action('admin_enqueue_scripts',array($this,'admin_enqueues'));
      add_action('admin_enqueue_scripts',array($this,'front_end_enqueues'),20);
      add_action('wp_enqueue_scripts',array($this,'front_end_enqueues'),20);

      if(!SELF::$_google_key){
        error_log('Bailed on loading map actions since Google will just say "Nope!"');
      }
      $this->hooks_and_filters();
    }

    /**
      * TRS_Map_it::includes()
      *
      * Plugin file includes for both front- and back-end
      * operations
      */

    private function includes(){
      /* include always */
      $includes = array(
        'classes/google/class-map.php',
        'classes/common/class-post-query.php',
//        'classes/common/class-archive-query.php',
        'functions/shortcodes/trmi_map.php',
        'functions/helpers.php',
        'functions/hooks.php',
        'functions/filters.php',
        'functions/ajax.php',
      );
      foreach ($includes as $path){
        require_once trailingslashit( TRMI_PLUGINDIR ).$path;
      }

      /* back-end only includes */
      if( is_admin() || (defined('WP_CLI') && WP_CLI)){
        $admin_includes = array(
          'classes/common/class-notices.php',
          'admin/functions/settings.php',
          'admin/functions/metaboxes.php',
          'admin/functions/setup.php',
        );
        foreach ($admin_includes as $path){
          require_once trailingslashit( TRMI_PLUGINDIR ).$path;
        }
      }

    }

    /**
    * TRS_Map_It::hooks_and_filters()
    *
    * conditionally initiates plugin related hooks and filters
    */
    private function hooks_and_filters(){
      add_action('admin_menu','create_trmi_settings_menu');
      add_action('admin_init','register_trmi_options');
      add_action('add_meta_boxes',array($this,'post_meta_box_activation'));
    }

    /**
      * TRS_Map_it::admin_enqueues()
      *
      * Enqueues plugin file styles and scripts for back-end
      * operations
      */


    public static function admin_enqueues(){
      wp_enqueue_style( 'trmi_admin_styles', trailingslashit( TRMI_PLUGINURL).'admin/assets/admin_style.css');
      wp_register_script( 'trmi_admin_scripts', trailingslashit( TRMI_PLUGINURL ).'admin/assets/admin_js.js', array('jquery') );
      wp_enqueue_script( 'trmi_admin_scripts' );
    }

    /**
      * TRS_Map_it::front_end_enqueues()
      *
      * Enqueues plugin file styles and scripts for front-end
      * operations
      */

    public static function front_end_enqueues(){
      wp_enqueue_style( 'trmi_front_styles', trailingslashit( TRMI_PLUGINURL).'assets/css/trmi_css.css');
      wp_register_script( 'trmi_gmaps_js', TRMI_PLUGINURL.'assets/js/trmi_gmaps.js', array('jquery'),null,true );
      $registered = wp_enqueue_script( 'trmi_gmaps_js');
      // Google Map Javascript functions
      $key = TRS_Map_It::get_API_key();
      $libraries = apply_filters( 'trmi_google_libraries', array() );
      $args = apply_filters('trmi_google_map_params',array(
        'key'=>$key,
        'callback'=>'init_map',
        'libraries'=>implode(',',$libraries),
      ));
      wp_register_script( 'trmi_google_maps-async-defer', add_query_arg($args,'https://maps.googleapis.com/maps/api/js'), $deps = array('jquery','trmi_gmaps_js'), false, array( 'strategy'=>'async','in_footer'=>true ));
      wp_enqueue_script('trmi_google_maps-async-defer');

    }


    /**
      * TRS_Map_it::google_check()
      *
      * Ensures the user has a valid Google Maps API key
      * @return boolean
      */

    static public function google_check(){
      if(isset(SELF::$options['trmi_google_api_key'])){
        $key = SELF::$options['trmi_google_api_key'];
        // cannot easily check validity of key - client side operation for most applications
        // filter is for expansion to determine enabled APIs, restricted access, etc.
        $valid = apply_filters('trmi_key_check',true,$key);
        SELF::$_google_key = $key;
        SELF::$_ready = (SELF::$_google_key)?$valid:false;
        SELF::$_google_server_key = (isset( SELF::$options['trmi_server_google_api_key']) ) ? SELF::$options['trmi_server_google_api_key']:null;
        return true;
      }
      else{
        error_log('No Google API Key');
      }
    }

    private function compatibility_check(){

    }

    public function post_meta_box_activation(){
      foreach ($this->active_post_types as $screen){
        add_meta_box(
          'trmi_post_options',
          __('Map it! Settings for this post','trs_mapit'),
          array($this,'render_metabox'),
          $screen
        );
      }
    }

    /**
      * TRS_Map_it::set_active_post_types()
      *
      * sets the current post types for which the plugin is active
      * @return array
      */

    public function set_active_post_types(){
      $active_post_types = get_option('trmi_active_post_types');
      return $active_post_types;
    }

    /* this is not currently actually used to render the post metaboxes */
    public function render_metabox(){
      $lat = (get_post_meta( $post->ID, 'trmi_post_latitude', true )) ? (get_post_meta( $post->ID, 'trmi_post_latitude', true )) : '';
      $lng = (get_post_meta( $post->ID, 'trmi_post_longitude', true )) ? (get_post_meta( $post->ID, 'trmi_post_longitude', true )) : '';
       ?>
       <?php
          ob_start();
        ?>
       <p>
         This is where the glorious things will display!
       </p>
       <div class="trmi_row">
         <div id="trmi_post_data" class="trmi_col trmi_col_6">
           <table>
             <tr>
               <td>
                 <h4>
                   Latitude:
                 </h4>
               </td>
               <td>
                <input id="trmi_post_latitude" class="mid_text_input" name="trmi_post_latitude" type="text" value="<?php echo $lat;?>">
              </td>
            </tr>
            <tr>
              <td>
                <h4>
                  Longitude:
                </h4>
              </td>
              <td>
                <input id="trmi_post_longitude" class="mid_text_input" name="trmi_post_longitude" type="text" value="<?php echo $lng;?>">
              </td>
            </tr>
          </table>
         </div>
         <div id="map_col" class="trmi_col trmi_col_6">
           <h4>Map of location:</h4>
           <div id="map" class="trmi_admin_map">
            <?php
              if(! SELF::$_google_key){
                ?>
                You will need to register a valid Google Maps API key to see a map of this location.
                <?php
              }
              ?>
           </div>
         </div>
       </div>

       <?php
       $meta_box_content = ob_get_clean();
       echo apply_filters( 'trmi_post_meta_content', $meta_box_content, $lat, $lng );
    }

    public static function get_API_key(){
      return SELF::$_google_key;
    }

    public static function get_server_API_key(){
      if(isset(SELF::$_google_server_key) ){
        return SELF::$_google_server_key;
      }
      return SELF::$_google_key;
    }

    public static function is_valid(){
      return SELF::$_ready;
    }

  }
}


global $TRMI;
$TRMI = new TRS_Map_it;
