/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Exposes a new contentHandler
 * 
 **/

var jQuery = bb.jquery; 
var htmlClean = bb.baseurl+bb.resourcesdir+'js/libs/jquery.htmlclean/jquery.htmlClean.min.js';  
define("bbcontentformat/bb5contenthandler", ['aloha','jquery',htmlClean,'aloha/contenthandlermanager'],
    function(Aloha, $, htmlClean, ContentHandlerManager){
        var Bb5contentPasteHandler = ContentHandlerManager.createHandler({
            enabled : false,
            handleContent: function(content){
                content = bb.jquery.htmlClean(content,{
                    allowedAttributes: [["style"]],
                    removeAttrs:["data-bbcontentref","aloha-editable-active","aloha-ed_itable","contentAloha","data-minentry","data-forbidenactions",
                    "data-parent","data-uid", "data-isloaded", "data-rendermode",
                    "data-maxentry", "data-refparent", "data-type", "data-accept",
                    "data-contentplugins", "data-element","data-rteconf", "data-parent",
                    "data-draftuid", "data-itemcontainer"]
                }); 
                return content;
            }
        }); 
        
        return Bb5contentPasteHandler;
    });
   
/**
    *
    *  new paragraph content handler
    *  this content handler allows us to control what can be pasted inside a p tag
    *  */
define("bbcontentformat/bb5ParagraphCleaner",["aloha","jquery",htmlClean,'aloha/contenthandlermanager'],
    function(Aloha, $, htmlClean, ContentHandlerManager){
        var paragraphContentHandler = ContentHandlerManager.createHandler({
            enabled: true,
            defaultSettings: {
                removeTags: ['p','ol','ul','li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'div', 'pre']
            },
            handleContent: function(content){
                var currentObject = Aloha.getActiveEditable();
                currentObject = currentObject.obj;
                if($(currentObject).is("p")){
                    var htmlCleanerConfig = Aloha.settings.contentHandler.handler.bb5ParagraphCleaner.config;
                    if( Aloha.settings.contentHandler &&
                        Aloha.settings.contentHandler.handler &&
                        Aloha.settings.contentHandler.handler.bb5ParagraphCleaner &&
                        Aloha.settings.contentHandler.handler.bb5ParagraphCleaner.config
                        ){
                        var cleaningSettings = $.extend(true,this.defaultSettings,htmlCleanerConfig);
                        content = bb.jquery.htmlClean(content,cleaningSettings); 
                    }                
                }
                return content;
            }
        });        
        /* register the content handler */
        ContentHandlerManager.register('bb5ParagraphCleaner', paragraphContentHandler);
    });
   
   
   
   

define([
    'aloha',
    'aloha/plugin',
    'aloha/contenthandlermanager',
    "bbcontentformat/bb5contenthandler",
    'jquery'],function(Aloha, Plugin, ContentHandlerManager, BbContentHandler,$){
        /* create contentHandler */
        return Plugin.create('bbcontentformat', {
            init: function(){
                BbContentHandler.enabled = true;
                ContentHandlerManager.register('bb5contentCleaner', BbContentHandler);
            }    
        });          
    });
