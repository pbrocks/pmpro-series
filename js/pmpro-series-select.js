jQuery(document).ready(function($) {
	$('#pmpro-series-check').click(function() {
		// alert('got a connection');
		$.ajax({
			type: "POST",
			url: ajaxurl,
			// datatype: 'JSON',
			data: {
				'action' : 'post_select_request',
				'delay' : $('#pmpros_delay').val(),
				'posts_to_add' : $('#pmpros_post').val(),
				'save' : pmpro_series_object.save,
				'series_id' : pmpro_series_object.series_id,
				'post_select_url' : pmpro_series_object.post_select_url,
				'post_select_nonce' : pmpro_series_object.post_select_nonce,
			},
			success:function(data) {
				// obj = JSON.parse(data);
				var responseHTML = "pmpros_add_post=1&pmpros_series=" + pmpro_series_object.series_id + "&pmpros_post=" + $('#pmpros_post').val() + '&pmpros_delay=' + $('#pmpros_delay').val()
				// $('#pmpros_series_posts').html(responseHTML)
				// var returnURL = obj.post_select_url + obj.returnpage + '&s=' + obj.filter;
				// var returnLink = '<a href="' + returnURL + '">' + returnURL + '</a>'; 
				$( '#ajax-return' ).html(data + responseHTML);
				// $( '#ajax-return' ).html(obj.save + ' array ' + obj.returning);
				// $( '#level-filter-request2' ).html('returnURL ' + returnLink + data);
			},
			error: function(jqXHR, textStatus, errorThrown){
				console.log(errorThrown);
			}
		});  
	});
});

function pmpros_editPost(post_id, delay){
	jQuery('#pmpros_post').val(post_id).trigger("change");
	jQuery('#pmpros_delay').val(delay);
	jQuery('#pmpros_save').html('Save');
	location.href = "#pmpros_edit_post";
}

function pmpros_removePost(post_id) {
	var seriesid = pmpro_series.series_id;
	jQuery.ajax({
		url: ajaxurl,
		type:'GET',
		timeout:2000,
		dataType: 'html',
		data: "pmpros_add_post=1&pmpros_series=" + seriesid + "&pmpros_remove="+post_id,
		error: function(xml){
			alert('Error removing series post [1]');
			//enable save button
			jQuery('#pmpros_save').removeAttr('disabled');												
		},
		success: function(responseHTML){
			if (responseHTML == 'error'){
				alert('Error removing series post [2]');
				//enable save button
				jQuery('#pmpros_save').removeAttr('disabled');	
			}else{
				jQuery('#pmpros_series_posts').html(responseHTML);
			}																						
		}
	});
}
