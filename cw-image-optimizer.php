<?php
/**
 * Integrate Linux image optimizers into WordPress.
 * @version 1.2
 * @package CW_Image_Optimizer
 */
/*
Plugin Name: CW Image Optimizer Adapted
Plugin URI: http://www.corbanworks.com/cw-image-optimizer/
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

/**
 * Hooks
 */
add_filter('wp_generate_attachment_metadata', 'cw_image_optimizer_resize_from_meta_data', 10, 2);
add_filter('manage_media_columns', 'cw_image_optimizer_columns');
add_action('manage_media_custom_column', 'cw_image_optimizer_custom_column', 10, 2);
add_action('admin_init', 'cw_image_optimizer_admin_init');
add_action('admin_action_cw_image_optimizer_manual', 'cw_image_optimizer_manual');

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

add_action('admin_notices', 'cw_image_optimizer_notice_littleutils');

function cw_io_default_settings() {
	$defaults = array(
		'cw_image_optimizer_skip_check' => false,
		'tools_last_checked' => time(),
		'tools' => array(
			'PNG' => false,
			'JPG' => false,
			'GIF' => false,
			'FALLBACK' => false,
		),
	);
	
	return $defaults;
}

function cw_io_get_settings( $option_name = '' ) {
	
	$options = get_option( 'cw_image_optimizer_settings', cw_io_default_settings() );
	
	$return = $options;
	
	if( !empty( $option_name ) ) {
		$return = null;
		if( $option_name == 'tools' ) {
			//foreach( 
			
		} else {
			if( isset( $options[ $option_name ] ) ) {
				$return = $options[ $option_name ];
			}
		}
	}
	
	return $return;
}

function cw_image_optimizer_notice_littleutils() {
    $required = array(
        'PNG' => 'opt-png',
        'JPG' => 'opt-jpg',
        'GIF' => 'opt-gif',
        'FALLBACK' => 'convert',
    );

	$skip_check = cw_io_get_settings('cw_image_optimizer_skip_check');
	$last_checked = cw_io_get_settings( 'tools_last_checked' );
	$tools = cw_io_get_settings( 'tools' );
	$timeout = 600; // 5 minutes

	// To skip binary checking, define CW_IMAGE_OPTIMIZER_SKIP_CHECK in your wp-config.php
	/*if(
		(defined('CW_IMAGE_OPTIMIZER_SKIP_CHECK') && CW_IMAGE_OPTIMIZER_SKIP_CHECK) ||
		get_option('cw_image_optimizer_skip_check') == TRUE
	) {*/
	if(
		(defined('CW_IMAGE_OPTIMIZER_SKIP_CHECK') && CW_IMAGE_OPTIMIZER_SKIP_CHECK) ||
		!empty( $skip_check )
	) {
        $skip = true;
    } else {
        $skip = false;
    }
 
    $missing = array();

	if( empty( $last_checked ) || ( !empty( $last_checked ) && $last_checked < time() - $timeout ) ) {

		foreach($required as $key => $req) {
			$result = trim(exec('which ' . $req));
			if(!$skip && empty($result)){
				if( $req != 'convert' ) {
					$missing[] = $req;
				}
				define('CW_IMAGE_OPTIMIZER_' . $key, false);
				set_transient( 'cw_io_tool_' . $key, false );
			} else {
				/**
				 * NOTE: Add additional data to the resp. available conversion / optimization tool
				 */
				define('CW_IMAGE_OPTIMIZER_' . $key, trim($result) );
				
				set_transient( 'cw_io_tool_' . $key, trim( $result ) );
			}
		}
	
	}
    
    $msg = implode(', ', $missing );
	$cookie_key = 'cw-io-warning-dismissed';

    if(!empty($msg) && empty($_COOKIE[ $cookie_key ]) ) {
        echo '<div id="cw-image-optimizer-warning-opt-png" class="updated notice is-dismissible fade"><p><strong>CW Image Optimizer requires <a href="http://sourceforge.net/projects/littleutils/">littleutils</a> or ImageMagick.</strong> You are missing: ' . $msg . '.</p><button class="notice-dismiss" type="button"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
        
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
        echo "<div id='cw-image-optimizer-warning-opt-png' class='updated fade'><p><strong>CW Image Optimizer requires exec().</strong> Your system administrator has disabled this function.</p></div>";
    }

}

/**
 * Plugin admin functions
 */
function cw_image_optimizer_admin_init() {
	load_plugin_textdomain(CW_IMAGE_OPTIMIZER_DOMAIN);
	wp_enqueue_script('common');
    register_setting('cw_image_optimizer_check_options', 'cw_image_optimizer_skip_check');
}

function cw_image_optimizer_admin_menu() {
  add_media_page( 'Bulk Optimize', 'Bulk Optimize', 'edit_others_posts', 'cw-image-optimizer-bulk', 'cw_image_optimizer_bulk_preview');

  add_options_page(
    'CW Image Optimizer',           //Title
    'CW Image Optimizer',           //Sub-menu title
    'manage_options',               //Security
    __FILE__,                       //File to open
    'cw_image_optimizer_options'    //Function to call
  );

}
add_action( 'admin_menu', 'cw_image_optimizer_admin_menu' );

function cw_image_optimizer_bulk_preview() {
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
			if( CW_IMAGE_OPTIMIZER_JPG == false && defined( 'CW_IMAGE_OPTIMIZER_FALLBACK' ) && CW_IMAGE_OPTIMIZER_FALLBACK !== false ) {
				$command = CW_IMAGE_OPTIMIZER_FALLBACK;
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
			
			if( CW_IMAGE_OPTIMIZER_PNG == false && defined( 'CW_IMAGE_OPTIMIZER_FALLBACK' ) && CW_IMAGE_OPTIMIZER_FALLBACK !== false ) {
				$command = CW_IMAGE_OPTIMIZER_FALLBACK;
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
 * Print column header for optimizer results in the media library using
 * the `manage_media_columns` hook.
 */
function cw_image_optimizer_columns($defaults) {
	$defaults['cw-image-optimizer'] = 'Image Optimizer';
	return $defaults;
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

/**
 * Print column data for optimizer results in the media library using
 * the `manage_media_custom_column` hook.
 */
function cw_image_optimizer_custom_column($column_name, $id) {
    if( $column_name == 'cw-image-optimizer' ) {
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
            $file_path =  $upload_path . $file_path;
        }

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
                if(CW_IMAGE_OPTIMIZER_JPG == false && CW_IMAGE_OPTIMIZER_FALLBACK == false) {
                    $valid = false;
                    $msg = '<br>' . __('<em>opt-jpg</em> is missing');
                }
                break; 
            case 'image/png':
                if(CW_IMAGE_OPTIMIZER_PNG == false && CW_IMAGE_OPTIMIZER_FALLBACK == false) {
                    $valid = false;
                    $msg = '<br>' . __('<em>opt-png</em> is missing');
                }
                break;
            case 'image/gif':
                if(CW_IMAGE_OPTIMIZER_GIF == false) {
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

function cw_image_optimizer_options () {
?>
    <div class="wrap">
        <div id="icon-options-general" class="icon32"><br /></div>
        <h2>CW Image Optimizer Settings</h2>
        <p>CW Image Optimizer performs several checks to make sure your system is capable of optimizing images.</p>
        <p>In some cases, these checks may erroneously report that you are missing littleutils even though you have littleutils installed.</p>

        <form method="post" action="options.php">
            <?php settings_fields('cw_image_optimizer_check_options'); ?>
            <p>Do you want to skip the littleutils check?</p>
            <input type="checkbox" id="cw_image_optimizer_skip_check" name="cw_image_optimizer_skip_check" value="true" <?php if(get_option('cw_image_optimizer_skip_check') == TRUE) : ?>checked="true"<?php endif; ?> /> <label for="cw_image_optimizer_skip_check" />Skip littleutils check</label><br />

            <p class="submit">
                <input type="submit" class="button-primary" value="Save Changes" />
            </p>
        </form>
    </div>
<?php
}

