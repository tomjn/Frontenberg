<?php

get_header();
?>
				<div class="block-editor">
					<h1 class="screen-reader-text hide-if-no-js">Edit Post</h1>
					<div id="editor" class="block-editor__container hide-if-no-js"></div>
					<div id="metaboxes" class="hidden">
						<?php /*
						this is commented out because it causes a fatal with do_metaboxes not being found
						the_block_editor_meta_boxes(); */ ?>
					</div>
					<div class="wrap hide-if-js block-editor-no-js">
						<h1 class="wp-heading-inline">Edit Post</h1>
						<div class="notice notice-error notice-alt">
							<p>The block editor requires JavaScript. Please enable JavaScript in your browser settings</p>
						</div>
					</div>
				</div>
<?php

get_footer();
