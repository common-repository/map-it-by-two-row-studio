<?php

/**
* Setup and deinstallation functions
* ==================================
*
* None at this time, but set for future needs
*/

defined ('ABSPATH') or die('Your father was a hamster and your father smelt of elderberries!') ;

function trmi_install(){
  update_option( '_trmi_version', TRMI_VERSION );
    update_option( '_trmi_db_version', TRMI_DB_VERSION );
}

function trmi_deactivate(){
  delete_option('_trmi_version');
}

function trmi_uninstall(){
  delete_option('_trmi_db_version');
}
