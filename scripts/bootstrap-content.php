<?php
/**
 * Idempotent local bootstrap for the initial Cortext website content.
 *
 * Run from the Studio site root after activating the theme:
 * studio wp eval-file wp-content/themes/cortext-website/scripts/bootstrap-content.php
 *
 * This script is for the initial Studio-to-WordPress.com sync. After launch,
 * WordPress.com is the editorial source of truth.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	wp_die( 'Run this bootstrap with WP-CLI.' );
}

const CORTEXT_BOOTSTRAP_VERSION = '1.0.0';

/**
 * Read a versioned content file.
 *
 * @param string $relative_path Path relative to the theme.
 * @return string
 */
function cortext_bootstrap_read( $relative_path ) {
	$path = get_theme_file_path( $relative_path );
	if ( ! is_readable( $path ) ) {
		WP_CLI::error( sprintf( 'Missing bootstrap file: %s', $path ) );
	}

	$content = file_get_contents( $path );
	if ( false === $content ) {
		WP_CLI::error( sprintf( 'Could not read bootstrap file: %s', $path ) );
	}

	return trim( $content );
}

/**
 * Render a PHP pattern to static block markup for editable page content.
 *
 * @param string $relative_path Path relative to the theme.
 * @return string
 */
function cortext_bootstrap_pattern( $relative_path ) {
	$path = get_theme_file_path( $relative_path );
	if ( ! is_readable( $path ) ) {
		WP_CLI::error( sprintf( 'Missing pattern: %s', $path ) );
	}

	ob_start();
	include $path;
	$content = ob_get_clean();

	// Root-relative asset URLs work in Studio, staging, and production without
	// storing an environment-specific hostname in editorial content.
	$content = str_replace( untrailingslashit( home_url() ), '', $content );

	return trim( $content );
}

/**
 * Import a bundled image once and return its attachment ID.
 *
 * @param string $relative_path Theme-relative file path.
 * @param string $title         Media title.
 * @param string $alt           Alternative text.
 * @return int
 */
function cortext_bootstrap_media( $relative_path, $title, $alt ) {
	$existing = get_posts( array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_key'       => '_cortext_source_asset',
		'meta_value'     => $relative_path,
	) );

	if ( $existing ) {
		$attachment_id = (int) $existing[0];
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
		return $attachment_id;
	}

	$source = get_theme_file_path( $relative_path );
	if ( ! is_readable( $source ) ) {
		WP_CLI::error( sprintf( 'Missing media asset: %s', $source ) );
	}

	$contents = file_get_contents( $source );
	if ( false === $contents ) {
		WP_CLI::error( sprintf( 'Could not read media asset: %s', $source ) );
	}

	$upload = wp_upload_bits( wp_basename( $source ), null, $contents );
	if ( ! empty( $upload['error'] ) ) {
		WP_CLI::error( sprintf( 'Could not import %s: %s', $relative_path, $upload['error'] ) );
	}

	$filetype      = wp_check_filetype( $upload['file'] );
	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => $filetype['type'],
			'post_title'     => $title,
			'post_content'   => '',
			'post_status'    => 'inherit',
		),
		$upload['file']
	);

	if ( is_wp_error( $attachment_id ) ) {
		WP_CLI::error( $attachment_id->get_error_message() );
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	$metadata = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
	wp_update_attachment_metadata( $attachment_id, $metadata );
	update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
	update_post_meta( $attachment_id, '_cortext_source_asset', $relative_path );

	WP_CLI::log( sprintf( 'Imported media: %s', $relative_path ) );
	return (int) $attachment_id;
}

/**
 * Create or safely update bootstrap-managed content.
 *
 * If an editor has changed the content since the previous run, the script
 * preserves that edit and only enforces closed comments and pings.
 *
 * @param array $postarr Post fields, including post_name and post_type.
 * @return int
 */
function cortext_bootstrap_post( $postarr ) {
	$existing = get_page_by_path( $postarr['post_name'], OBJECT, $postarr['post_type'] );
	$updated  = true;

	if ( $existing ) {
		$post_id = (int) $existing->ID;
		$managed = (bool) get_post_meta( $post_id, '_cortext_bootstrap_managed', true );

		if ( ! $managed ) {
			WP_CLI::warning( sprintf( 'Preserved existing, unmanaged content at /%s/.', $postarr['post_name'] ) );
			return $post_id;
		}

		$stored_hash  = (string) get_post_meta( $post_id, '_cortext_bootstrap_content_hash', true );
		$current_hash = hash( 'sha256', (string) $existing->post_content );

		if ( $stored_hash && ! hash_equals( $stored_hash, $current_hash ) ) {
			$updated = false;
			$result  = wp_update_post( array(
				'ID'             => $post_id,
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			), true );
			WP_CLI::warning( sprintf( 'Preserved editorial changes to “%s”.', $existing->post_title ) );
		} else {
			$postarr['ID'] = $post_id;
			$result        = wp_update_post( $postarr, true );
		}
	} else {
		$result = wp_insert_post( $postarr, true );
	}

	if ( is_wp_error( $result ) ) {
		WP_CLI::error( $result->get_error_message() );
	}

	$post_id = (int) $result;
	update_post_meta( $post_id, '_cortext_bootstrap_managed', CORTEXT_BOOTSTRAP_VERSION );

	if ( $updated ) {
		update_post_meta( $post_id, '_cortext_bootstrap_content_hash', hash( 'sha256', (string) $postarr['post_content'] ) );
	}

	WP_CLI::log( sprintf( '%s: %s', $existing ? 'Updated' : 'Created', $postarr['post_title'] ) );
	return $post_id;
}

/**
 * Create the primary menu and assign it to the registered theme location.
 *
 * The header also contains the same links as a file-based fallback, so a fresh
 * theme install remains usable before this bootstrap runs.
 *
 * @param int $blog_page_id Blog page ID.
 */
function cortext_bootstrap_navigation( $blog_page_id ) {
	$menu = wp_get_nav_menu_object( 'Cortext Primary' );
	if ( ! $menu ) {
		$menu_id = wp_create_nav_menu( 'Cortext Primary' );
		if ( is_wp_error( $menu_id ) ) {
			WP_CLI::error( $menu_id->get_error_message() );
		}
		$menu = wp_get_nav_menu_object( $menu_id );
	}

	$menu_id = (int) $menu->term_id;
	update_term_meta( $menu_id, '_cortext_bootstrap_managed', CORTEXT_BOOTSTRAP_VERSION );

	$items_by_key = array();
	foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $item ) {
		$key = get_post_meta( $item->ID, '_cortext_menu_key', true );
		if ( $key ) {
			$items_by_key[ $key ] = (int) $item->ID;
		}
	}

	$items = array(
		'product' => array(
			'title'   => 'Product',
			'url'     => '/#product',
			'target'  => '',
			'classes' => '',
			'xfn'     => '',
		),
		'blog' => array(
			'title'   => 'Blog',
			'url'     => '/blog/',
			'target'  => '',
			'classes' => '',
			'xfn'     => '',
		),
		'github' => array(
			'title'   => 'GitHub',
			'url'     => 'https://github.com/Automattic/cortext',
			'target'  => '_blank',
			'classes' => '',
			'xfn'     => 'noopener noreferrer',
		),
		'playground' => array(
			'title'   => 'Try the demo',
			'url'     => 'https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/Automattic/cortext/main/assets/wordpress-org/blueprints/blueprint.json',
			'target'  => '_blank',
			'classes' => 'nav-cta',
			'xfn'     => 'noopener noreferrer',
		),
	);

	$position = 1;
	foreach ( $items as $key => $item ) {
		$item_id = wp_update_nav_menu_item(
			$menu_id,
			isset( $items_by_key[ $key ] ) ? $items_by_key[ $key ] : 0,
			array(
				'menu-item-title'    => $item['title'],
				'menu-item-url'      => $item['url'],
				'menu-item-status'   => 'publish',
				'menu-item-type'     => 'custom',
				'menu-item-position' => $position,
				'menu-item-target'   => $item['target'],
				'menu-item-classes'  => $item['classes'],
				'menu-item-xfn'      => $item['xfn'],
			)
		);

		if ( is_wp_error( $item_id ) ) {
			WP_CLI::error( $item_id->get_error_message() );
		}

		update_post_meta( $item_id, '_cortext_menu_key', $key );
		++$position;
	}

	$locations            = get_theme_mod( 'nav_menu_locations', array() );
	$locations['primary'] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
	WP_CLI::log( 'Updated primary navigation.' );
}

/**
 * Move untouched WordPress sample content to the trash.
 */
function cortext_bootstrap_remove_samples() {
	$about = get_page_by_path( 'about', OBJECT, 'page' );
	if (
		$about &&
		'About' === $about->post_title &&
		preg_match( '/example(?: of a)? page/i', wp_strip_all_tags( $about->post_content ) )
	) {
		wp_trash_post( $about->ID );
		WP_CLI::log( 'Moved the untouched sample About page to the trash.' );
	}

	$hello = get_page_by_path( 'hello-world', OBJECT, 'post' );
	if ( $hello && 'Hello world!' === $hello->post_title ) {
		wp_trash_post( $hello->ID );
		WP_CLI::log( 'Moved the untouched sample post to the trash.' );
	}
}

$theme = wp_get_theme();
if ( 'cortext-website' !== $theme->get_stylesheet() ) {
	WP_CLI::error( 'Activate the cortext-website theme before running the bootstrap.' );
}

$admins    = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
$author_id = $admins ? (int) $admins[0] : 1;

$icon_id      = cortext_bootstrap_media( 'assets/images/icon-dark.png', 'Cortext icon', 'Cortext' );
$workspace_id = cortext_bootstrap_media( 'assets/images/workspace.jpg', 'Cortext workspace', 'A Cortext workspace showing nested documents and a product collection as a table' );
$banner_id    = cortext_bootstrap_media( 'assets/images/banner.png', 'Cortext banner', 'Cortext — Your Digital Brain. Your Rules. Powered by WordPress.' );

$home_content = cortext_bootstrap_read( 'content/home.html' );
$home_content = strtr(
	$home_content,
	array(
		'{{PATTERN_HERO}}'         => cortext_bootstrap_pattern( 'patterns/hero.php' ),
		'{{PATTERN_FEATURE_GRID}}' => cortext_bootstrap_pattern( 'patterns/feature-grid.php' ),
		'{{PATTERN_OWNERSHIP}}'    => cortext_bootstrap_pattern( 'patterns/ownership.php' ),
		'{{PATTERN_FINAL_CTA}}'    => cortext_bootstrap_pattern( 'patterns/final-cta.php' ),
		'{{WORKSPACE_IMAGE_ID}}'   => (string) $workspace_id,
		'{{WORKSPACE_IMAGE_URL}}'  => wp_make_link_relative( wp_get_attachment_url( $workspace_id ) ),
	)
);

if ( preg_match( '/{{[A-Z0-9_]+}}/', $home_content, $unresolved ) ) {
	WP_CLI::error( sprintf( 'Unresolved content token: %s', $unresolved[0] ) );
}

$common = array(
	'post_author'     => $author_id,
	'post_status'     => 'publish',
	'comment_status' => 'closed',
	'ping_status'    => 'closed',
);

$home_id = cortext_bootstrap_post( array_merge( $common, array(
	'post_type'    => 'page',
	'post_name'    => 'home',
	'post_title'   => 'Cortext',
	'post_excerpt' => 'An open-source workspace for documents, structured collections, connected records, and flexible views.',
	'post_content' => $home_content,
) ) );

$blog_id = cortext_bootstrap_post( array_merge( $common, array(
	'post_type'    => 'page',
	'post_name'    => 'blog',
	'post_title'   => 'Notes from Cortext',
	'post_excerpt' => 'Product updates, release details, and ideas for building a knowledge base you own.',
	'post_content' => '',
) ) );

$privacy_id = cortext_bootstrap_post( array_merge( $common, array(
	'post_type'    => 'page',
	'post_name'    => 'privacy',
	'post_title'   => 'Privacy',
	'post_excerpt' => 'How cortext.digital and its WordPress.com hosting handle visitor information.',
	'post_content' => cortext_bootstrap_read( 'content/privacy.html' ),
) ) );

$post_id = cortext_bootstrap_post( array_merge( $common, array(
	'post_type'     => 'post',
	'post_name'     => 'introducing-cortext-0-2',
	'post_title'    => 'Introducing Cortext 0.2',
	'post_excerpt'  => 'Mentions, backlinks, formula fields, faster workspaces, and a sturdier desktop app make Cortext 0.2 a much more connected beta.',
	'post_content'  => cortext_bootstrap_read( 'content/introducing-cortext-0-2.html' ),
	'post_date'     => '2026-07-31 17:00:00',
	'post_date_gmt' => '2026-07-31 15:00:00',
) ) );

set_post_thumbnail( $post_id, $banner_id );
set_theme_mod( 'custom_logo', $icon_id );

update_option( 'blogname', 'Cortext' );
update_option( 'blogdescription', 'Own and connect your knowledge.' );
update_option( 'WPLANG', '' );
update_option( 'timezone_string', 'Europe/Madrid' );
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $home_id );
update_option( 'page_for_posts', $blog_id );
update_option( 'permalink_structure', '/blog/%postname%/' );
update_option( 'default_comment_status', 'closed' );
update_option( 'default_ping_status', 'closed' );
update_option( 'default_pingback_flag', 0 );
update_option( 'posts_per_page', 10 );
update_option( 'site_icon', $icon_id );

cortext_bootstrap_navigation( $blog_id );
cortext_bootstrap_remove_samples();
flush_rewrite_rules();

WP_CLI::log( '' );
WP_CLI::log( 'Public routes:' );
WP_CLI::log( home_url( '/' ) );
WP_CLI::log( get_permalink( $blog_id ) );
WP_CLI::log( get_permalink( $post_id ) );
WP_CLI::log( get_permalink( $privacy_id ) );
WP_CLI::log( get_feed_link() );
WP_CLI::success( sprintf( 'Cortext website bootstrap %s complete.', CORTEXT_BOOTSTRAP_VERSION ) );
