// duplicated code from common/format

define([
    'aloha',
    'aloha/plugin',
    'aloha/state-override',
    'jquery',
    'util/arrays',
    'util/maps',
    'ui/ui',
    'ui/toggleButton',
    'ui/port-helper-multi-split',
    'PubSub',
    'i18n!format/nls/i18n',
    'i18n!aloha/nls/i18n',
    'aloha/selection',
    'ui/scopes'
    
    ], function (
        Aloha,
        Plugin,
        StateOverride,
        jQuery,
        Arrays,
        Maps,
        Ui,
        ToggleButton,
        MultiSplitButton,
        PubSub,
        i18n,
        i18nCore,
        Select,
        Scopes
        ) {
	
        var GENTICS = window.GENTICS;
        var pluginNamespace = 'bb-formatstyle';
        var commandsByElement = {
            'b': 'bold',
            'strong': 'bold',
            'i': 'italic',
            'em': 'italic',
            'del': 'strikethrough',
            'sub': 'subscript',
            'sup': 'superscript',
            'u': 'underline',
            's': 'strikethrough'
        };
        var componentNameByElement = {
            'strong': 'strong',
            'em': 'emphasis',
            's': 'strikethrough2'
        };
        var textLevelSemantics = {
            'u': true,
            'em': true,
            'strong': true,
            'b': true,
            'i': true,
            'cite': true,
            'q': true,
            'code': true,
            'abbr': true,
            'del': true,
            's': true,
            'sub': true,
            'sup': true
        };
        var blockLevelSemantics = {
            'p': true,
            'h1': true,
            'h2': true,
            'h3': true,
            'h4': true,
            'h5': true,
            'h6': true,
            'pre': true
        };
        var interchangeableNodeNames = {
            "B": ["STRONG", "B"],
            "I": ["EM", "I"],
            "STRONG": ["STRONG", "B"],
            "EM": ["EM", "I"]
        };

        function formatInsideTableWorkaround(button) {
            var selectedCells = jQuery('.aloha-cell-selected');
            if (selectedCells.length > 0) {
                var cellMarkupCounter = 0;
                selectedCells.each(function () {
                    var cellContent = jQuery(this).find('div'),
                    cellMarkup = cellContent.find(button);
                    if (cellMarkup.length > 0) {
                        // unwrap all found markup text
                        // <td><b>text</b> foo <b>bar</b></td>
                        // and wrap the whole contents of the <td> into <b> tags
                        // <td><b>text foo bar</b></td>
                        cellMarkup.contents().unwrap();
                        cellMarkupCounter++;
                    }
                    cellContent.contents().wrap('<'+button+'></'+button+'>');
                });

                // remove all markup if all cells have markup
                if (cellMarkupCounter === selectedCells.length) {
                    selectedCells.find(button).contents().unwrap();
                }
                return true;
            }
            return false;
        }

        function textLevelButtonClickHandler(formatPlugin, button) {
            if (formatInsideTableWorkaround(button)) {
                return false;
            }
            formatPlugin.addMarkup( button ); 
            return false;
        }

        function blockLevelButtonClickHandler(formatPlugin, button) {
            if (formatInsideTableWorkaround(button)) {
                return false;
            }
            formatPlugin.changeMarkup( button );

            // setting the focus is needed for mozilla to have a working rangeObject.select()
            if (Aloha.activeEditable && jQuery.browser.mozilla) {
                Aloha.activeEditable.obj.focus();
            }
		
            // triggered for numerated-headers plugin
            if (Aloha.activeEditable) {
                Aloha.trigger( 'aloha-format-block' );
            }
        }

        function makeTextLevelButton(formatPlugin, button) {
            var command = commandsByElement[button];
            var componentName = command;
            if (componentNameByElement.hasOwnProperty(button)) {
                componentName = componentNameByElement[button];
            }
		
            var component = Ui.adopt(componentName, ToggleButton, {
                tooltip : i18n.t('button.' + button + '.tooltip'),
                icon: 'aloha-icon aloha-icon-' + componentName,
                scope: 'Aloha.continuoustext',
                click: function () {
                    return textLevelButtonClickHandler(formatPlugin, button);
                }
            });
            return component;
        }

        function makeBlockLevelButton(formatPlugin, button) {
            return {
                name: button,
                tooltip: i18n.t('button.' + button + '.tooltip'),
                iconClass: 'aloha-icon ' + i18n.t('aloha-large-icon-' + button),
                markup: jQuery('<' + button + '>'),
                click: function () {
                    return blockLevelButtonClickHandler(formatPlugin, button);
                }
            };
        }

        function makeRemoveFormatButton(formatPlugin, button) {
            return {
                name: button,
                text: i18n.t('button.' + button + '.text'),
                tooltip: i18n.t('button.' + button + '.tooltip'),
                wide: true,
                cls: 'aloha-ui-multisplit-fullwidth',
                click: function () {
                    formatPlugin.removeFormat();
                }
            };
        }
        
        
        function changeMarkup(button,extra) {
            var extra = (typeof extra == "object") ? extra : null;
            var markup = jQuery('<' + button + '>',extra);
            
            /*fake toggle here cond: if block: call remove*/
            
            if(isMarkupInCurrentSelection(markup,extra['class'])){
                //clean node
                var rangeObject = Aloha.Selection.rangeObject;
                if (rangeObject.isCollapsed()) {
                    return;
                }
                GENTICS.Utils.Dom.removeMarkup(rangeObject,markup, Aloha.activeEditable.obj);
                // select the modified range
                rangeObject.select();
            }
            else{
                Aloha.Selection.changeMarkupOnSelection(jQuery('<' + button + '>',extra));
            }
        }

        function updateUiAfterMutation(formatPlugin, rangeObject) {
            // select the modified range
            rangeObject.select();
            // update Button toggle state. We take 'Aloha.Selection.getRangeObject()'
            // because rangeObject is not up-to-date
            onSelectionChanged(formatPlugin, Aloha.Selection.getRangeObject());
        }

        function format(formatPlugin, rangeObject, markup) {
            GENTICS.Utils.Dom.addMarkup(rangeObject, markup);
            updateUiAfterMutation(formatPlugin, rangeObject);
        }

        function isFormatAllowed(tagname, plugin, editable) {
            var config = plugin.getEditableConfig(editable.obj);
            return jQuery.inArray(tagname, config) > -1;
        }
        
        function isMarkupInCurrentSelection(markup,contentClass){
            var rangeObject = Aloha.Selection.rangeObject;
            var foundMarkup = rangeObject.findMarkup(function() {
                return jQuery(this).hasClass(contentClass); 
            // return -1 !== Arrays.indexOf(nodeNames, this.nodeName);
            }, Aloha.activeEditable.obj);
            return foundMarkup;
        }
        
        function addMarkup(button,extra) {
            var formatPlugin = this;
            var extra = (typeof extra == "object") ? extra : null;
            var markup = jQuery('<'+button+'>',extra);
            var rangeObject = Aloha.Selection.rangeObject;
            if ( typeof button === "undefined" || button == "" ) {
                return;
            }

            // check whether the markup is found in the range (at the start of the range)
            var nodeNames = interchangeableNodeNames[markup[0].nodeName] || [markup[0].nodeName];
            var foundMarkup = rangeObject.findMarkup(function() {
                return -1 !== Arrays.indexOf(nodeNames, this.nodeName);
            }, Aloha.activeEditable.obj);
           
            if (foundMarkup) {
                
                // remove the markup
                if (rangeObject.isCollapsed()) {
                    // when the range is collapsed, we remove exactly the one DOM element
                    GENTICS.Utils.Dom.removeFromDOM(foundMarkup, rangeObject, true);
                } else {
                    // the range is not collapsed, so we remove the markup from the range
                    GENTICS.Utils.Dom.removeMarkup(rangeObject, jQuery(foundMarkup), Aloha.activeEditable.obj);
                }
                updateUiAfterMutation(formatPlugin, rangeObject);
            } else {
                // when the range is collapsed, extend it to a word
                if (rangeObject.isCollapsed()) {
                    GENTICS.Utils.Dom.extendToWord(rangeObject);
                    if (rangeObject.isCollapsed()) {
                        if (StateOverride.enabled()) {
                            StateOverride.setWithRangeObject(
                                commandsByElement[button],
                                rangeObject,
                                function (command, rangeObject) {
                                    format(formatPlugin, rangeObject, markup);
                                }
                                );
                            return;
                        }
                    }
                }
                format(formatPlugin, rangeObject, markup);
            }
        }

        /**
        * Préciser pour la sélection le style à appliquer
        **/
        function onSelectionChanged(formatPlugin, rangeObject) {
            var effectiveMarkup,
            foundMultiSplit, i, j, multiSplitItem;
            var styleItems = formatPlugin.availableStyleItems;

            if (formatPlugin.availableStyleItems.length > 0) {
                foundMultiSplit = false;

                // iterate over the markup elements
                for (i = 0; i < rangeObject.markupEffectiveAtStart.length && !foundMultiSplit; i++) {
                    effectiveMarkup = rangeObject.markupEffectiveAtStart[i];

                    for (j = 0; j < styleItems.length && !foundMultiSplit; j++) {
                        multiSplitItem = styleItems[j];

                        if (!multiSplitItem.markup) {
                            continue;
                        }
                        // now check whether one of the multiSplitItems fits to the effective markup
                        if (Aloha.Selection.standardTextLevelSemanticsComparator(effectiveMarkup, multiSplitItem.markup)) {
                            formatPlugin.multiSplitStyleButtons.setActiveItem(multiSplitItem.name);
                            foundMultiSplit = true;
                        }
                    }
                }

                if (!foundMultiSplit) {
                    formatPlugin.multiSplitStyleButtons.setActiveItem(null);
                }
            }
        }
	
        function makeStyleButton(formatPlugin,styleConf){
			
            /* click handle depends on tag */
            var tagname = (typeof styleConf.tagname == "string") ? styleConf.tagname : "span";
            return {
                name:  styleConf.tooltip,
                tooltip: styleConf.tooltip,
                wide: true,
                cls: "aloha-large-button styleConf.markup",
                markup: jQuery('<'+tagname+'>',{
                    'class':styleConf.markup
                }),
                click: function(){
                    return styleButtonClickHandler(formatPlugin, styleConf);
                }
            }
        }
	
        function styleButtonClickHandler(formatPlugin,styleConf){
            var tagname = styleConf.tagname;
            if(formatInsideTableWorkaround(tagname)){
                return false;
            }
            console.log("tagname:" +tagname);
		
            if(textLevelSemantics[tagname] || tagname=="span"){
                formatPlugin.addMarkup(tagname,{
                    'class':styleConf.markup
                });
                return false;
            }
		
            if(blockLevelSemantics[tagname]){
                formatPlugin.changeMarkup( tagname , {
                    "class":styleConf.markup
                });
            }
		
            // setting the focus is needed for mozilla to have a working rangeObject.select()
            if (Aloha.activeEditable && jQuery.browser.mozilla) {
                Aloha.activeEditable.obj.focus();
            }
		
            // triggered for numerated-headers plugin
            if (Aloha.activeEditable) {
                Aloha.trigger( 'aloha-format-block' );
            }
		
        }
	
        /**
	 * register the plugin with unique name
	 */
        return Plugin.create('bbformatstyle', {
            /**
		 * default button configuration
		 */
            config: [ 'b', 'i', 'sub', 'sup', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'pre', 'removeFormat' ],

            /**
		 * available options / buttons
		 * 
		 * @todo new buttons needed for 'code'
		 */
            availableButtons: [ 'u', 'strong','del', 'em', 'b', 'i', 's', 'sub', 'sup', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'pre', 'removeFormat' ],

            /**
		 * HotKeys used for special actions
		 */
            hotKey: { 
                formatBold: 'ctrl+b',
                formatItalic: 'ctrl+i',
                formatParagraph: 'alt+ctrl+0',
                formatH1: 'alt+ctrl+1',
                formatH2: 'alt+ctrl+2',
                formatH3: 'alt+ctrl+3',
                formatH4: 'alt+ctrl+4',
                formatH5: 'alt+ctrl+5',
                formatH6: 'alt+ctrl+6',
                formatPre: 'ctrl+p',
                formatDel: 'ctrl+d',
                formatSub: 'alt+shift+s',
                formatSup: 'ctrl+shift+s'
            },

            /**
		 * Initialize the plugin and set initialize flag on true
		 */
            init: function () {
                // Prepare
                
                var me = this;
                if (typeof this.settings.hotKey !== 'undefined') {
                    jQuery.extend(true, this.hotKey, this.settings.hotKey);
                }
                this.availableStyles = jQuery.isArray(this.settings.styles) ? this.settings.styles : [];
                if(jQuery.isArray(this.availableStyles) && this.availableStyles.length==0) return;
                
                this.initButtons();
                this.initStyleComponents();
                Scopes.enterScope("bb.customstyle");
                Aloha.bind('aloha-plugins-loaded', function () {});
                Aloha.bind('aloha-editable-activated', function (e, params) {});
                Aloha.bind('aloha-editable-deactivated', function (e, params) {});
            },
            
            createStyleTab: function(){
                Aloha.settings.toolbar.tabs.push({
                    label:"Styles", 
                    components: ["formatStyle"]
                    });
                console.log( Aloha.settings.toolbar);
            //jQuery.extend(true,Aloha.settings.toolbar,{label:"Styles", components: ["formatStyle"]});
            //console.log(Aloha.settings);
            },
            /**
		 * applys a configuration specific for an editable
		 * buttons not available in this configuration are hidden
		 * @param {Object} id of the activated editable
		 * @return void
		 */
            applyButtonConfig: function (obj) {
                return false; // no used yet
                var config = this.getEditableConfig(obj),
                button, i, len;

                if ( typeof config === 'object' ) {
                    var config_old = [];
                    jQuery.each(config, function(j, button) {
                        if ( !(typeof j === 'number' && typeof button === 'string') ) {
                            config_old.push(j);
                        }
                    });
				
                    if ( config_old.length > 0 ) {
                        config = config_old;
                    }
                }
                this.formatOptions = config;

                // now iterate all buttons and show/hide them according to the config
                for ( button in this.buttons) {
                    if (this.buttons.hasOwnProperty(button)) {
                        if (jQuery.inArray(button, config) !== -1) {
                            this.buttons[button].handle.show();
                        } else {
                            this.buttons[button].handle.hide();
                        }
                    }
                }

                // and the same for multisplit items
                len = this.multiSplitItems.length;
                for (i = 0; i < len; i++) {
                    if (jQuery.inArray(this.multiSplitItems[i].name, config) !== -1) {
                        this.multiSplitButton.showItem(this.multiSplitItems[i].name); //enable
                    } else {
                        this.multiSplitButton.hideItem(this.multiSplitItems[i].name); //disable
                    }
                }
            },

            /**
		 * initialize the buttons and register them on floating menu
		 * @param event event object
		 * @param editable current editable object
		 */
            initButtons: function () {
                var that = this;
                return; 
                
                this.buttons = {};
                this.multiSplitItems = [];
                this.multiSplitStyleButtons = [];
                jQuery.each(this.availableButtons, function(j, button) {
                    var button_config = false;

                    if (typeof j !== 'number' && typeof button !== 'string') {
                        button_config = button;
                        button = j;
                    }
				
                    if (textLevelSemantics[button]) {
                        that.buttons[button] = {
                            handle: makeTextLevelButton(that, button),
                            markup: jQuery('<'+button+'>', {
                                'class': button_config || ''
                            })
                        };
                    } else if (blockLevelSemantics[button]) {
					
                        that.multiSplitItems.push(makeBlockLevelButton(that, button));
                    } else if ('removeFormat' === button) {
                        that.multiSplitItems.push(makeRemoveFormatButton(that, button));
                    } else {
                        Aloha.log('warn', that, 'Button "' + button + '" is not defined');
                    }
                });

                this.multiSplitButton = MultiSplitButton({
                    name: 'notUsed',
                    items: this.multiSplitItems,
                    hideIfEmpty: true,
                    scope: 'bb.customstyle'
                });

                PubSub.sub('aloha.selection.context-change', function(message) {
                    onSelectionChanged(that, message.range);
                });
            },
            
            /* create style here */
            initStyleComponents: function(availablesStyles){
                var that = this;
                this.availableStyleItems = [];
                /*handle text level style & */
                jQuery.each(this.availableStyles, function(i,styleInfo){
                    if("tagname" in styleInfo){
                        that.availableStyleItems.push(makeStyleButton(that,styleInfo));
                    }
                });
					
                this.multiSplitStyleButtons = MultiSplitButton({
                    name: "formatStyle",
                    items: this.availableStyleItems,
                    hideIfEmpty : true,
                    scope: 'Aloha.continoustext'
                }); 

            },
            // duplicated code from link-plugin
            //Creates string with this component's namepsace prefixed the each classname
            nsClass: function () {
                var stringBuilder = [], prefix = pluginNamespace;
                jQuery.each( arguments, function () {
                    stringBuilder.push( this == '' ? prefix : prefix + '-' + this );
                } );
                return jQuery.trim(stringBuilder.join(' '));
            },

            // duplicated code from link-plugin
            nsSel: function () {
                var stringBuilder = [], prefix = pluginNamespace;
                jQuery.each( arguments, function () {
                    stringBuilder.push( '.' + ( this == '' ? prefix : prefix + '-' + this ) );
                } );
                return jQuery.trim(stringBuilder.join(' '));
            },

            addMarkup: addMarkup,
            changeMarkup: changeMarkup,
            /**
		 * Removes all formatting from the current selection.
		 */
            removeFormat: function() {
                var formats = [ 'u', 'strong', 'em', 'b', 'i', 'q', 'del', 's', 'code', 'sub', 'sup', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'pre', 'quote', 'blockquote' ],
                rangeObject = Aloha.Selection.rangeObject,
                i;

                // formats to be removed by the removeFormat button may now be configured using Aloha.settings.plugins.format.removeFormats = ['b', 'strong', ...]
                if (this.settings.removeFormats) {
                    formats = this.settings.removeFormats;
                }

                if (rangeObject.isCollapsed()) {
                    return;
                }

                for (i = 0; i < formats.length; i++) {
                    GENTICS.Utils.Dom.removeMarkup(rangeObject, jQuery('<' + formats[i] + '>'), Aloha.activeEditable.obj);
                }

                // select the modified range
                rangeObject.select();
            // TODO: trigger event - removed Format
            },

            /**
		 * toString method
		 * @return string
		 */
            toString: function () {
                return 'format';
            }
        });
    });
