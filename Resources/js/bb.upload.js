var bb = (bb) ? bb : {};

bb.upload = {};

$.extend(bb.upload, {
    token: null,
    endPoint: null,
    namespace: null,
    
    setup: function(params) {
        this.token = params.token;
        this.endPoint = params.endPoint;
        this.namespace = params.namespace;
        return this;
    },
    
    setToken: function(token) {
        this.token = token;
    },
    
    upload: function(method, config) {
        var dropbox = $('<div/>');
        $('body').prepend(dropbox);
        
        dropbox.filedrop($.extend(config, {
            url: this.endPoint,
            
            data: $.extend(config.data, {
                method: this.namespace + '.' + method
            }),
            
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-BB-METHOD': 'Upload',
                'X-BB-AUTH': bb.authmanager.getToken(),
                X_BB_TOKEN: this.token
            }
        }));
        
        $('#' + config.fallback_id).trigger('change');
        
        dropbox.filedrop('destroy');
        dropbox.remove();
    },
    
    filedrop: function(method, config, dropbox_el) {
        var dropbox = $(dropbox_el) ? $(dropbox_el) : $('body').prepend($('<div>'));
        
        return dropbox.filedrop($.extend(config, {
            url: this.endPoint,
            
            data: $.extend(config.data, {
                method: this.namespace + '.' + method
            }),
            
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-BB-METHOD': 'Upload',
                'X-BB-AUTH': bb.authmanager.getToken(),
                X_BB_TOKEN: this.token
            }
        }));
    }
});

bb.uploadManager = {};

$.extend(bb.uploadManager, {
    uploads: {},
    
    setup: function(config) {
        var myself = this;
        
        $.each(config.uploads, function(index, upload) {
            myself.uploads[upload.name] = {};
            myself.uploads[upload.name] = $.extend({}, {}, bb.upload);

            myself.uploads[upload.name] = myself.uploads[upload.name].setup({
                token: config.token,
                endPoint: (bb.baseurl ? bb.baseurl : '')+config.endPoint,
                namespace:  upload.namespace
            });
        });
    },
    
    setToken: function(token) {
        $.each(this.uploads, function(index, upload) {
            upload.setToken(token);
        });
    },
    
    getInstance: function(name) {
        return this.uploads[name];
    }
});