<?php

defined('ABSPATH') or die('Your mother was a hamster and your father smelt of elderberries!');

class TRMI_MAP_OBJECT {
  public $atts;
  protected $center_location;
  protected $post_args;
  protected $posts;
  protected $map_constraints;
  protected $height;
  protected $width;
  protected $show_list;
  protected $show_filter;
  protected $min_lat;
  protected $max_lat;
  protected $min_long;
  protected $max_long;
  protected $post_id;
  protected $post_ids;

  public function __construct($atts){
    $options = get_option( 'trmi_options', $default = false );
    $default_distance = $options['trmi_default_distance'];
    $units = $options['trmi_units'];
    if($units == 'miles'){
      $default_distance = trmi_miles_to_meters($default_distance);
    }elseif($units == 'km'){
      $default_distance = $default_distance * 1000;
    }
    /* add filter ONLY for intantiated map objects */
    add_filter ('get_meta_sql', 'trmi_reset_decimal_precision');

    $show_list = (is_admin() || is_single()) ? false : true;
    $this->atts = shortcode_atts( array(
        'posts'=>25,
        'post_status' => 'publish',
        'post_types'=>array('any'),
        'post_order'=>'ASC',
        'post_order_by'=>'menu_order',
        'filter'=>true,
        'meta_keys'=>'',
        'meta_values'=>'',
        'height' => '360px',
        'width' => '450px',
        'this_post' => false,
        'data_container'=>'map_data',
        'location' =>$this->get_default_location(), /* to be passsed as long,lat */
        'distance' => $default_distance, /* in meters */
        'map_id' => 'trmi_map',
        'zoom' => 8,
        'show_list' => $show_list,
      ),
      $atts,
      $shortcode = 'trmi_map'
    );
    $other_keys = explode(',',$this->atts['meta_keys']);
    $other_values = explode(',',$this->atts['meta_values']);
    $other_meta=array();
    $this->show_list = $this->atts['show_list'];
    $this->show_filter = ($this->atts['filter']===true || $this->atts['filter']==="true");
    if($this->atts['post_types'] != '' && ! is_array( $this->atts['post_types'] ) ){
      $this->atts['post_types'] = explode(',',$this->atts['post_types']);
    }
    if(!is_array($this->atts['post_types'])){
      $this->atts['post_types'] = array($this->atts['post_types']);
    }
    if($other_keys[0] != '' && (count( $other_keys) == count($other_values)) ){
      for ($i=0; $i<count($other_values);$i++){
        $other_meta[$other_keys[$i]] = $other_values[$i];
      }
    }elseif($other_keys[0] != null){
      error_log('meta keys exist, but the wrong number of values was received.');
    }

    error_log ('Map Attributes: '.print_r($atts,true));

    $this->center_location = (is_array($this->atts['location']))? $this->atts['location'] : $this->get_location_array($this->atts['location']);
    $adj_radius_for_lat = trmi_core_to_surface_radius_at_lat(deg2rad(floatval($this->center_location[1])));
    $radius_at_lat = trmi_slice_radius_at_lat(deg2rad(floatval($this->center_location[1])));
    $lat_diff = (360 * (floatval($this->atts['distance']) / (2 * pi() * $adj_radius_for_lat)));
    $long_diff =  ( 360 * (floatval($this->atts['distance'])/(2* pi() * $radius_at_lat)));
    $this->min_lat = max(-90,(round((floatval($this->center_location[1]) - $lat_diff),5)));
    $this->max_lat = min(90,(round((floatval($this->center_location[1]) + $lat_diff),5)));
    $this->min_long = max(-180,(round((floatval($this->center_location[0]) - $long_diff),5)));
    $this->max_long = min(180, (round((floatval($this->center_location[0]) + $long_diff),5)));

    if (TRS_Map_It::is_valid()){
      $options = get_option( 'trmi_active_post_types' );
      $post_types= get_post_types(array('public'=>true),'objects');
      $post_type_arr = array();
      foreach ($post_types as $post_type){
        if (isset($options[$post_type->name]) && $options[$post_type->name]=='on'){
          $post_type_arr[] = $post_type->name;
        }
      }
      if ($this->atts['this_post']){
        global $post;
        if(is_admin()){
          $this->post_id = $post->ID;
        }
        $map_post_args = array(
            'post__in'=>array($post->ID),
            'post_type'=>$post_type_arr,
        );
        if(get_post_type($post->ID) == 'attachment'){
          $map_post_args['post_type']='attachment';
          $map_post_args['post_status']=array('publish','inherit');
        }
      }elseif( is_archive() || ( wp_doing_ajax()  && isset($wp_query) ) ) {
        global $wp_query;
        $map_post_args = $wp_query->query_vars;
      }else{
        /* get posts to be mapped */
        $map_post_args = array(
            'posts_per_page' => $this->atts['posts'],
            'post_type'=>$this->atts['post_types'],
            'meta_query'=>array(
              'relation' => 'AND',
              array(
                'key' =>'_trmi_longitude',
                'compare' => 'EXISTS',
              array(
                'key'=>'_trmi_latitude',
                'compare' => 'EXISTS',
              )
            ),
          ),
        );
        /* adjust for pagination */
        if (is_paged()){
          $page = ( get_query_var('paged') ) ? get_query_var('paged') : 1;
          $map_post_args['offset'] = ($map_post_args['posts_per_page'] * ($page - 1));
        }

        /* limit by distance if set */
        if ($this->atts['distance']){
          $map_post_args['meta_query'] = array(
                        'relation' => 'AND',
                        array(
                          'key' =>'_trmi_longitude',
                          'value' => array(floatval($this->min_long),floatval($this->max_long)),
                          'compare' =>'BETWEEN',
                          'type' =>'DECIMAL'
                        ),
                        array(
                          'key'=>'_trmi_latitude',
                          'value' => array(floatval($this->min_lat),floatval($this->max_lat)),
                          'compare' =>'BETWEEN',
                          'type' =>'DECIMAL',
                        )
                      );
        }
      }
      if(in_array('attachment',$this->atts['post_types']) || in_array('any',$this->atts['post_types'])){
          $map_post_args['post_status']=array('publish','inherit');
      }
      if(0 != count($other_meta)){
        foreach ($other_meta as $meta_key=>$meta_value){
          $map_post_args['meta_query'][]=array('key'=>$meta_key,'value'=>$meta_value);
        }
      }
      // allow additional selection args for mapped posts
      $this->post_args = apply_filters('trmi_mapped_post_select_args',$map_post_args);
    }

  }

  public function get_map_data_section(){
    /* Generate html and re-order posts by distance */
    $initiated_posts = new TRMI_POST_QUERY($this->post_args,$this->atts['this_post']);
    foreach($initiated_posts->map_posts->posts as $mapped_post){
      $this->post_ids[]=$mapped_post->ID;
    };
    $this->posts = $initiated_posts->render_map_data($this->center_location[1],$this->center_location[0]);
    $this->map_constraints = array(
      'postnum'=>$initiated_posts->postnum,
      'total_long'=>$initiated_posts->total_long,
      'total_lat'=>$initiated_posts->total_lat,
      'max_long'=>$initiated_posts->max_long,
      'min_long'=>$initiated_posts->min_long,
      'max_lat'=>$initiated_posts->max_lat,
      'min_lat'=>$initiated_posts->min_lat,
    );
  }

  public static function get_default_location(){
    $trmi_options = get_option('trmi_options');
    $long = $trmi_options['trmi_default_longitude'];
    $lat = $trmi_options['trmi_default_latitude'];
    return $long.','.$lat;
  }

  public function get_location_array($long_lat_str){
    $clean_coords = preg_replace( '/\s*,\s*/', ',', $long_lat_str );
    return explode(',',$clean_coords);
  }

  public function render_map(){
    ob_start();
    ?>
    <div id="map_data">
      <?php
    $this->get_map_data_section();
    ?>
  </div>
  <?php

  if($this->show_filter){
    $this->render_filter();
  }
  /* build map elements */
  do_action('trmi_before_map_container');
    ?>
    <div id="<?php echo $this->atts['map_id'];?>" class="trmi_map_container" style="height:<?php echo $this->atts['height'];?>;width:<?php echo $this->atts['width'];?>">
    </div>
    <?php
    $this->render_map_js();
    $google_notice = '<div class="google_credit">Map and data provided by Google</div>';
    echo apply_filters( 'trmi_google_notice', $google_notice );

    do_action('trmi_after_map_container',$this->atts['filter']);
    if($this->show_list===true){
      do_action('trmi_before_post_summary_list');
      $classes = apply_filters( 'trmi_map_summary_classes', array() );
      ?>
        <div id="mapped_post_summaries" class="<?php echo implode(' ', $classes); ?>">
          <?php
            echo $this->render_list();
          ?>
        </div>
      <?php
      do_action('trmi_after_post_summary_list');
    }
    $html = ob_get_clean();
    return $html;
  }

  protected function render_filter(){
    ?>
    <div id="map_filter">
      <?php do_action('trmi_before_autocomplete'); ?>
      <?php echo apply_filters('trmi_render_map_filter',''); ?>
      <?php do_action('trmi_after_autocomplete'); ?>
    </div>
    <?php
  }

  protected function render_map_js(){
    $draggable = (is_admin())? 'draggable: true,' : '';
    $marker_params = $draggable.'
    position: post_loc,
    map: map';
    wp_nonce_field( 'trmi_embedded_map', $name = "trmi_embedded_map_nonce", $referer = true, $echo = true );
    ?>
    <script>

       var map;
       var markers=[];
       var info_window;
       var trigger_distance = 10000; /*distance in meters */
       var search_location = {
         lat:<?php echo floatval($this->map_constraints['total_lat']) / intval($this->map_constraints['postnum']);?>,
         lng:<?php echo floatval($this->map_constraints['total_long']) / intval($this->map_constraints['postnum']);?>
       };
       var bounds;
       <?php echo apply_filters( 'trmi_init_map_js_vars', "/* Developers: Use the 'trmi_init_map_js_vars' WP filter to add variables for map initialization */\r\n" );?>

      function init_map(map_id){
        bounds = new google.maps.LatLngBounds();
        map = new google.maps.Map(document.getElementById('<?php echo $this->atts['map_id'];?>'),
         {
           center: {
             lat:<?php echo floatval($this->map_constraints['total_lat']) / intval($this->map_constraints['postnum']);?>,
             lng:<?php echo floatval($this->map_constraints['total_long']) / intval($this->map_constraints['postnum']);?>
           },
           <?php
            if (TRS_Map_It::$hide_map_markers == true){
             ?>
             styles:[
               {
                 "featureType":"poi",
                 "stylers":[
                   {
                     "visibility":"off"
                   }
                 ]
               }
             ],
             <?php
           }
           ?>
           zoom:<?php echo $this->atts['zoom']; ?>
         }
         );
         // var distance_srv = new google.maps.DistanceMatrixService;
         info_window = new google.maps.InfoWindow({
           content:''
          });
        jQuery('.trmi_item').each(function(){
          add_markers(this);
        });
        map.fitBounds(bounds);
        jQuery('#mapped_post_summaries .mapped_post_record').each(function(){
          var summ_post_id = jQuery(this).data('post_id');
          var distance = jQuery('#map_data *[data-post_id="'+summ_post_id+'"]').data('distance_text')+' away';
          if (jQuery('#map_data *[data-post_id="'+summ_post_id+'"]').data('distance_text') == ''){
            distance = '';
          }
          jQuery(this).find('.distance').html(distance);
        });

        var map_zoom = map.getZoom();

        map.addListener('tilesloaded',function(){
          map.addListener('idle',function(){
            //check if map panned far enough to refresh
            var start_loc = new google.maps.LatLng(search_location.lat,search_location.lng);
            var end_loc = map.getCenter();
            var pan_trigger = check_coord_distance(search_location.lat,search_location.lng, end_loc.lat(), end_loc.lng(),trigger_distance);
            var zoomed = map.getZoom() != map_zoom;
            var map_distance = get_map_visible_distance();
            if(pan_trigger==true  || zoomed){
              trigger_distance = map_distance / 6;
              jQuery('#mapped_post_summaries').html('<div class="loading">Retrieving Results...</div>')
              jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php');?>',
                method: 'POST',
                data: {
                  action: 'trmi_update_results',
                  <?php
                    if( $this->post_args['post_type'] !='any' && ( !isset( $this->post_args['this_post'] ) || !$this->post_args['this_post'] ) ) {
                      $post_type_arg = (is_array($this->post_args['post_type']))?implode(',',$this->post_args['post_type']) : $this->post_args['post_type'];
                      echo 'post_types: "'.$post_type_arg.'",';
                    }
                  ?>
                  center: map.getCenter().lng() + ',' +map.getCenter().lat(),
                  distance: map_distance / 2,
                  nonce: jQuery('#trmi_embedded_map_nonce').val()
                }
              })
              .success(function(response){
                if(response.success == true){
                  clear_markers();
                  jQuery('#map_data').html(response.data.markers);
                  jQuery('.trmi_item').each(function(){
                    add_markers(this);
                  });
                  jQuery('#mapped_post_summaries').html(response.data.list);
                  jQuery('#mapped_post_summaries .mapped_post_record').each(function(){
                    var summ_post_id = jQuery(this).data('post_id');
                    var distance = jQuery('#map_data *[data-post_id="'+summ_post_id+'"]').data('distance_text')+' away';
                    jQuery(this).find('.distance').html(distance);
                  });
                  search_location = {
                    lat: map.getCenter().lat(),
                    lng:map.getCenter().lng()
                  }
                  map_zoom = map.getZoom();
                }
                /* Developers: Use the 'trmi_map_view_altered_js' WP filter to add actions on map move / resize / zoom */
                <?php echo apply_filters( 'trmi_map_view_altered_js', "" );?>
              });
            }
          });
        });
        var map_resize = map.addListener('tilesloaded',function(){
          if(map.getZoom() > 18 && !map.initial_sizing){
            map.setZoom(18);
          }
          map.initial_sizing = true;
        });
        <?php if(!$this->atts['this_post'] && $this->map_constraints != null){
          if($this->map_constraints['postnum'] > 1){?>
        map.fitBounds({
            south:<?php echo floatval($this->map_constraints['min_lat']);?>,
            west:<?php echo floatval($this->map_constraints['min_long']);?>,
            north:<?php echo floatval($this->map_constraints['max_lat']);?>,
            east:<?php echo floatval($this->map_constraints['max_long']);?>
        });
        <?php } ?>
        <?php if ($this->map_constraints['postnum'] =1){
          ?>
            var event_lat = jQuery(".trmi_item").data("lat");
            var event_lng = jQuery(".trmi_item").data("long")
            search_location = {
              lat:  event_lat,
              lng: event_lng
            };
            map.setCenter({lat:event_lat,lng:event_lng});
            map.setZoom(16);
          <?php
        }
       }elseif (is_admin()){
        ?>
          map.addListener('click',function(loc){
            var post_loc = loc.latLng;
            markers[<?php echo $this->post_id;?>] = new google.maps.Marker({
              <?php echo apply_filters( 'trmi_marker_parameters', $marker_params );?>
            });
            update_coords(markers[<?php echo $this->post_id;?>]);
            markers[<?php echo $this->post_id;?>].addListener('dragend',function(){
              update_coords(this);
            });
          });
        <?php
      } ?>
        /* Developers: Use the 'trmi_map_init_add_code' WP filter to add actions on map initialization */
        <?php echo apply_filters( 'trmi_map_init_add_code', "" );?>


      }

      function get_map_visible_distance(){
        return get_coord_distance(map.getBounds().getNorthEast().lat(),map.getBounds().getSouthWest().lng(),map.getBounds().getSouthWest().lat(),map.getBounds().getNorthEast().lng()) * .75;
      }

      function get_coord_distance (lat1,lng1,lat2,lng2) {
        var lat1_rad = deg2rad(parseFloat(lat1));
        var lng1_rad = deg2rad(parseFloat(lng1));
        var lat2_rad = deg2rad(parseFloat(lat2));
        var lng2_rad = deg2rad(parseFloat(lng2));
        var dlng_rad = lng2_rad - lng1_rad;
        var dlat_rad = lat2_rad - lat1_rad;

        var dlat_m = (2 * Math.PI * core_to_surface_radius_at_lat(lat1_rad))*(Math.abs((lat1 - lat2)) / 360);
        var dlng_m = (2 * Math.PI * slice_radius_at_lat(lat1_rad))*(Math.abs((lng1 - lng2)) / 360);

        var d_m = Math.sqrt(Math.pow(dlat_m,2) + Math.pow(dlng_m,2));
        return d_m;
      }

      function check_coord_distance(lat1,lng1,lat2,lng2,limit_m){
        var d_m = get_coord_distance(lat1,lng1,lat2,lng2,limit_m);
        return (d_m > limit_m);
      }

      function deg2rad(degrees){
        var pi = Math.PI;
        return degrees * (pi/180);
      }

      function core_to_surface_radius_at_lat(lat_rad){
        var radius = Math.sqrt((Math.pow((Math.pow(6378137 , 2) * Math.cos(lat_rad)),2) + Math.pow((Math.pow(6356752.31420 , 2) * Math.sin(lat_rad)), 2)) / (Math.pow((6378137 * Math.cos(lat_rad)),2)+(Math.pow((6356752.31420 * Math.sin(lat_rad)), 2))));
        return radius;
      }

      function slice_radius_at_lat(lat_rad){
        var radius = 6378137 * Math.cos(lat_rad);
        return radius;
      }

      function show_info_window(post_id){
        info_window.setContent('');
        var nonce = jQuery('#trmi_embedded_map_nonce').val();
        jQuery.ajax({
          url:'<?php echo admin_url('admin-ajax.php');?>',
          method:'POST',
          data:{
            action:'trmi_get_post_summary',
            post_id:post_id,
            nonce: nonce
          }
        })
        .success(function(response){
          if(response.success == true){
            var text = response.data.html;
            if(jQuery('#map_data *[data-post_id="'+post_id+'"]').data('distance_text')){
              var dist_text = '<div class="distance">'+jQuery('#map_data *[data-post_id="'+post_id+'"]').data('distance_text')+' away.';
              text=text.replace('<div class=\"distance\">',dist_text);
            }
            info_window.setContent(text);
            info_window.open(map, markers[post_id]);
          }
        });
      }

      function clear_markers(){
        jQuery('.trmi_item').each(function(){
          marker_id = jQuery(this).data('post_id');
          if(markers[marker_id]){
            markers[marker_id].setMap(null);
          }
        });
        jQuery('#map_data').html('');
        markers=[];
      }

      function add_markers(marker_item){
        var long = parseFloat(jQuery(marker_item).data('long'));
        var lat = parseFloat(jQuery(marker_item).data('lat'));
        var link = jQuery(marker_item).data('link');
        var post_loc = {lat: lat, lng: long};
        var marker_id = jQuery(marker_item).data('post_id');
        markers[marker_id] = new google.maps.Marker({
          <?php echo apply_filters( 'trmi_marker_parameters', $marker_params );?>
        });
        markers[marker_id].addListener('click',function(){
          <?php echo apply_filters( 'trmi_marker_click_action','show_info_window(jQuery(marker_item).data("post_id"));');?>
        });
        <?php echo apply_filters( 'trmi_add_marker_js_add_actions', "/* Developers: Use the 'trmi_add_marker_js_add_actions' WP filter to add actions for creating markers */\r\n" );?>
        markers[marker_id].addListener('dragend',function(){
          update_coords(this);
        });
        bounds.extend(post_loc);
      }

      function update_coords(marker){
        var new_pos = marker.getPosition();
        jQuery('#_trmi_longitude').val(new_pos.lng);
        jQuery('#_trmi_latitude').val(new_pos.lat);
      }

      <?php echo apply_filters( 'trmi_general_mapping_js', "/* Developers: Use the 'trmi_general_mapping_js' WP filter to add general mapping actions */\r\n" ); ?>
    </script>
    <?php

  }

  public function render_list(){
    $list_html = '';
    $titles = $contents = array();
    $classes = apply_filters( 'trmi_list_item_classes', array('mapped_post_record', 'container') );
    foreach ($this->posts as $post) {
      $post_html='';
      $post_html.='<div id="post_record-'.$post.'" class="'.implode(' ', $classes).'" data-post_id="'.$post.'">';
      $titles[] =  get_the_title($post,'trs_mapit');
      $post_html.='<a href="'.get_permalink($post).'"><h4>'.apply_filters('trmi_mapped_post_title',__(get_the_title($post)),$post).'</h4></a><br/>';
      $post_html.='<div class="distance"></div>';
      $contents[]=get_the_content($post);
      $post_html.=apply_filters( 'trmi_mapped_post_content',__(get_the_excerpt($post),'trs_mapit'),$post);
      $post_html .="</div>";
      $list_html .=apply_filters( 'trmi_mapped_post_html', $post_html, $post );
    }
    return $list_html;
  }
}


 ?>
