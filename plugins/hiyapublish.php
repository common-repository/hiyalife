<?php


	function hiya_plugin_settings_menu(){
		add_options_page( 'Hiyalife Settings', 'Hiyalife Settings', 'manage_options', 'hylwp', 'hiya_plugin_options');
	}

	function hiya_admin_init(){
		register_setting('hylwp-group','hylwp_publish_default');
		register_setting('hylwp-group','hylwp_user_key', 'hiya_validate_key_options');
		register_setting('hylwp-group','hylwp_secret_key', 'hiya_validate_secret_options');
		hiya_init_cron_auth();
	}

	function hiya_validate_key_options($input){
		$output= sanitize_key($input);
		if (strlen($output) == 0) {
			add_settings_error(
                    'todo_url', 					// setting title
                    'todourl_texterror',			// error ID
                    'Please enter a valid Hiyalife Customer key',		// error message
                    'error'							// type of message
                    );
			# Set it to the default value
			$output ="";
		}
		return $output;
	}

	function hiya_validate_secret_options($input){
		$output= sanitize_key($input);
		if (strlen($output) == 0) {
			add_settings_error(
                    'todo_url', 					// setting title
                    'todourl_texterror',			// error ID
                    'Please enter a valid Hiyalife Secret key',		// error message
                    'error'							// type of message
                    );
			# Set it to the default value
			$output ="";
		}
		return $output;
	}

	function hiya_plugin_options(){
		$connection = new HiyaConn();
		?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2>Hiyalife Settings</h2>
			<a href="<?php echo $connection->getReedemKeysURL(hiya_get_blog_id()); ?>" target="_blank">Reedem your keys</a>
			<form action="options.php" method="post">
				<?php settings_fields('hylwp-group'); ?>
				<?php @do_settings_fields('hylwp-group'); ?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><label for="hylwp_user_key">Your Hiyalife Consumer key</label></th>
						<td>
							<input type="text" name="hylwp_user_key" id="hylwp_user_key" value="<?php echo get_option('hylwp_user_key'); ?>" />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="hylwp_secret_key">Your Hiyalife Secret key</label></th>
						<td>
							<input type="text" name="hylwp_secret_key" id="hylwp_secret_key" value="<?php echo get_option('hylwp_secret_key'); ?>" />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="hylwp_publish_default">Publish by default</label></th>
						<td>
							<input type="checkbox" name="hylwp_publish_default" id="hylwp_publish_default" value="checked" <?php echo get_option('hylwp_publish_default');?> />
						</td>
					</tr>
				</table><?php @submit_button( ); ?>
			</form>
		</div>
		<?php
	}

	function hiya_init_cron_auth(){
		if(!wp_next_scheduled('hiya_cron_auth')){
			wp_schedule_event(time(), 'hourly', 'hiya_cron_auth');
		}else{
			if(_empty(get_option("HIYA_ACCESS_TOKEN"))){
				hiya_auth_submit();
			}
		}
	}


	function hiya_auth_submit(){
		$connection = new HiyaConn();
		$tokens="";
		$keys = new HiyaKeys();
		$user_key = get_option('hylwp_user_key');
		$secret_key = get_option('hylwp_secret_key');
		$refresh_token = get_option('HIYA_REFRESH_TOKEN');
		if(!empty($user_key) && !empty($secret_key)){
			if(empty($refresh_token)){
				$keys->setKeys($user_key,$secret_key);
				$blog_name = hiya_get_blog_id();
				$tokens = $connection->authenticate($keys,$blog_name);
			}else{
				$keys->setTokens(get_option('HIYA_ACCESS_TOKEN'),$refresh_token);
				$tokens = $connection->auth_refresh($keys);
				if(empty($tokens)){ // if not valid refresh, we remove refresh token and call to new authentication process
					delete_option("HIYA_REFRESH_TOKEN");
					delete_option("HIYA_ACCESS_TOKEN");
					hiya_auth_submit();
					return;
				}
			}
			if(!_empty($tokens)){
				if($tokens=="noExist"){
					update_option("hiyamsgAuth", true );
				}else{
				update_option("HIYA_ACCESS_TOKEN",$tokens->access_token);			
				update_option("HIYA_REFRESH_TOKEN",$tokens->refresh_token);
				delete_option("hiyamsgAuth");
				}
			}
		}
	}

	function hiya_get_blog_id(){
		if(_empty(get_option('hylwp_blog_id'))){
			update_option("hylwp_blog_id","wordpress".rand(100000,999999));
		}
		return get_option('hylwp_blog_id');
	}


	function hiya_post_published_message( $messages) {
		global $post_ID;
		$messageStatus = get_option("messageStatus","OK");
		$lastResult= get_option("lastResult","OK");
		$text="";
		if($messageStatus == "OK"){
			if(!_empty(get_post_meta($post_ID, "hiya_id", true ))){
				$connection = new HiyaConn();
				$text = sprintf( __('<br/><br/><a href="%s" target="_blank">View on Hiyalife</a>'), $connection->auth_url.'/meemo/'. get_post_meta($post_ID, "hiya_id", true )); 
			}
		}
		else if($messageStatus == "Fail"){
			$text = sprintf( __('<br/><div class="error">Publishing on Hiyalife Failed<br/>%s</div>'), $lastResult ); 
		}else if($messageStatus == "Unlinked"){
			$text = sprintf( __('<br/><div class="error">Publishing on Hiyalife not found or deleted.<br/> Click on "Update" to repubish on Hiyalife</div>')); 
		}
		$messages['post'][6] = $messages['post'][6].$text;
		$messages['post'][1] = $messages['post'][1].$text;
		return $messages;
	}



	function hiya_post_published($post_ID){
		if(isset($_POST["hiya_publish_check"]) && $_POST["hiya_publish_check"]=="YES" && !_empty(get_option('hylwp_user_key')) && !_empty(get_option('hylwp_secret_key')) && !_empty(get_option('HIYA_ACCESS_TOKEN')) ){ 
			$time_start = microtime(true); ///Inicio de marca de tiempo
			$current_post = get_post($post_ID);
			$meta_images = get_post_meta($post_ID, "hiya_images",true);

			$meta_hiya_id = get_post_meta($post_ID, "hiya_id", true );
			if(_empty($meta_images)) $meta_images=array();

			$post_thum = wp_get_attachment_url( get_post_thumbnail_id($post_ID) );
			$meemo = new HiyaMeemo($current_post->post_title,$current_post->post_content,$current_post->post_date,$meta_images,$meta_hiya_id,$post_thum);
			if(_empty($meemo->meemo_text)) return $post_ID; //Nothing to publish
			$connection = new HiyaConn();
			$keys = new HiyaKeys();
			$keys->setTokens(get_option("HIYA_ACCESS_TOKEN"),get_option("HIYA_REFRESH_TOKEN"));
			//  update or publish?
			if(_empty($meta_hiya_id)){
				$result = $connection->publishMeemo($keys,$meemo);
			}else{
				$result = $connection->updateMeemo($keys,$meemo);
			}
			update_option('lastResult',$connection->lastResult);
			if(!_empty($result)){
				if($result=="unlinked"){
					delete_post_meta($post_ID, "hiya_id");
					delete_post_meta($post_ID, "hiya_images" );
					update_option("messageStatus","Unlinked");
					return $post_ID;
				}
			// Add image upload information into post metadata
				$newImages=$meemo->meemo_images;
				foreach ($newImages as $img) {
					array_push($meta_images, $img);
				}
				delete_post_meta($post_ID, "hiya_images" );
				add_post_meta( $post_ID, "hiya_images", $meta_images);
				if($result!="Updated"){
					add_post_meta( $post_ID, "hiya_id", $result, true );
				}
				update_option("messageStatus","OK");
				return $post_ID;
			}else{
				/*echo "<h2>Publishing Failed</h2>";
				var_dump($connection->lastRequest);
				echo "<pre>".$connection->lastResult."</pre>";
				echo '<div style="text-align:center;"><a href="post.php">Back to Wordpress</a></div>';
				exit;
				*/		
				update_option("messageStatus","Fail");
				return $post_ID;
			}
			/*
			//For debug
			var_dump($connection->lastRequest);
			echo "<pre>".$connection->lastResult."</pre>";
			echo '<div style="text-align:center;"><a href="post.php">Back to Wordpress</a></div>';
			echo get_num_queries(); ?> queries in <?php timer_stop(1); ?> seconds. <?PHP
			$time_end = microtime(true);
			$execution_time = ($time_end - $time_start)/60;
			echo '<br/><b>Total Execution Time:</b> '.$execution_time.' Mins<br/>';
			exit;*/		
		} else if(_empty(get_option('HIYA_ACCESS_TOKEN'))){
			update_option("messageStatus","Fail" );	
			update_option("lastResult", "Failed in authentication");
			return $post_ID;
		}
		update_option("messageStatus","");
		return $post_ID;
	}


	function myplugin_addOption()
	{
		if( function_exists( 'add_meta_box' )) {
			add_meta_box( 'hiyapublish','Hiyalife Publish', 'hiya_addMetaBox','post','side','high');
		}
	}

	function hiya_addMetaBox(){
		if(_empty(get_option('hylwp_user_key')) || _empty(get_option('hylwp_secret_key'))){
			?>
			<p>Check your consumer keys y Hiyalife Settings.</p>
			<?php
		}else{
			if(isset($_GET["post"]) && !_empty(get_post_meta($_GET["post"], "hiya_id", true ))){
				echo "<p>This post will be updated on Hiyalife.</p>";
				echo '<input type="hidden" name="hiya_publish_check" id="hiya_publish_check" value="YES">';
			}else {
				?>
				<p>
					<label class="selectit" for="comment_status">
						<input id="hiya_publish_check" type="checkbox" name="hiya_publish_check" value="YES" <?php echo get_option('hylwp_publish_default'); ?>/>
						Publish on Hiyalife 
					</label>
				</p>
				<?php 
			}
		}
	}

	function add_post_content($content) {
		if(!is_feed() && !is_home()) {
			global $post;
			$connection = new HiyaConn();
			$hiya_id = get_post_meta( $post->ID, "hiya_id", true );
			if(!_empty($hiya_id)){
				$content .= '<p><a href="'.$connection->auth_url.'/meemo/'.$hiya_id.'" target="_blank">View on Hiyalife</a></p>';
			}
		}
		return $content;
	}


	function showMessage($message, $errormsg = false)
	{
		if ($errormsg) {
			echo '<div id="message" class="error">';
		}
		else {
			echo '<div id="message" class="updated fade">';
		}
		echo "<p><strong>$message</strong></p></div>";
	}    

	function showAdminMessages()
	{
		if (_empty(get_option('hylwp_user_key')) || _empty(get_option('hylwp_secret_key'))) {
			showMessage('You need to settings consumer key and consumer secret in <a href="options-general.php?page=hylwp">Hiyalife Settings</a> as soon as possible...', true);
		} else if (get_option("hiyamsgAuth")){
			showMessage('User does not exist. You need to test your consumer key and consumer secret in <a href="options-general.php?page=hylwp">Hiyalife Settings</a>.', true);
		}
	}

		// Auth keys
		add_option("HIYA_ACCESS_TOKEN",'','','yes');
		add_option("HIYA_REFRESH_TOKEN",'','','yes');
		//Start Settings
		// Add menu
		add_action('admin_menu','hiya_plugin_settings_menu' );
		// Init options
		add_action('admin_init','hiya_admin_init');

		// Scheduled of auth keys
		add_action('init','hiya_init_cron_auth');
		add_action('hiya_cron_auth','hiya_auth_submit');
		//Custom messages
		add_filter('post_updated_messages', 'hiya_post_published_message');
		// Add a publish option on create/edit post page.
		add_action('admin_menu', 'myplugin_addOption');
		// Show View on Hiyalife after post content
		add_filter('the_content', 'add_post_content');
		// Show admin notice message
		add_action('admin_notices', 'showAdminMessages');     
		// Hook on publish
		add_filter('publish_post', 'hiya_post_published');
	
?>