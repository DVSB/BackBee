(function($) {

bb.jquery.ui.dialog.prototype._oldinit = bb.jquery.ui.dialog.prototype._init;
bb.jquery.ui.dialog.prototype._init = function() {
    bb.jquery(this.element).parent().css('position', 'fixed');
    bb.jquery(this.element).dialog('option',{
        resizeStop: function(event,ui) {
            var position = [(Math.floor(ui.position.left) - bb.jquery(window).scrollLeft()),
            (Math.floor(ui.position.top) - bb.jquery(window).scrollTop())];
            bb.jquery(event.target).parent().css('position', 'fixed');
            bb.jquery(event.target).parent().dialog('option','position',position);
            return true;
        }
    });
    
    this._oldinit();
};

}) (bb.jquery);
