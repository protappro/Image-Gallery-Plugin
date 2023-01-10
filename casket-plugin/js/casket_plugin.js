jQuery(document).ready(function() {

	jQuery(".casket_list a").fancybox({		
		openEffect	: 'none',
		closeEffect	: 'none',
		padding : 0,
		margin      : [20, 60, 20, 60],
		helpers	: {
			thumbs	: {
				width	: 50,
				height	: 50
			}
		}		
	});
});





/*jQuery(document).ready(function() {
  
  jQuery(".casket_list a").fancybox();
  
 jQuery(".casket_list a").attr("data-fancybox","casket_gallery");
 jQuery(".casket_list a").each(function(){
 	jQuery(this).attr("title", jQuery(this).find("img").attr("alt"));
 	var title = jQuery(this).attr("title");
 	var img_title = jQuery("<div class='title_position'>"+title+"</div>");
 	console.log(img_title);
 	
 	jQuery(".fancybox-content .fancybox-image").after(img_title);


    //jQuery(this).attr("data-caption", jQuery(this).find("img").attr("alt"));
    
  });
   
  jQuery(".casket_list a").fancybox();
  

});*/



