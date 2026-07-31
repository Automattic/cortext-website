<?php
/**
 * Title: Cortext ownership panel
 * Slug: cortext-website/ownership
 * Categories: cortext, featured
 * Description: Open-source and data portability message.
 */
?>
<!-- wp:columns {"verticalAlignment":"center","align":"wide","className":"ownership-panel"} -->
<div class="wp-block-columns are-vertically-aligned-center alignwide ownership-panel">
	<!-- wp:column {"verticalAlignment":"center","width":"30%"} -->
	<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:30%">
		<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"ownership-panel__mark"} -->
		<figure class="wp-block-image size-full ownership-panel__mark"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/icon-dark.png' ) ); ?>" alt="Cortext icon" /></figure>
		<!-- /wp:image -->
	</div>
	<!-- /wp:column -->
	<!-- wp:column {"verticalAlignment":"center","width":"70%"} -->
	<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:70%">
		<!-- wp:paragraph {"className":"eyebrow"} --><p class="eyebrow">Open by design</p><!-- /wp:paragraph -->
		<!-- wp:heading --><h2 class="wp-block-heading">Your knowledge stays yours.</h2><!-- /wp:heading -->
		<!-- wp:paragraph --><p>Cortext stores its content as WordPress posts and metadata. You can inspect the code, run it on infrastructure you control, and take your data with you.</p><!-- /wp:paragraph -->
		<!-- wp:paragraph --><p><a class="text-link" href="https://github.com/Automattic/cortext" target="_blank" rel="noreferrer noopener">Read the source on GitHub</a></p><!-- /wp:paragraph -->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->
