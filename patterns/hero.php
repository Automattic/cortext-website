<?php
/**
 * Title: Cortext product hero
 * Slug: cortext-website/hero
 * Categories: cortext, featured
 * Description: The primary Cortext product message and calls to action.
 */
?>
<!-- wp:group {"align":"full","className":"cortext-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull cortext-hero">
	<!-- wp:group {"align":"wide","className":"cortext-hero__content","layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide cortext-hero__content">
		<!-- wp:image {"width":"74px","sizeSlug":"full","linkDestination":"none","className":"cortext-hero__mark"} -->
		<figure class="wp-block-image size-full is-resized cortext-hero__mark"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/icon-dark.png' ) ); ?>" alt="" style="width:74px" /></figure>
		<!-- /wp:image -->
		<!-- wp:paragraph {"className":"cortext-hero__kicker"} -->
		<p class="cortext-hero__kicker">An open-source knowledge workspace</p>
		<!-- /wp:paragraph -->
		<!-- wp:heading {"level":1} -->
		<h1 class="wp-block-heading">Your knowledge should outlive the tool.</h1>
		<!-- /wp:heading -->
		<!-- wp:paragraph {"className":"cortext-hero__subtitle"} -->
		<p class="cortext-hero__subtitle">Write it, shape it, publish it, own it. Nested pages, typed collections, relations and rollups. Built on WordPress, so nothing you write is locked to one app.</p>
		<!-- /wp:paragraph -->
		<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
		<div class="wp-block-buttons">
			<!-- wp:button -->
			<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#product">See the workspace</a></div>
			<!-- /wp:button -->
			<!-- wp:button {"className":"is-style-outline"} -->
			<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/Automattic/cortext/main/assets/wordpress-org/blueprints/blueprint.json" target="_blank" rel="noreferrer noopener">Try the demo</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
		<!-- wp:paragraph {"className":"hero-status"} -->
		<p class="hero-status">Runs as a Mac app, on your own WordPress site, or in your browser.</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
