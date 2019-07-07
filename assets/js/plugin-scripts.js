jQuery(function($) {
	$(document).ready(function(){
		
		$( '#uty_videos' ).hide();
		$( '#uty_settings' ).hide();
		$( '.tab_control li a' ).on( 'click' , function(){
			var showTab = $(this).attr('href');
			$('.tab_content > div').hide();
			$('.tab_content '+showTab).show();
		});
		
		
		$('.accordion_section h4.accordion_heading').on( 'click' , function(){
			if($(this).hasClass('active')){
				$(this).removeClass('active');
				$(this).siblings('.accordion_content').removeClass('active');
				$(this).siblings('.accordion_content').slideUp('slow');
			}
			else{
				$('.accordion_heading').removeClass('active');
				$('.accordion_content').removeClass('active');
				$('.accordion_content').slideUp('slow');
				$(this).addClass('active');
				$(this).siblings('.accordion_content').addClass('active');
				$(this).siblings('.accordion_content').slideDown('slow');
			}
		})
	})
});