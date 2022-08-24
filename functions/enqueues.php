<?php

add_action( 'wp_enqueue_scripts', 'enqueue_scripts_doli_gdrf_public' );
function enqueue_scripts_doli_gdrf_public() {
	wp_register_script( 'gdrf-public-scripts', plugins_url( 'doliconnect/includes/js/gdrf-public.js'), array( 'jquery' ), '', false );
	$translations = array(
		'gdrf_ajax_url' => esc_url( admin_url( 'admin-ajax.php' ) ),
		'gdrf_success'  => __( 'Your enquiry have been submitted. Check your email to validate your data request.', 'doliconnect'),
		'gdrf_errors'   => __( 'Some errors occurred:', 'doliconnect'),
	);
	wp_localize_script( 'gdrf-public-scripts', 'gdrf_localize', $translations );
}

function doliconnect_enqueues() { 

/* Styles */
if ( empty(get_theme_mod( 'ptibogxivtheme_css')) || get_theme_mod( 'ptibogxivtheme_css') == 'css' ) {
$css='';
$versionbase = '5.2.0'; 
$version=$versionbase; 
} else {
$css='bootswatch/'.get_theme_mod( 'ptibogxivtheme_css').'/';
$version='5.2.0'; 
$versionbase=$version;
}

if (!empty(get_theme_mod( 'ptibogxivtheme_css')) && $version != $versionbase && empty(get_option('doliconnectbeta'))) {
$css='';
$version=$versionbase;
}

	wp_register_style( 'bootstrap.min.css', plugins_url( 'doliconnect/includes/bootstrap/css/'.$css.'bootstrap.min.css'), array(), $version);
	wp_enqueue_style( 'bootstrap.min.css');
	wp_register_script( 'bootstrap.bundle.min.js', plugins_url( 'doliconnect/includes/bootstrap/js/bootstrap.bundle.min.js'), array('jquery'), $version, true);
  	wp_enqueue_script( 'bootstrap.bundle.min.js');
	//wp_register_script( 'masonry.pkgd.min.js', 'https://cdn.jsdelivr.net/npm/masonry-layout@4.2.2/dist/masonry.pkgd.min.js', array(), '4.2.2', true);
  	//wp_enqueue_script( 'masonry.pkgd.min.js');
  if (empty(get_option('doliconnectfontawesome'))) {
  	wp_register_script( 'font-awesome', '//use.fontawesome.com/releases/v6.1.2/js/all.js', array(), '6.1.2' );
	wp_enqueue_script( 'font-awesome');
  }
  	wp_register_style( 'bootstrap-social', plugins_url( 'doliconnect/includes/bootstrap/css/bootstrap-social.css'), array(), $version);
	wp_enqueue_style( 'bootstrap-social');
  	wp_register_style( 'flag-icon-css', plugins_url( 'doliconnect/includes/flag-icon-css/css/flag-icons.css'), array(), '6.6.5'); 
	wp_enqueue_style( 'flag-icon-css');
}

?>