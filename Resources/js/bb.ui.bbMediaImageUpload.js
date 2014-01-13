(function($){
    bb.jquery.widget('ui.bbMediaImageUpload', {
        options: {
            media_uid: null,
            media_classname: null
        },
        
        i18n: {
            upload_browser_not_supported: 'Your browser does not support HTML5 file uploads!',
            upload_too_many_file: 'Too many files!',
            upload_file_too_large: ' is too large!',
            upload_only_image_allowed: 'Only images are allowed!'
        },
        
        _templates: {
        },
        
        _context: {
        },
        
        _create: function() {
            var myself = this;
        },

        _init: function() {
            var myself = this;
            var dropbox = bb.jquery(this.element);
            bb.uploadManager.getInstance('ws_local_media').filedrop('uploadImage', {
                paramname: 'image',
                maxfiles: 1,
                maxfilesize: bb.config.maxFileSize,
                data: {
                },
        
                uploadFinished:function(i, file, response) {
                    if (!response.error) {
                        bb.webserviceManager.getInstance('ws_local_media').request('postBBMediaUpload', {
                            params: {
                                media_uid: myself.options.media_uid,
                                media_classname: myself.options.media_classname,
                                content_values: JSON.stringify(response.result)
                            },
                
                            success: function(result) {
                                myself._trigger('uploadFinishedSuccess', file, result);
                            },
                            
                            error: function(result) {
                                dropbox.empty();
                                myself._trigger('uploadFinishedError', file, result);
                            }
                        });
                        
                    } else {
                        dropbox.empty();
                        myself._trigger('uploadFinishedError', file, response);
                    }
                },
		
                error: function(err, file) {
                    switch(err) {
                        case 'BrowserNotSupported':
                            myself._trigger('uploadError', file, bb.i18n.__('toolbar.editing.upload_browser_not_supported'));
                            break;
                        case 'TooManyFiles':
                            myself._trigger('uploadError', file, bb.i18n.__('toolbar.editing.upload_too_many_file'));
                            break;
                        case 'FileTooLarge':
                            myself._trigger('uploadError', file, bb.i18n.__('toolbar.editing.upload_file_too_large'));
                            break;
                        default:
                            break;
                    }
                },
                        
                beforeEach: function(file) {
                    if(!file.type.match(/^image\//)) {
                        myself._trigger('uploadError', file, bb.i18n.__('toolbar.editing.upload_only_image_allowed'));
                        return false;
                    }
                },
		
                uploadStarted:function(i, file, len) {
                   myself._createPreview(file, dropbox);
                   myself._trigger('uploadStarted', i, file, len);
                },
		
                progressUpdated: function(i, file, progress) {
                    myself._trigger('progressUpdated', i, file, progress);
                },
        
                dragOver: function(e) {
                    myself._trigger('dragOver', e);
                },
        
                dragLeave: function(e) {
                    myself._trigger('dragLeave', e);
                },
        
                drop: function(e) {
                    myself._trigger('drop', e);
                }
    	 
            }, dropbox);
                    
            this._trigger('ready');
        },
        
        _createPreview: function(file, dropbox) {
        },
        
        destroy: function() {
            bb.jquery(this.element).unbind('drop').unbind('dragenter').unbind('dragover').unbind('dragleave');
            bb.jquery(document).unbind('drop').unbind('dragenter').unbind('dragover').unbind('dragleave');
            
            bb.jquery.Widget.prototype.destroy.apply(this, arguments);
        }
    })
})(bb.jquery);
