<?php
/**
 * The template for displaying the options page.
 *
 * @package WordPress
 * @subpackage Epitome SEO
 */
?>
	<div class="wrap">
		<?php screen_icon(); ?>
		<h2><?php _e("SEO settings", $this->get_text_domain()); ?></h2>
		<?php echo $seoco_output_tab; ?>

		<form method="post" action="options.php">
			<?php
				settings_fields($this->settings['group-name']);
				do_settings_sections($this->settings['menu-slug']);
				submit_button();
			?>
		</form>
	</div>
