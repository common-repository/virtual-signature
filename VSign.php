<?php
/*
Plugin Name: Signature
Plugin URI: http://virtualrealitysystems.net/plugins/VSign/
Description: It adds signature to Post
Version: 1.0
Author: Virtual Reality Systems
Author URI: http://virtualrealitysystems.net/
License: GPL2
*/
class options_page {
	
	function __construct() {
		add_action('admin_init', array(&$this, 'register_my_setting' ));
		add_action('admin_menu', array(&$this, 'admin_menu'));
		add_action( 'wp_insert_post', array(&$this, 'on_wp_insert_post'), 10, 2 );
		add_action("admin_head", array(&$this, "enqueue_script"));
		add_filter('tiny_mce_before_init', array(&$this, 'fb_change_mce_buttons'));
		//add_filter('the_editor_content',array(&$this, 'pub')); // Use When Required
	}
	
	function fb_change_mce_buttons( $initArray ) {
	/*echo '<pre>';
	var_dump($initArray);
	echo '</pre>';*/
	?>
	<style type="text/css">
		
	</style>
	<?php
   // $initArray['width'] = '200px';	
	$initArray['theme_advanced_resizing'] = false;
    return $initArray;
    }
	
    
	// add script
	function enqueue_script() {
		wp_enqueue_script(array('jquery', 'editor', 'thickbox', 'media-upload'));
		wp_enqueue_style('thickbox');
		
	}
	
	// Init plugin options to white list our options
	function register_my_setting(){
		register_setting( 'setting_signature_options', 'setting_signature');
		add_action( 'add_meta_boxes', array( &$this, 'add_some_meta_box' ) );
	}
	
	// Add MetaBox on POST
	function add_some_meta_box(){
		add_meta_box('signature_types', __('Add Signature', 'add-signature'), array(&$this, 'add_featured_meta_box'), 'post', 'side', 'high');
	}
	
	// Call from add_meta_box
	function add_featured_meta_box($data){
		$value = get_post_meta($data->ID, '_signature-types', true);
		?>
		<ul>				
			<li><label for='signature'><input type='checkbox' name='signature_post' id='signature_post' value='1' <?php checked('1', isset($value['signature_post']) ? $value['signature_post'] : '')?> /> Add Signature</label></li>
		</ul>
		<?php
	}

	
	// Add menu page
	function admin_menu() {
		add_options_page('Signature', 'Signature', 'manage_options', 'options_page_slug', array(&$this, 'setting_page'));
	}
	
	/* Save the meta box's post metadata. and Append Signature To Editor If Checkbox is Checked */
	function on_wp_insert_post($id)
	{
		// For Checkbox Add Signature
		global $id;
		global $post; // Refers Record From DB		
		if ( !isset($id) )
			$id = (int)isset($_REQUEST['post_ID']) ? $_REQUEST['post_ID'] : isset($post->ID)? $post->ID : '';		
		if(isset($id) and !empty($id))
		{
			if ( isset($_POST['signature_post']) && $_POST['signature_post'] != '' )
				$this->data['signature_post'] = esc_attr( $_POST['signature_post'] );
			else
				$this->data['signature_post'] = 0;
			
			if ( isset($this->data) && $this->data != '' )
				update_post_meta($id, '_signature-types', $this->data);
				
			// End Checkbox Add Signature	
		
			// For Signature To Append To Editor
			
			$my_post = array();
			$options = get_option('setting_signature');	
			$value = get_post_meta($post->ID, '_signature-types', true); // MetaBox Value or Checkbox value
			if($post and $post->post_type == 'post' and isset($value['signature_post']) and $value['signature_post'])
			{		
				$my_post['ID'] = $post->ID;
				$my_post['post_title'] = $_POST['post_title'];
				$posted_content = $_POST['content'];
				if(!empty($posted_content))
					$my_post['post_content'] = $posted_content.'<br/>'.$options['signature_body'];
				else
					$my_post['post_content'] = $options['signature_body'];
				if(isset($my_post['post_content']))				
				{
					remove_action( 'wp_insert_post', array(&$this, 'on_wp_insert_post')); // For Removing Infinite
					$myid = wp_update_post( $my_post );
					add_action( 'wp_insert_post', array(&$this, 'on_wp_insert_post'), 10, 2 ); // For Removing Infinite	
				}
			}
			// End Signature To Append To Editor
		}
	}// End function on_wp_insert_post
	
	// Draw the menu page itself
	function setting_page() {
		?>
		<div class="wrap">
			<?php screen_icon('themes'); ?>
			<h2>Signature</h2>  
			<form method="post" action="options.php">
				<?php settings_fields('setting_signature_options'); ?>
				<?php $options = get_option('setting_signature');?>
				<table class="form-table">
					<!--<tr valign="top">
						<th scope="row">
							<label for="signature">
								Append Signature
							</label>
						</th>
						<td><input name="setting_signature[signature]" type="checkbox" value="1" <?php checked('1', isset($options['signature'])) ? $options['signature'] : ''; ?> /></td>
					</tr>-->
					<tr valign="top">
						<th>
							<label for="signature_body">
								Signature Body
							</label>
						</th>
					</tr>
					<tr valign="top">
						<td><!--<input type="text" name="setting_signature[signature_body]" value="<?php echo isset($options['signature_body'])? $options['signature_body']:'' ?>" />-->
						<?php
						wp_editor(isset($options['signature_body'])? $options['signature_body']:'', 'setting_signature[signature_body]wp'); // true gives you a stripped down version of the editor
						?>
						</td>
					</tr>
				</table>
				<p><?php submit_button(); ?></p>  
			</form>
		</div>
		<?php	
	} // End function setting_page
	
	
	function pub(){
		global $post;
		$my_post = array();
		$options = get_option('setting_signature');	
		if(isset($_REQUEST['action']) and $_REQUEST['action']=='edit')
		{
			if($post and $post->post_type == 'post')
			{		
				$my_post['ID'] = $post->ID;
				$my_post['post_title'] = $post->post_title;			
				$my_post['post_content'] = $post->post_content.'<br/>'.$options['signature_body'];
				if(isset($options['signature']) and $options['signature'])
				{
					$id = wp_update_post( $my_post );
					if($id == $my_post['ID'])
						return $my_post['post_content'];
				}
				else
				{
					return $post->post_content;
				}
			}
		}
	}//End Pub
} //End Class options_page
new options_page;
