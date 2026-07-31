<?php
/**
 * Read-only validation for theme templates and bootstrapped content.
 *
 * Run with:
 * studio wp eval-file wp-content/themes/cortext-website/scripts/validate-content.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	wp_die( 'Run this validation with WP-CLI.' );
}

$cortext_validation_errors = array();

/**
 * Check parsed blocks recursively.
 *
 * @param array  $blocks  Parsed blocks.
 * @param string $context Human-readable source.
 * @param array  $errors  Collected errors.
 */
function cortext_validate_blocks( $blocks, $context, &$errors ) {
	$registry = WP_Block_Type_Registry::get_instance();

	foreach ( $blocks as $block ) {
		if ( $block['blockName'] && ! $registry->is_registered( $block['blockName'] ) ) {
			$errors[] = sprintf( '%s uses unregistered block %s.', $context, $block['blockName'] );
		}

		if ( ! $block['blockName'] && '' !== trim( (string) $block['innerHTML'] ) ) {
			$errors[] = sprintf( '%s contains unexpected freeform markup.', $context );
		}

		if ( ! empty( $block['innerBlocks'] ) ) {
			cortext_validate_blocks( $block['innerBlocks'], $context, $errors );
		}
	}
}

$required_templates = array( '404', 'archive', 'front-page', 'home', 'index', 'page', 'search', 'single' );
foreach ( $required_templates as $template ) {
	$path = get_theme_file_path( 'templates/' . $template . '.html' );
	if ( ! is_readable( $path ) ) {
		$cortext_validation_errors[] = sprintf( 'Missing template %s.', $template );
		continue;
	}

	$markup = file_get_contents( $path );
	cortext_validate_blocks( parse_blocks( $markup ), 'Template ' . $template, $cortext_validation_errors );
}

foreach ( array( 'header', 'footer' ) as $part ) {
	$path   = get_theme_file_path( 'parts/' . $part . '.html' );
	$markup = is_readable( $path ) ? file_get_contents( $path ) : '';
	if ( ! $markup ) {
		$cortext_validation_errors[] = sprintf( 'Missing template part %s.', $part );
		continue;
	}
	cortext_validate_blocks( parse_blocks( $markup ), 'Template part ' . $part, $cortext_validation_errors );
}

$posts = get_posts( array(
	'post_type'      => array( 'page', 'post' ),
	'post_status'    => 'publish',
	'posts_per_page' => -1,
) );

foreach ( $posts as $post ) {
	if ( preg_match( '/{{[A-Z0-9_]+}}/', $post->post_content ) ) {
		$cortext_validation_errors[] = sprintf( 'Unresolved token in “%s”.', $post->post_title );
	}

	$blocks = parse_blocks( $post->post_content );
	cortext_validate_blocks( $blocks, 'Post ' . $post->ID, $cortext_validation_errors );

	ob_start();
	do_blocks( $post->post_content );
	ob_end_clean();

	if ( 'closed' !== $post->comment_status || 'closed' !== $post->ping_status ) {
		$cortext_validation_errors[] = sprintf( 'Comments or pings are open on “%s”.', $post->post_title );
	}
}

$expected_options = array(
	'blogname'               => 'Cortext',
	'show_on_front'          => 'page',
	'permalink_structure'    => '/blog/%postname%/',
	'default_comment_status' => 'closed',
	'default_ping_status'    => 'closed',
);

foreach ( $expected_options as $option => $expected ) {
	if ( $expected !== get_option( $option ) ) {
		$cortext_validation_errors[] = sprintf( 'Unexpected value for option %s.', $option );
	}
}

if ( 'en_US' !== get_locale() ) {
	$cortext_validation_errors[] = 'The site locale is not en_US.';
}

if ( ! get_option( 'site_icon' ) ) {
	$cortext_validation_errors[] = 'The site icon is not configured.';
}

if ( $cortext_validation_errors ) {
	foreach ( $cortext_validation_errors as $error ) {
		WP_CLI::warning( $error );
	}
	WP_CLI::error( sprintf( 'Validation failed with %d error(s).', count( $cortext_validation_errors ) ) );
}

WP_CLI::success( sprintf( 'Validated %d published posts/pages and all required theme templates.', count( $posts ) ) );
