(function($){
    $.widget('ui.bbLinkSelector', {
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
            this._templates.panel = $(this._templates.panel).clone();
            var contentId = bb.Utils.generateId("externLinksSelector");
            $(this._templates.panel).attr("id",contentId); 
            this.element.html($(this._templates.panel).show());
            $(this.element).html($(this._templates.panel).html());
        },

        _init: function() {
            var myself = this,
            context = this.getContext();
            
            context.callback = this.options.callback;
            $(this.element).find('.bb5-ico-select').bind('click', function() {
                var context = myself.getContext();
                if ( ($(myself.element).find('#bb5-form001').val().length>0) && ($(myself.element).find('#bb5-form002').val().length>0) ) {
                    if (context.callback) {
                        context.callback({
                            type: 'link',
                            uid: null,
                            title: $(myself.element).find('#bb5-form002').val(),
                            value: $(myself.element).find('#bb5-form001').val(),
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
            return $(this.element).data('context', $.extend($(this.element).data('context'), context));
        },
        
        getContext: function() {
            return ( (typeof $(this.element).data('context') != 'undefined') ? $(this.element).data('context') : {} );
        },
        
        destroy: function(){
            $(this.element).empty();
            
            $.Widget.prototype.destroy.apply(this, arguments);
        }
    })
})(jQuery);