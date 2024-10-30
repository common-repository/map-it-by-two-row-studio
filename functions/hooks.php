<?php

defined('ABSPATH') or die('Your mother was a hamster and your father smelt of elderberries!');

/* for action hooks as needed */

/*
  * this function loads my plugin translation files
  */
 function trmi_load_translation_files() {
  load_plugin_textdomain('trs_mapit', false, '/languages');
 }

 //add action to load my plugin files
 add_action('plugins_loaded', 'trmi_load_translation_files');
 ?>
