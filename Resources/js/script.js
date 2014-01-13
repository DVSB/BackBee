/* Author:
	Groupe Lp - june2012
*/
(function($) {

//BB5 Toolbar MAINTABS
//http://www.jacklmoore.com/notes/jquery-tabs
bb.jquery('#bb5-maintabs>ul.bb5-tabs').each(function(){
    return;
    // For each set of tabs, we want to keep track of
    // which tab is active and it's associated content
    var $active, $content, $links = bb.jquery(this).find('a');

    // Use the first link as the initial active tab
    $active = $links.first().addClass('bb5-tabs-active');
    $content = bb.jquery($active.attr('href'));

    // Hide the remaining content
    $links.not(':first').each(function () {
        bb.jquery(bb.jquery(this).attr('href')).hide();
    });

    // Bind the click event handler
    bb.jquery(this).on('click', 'a', function(e){
        // Make the old tab inactive.
        $active.removeClass('bb5-tabs-active');
        $content.hide();

        // Update the variables with the new link and content
        $active = bb.jquery(this);
        $content = bb.jquery(bb.jquery(this).attr('href'));

        // Make the tab active.
        $active.addClass('bb5-tabs-active');
        $content.show();

        // Prevent the anchor's default click action
        e.preventDefault();
    });
});

//BB5 Layout Slider
/*bb.jquery('#bb5-maintabs #bb5-slider-layout').bxSlider({
    nextText:'<span><i class="visuallyhidden focusable">Suivant</i></span>',
    prevText:'<span><i class="visuallyhidden focusable">Précédent</i></span>',
    infiniteLoop:false, 
    hideControlOnEnd:true,
    displaySlideQty:4,
    moveSlideQty:1,
    pager:false
});





//BB5 Block Slider
//	bb.jquery('#bb5-maintabs #bb5-slider-blocks').bxSlider({
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
bb.jquery('.bb5-select').selectgroup();


//BB5 Exam Path
//bb.jquery("#bb5-exam-path-wrapper").hide();
bb.jquery('#bb5-maintabs li a[href="#bb5-editing"]').bind('click',function() {
    //bb.jquery("#bb5-exam-path-wrapper").slideToggle();
    });
bb.jquery('a.bb5-ico-pathclose').bind('click',function() {
    //bb.jquery("#bb5-exam-path-wrapper").slideToggle();
    });
bb.jquery('#bb5-maintabs li a[href="#bb5-editing"]').bind('focusout',function() {
    //bb.jquery("#bb5-exam-path-wrapper").slideUp();
    });


//BB5 Dialog
bb.jquery( ".bb5-dialog" ).dialog({
    //autoOpen: false,
    dialogClass: "bb5-dialog-wrapper",
    show: "blind",
    hide: "fade",
    zIndex: 601000,
    open:function(event,ui){
        var closeBtn =  bb.jquery(this).parents(".ui-dialog").find(".ui-dialog-titlebar-close");
        bb.jquery(closeBtn).replaceWith("<button class=\"bb5-button bb5-ico-close bb5-button-square bb5-invert\"></button>");
        var self = this;
        bb.jquery('.ui-dialog .bb5-button.bb5-ico-close.bb5-button-square.bb5-invert').bind('click',function() {
            bb.jquery(self).dialog("close")
        });
        bb.jquery(".ui-dialog").addClass("bb5-ui");
        return false;		
    }
});

//Dialog -> Edit Property 
/*bb.jquery( ".bb5-dialog.bb5-dialog-editproperty" ).dialog({
			//autoOpen: false,
			dialogClass: "bb5-dialog-wrapper bb5-dialog-editproperty",
			title: "Editer les propriétés de cet item"
		});*/

//Dialog -> Deletion 
/*bb.jquery( ".bb5-dialog.bb5-dialog-deletion" ).dialog({
			//autoOpen: false,
			dialogClass: "bb5-dialog-wrapper bb5-dialog-deletion",
			title: "Supprimer cet item ?"
		});*/

//Dialog -> Alert
/*bb.jquery( ".bb5-dialog.bb5-dialog-alert" ).dialog({
			//autoOpen: false,
			dialogClass: "bb5-dialog-wrapper bb5-dialog-alert",
			title: "Alerte"
		});*/

//Dialog -> Info 
/*bb.jquery( ".bb5-dialog.bb5-dialog-info" ).dialog({
			//autoOpen: false,
			dialogClass: "bb5-dialog-wrapper bb5-dialog-info",
			title: "Information"
		});*/
		
//Dialog -> confirmation
/*bb.jquery( ".bb5-dialog.bb5-dialog-confirmation" ).dialog({
			//autoOpen: false,
			dialogClass: "bb5-dialog-wrapper bb5-dialog-confirmation",
			title: "Confirmation"
		});*/
		
	
//BB5 Contextual Menu

    bb.jquery(function() {
        bb.jquery( ".bb5-ui.bb5-context-menu" ).draggable();
    });
}) (bb.jquery);