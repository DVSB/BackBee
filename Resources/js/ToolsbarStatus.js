/*StatusToolsbar*/

(function($) {

BB4.ToolsbarManager.register('statustb',{
    _settings: {
        toolsbarContainer: '#bb5-status',
        toolsbarEdition: '#bb5-edit-tabs-page',
		checkboxOnline: '#bb5-pageOnline',
		checkboxHidden: '#bb5-pageHidden',
		btnSavePage: '.bb5-ico-save',
		btnEditPage: '.bb5-ico-edit',
		btnRemovePage: '.bb5-ico-del',
		btnCommit: '.bb5-ico-commit',
		btnRevert: '.bb5-ico-cancel',
		switchOnline: '.bb5-switch-online',
		switchHidden: '.bb5-switch-hidden'
	},
	
	_events: {
	},
	
	_beforeCallbacks: {
	},
	
        _metadataDialog: null,
        
	_bindPrivateEvents : function() {
            var self = this;
            bb.jquery(this._settings.toolsbarContainer).unbind('page:onload').bind('page:onload', function(){ return self.onPageLoad(); });
            bb.jquery(this._settings.toolsbarContainer).find(this._settings.checkboxOnline).unbind('change').bind('change', function(e) { return self.onChangeOnline(); });
            bb.jquery(this._settings.toolsbarContainer).find(this._settings.checkboxHidden).unbind('change').bind('change', function(e) { return self.onChangeHidden(); });
            bb.jquery(this._settings.toolsbarContainer).find(this._settings.switchOnline).unbind('click').bind('click', function(e) { return self.onChangeOnline(); });
            bb.jquery(this._settings.toolsbarContainer).find(this._settings.switchHidden).unbind('click').bind('click', function(e) { bb.jquery(this).toggleClass('on'); return self.onChangeHidden( !bb.StatusManager.getInstance().getHidden() ); });
            bb.jquery(this._settings.toolsbarContainer).find(this._settings.btnSavePage).unbind('click').bind('click', function(e) { return self.onSavePage(); });
            bb.jquery(this._settings.toolsbarContainer).find(this._settings.btnEditPage).unbind('click').bind('click', function(e) { return self.onEditPage(); });
            bb.jquery(this._settings.toolsbarContainer).find(this._settings.btnRemovePage).unbind('click').bind('click', function(e) { return self.onRemovePage(); });
            bb.jquery(this._settings.toolsbarContainer).find(this._settings.btnCommit).unbind('click').bind('click', function(e) { alert("sd");return self.onCommit(); });
            bb.jquery(this._settings.toolsbarContainer).find(this._settings.btnRevert).unbind('click').bind('click', function(e) { return self.onRevert(); });

            bb.jquery(this._settings.toolsbarEdition).find(".bb5-tabdrawer-wrapper .bb5-tabdrawer-toggle").unbind('click').bind('click',function(){ return self.onTogglePublishingOptions(); });
            bb.jquery(this._settings.toolsbarEdition).find('.bb5-status-down').unbind('click').bind('click', function(e) { return self.onStateDown(); /*self.onChangeOnline(false);*/ });
            bb.jquery(this._settings.toolsbarEdition).find('.bb5-status-up').unbind('click').bind('click', function(e) { return self.onStateUp(); /*self.onChangeOnline(true);*/ });
            bb.jquery(this._settings.toolsbarEdition).find(this._settings.switchHidden).unbind('click').bind('click', function(e) { return self.onChangeHidden( !bb.StatusManager.getInstance().getHidden() ); });
            bb.jquery(this._settings.toolsbarEdition).find('.bb5-schedule-publishing-validate').unbind('click').bind('click', function(e) { self.onSchedule(); return self.onTogglePublishingOptions(); });
            bb.jquery(this._settings.toolsbarEdition).find('.bb5-metadata-edit').unbind('click').bind('click', function(e) { return self.onEditMetadata(); });
            
            bb.jquery(document).bind('locale.change', function(e) { return self.onSchedule(); });
	},
	
	_init: function(){                
		this._bindPrivateEvents();
                bb.jquery(this._settings.toolsbarEdition).find('.bb5-page-publishing-date').datetimepicker({
                    dateFormat: 'dd/mm/yy',
                    timeFormat: 'HH:mm',
                    beforeShow : bb.jquery.proxy(this._beforeShowDatepicker,this)
                });
                bb.jquery(this._settings.toolsbarEdition).find('.bb5-page-archiving-date').datetimepicker({
                    dateFormat: 'dd/mm/yy',
                    timeFormat: 'HH:mm',
                    beforeShow : bb.jquery.proxy(this._beforeShowDatepicker,this)
                });
	},
    
        onTogglePublishingOptions: function(){
            return bb.jquery(this._settings.toolsbarEdition).find(".bb5-tabdrawer-wrapper .bb5-tabdrawer-toggle")
                                                    .toggleClass('opened')
                                                    .parent()
                                                    .next()
                                                    .slideToggle();
        },
        
        _beforeShowDatepicker: function(dateField,dpInfos){
            bb.jquery(dpInfos.dpDiv).css('z-index', 901021);
            bb.jquery(dpInfos.dpDiv).addClass('bb5-ui bb5-dialog-wrapper');
        },
        
        _getMetadataForm: function() {
            var currentPage = bb.StatusManager.getInstance().getCurrentPage();
            var content = '<fieldset><legend>'+bb.i18n.__('toolbar.pagebrowser.title')+'</legend>';
            content += '<textarea id="bb5-meta-title">'+currentPage.title+'</textarea>';

            if (currentPage.metadata) {
                bb.jquery.each(currentPage.metadata, function(name, metadatas) {
                    content += '<fieldset><legend>'+name+'</legend>';
                    bb.jquery.each(metadatas, function(index, metadata) {
                        if ('name' != metadata.attr && 'property' != metadata.attr) {
                            if ('content' != metadata.attr) {
                                content += '<label for="bb5-meta-'+name+'-'+metadata.attr+'">'+metadata.attr+'</label>';
                            }
                            content += '<textarea id="bb5-meta-'+name+'-'+metadata.attr+'">'+metadata.value+'</textarea>';
                        }
                    });
                    content += '</fieldset>';
                });
            } else {
                content = '<p>'+bb.i18n.__('toolbar.editing.no_metadata')+'</p>';
            }
            
            return bb.jquery(content);
        },
        
        _postMetadataForm: function() {
            var currentPage = bb.StatusManager.getInstance().getCurrentPage();
            var upd_metadatas = {};
            
            if (currentPage.metadata) {
                bb.jquery.each(currentPage.metadata, function(name, metadatas) {
                    upd_metadatas[name] = new Array();
                    bb.jquery.each(metadatas, function(index, metadata) {
                        upd_metadatas[name][index] = {};
                        if (0 < bb.jquery('.bb5-dialog-metadata-editor #bb5-meta-'+name+'-'+metadata.attr).length) {
                            upd_metadatas[name][index].value = bb.jquery('.bb5-dialog-metadata-editor #bb5-meta-'+name+'-'+metadata.attr).val();
                            if (metadata.value != upd_metadatas[name][index].value)
                                upd_metadatas[name][index].iscomputed = false;
                        }
                    });
                });
                
                bb.StatusManager.getInstance().setMetadata( bb.jquery.extend(true, {}, currentPage.metadata, upd_metadatas) );
            }
            
            bb.StatusManager.getInstance().setTitle( bb.jquery('.bb5-dialog-metadata-editor #bb5-meta-title').val() );
        },
        
        onStateUp: function() {
            var self = this;
            var has_changed = false;
            var current_state;
            var workflow = bb.StatusManager.getInstance().getWorkflowStates();
            
            if (0 == workflow.length && !(currentPage.state & 1)) {
                return self.onChangeOnline(true);
            }
            
            if (currentPage.state & 1) {
                current_state = (null == currentPage.workflow_state) ? 0 : currentPage.workflow_state;
                bb.jquery.each(workflow, function(code, state) {
                    if (code > current_state) {
                        bb.jquery(self._settings.toolsbarEdition).find('.bb5-button-selector-status').attr('data-i18n', 'toolbar.editing.states.offline').empty().html(bb.i18n.__(state.label));
                        bb.StatusManager.getInstance().setWorkflowState(state);
                        has_changed = true;
                        return false;
                    }

                    return true;
                });
            } else {
                current_state = (null == currentPage.workflow_state) ? -1000000 : currentPage.workflow_state;
                bb.jquery.each(workflow, function(code, state) {
                    if (0 > code && code > current_state) {
                        bb.jquery(self._settings.toolsbarEdition).find('.bb5-button-selector-status').attr('data-i18n', 'toolbar.editing.states.offline').empty().html(bb.i18n.__(state.label));
                        bb.StatusManager.getInstance().setWorkflowState(state);
                        has_changed = true;
                        return false;
                    }

                    return true;
                });
                
                if (!has_changed) {
                    bb.StatusManager.getInstance().resetWorkflowState();
                    self.onChangeOnline(true);
                }
            }
        },
        
        onStateDown: function() {
            var self = this;
            var has_changed = false;
            var current_state;
            var workflow = bb.StatusManager.getInstance().getWorkflowStates();
            
            if (0 == workflow.length && (currentPage.state & 1)) {
                return self.onChangeOnline(false);
            }
            
            if (currentPage.state & 1) {
                current_state = (null == currentPage.workflow_state) ? 0 : currentPage.workflow_state;
                bb.jquery(self._settings.toolsbarContainer).find(self._settings.checkboxOnline).val([0]).parent().removeClass('on');
                
                bb.jquery.each(workflow, function(code, state) {
                    if (code < current_state) {
                        bb.jquery(self._settings.toolsbarEdition).find('.bb5-button-selector-status').attr('data-i18n', 'toolbar.editing.states.offline').empty().html(bb.i18n.__(state.label));
                        bb.StatusManager.getInstance().setOnline(code > 0);
                        bb.StatusManager.getInstance().setWorkflowState(state);
                        has_changed = (code > 0 || current_state == 0);
                    }

                    return true;
                });
            } else {
                current_state = (null == currentPage.workflow_state) ? -1000000 : currentPage.workflow_state;
                bb.jquery.each(workflow, function(code, state) {
                    if (0 > code && code < current_state) {
                        bb.jquery(self._settings.toolsbarEdition).find('.bb5-button-selector-status').attr('data-i18n', 'toolbar.editing.states.offline').empty().html(bb.i18n.__(state.label));
                        bb.StatusManager.getInstance().setWorkflowStatus(state);
                        has_changed = true;
                    }

                    return true;
                });
                
                if (!has_changed) {
                    bb.StatusManager.getInstance().resetWorkflowState();
                    self.onChangeOnline(false);
                }
            }
        },
        
	onChangeOnline: function(online) {
            if ('undefined' == typeof(online))
                online = (bb.jquery(this._settings.toolsbarContainer).find(this._settings.checkboxOnline+':checked').length == 0);

            if (!online) {
                bb.jquery(this._settings.toolsbarContainer).find(this._settings.checkboxOnline).val([0]).parent().removeClass('on');
                bb.jquery(this._settings.toolsbarEdition).find('.bb5-button-selector-status').attr('data-i18n', 'toolbar.editing.states.offline').empty().html(bb.i18n.__('toolbar.editing.states.offline'));
            } else {
                bb.jquery(this._settings.toolsbarContainer).find(this._settings.checkboxOnline).val([1]).parent().addClass('on');
                bb.jquery(this._settings.toolsbarEdition).find('.bb5-button-selector-status').attr('data-i18n', 'toolbar.editing.states.online').empty().html(bb.i18n.__('toolbar.editing.states.online'));
            }

            return bb.StatusManager.getInstance().setOnline(online);
	},
	
	onChangeHidden: function(hidden) {
            if ('undefined' == typeof(hidden))
                hidden = (bb.jquery(this._settings.toolsbarContainer).find(this._settings.checkboxHidden+':checked').length == 0);

            if (hidden) {
                bb.jquery(this._settings.toolsbarContainer).find(this._settings.checkboxHidden).val([2]).parent().removeClass('on');
                bb.jquery(this._settings.toolsbarEdition).find('.bb5-page-visible').removeClass('bb5-button-selected');
                bb.jquery(this._settings.toolsbarEdition).find('.bb5-page-hidden').addClass('bb5-button-selected');
            } else {
                bb.jquery(this._settings.toolsbarContainer).find(this._settings.checkboxHidden).val([0]).parent().addClass('on');
                bb.jquery(this._settings.toolsbarEdition).find('.bb5-page-visible').addClass('bb5-button-selected');
                bb.jquery(this._settings.toolsbarEdition).find('.bb5-page-hidden').removeClass('bb5-button-selected');
            }

            return bb.StatusManager.getInstance().setHidden(hidden);
	},
	
        onSchedule : function(publishing, archiving) {
            var publishingInput = bb.jquery(this._settings.toolsbarEdition).find('.bb5-page-publishing-date');
            var archivingInput = bb.jquery(this._settings.toolsbarEdition).find('.bb5-page-archiving-date');
            var schedulingInfos = bb.jquery(this._settings.toolsbarEdition).find('.bb5-scheduling');
            
            if (!publishing) publishing = publishingInput.datetimepicker('getDate');
            if (!archiving) archiving = archivingInput.datetimepicker('getDate');
            
            publishingInput.datetimepicker('setDate', new Date(publishing));
            archivingInput.datetimepicker('setDate', new Date(archiving));
            
            if (null == publishing) publishingInput.val('');
            if (null == archiving) archivingInput.val('');
            
            schedulingInfos.empty();
            if (null == publishing && null == archiving) {
                schedulingInfos.append(bb.i18n.__('toolbar.editing.no_scheduling'));
            } else {
                if (null != publishing) schedulingInfos.append(bb.i18n.__('toolbar.editing.scheduling_from', publishingInput.val()));
                if (null != publishing && null != archiving) schedulingInfos.append(' ');
                if (null != archiving) schedulingInfos.append(bb.i18n.__('toolbar.editing.scheduling_to', archivingInput.val()));
            }
            
            bb.StatusManager.getInstance().setPublishingDate( new Date(publishing) );
            bb.StatusManager.getInstance().setArchivingDate( new Date(archiving) );
        },
        
        onEditMetadata : function() {
            var self = this;
            if (null == this._metadataDialog) {
                var popupDialog = bb.PopupManager.init({});
                this._metadataDialog = popupDialog.create("metadataEditor",{
                    title: bb.i18n.__('toolbar.editing.SEO'),
                    buttons : {
                        save : {
                            text: bb.i18n.__('popupmanager.button.save'),
                            click: function(){
                                self._postMetadataForm();
                                self._metadataDialog.close();
                            }
                        },
                        cancel : {
                            text: bb.i18n.__('popupmanager.button.cancel'),
                            click: function(){
                                self._metadataDialog.close();
                                self._metadataDialog.setContent(self._getMetadataForm());
                            }
                        }
                    },
					
                    width: 520,
					height: 600,
                    maxHeigh : 600
                });
                this._metadataDialog.setContent(this._getMetadataForm());
            }
            
            self._metadataDialog.open();
        },
        
	onSavePage : function() {
		return bb.StatusManager.getInstance().update();
	},
	
	onEditPage : function() {
		return bb.StatusManager.getInstance().edit();
	},
	
	onRemovePage : function() {
        return bb.StatusManager.getInstance().remove();
	},
	
	onCommit : function() {
                bb.ContentWrapper.persist(false);//make persist synchronous request
		return bb.StatusManager.getInstance().commit();
	},
	
	onRevert : function() {
		return bb.StatusManager.getInstance().revert();
	},
	
	onPageLoad : function() {
            var self = this;
            currentPage = bb.StatusManager.getInstance().getCurrentPage();

            this.onChangeOnline( (currentPage.state & 1) );
            
            if (null != currentPage.workflow_state) {
                workflow = bb.StatusManager.getInstance().getWorkflowStates();
                bb.jquery.each(workflow, function(code, state) {
                    if (code == currentPage.workflow_state) {
                        bb.jquery(self._settings.toolsbarEdition).find('.bb5-button-selector-status').attr('data-i18n', 'toolbar.editing.states.offline').empty().html(bb.i18n.__(state.label));
                        return false;
                    }
                    return true;
                });
            }
            
            this.onChangeHidden( (currentPage.state & 2) );
            this.onSchedule ( bb.StatusManager.getInstance().getPublishingDate(), bb.StatusManager.getInstance().getArchivingDate() )
//		if (currentPage.state & 1)
//			bb.jquery(this._settings.toolsbarContainer).find(this._settings.switchOnline).trigger('click');
//		if (!(currentPage.state & 2))
//			bb.jquery(this._settings.toolsbarContainer).find(this._settings.checkboxHidden).trigger('click');
	}
});

}) (bb.jquery);
