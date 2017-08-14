<?php
/*
Plugin Name: PS WP Multi Domain
Plugin URI: http://www.web-strategy.jp/plugins/ps_wp_multi_domain/
Description: This plugin rewrite "wp-config.php" to enable access multiple domain.
Version: 0.8.0
Author: Yuji
Author URI: http://www.web-strategy.jp/
*/
/*  Copyright 2009  Prime Strategy Co.,Ltd.  (email : staff@prime-strategy.co.jp)
*/

class ps_wp_multi_domain {

function __construct() {
	$this->set_wpconfig();
	if ( !is_writable($this->wpconfig) ) {
		add_action('admin_notices', array( &$this, 'force_deactivate' ) );
	}
	register_activation_hook(__FILE__, array( &$this, 'wp_multi_domain_activate' ) );
	register_deactivation_hook(__FILE__, array( &$this, 'wp_multi_domain_deactivate' ) );
	load_plugin_textdomain( 'ps-wp-multidomain', 'wp-content/plugins/ps-wp-multidomain' );
}

function ps_wp_multi_domain() {
	$this->__construct();
}

function set_wpconfig(){
	if ( file_exists( ABSPATH . 'wp-config.php') ) {
	/** The config file resides in ABSPATH */
	$this->wpconfig= ABSPATH . 'wp-config.php';
	} elseif ( file_exists( dirname(ABSPATH) . '/wp-config.php' ) && ! file_exists( dirname(ABSPATH) . '/wp-load.php' ) ) {
	/** The config file resides one level below ABSPATH */
	$this->$wpconfig= dirname(ABSPATH) . '/wp-config.php' ;
	}else{
	$this->$wpconfig= ABSPATH . '/wp-config.php';
	}
}


function force_deactivate() {
	echo "
	<div class='updated fade dashboard-widget-error'><p><strong>".__('WP multi-domain can not start.',"ps-wp-multidomain")."</strong></p></div>
	<div class='dashboard-widget-error'><p><strong>".__('WP multi-domain has stopped!! ',"ps-wp-multidomain")."</strong>".__('You must make "wp-config.php" rewriteble to work.',"ps-wp-multidomain")."</p></div>";
	$active_plugins = get_option('active_plugins');
	$search_plugin = str_replace( str_replace( '\\', '/', ABSPATH . PLUGINDIR . '/' ), '', str_replace( '\\', '/', __file__ ) );
	$key = array_search( $search_plugin, $active_plugins );
	if ( $key !== false ) {
		unset( $active_plugins[$key] );
	}
	update_option( 'active_plugins', $active_plugins );
	return;
}

function my_file_put_contents($file_name , $content){
	$fop = fopen($file_name, "w");
	fwrite($fop, $content );
	fclose($fop);
}


function wp_multi_domain_activate() {
	$this->set_wpconfig();
	if ( !is_writable($this->wpconfig) ) {
	}else{
		$homeurl = get_option('home');
		$siteurl = get_option('siteurl');
		
		// Judge https or not.
		if(preg_match("/^https.+/", "$homeurl") ){
			$homessl = 's';
		}else{
			$homessl = '';
		}
		if(preg_match("/^https.+/", "$siteurl") ){
			$sitessl = 's';
		}else{
			$sitessl = '';
		}
		
		//Judge root or directory.
		if(preg_match("/https*:\/\/[^\/]+\/+(.+)$/", "$homeurl")){
			$trimhome = preg_replace('/http:\/\/[^\/]+\/+(.+)\/*$/', '$1', $homeurl );
			$trimhome = ".'/"."$trimhome"."'";
		}else{
			$trimhome="";	
		}
		if(preg_match("/https*:\/\/[^\/]+\/+(.+)$/", "$siteurl")){
			$trimsite = preg_replace('/http:\/\/[^\/]+\/+(.+)\/*$/', '$1', $siteurl );
			$trimsite = ".'/"."$trimsite"."'";
		}else{
			$trimsite="";	
		}

	$firstpattern = '/<\?php/';
	$replacement = "<?php\n\n//wp_multi_domain_start\ndefine( 'WP_SITEURL','http".$sitessl."://'.\$_SERVER['SERVER_NAME']".$trimsite." );\ndefine( 'WP_HOME','http".$homessl."://'.\$_SERVER['SERVER_NAME']".$trimhome." );\n//wp_multi_domain_end\n";
	$filecontents = file_get_contents($this->wpconfig);
	$filecontents = preg_replace($firstpattern, $replacement, $filecontents, 1);
	$this->my_file_put_contents($this->wpconfig, $filecontents);
	}
}

function wp_multi_domain_deactivate() {
	$this->set_wpconfig();
	$newpattern = '/\n*\/\/wp_multi_domain_start[^@]*wp_multi_domain_end\n/';
	$replacement = "";
	$filecontents = file_get_contents($this->wpconfig);
	$filecontents = preg_replace($newpattern, $replacement, $filecontents, 1);
	$this->my_file_put_contents($this->wpconfig, $filecontents);
}

} // class end

$ps_wp_multi_domain =& new ps_wp_multi_domain();
