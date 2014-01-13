(function($){
    bb.jquery.widget('ui.bbLinkSelector', {
        options: {
            callback: null
        },
        
        i18n: {
        },
        
        _templates: {
            panel: '#bb5-bbexterneLinks-tpl'
        },
        
        _context: {
            callback: null
        },
        
        _create: function() {
            var myself = this;
            this._templates.panel = bb.jquery(this._templates.panel).clone();
            var contentId = bb.Utils.generateId("externLinksSelector");
            bb.jquery(this._templates.panel).attr("id",contentId); 
            this.element.html(bb.jquery(this._templates.panel).show());
            bb.jquery(this.element).html(bb.jquery(this._templates.panel).html());
        },

        _init: function() {
            var myself = this,
            context = this.getContext();
            
            context.callback = this.options.callback;
            bb.jquery(this.element).find('.bb5-ico-select').bind('click', function() {
                var context = myself.getContext();
                if ( (bb.jquery(myself.element).find('#bb5-form001').val().length>0) && (bb.jquery(myself.element).find('#bb5-form002').val().length>0) ) {
                    if (context.callback) {
                        context.callback({
                            type: 'link',
                            uid: null,
                            title: bb.jquery(myself.element).find('#bb5-form002').val(),
                            value: bb.jquery(myself.element).find('#bb5-form001').val(),
                            target: '_blank',
                            data: null
                        });
                    }
                }
            });
            
            this.setContext(context);
            this._trigger('ready');
        },
        
        setCallback: function(callback) {
            if(typeof callback=="function"){
                var context = this.getContext();
                context.callback = callback;
                this.options.callback = callback;
                this.setContext(context);
            }
        },
                
        setContext: function(context) {
            return bb.jquery(this.element).data('context', bb.jquery.extend(bb.jquery(this.element).data('context'), context));
        },
        
        getContext: function() {
            return ( (typeof bb.jquery(this.element).data('context') != 'undefined') ? bb.jquery(this.element).data('context') : {} );
        },
        
        destroy: function(){
            bb.jquery(this.element).empty();
            
            bb.jquery.Widget.prototype.destroy.apply(this, arguments);
        }
    })
})(bb.jquery);