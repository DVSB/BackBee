/*!
* Aloha Editor
* Author & Copyright (c) 2010 Gentics Software GmbH
* aloha-sales@gentics.com
* Licensed unter the terms of http://www.aloha-editor.com/license.html
*/

define(
['aloha', 'aloha/plugin', 'aloha/jquery', 'aloha/floatingmenu', 'i18n!format/nls/i18n', 'i18n!aloha/nls/i18n', 'aloha/console',
 		'css!wordinfo/css/wordinfo.css'],
function(Aloha, Plugin, jQuery, FloatingMenu, i18n, i18nCore) {

	var
		GENTICS = window.GENTICS;

	/**
	 * register the plugin with unique name
	 */
	return Plugin.create('wordinfo', {
		/**
		 * Configure the available languages
		 */
		languages: ['fr'],

		/**
		 * default button configuration
		 */
		config: [ 'infobulle', 'infopopup', 'infocadre'],

		/**
		 * Initialize the plugin and set initialize flag on true
		 */
		init: function () {
			// Prepare
			var me = this;

			this.initButtons();

			// apply specific configuration if an editable has been activated
			Aloha.bind('aloha-editable-activated',function (e, params) {
				//debugger;
				me.applyButtonConfig(params.editable.obj);
			});

		},

		/**
		 * applys a configuration specific for an editable
		 * buttons not available in this configuration are hidden
		 * @param {Object} id of the activated editable
		 * @return void
		 */
		applyButtonConfig: function (obj) {

			var config = this.getEditableConfig(obj),
				button, i, len;

			// now iterate all buttons and show/hide them according to the config
			for ( button in this.buttons) {
				if (jQuery.inArray(button, config) != -1) {
					this.buttons[button].button.show();
				} else {
					this.buttons[button].button.hide();
				}
			}

			// and the same for multisplit items
			len = this.multiSplitItems.length;
			for (i = 0; i < len; i++) {
				if (jQuery.inArray(this.multiSplitItems[i].name, config) != -1) {
					this.multiSplitButton.showItem(this.multiSplitItems[i].name);
				} else {
					this.multiSplitButton.hideItem(this.multiSplitItems[i].name);
				}
			}
		},

		/**
		 * initialize the buttons and register them on floating menu
		 * @param event event object
		 * @param editable current editable object
		 */
		initButtons: function () {
			var
				scope = 'Aloha.continuoustext',
				that = this;

			// reset
			this.buttons = {};

			// collect the multisplit items here
			this.multiSplitItems = [];
			//this.multiSplitButton;

			//iterate configuration array an push buttons to buttons array
			jQuery.each(this.config, function(j, button) {
				switch( button ) {
					// text level semantics:
					case 'infobulle':
							that.buttons[button] = {'button' : new Aloha.ui.Button({
							'name' : button,
							'iconClass' : 'aloha-button aloha-button-' + button,
							'size' : 'small',
							'onclick' : function () {
								var
									markup = jQuery('<div id="'+button+'"></div>'),
									rangeObject = Aloha.Selection.rangeObject,
									foundMarkup;

								// check whether the markup is found in the range (at the start of the range)
								foundMarkup = rangeObject.findMarkup(function() {
									return this.nodeName.toLowerCase() == markup.get(0).nodeName.toLowerCase();
								}, Aloha.activeEditable.obj);

								if (foundMarkup) {
									// remove the markup
									if (rangeObject.isCollapsed()) {
										// when the range is collapsed, we remove exactly the one DOM element
										GENTICS.Utils.Dom.removeFromDOM(foundMarkup, rangeObject, true);
									} else {
										// the range is not collapsed, so we remove the markup from the range
										GENTICS.Utils.Dom.removeMarkup(rangeObject, markup, Aloha.activeEditable.obj);
									}
								} else {
									// when the range is collapsed, extend it to a word
									if (rangeObject.isCollapsed()) {
										GENTICS.Utils.Dom.extendToWord(rangeObject);
									}

									// add the markup
									GENTICS.Utils.Dom.addMarkup(rangeObject, markup);
								}
								// select the modified range
								rangeObject.select();
								return false;
							},
							'tooltip' : i18n.t('' + button + ''),
							'toggle' : true
						}), 'markup' : jQuery('<div id="'+button+'"></div>')};

						FloatingMenu.addButton(
							scope,
							that.buttons[button].button,
							i18nCore.t('floatingmenu.tab.format'),
							1
						);
						break;

					case 'infopopup':
						that.buttons[button] = {'button' : new Aloha.ui.Button({
							'name' : button,
							'iconClass' : 'aloha-button aloha-button-' + button,
							'size' : 'small',
							'onclick' : function () {
								var
									markup = jQuery('<div id="'+button+'"></div>'),
									rangeObject = Aloha.Selection.rangeObject,
									foundMarkup;

								// check whether the markup is found in the range (at the start of the range)
								foundMarkup = rangeObject.findMarkup(function() {
									return this.nodeName.toLowerCase() == markup.get(0).nodeName.toLowerCase();
								}, Aloha.activeEditable.obj);

								if (foundMarkup) {
									// remove the markup
									if (rangeObject.isCollapsed()) {
										// when the range is collapsed, we remove exactly the one DOM element
										GENTICS.Utils.Dom.removeFromDOM(foundMarkup, rangeObject, true);
									} else {
										// the range is not collapsed, so we remove the markup from the range
										GENTICS.Utils.Dom.removeMarkup(rangeObject, markup, Aloha.activeEditable.obj);
									}
								} else {
									// when the range is collapsed, extend it to a word
									if (rangeObject.isCollapsed()) {
										GENTICS.Utils.Dom.extendToWord(rangeObject);
									}

									// add the markup
									GENTICS.Utils.Dom.addMarkup(rangeObject, markup);
								}
								// select the modified range
								rangeObject.select();
								return false;
							},
							'tooltip' : i18n.t('' + button + ''),
							'toggle' : true
						}), 'markup' : jQuery('<div id="'+button+'"></div>')};

						FloatingMenu.addButton(
							scope,
							that.buttons[button].button,
							i18nCore.t('floatingmenu.tab.format'),
							1
						);
						break;

					case 'infocadre':
						that.buttons[button] = {'button' : new Aloha.ui.Button({
							'name' : button,
							'iconClass' : 'aloha-button aloha-button-' + button,
							'size' : 'small',
							'onclick' : function () {
								var
									markup = jQuery('<div class="encadrearticle"></div>'),
									rangeObject = Aloha.Selection.rangeObject,
									foundMarkup;

									//alert(markup);
									//alert(rangeObject);


								// check whether the markup is found in the range (at the start of the range)
								foundMarkup = rangeObject.findMarkup(function() {
									return this.nodeName.toLowerCase() == markup.get(0).nodeName.toLowerCase();
								}, Aloha.activeEditable.obj);

								//alert(foundMarkup);

								if (foundMarkup) {
									// remove the markup
									if (rangeObject.isCollapsed()) {
										// when the range is collapsed, we remove exactly the one DOM element
										GENTICS.Utils.Dom.removeFromDOM(foundMarkup, rangeObject, true);
									} else {
										// the range is not collapsed, so we remove the markup from the range
										GENTICS.Utils.Dom.removeMarkup(rangeObject, markup, Aloha.activeEditable.obj);
									}
								} else {
									alert(rangeObject.isCollapsed());

									// when the range is collapsed, extend it to a word
									if (rangeObject.isCollapsed()) {
										GENTICS.Utils.Dom.extendToWord(rangeObject);
									}

									// add the markup
									GENTICS.Utils.Dom.addMarkup(rangeObject, markup);
								}
								// select the modified range
								rangeObject.select();
								return false;
							},
							'tooltip' : i18n.t('' + button + ''),
							'toggle' : true
						}), 'markup' : jQuery('<div class="encadrearticle"></div>')};

						FloatingMenu.addButton(
							scope,
							that.buttons[button].button,
							i18nCore.t('floatingmenu.tab.format'),
							1
						);
						break;

					// wide multisplit buttons
					case 'removeFormat':
						that.multiSplitItems.push({
							'name' : button,
							'text' : i18n.t('button.' + button + '.text'),
							'tooltip' : i18n.t('button.' + button + '.tooltip'),
							'iconClass' : 'aloha-button aloha-button-' + button,
							'wide' : true,
							'click' : function() {
								that.removeFormat();
							}
						});
						break;

					//no button defined
					default:
						Aloha.log('warn', this, 'Button "' + button + '" is not defined');
						break;
				}
			});

			if (this.multiSplitItems.length > 0) {
				this.multiSplitButton = new Aloha.ui.MultiSplitButton({
					'name' : 'phrasing',
					'items' : this.multiSplitItems
				});
				FloatingMenu.addButton(
					scope,
					this.multiSplitButton,
					i18nCore.t('floatingmenu.tab.format'),
					3
				);
			}

			// add the event handler for selection change
			Aloha.bind('aloha-selection-changed',function(event,rangeObject){
				// iterate over all buttons
				var
					statusWasSet = false, effectiveMarkup,
					foundMultiSplit, i, j, multiSplitItem;

				jQuery.each(that.buttons, function(index, button) {
					statusWasSet = false;
					for ( i = 0; i < rangeObject.markupEffectiveAtStart.length; i++) {
						effectiveMarkup = rangeObject.markupEffectiveAtStart[ i ];
						if (Aloha.Selection.standardTextLevelSemanticsComparator(effectiveMarkup, button.markup)) {
							button.button.setPressed(true);
							statusWasSet = true;
						}
					}
					if (!statusWasSet) {
						button.button.setPressed(false);
					}
				});

				if (that.multiSplitItems.length > 0) {
					foundMultiSplit = false;

					// iterate over the markup elements
					for ( i = 0; i < rangeObject.markupEffectiveAtStart.length && !foundMultiSplit; i++) {
						effectiveMarkup = rangeObject.markupEffectiveAtStart[ i ];

						for ( j = 0; j < that.multiSplitItems.length && !foundMultiSplit; j++) {
							multiSplitItem = that.multiSplitItems[j];

							if (!multiSplitItem.markup) {
								continue;
							}

							// now check whether one of the multiSplitItems fits to the effective markup
							if (Aloha.Selection.standardTextLevelSemanticsComparator(effectiveMarkup, multiSplitItem.markup)) {
								that.multiSplitButton.setActiveItem(multiSplitItem.name);
								foundMultiSplit = true;
							}
						}
					}

					if (!foundMultiSplit) {
						that.multiSplitButton.setActiveItem(null);
					}
				}
			});

		},

		/**
		 * Removes all formatting from the current selection.
		 */
		removeFormat: function() {
			var formats = [ 'infobulle', 'infopopup', 'infocadre'],
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
				GENTICS.Utils.Dom.removeMarkup(rangeObject, jQuery('<div class="'+ formats[i] +'"></div>'), Aloha.activeEditable.obj);
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

define(
['aloha', 'aloha/plugin', 'aloha/jquery', 'aloha/floatingmenu', 'i18n!format/nls/i18n', 'i18n!aloha/nls/i18n', 'aloha/console',
 		'css!wordinfo/css/wordinfo.css'],
function(Aloha, Plugin, jQuery, FloatingMenu, i18n, i18nCore) {

	var
		GENTICS =  = window.GENTICS;

	return Plugin.create('wordinfo', {

		languages : ['en', 'fr'],
		config : ['infocadre', 'infobulle', 'infopopup'],
		init : function(){
			// Preparation
			var me = this;
			this.initButtons();



		},
		configButtons : ,
		iniButtons : function(){

			tabInsert = i18nCore.t('floatingmenu.tab.insert'),
			tabImage = i18n.t('floatingmenu.tab.img'),
			tabFormatting = i18n.t('floatingmenu.tab.formatting'),
			tabCrop = i18n.t('floatingmenu.tab.crop'),
			tabResize = i18n.t('floatingmenu.tab.resize');

		},
		removeFormat : ,
		toString :


	});

});


	/*return Plugin.create('wordinfo',
	{

		languages : ['en', 'fr'],
		config: ['infobulle', 'infopopup', 'infocadre'],

		init : function ()
		{
			// create a new button
			var that = this;

			var button = new GENTICS.Aloha.ui.Button(
			{
				'iconClass' : 'aloha-button aloha-button-encadre',
				'size' : 'small',
				'onclick' : function ()
				{
					var markup = jQuery('<div class="encadrearticle"></div>');
					var rangeObject = GENTICS.Aloha.Selection.rangeObject;
					// add the markup
					GENTICS.Utils.Dom.addMarkup(rangeObject, markup);
				},
			'tooltip' : that.i18n('button.encadre.tooltip')
			});

			// add it to the floating menu
			GENTICS.Aloha.FloatingMenu.addButton(
				'GENTICS.Aloha.continuoustext',
				button,
				GENTICS.Aloha.i18n(GENTICS.Aloha, 'floatingmenu.tab.format'),
				4
		)}

	});




	/*!
* Aloha Editor
* Author & Copyright (c) 2010 Gentics Software GmbH
* aloha-sales@gentics.com
* Licensed unter the terms of http://www.aloha-editor.com/license.html
*/

define(
['aloha', 'aloha/plugin', 'aloha/jquery', 'aloha/floatingmenu', 'i18n!format/nls/i18n', 'i18n!aloha/nls/i18n', 'aloha/console',
 		'css!wordinfo/css/wordinfo.css'],
function(Aloha, Plugin, jQuery, FloatingMenu, i18n, i18nCore) {

	var
		GENTICS = window.GENTICS;

	/**
	 * register the plugin with unique name
	 */
	return Plugin.create('wordinfo', {
		/**
		 * Configure the available languages
		 */
		languages: ['fr'],

		/**
		 * default button configuration
		 */
		config: [ 'infobulle', 'infopopup', 'infocadre'],

		/**
		 * Initialize the plugin and set initialize flag on true
		 */
		init: function () {
			// Prepare
			var me = this;

			this.initButtons();

			// apply specific configuration if an editable has been activated
			Aloha.bind('aloha-editable-activated',function (e, params) {
				//debugger;
				me.applyButtonConfig(params.editable.obj);
			});

		},

		/**
		 * applys a configuration specific for an editable
		 * buttons not available in this configuration are hidden
		 * @param {Object} id of the activated editable
		 * @return void
		 */
		applyButtonConfig: function (obj) {

			var config = this.getEditableConfig(obj),
				button, i, len;

			// now iterate all buttons and show/hide them according to the config
			for ( button in this.buttons) {
				if (jQuery.inArray(button, config) != -1) {
					this.buttons[button].button.show();
				} else {
					this.buttons[button].button.hide();
				}
			}

			// and the same for multisplit items
			len = this.multiSplitItems.length;
			for (i = 0; i < len; i++) {
				if (jQuery.inArray(this.multiSplitItems[i].name, config) != -1) {
					this.multiSplitButton.showItem(this.multiSplitItems[i].name);
				} else {
					this.multiSplitButton.hideItem(this.multiSplitItems[i].name);
				}
			}
		},

		/**
		 * initialize the buttons and register them on floating menu
		 * @param event event object
		 * @param editable current editable object
		 */
		initButtons: function () {
			var
				scope = 'Aloha.continuoustext',
				that = this;

			// reset
			this.buttons = {};

			// collect the multisplit items here
			this.multiSplitItems = [];
			//this.multiSplitButton;

			//iterate configuration array an push buttons to buttons array
			jQuery.each(this.config, function(j, button) {
				switch( button ) {
					// text level semantics:
					case 'infobulle':
							that.buttons[button] = {'button' : new Aloha.ui.Button({
							'name' : button,
							'iconClass' : 'aloha-button aloha-button-' + button,
							'size' : 'small',
							'onclick' : function () {
								var
									markup = jQuery('<div id="'+button+'"></div>'),
									rangeObject = Aloha.Selection.rangeObject,
									foundMarkup;

								// check whether the markup is found in the range (at the start of the range)
								foundMarkup = rangeObject.findMarkup(function() {
									return this.nodeName.toLowerCase() == markup.get(0).nodeName.toLowerCase();
								}, Aloha.activeEditable.obj);

								if (foundMarkup) {
									// remove the markup
									if (rangeObject.isCollapsed()) {
										// when the range is collapsed, we remove exactly the one DOM element
										GENTICS.Utils.Dom.removeFromDOM(foundMarkup, rangeObject, true);
									} else {
										// the range is not collapsed, so we remove the markup from the range
										GENTICS.Utils.Dom.removeMarkup(rangeObject, markup, Aloha.activeEditable.obj);
									}
								} else {
									// when the range is collapsed, extend it to a word
									if (rangeObject.isCollapsed()) {
										GENTICS.Utils.Dom.extendToWord(rangeObject);
									}

									// add the markup
									GENTICS.Utils.Dom.addMarkup(rangeObject, markup);
								}
								// select the modified range
								rangeObject.select();
								return false;
							},
							'tooltip' : i18n.t('' + button + ''),
							'toggle' : true
						}), 'markup' : jQuery('<div id="'+button+'"></div>')};

						FloatingMenu.addButton(
							scope,
							that.buttons[button].button,
							i18nCore.t('floatingmenu.tab.format'),
							1
						);
						break;

					case 'infopopup':
						that.buttons[button] = {'button' : new Aloha.ui.Button({
							'name' : button,
							'iconClass' : 'aloha-button aloha-button-' + button,
							'size' : 'small',
							'onclick' : function () {
								var
									markup = jQuery('<div id="'+button+'"></div>'),
									rangeObject = Aloha.Selection.rangeObject,
									foundMarkup;

								// check whether the markup is found in the range (at the start of the range)
								foundMarkup = rangeObject.findMarkup(function() {
									return this.nodeName.toLowerCase() == markup.get(0).nodeName.toLowerCase();
								}, Aloha.activeEditable.obj);

								if (foundMarkup) {
									// remove the markup
									if (rangeObject.isCollapsed()) {
										// when the range is collapsed, we remove exactly the one DOM element
										GENTICS.Utils.Dom.removeFromDOM(foundMarkup, rangeObject, true);
									} else {
										// the range is not collapsed, so we remove the markup from the range
										GENTICS.Utils.Dom.removeMarkup(rangeObject, markup, Aloha.activeEditable.obj);
									}
								} else {
									// when the range is collapsed, extend it to a word
									if (rangeObject.isCollapsed()) {
										GENTICS.Utils.Dom.extendToWord(rangeObject);
									}

									// add the markup
									GENTICS.Utils.Dom.addMarkup(rangeObject, markup);
								}
								// select the modified range
								rangeObject.select();
								return false;
							},
							'tooltip' : i18n.t('' + button + ''),
							'toggle' : true
						}), 'markup' : jQuery('<div id="'+button+'"></div>')};

						FloatingMenu.addButton(
							scope,
							that.buttons[button].button,
							i18nCore.t('floatingmenu.tab.format'),
							1
						);
						break;

					case 'infocadre':
						that.buttons[button] = {'button' : new Aloha.ui.Button({
							'name' : button,
							'iconClass' : 'aloha-button aloha-button-' + button,
							'size' : 'small',
							'onclick' : function () {
								var
									markup = jQuery('<div class="encadrearticle"></div>'),
									rangeObject = Aloha.Selection.rangeObject,
									foundMarkup;

									//alert(markup);
									//alert(rangeObject);


								// check whether the markup is found in the range (at the start of the range)
								foundMarkup = rangeObject.findMarkup(function() {
									return this.nodeName.toLowerCase() == markup.get(0).nodeName.toLowerCase();
								}, Aloha.activeEditable.obj);

								//alert(foundMarkup);

								if (foundMarkup) {
									// remove the markup
									if (rangeObject.isCollapsed()) {
										// when the range is collapsed, we remove exactly the one DOM element
										GENTICS.Utils.Dom.removeFromDOM(foundMarkup, rangeObject, true);
									} else {
										// the range is not collapsed, so we remove the markup from the range
										GENTICS.Utils.Dom.removeMarkup(rangeObject, markup, Aloha.activeEditable.obj);
									}
								} else {
									alert(rangeObject.isCollapsed());

									// when the range is collapsed, extend it to a word
									if (rangeObject.isCollapsed()) {
										GENTICS.Utils.Dom.extendToWord(rangeObject);
									}

									// add the markup
									GENTICS.Utils.Dom.addMarkup(rangeObject, markup);
								}
								// select the modified range
								rangeObject.select();
								return false;
							},
							'tooltip' : i18n.t('' + button + ''),
							'toggle' : true
						}), 'markup' : jQuery('<div class="encadrearticle"></div>')};

						FloatingMenu.addButton(
							scope,
							that.buttons[button].button,
							i18nCore.t('floatingmenu.tab.format'),
							1
						);
						break;

					// wide multisplit buttons
					case 'removeFormat':
						that.multiSplitItems.push({
							'name' : button,
							'text' : i18n.t('button.' + button + '.text'),
							'tooltip' : i18n.t('button.' + button + '.tooltip'),
							'iconClass' : 'aloha-button aloha-button-' + button,
							'wide' : true,
							'click' : function() {
								that.removeFormat();
							}
						});
						break;

					//no button defined
					default:
						Aloha.log('warn', this, 'Button "' + button + '" is not defined');
						break;
				}
			});

			if (this.multiSplitItems.length > 0) {
				this.multiSplitButton = new Aloha.ui.MultiSplitButton({
					'name' : 'phrasing',
					'items' : this.multiSplitItems
				});
				FloatingMenu.addButton(
					scope,
					this.multiSplitButton,
					i18nCore.t('floatingmenu.tab.format'),
					3
				);
			}

			// add the event handler for selection change
			Aloha.bind('aloha-selection-changed',function(event,rangeObject){
				// iterate over all buttons
				var
					statusWasSet = false, effectiveMarkup,
					foundMultiSplit, i, j, multiSplitItem;

				jQuery.each(that.buttons, function(index, button) {
					statusWasSet = false;
					for ( i = 0; i < rangeObject.markupEffectiveAtStart.length; i++) {
						effectiveMarkup = rangeObject.markupEffectiveAtStart[ i ];
						if (Aloha.Selection.standardTextLevelSemanticsComparator(effectiveMarkup, button.markup)) {
							button.button.setPressed(true);
							statusWasSet = true;
						}
					}
					if (!statusWasSet) {
						button.button.setPressed(false);
					}
				});

				if (that.multiSplitItems.length > 0) {
					foundMultiSplit = false;

					// iterate over the markup elements
					for ( i = 0; i < rangeObject.markupEffectiveAtStart.length && !foundMultiSplit; i++) {
						effectiveMarkup = rangeObject.markupEffectiveAtStart[ i ];

						for ( j = 0; j < that.multiSplitItems.length && !foundMultiSplit; j++) {
							multiSplitItem = that.multiSplitItems[j];

							if (!multiSplitItem.markup) {
								continue;
							}

							// now check whether one of the multiSplitItems fits to the effective markup
							if (Aloha.Selection.standardTextLevelSemanticsComparator(effectiveMarkup, multiSplitItem.markup)) {
								that.multiSplitButton.setActiveItem(multiSplitItem.name);
								foundMultiSplit = true;
							}
						}
					}

					if (!foundMultiSplit) {
						that.multiSplitButton.setActiveItem(null);
					}
				}
			});

		},

		/**
		 * Removes all formatting from the current selection.
		 */
		removeFormat: function() {
			var formats = [ 'infobulle', 'infopopup', 'infocadre'],
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
				GENTICS.Utils.Dom.removeMarkup(rangeObject, jQuery('<div class="'+ formats[i] +'"></div>'), Aloha.activeEditable.obj);
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

	*/
/*WordinfoEncadre: function()
		{
			var
									markup = jQuery('<div class="encadrearticle"></div>'),
									//rangeObject = Aloha.Selection.rangeObject,
									range = Aloha.Selection.getRangeObject();
									//foundMarkup;

									if(range.isCollapsed() && extendToWord !=false)
									{
										GENTICS.Utils.Dom.extendToWord(range);
									}

									if ( range.isCollapsed() )
									{
										//var selection = this.i18n('newlink.defaulttext');
										var newselection = jQuery('<div class="encadrearticle"></div>');
										GENTICS.Utils.Dom.insertIntoDOM(newselection, range, jQuery(GENTICS.Aloha.activeEditable.obj));
										range.startContainer = range.endContainer = newLink.contents().get(0);
										range.startOffset = 0;
                						range.endOffset = linkText.length;
									}

									// check whether the markup is found in the range (at the start of the range)
									/*foundMarkup = rangeObject.findMarkup(function() {
										return this.nodeName.toLowerCase() == markup.get(0).nodeName.toLowerCase();
									}, Aloha.activeEditable.obj);*/

									/*if(foundMarkup)
									{
										//remove marqueur

									}
									else
									{
										// add marqueur
										alert(rangeObject);
										//GENTICS.Utils.Dom.addMarkup(rangeObject, markup);
									}*/

								/*	if (foundMarkup) {
										// remove the markup
										if (rangeObject.isCollapsed()) {
											// when the range is collapsed, we remove exactly the one DOM element
											GENTICS.Utils.Dom.removeFromDOM(foundMarkup, rangeObject, true);
										} else {
											// the range is not collapsed, so we remove the markup from the range
											GENTICS.Utils.Dom.removeMarkup(rangeObject, markup, Aloha.activeEditable.obj);
										}
									} else {
										alert(rangeObject.isCollapsed());

										// when the range is collapsed, extend it to a word
										if (rangeObject.isCollapsed()) {
											GENTICS.Utils.Dom.extendToWord(rangeObject);
										}

										// add the markup
										GENTICS.Utils.Dom.addMarkup(rangeObject, markup);
									}
								// select the modified range
								//rangeObject.select();
								return false;
		},
*/




range{
	 '_super' => "undefined"
	    'startContainer' ...
	undefined    'endContainer' ...
	undefined    'startOffset' => "0"
	    'endOffset' => "646"
	    'commonAncestorContainer' ...
	undefined    'limitObject' ...
	undefined    'markupEffectiveAtStart' ...
	undefined    'unmodifiableMarkupAtStart' ...
	undefined    'splitObject' ...
	undefined    'selectionTree' => "undefined"
	    '_constructor' => "function () {
	    var tmp = this._super;
	    this._super = _super[name];
	    var ret = fn.apply(this, arguments);
	    this._super = tmp;
	    return ret;
	}"
	    'select' => "function () {
	    var tmp = this._super;
	    this._super = _super[name];
	    var ret = fn.apply(this, arguments);
	    this._super = tmp;
	    return ret;
	}"
	    'update' => "function (commonAncestorContainer) {
	    this.updatelimitObject();
	    this.updateMarkupEffectiveAtStart();
	    this.updateCommonAncestorContainer(commonAncestorContainer);
	    this.selectionTree = undefined;
	}"
	    'getSelectionTree' => "function () {
	    if (!this.selectionTree) {
	        this.selectionTree = Aloha.Selection.getSelectionTree(this);
	    }
	    return this.selectionTree;
	}"
	    'getSelectedSiblings' => "function (domobj) {
	    var selectionTree = this.getSelectionTree();
	    return this.recursionGetSelectedSiblings(domobj, selectionTree);
	}"
	    'recursionGetSelectedSiblings' => "function (domobj, selectionTree) {
	    var selectedSiblings = false, foundObj = false, i;
	    for (i = 0; i < selectionTree.length; ++i) {
	        if (selectionTree[i].domobj === domobj) {
	            foundObj = true;
	            selectedSiblings = [];
	        } else if (!foundObj && selectionTree[i].children) {
	            selectedSiblings = this.recursionGetSelectedSiblings(domobj, selectionTree[i].children);
	            if (selectedSiblings !== false) {
	                break;
	            }
	        } else if (foundObj &&
	            selectionTree[i].domobj &&
	            selectionTree[i].selection != "collapsed" &&
	            selectionTree[i].selection != "none") {
	            selectedSiblings.push(selectionTree[i].domobj);
	        } else if (foundObj && selectionTree[i].selection == "none") {
	            break;
	        }
	    }
	    return selectedSiblings;
	}"
	    'updateMarkupEffectiveAtStart' => "function () {
	    this.markupEffectiveAtStart = [];
	    this.unmodifiableMarkupAtStart = [];
	    var parents = this.getStartContainerParents(), limitFound = false, splitObjectWasSet, i, el;
	    for (i = 0; i < parents.length; i++) {
	        el = parents[i];
	        if (!limitFound && el !== this.limitObject) {
	            this.markupEffectiveAtStart[i] = el;
	            if (!splitObjectWasSet && GENTICS.Utils.Dom.isSplitObject(el)) {
	                splitObjectWasSet = true;
	                this.splitObject = el;
	            }
	        } else {
	            limitFound = true;
	            this.unmodifiableMarkupAtStart.push(el);
	        }
	    }
	    if (!splitObjectWasSet) {
	        this.splitObject = false;
	    }
	    return;
	}"
	    'updatelimitObject' => "function () {
	    if (Aloha.editables && Aloha.editables.length > 0) {
	        var parents = this.getStartContainerParents(), editables = Aloha.editables, i, el, j, editable;
	        for (i = 0; i < parents.length; i++) {
	            el = parents[i];
	            for (j = 0; j < editables.length; j++) {
	                editable = editables[j].obj[0];
	                if (el === editable) {
	                    this.limitObject = el;
	                    return true;
	                }
	            }
	        }
	    }
	    this.limitObject = jQuery("body");
	    return true;
	}"
	    'toString' => "function (verbose) {
	    if (!verbose) {
	        return "Aloha.Selection.SelectionRange";
	    }
	    return "Aloha.Selection.SelectionRange {start [" + this.startContainer.nodeValue + "] offset " + this.startOffset + ", end [" + this.endContainer.nodeValue + "] offset " + this.endOffset + "}";
	}"
	    'deleteContents' => "function () {
	    Dom.removeRange(this);
	}"
	    'log' => "function (message) {
	    console.deprecated("Utils.RangeObject", "log() is deprecated. use " + "console.log() from module \"aloha/console\" instead: " + message);
	}"
	    'isCollapsed' => "function () {
	    return !this.endContainer ||
	        this.startContainer === this.endContainer &&
	        this.startOffset === this.endOffset;
	}"
	    'getCommonAncestorContainer' => "function () {
	    if (this.commonAncestorContainer) {
	        return this.commonAncestorContainer;
	    }
	    this.updateCommonAncestorContainer();
	    return this.commonAncestorContainer;
	}"
	    'getContainerParents' => "function (limit, fromEnd) {
	    var container = fromEnd ? this.endContainer : this.startContainer, parents, limitIndex, i;
	    if (!container) {
	        return false;
	    }
	    if (typeof limit === "undefined" || !limit) {
	        limit = jQuery("body");
	    }
	    if (container.nodeType == 3) {
	        parents = jQuery(container).parents();
	    } else {
	        parents = jQuery(container).parents();
	        for (i = parents.length; i > 0; --i) {
	            parents[i] = parents[i - 1];
	        }
	        parents[0] = container;
	    }
	    limitIndex = parents.index(limit);
	    if (limitIndex >= 0) {
	        parents = parents.slice(0, limitIndex);
	    }
	    return parents;
	}"
	    'getStartContainerParents' => "function (limit) {
	    return this.getContainerParents(limit, false);
	}"
	    'getEndContainerParents' => "function (limit) {
	    return this.getContainerParents(limit, true);
	}"
	    'updateCommonAncestorContainer' => "function (commonAncestorContainer) {
	    var parentsStartContainer = this.getStartContainerParents(), parentsEndContainer = this.getEndContainerParents(), i;
	    if (!commonAncestorContainer) {
	        if (!(parentsStartContainer.length > 0 &&
	            parentsEndContainer.length > 0)) {
	            console.warn("could not find commonAncestorContainer");
	            return false;
	        }
	        for (i = 0; i < parentsStartContainer.length; i++) {
	            if (parentsEndContainer.index(parentsStartContainer[i]) != -1) {
	                this.commonAncestorContainer = parentsStartContainer[i];
	                break;
	            }
	        }
	    } else {
	        this.commonAncestorContainer = commonAncestorContainer;
	    }
	    console.debug(commonAncestorContainer ? "commonAncestorContainer was set successfully" : "commonAncestorContainer was calculated successfully");
	    return true;
	}"
	    'getCollapsedIERange' => "function (container, offset) {
	    var ieRange = document.body.createTextRange(), tmpRange, right, parent, left;
	    left = this.searchElementToLeft(container, offset);
	    if (left.element) {
	        tmpRange = document.body.createTextRange();
	        tmpRange.moveToElementText(left.element);
	        ieRange.setEndPoint("StartToEnd", tmpRange);
	        if (left.characters !== 0) {
	            ieRange.moveStart("character", left.characters);
	        } else {
	            ieRange.moveStart("character", 1);
	            ieRange.moveStart("character", -1);
	        }
	    } else {
	        right = this.searchElementToRight(container, offset);
	        parent = container.nodeType == 3 ? container.parentNode : container;
	        tmpRange = document.body.createTextRange();
	        tmpRange.moveToElementText(parent);
	        ieRange.setEndPoint("StartToStart", tmpRange);
	        if (left.characters !== 0) {
	            ieRange.moveStart("character", left.characters);
	        }
	    }
	    ieRange.collapse();
	    return ieRange;
	}"
	    'searchElementToLeft' => "function (container, offset) {
	    var checkElement, characters = 0;
	    if (container.nodeType === 3) {
	        characters = offset;
	        checkElement = container.previousSibling;
	    } else {
	        if (offset > 0) {
	            checkElement = container.childNodes[offset - 1];
	        }
	    }
	    while (checkElement && checkElement.nodeType === 3) {
	        characters += checkElement.data.length;
	        checkElement = checkElement.previousSibling;
	    }
	    return {element: checkElement, characters: characters};
	}"
	    'searchElementToRight' => "function (container, offset) {
	    var checkElement, characters = 0;
	    if (container.nodeType === 3) {
	        characters = container.data.length - offset;
	        checkElement = container.nextSibling;
	    } else {
	        if (offset < container.childNodes.length) {
	            checkElement = container.childNodes[offset];
	        }
	    }
	    while (checkElement && checkElement.nodeType === 3) {
	        characters += checkElement.data.length;
	        checkElement = checkElement.nextSibling;
	    }
	    return {element: checkElement, characters: characters};
	}"
	    'initializeFromUserSelection' => "function (event) {
	    var selection = rangy.getSelection(), browserRange;
	    if (!selection) {
	        return false;
	    }
	    if (!selection.rangeCount) {
	        return false;
	    }
	    browserRange = selection.getRangeAt(0);
	    if (!browserRange) {
	        return false;
	    }
	    this.startContainer = browserRange.startContainer;
	    this.endContainer = browserRange.endContainer;
	    this.startOffset = browserRange.startOffset;
	    this.endOffset = browserRange.endOffset;
	    this.correctRange();
	    return;
	}"
	    'correctRange' => "function () {
	    var adjacentTextNode, textNode, checkedElement, parentNode, offset;
	    this.clearCaches();
	    if (this.isCollapsed()) {
	        if (this.startContainer.nodeType === 1) {
	            if (this.startOffset > 0 &&
	                this.startContainer.childNodes[this.startOffset - 1].nodeType === 3) {
	                this.startContainer = this.startContainer.childNodes[this.startOffset - 1];
	                this.startOffset = this.startContainer.data.length;
	                this.endContainer = this.startContainer;
	                this.endOffset = this.startOffset;
	                return;
	            }
	            if (this.startOffset > 0 &&
	                this.startContainer.childNodes[this.startOffset - 1].nodeType === 1) {
	                adjace
}