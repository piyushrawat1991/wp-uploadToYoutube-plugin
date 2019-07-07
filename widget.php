<?php
class uty_plugin_widget extends WP_Widget {
	function __construct() {
		
		parent::__construct(false, $name = __('Upload To Youtube', 'uty_plugin_widget') );

	}

	function form($instance) {	
		
		// Check values
		if( $instance) {
			$title = esc_attr($instance['title']);
			$numberofvideos = esc_attr($instance['numberofvideos']);
		}
		else {	
			$title = '';
			$numberofvideos = '';
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Widget Title', 'wp_widget_plugin'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('numberofvideos'); ?>"><?php _e('Number of Videos:', 'uty_plugin_widget'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('numberofvideos'); ?>" name="<?php echo $this->get_field_name('numberofvideos'); ?>" type="number" value="<?php echo $numberofvideos; ?>" />
			<small><em>Default : 3</em></small>
		</p>
		<?php

	}

	function update($new_instance, $old_instance) {
	
		$instance = $old_instance;

		// Fields
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['numberofvideos'] = strip_tags($new_instance['numberofvideos']);
		return $instance;
	
	}

	function widget($args, $instance) {
		
		extract( $args );	
		// these are the widget options
		$title = apply_filters('widget_title', $instance['title']);
		$numberofvideos = $instance['numberofvideos'];
		echo $before_widget;
		// Display the widget
		echo '<div class="widget-text wp_widget_plugin_box">';

		// Check if title is set
		if ( $title ) {
			echo $before_title . $title . $after_title;
		}

		// Check if text is set
		if( $numberofvideos ) {
			require_once ('config.php');
			if( $client->isAccessTokenExpired() ) {
				if(get_option( 'uty_refresh_token' ) != ''){
					$client->refreshToken( get_option( 'uty_refresh_token' ) );
					$_SESSION['token'] = $client->getAccessToken();
					$client->setAccessToken($client->getAccessToken());
				
					try {
						$channelsResponse = $youtube->channels->listChannels(
							'contentDetails',
							array(
								'mine' => 'true',
							)
						);
						$htmlBody = '';
						foreach ($channelsResponse['items'] as $channel) {
							$uploadsListId = $channel['contentDetails']['relatedPlaylists']['uploads'];
									
							$playlistItemsResponse = $youtube->playlistItems->listPlaylistItems(
								'snippet',
								array(
									'playlistId' => $uploadsListId,
									'maxResults' => $numberofvideos
								)
							);
												
							$htmlBody .= '<div class="videos_list">';
							foreach ($playlistItemsResponse['items'] as $playlistItem) {
								$videoID = $playlistItem['snippet']['resourceId']['videoId'];
										
								$videoTitle = $playlistItem['snippet']['title'];
								
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
					echo $htmlBody;
				}
			}
		}

		echo '</div>';
		echo $after_widget;
	
	}
}

// register widget
add_action('widgets_init', create_function('', 'return register_widget("uty_plugin_widget");'));
?>