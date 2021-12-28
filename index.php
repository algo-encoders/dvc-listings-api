<?php 
/*
	Plugin Name: DVC Listings API
	Plugin URI: https://kinsta.cloud
	Description: Plugin Use for DVC Listings API.
	Version: 2.0.3
	Author: Omar Ahmad
	Author URI: https://kinsta.cloud
	Text Domain: k-cloud
	Domain Path: /languages/
	License: GPL2
	
	Kinsta Cloud is software only for company use
*/ 


		
	global $woo_cs_dir, $woo_cs_url;

	$woo_cs_dir = plugin_dir_path( __FILE__ );
	$woo_cs_url = plugin_dir_url( __FILE__ );

	include('io/class.dvc-listings-api.php');

	if(!function_exists('pree')){
	    function pree($d){
	        echo '<pre>';
	        print_r($d);
	        echo '</pre>';
        }
    }

	if(class_exists('DVC_LISTINGS_API')){
		$woo_cs_android_settings = new DVC_LISTINGS_API();
	}

	
