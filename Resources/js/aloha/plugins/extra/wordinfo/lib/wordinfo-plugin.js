define(
[
// js
	'aloha',
	'aloha/plugin',
	'aloha/jquery',
	'aloha/floatingmenu',
	'i18n!format/nls/i18n',
	'i18n!aloha/nls/i18n',
	'aloha/console',
 //css
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
		config:['infobulle', 'infopopup', 'infocadre'],

		/**
		 * default tab configuration
		 */
		defaultSettings : {

			ui: {
					oneTab		: false, //Place all ui components within one tab
					infobulle   : true,
					infopopup 	: true,
					infocadre	: true
				}
		},


		/**
		 * Initialize the plugin and set initialize flag on true
		 */
		init: function () {

			var that = this;
			this.conf = this.config;
			this.settings = this.defaultSettings;

			that.initializeButtons();
			//that.bindInteractions();
			//that.subscribeEvents();

		},

		/*getSelected : function() {
		 if(window.getSelection) { return window.getSelection(); }
		  else if(document.getSelection) { return document.getSelection(); }
		  else {
		    var selection = document.selection && document.selection.createRange();
		    if(selection.text) { return selection.text; }
		    return false;
		  }
		  return false;
		},*/

		initializeButtons: function(){

			var
				scope = 'Aloha.continuoustext',
				that = this,
				tabWordInfo = i18nCore.t('WordInfo');

			// reset
			this.buttons = {};

			//create button et tab
			jQuery.each(this.config, function(j, button) {
				switch( button ) {
					// text level semantics:
					case 'infobulle':
						that.buttons[button] = {'button' : new Aloha.ui.Button({
							'name' : button,
							'iconClass' : 'aloha-button aloha-button-' + button,
							'size' : 'small',
							'onclick' : function (){
								var	Selection;
								Selection = rangeObject = Aloha.Selection.rangeObject;

							},
							'tooltip' : i18n.t('' + button + ''),
							'toggle' : true
						}), 'markup' : jQuery('<div id="'+button+'">'+that.Selection+'</div>')};

						FloatingMenu.addButton(
							scope,
							that.buttons[button].button,
							i18nCore.t('WordInfo'),
							1
						);
					break;

					case 'infopopup':
						that.buttons[button] = {'button' : new Aloha.ui.Button({
							'name' : button,
							'iconClass' : 'aloha-button aloha-button-' + button,
							'size' : 'small',
							'onclick' : function () {

							},
							'tooltip' : i18n.t('' + button + ''),
							'toggle' : true
						}), 'markup' : jQuery('<div id="'+button+'">'+that.getSelected+'</div>')};

						FloatingMenu.addButton(
							scope,
							that.buttons[button].button,
							i18nCore.t('WordInfo'),
							1
						);
					break;

					case 'infocadre':
					that.buttons[button] = {'button' : new Aloha.ui.Button({
							'name' : button,
							'iconClass' : 'aloha-button aloha-button-' + button,
							'size' : 'small',
							'onclick' : function () { that.WordinfoEncadre(jQuery('<div class="encadrearticle"></div>'))},
							'tooltip' : i18n.t('' + button + ''),
							'toggle' : true
						}), 'markup' : jQuery('<div class="encadrearticle"></div>')};

						FloatingMenu.addButton(
							scope,
							that.buttons[button].button,
							i18nCore.t('WordInfo'),
							1
						);
					break;
				}
			});
			//FloatingMenu.createScope(this.name, 'Aloha.empty');
		},

		WordinfoEncadre : function(markup)
		{
		  	var
		  		that = this,
	        	range = Aloha.Selection.getRangeObject();

	        if (Aloha.activeEditable) {
	                var foundMarkup = that.SearchForMarkup(range, markup);
	                if ( foundMarkup )
	                {
		                that.removeEncadre(range, markup);
	                } else {
	                	that.insertEncadre(range, markup);
	                }
	        }
		},

		SearchForMarkup : function (range, markup)
		{
			var
				//markup = jQuery('<div class="encadrearticle"></div>'),
				rangeObject = Aloha.Selection.rangeObject,
				foundMarkup;

				//alert(markup);
				//alert(rangeObject);


			// check whether the markup is found in the range (at the start of the range)
			foundMarkup = rangeObject.findMarkup(function() {
				return this.nodeName.toLowerCase() == markup.get(0).nodeName.toLowerCase();
			}, Aloha.activeEditable.obj);

	        /*if ( typeof range == 'undefined' )
	        {
	        	var range = Aloha.Selection.getRangeObject();
	        }

			return range.findMarkup(function()
	        {
	        	return this.nodeName.toLowerCase() == 'a';
	        }, Aloha.activeEditable.obj);*/
		},

		dump : function (arr,level) {
			var dumped_text = "";
			if(!level) level = 0;

			//The padding given at the beginning of the line.
			var level_padding = "";
			for(var j=0;j<level+1;j++) level_padding += "    ";

			if(typeof(arr) == 'object') { //Array/Hashes/Objects
				for(var item in arr) {
					var value = arr[item];

					if(typeof(value) == 'object') { //If it is an array,
						dumped_text += level_padding + "'" + item + "' ...\n";
						dumped_text += dump(value,level+1);
					} else {
						dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";
					}
				}
			} else { //Stings/Chars/Numbers etc.
				dumped_text = "===>"+arr+"<===("+typeof(arr)+")";
			}
			return dumped_text;
		},


		insertEncadre : function(range, markup)
		{
			var that = this;

			//alert(that.dump(range));

			/*
			this.startContainer = browserRange.startContainer;
	    this.endContainer = browserRange.endContainer;

			*/
			//alert(that.dump(range.startContainer.(0)));

			    /*for (var i in a = range.startContainer.data)
			    {
			    alert(a[i]);
			    }*/

			var startOffset = range.startOffset;
	   		var endOffset = range.endOffset;


			alert(that.dump(range.commonAncestorContainer.innerHTML));
						alert(that.dump(range.commonAncestorContainer));
			var textlength = range.commonAncestorContainer.innerHTML.length;

			//alert(that.dump(range.commonAncestorContainer));
			alert(textlength);
			alert(startOffset);
			alert(endOffset);


		/*	alert(that.dump(range.commonAncestorContainer));
			alert(that.dump(range.commonAncestorContainer));
			alert(that.dump(range.commonAncestorContainer));


			/*undefined    'parentElement' ...
undefined    'childNodes' ...
undefined    'firstChild' ...
undefined    'lastChild' ...
undefined    'previousSibling' ...
undefined    'nextSibling' ...
undefined    'attributes' ...
undefined    'ownerDocument' ...



/*alert(that.dump(range.startContainer));
alert(that.dump(range.endContainer));
alert(that.dump(range.startOffset));
alert(that.dump(range.endOffset));*/
/*
alert(that.dump(range.limitObject));
alert(that.dump(range.markupEffectiveAtStart));
alert(that.dump(range.unmodifiableMarkupAtStart));
alert(that.dump(range.splitObject));
alert(that.dump(range.selectionTree));*/


			//alert(that.dump(range..startContainer));
			/*alert(that.dump(range.endContainer.length));
			alert(that.dump(range.endContainer));
			alert(that.dump(range.startOffset));
			alert(that.dump(range.endOffset));
			alert(that.dump(range.endContainer.parentNode));
			alert(that.dump(range.endContainer.parentElement));
			alert(that.dump(range.endContainer.childNodes));
			//alert(that.dump(range.endContainer));
			/*alert(that.dump(range.startOffset));
			alert(that.dump(range.endOffset));
			alert(that.dump(range.correctRange));*/

			//alert(that.dump(markup));

			// when the range is collapsed, extend it to a word
			if (range.isCollapsed()) {
				GENTICS.Utils.Dom.extendToWord(range);
			}

			/*jQuery().append('<div class="encadrearticle"></div>');
			jQuery().append(''+range.endContainer.data+'')*/

			// add the markup
			GENTICS.Utils.Dom.addMarkup(range, markup);
			// select the modified range
			//range.select();
			return false;
		},

		RemoveEncadre : function(range, markup)
		{
			// remove the markup
			if (range.isCollapsed()) {
				// when the range is collapsed, we remove exactly the one DOM element
				GENTICS.Utils.Dom.removeFromDOM(foundMarkup, range, true);
			} else {
				// the range is not collapsed, so we remove the markup from the range
				GENTICS.Utils.Dom.removeMarkup(range, markup, Aloha.activeEditable.obj);
			}
			range.select();
			return false;
		},

		toString: function () {
			return 'format';
		}
	});
});


