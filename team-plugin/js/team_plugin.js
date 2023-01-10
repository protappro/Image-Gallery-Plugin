jQuery(document).ready(function () {
	jQuery('div.team_member_card').click(function(){
		var team_member_id = jQuery(this).data('team_member_id');
		var data = {
			'action': 'get_team_member_details',
			'team_member_id': team_member_id
		};
		jQuery.ajax({
			type: "POST",               
			url: ajaxurl,
			data: data,
			success: function (response) {  
				jQuery('.team_member_deatils_wrapper').html(response);
				jQuery('#team_member_modal').modal('show');
			}
		});
	});	
});