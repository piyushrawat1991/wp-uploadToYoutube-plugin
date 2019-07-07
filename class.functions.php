<?php
class uploadToYoutube{
	
	//Called on plugin activation
	public function uty_plugin_activation(){
		global $wpdb;
		$table_prefix = $wpdb->prefix;
		$wp_track_table = $table_prefix."uty_listing";

		#Check to see if the table exists already, if not, then create it
	
		if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table){
			$sql = "CREATE TABLE `". $wp_track_table . "` ( ";
			$sql .= "  `listing_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, ";
			$sql .= "  `video_title` varchar(255) COLLATE utf8_unicode_ci NOT NULL, ";
			$sql .= "  `video_description` text COLLATE utf8_unicode_ci NOT NULL, ";
			$sql .= "  `video_tags` varchar(255) COLLATE utf8_unicode_ci NOT NULL, ";
			$sql .= "  `video_path` varchar(255) COLLATE utf8_unicode_ci NOT NULL, ";
			$sql .= "  `youtube_video_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL, ";
			$sql .= "  `status` tinyint(1) NOT NULL DEFAULT '0', ";
			$sql .= "  `delete_status` tinyint(1) NOT NULL DEFAULT '0' ";
			$sql .= ");";
			
			require_once(ABSPATH . '/wp-admin/includes/upgrade.php');
			dbDelta($sql);
		}
	}
	
	//Registers plugin page on the dashboard
	public function register_uty_menu_page() {
		add_menu_page( 'Upload To Youtube', 'UTY', 'administrator', 'upload-to-youtube', array( $this , 'uty_main_page') );
		add_submenu_page( 'upload-to-youtube', 'UTY', 'UTY', 'administrator', 'upload-to-youtube', array( $this , 'uty_main_page') );
		add_submenu_page( 'upload-to-youtube', 'FAQs', 'FAQs', 'administrator', 'upload-to-youtube-faqs', array( $this , 'uty_faq_page') );
	}

	//Scripts & styles enqueued for admin
	public function uty_admin_enqueue(){
		wp_enqueue_style( 'uty-plugin-css', plugins_url( '/assets/css/plugin-styles.css', __FILE__ ) );
		wp_enqueue_script( 'uty-ajax-scripts', plugins_url( '/assets/js/plugin-ajax.js', __FILE__ ) , array( 'jquery' ), '1.0.0', true );
		
		//Localize the script for ajax purposes
		wp_localize_script(
			'uty-ajax-scripts',
			'ajax_params',
			array( 
				'ajax_url' => admin_url( 'admin-ajax.php' )
			)
		);
	}

	//Script enqueued after successful authentication
	public function uty_admin_enqueue_after_authorization(){
		wp_enqueue_script( 'uty-plugin-scripts', plugins_url( '/assets/js/plugin-scripts.js', __FILE__ ) , array( 'jquery' ), '1.0.0', true );
	}
	
	//Function to implement shortcode
	public function uty_video_shortcode( $atts , $content = null ){
		extract(shortcode_atts(array(
			'count' => 3,
		), $atts));
		require_once ('config.php');
		if( $client->isAccessTokenExpired() ) {
			if(get_option( 'uty_refresh_token' ) != ''){
				$client->refreshToken( get_option( 'uty_refresh_token' ) );
				$_SESSION['token'] = $client->getAccessToken();
				$client->setAccessToken($client->getAccessToken());
				
				try {
					$channelsResponse = $youtube->channels->listChannels('contentDetails', array(
						'mine' => 'true',
					));
					$htmlBody = '';
					foreach ($channelsResponse['items'] as $channel) {
						$uploadsListId = $channel['contentDetails']['relatedPlaylists']['uploads'];
									
						$playlistItemsResponse = $youtube->playlistItems->listPlaylistItems('snippet', array(
								'playlistId' => $uploadsListId,
								'maxResults' => $count
							));
						
						
						$htmlBody .= '<div class="videos_list">';
						foreach ($playlistItemsResponse['items'] as $playlistItem) {
							$videoID = $playlistItem['snippet']['resourceId']['videoId'];
										
							$htmlBody .= '<div class="video_item"><iframe width="100%" src="https://www.youtube.com/embed/'.$videoID.'"></iframe></div>';
						}
						$htmlBody .= '</div>';

					}
				}
				catch (Google_Service_Exception $e) {
					$htmlBody = sprintf('<p>A service error occurred: <code>%s</code></p>',	htmlspecialchars($e->getMessage()));
				}
				catch (Google_Exception $e) {
					$htmlBody = sprintf('<p>A client error occurred: <code>%s</code></p>', htmlspecialchars($e->getMessage()));
				}
				return $htmlBody;
			}
		}
	}
	
	//Function to delete videos via AJAX
	public function deleteVideo(){
		require_once ('config.php');
		if( $client->isAccessTokenExpired() ) {
			if(get_option( 'uty_refresh_token' ) != ''){
				$client->refreshToken( get_option( 'uty_refresh_token' ) );
				$_SESSION['token'] = $client->getAccessToken();
				$client->setAccessToken($client->getAccessToken());
				
				$id = $_POST['videoid'];
				$youtube->videos->delete($id);
				try {
					$channelsResponse = $youtube->channels->listChannels('contentDetails', array(
						'mine' => 'true',
					));
					$htmlBody = '';
					foreach ($channelsResponse['items'] as $channel) {
						$uploadsListId = $channel['contentDetails']['relatedPlaylists']['uploads'];
									
						$playlistItemsResponse = $youtube->playlistItems->listPlaylistItems('snippet', array(
								'playlistId' => $uploadsListId,
								'maxResults' => 50
							));
						
						
						foreach ($playlistItemsResponse['items'] as $playlistItem) {
							$videoID = $playlistItem['snippet']['resourceId']['videoId'];
										
							$videoTitle = $playlistItem['snippet']['title'];
							$delIconPath = plugins_url('upload-to-youtube').'/assets/img/icon-delete.png';
							$playIconPath = plugins_url('upload-to-youtube').'/assets/img/icon-youtube.png';
							$deleteIcon ='<img src="'.$delIconPath.'">';
							$playIcon ='<img src="'.$playIconPath.'">';
							$htmlBody .= '<li class="video_item"><iframe width="100%" src="https://www.youtube.com/embed/'.$videoID.'"></iframe><a class="delete_video" data-video-id="'.$videoID.'">'.$deleteIcon.'</a><a title="Watch on YouTUbe" target="_blank" href="https://www.youtube.com/watch?v='.$videoID.'">'.$playIcon.'</a></li>';
						}
					}
				}
				catch (Google_Service_Exception $e) {
					$htmlBody = sprintf('<p>A service error occurred: <code>%s</code></p>',	htmlspecialchars($e->getMessage()));
				}
				catch (Google_Exception $e) {
					$htmlBody = sprintf('<p>A client error occurred: <code>%s</code></p>', htmlspecialchars($e->getMessage()));
				}
				echo $htmlBody;
				wp_die();
			}
		}
	}
	
	//Registers plugin options
	public function uty_register_settings(){
		register_setting( 'uty_plugin_settings', 'uty_refresh_token' );
		register_setting( 'uty_plugin_settings', 'uty_google_client_id' );
		register_setting( 'uty_plugin_settings', 'uty_google_client_secret' );
		register_setting( 'uty_plugin_settings', 'uty_google_client_api' );
		register_setting( 'uty_plugin_settings', 'uty_youtube_channel' );
	}
	
	//Helper function to bolster video upload to YouTube
	public function readVideoChunk($handle, $chunkSizeBytes){
		$byteCount = 0;
		$giantChunk = "";
		while (!feof($handle)) {
			$chunk = fread($handle, 8192);
			$byteCount += strlen($chunk);
			$giantChunk .= $chunk;
			if ($byteCount >= $chunkSizeBytes){
				return $giantChunk;
			}
		}
		return $giantChunk;
	}
	
	//Helper function to get the video id from wordpress database
	public function get_attachment_id_from_src ($image_src) {
		global $wpdb;
		$id = $wpdb->get_var('SELECT ID FROM '.$wpdb->posts.' WHERE guid="'.$image_src.'"');	
		return $id;	
	}
	
	//Main function to display all the content
	public function uty_main_page(){
		require_once ('config.php');
		global $wpdb;
		if(isset($_GET['code'])) {
			$client->authenticate($_GET['code']);
			$_SESSION['token'] = $client->getAccessToken();
			$token_decode = json_decode($_SESSION['token']);
			update_option( 'uty_refresh_token' , $token_decode->refresh_token );
			$client->setAccessToken($_SESSION['token']);
			echo 'Authorization Successful';
		}
		?>
		<div class="wrap">
			<div class="uty_wrapper">
				<div class="tab_control">
					<ul>
						<?php if(get_option( 'uty_refresh_token' ) != ''){ ?>
							<li><a href="#uty_upload">Upload</a></li>
							<li><a href="#uty_videos">All Videos</a></li>
						<?php } ?>
						<li><a href="#uty_settings">Settings</a></li>
					</ul>
				</div>
				<div class="tab_content">
					<?php if( $client->isAccessTokenExpired() ) { ?>
						<?php if(get_option( 'uty_refresh_token' ) != ''){ ?>
							<?php
							$client->refreshToken( get_option( 'uty_refresh_token' ) );
							$_SESSION['token'] = $client->getAccessToken();
							$client->setAccessToken($client->getAccessToken());
							?>
							<div id="uty_upload">
								<?php
								if(isset($_POST['uty_video_submit'])){
									if(isset($_FILES['uty_video'])){
										$uploaded = media_handle_upload('uty_video', 0);
										if(is_wp_error($uploaded)){
											$message = $uploaded->get_error_message();
										}
										else{
											try{
												$snippet = new Google_Service_YouTube_VideoSnippet();
											
												$videoPath = wp_get_attachment_url( $uploaded );
											
												$videoTitle = sanitize_text_field($_POST['uty_video_title']);
												$snippet->setTitle($videoTitle);
											
												if(isset($_POST['uty_video_tags']) && !empty($_POST['uty_video_tags'])){
													$videoTags = sanitize_text_field($_POST['uty_video_tags']);
													$snippet->setTags(explode(",",$videoTags));
												}

												if(isset($_POST['uty_video_description']) && !empty($_POST['uty_video_description'])){
													$videoDescription = sanitize_text_field($_POST['uty_video_description']);
													$snippet->setDescription($videoDescription);
												}
											
												$snippet->setCategoryId("22");
												$status = new Google_Service_YouTube_VideoStatus();
												$status->privacyStatus = "public";
												$video = new Google_Service_YouTube_Video();
												$video->setSnippet($snippet);
												$video->setStatus($status);
												$chunkSizeBytes = 1 * 1024 * 1024;
												$client->setDefer(true);
												$insertRequest = $youtube->videos->insert("status,snippet", $video);
												$media = new Google_Http_MediaFileUpload(
													$client,
													$insertRequest,
													'video/*',
													null,
													true,
													$chunkSizeBytes
												);
												$videoID = $this->get_attachment_id_from_src($videoPath);
												$videoSize = get_attached_file($videoID);
												$media->setFileSize(filesize($videoSize));
												$status = false;
												$handle = fopen($videoPath, "rb");
												while (!$status && !feof($handle)) {
													$chunk = $this->readVideoChunk($handle, $chunkSizeBytes);
													$status = $media->nextChunk($chunk);
												}
												$result = false;
												if ($status != false) {
													$result = $status;
												}
												fclose($handle);
												$client->setDefer(false);
												wp_delete_attachment( $videoID, true );
												$message = 'Video Uploaded succesfully';
											} 
											catch (Google_ServiceException $e) {
												$message = sprintf('<p>A service error occurred: <code>%s</code></p>', htmlspecialchars($e->getMessage()));
											}
											catch (Google_Exception $e) {
												$message = sprintf('<p>A client error occurred: <code>%s</code></p>', htmlspecialchars($e->getMessage()));
											}
										}
									}
								}
								?>
								<form enctype="multipart/form-data" method="post" action="">
									<p><?php if(isset($message)){ echo $message; } ?></p>
									<h4>Select a video to upload to your YouTube channel</h4>
									<table class="form-table">
										<tr valign="top">
											<th scope="row">Title</th>
											<td><input class="uty_input_field" type="text" name="uty_video_title" placeholder="Your video Title" required></td>
										</tr>
										<tr valign="top">
											<th scope="row">Tags</th>
											<td><input class="uty_input_field" type="text" name="uty_video_tags" placeholder="tag1,tag2,tag3....."></td>
										</tr>
										<tr valign="top">
											<th scope="row">Description</th>
											<td><textarea class="uty_input_field" rows="8" cols="72" type="text" name="uty_video_description" placeholder="Your video Description"></textarea></td>
										</tr>
										<tr valign="top">
											<td><input type="file" name="uty_video" accept="video/mp4,video/x-m4v,video/*" required></td>
										</tr>
										<tr valign="top">
											<td><input type="submit" name="uty_video_submit" value="Upload"></td>
										</tr>
									</table>
								</form>
							</div>
							<div id="uty_videos">
								<p class="removeSuccess">Video Removed Succesfully</p>
								<?php
								try {
									$channelsResponse = $youtube->channels->listChannels('contentDetails', array(
									'mine' => 'true',
									));
									$htmlBody = '';
									foreach ($channelsResponse['items'] as $channel) {
										$uploadsListId = $channel['contentDetails']['relatedPlaylists']['uploads'];
									
										$playlistItemsResponse = $youtube->playlistItems->listPlaylistItems('snippet', array(
												'playlistId' => $uploadsListId,
												'maxResults' => 50
											));
							
										$htmlBody .= '<ul class="videos_list">';
										foreach ($playlistItemsResponse['items'] as $playlistItem) {
										
											$videoID = $playlistItem['snippet']['resourceId']['videoId'];
											$videoTitle = $playlistItem['snippet']['title'];

											$delIconPath = plugins_url('upload-to-youtube').'/assets/img/icon-delete.png';
											$playIconPath = plugins_url('upload-to-youtube').'/assets/img/icon-youtube.png';
										
											$deleteIcon ='<img src="'.$delIconPath.'">';
											$playIcon ='<img src="'.$playIconPath.'">';
										
											$htmlBody .= '<li class="video_item"><iframe width="100%"										src="https://www.youtube.com/embed/'.$videoID.'"></iframe><div class="video-info"><div class="left-sec"><p>'.$videoTitle.'</p></div><div class="right-sec"><a class="delete_video" title="Delete" data-video-id="'.$videoID.'">'.$deleteIcon.'</a><a title="Watch on YouTUbe" target="_blank" href="https://www.youtube.com/watch?v='.$videoID.'">'.$playIcon.'</a></div></div></li>';
										}
										$htmlBody .= '</ul>';
									}
								}
								catch (Google_Service_Exception $e) {
									$htmlBody = sprintf('<p>A service error occurred: <code>%s</code></p>',	htmlspecialchars($e->getMessage()));
								}
								catch (Google_Exception $e) {
									$htmlBody = sprintf('<p>An client error occurred: <code>%s</code></p>', htmlspecialchars($e->getMessage()));
								}
								echo $htmlBody;
								?>
							</div>
						<?php } ?>
					<?php } ?>
					<div id="uty_settings">
						<?php if(isset($_POST['revoke-access-token'])){ ?>
							<?php update_option( 'uty_refresh_token' , '' ); ?>
							<?php update_option( 'uty_google_client_api' , '' ); ?>
							<?php update_option( 'uty_google_client_id' , '' ); ?>
							<?php update_option( 'uty_google_client_secret' , '' ); ?>
							<?php update_option( 'uty_youtube_channel' , '' ); ?>
						<?php } ?>
						<h3>Please enter the below details to kick start your Youtube uploads</h3>
						<form method="post" action="options.php">
							<?php settings_fields( 'uty_plugin_settings' ); ?>
							<?php do_settings_sections( 'uty_plugin_settings' ); ?>
							<table class="form-table">
								<tr valign="top">
									<th scope="row">Google API</th>
									<td><input autocomplete="off" type="text" class="uty_input_field" name="uty_google_client_api" value="<?php echo get_option( 'uty_google_client_api' ); ?>"/></td>
								</tr>
								<tr valign="top">
									<th scope="row">Google Client ID</th>
									<td><input autocomplete="off" type="text" class="uty_input_field" name="uty_google_client_id" value="<?php echo get_option( 'uty_google_client_id' ); ?>"/></td>
								</tr>
								<tr valign="top">
									<th scope="row">Google Client Secret</th>
									<td><input autocomplete="off" type="text" class="uty_input_field" name="uty_google_client_secret" value="<?php echo get_option( 'uty_google_client_secret' ); ?>"/></td>
								</tr>
								<tr valign="top">
									<th scope="row">YouTube Channel ID</th>
									<td><input autocomplete="off" type="text" class="uty_input_field" name="uty_youtube_channel" value="<?php echo get_option( 'uty_youtube_channel' ); ?>"/></td>
								</tr>
								<tr valign="top">
									<th scope="row">Refresh token</th>
									<td>
										<input readonly type="text" class="uty_input_field" name="uty_refresh_token" value="<?php echo get_option( 'uty_refresh_token' ); ?>"/>
										<p><em>The <b>Refresh Token</b> is generated when you grant permissions for the first time only. Try to <a target="_blank" href="https://myaccount.google.com/permissions">clear your permissions</a> from google before authorizing same credentials again.</em></p>
									</td>
								</tr>
							</table>
							<?php submit_button(); ?>
						</form>
						<?php if( !empty(get_option('uty_google_client_api')) && !empty(get_option('uty_google_client_id')) && !empty(get_option('uty_google_client_secret')) && empty(get_option('uty_refresh_token')) ){ ?>
								<?php $state = mt_rand(); ?>
								<?php $client->setState($state); ?>
								<?php $_SESSION['state'] = $state; ?>
								<?php $authUrl = $client->createAuthUrl(); ?>
								<a href="<?php echo $authUrl; ?>">Click here to authorize your credentials</a>
						<?php } elseif( !empty(get_option('uty_google_client_api')) && !empty(get_option('uty_google_client_id')) && !empty(get_option('uty_google_client_secret')) && !empty(get_option('uty_refresh_token')) ){ ?>
								<form action="" method="post">
									<p>Click the below button only if you want to change your credentials or facing any issues in uploading/deleting</p>
									<input type="submit" class="no-style-submit" name="revoke-access-token" value="Revoke access token">
								</form>
						<?php } ?>
					</div>
				</div>
			</div>
		</div>
	<?php }
	
	public function client_project_mail(array $data){
		$name = $_POST['clientName'];
		$email = $_POST['clientEmail'];
		$message = $_POST['clientJobDescription'];
		$adminEmail = get_bloginfo('admin_email');
		$adminName =  get_bloginfo('name');

		$headers = array('Content-Type: text/html; charset=UTF-8','From: '.$adminName.'<'.$adminEmail.'>');
	    
		wp_mail( 'piyushrawat1991@gmail.com' , 'Get A Quote - U2Y Plugin' , $message , $headers );
	}
	public function uty_faq_page(){
		if(isset($_POST['clientFormSubmit'])){
			$mailResponse = $this->client_project_mail($_POST);
		}
		?>
		<style>
		h4.accordion_heading.active {
			background-image: url(<?php echo plugins_url('upload-to-youtube').'/assets/img/minus.png'; ?>);
		}
		h4.accordion_heading {
			background-image: url(<?php echo plugins_url('upload-to-youtube').'/assets/img/plus.png'; ?>);
		}
		</style>
		<div class="wrap">
			<div class="uty_wrapper">
				<div class="faq_top_section">
					<h2>Facing any issues?</h2>
					<p>No need to worry, your solution might be in one of the below FAQs</p>
				</div>
				<div class="uty_faq_section">
					<div>
						<div class="accordion_section">
							<h4 class="accordion_heading">This that!!</h4>
							<p class="accordion_content">qwsaeftrvergwerwrewerfsdvsfa</p>
						</div>
						<div class="accordion_section">
							<h4 class="accordion_heading">This that!!</h4>
							<p class="accordion_content">qwsaeftrvergwerwrewerfsdvsfa</p>
						</div>
						<div class="accordion_section">
							<h4 class="accordion_heading">This that!!</h4>
							<p class="accordion_content">qwsaeftrvergwerwrewerfsdvsfa</p>
						</div>
						<div class="accordion_section">
							<h4 class="accordion_heading">This that!!</h4>
							<p class="accordion_content">qwsaeftrvergwerwrewerfsdvsfa</p>
						</div>
						<div class="accordion_section">
							<h4 class="accordion_heading">This that!!</h4>
							<p class="accordion_content">qwsaeftrvergwerwrewerfsdvsfa</p>
						</div>
						<div class="accordion_section">
							<h4 class="accordion_heading">This that!!</h4>
							<p class="accordion_content">qwsaeftrvergwerwrewerfsdvsfa</p>
						</div>
						<div class="accordion_section">
							<h4 class="accordion_heading">This that!!</h4>
							<p class="accordion_content">qwsaeftrvergwerwrewerfsdvsfa</p>
						</div>
						<div class="accordion_section">
							<h4 class="accordion_heading">This that!!</h4>
							<p class="accordion_content">qwsaeftrvergwerwrewerfsdvsfa</p>
						</div>
						<div class="accordion_section">
							<h4 class="accordion_heading">This that!!</h4>
							<p class="accordion_content">qwsaeftrvergwerwrewerfsdvsfa</p>
						</div>
						<div class="accordion_section">
							<h4 class="accordion_heading">This that!!</h4>
							<p class="accordion_content">qwsaeftrvergwerwrewerfsdvsfa</p>
						</div>
						<div class="accordion_section">
							<h4 class="accordion_heading">This that!!</h4>
							<p class="accordion_content">qwsaeftrvergwerwrewerfsdvsfa</p>
						</div>
						<div class="accordion_section">
							<h4 class="accordion_heading">This that!!</h4>
							<p class="accordion_content">qwsaeftrvergwerwrewerfsdvsfa</p>
						</div>
						<div class="accordion_section">
							<h4 class="accordion_heading">This that!!</h4>
							<p class="accordion_content">qwsaeftrvergwerwrewerfsdvsfa</p>
						</div>
						<div class="accordion_section">
							<h4 class="accordion_heading">This that!!</h4>
							<p class="accordion_content">qwsaeftrvergwerwrewerfsdvsfa</p>
						</div>
					</div>
				</div>
				<div class="uty_faq_sidebar">
					<div class="hiring-section">
						<h2>Stuck somewhere? Want to hire for work? Need a custom plugin?</h2>
						<p>I would be glad to help you in solving your issues. Fill the below form with all the neccessary information about your project/task.</p>
						<form class="hiringForm" method="POST" action="">
							<input type="text" name="clientName" placeholder="Your Name" required/>
							<input type="email" name="clientEmail" placeholder="Your Email" required/>
							<textarea rows="6" cols="42"  name="clientJobDescription" placeholder="Brief description about the project you want me to be involved with" required></textarea>
							<input type="submit" name="clientFormSubmit" value="Submit"/>
						</form>
					</div>
					<div class="donation-section">
						<h2>Like my work?</h2>
						<p>If you like my work you can donate me through paypal.</p>
						<a target="_blank" href="https://www.paypal.me/piyushrawat1991"><img src="<?php echo plugins_url('upload-to-youtube').'/assets/img/PayPalDonateNow.png'; ?>"></a>
					</div>
				</div>
			
			</div>
		</div>
		<?php
	}
}