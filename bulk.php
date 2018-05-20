<div class="wrap"> 
	<div id="icon-upload" class="icon32"><br /></div>
	<h2><?php printf( __('Bulk %s', CW_IMAGE_OPTIMIZER_DOMAIN ), $strPluginName ); ?></h2>

<?php 

if ( sizeof($attachments) < 1 ) { ?>
	
	<p><?php  _e(' You don’t appear to have uploaded any images yet.', CW_IMAGE_OPTIMIZER_DOMAIN ); ?></p>

<?php 
} else {
	
	if( empty( $_POST ) ) { // instructions page
?>
	<p><?php _e(' This tool will run all of the images in your media library through the Linux image optimization programs.', CW_IMAGE_OPTIMIZER_DOMAIN ); ?></p>

	<p><?php printf( __('We found %d images in your media library.', CW_IMAGE_OPTIMIZER_DOMAIN ), sizeof( $attachments ) ); ?></p>

	<form method="post" action="">
		<?php wp_nonce_field( 'cw-image-optimizer-bulk', '_cw-image-optimizer-bulk-nonce'); ?>
		<button type="submit" class="button-secondary action">Run all my images through image optimizers right now</button>
	</form>
  
<?php
	} else { // run the script
  
		if (!wp_verify_nonce( $_POST['_cw-image-optimizer-bulk-nonce'], 'cw-image-optimizer-bulk' ) || !current_user_can( 'edit_others_posts' ) ) {
			wp_die( __( 'Cheatin&#8217; uh?' ) );
		}

		ob_implicit_flush(true);
		ob_end_flush();

		foreach( $attachments as $attachment ) {
			echo '<p>' . sprintf( __('Processing <strong>%s</strong>&hellip;', CW_IMAGE_OPTIMIZER_DOMAIN ), esc_html($attachment->post_name) ) .  '<br />';

			$meta = cw_image_optimizer_resize_from_meta_data( wp_get_attachment_metadata( $attachment->ID, true ), $attachment->ID );

			if(isset($meta['sizes']) && is_array($meta['sizes'])) {
				foreach( $meta['sizes'] as $size ) {
					printf( '&mdash; %s<br />', $size['cw_image_optimizer'] );
				}
			}

			echo '</p>';

			wp_update_attachment_metadata( $attachment->ID, $meta );

			@ob_flush();
			flush();
		}
	}
}
?>
</div>
