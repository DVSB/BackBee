//@ sourceURL=ressources/js/bb.ui.bbPageBrowser.js
/*
 *Arborescence
 *
 *
 **/
(function($) {
    bb.jquery.widget('ui.bbPageBrowser', {
        options: {
            popup: {
                width: 200,
                height: null,
                position: null
            },
            site: null,
            breadcrumb: [],
            editMode: false,
            dialogClass: 'bb5-ui bb5-dialog-wrapper',
            title: "",
            enableNavigation: true,
            enableMultiSite: false,
            having_child: false
        },
        _statesWatchable: {
			width: 238,
			height: 210,
			left: 43,
			top: 85,
            open: false
        },
        i18n: {
            new_node: 'News page',
            multiple_selection: 'Multiple selection',
            create: 'Create',
            edit: 'Edit',
            rename: 'Rename',
            remove: 'Delete',
            ccp: 'Cut/Paste',
            cut: 'Cut',
            copy: 'Copy',
            paste: 'Paste',
            pastebf: "Paste before",
            pasteaft: "Paste after",
            flyto: 'Browse to',
            save: 'Save',
            close: 'Close',
            notice: 'Notice',
            error: 'Error',
            utils: "Outils"
        },
        _templates: {
        },
        _context: {
            site: null,
            selected: null,
            treeview: null,
            layouts: null,
            clipboard: null,
            pastemode: null,
            onclick: false
        },
        _popupDialog: null,
        _create: function() {
            var myself = this;

            this.setContext({
                site: null,
                selected: null,
                treeview: null,
                layouts: null,
                maxresult: 25,
                having_child: myself.options.having_child
            });

        },
        _init: function() {
            var myself = bb.StateManager.extend(this, 'uibbPageBrowser'),
                    context = this.getContext();

            // dialogs manager
            myself._popupDialog = bb.PopupManager.init({
                dialogSettings: {
                    modal: true
                }
            });
            //treeviewer
            if (this.options.popup) {
                bb.jquery(this.element).dialog({
                    dialogClass: 'bb5-ui bb5-dialog-wrapper bb5-dialog-treeviewer',
					position: [myself.getStateLeft(), myself.getStateTop()],
                    width: myself.getStateWidth(),
                    height: myself.getStateHeight(),
					minWidth: 238,
                    minHeight: 210,
                    autoOpen: myself.getStateOpen(),
                    closeOnEscape: false,
                    zIndex: 500001,
                    title: this.options.title,
                    create: function(event, ui) {
                        context = myself.getContext();
                        bb.jquery(event.target).parent().css('position', 'fixed');
                        var utilsMenu = bb.jquery("<span></span>").clone();
                        bb.jquery(utilsMenu).addClass("bb5-dialog-title-tools");
                        if (myself.options.editMode) {
                            var utilsBtn = bb.jquery("<a></a>").clone();
                            bb.jquery(utilsBtn).addClass("bb5-button-link bb5-button bb5-toolsbtn bb5-button-square bb5-invert").attr("href", "javascript:;").text(bb.i18n.__('pageselector.utils'));
                            utilsBtn.appendTo(bb.jquery(utilsMenu));

                            bb.jquery(event.target).parent().find('.ui-dialog-titlebar .ui-dialog-titlebar-close').before(utilsMenu);

                            bb.jquery(event.target).parent().find('a.bb5-toolsbtn').bind('click', function(e) {
                                if ((context.selected) && (context.treeview)) {
                                    context.treeview.jstree('show_contextmenu', bb.jquery(myself.element).find('#node_' + context.selected), e.pageX, e.pageY + 10);
                                }
                                return false;
                            });
                        }
                        
                        var havingChildren = bb.jquery("<div><input type='checkbox' class='bb5-having-child' />&nbsp;"+bb.i18n.__('toolbar.selector.having_child')+"</div>");
                        bb.jquery(event.target).prepend(havingChildren);
                        bb.jquery(event.target).find('input.bb5-having-child')
                                .attr('checked', context.having_child)
                                .off('change')
                                .on('change', function(e) {
                                    context = myself.getContext();
                                    context.having_child = bb.jquery(e.target).is(':checked');
                                    myself.setContext(context);
                                    myself._initTree(context.site);
                                });
                        
                        if (myself.options.enableMultiSite) {
                            var sitesMenu = bb.jquery("<select class='bb5-available-sites'><option value='' data-i18n='toolbar.selector.select_site'>Sélectionner un site ...</option></select>").clone();
                            bb.jquery(event.target).prepend(sitesMenu);
                            bb.webserviceManager.getInstance('ws_local_site').request('getBBSelectorList', {    
                                useCache:true,
                                cacheTags:["userSession"],
                                async : false, 
                                success: function(result) {
                                    context = myself.getContext();
                                    select = bb.jquery(myself.element).find('.bb5-available-sites').eq(0);

                                    //select change event
                                    select.bind('change', function() {
                                        myself._initTree(bb.jquery(this).val());
                                    });

                                    //select sites populating
                                    select.empty();
                                    bb.jquery.each(result.result, function(index, site) {
                                        var option = bb.jquery("<option></option>").clone();
                                        bb.jquery(option).attr("value",index).text(site);
                                        select.append(option);         
                                    });

                                    //select current site if configured
                                    if (null != context.site_uid) {
                                        select.val(context.site_uid);
                                    }

                                    select.trigger("change");

                                    myself._unmask();
                                    myself._trigger('ready');
                                }
                            });
                        }
                    }

                });

                /*jquery ui bug cf http://old.nabble.com/binding-the-dialog-resizeStop-event--after--dialog-creation-td25588022s27240.html*/
                // fixme position save the current dialog position
                bb.jquery(this.element).bind("dialogresizestop", function(event, ui) {
                    var position = [(Math.floor(ui.position.left) - bb.jquery(window).scrollLeft()), (Math.floor(ui.position.top) - bb.jquery(window).scrollTop())];
                    bb.jquery(event.target).parent().css('position', 'fixed');
                    bb.jquery(myself.element).dialog('option', 'position', position);
					
					myself.setStateWidth(ui.size.width);
					myself.setStateHeight(ui.size.height);
                });

                /*fix dialog position*/
                bb.jquery(this.element).bind("dialogclose", function(event, ui) {
                    myself.setStateOpen(false);
                    var context = myself.getContext();
                    context.previousDialogPosition = bb.jquery(this).dialog("option", "position");
                    myself.setContext(context);
                });

                bb.jquery(this.element).bind("dialogopen", function(event, ui) {
                    myself.setStateOpen(true);
                    var top = parseInt(bb.jquery(this).parent(".bb5-dialog-wrapper").css("top"));
                    if (top < 0) {
                        top = Math.abs(top);
                        var position = bb.jquery(myself.element).dialog('option', 'position');
                        position[1] = top;
                        bb.jquery(myself.element).dialog('option', 'position', position);
						myself.setStateTop(top);
                    }
                });

                bb.jquery(this.element).bind("dialogdragstop", function(event, ui) {
					myself.setStateTop(ui.position.top);
					myself.setStateLeft(ui.position.left);
					
                    var top = parseInt(bb.jquery(this).parent(".bb5-dialog-wrapper").css("top"));
                    /*move up*/
                    if (top < 0) {
                        top = Math.abs(top);
                        var position = bb.jquery(myself.element).dialog('option', 'position');
                        position[1] = top;
                        bb.jquery(myself.element).dialog('option', 'position', position);
						myself.setStateTop(top);
                        return;
                    }
                    /*move down*/
                    var dialogHeight = parseInt(myself.element.dialog("option", "height"));
                    var offsetTop = ui.offset.top;
                    var winHeight = bb.jquery(window).height();
                    var dialogCurrentTop = ui.position.top;
                    /*as dialog is fixed*/
                    var adjustPosition = ((dialogCurrentTop + dialogHeight) > winHeight) ? true : false;
                    if (adjustPosition) {
                        var adjustSize = dialogCurrentTop + dialogHeight - winHeight;
                        var dialogWrapper = bb.jquery(this).parent(".bb5-dialog-wrapper").eq(0);
                        var newTop = (dialogCurrentTop - adjustSize) - 15; // margin de 15px
                        bb.jquery(dialogWrapper).animate({
                            top: newTop + "px"
                        });
                    }
                });

                bb.jquery(document).ajaxComplete(function() {
                    myself._unmask();
                });
            } else {
                bb.jquery(this.element).show();
            }

            bb.webserviceManager.getInstance('ws_local_layout').request('getLayoutsFromSite', {
                useCache: true,
                cacheTags: ["userSession"],
                params: {
                    siteId: myself.options.site
                },
                success: function(result) {
                    context.layouts = result.result;
                    myself.setContext(context);
                    myself._initTree(myself.options.site);
                }
            });
            bb.jquery(this.element).disableSelection();
        },
        _initTree: function(site_uid) {
            var myself = this, self = this,
                    context = this.getContext();
            //tree
            this._destroyTree();
            context.site = null;

            var plugins = [
                'themes', 'rpc_data', 'ui', 'crrm', 'types', 'html_data'
            ];

            if (this.options.editMode) {
                plugins.push('dnd');
                plugins.push('contextmenu');
            }

            if ((site_uid) && (site_uid.length > 0)) {
                context.site = site_uid;
                context.treeview = bb.jquery(this.element).find('#browser').jstree({
                    plugins: plugins,
                    rpc_data: {
                        ajax: {
                            webservice: {
                                instance: bb.webserviceManager.getInstance('ws_local_page'),
                                method: 'getBBBrowserTree'
                            },
                            data: function(n) {
                                return {
                                    'site_uid': site_uid,
                                    'root_uid': n.attr ? n.attr('id').replace('node_', '') : null,
                                    'current_uid': bb.frontApplication.getPageId(),
                                    'fisrtresult': 0,
                                    'maxresult': context.maxresult,
                                    'having_child':context.having_child
                                };
                            }
                        }
                    },
                    types: {
                        valid_children: ['root'],
                        types: {
                            root: {
                                valid_children: ['default', 'folder'],
                                start_drag: false,
                                move_node: false,
                                delete_node: false,
                                remove: false
                            }
                        }
                    },
                    themes: {
                        theme: 'bb5',
                        dots: false
                    },
                    ui: {
                        select_limit: 1
                    },
                    core: {
                        strings: {
                            loading: bb.i18n.__('loading'),
                            new_node: bb.i18n.__('pageselector.new_node'),
                            multiple_selection: bb.i18n.__('pageselector.multiple_selection')
                        }
                    },
                    contextmenu: {
                        select_node: true,
                        show_at_node: false,
                        items: {
                            create: null,
                            rename: null,
                            remove: null,
                            edit: null,
                            ccp: null,
                            "bb5-context-menu-add": {
                                label: bb.i18n.__('popupmanager.button.create'),
                                action: function(obj) {
                                    if (obj)
                                        myself.editPage(null, obj.attr("id").replace("node_", ""));
                                },
                                separator_before: false,
                                separator_after: false,
                                icon: 'bb5-context-menu-add'
                            },
                            "bb5-context-menu-edit": {
                                label: bb.i18n.__('popupmanager.button.edit'),
                                action: function(obj) {
                                    var root = obj.parents('li:first');

                                    if (obj)
                                        myself.editPage(obj.attr("id").replace("node_", ""), (root.length > 0) ? root.attr("id").replace("node_", "") : null);
                                },
                                separator_before: false,
                                separator_after: false,
                                icon: 'bb5-context-menu-edit'
                            },
                            "bb5-context-menu-remove": {
                                label: bb.i18n.__('popupmanager.button.remove'),
                                icon: 'bb5-context-menu-remove',
                                action: function(obj) {
                                    if (obj)
                                        myself.removePage(obj);
                                }
                            },
                            "bb5-context-menu-copy": {
                                label: bb.i18n.__('popupmanager.button.copy'),
                                action: function(obj) {
                                    if (obj) {
                                        myself.handleClonePage(obj.attr("id").replace("node_", ""));
                                    }
                                },
                                separator_before: false,
                                separator_after: false,
                                icon: 'bb5-context-menu-copy'
                            },
                            "bb5-context-menu-cut": {
                                label: bb.i18n.__('popupmanager.button.cut'),
                                icon: "bb5-context-menu-cut",
                                action: function(obj) {
                                    if (obj)
                                        myself.cutPage(obj.attr("id").replace("node_", ""));
                                }
                            },
                            "bb5-context-menu-paste": {
                                label: bb.i18n.__('popupmanager.button.paste'),
                                icon: "bb5-context-menu-paste",
                                action: function(obj) {
                                    if (obj)
                                        myself.pastePage(obj.attr("id").replace("node_", ""), "inside");
                                }
                            },
                            "bb5-context-menu-paste-bf": {
                                label: bb.i18n.__('pageselector.pastebf'),
                                icon: "bb5-context-menu-paste",
                                action: function(obj) {
                                    if (obj)
                                        myself.pastePage(obj.attr("id").replace("node_", ""), "before");
                                }
                            },
                            "bb5-context-menu-paste-aft": {
                                label: bb.i18n.__('pageselector.pasteaft'),
                                icon: "bb5-context-menu-paste",
                                action: function(obj) {
                                    if (obj)
                                        myself.pastePage(obj.attr("id").replace("node_", ""), "after");
                                }
                            },
                            "bb5-context-menu-flyto": {
                                label: bb.i18n.__('pageselector.flyto'),
                                action: function(obj) {
                                    if (obj)
                                        myself.browsePage(obj.attr("id").replace("node_", ""), true);
                                },
                                separator_before: true,
                                separator_after: false,
                                icon: 'bb5-context-menu-flyto'
                            }
                        }
                    }
                }).bind('loaded.jstree', function(e, node) {
                    //selected node
                    if ((myself.options.breadcrumb) && (myself.options.breadcrumb.length > 0)) {
                        context.selected = myself.options.breadcrumb[myself.options.breadcrumb.length - 1];
                        bb.jquery(myself.element).find('#node_' + myself.options.breadcrumb[myself.options.breadcrumb.length - 1] + ' a:first').attr('class', 'jstree-clicked');
                        myself.setContext(context);
                    }

                    //disable specific actions for node
                    bb.jquery(document).live('context_show.vakata', function(e) {
                        var context = myself.getContext();
                        context.selected = bb.jquery.vakata.context.par.attr('id').replace('node_', '');
                        var treeContainer = context.treeview.jstree("get_container");
                        myself._trigger('select', e, {
                            node_id: context.selected
                        });
                        myself.setContext(context);
                        //root change
                        if (bb.jquery.vakata.context.par.attr('url') == '/') {
                            bb.jquery(bb.jquery.vakata.context.cnt).find('a[rel="bb5-context-menu-remove"]').parent("li").hide();
                            bb.jquery(bb.jquery.vakata.context.cnt).find('a[rel="bb5-context-menu-copy"]').parent("li").hide();
                            bb.jquery(bb.jquery.vakata.context.cnt).find('a[rel="bb5-context-menu-cut"]').parent("li").hide();
                            bb.jquery(bb.jquery.vakata.context.cnt).find('a[rel="bb5-context-menu-paste-bf"]').parent("li").hide();
                            bb.jquery(bb.jquery.vakata.context.cnt).find('a[rel="bb5-context-menu-paste-aft"]').parent("li").hide();
                        }

                        if (!("clipboard" in context) || (("clipboard" in context) && !context.clipboard)) {
                            bb.jquery(bb.jquery.vakata.context.cnt).find('a[rel="bb5-context-menu-paste"]').parent("li").hide();
                            bb.jquery(bb.jquery.vakata.context.cnt).find('a[rel="bb5-context-menu-paste-bf"]').parent("li").hide();
                            bb.jquery(bb.jquery.vakata.context.cnt).find('a[rel="bb5-context-menu-paste-aft"]').parent("li").hide();
                        }

                        if (("clipboard" in context) && (context.selected == context.clipboard)) {
                            bb.jquery(bb.jquery.vakata.context.cnt).find('a[rel="bb5-context-menu-copy"]').parent("li").hide();
                            bb.jquery(bb.jquery.vakata.context.cnt).find('a[rel="bb5-context-menu-cut"]').parent("li").hide();
                            bb.jquery(bb.jquery.vakata.context.cnt).find('a[rel="bb5-context-menu-paste"]').parent("li").hide();
                            bb.jquery(bb.jquery.vakata.context.cnt).find('a[rel="bb5-context-menu-paste-bf"]').parent("li").hide();
                            bb.jquery(bb.jquery.vakata.context.cnt).find('a[rel="bb5-context-menu-paste-aft"]').parent("li").hide();
                        }
                        /* ne pas coller un parent dans son enfant*/
                        var selectedIsChildOfClipboard = bb.jquery(treeContainer).find("#node_" + context.clipboard).find("#node_" + context.selected);
                        if (("clipboard" in context) && selectedIsChildOfClipboard.length != 0) {
                            bb.jquery(bb.jquery.vakata.context.cnt).find('a[rel="bb5-context-menu-paste"]').parent("li").hide();
                            bb.jquery(bb.jquery.vakata.context.cnt).find('a[rel="bb5-context-menu-paste-bf"]').parent("li").hide();
                            bb.jquery(bb.jquery.vakata.context.cnt).find('a[rel="bb5-context-menu-paste-aft"]').parent("li").hide();
                        }

                    });

                    myself._trigger('ready');
                }).bind('click.jstree', function(e) {
                    var context = myself.getContext();
                    if (bb.jquery(e.target).hasClass('nextresults')) {
                        bb.jquery(e.target).removeClass('dontmove');

                        // for next 25 results
                        if (context.onclick == true) {
                            return;
                        }
                        context.onclick = true;
                        var next_firstresult = bb.jquery(e.target).attr('data-nextresult');
                        var parent = bb.jquery(e.target).parent().parent().parent();
                        var parent_uid = bb.jquery(e.target).parent().parent().parent().attr('uid');
                        bb.webserviceManager.getInstance('ws_local_page').request('getBBBrowserTree', {
                            params: {
                                'site_uid': context.site,
                                'root_uid': parent_uid,
                                'current_uid': bb.frontApplication.getPageId(),
                                'fisrtresult': next_firstresult,
                                'maxresult': context.maxresult,
                                'nextresults': context.nextresults
                            },
                            success: function(response) {
                                context.onclick = false;
                                if (!response.result) {
                                    return;
                                }
                                else {
                                    bb.jquery.each(response.result.results, function(item, node) {
                                        context.treeview.jstree('create_node', bb.jquery(myself.element).find('#node_' + parent_uid), 'last', node);
                                    });
                                    bb.jquery(e.target).attr('data-nextresult', 1 * response.result.firstresult + 1 * response.result.maxresult);
                                    try {
                                        if (response.result.numresults < (1 * response.result.firstresult + 1 * response.result.maxresult)) {
                                            context.treeview.jstree('delete_node', bb.jquery(e.target));
                                        } else {
                                            context.treeview.jstree('move_node', bb.jquery(e.target), '#node_' + parent_uid, "last");
                                        }
                                    } catch (e) {
                                        // nothing to do
                                    }
                                }
                            }
                        });
                    } else if ((bb.jquery(e.target).parents('a:first').hasClass('jstree-clicked')) || (bb.jquery(e.target).hasClass('jstree-clicked'))) {
                        context.selected = bb.jquery(e.target).parents('li:first').attr('id').replace('node_', '');
                        myself._trigger('select', e, {
                            node_id: context.selected
                        });
                        myself.setContext(context);
                    }
                }).bind('dblclick.jstree', function(e) {
                    if (bb.jquery(e.target).hasClass('nextresults'))
                        return;
                    if (!myself.options.enableNavigation)
                        return;
                    if ((bb.jquery(e.target).parents('a:first').hasClass('jstree-clicked')) || (bb.jquery(e.target).hasClass('jstree-clicked'))) {
                        myself.browsePage(bb.jquery(e.target).parents('li:first').attr('id').replace('node_', ''), false);
                    }
                }).bind("create.jstree", function(e, data) {
                    bb.webserviceManager.getInstance('ws_local_page').request('insertBBBrowserTree', {
                        params: {
                            title: data.rslt.name,
                            root_uid: data.rslt.parent.attr("id").replace("node_", "")
                        },
                        success: function(result) {
                            if (!result.result)
                                bb.jquery.jstree.rollback(data.rlbk);
                            else {
                                bb.jquery(data.rslt.obj).attr('id', result.result.attr.id);
                                bb.jquery(data.rslt.obj).attr('rel', result.result.attr.rel);

                            }
                        }
                    });
                }).bind("rename.jstree", function(e, data) {
                    bb.webserviceManager.getInstance('ws_local_page').request('renameBBBrowserTree', {
                        params: {
                            title: data.rslt.new_name,
                            page_uid: data.rslt.obj.attr("id").replace("node_", "")
                        },
                        success: function(result) {
                            if (!result.result)
                                bb.jquery.jstree.rollback(data.rlbk);
                        }
                    });
                }).bind("delete.jstree", function(e, data) {
                    var context = myself.getContext();
                    if ((data) && !(data.rslt)) {
                        data = {
                            rslt: {
                                obj: bb.jquery(data)
                            }
                        };
                    }
                    if (data && data.rslt.obj) {
                        context.treeview.jstree('delete_node', data.rslt.obj);
                        bb.webserviceManager.getInstance('ws_local_page').request('delete', {
                            params: {
                                uid: data.rslt.obj.attr("id").replace("node_", "")
                            },
                            success: function(result) {
                                if (!result.result) {
                                    bb.jquery.jstree.rollback(data.rlbk);
                                }
                            }
                        });
                    } else {
                        if (data && data.rlbk)
                            bb.jquery.jstree.rollback(data.rlbk);
                    }
                    e.stopPropagation();
                }).bind("move_node.jstree", function(e, data) {
                    var widget = myself;
                    data.rslt.o.each(function(i) {
                        var myself = this;
                        var id = bb.jquery(myself).attr('id');
                        //remove from the dom if exitst the element's phantom after it has been pasted
                        var oldcontent = bb.jquery(this).parents(".jstree-bb5").eq(0).find("[id='" + id + "']").not(this);
                        if (oldcontent.length) {
                            bb.jquery(oldcontent).remove();
                        }
                        if (data.rslt.cr !== -1) {
                            if (bb.jquery(data.rslt.or).find('a').hasClass('nextresults')) {
                                bb.jquery.jstree.rollback(data.rlbk);
                                return;
                            }
                            if ('undefined' !== typeof (bb.jquery(myself).attr('id'))) {
                                widget._mask();
                                bb.webserviceManager.getInstance('ws_local_page').request('moveBBBrowserTree', {
                                    params: {
                                        page_uid: bb.jquery(myself).attr('id').replace('node_', ''),
                                        root_uid: data.rslt.np.attr('id').replace('node_', ''),
                                        next_uid: (((data.rslt.or.length == 0) ? null : data.rslt.or.attr('id').replace('node_', '')))
                                    },
                                    success: function(result) {
                                        if (!result.result)
                                            bb.jquery.jstree.rollback(data.rlbk);
                                        widget._unmask();
                                    },
                                    error: function() {
                                        widget._unmask();
                                    }
                                });
                            } else {
                                if (bb.jquery(myself).find('a').hasClass('dontmove')) {
                                    bb.jquery.jstree.rollback(data.rlbk);
                                    return;
                                } else {
                                    bb.jquery(myself).find('a').addClass('dontmove');
                                }
                            }
                        } else {
                            bb.jquery.jstree.rollback(data.rlbk);
                        }
                    });
                });
                window.tree = context.treeview;
                this.setContext(context);
            }
        },
        _destroyTree: function() {
            var context = this.getContext();

            if (context.treeview) {
                context.treeview.jstree('destroy');
                bb.jquery(this.element).find('#browser').children().remove();
                this.setContext(context);
            }
        },
        _showMessage: function(title, message, icon) {
            var myself = this,
                    context = this.getContext();

            if (!context.messageBox) {
                bb.jquery('body').append(bb.jquery('<div id="bb5-ui-bbpagebrowser-message"/>'));
                context.messageBox = bb.jquery('#bb5-ui-bbpagebrowser-message');
            }

            context.messageBox.html('<p><span class="ui-icon ui-icon-' + icon + '" style="float:left; margin:0 7px 50px 0;"></span>' + message + '</p>');

            context.messageBox.dialog({
                title: title,
                dialogClass: myself.options.dialogClass,
                autoOpen: true,
                width: 350,
                height: 'auto',
                modal: true,
                resizable: false,
                position: ['center', 'center'],
                buttons: {},
                close: function() {
                }
            });

            var buttons = {};
            buttons[bb.i18n.__('popupmanager.button.close')] = function() {
                context.messageBox.dialog('close');
            };

            context.messageBox.dialog('option', 'buttons', buttons);

            this.setContext(context);
        },
        _mask: function() {
            bb.jquery(this.element).parent().mask(bb.i18n.__('loading'));
        },
        _unmask: function() {
            bb.jquery(this.element).parent().unmask();
        },
        open: function() {
            if (this.options.popup) {
                bb.jquery(this.element).dialog('open');
            } else {
                bb.jquery(this.element).show();
            }

            this._trigger('open');
        },
        close: function() {
            if (this.options.popup) {
                bb.jquery(this.element).dialog('close');
            } else {
                bb.jquery(this.element).hide();


            }

            this._trigger('close');
        },
        destroy: function() {
            var context = this.getContext();

            this._destroyTree();

            context.site = null;

            //popin
            if (this.options.popup) {
                bb.jquery(this.element).dialog('destroy');
            } else {
                bb.jquery(this.element).hide();
            }

            bb.jquery.Widget.prototype.destroy.apply(this, arguments);

            this.setContext(context);
        },
        cutPage: function(page_uid) {
            var myself = this;
            var context = myself.getContext();
            context.clipboard = page_uid;
            context.pastemode = 'cut';
            myself.setContext(context);
        },
        handleClonePage: function(page_uid) {
            var context = this.getContext();
            context.clipboard = page_uid;
            context.pastemode = "copy";
            this.setContext(context);
        },
        clonePage: function(page_uid, callback) {
            var myself = this;
            var context = myself.getContext();
            var flyto = (typeof flyto == "boolean") ? flyto : true; //true by default
            myself.setContext(context);

            bb.webserviceManager.getInstance('ws_local_page').request('getBBSelectorForm', {
                params: {
                    page_uid: page_uid
                },
                success: function(result) {

                    bb.jquery('#bb-ui-bbpagebrowser-form #bb-ui-bbpagebrowser-form-layout').attr("disabled", true);
                    var buttons = {};
                    buttons[bb.i18n.__('popupmanager.button.save')] = function() {
                        var editDialog = this;
                        var title = (bb.jquery.trim(bb.jquery(editDialog).find('#bb-ui-bbpagebrowser-form-title').val()).length) ? bb.jquery.trim(bb.jquery(editDialog).find('#bb-ui-bbpagebrowser-form-title').val()) : false;
                        var errors = [];
                        if (!title) {
                            errors.push(bb.jquery(editDialog).find('#bb-ui-bbpagebrowser-form-title'));
                        }

                        if (errors.length) {
                            myself._showErrors(errors);
                            bb.jquery(editDialog).parents('.ui-dialog:first').unmask();
                            return;
                        }

                        bb.jquery(editDialog).parents('.ui-dialog:first').mask(bb.i18n.__('loading'));
                        bb.webserviceManager.getInstance('ws_local_page').request('cloneBBPage', {
                            params: {
                                page_uid: page_uid,
                                title: bb.jquery(editDialog).find('#bb-ui-bbpagebrowser-form-title').val(),
                                url: bb.jquery(editDialog).find('#bb-ui-bbpagebrowser-form-url').val(),
                                redirect: bb.jquery(editDialog).find('#bb-ui-bbpagebrowser-form-redirect').val(),
                                alttitle: bb.jquery(editDialog).find('#bb-ui-bbpagebrowser-form-alttitle').val()
                            },
                            success: function(response) {
                                bb.jquery(editDialog).dialog("close");
                                if (typeof callback == "function") {
                                    callback(response.result);
                                    return;
                                }
                                myself.browsePage(result.result.attr.id.replace('node_', '', 'i'), false);
                            },
                            error: function(result) {
                                bb.jquery(this).parents('.ui-dialog:first').unmask();
                                myself._showMessage(bb.i18n.__('toolbar.editing.error'), result.error.message, 'alert');
                            }
                        });
                    };
                    buttons[bb.i18n.__('popupmanager.button.close')] = function() {
                        bb.jquery(this).dialog("close");
                    };

                    var editDialog = myself._popupDialog.create("confirmDialog", {
                        title: ((!page_uid) ? bb.i18n.__('popupmanager.button.create') : bb.i18n.__('popupmanager.button.edit')),
                        buttons: buttons
                    });
                    bb.jquery(editDialog.dialog).html(bb.jquery('#bb-ui-bbpagebrowser-form').get(0).innerHTML);
                    bb.jquery(editDialog.dialog).find('#bb-ui-bbpagebrowser-form-title').val(result.result.title);
                    bb.jquery(editDialog.dialog).find('#bb-ui-bbpagebrowser-form-url').val(result.result.url);
                    bb.jquery(editDialog.dialog).find('#bb-ui-bbpagebrowser-form-redirect').val(result.result.redirect);
                    bb.jquery(editDialog.dialog).find('#bb-ui-bbpagebrowser-form-layout').val(result.result.layout_uid);
                    bb.jquery(editDialog.dialog).find('#bb-ui-bbpagebrowser-form-alttitle').val(result.result.alttitle);
                    bb.jquery(editDialog.dialog).find('#bb-ui-bbpagebrowser-form-url').attr("disabled", true);

                    editDialog.show();
                },
                error: function(result) {
                    myself._showMessage(bb.i18n.__('toolbar.editing.error'), result.error.message, 'alert');
                }
            });
        },
        pastePage: function(page_uid, position) {
            var myself = this;
            var position = position || false;
            var context = myself.getContext();
            if (context.clipboard && position) {
                /* handle cut/paste */
                if (context.pastemode == "cut") {
                    if ('inside' == position) {
                        /*node is always added as the first child of his parent*/
                        context.treeview.jstree('move_node', '#node_' + context.clipboard, '#node_' + page_uid, "first");
                    }

                    else if ("before" == position) {
                        context.treeview.jstree('move_node', '#node_' + context.clipboard, '#node_' + page_uid, position);
                    }

                    else if ("after" == position) {
                        context.treeview.jstree('move_node', '#node_' + context.clipboard, '#node_' + page_uid, position);
                    }
                }
                /*handle copy/paste */
                if (context.pastemode == "copy") {
                    this.clonePage(context.clipboard, function(newNode) {

                        /*first append to root then move */
                        var rootUid = "#node_" + bb.jquery("#node_" + page_uid).attr("rootuid");

                        /*new node*/
                        context.treeview.jstree('create_node', rootUid, 'first', newNode, function() {
                            //context.treeview.jstree("clear_node");
                            var newNodeId = bb.jquery("#" + newNode.attr.id);
                            if ('inside' == position) {
                                context.treeview.jstree('move_node', newNodeId, '#node_' + page_uid, 'first');
                            }
                            if ('before' == position) {
                                context.treeview.jstree('move_node', newNodeId, '#node_' + page_uid, 'before');
                            }
                            if ('after' == position) {
                                context.treeview.jstree('move_node', newNodeId, '#node_' + page_uid, 'after');
                            }
                        });


                    });
                }
                context.treeview.jstree("clear_node");
            }

            context.clipboard = null;
            context.pastemode = null;
            myself.setContext(context);
        },
        _showErrors: function(fieldWithErrors) {
            var context = this.getContext();
            if (bb.jquery.isArray(fieldWithErrors) && fieldWithErrors.length) {
                bb.jquery.each(fieldWithErrors, function(i, field) {
                    bb.jquery(field).addClass("hasError");
                    bb.jquery(field).unbind("focus.error").bind("focus.error", function(e) {
                        bb.jquery(e.target).removeClass("hasError");
                    });
                });
                context.formHasError = true;
                this.setContext(context);
            }

        },
        editPage: function(page_uid, root_uid, callback, flag) {
            var myself = this,
                    context = this.getContext(),
                    flag_value = 'contextual';
            if (flag) {
                flag_value = flag;
            }

            if (!context.layouts) {
                bb.webserviceManager.getInstance('ws_local_layout').request('getLayoutsFromSite', {
                    params: {
                        siteId: myself.options.site
                    },
                    success: function(result) {
                        context.layouts = result.result;
                        myself.setContext(context);
                    }
                });
            }

            bb.webserviceManager.getInstance('ws_local_page').request('getBBSelectorForm', {
                params: {
                    page_uid: page_uid
                },
                success: function(result) {
                    bb.jquery('#bb-ui-bbpagebrowser-form #bb-ui-bbpagebrowser-form-layout').attr("disabled", false);
                    bb.jquery('#bb-ui-bbpagebrowser-form #bb-ui-bbpagebrowser-form-layout').empty();
                    bb.jquery('#bb-ui-bbpagebrowser-form #bb-ui-bbpagebrowser-form-layout').append(bb.jquery('<option/>').val("").text(""));
                    bb.jquery.each(context.layouts, function(index, layout) {
                        bb.jquery('#bb-ui-bbpagebrowser-form #bb-ui-bbpagebrowser-form-layout').append(bb.jquery('<option/>').val(layout.uid).text(layout.templateTitle));
                    });

                    bb.jquery('#bb-ui-bbpagebrowser-form #bb-ui-bbpagebrowser-form-target').val(result.target);
                    var buttons = {};
                    buttons[bb.i18n.__('popupmanager.button.save')] = function() {
                        var editDialog = this;
                        bb.jquery(editDialog).parents('.ui-dialog:first').mask(bb.i18n.__('loading'));

                        /*handle error: title can't be empty && handle error layout can't be empty*/
                        var title = (bb.jquery.trim(bb.jquery(editDialog).find('#bb-ui-bbpagebrowser-form-title').val()).length) ? bb.jquery.trim(bb.jquery(editDialog).find('#bb-ui-bbpagebrowser-form-title').val()) : false;
                        var selectedLayout = (bb.jquery.trim(bb.jquery(editDialog).find('#bb-ui-bbpagebrowser-form-layout').val()).length) ? bb.jquery.trim(bb.jquery(editDialog).find('#bb-ui-bbpagebrowser-form-layout').val()) : false;
                        /* fixme*/
                        var errors = [];
                        if (!title) {
                            errors.push(bb.jquery(editDialog).find('#bb-ui-bbpagebrowser-form-title'));
                        }
                        if (!selectedLayout) {
                            errors.push(bb.jquery(editDialog).find('#bb-ui-bbpagebrowser-form-layout'));
                        }
                        if (errors.length) {
                            myself._showErrors(errors);
                            bb.jquery(editDialog).parents('.ui-dialog:first').unmask();
                            return;
                        }



                        bb.webserviceManager.getInstance('ws_local_page').request('postBBSelectorForm', {
                            params: {
                                page_uid: page_uid,
                                root_uid: root_uid,
                                title: title,
                                url: bb.jquery(editDialog).find('#bb-ui-bbpagebrowser-form-url').val(),
                                target: bb.jquery(editDialog).find('#bb-ui-bbpagebrowser-form-target').val(),
                                redirect: bb.jquery(editDialog).find('#bb-ui-bbpagebrowser-form-redirect').val(),
                                layout_uid: selectedLayout,
                                alttitle: bb.jquery(editDialog).find('#bb-ui-bbpagebrowser-form-alttitle').val(),
                                flag: flag_value
                            },
                            success: function(result) {
                                if (page_uid === null) {
                                    context.treeview.jstree('create_node', bb.jquery(myself.element).find('#node_' + root_uid), 'first', result.result);
                                    bb.jquery(myself.element).find('#' + result.result.attr.id + ' a ins').addClass('bb5-jstree-offline bb5-jstree-hidden');
                                } else {
                                    context.treeview.jstree('rename_node', bb.jquery(myself.element).find('#node_' + page_uid), result.result.data);
                                }

                                bb.jquery(editDialog).parents('.ui-dialog:first').unmask();
                                bb.jquery(editDialog).dialog("close");

                                if (callback) {
                                    callback(result.result);
                                }
                            },
                            error: function(result) {
                                bb.jquery(this).parents('.ui-dialog:first').unmask();
                                myself._showMessage(bb.i18n.__('toolbar.editing.error'), result.error.message, 'alert');
                            }
                        });
                    };
                    buttons[bb.i18n.__('popupmanager.button.close')] = function() {
                        bb.jquery(this).dialog("close");
                    };

                    var editDialog = myself._popupDialog.create("confirmDialog", {
                        title: ((!page_uid) ? bb.i18n.__('popupmanager.button.create') : bb.i18n.__('popupmanager.button.edit')),
                        buttons: buttons
                    });
                    bb.jquery(editDialog.dialog).html(bb.jquery('#bb-ui-bbpagebrowser-form').get(0).innerHTML);
                    bb.jquery(editDialog.dialog).find('#bb-ui-bbpagebrowser-form-title').val(result.result.title);
                    bb.jquery(editDialog.dialog).find('#bb-ui-bbpagebrowser-form-url').val(result.result.url);
                    bb.jquery(editDialog.dialog).find('#bb-ui-bbpagebrowser-form-target').val(result.result.target);
                    bb.jquery(editDialog.dialog).find('#bb-ui-bbpagebrowser-form-redirect').val(result.result.redirect);
                    bb.jquery(editDialog.dialog).find('#bb-ui-bbpagebrowser-form-layout').val(result.result.layout_uid);
                    bb.jquery(editDialog.dialog).find('#bb-ui-bbpagebrowser-form-alttitle').val(result.result.alttitle);
                    bb.jquery(editDialog.dialog).find('#bb-ui-bbpagebrowser-form-url').attr("disabled", true);

                    editDialog.show();
                },
                error: function(result) {
                    myself._showMessage(bb.i18n.__('toolbar.editing.error'), result.error.message, 'alert');
                }
            });
        },
        removePage: function(page) {
            var myself = this;
            var page = page;
            if ('string' == typeof (page))
                page = bb.jquery(myself.element).find('#node_' + page)

            if ('undefined' != typeof (myself._confirmRemoveDialog)) {
                myself._confirmRemoveDialog.destroy();
            }

            var alertDialog = bb.PopupManager.init({});
            myself._confirmRemoveDialog = alertDialog.create('confirmDialog', {
                dialogType: alertDialog.dialogType.CONFIRMATION,
                title: 'Page removal',
                buttons: {
                    "Confirm": function() {
                        if (bb.jquery(page).attr('uid') === bb.frontApplication.getPageId()) {
                            var pageWebservice = bb.webserviceManager.getInstance('ws_local_page');
                            pageWebservice.request('delete', {
                                params: {
                                    uid: bb.frontApplication.getPageId()
                                },
                                success: function(response) {
                                    bb.jquery(this).dialog("close");
                                    if (response.result) {
                                        document.location = bb.baseurl + response.result.url;
                                    } else {
                                        _displayError('Unable to remove current page', null, _remove);
                                    }
                                },
                                error: function(response) {
                                    bb.jquery(confirmDialog).dialog("close");
                                    _displayError('Unable to remove current page', response.error, _remove);
                                }
                            });
                        } else {
                            bb.jquery(this).dialog('close');
                            myself.getContext().treeview.trigger('delete.jstree', page);
                        }
                    },
                    "Cancel": function() {
                        bb.jquery(this).dialog('close');
                    }
                }
            });
            bb.jquery(myself._confirmRemoveDialog.dialog).empty().html('Delete selected page ?');

            myself._confirmRemoveDialog.show();
        },
        browsePage: function(page_uid, redirect) {
            this._mask();
            bb.webserviceManager.getInstance('ws_local_page').request('getBBSelectorForm', {
                params: {
                    page_uid: page_uid
                },
                success: function(result) {
                    if (result.result) {
                        var href = bb.baseurl + ((result.result.url.indexOf('/', 0) == 0) ? result.result.url.substring(1, result.result.url.length) : result.result.url);
                        if (result.result.url.lastIndexOf('/') != (result.result.url.length - 1)) {
                            href += '.html';
                        }

                        var parameters = [];
                        if (false == redirect) {
                            parameters.push('bb5-redirect=false');
                        }

                        document.location.href = href + ((0 != parameters.length) ? '?' + parameters.join('&') : '');
                    }
                }
            });
        },
        setContext: function(context) {
            return bb.jquery(this.element).data('context', bb.jquery.extend(bb.jquery(this.element).data('context'), context));
        },
        getContext: function() {
            return ((typeof bb.jquery(this.element).data('context') != 'undefined') ? bb.jquery(this.element).data('context') : {});
        }
    })
})(bb.jquery);
