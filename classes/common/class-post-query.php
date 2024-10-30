<?php

defined ('ABSPATH') or die('Your father was a hamster and your father smelt of elderberries!') ;


class TRMI_POST_QUERY {
    public $args;
    public $single;
    public $map_posts;
    public $total_lat;
    public $total_long;
    public $max_lat;
    public $min_lat;
    public $max_long;
    public $min_long;
    public $postnum;

    public function __construct($args,$single=false){
      $this->args = $args;
      $this->single = $single;
      $this->map_posts = new WP_Query($this->args);
    }

    public function render_map_data($center_lat=null, $center_long=null){
        $item_array=array();
        $new_post_order=array();
           if($this->map_posts->have_posts()){
             $this->postnum = $this->map_posts->post_count;
             $total_long = $total_lat = 0;
             $this->max_long = -180;
             $this->min_long = 180;
             $this->max_lat = -90;
             $this->min_lat = 90;
             $item_array=array();
             $new_post_order=array();
             $distance_data = apply_filters('trmi_get_post_distance_data',false,$this->map_posts, $center_lat, $center_long);
             $trmi_options = get_option('trmi_options');

           while($this->map_posts->have_posts()){
             $this->map_posts->the_post();
             $distance = $distance_text = null;
             if ($distance_data && null != $center_lat && null != $center_long){
               $distance = $distance_data[$this->map_posts->post->ID]['distance'];
               $distance_text = $distance_data[$this->map_posts->post->ID]['text'];
             }
             $new_post_order[] = $this->map_posts->post->ID; //for default return array
             $recorded_long = ( get_post_meta( $this->map_posts->post->ID,'_trmi_longitude',true) != null ) ? get_post_meta( $this->map_posts->post->ID, '_trmi_longitude', true ) : $trmi_options['trmi_default_longitude'];
             $long = apply_filters( 'trmi_post_long', $recorded_long, $this->map_posts->post->ID );
             $recorded_lat = (get_post_meta( $this->map_posts->post->ID, '_trmi_latitude',true) != null ) ? get_post_meta( $this->map_posts->post->ID, '_trmi_latitude', true ) : $trmi_options['trmi_default_latitude'];
             $lat = apply_filters( 'trmi_post_lat', $recorded_lat, $this->map_posts->post->ID );
             if ($long == '' || !$long || $long == null || $lat == '' || !$lat || $lat == null){
               $this->postnum -= 1;
               continue;
             }
             $this->total_long += $long;
             if($long > $this->max_long){
               $this->max_long = $long;
             }
             if($long < $this->min_long){
               $this->min_long = $long;
             }
             $this->total_lat += $lat;
             if($lat > $this->max_lat){
               $this->max_lat = $lat;
             }
             if($lat < $this->min_lat){
               $this->min_lat = $lat;
             }
             $permalink = get_permalink( $this->map_posts->post->ID );
             $data_elements = apply_filters( 'trmi_mapped_post_data_elements',' data-post_id ="'.$this->map_posts->post->ID.'" data-long="'.$long.'" data-lat="'.$lat.'" data-link="'.esc_attr( $permalink).'" data-distance="'.$distance.'" data-distance_text="'.$distance_text.'"',$this->map_posts->post->ID);
             $item_html='<div class="trmi_item '.$this->map_posts->post->post_type.' '.$this->map_posts->post->ID.'" id="'.$this->map_posts->post->post_type.'-'.$this->map_posts->post->ID.'"'.$data_elements.'></div>';
             if(null==$distance){
               $item_array[intval('0'.str_pad($this->map_posts->post->ID,5,"0",STR_PAD_LEFT)) ] = array( 'id'=>$this->map_posts->post->ID,'content'=>apply_filters( 'trmi_mapped_post_item_html', $item_html, $this->map_posts->post->ID) );
             }else{
               /*append postID to distance for items in the same location to prevent loss of data */
               $item_array[intval(($distance * 100).str_pad($this->map_posts->post->ID,5,"0",STR_PAD_LEFT))] = array('id'=>$this->map_posts->post->ID,'content'=>apply_filters( 'trmi_mapped_post_item_html', $item_html, $this->map_posts->post->ID));
             }
           }
           wp_reset_postdata();
           if(!empty($item_array)){
             ksort($item_array);
             $new_post_order= array();
             foreach($item_array as $item=>$data){
               echo $data['content'];
               $new_post_order[]=$data['id'];
             }
           }
         }else{
           $this->postnum = 1;
           $default_loc = explode(',',TRMI_MAP_OBJECT::get_default_location());
           $this->total_lat = $default_loc[1];
           $this->total_long = $default_loc[0];
           ?>
           <div style="text-align:center;"><?php __('Sorry! No matches this location or those that do are missing coordinates.', 'trs_mapit');?></div>
           <?php
         }

         return $new_post_order;
    }

}

 ?>
