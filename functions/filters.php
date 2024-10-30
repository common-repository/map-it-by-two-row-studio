<?php

defined('ABSPATH') or die('Your mother was a hamster and your father smelt of elderberries!');


/**
*  Adjusts precision on distance queries to better match
*  coordinate magnitudes to radial distances
*
*  Sets precision to xxxx.yyyyy for coord comparisons
*
* @param array $meta_query
*
* @return array $meta_query with updated precision
*/
function trmi_reset_decimal_precision($meta_query){
  $meta_query['where'] = str_replace('DECIMAL', 'DECIMAL(9,5)', $meta_query['where']);
  return $meta_query;
}

add_filter('trmi_get_post_distance_data', 'trmi_default_distance_data',10,4);

/**
*  Default filter sets distance text and value for each post based
*  option settings for units.
*
* @param bool $dist_data false
* @param array $query - post query output
* @param float $center_lat - defined map center Latitude
* @param float $center_long - defined map center Longitude
*
* @return array $dist_data array('post_id'=>array('distance','text'))
*/

function trmi_default_distance_data($dist_data, $query, $center_lat, $center_long){
  if (null != $center_lat && null != $center_long){
    $dist_data = array();
    $options = get_option('trmi_options');
    $units = $options['trmi_units'];
    $units_text = '';
    switch($units){
      case 'km':
        $units_text = ' km';
        break;
      default:
        $units_text = ' mi.';
    }
    while ($query->have_posts()){
      $query->the_post();
      $lat2 = get_post_meta( $query->post->ID, $key = '_trmi_latitude', $single = true );
      $lng2 = get_post_meta( $query->post->ID, $key = '_trmi_longitude', $single = true );
      $distance_val = trmi_dist_from_coords($center_lat, $center_long, $lat2, $lng2);
      switch($units){
        case 'km':
          $distance_val = round($distance_val / 1000,2);
          break;
        default:
          $distance_val = round(trmi_meters_to_miles($distance_val),2);
      }
      $distance_text = $distance_val.$units_text;
      $dist_data[$query->post->ID]=array('distance'=>$distance_val, 'text'=>$distance_text);
    }
  }
  return $dist_data;
}

/**
* Add async or defer attributes to script enqueues
* @author Mike Kormendy - modified by Eric Groft
* @param  String  $tag     The original enqueued <script src="...> tag
* @param  String  $handle  The registered unique name of the script
* @return String  $tag     The modified <script async|defer src="...> tag
*/
// only on the front-end
if(!is_admin()) {
    function add_asyncdefer_attribute($tag, $handle) {
        // if the unique handle/name of the registered script has 'async' in it
        if (strpos($handle, 'async') !== false) {
            // return the tag with the async attribute
            $tag =  str_replace( '<script ', '<script async ', $tag );
        }
        // if the unique handle/name of the registered script has 'defer' in it
        // was 'else if' but modified to allow both asynch and defer tags
        if (strpos($handle, 'defer') !== false) {
            // return the tag with the defer attribute
            $tag =  str_replace( '<script ', '<script defer ', $tag );
        }
        // otherwise skip
            return $tag;
    }
    add_filter('script_loader_tag', 'add_asyncdefer_attribute', 10, 2);
}

 ?>
