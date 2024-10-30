<?php

defined('ABSPATH') or die ('Your mother was a hamster!');

/**
* Earth's major radius in meters = 6378137
* Mean earth radius in meters = 6371009
* Earth's minor radius in meters = 6356752.31420
* Source: Wikipedia (https://en.wikipedia.org/wiki/Earth_radius)
*/

define('MAJ_EARTH_RADIUS', 6378137);
define('MIN_EARTH_RADIUS', 6356752.31420);

/**
*   Calculate the distance in meters bewtween two
*   lat / lng Coordinates
*
*   Function smooths resutls for elliptical shape of earth
*   for latitude differences and narrowing effect on Longitude
*   distances, but not for combined effect of narrowing and
*   elliptical shaping on longitudinal distances.
*
* @param float $lat1 - starting latitude in degrees
* @param float $lng1 - starting longitude in degrees
* @param float $lat2 - ending latitude in degrees
* @param float $lng2 - ending longitude in degrees
*
* @return float $d_m - distance in meters bwteen points in direct line
*
*/

function trmi_dist_from_coords($lat1, $lng1, $lat2, $lng2){
  $lat1_rad = deg2rad(floatval($lat1));
  $lng1_rad = deg2rad(floatval($lng1));
  $lat2_rad = deg2rad(floatval($lat2));
  $lng2_rad = deg2rad(floatval($lng2));
  $dlng_rad = $lng2_rad - $lng1_rad;
  $dlat_rad = $lat2_rad - $lat1_rad;

  $dlat_m = (2 * pi() * trmi_core_to_surface_radius_at_lat($lat1_rad))*(abs((floatval($lat1) - floatval($lat2))) / 360);
  $dlng_m = (2 * pi() * trmi_slice_radius_at_lat($lat1_rad))*(abs((floatval($lng1) - floatval($lng2))) / 360);

  $d_m = sqrt(pow($dlat_m,2) + pow($dlng_m,2));
  return $d_m;
}

/**
*   Calculate the distance miles given distance in meters
*
*
* @param float $meters
*
* @return float $miles - distance in meters
*
*/

function trmi_meters_to_miles ($meters){
  if(!is_numeric($meters)){
    return 0;
  }
  return $meters * .00062137;
}

/**
*   Calculate the distance meters given distance in miles
*
*
* @param float $miles
*
* @return float $meters - distance in meters
*
*/

function trmi_miles_to_meters ($miles){
  if(!is_numeric($miles)){
    return 0;
  }
  return $miles / .00062137;
}

/**
*   Calculate the radius of earth from the center of
*   the planet in meters given latitude
*
*
* @param float $lat_rad - latitude angle in radians
*
* @return float $radius - radius in meters
*
*/

function trmi_core_to_surface_radius_at_lat($lat_rad){
  $radius = sqrt((pow((pow(MAJ_EARTH_RADIUS , 2) * cos($lat_rad)),2) + pow((pow(MIN_EARTH_RADIUS , 2) * sin($lat_rad)), 2)) / (pow((MAJ_EARTH_RADIUS * cos($lat_rad)),2)+(pow((MIN_EARTH_RADIUS * sin($lat_rad)), 2))));
  return $radius;
}

/**
*   Calculate the radius of the slice of the earth at a given
*   latitude
*
*
* @param float $lat_rad - latitude angle in radians
*
* @return float $radius - radius in meters
*
*/

function trmi_slice_radius_at_lat($lat_rad){
  $radius = MAJ_EARTH_RADIUS * cos($lat_rad);
  return $radius;
}

function trmi_filter_validate_coordinates($position_coords){
  if (!is_array($position_coords)){
    if(strpos($position_coords,',')){
      $position_coords = explode(',',$position_coords);
    }
    if(count($position_coords) != 2){
      return false;
    }
  }
  $position_coords[0] = floatval($position_coords[0]);
  $position_coords[1] = floatval($position_coords[1]);
  if(!is_numeric($position_coords[0]) || !is_numeric($position_coords[1] )){
    return false;
  }elseif($position_coords[0] < -180 || $position_coords[0] > 180 || $position_coords[1] < -90 || $position_coords[1] > 90){
    return false;
  }
  return $position_coords;
}

 ?>
