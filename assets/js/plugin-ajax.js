jQuery(function($) {
	$(document).ready(function(){
		$('ul.videos_list').on( 'click' , '.delete_video' , function(){
			var videoid = $(this).data('video-id');
			$.ajax({
				type: 'POST',
				url: ajax_params.ajax_url,
				dataType: 'html',
				data: {
					action: 'deletevideo',
					videoid: videoid
				},
				success: function(response) {
					$('.videos_list').html(response);
					$('.removeSuccess').fadeIn('slow');
					setTimeout(function(){
						$('.removeSuccess').fadeOut('slow');
					}, 4000)
				},
				error: function(errorThrown){
					console.log(errorThrown);
				}	 
			})
		})
	})
});