/* Author:
	Groupe Lp - june2012
*/


//BB5 Toolbar MAINTABS
//http://www.jacklmoore.com/notes/jquery-tabs
$('#bb5-maintabs>ul.bb5-tabs').each(function(){
    return;
    // For each set of tabs, we want to keep track of
    // which tab is active and it's associated content
    var $active, $content, $links = $(this).find('a');

    // Use the first link as the initial active tab
    $active = $links.first().addClass('bb5-tabs-active');
    $content = $($active.attr('href'));

    // Hide the remaining content
    $links.not(':first').each(function () {
        $($(this).attr('href')).hide();
    });

    // Bind the click event handler
    $(this).on('click', 'a', function(e){
        // Make the old tab inactive.
        $active.removeClass('bb5-tabs-active');
        $content.hide();

        // Update the variables with the new link and content
        $active = $(this);
        $content = $($(this).attr('href'));

        // Make the tab active.
        $active.addClass('bb5-tabs-active');
        $content.show();

        // Prevent the anchor's default click action
        e.preventDefault();
    });
});

//BB5 Layout Slider
/*$('#bb5-maintabs #bb5-slider-layout').bxSlider({
    nextText:'<span><i class="visuallyhidden focusable">Suivant</i></span>',
    prevText:'<span><i class="visuallyhidden focusable">Précédent</i></span>',
    infiniteLoop:false, 
    hideControlOnEnd:true,
    displaySlideQty:4,
    moveSlideQty:1,
    pager:false
});





//BB5 Block Slider
//	$('#bb5-maintabs #bb5-slider-blocks').bxSlider({
//		nextText:'<span><i class="visuallyhidden focusable">Suivant</i></span>',
//		prevText:'<span><i class="visuallyhidden focusable">Précédent</i></span>',
//		infiniteLoop:false, 
//		hideControlOnEnd:true,
//		displaySlideQty:4,
//		moveSlideQty:1,
//		pager:false
//		});


//BB5 Custom Select
//http://www.georgepaterson.com/sandbox/jquery-ui-custom-select-demo/
$('.bb5-select').selectgroup();


//BB5 Exam Path
//$("#bb5-exam-path-wrapper").hide();
$('#bb5-maintabs li a[href="#bb5-editing"]').bind('click',function() {
    //$("#bb5-exam-path-wrapper").slideToggle();
    });
$('a.bb5-ico-pathclose').bind('click',function() {
    //$("#bb5-exam-path-wrapper").slideToggle();
    });
$('#bb5-maintabs li a[href="#bb5-editing"]').bind('focusout',function() {
    //$("#bb5-exam-path-wrapper").slideUp();
    });


//BB5 Dialog
$( ".bb5-dialog" ).dialog({
    //autoOpen: false,
    dialogClass: "bb5-dialog-wrapper",
    show: "blind",
    hide: "fade",
    zIndex: 601000,
    open:function(event,ui){
        var closeBtn =  $(this).parents(".ui-dialog").find(".ui-dialog-titlebar-close");
        $(closeBtn).replaceWith("<button class=\"bb5-button bb5-ico-close bb5-button-square bb5-invert\"></button>");
        var self = this;
        $('.ui-dialog .bb5-button.bb5-ico-close.bb5-button-square.bb5-invert').bind('click',function() {
            $(self).dialog("close")
        });
        $(".ui-dialog").addClass("bb5-ui");
        return false;		
    }
});

//Dialog -> Edit Property 
/*$( ".bb5-dialog.bb5-dialog-editproperty" ).dialog({
			//autoOpen: false,
			dialogClass: "bb5-dialog-wrapper bb5-dialog-editproperty",
			title: "Editer les propriétés de cet item"
		});*/

//Dialog -> Deletion 
/*$( ".bb5-dialog.bb5-dialog-deletion" ).dialog({
			//autoOpen: false,
			dialogClass: "bb5-dialog-wrapper bb5-dialog-deletion",
			title: "Supprimer cet item ?"
		});*/

//Dialog -> Alert
/*$( ".bb5-dialog.bb5-dialog-alert" ).dialog({
			//autoOpen: false,
			dialogClass: "bb5-dialog-wrapper bb5-dialog-alert",
			title: "Alerte"
		});*/

//Dialog -> Info 
/*$( ".bb5-dialog.bb5-dialog-info" ).dialog({
			//autoOpen: false,
			dialogClass: "bb5-dialog-wrapper bb5-dialog-info",
			title: "Information"
		});*/
		
//Dialog -> confirmation
/*$( ".bb5-dialog.bb5-dialog-confirmation" ).dialog({
			//autoOpen: false,
			dialogClass: "bb5-dialog-wrapper bb5-dialog-confirmation",
			title: "Confirmation"
		});*/
		
	
//BB5 Contextual Menu
$(function() {
    $( ".bb5-ui.bb5-context-menu" ).draggable();
});