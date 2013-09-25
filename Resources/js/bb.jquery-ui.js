$.ui.dialog.prototype._oldinit = $.ui.dialog.prototype._init;
$.ui.dialog.prototype._init = function() {
    $(this.element).parent().css('position', 'fixed');
    $(this.element).dialog('option',{
        resizeStop: function(event,ui) {
            var position = [(Math.floor(ui.position.left) - $(window).scrollLeft()),
            (Math.floor(ui.position.top) - $(window).scrollTop())];
            $(event.target).parent().css('position', 'fixed');
            $(event.target).parent().dialog('option','position',position);
            return true;
        }
    });
    
    this._oldinit();
};