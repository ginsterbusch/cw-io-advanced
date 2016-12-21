<?php
/**
 * Integrate Linux image optimizers into WordPress.
 * @version 1.2
 * @package CW_Image_Optimizer
 */
/*
Plugin Name: CW Image Optimizer Advanced
Plugin URI: http://f2w.de/cw-io-advanced
Description: Reduce image file sizes and improve performance using Linux image optimizers within WordPress. Adapted version of CW Image Optimized, which is using ImageMagick as fallback option.
Author: Fabian Wolf
Version: 1.2
Author URI: http://usability-idealist.de
*/

/**
 * Constants
 */
define('CW_IMAGE_OPTIMIZER_DOMAIN', 'cw_image_optimizer');
define('CW_IMAGE_OPTIMIZER_PLUGIN_DIR', dirname(plugin_basename(__FILE__)));
define('_UI_IO_NAME', 'CW Image Optimizer Advanced');
/**
 * Hooks
 */
add_filter('wp_generate_attachment_metadata', 'cw_image_optimizer_resize_from_meta_data', 10, 2);


/**
 * Check if system requirements are met
 */
/*
if('Linux' != PHP_OS && 'Darwin' != PHP_OS) {
    add_action('admin_notices', 'cw_image_optimizer_notice_os');
    define('CW_IMAGE_OPTIMIZER_PNG', false);
    define('CW_IMAGE_OPTIMIZER_GIF', false);
    define('CW_IMAGE_OPTIMIZER_JPG', false);
}else{
    
//}   

function cw_image_optimizer_notice_os() {
    echo "<div id='cw-image-optimizer-warning-os' class='updated fade'><p><strong>CW Image Optimizer isn't supported on your server.</strong> Unfortunately, the CW Image Optimizer plugin doesn't work with " . htmlentities(PHP_OS) . ".</p></div>";
}   
*/

register_activation_hook( __FILE__, array( '_ui_io_base', 'plugin_install' ) );


class _ui_io_base {
	const pluginName = 'CW Image Optimizer Advanced';
	const pluginVersion = '1.2';
	const optionName = 'cw_io_settings';
	

	public static function plugin_install() {
		// check if there are already options in place
		$test_option = get_option( self::optionName, false );
		
		// if not, do the initial tool detection and settings creation
		if( empty( $test_option ) ) {
			$tools = self::detect_tools();
			
			$init_settings = self::get_default_settings();
			
			if( !empty( $tools['found'] ) ) {
				foreach( $tools['found'] as $strToolName => $strPath ) {
					$init_settings['tools'][$strToolName] = $strPath;
				}
			}
			
			update_option( self::optionName, $init_settings );
		}
	}

	public static function get_default_settings() {
		$defaults = array(
			'skip_check' => false,
			'tools' => array(
				'png' => false,
				'jpg' => false,
				'gif' => false,
				'fallback' => false,
			),
			'quality_jpg' => 75,
			'quality_png' => 4,
		);
		
		return $defaults;
	}

	public static function get_settings( $option_name = '', $default_value = null ) {
	
		$options = get_option( 'cw_io_settings', self::get_default_settings() );
		
		$return = $options;
		
		if( !empty( $option_name ) ) {
			$return = $default_value;
		
			if( isset( $options[ $option_name ] ) ) {
				$return = $options[ $option_name ];
			}
		}
		
		return $return;
	}
	
	public static function update_settings( $settings = false ) {
		$return = false;
		
		if( !empty( $settings ) ) {
			//new __debug( $settings, __METHOD__ );
			
			$return = update_option( 'cw_io_settings', $settings );
		}
		
		return $return;
	}
	
	public static function update_setting( $option_name = '', $value = '' ) {
		$return = false;
		
		$options = get_option( 'cw_io_settings', self::get_default_settings() );
		$update_options = $options;
		
		if( !empty( $option_name ) && isset( $options[ $option_name ] ) && $options[ $option_name ] != $value ) {
			$update_options[ $option_name ] = $value;
		}
		
		if( $update_options != $options ) {
			$return = update_option( 'cw_io_settings', $update_options );
		}
		
		return $return;
	}
	
	public static function detect_tools() {
		$return = array( 'missing' => false, 'found' => false );
		
		$missing = array();
		
		$required = array(
			'png' => 'opt-png',
			'jpg' => 'opt-jpg',
			'gif' => 'opt-gif',
			'fallback' => 'convert',
		);

		foreach($required as $key => $req) {
			$result = trim(exec('which ' . $req));
			
			if(!$skip && empty($result) ) {
				$missing[] = $req;
				
			} else {
				$found[$key] = trim( $result );
			}
		}

		if( !empty( $missing ) ) {
			$return['missing'] = $missing;
		}
		
		if( !empty( $found ) ) {
			$return['found'] = $found;
		}

		return $return;
	}
	/**
	 * Adapted from @link http://stackoverflow.com/a/12126772
	 */
	
	public static function is_valid_filepath( $path = '' ) {
		$return = false;
		
		if( !empty( $path ) ) {

			$path = trim($path);
			
			if(preg_match('/^[^*?"<>|:]*$/',$path)) {
				$return = true; // good to go
			} else {

				if( !defined('WINDOWS_SERVER') ) {
					$tmp = dirname(__FILE__);
					
					if( strpos($tmp, '/', 0 )!== false ) {
						define('WINDOWS_SERVER', false);
					} else {
						define('WINDOWS_SERVER', true);
					}
				}
				
				/*first, we need to check if the system is windows*/
				if( WINDOWS_SERVER ) {
					if(strpos($path, ":") == 1 && preg_match('/[a-zA-Z]/', $path[0])) { // check if it's something like C:\
						$tmp = substr($path,2);
						$bool = preg_match('/^[^*?"<>|:]*$/',$tmp);
						$return = ($bool == 1); // so that it will return only true and false
					}
					//return false;
				}
			}
		}
		
		return $return;
	}
}


add_action('init', array( '_ui_io_admin', 'init' ) );
add_action('admin_action_cw_image_optimizer_manual', 'cw_image_optimizer_manual' );

class _ui_io_admin extends _ui_io_base {
	public static function init() {
		new self();
	}

	public $strOptionName = 'cw_io_settings';
	
	function __construct() {
		
		add_action('admin_notices', array( $this, 'add_notice_littleutils') );
		
		
		add_filter('manage_media_columns', array( $this, 'add_media_columns' ) );
		add_action('manage_media_custom_column', array( $this, 'add_media_custom_column'), 10, 2  );
		
		add_action( 'admin_menu', array( $this, 'add_admin_pages' ) );
		
		add_action( 'admin_init', array( $this, 'admin_init' ) );
	}	
	
	
	

	function add_admin_pages() {
		add_media_page( 'Bulk Optimize', 'Bulk Optimize', 'edit_others_posts', 'cw-image-optimizer-bulk', array( $this, 'bulk_preview' ) );

		add_options_page(
			self::pluginName,           //Title
			self::pluginName,           //Sub-menu title
			'manage_options',               //Security
			__FILE__,                       //File to open
			array( $this, 'admin_page_options' )    //Function to call
		);

	}
	

	/**
	 * Plugin admin functions
	 */
	function admin_init() {
		//load_plugin_textdomain(CW_IMAGE_OPTIMIZER_DOMAIN);
		//wp_enqueue_script('common');
		//register_setting('cw_image_optimizer_options', 'cw_image_optimizer_skip_check');
		
		register_setting( 'cw_io_settings', 'cw_io_settings', array( $this, 'admin_validate_settings' ) );
		
		$strSectionID = 'cw_io_main';
		$strSectionPageSlug = 'cw_io_main_settings';
		
		add_settings_section( $strSectionID, self::pluginName . ' Settings', array( $this, 'admin_section_main' ), $strSectionPageSlug );
		add_settings_field( 'field-skip_check', 'Skip littleutils check', array( $this, 'admin_field_skip_check' ), $strSectionPageSlug, $strSectionID );
		add_settings_field( 'field-tools_jpg', 'JPEG optimizer path', array( $this, 'admin_field_tools_jpg' ), $strSectionPageSlug, $strSectionID );
		add_settings_field( 'field-tools_png', 'PNG optimizer path', array( $this, 'admin_field_tools_png' ), $strSectionPageSlug, $strSectionID );
		add_settings_field( 'field-tools_gif', 'GIF optimizer path', array( $this, 'admin_field_tools_gif' ), $strSectionPageSlug, $strSectionID );
		add_settings_field( 'field-tools_fallback', 'ImageMagick path', array( $this, 'admin_field_tools_fallback' ), $strSectionPageSlug, $strSectionID );
		
		
		//$strSectionID = 'cw_io_quality';
		//$strSectionPageSlug = 'cw_io_quality_settings';
		
		add_settings_field( 'field-quality_jpg', 'JPEG quality', array( $this, 'admin_field_quality_jpg' ), $strSectionPageSlug, $strSectionID );
		add_settings_field( 'field-quality_png', 'PNG quality', array( $this, 'admin_field_quality_png' ), $strSectionPageSlug, $strSectionID );
		
		
		//add_settings_section( $strSectionID, self::pluginName . ' Settings', array( $this, 'admin_section_main' ), $strSectionPageSlug );
	}
	
	function admin_section_main( $arg ) {
		//new __debug( $arg, __METHOD__ );
		?>
		<p><?php echo self::pluginName; ?> performs several checks to make sure your system is capable of optimizing images.</p>
		<p>In some cases, these checks may erroneously report that you are missing littleutils even though you have littleutils installed.</p>
		<?php
	}
	
	function admin_field_skip_check() {
		$skip_check = self::get_settings( 'skip_check' );
	
		?><input type="checkbox" id="field-skip_check" name="<?php echo $this->strOptionName . '[skip_check]'; ?>" value="1" <?php checked( !empty( $skip_check ), true ); ?> /> Disables the check<?php
	}
	
	function admin_field_tools_jpg() {
		$tools = self::get_settings( 'tools' );
		?><input type="text" id="field-tools_jpg" name="<?php echo $this->strOptionName . '[tools][jpg]'; ?>" class="regular-text" value="<?php echo $tools['jpg']; ?>" /><?php
	}
	
	function admin_field_tools_png() {
		$tools = self::get_settings( 'tools' );		
		?><input type="text" id="field-tools_png" name="<?php echo $this->strOptionName . '[tools][png]'; ?>" class="regular-text" value="<?php echo $tools['png']; ?>" /><?php
	}
	
	function admin_field_tools_gif() {
		$tools = self::get_settings( 'tools' );
		?><input type="text" id="field-tools_gif" name="<?php echo $this->strOptionName . '[tools][gif]'; ?>" class="regular-text" value="<?php echo $tools['gif']; ?>" /><?php
	}
	
	function admin_field_tools_fallback() {
		$tools = self::get_settings( 'tools' );
		if( isset( $tools['convert'] ) && !isset( $tools['fallback'] ) ) {
			$tools['fallback'] = $tools['convert'];
		}
		?><input type="text" id="field-tools_fallback" name="<?php echo $this->strOptionName . '[tools][fallback]'; ?>" class="regular-text" value="<?php echo $tools['fallback']; ?>" />
		<p class="description">Path to the <code>convert</code> binary</p>
		<?php
	}
	
	function admin_field_quality_jpg() {
		$quality = self::get_settings( 'quality_jpg' );
		$default = 75;
		if( empty( $quality ) ) {
			$quality = $default;
		}
		?>
		<input type="number" class="small-text" id="field-quality_jpg" name="<?php echo $this->strOptionName . '[quality_jpg]'; ?>" value="<?php echo $quality; ?>" min="10" step="1" max="100" /> <span class="description">Quality in percent (10 - 100%; defaults to <?php echo $default; ?>%)</span><?php
	}
	
	function admin_field_quality_png() {
		$quality = self::get_settings( 'quality_png' );
		$default = 4;
		if( empty( $quality ) ) {
			$quality = $default;
		}
		
		?><input type="number" class="small-text" id="field-quality_png" name="<?php echo $this->strOptionName . '[quality_png]'; ?>" class="regular-text" value="<?php echo $quality; ?>" /><?php
	}

	function admin_validate_settings( $input ) {
		$return = self::get_settings();
		$current_settings = $return;
		$default_settings = self::get_default_settings();
		
		//new __debug( $input, 'input' );
		//new __debug( $current_settings, 'current settings' );
		
		if( !empty( $input ) ) {
			if( empty( $current_settings ) ) {
				$return = $current_settings = $default_settings;
			}
			
			foreach( $default_settings as $strName => $value ) {
				if( $strName == 'skip_check' ) {
					$return[ 'skip_check' ] = ( !empty( $input[ 'skip_check' ] ) ? 1 : 0 );
				} elseif( $strName == 'tools' ) {
					
					foreach( $value as $strToolName => $toolValue ) {
						//$inputValue = trim( $input[ 'tools' ][ $strToolName ] );
						
						//if( isset( $input[ $strName ][ $strToolName ] ) && $input[ $strName ][ $strToolName ] != $toolValue ) {
							if( !empty( $input[ 'tools' ][ $strToolName ] ) ) {
								if( self::is_valid_filepath( $input[ 'tools' ][ $strToolName ] ) != false ) {
									$return[ 'tools' ][ $strToolName ] = $input[ 'tools' ][ $strToolName ];
								}
							} else {
								$return['tools'][ $strToolName ] = '';
							}
						//} elseif( empty( trim( $input[ $strName ][ $strToolName ] ) ) ) {
							
						//}
						//}
					}
				} elseif( strpos( $strName, 'quality_' ) !== false ) {
					$return[ $strName ] = intval( $value );
				} else {
					if( isset( $input[ $strName] ) && $input[ $strName ] != $value ) {
						$return[ $strName ] = $value;
					}
				}
				
				//if( isset( $input[ $strName ] ) && $input[ $strName ] != $value ) {
					/*switch( $strName ) {
						case 'skip_check':
							$return[ $strName ] = !empty( $input[ $strName ] );
							break;
						default:
							//if( self::is_valid_filepath( $input[ $strName ] ) || empty( $input[ $strName ] ) ) {
								$return[ $strName ] = trim( $input[ $strName ]);
							//}
							break;
					}*/
				//}
			}
		}
			
		return $return;
	}

	function admin_page_options() {
		$options = get_option( $this->strOptionName, false );
		$settings = self::get_settings();
		
		/*
		new __debug( array(
			'options' => $options,
			'settings' => $settings,
			'post' => $_POST,
			'get' => $_GET,
		) );*/
		
		if( !empty($_POST['detect_tools'] ) ) {
			$result = self::detect_tools();
			
			//new __debug( $result );
			
			if( !empty( $result ) && !empty( $result['found'] ) ) {
				
				$tools_settings = $settings['tools'];
				
				//new __debug( $tools_settings, 'tools_settings' );
				
				foreach( $tools_settings as $strType => $value ) {
					/*new __debug( array(
						'type' => $strType,
						'value' => $value,
						'found(' . $strType . ')' => $result['found'][ $strType ],
					) );
					*/
					if( isset( $result['found'][ $strType ] ) && $value != $result['found'][ $strType ] ) {
						$tools_settings[ $strType ] = $result['found'][ $strType ];
					}
				}
				
				$settings['tools'] = $tools_settings;
				
				
				self::update_settings( $settings );
			}
			
			
			
			//new __debug( $result, 'tool detection result' );
		}
		
		?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"><br /></div>
			
			<form method="post" action="<?php echo admin_url('options.php'); ?>">
			
				<?php settings_fields('cw_io_settings'); ?>
				<?php do_settings_sections('cw_io_main_settings'); ?>
				
				<p>Local path: <input type="text" class="regular-text" value="<?php echo ABSPATH; ?>" /></p>
				
				<p class="submit"><?php submit_button( __('Save Changes'), 'primary', 'submit', false ); ?>
					<?php submit_button('Detect paths', 'primary', 'detect_tools', false );?>
				</p>
			</form>
		</div>
		<?php	
	}
	
	function add_notice_littleutils() {
		/*$required = array(
			'PNG' => 'opt-png',
			'JPG' => 'opt-jpg',
			'GIF' => 'opt-gif',
			'FALLBACK' => 'convert',
		);*/

		$settings = self::get_settings();

		$skip = false;
		if( !empty( $settings['tools'] ) ) {
			$tools = $settings['tools'];
		}

		// To skip binary checking, define CW_IMAGE_OPTIMIZER_SKIP_CHECK in your wp-config.php
		if( (defined('CW_IMAGE_OPTIMIZER_SKIP_CHECK') && CW_IMAGE_OPTIMIZER_SKIP_CHECK) || !empty( $settings['skip_check'] ) ) {
			$skip = true;
		}		
		
		$result = self::detect_tools();
		
		$msg = implode(', ', $result['missing'] );
		$cookie_key = 'cw-io-warning-dismissed';

		if(!empty($msg) && empty($_COOKIE[ $cookie_key ]) ) {
			echo '<div id="cw-image-optimizer-warning-opt-png" class="updated notice is-dismissible fade"><p><strong>' . self::pluginName . ' requires <a href="http://sourceforge.net/projects/littleutils/">littleutils</a> or ImageMagick.</strong> You are missing: ' . $msg . '.</p><button class="notice-dismiss" type="button"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
			
			?>
			<script>jQuery('#cw-image-optimizer-warning-opt-png').on('click', function() {
				document.cookie = '<?php echo $cookie_key . '=1'; ?>';
			});</script>
			<?php
			
			//<div class="updated notice is-dismissible" id="message"><p>Plugin <strong>activated</strong>.</p><button class="notice-dismiss" type="button"><span class="screen-reader-text">Dismiss this notice.</span></button></div>
		}

		// Check if exec is disabled
		$disabled = array_map('trim', explode(',', ini_get('disable_functions')));
		if(in_array('exec', $disabled)){
			echo '<div id="cw-image-optimizer-warning-opt-png" class="updated fade"><p><strong>' . self::pluginName . ' requires exec().</strong> Your system administrator has disabled this function.</p></div>';
		}

	}


	public static function bulk_preview() {
	  if ( function_exists( 'apache_setenv' ) ) {
		@apache_setenv('no-gzip', 1);
	  }
	  @ini_set('output_buffering','on');
	  @ini_set('zlib.output_compression', 0);
	  @ini_set('implicit_flush', 1);
	  $attachments = get_posts( array(
		'numberposts' => -1,
		'post_type' => 'attachment',
		'post_mime_type' => 'image'
	  ));
	  require( dirname(__FILE__) . '/bulk.php' );
	}
	
	
	/**
	 * Print column header for optimizer results in the media library using
	 * the `manage_media_columns` hook.
	 */
	function add_media_columns($defaults) {
		$defaults['cw-io-advanced'] = 'Image Optimizer';
		$defaults['cw-io-filesize'] = 'File Size';
		return $defaults;
	}
	
	/**
	 * Print column data for optimizer results in the media library using
	 * the `manage_media_custom_column` hook.
	 */
	function add_media_custom_column($column_name, $id) {
		// adds file sizes
		if( $column_name == 'cw-io-filesize' ) {
			$data = wp_get_attachment_metadata($id);
			//new __debug( $data );
			
			if( !empty( $data['file'] ) ) {
				$file_path = $data['file'];
			
				$upload_dir = wp_upload_dir();
				$upload_path = trailingslashit( $upload_dir['basedir'] );
				
				$file_path = $upload_path . $file_path;
				
				$size = filesize( $file_path );
				
				echo ( cw_image_optimizer_format_bytes( $size ) );
				
			} else {
				echo '-';
			}
			
		}
		
		// optimizer column
		if( $column_name == 'cw-io-advanced' ) {
			$data = wp_get_attachment_metadata($id);

			if(!isset($data['file'])){
				$msg = 'Metadata is missing file path.';
				//print __('Unsupported file type', CW_IMAGE_OPTIMIZER_DOMAIN) . $msg;
				print __('Unsupported file type', CW_IMAGE_OPTIMIZER_DOMAIN) . $msg;
				
				return;
			}

			$file_path = $data['file'];
			$upload_dir = wp_upload_dir();
			$upload_path = trailingslashit( $upload_dir['basedir'] );

			// WordPress >= 2.6.2: determine the absolute $file_path (http://core.trac.wordpress.org/changeset/8796)
			if ( FALSE === strpos($file_path, WP_CONTENT_DIR) ) {
				$file_path = $upload_path . $file_path;
			}
			
			$tools = self::get_settings( 'tools' );
			
			$msg = '';

			if(function_exists('getimagesize')){
				$type = getimagesize($file_path);
				if(false !== $type){
					$type = $type['mime'];
				}
			}elseif(function_exists('mime_content_type')){
				$type = mime_content_type($file_path);
			}else{
				$type = false;
				$msg = 'getimagesize() and mime_content_type() PHP functions are missing';
			}



			$valid = true;
			switch($type){
				case 'image/jpeg':
					//if(CW_IMAGE_OPTIMIZER_JPG == false && CW_IMAGE_OPTIMIZER_FALLBACK == false) {
					
					if( empty( $tools[ 'jpg' ] ) && empty( $tools['fallback'] ) ) {
						$valid = false;
						$msg = '<br>' . __('<em>opt-jpg</em> is missing');
					}
					break; 
				case 'image/png':
					//if(CW_IMAGE_OPTIMIZER_PNG == false && CW_IMAGE_OPTIMIZER_FALLBACK == false) {
					if( empty( $tools[ 'png' ] ) && empty( $tools['fallback'] ) ) {
						$valid = false;
						$msg = '<br>' . __('<em>opt-png</em> is missing');
					}
					break;
				case 'image/gif':
					if( empty( $tools[ 'gif' ] ) ) {
					//if(CW_IMAGE_OPTIMIZER_GIF == false) {
						$valid = false;
						$msg = '<br>' . __('<em>opt-gif</em> is missing');
					}
					break;
				default:
					$valid = false;
			}

			if($valid == false) {
				print __('Unsupported file type', CW_IMAGE_OPTIMIZER_DOMAIN) . $msg;
				return;
			}

			if ( isset($data['cw_image_optimizer']) && !empty($data['cw_image_optimizer']) ) {
				print $data['cw_image_optimizer'];
				printf("<br><a href=\"admin.php?action=cw_image_optimizer_manual&amp;attachment_ID=%d\">%s</a>",
						 $id,
						 __('Re-optimize', CW_IMAGE_OPTIMIZER_DOMAIN));
			} else {
				print __('Not processed', CW_IMAGE_OPTIMIZER_DOMAIN);
				printf("<br><a href=\"admin.php?action=cw_image_optimizer_manual&amp;attachment_ID=%d\">%s</a>",
						 $id,
						 __('Optimize now!', CW_IMAGE_OPTIMIZER_DOMAIN));
			}
		}
	}

	function _admin_page_options() {
		$settings = self::get_settings();
		
	?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"><br /></div>
			<h2><?php echo self::pluginName; ?> Settings</h2>
			<p><?php echo self::pluginName; ?> performs several checks to make sure your system is capable of optimizing images.</p>
			<p>In some cases, these checks may erroneously report that you are missing littleutils even though you have littleutils installed.</p>

			<form method="post" action="options.php">
				<?php settings_fields('cw_image_optimizer_check_options'); ?>
				<p>Do you want to skip the littleutils check?</p>
				
				<p>
					<input type="checkbox" id="field-skip_check" name="field_skip_check" value="true" <?php checked( $settings['skip_check'], true ); ?> /> <label for="field-skip_check" />Skip littleutils check</label>
				</p>
				
				<p>
					<label for="field-tools_jpg">Path to JPEG optimizer</label>
					<input type="text" id="field-tools_jpg" name="field_tools[jpg]" value="<?php echo $tools['jpg']; ?>" />
				</p>
				
				<p>
					<label for="field-tools_png">Path to PNG optimizer</label>
					<input type="text" id="field-tools_png" name="field_tools[png]" value="<?php echo $tools['png']; ?>" />
				</p>

				<p>
					<label for="field-tools_gif">Path to GIF optimizer</label>
					<input type="text" id="field-tools_gif" name="field_tools[gif]" value="<?php echo $tools['gif']; ?>" />
				</p>

				<p>
					<label for="field-tools_fallback">Path to ImageMagick</label>
					<input type="text" id="field-tools_fallback" name="field_tools[fallback]" value="<?php echo $tools['fallback']; ?>" />
				</p>


				<p class="submit">
					<button type="submit" class="button-primary"><?php _e('Save changes'); ?></button>
				</p>
			</form>
		</div>
	<?php
	}

	
}


/**
 * Manually process an image from the Media Library
 */
function cw_image_optimizer_manual() {
	if ( FALSE === current_user_can('upload_files') ) {
		wp_die(__('You don\'t have permission to work with uploaded files.', CW_IMAGE_OPTIMIZER_DOMAIN));
	}

	if ( FALSE === isset($_GET['attachment_ID'])) {
		wp_die(__('No attachment ID was provided.', CW_IMAGE_OPTIMIZER_DOMAIN));
	}

	$attachment_ID = intval($_GET['attachment_ID']);

	$original_meta = wp_get_attachment_metadata( $attachment_ID );

	$new_meta = cw_image_optimizer_resize_from_meta_data( $original_meta, $attachment_ID );
	wp_update_attachment_metadata( $attachment_ID, $new_meta );

	$sendback = wp_get_referer();
	$sendback = preg_replace('|[^a-z0-9-~+_.?#=&;,/:]|i', '', $sendback);
	wp_redirect($sendback);
	exit(0);
}

/**
 * Process an image.
 *
 * Returns an array of the $file $results.
 *
 * @param   string $file            Full absolute path to the image file
 * @returns array
 */
function cw_image_optimizer($file, $quality = '') {
	// don't run on localhost, IPv4 and IPv6 checks
	// if( in_array($_SERVER['SERVER_ADDR'], array('127.0.0.1', '::1')) )
	//	return array($file, __('Not processed (local file)', CW_IMAGE_OPTIMIZER_DOMAIN));

	// canonicalize path - disabled 2011-02-1 troubleshooting 'Could not find...' errors.
	// From the PHP docs: "The running script must have executable permissions on 
	// all directories in the hierarchy, otherwise realpath() will return FALSE."
	// $file_path = realpath($file);
	$tools = _ui_io_base::get_settings( 'tools' );
	
	$file_path = $file;

	// check that the file exists
	if ( FALSE === file_exists($file_path) || FALSE === is_file($file_path) ) {
		$msg = sprintf(__("Could not find <span class='code'>%s</span>", CW_IMAGE_OPTIMIZER_DOMAIN), $file_path);
		return array($file, $msg);
	}

	// check that the file is writable
	if ( FALSE === is_writable($file_path) ) {
		$msg = sprintf(__("<span class='code'>%s</span> is not writable", CW_IMAGE_OPTIMIZER_DOMAIN), $file_path);
		return array($file, $msg);
	}

	// check that the file is within the WP_CONTENT_DIR
	$upload_dir = wp_upload_dir();
	$wp_upload_dir = $upload_dir['basedir'];
	$wp_upload_url = $upload_dir['baseurl'];
	if ( 0 !== stripos(realpath($file_path), realpath($wp_upload_dir)) ) {
		$msg = sprintf(__("<span class='code'>%s</span> must be within the content directory (<span class='code'>%s</span>)", CW_IMAGE_OPTIMIZER_DOMAIN), htmlentities($file_path), $wp_upload_dir);

		return array($file, $msg);
	}

    if(function_exists('getimagesize')){
        $type = getimagesize($file_path);
        if(false !== $type){
            $type = $type['mime'];
        }
    }elseif(function_exists('mime_content_type')){
        $type = mime_content_type($file_path);
    }else{
        $type = 'Missing getimagesize() and mime_content_type() PHP functions';
    }
	
	$file_copy = '';
	$params = '';
	$old_size = 0;
	
    switch($type){
        case 'image/jpeg':
			$command = 'opt-jpg';
			$command = $tools[ 'jpg' ];
			
			//if( CW_IMAGE_OPTIMIZER_JPG == false && defined( 'CW_IMAGE_OPTIMIZER_FALLBACK' ) && CW_IMAGE_OPTIMIZER_FALLBACK !== false ) {
			if( empty( $tools[ 'jpg' ] ) && !empty( $tools['fallback'] ) ) {	
				
				$command = $tools['fallback'];
				$params = ' %s -quality %d %s';
				
				//$file_copy = pathinfo( $file , PATHINFO_FILENAME ) . '.bak.' . pathinfo( $file , PATHINFO_EXTENSION );
				
				$file_path = pathinfo( $file );
				
				$file_copy = trailingslashit( $file_path['dirname'] ) . $file_path['filename'] . '.bak.' . $file_path['extension'];
				
				if( empty( $quality ) ) {
					$quality = '75%';
				}
			}

			break;
		case 'image/png':
			$command = 'opt-png';
			$command = $tools[ 'png' ];
			
			if( empty( $tools[ 'png' ] ) && !empty( $tools['fallback'] ) ) {
				$command = $tools['fallback'];
				$params = ' %s -quality %s %s ';
				$file_path = pathinfo( $file );
				
				$file_copy = trailingslashit( $file_path['dirname'] ) . $file_path['filename'] . '.bak.' . $file_path['extension'];
				
				if( empty( $quality ) ) {
					$quality = '4';
				}
			}

			break;
		case 'image/gif':
			$command = 'opt-gif';
			break;
		default:
			return array($file, __('Unknown type: ' . $type, CW_IMAGE_OPTIMIZER_DOMAIN));
			break;
    }

	if( !empty( $params ) ) {
		
		if( empty( $old_size ) ) {
			$old_size = filesize( $file );
		}
		
		if( empty( $file_copy ) ) {
			$file_copy = $file;
		}
		
		$strCommand = $command . ' ' . sprintf( $params, $file_copy, $quality, $file );
		
		//new __debug( array('file_copy' => $file_copy, 'file' => $file, 'old_size' => $old_size, 'cli' => $strCommand ), 'running convert' );
		
		//$result = exec( $command . ' ' . sprintf( $params, $file, $quality, $file_copy  ) );
		if( $file_copy != $file ) { // switch files before processing them
			copy( $file, $file_copy );
		}
		
		$result = exec( $strCommand );
		
		
	
	} else {
		$result = exec($command . ' ' . escapeshellarg($file));

		$result = str_replace($file . ': ', '', $result);

	}
	
	$return = array( $file, __('Bad response from optimizer', CW_IMAGE_OPTIMIZER_DOMAIN) );
	
	if($result == 'unchanged') {
		$return = array($file, __('No savings', CW_IMAGE_OPTIMIZER_DOMAIN));
	}

	if(strpos($result, ' vs. ') !== false) {
		$s = explode(' vs. ', $result);
		
		$savings = intval($s[0]) - intval($s[1]);
		$savings_str = cw_image_optimizer_format_bytes($savings, 1);
		$savings_str = str_replace(' ', '&nbsp;', $savings_str);

		$percent = 100 - (100 * ($s[1] / $s[0]));

		$results_msg = sprintf(__("Reduced by %01.1f%% (%s)", CW_IMAGE_OPTIMIZER_DOMAIN),
					 $percent,
					 $savings_str);

		$return = array($file, $results_msg);
	}

	/**
	 * NOTE: Should normally only available to convert / IM
	 */

	if( !empty( $quality ) ) {
		
		
		if( !empty( $file_copy ) && $file_copy !== $file ) {
		
			$old_size = filesize( $file_copy );
		}
		
		$new_size = filesize( $file );
		
		/*new __debug( array(
			'quality' => $quality,
			'old_size' => $old_size,
			'new_size' => $new_size,
			'file_copy' => $file_copy,
			'file' => $file,
		) );
		*/
		if( !empty( $old_size ) ) {
			$savings = intval( $old_size ) - intval( $new_size );
			
			$savings_str = cw_image_optimizer_format_bytes($savings, 1);
			$savings_str = str_replace(' ', '&nbsp;', $savings_str);

			$percent = 100 - (100 * ( $new_size / $old_size));

			$results_msg = sprintf(__("Reduced by %01.1f%% (%s)", CW_IMAGE_OPTIMIZER_DOMAIN),
						 $percent,
						 $savings_str);

			$return = array($file, $results_msg);
		}
	}

	return $return;
    //return array($file, __('Bad response from optimizer', CW_IMAGE_OPTIMIZER_DOMAIN));
}


/**
 * Read the image paths from an attachment's meta data and process each image
 * with cw_image_optimizer().
 *
 * This method also adds a `cw_image_optimizer` meta key for use in the media library.
 *
 * Called after `wp_generate_attachment_metadata` is completed.
 */
function cw_image_optimizer_resize_from_meta_data($meta, $ID = null) {
	$file_path = $meta['file'];
	$store_absolute_path = true;
	$upload_dir = wp_upload_dir();
	$upload_path = trailingslashit( $upload_dir['basedir'] );

	// WordPress >= 2.6.2: determine the absolute $file_path (http://core.trac.wordpress.org/changeset/8796)
	if ( FALSE === strpos($file_path, WP_CONTENT_DIR) ) {
		$store_absolute_path = false;
		$file_path =  $upload_path . $file_path;
	}

	list($file, $msg) = cw_image_optimizer($file_path);

	$meta['file'] = $file;
	$meta['cw_image_optimizer'] = $msg;

	// strip absolute path for Wordpress >= 2.6.2
	if ( FALSE === $store_absolute_path ) {
		$meta['file'] = str_replace($upload_path, '', $meta['file']);
	}

	// no resized versions, so we can exit
	if ( !isset($meta['sizes']) )
		return $meta;

	// meta sizes don't contain a path, so we calculate one
	$base_dir = dirname($file_path) . '/';


	foreach($meta['sizes'] as $size => $data) {
		list($optimized_file, $results) = cw_image_optimizer($base_dir . $data['file']);

		$meta['sizes'][$size]['file'] = str_replace($base_dir, '', $optimized_file);
		$meta['sizes'][$size]['cw_image_optimizer'] = $results;
	}

	return $meta;
}



/**
 * Return the filesize in a humanly readable format.
 * Taken from http://www.php.net/manual/en/function.filesize.php#91477
 */
function cw_image_optimizer_format_bytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}


