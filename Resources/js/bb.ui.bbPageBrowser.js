/*
 *Arborescence
 *
 *
 **/
(function($) {
    $.widget('ui.bbPageBrowser', {
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
            enableNavigation: true
        },
        _statesWatchable: {
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
                maxresult: 25
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
                $(this.element).dialog({
                    dialogClass: 'bb5-ui bb5-dialog-wrapper bb5-dialog-treeviewer',
                    width: this.options.popup.width,
                    minWidth: 200,
                    minHeight: 147,
                    autoOpen: myself.getStateOpen(),
                    closeOnEscape: false,
                    zIndex: 500001,
                    title: this.options.title,
                    create: function(event, ui) {
                        context = myself.getContext();
                        $(event.target).parent().css('position', 'fixed');
                        var utilsMenu = $("<span></span>").clone();
                        $(utilsMenu).addClass("bb5-dialog-title-tools");
                        if (myself.options.editMode) {
                            var utilsBtn = $("<a></a>").clone();
                            $(utilsBtn).addClass("bb5-button-link bb5-button bb5-toolsbtn bb5-button-square bb5-invert").attr("href", "javascript:;").text(bb.i18n.__('pageselector.utils'));
                            utilsBtn.appendTo($(utilsMenu));

                            $(event.target).parent().find('.ui-dialog-titlebar .ui-dialog-titlebar-close').before(utilsMenu);

                            $(event.target).parent().find('a.bb5-toolsbtn').bind('click', function(e) {
                                if ((context.selected) && (context.treeview)) {
                                    context.treeview.jstree('show_contextmenu', $(myself.element).find('#node_' + context.selected), e.pageX, e.pageY + 10);
                                }
                                return false;
                            });
                        }

                    }

                });

                /*jquery ui bug cf http://old.nabble.com/binding-the-dialog-resizeStop-event--after--dialog-creation-td25588022s27240.html*/
                // fixme position save the current dialog position
                $(this.element).bind("dialogresizestop", function(event, ui) {
                    var position = [(Math.floor(ui.position.left) - $(window).scrollLeft()), (Math.floor(ui.position.top) - $(window).scrollTop())];
                    $(event.target).parent().css('position', 'fixed');
                    $(myself.element).dialog('option', 'position', position);
                });

                /*fix dialog position*/
                $(this.element).bind("dialogclose", function(event, ui) {
                    myself.setStateOpen(false);
                    var context = myself.getContext();
                    context.previousDialogPosition = $(this).dialog("option", "position");
                    myself.setContext(context);
                });

                $(this.element).bind("dialogopen", function(event, ui) {
                    myself.setStateOpen(true);
                    var top = parseInt($(this).parent(".bb5-dialog-wrapper").css("top"));
                    if (top < 0) {
                        top = Math.abs(top);
                        var position = $(myself.element).dialog('option', 'position');
                        position[1] = top;
                        $(myself.element).dialog('option', 'position', position);
                    }
                });

                $(this.element).bind("dialogdragstop", function(event, ui) {
                    var top = parseInt($(this).parent(".bb5-dialog-wrapper").css("top"));
                    /*move up*/
                    if (top < 0) {
                        top = Math.abs(top);
                        var position = $(myself.element).dialog('option', 'position');
                        position[1] = top;
                        $(myself.element).dialog('option', 'position', position);
                        return;
                    }
                    /*move down*/
                    var dialogHeight = parseInt(myself.element.dialog("option", "height"));
                    var offsetTop = ui.offset.top;
                    var winHeight = $(window).height();
                    var dialogCurrentTop = ui.position.top;
                    /*as dialog is fixed*/
                    var adjustPosition = ((dialogCurrentTop + dialogHeight) > winHeight) ? true : false;
                    if (adjustPosition) {
                        var adjustSize = dialogCurrentTop + dialogHeight - winHeight;
                        var dialogWrapper = $(this).parent(".bb5-dialog-wrapper").eq(0);
                        var newTop = (dialogCurrentTop - adjustSize) - 15; // margin de 15px
                        $(dialogWrapper).animate({
                            top: newTop + "px"
                        });
                    }
                });

                $(document).ajaxComplete(function() {
                    myself._unmask();
                });
                if (this.options.popup.height)
                    $(this.element).dialog('option', 'height', this.options.popup.height);

                if (this.options.popup.position)
                    $(this.element).dialog('option', 'position', this.options.popup.position);
            } else {
                $(this.element).show();
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
            $(this.element).disableSelection();
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
                context.treeview = $(this.element).find('#browser').jstree({
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
                                    'maxresult': context.maxresult
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
                        $(myself.element).find('#node_' + myself.options.breadcrumb[myself.options.breadcrumb.length - 1] + ' a:first').attr('class', 'jstree-clicked');
                        myself.setContext(context);
                    }

                    //disable specific actions for node
                    $(document).live('context_show.vakata', function(e) {
                        var context = myself.getContext();
                        context.selected = $.vakata.context.par.attr('id').replace('node_', '');
                        var treeContainer = context.treeview.jstree("get_container");
                        myself._trigger('select', e, {
                            node_id: context.selected
                        });
                        myself.setContext(context);
                        //root change
                        if ($.vakata.context.par.attr('url') == '/') {
                            $($.vakata.context.cnt).find('a[rel="bb5-context-menu-remove"]').parent("li").hide();
                            $($.vakata.context.cnt).find('a[rel="bb5-context-menu-copy"]').parent("li").hide();
                            $($.vakata.context.cnt).find('a[rel="bb5-context-menu-cut"]').parent("li").hide();
                            $($.vakata.context.cnt).find('a[rel="bb5-context-menu-paste-bf"]').parent("li").hide();
                            $($.vakata.context.cnt).find('a[rel="bb5-context-menu-paste-aft"]').parent("li").hide();
                        }

                        if (!("clipboard" in context) || (("clipboard" in context) && !context.clipboard)) {
                            $($.vakata.context.cnt).find('a[rel="bb5-context-menu-paste"]').parent("li").hide();
                            $($.vakata.context.cnt).find('a[rel="bb5-context-menu-paste-bf"]').parent("li").hide();
                            $($.vakata.context.cnt).find('a[rel="bb5-context-menu-paste-aft"]').parent("li").hide();
                        }

                        if (("clipboard" in context) && (context.selected == context.clipboard)) {
                            $($.vakata.context.cnt).find('a[rel="bb5-context-menu-copy"]').parent("li").hide();
                            $($.vakata.context.cnt).find('a[rel="bb5-context-menu-cut"]').parent("li").hide();
                            $($.vakata.context.cnt).find('a[rel="bb5-context-menu-paste"]').parent("li").hide();
                            $($.vakata.context.cnt).find('a[rel="bb5-context-menu-paste-bf"]').parent("li").hide();
                            $($.vakata.context.cnt).find('a[rel="bb5-context-menu-paste-aft"]').parent("li").hide();
                        }
                        /* ne pas coller un parent dans son enfant*/
                        var selectedIsChildOfClipboard = $(treeContainer).find("#node_" + context.clipboard).find("#node_" + context.selected);
                        if (("clipboard" in context) && selectedIsChildOfClipboard.length != 0) {
                            $($.vakata.context.cnt).find('a[rel="bb5-context-menu-paste"]').parent("li").hide();
                            $($.vakata.context.cnt).find('a[rel="bb5-context-menu-paste-bf"]').parent("li").hide();
                            $($.vakata.context.cnt).find('a[rel="bb5-context-menu-paste-aft"]').parent("li").hide();
                        }

                    });

                    myself._trigger('ready');
                }).bind('click.jstree', function(e) {
                    var context = myself.getContext();
                    if ($(e.target).hasClass('nextresults')) {
                        // for next 25 results
                        if (context.onclick == true) {
                            return;
                        }
                        context.onclick = true;
                        var next_firstresult = $(e.target).attr('data-nextresult');
                        var parent = $(e.target).parent().parent().parent();
                        var parent_uid = $(e.target).parent().parent().parent().attr('uid');
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
                                    $.each(response.result.results, function(item, node) {
                                        context.treeview.jstree('create_node', $(myself.element).find('#node_' + parent_uid), 'last', node);
                                    });
                                    $(e.target).attr('data-nextresult', 1 * response.result.firstresult + 1 * response.result.maxresult);
                                    try {
                                        if (response.result.numresults < (1 * response.result.firstresult + 1 * response.result.maxresult)) {
                                            context.treeview.jstree('delete_node', $(e.target));
                                        } else {
                                            context.treeview.jstree('move_node', $(e.target), '#node_' + parent_uid, "last");
                                        }
                                    } catch (e) {
                                        // nothing to do
                                    }
                                }
                            }
                        });
                    } else if (($(e.target).parents('a:first').hasClass('jstree-clicked')) || ($(e.target).hasClass('jstree-clicked'))) {
                        context.selected = $(e.target).parents('li:first').attr('id').replace('node_', '');
                        myself._trigger('select', e, {
                            node_id: context.selected
                        });
                        myself.setContext(context);
                    }
                }).bind('dblclick.jstree', function(e) {
                    if ($(e.target).hasClass('nextresults'))
                        return;
                    if (!myself.options.enableNavigation)
                        return;
                    if (($(e.target).parents('a:first').hasClass('jstree-clicked')) || ($(e.target).hasClass('jstree-clicked'))) {
                        myself.browsePage($(e.target).parents('li:first').attr('id').replace('node_', ''), false);
                    }
                }).bind("create.jstree", function(e, data) {
                    bb.webserviceManager.getInstance('ws_local_page').request('insertBBBrowserTree', {
                        params: {
                            title: data.rslt.name,
                            root_uid: data.rslt.parent.attr("id").replace("node_", "")
                        },
                        success: function(result) {
                            if (!result.result)
                                $.jstree.rollback(data.rlbk);
                            else {
                                $(data.rslt.obj).attr('id', result.result.attr.id);
                                $(data.rslt.obj).attr('rel', result.result.attr.rel);

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
                                $.jstree.rollback(data.rlbk);
                        }
                    });
                }).bind("delete.jstree", function(e, data) {
                    var context = myself.getContext();
                    if ((data) && !(data.rslt)) {
                        data = {
                            rslt: {
                                obj: $(data)
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
                                    $.jstree.rollback(data.rlbk);
                                }
                            }
                        });
                    } else {
                        if (data && data.rlbk)
                            $.jstree.rollback(data.rlbk);
                    }
                    e.stopPropagation();
                }).bind("move_node.jstree", function(e, data) {

                    var widget = myself;
                    data.rslt.o.each(function(i) {
                        var myself = this;
                        var id = $(myself).attr('id');
                        //remove from the dom if exitst the element's phantom after it has been pasted
                        var oldcontent = $(this).parents(".jstree-bb5").eq(0).find("[id='" + id + "']").not(this);
                        if (oldcontent.length) {
                            $(oldcontent).remove();
                        }
                        if (data.rslt.cr !== -1) {
                            widget._mask();
                            bb.webserviceManager.getInstance('ws_local_page').request('moveBBBrowserTree', {
                                params: {
                                    page_uid: $(myself).attr('id').replace('node_', ''),
                                    root_uid: data.rslt.np.attr('id').replace('node_', ''),
                                    next_uid: (((data.rslt.or.length == 0) ? null : data.rslt.or.attr('id').replace('node_', '')))
                                },
                                success: function(result) {
                                    if (!result.result)
                                        $.jstree.rollback(data.rlbk);
                                    widget._unmask();
                                },
                                error: function() {
                                    widget._unmask();
                                }
                            });
                        } else {
                            $.jstree.rollback(data.rlbk);
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
                $(this.element).find('#browser').children().remove();
                this.setContext(context);
            }
        },
        _showMessage: function(title, message, icon) {
            var myself = this,
                    context = this.getContext();

            if (!context.messageBox) {
                $('body').append($('<div id="bb5-ui-bbpagebrowser-message"/>'));
                context.messageBox = $('#bb5-ui-bbpagebrowser-message');
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
            $(this.element).parent().mask(bb.i18n.__('loading'));
        },
        _unmask: function() {
            $(this.element).parent().unmask();
        },
        open: function() {
            if (this.options.popup) {
                $(this.element).dialog('open');
            } else {
                $(this.element).show();
            }

            this._trigger('open');
        },
        close: function() {
            if (this.options.popup) {
                $(this.element).dialog('close');
            } else {
                $(this.element).hide();


            }

            this._trigger('close');
        },
        destroy: function() {
            var context = this.getContext();

            this._destroyTree();

            context.site = null;

            //popin
            if (this.options.popup) {
                $(this.element).dialog('destroy');
            } else {
                $(this.element).hide();
            }

            $.Widget.prototype.destroy.apply(this, arguments);

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

                    $('#bb-ui-bbpagebrowser-form #bb-ui-bbpagebrowser-form-layout').attr("disabled", true);
                    var buttons = {};
                    buttons[bb.i18n.__('popupmanager.button.save')] = function() {
                        var editDialog = this;
                        var title = ($.trim($(editDialog).find('#bb-ui-bbpagebrowser-form-title').val()).length) ? $.trim($(editDialog).find('#bb-ui-bbpagebrowser-form-title').val()) : false;
                        var errors = [];
                        if (!title) {
                            errors.push($(editDialog).find('#bb-ui-bbpagebrowser-form-title'));
                        }

                        if (errors.length) {
                            myself._showErrors(errors);
                            $(editDialog).parents('.ui-dialog:first').unmask();
                            return;
                        }

                        $(editDialog).parents('.ui-dialog:first').mask(bb.i18n.__('loading'));
                        bb.webserviceManager.getInstance('ws_local_page').request('cloneBBPage', {
                            params: {
                                page_uid: page_uid,
                                title: $(editDialog).find('#bb-ui-bbpagebrowser-form-title').val(),
                                url: $(editDialog).find('#bb-ui-bbpagebrowser-form-url').val(),
                                redirect: $(editDialog).find('#bb-ui-bbpagebrowser-form-redirect').val()
                            },
                            success: function(response) {
                                $(editDialog).dialog("close");
                                if (typeof callback == "function") {
                                    callback(response.result);
                                    return;
                                }
                                myself.browsePage(result.result.attr.id.replace('node_', '', 'i'), false);
                            },
                            error: function(result) {
                                $(this).parents('.ui-dialog:first').unmask();
                                myself._showMessage(bb.i18n.__('toolbar.editing.error'), result.error.message, 'alert');
                            }
                        });
                    };
                    buttons[bb.i18n.__('popupmanager.button.close')] = function() {
                        $(this).dialog("close");
                    };

                    var editDialog = myself._popupDialog.create("confirmDialog", {
                        title: ((!page_uid) ? bb.i18n.__('popupmanager.button.create') : bb.i18n.__('popupmanager.button.edit')),
                        buttons: buttons
                    });
                    $(editDialog.dialog).html($('#bb-ui-bbpagebrowser-form').get(0).innerHTML);
                    $(editDialog.dialog).find('#bb-ui-bbpagebrowser-form-title').val(result.result.title);
                    $(editDialog.dialog).find('#bb-ui-bbpagebrowser-form-url').val(result.result.url);
                    $(editDialog.dialog).find('#bb-ui-bbpagebrowser-form-redirect').val(result.result.redirect);
                    $(editDialog.dialog).find('#bb-ui-bbpagebrowser-form-layout').val(result.result.layout_uid);
                    $(editDialog.dialog).find('#bb-ui-bbpagebrowser-form-url').attr("disabled", true);

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
                        var rootUid = "#node_" + $("#node_" + page_uid).attr("rootuid");

                        /*new node*/
                        context.treeview.jstree('create_node', rootUid, 'first', newNode, function() {
                            //context.treeview.jstree("clear_node");
                            var newNodeId = $("#" + newNode.attr.id);
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
            if ($.isArray(fieldWithErrors) && fieldWithErrors.length) {
                $.each(fieldWithErrors, function(i, field) {
                    $(field).addClass("hasError");
                    $(field).unbind("focus.error").bind("focus.error", function(e) {
                        $(e.target).removeClass("hasError");
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
                    $('#bb-ui-bbpagebrowser-form #bb-ui-bbpagebrowser-form-layout').attr("disabled", false);
                    $('#bb-ui-bbpagebrowser-form #bb-ui-bbpagebrowser-form-layout').empty();
                    $('#bb-ui-bbpagebrowser-form #bb-ui-bbpagebrowser-form-layout').append($('<option/>').val("").text(""));
                    $.each(context.layouts, function(index, layout) {
                        $('#bb-ui-bbpagebrowser-form #bb-ui-bbpagebrowser-form-layout').append($('<option/>').val(layout.uid).text(layout.templateTitle));
                    });

                    $('#bb-ui-bbpagebrowser-form #bb-ui-bbpagebrowser-form-target').val(result.target);
                    var buttons = {};
                    buttons[bb.i18n.__('popupmanager.button.save')] = function() {
                        var editDialog = this;
                        $(editDialog).parents('.ui-dialog:first').mask(bb.i18n.__('loading'));

                        /*handle error: title can't be empty && handle error layout can't be empty*/
                        var title = ($.trim($(editDialog).find('#bb-ui-bbpagebrowser-form-title').val()).length) ? $.trim($(editDialog).find('#bb-ui-bbpagebrowser-form-title').val()) : false;
                        var selectedLayout = ($.trim($(editDialog).find('#bb-ui-bbpagebrowser-form-layout').val()).length) ? $.trim($(editDialog).find('#bb-ui-bbpagebrowser-form-layout').val()) : false;
                        /* fixme*/
                        var errors = [];
                        if (!title) {
                            errors.push($(editDialog).find('#bb-ui-bbpagebrowser-form-title'));
                        }
                        if (!selectedLayout) {
                            errors.push($(editDialog).find('#bb-ui-bbpagebrowser-form-layout'));
                        }
                        if (errors.length) {
                            myself._showErrors(errors);
                            $(editDialog).parents('.ui-dialog:first').unmask();
                            return;
                        }



                        bb.webserviceManager.getInstance('ws_local_page').request('postBBSelectorForm', {
                            params: {
                                page_uid: page_uid,
                                root_uid: root_uid,
                                title: title,
                                url: $(editDialog).find('#bb-ui-bbpagebrowser-form-url').val(),
                                target: $(editDialog).find('#bb-ui-bbpagebrowser-form-target').val(),
                                redirect: $(editDialog).find('#bb-ui-bbpagebrowser-form-redirect').val(),
                                layout_uid: selectedLayout,
                                flag: flag_value
                            },
                            success: function(result) {
                                if (page_uid === null) {
                                    context.treeview.jstree('create_node', $(myself.element).find('#node_' + root_uid), 'first', result.result);
                                    $(myself.element).find('#' + result.result.attr.id + ' a ins').addClass('bb5-jstree-offline bb5-jstree-hidden');
                                } else {
                                    context.treeview.jstree('rename_node', $(myself.element).find('#node_' + page_uid), result.result.data);
                                }

                                $(editDialog).parents('.ui-dialog:first').unmask();
                                $(editDialog).dialog("close");

                                if (callback) {
                                    callback(result.result);
                                }
                            },
                            error: function(result) {
                                $(this).parents('.ui-dialog:first').unmask();
                                myself._showMessage(bb.i18n.__('toolbar.editing.error'), result.error.message, 'alert');
                            }
                        });
                    };
                    buttons[bb.i18n.__('popupmanager.button.close')] = function() {
                        $(this).dialog("close");
                    };

                    var editDialog = myself._popupDialog.create("confirmDialog", {
                        title: ((!page_uid) ? bb.i18n.__('popupmanager.button.create') : bb.i18n.__('popupmanager.button.edit')),
                        buttons: buttons
                    });
                    $(editDialog.dialog).html($('#bb-ui-bbpagebrowser-form').get(0).innerHTML);
                    $(editDialog.dialog).find('#bb-ui-bbpagebrowser-form-title').val(result.result.title);
                    $(editDialog.dialog).find('#bb-ui-bbpagebrowser-form-url').val(result.result.url);
                    $(editDialog.dialog).find('#bb-ui-bbpagebrowser-form-target').val(result.result.target);
                    $(editDialog.dialog).find('#bb-ui-bbpagebrowser-form-redirect').val(result.result.redirect);
                    $(editDialog.dialog).find('#bb-ui-bbpagebrowser-form-layout').val(result.result.layout_uid);
                    $(editDialog.dialog).find('#bb-ui-bbpagebrowser-form-url').attr("disabled", true);

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
                page = $(myself.element).find('#node_' + page)

            if ('undefined' != typeof (myself._confirmRemoveDialog)) {
                myself._confirmRemoveDialog.destroy();
            }

            var alertDialog = bb.PopupManager.init({});
            myself._confirmRemoveDialog = alertDialog.create('confirmDialog', {
                dialogType: alertDialog.dialogType.CONFIRMATION,
                title: 'Page removal',
                buttons: {
                    "Confirm": function() {
                        if ($(page).attr('uid') === bb.frontApplication.getPageId()) {
                            var pageWebservice = bb.webserviceManager.getInstance('ws_local_page');
                            pageWebservice.request('delete', {
                                params: {
                                    uid: bb.frontApplication.getPageId()
                                },
                                success: function(response) {
                                    $(this).dialog("close");
                                    if (response.result) {
                                        document.location = bb.baseurl + response.result.url;
                                    } else {
                                        _displayError('Unable to remove current page', null, _remove);
                                    }
                                },
                                error: function(response) {
                                    $(confirmDialog).dialog("close");
                                    _displayError('Unable to remove current page', response.error, _remove);
                                }
                            });
                        } else {
                            $(this).dialog('close');
                            myself.getContext().treeview.trigger('delete.jstree', page);
                        }
                    },
                    "Cancel": function() {
                        $(this).dialog('close');
                    }
                }
            });
            $(myself._confirmRemoveDialog.dialog).empty().html('Delete selected page ?');

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
            return $(this.element).data('context', $.extend($(this.element).data('context'), context));
        },
        getContext: function() {
            return ((typeof $(this.element).data('context') != 'undefined') ? $(this.element).data('context') : {});
        }
    })
})(jQuery);