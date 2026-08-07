<?php
/**
 * Theme functions for Cortext Website.
 *
 * @package Cortext_Website
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Configure theme supports and editor styles.
 */
function cortext_website_setup() {
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'custom-logo', array(
		'height'      => 256,
		'width'       => 256,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'title-tag' );
	add_editor_style( 'style.css' );
	register_nav_menus( array(
		'primary' => __( 'Primary navigation', 'cortext-website' ),
	) );

	register_block_pattern_category(
		'cortext',
		array( 'label' => __( 'Cortext', 'cortext-website' ) )
	);
}
add_action( 'after_setup_theme', 'cortext_website_setup' );

/**
 * Load the single, dependency-free stylesheet.
 */
function cortext_website_enqueue_styles() {
	$theme = wp_get_theme();
	wp_enqueue_style(
		'cortext-website',
		get_stylesheet_uri(),
		array(),
		$theme->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'cortext_website_enqueue_styles' );

/**
 * Keep public discussion disabled. The site does not expose a comment form.
 */
function cortext_website_disable_comments_support() {
	remove_post_type_support( 'post', 'comments' );
	remove_post_type_support( 'post', 'trackbacks' );
	remove_post_type_support( 'page', 'comments' );
	remove_post_type_support( 'page', 'trackbacks' );
}
add_action( 'init', 'cortext_website_disable_comments_support', 100 );
add_filter( 'comments_open', '__return_false', 20 );
add_filter( 'pings_open', '__return_false', 20 );
add_filter( 'feed_links_show_comments_feed', '__return_false' );

/**
 * Keep missing pages out of search indexes while allowing link discovery.
 *
 * @param array $robots Existing robots directives.
 * @return array
 */
function cortext_website_robots( $robots ) {
	if ( is_404() ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}

	return $robots;
}
add_filter( 'wp_robots', 'cortext_website_robots' );

/**
 * Use a compact title separator.
 *
 * @return string
 */
function cortext_website_document_title_separator() {
	return '|';
}
add_filter( 'document_title_separator', 'cortext_website_document_title_separator' );

/**
 * Return a useful description for the current public view.
 *
 * @return string
 */
function cortext_website_meta_description() {
	if ( is_404() ) {
		return 'The requested page could not be found on Cortext.';
	}

	if ( is_front_page() ) {
		return 'An open-source knowledge workspace with nested pages, typed collections, relations and rollups. Built on WordPress.';
	}

	if ( is_home() ) {
		return 'Notes from Cortext: product updates, release details, and ideas for building a knowledge base you own.';
	}

	if ( is_singular() ) {
		$description = get_the_excerpt();
		if ( ! $description ) {
			$description = wp_trim_words( wp_strip_all_tags( get_the_content() ), 28 );
		}
		return $description;
	}

	if ( is_search() ) {
		return sprintf( 'Search results for “%s” on Cortext.', get_search_query() );
	}

	if ( is_archive() ) {
		$description = wp_strip_all_tags( get_the_archive_description() );
		return $description ? $description : 'Browse the Cortext archive.';
	}

	return get_bloginfo( 'description' );
}

/**
 * Return the canonical URL for the current view.
 *
 * @return string
 */
function cortext_website_canonical_url() {
	if ( is_404() ) {
		return '';
	}

	if ( is_singular() ) {
		return wp_get_canonical_url();
	}

	if ( is_front_page() ) {
		return home_url( '/' );
	}

	if ( is_home() ) {
		$page_for_posts = (int) get_option( 'page_for_posts' );
		return $page_for_posts ? get_permalink( $page_for_posts ) : home_url( '/blog/' );
	}

	if ( is_search() ) {
		return get_search_link();
	}

	return get_pagenum_link( max( 1, get_query_var( 'paged' ) ) );
}

/**
 * Print the small set of metadata the site needs without adding a plugin.
 */
function cortext_website_head_metadata() {
	if ( is_admin() || is_feed() ) {
		return;
	}

	$description = cortext_website_meta_description();
	$canonical   = cortext_website_canonical_url();
	$title       = wp_get_document_title();
	$type        = is_single() ? 'article' : 'website';
	$has_thumbnail = is_singular() && has_post_thumbnail();
	$image         = $has_thumbnail
		? get_the_post_thumbnail_url( null, 'full' )
		: get_theme_file_uri( 'assets/images/banner.jpg' );

	if ( $canonical ) {
		printf( "\n<link rel=\"canonical\" href=\"%s\" />\n", esc_url( $canonical ) );
	}

	printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $description ) );
	printf( '<meta property="og:site_name" content="%s" />' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
	printf( '<meta property="og:type" content="%s" />' . "\n", esc_attr( $type ) );
	printf( '<meta property="og:title" content="%s" />' . "\n", esc_attr( $title ) );
	printf( '<meta property="og:description" content="%s" />' . "\n", esc_attr( $description ) );
	if ( $canonical ) {
		printf( '<meta property="og:url" content="%s" />' . "\n", esc_url( $canonical ) );
	}
	printf( '<meta property="og:image" content="%s" />' . "\n", esc_url( $image ) );

	// The share card ships at a fixed size. Declaring it saves scrapers a fetch
	// and stops them guessing a crop.
	if ( ! $has_thumbnail ) {
		echo '<meta property="og:image:width" content="1200" />' . "\n";
		echo '<meta property="og:image:height" content="630" />' . "\n";
		printf( '<meta property="og:image:alt" content="%s" />' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
	}

	echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
}
remove_action( 'wp_head', 'rel_canonical' );
add_action( 'wp_head', 'cortext_website_head_metadata', 2 );

// Jetpack ships its own Open Graph and SEO tags, which would duplicate every tag
// above and leave scrapers to pick between two different titles and images.
add_filter( 'jetpack_enable_open_graph', '__return_false' );
add_filter( 'jetpack_seo_meta_tags_enabled', '__return_false' );
