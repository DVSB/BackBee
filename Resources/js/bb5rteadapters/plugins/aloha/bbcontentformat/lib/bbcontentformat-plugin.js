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
                    removeAttrs:["data-bbcontentref","aloha-editable-active","aloha-editable","contentAloha","data-minentry","data-forbidenactions",
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
    *  this content handler allows us to control what can be pasted inside a p or div tag
    *  */
define("bbcontentformat/bb5ParagraphCleaner",["aloha","jquery",htmlClean,'aloha/contenthandlermanager'],
    function(Aloha, $, htmlClean, ContentHandlerManager){
        
        
        var paragraphContentHandler = ContentHandlerManager.createHandler({
            enabled: true,
            defaultSettings: {
                removeTags: []
            },
            handleContent: function(content){
                var currentObject = Aloha.getActiveEditable();
                if(!currentObject || !("obj" in currentObject)) return content;
                currentObject = currentObject.obj;
                var isP = $(currentObject).is("p");
                var isDiv = $(currentObject).is("div");
                if(isP || isDiv){
                    var htmlCleanerConfig = Aloha.settings.contentHandler.handler.bb5ParagraphCleaner.config;
                    if( Aloha.settings.contentHandler &&
                        Aloha.settings.contentHandler.handler &&
                        Aloha.settings.contentHandler.handler.bb5ParagraphCleaner &&
                        Aloha.settings.contentHandler.handler.bb5ParagraphCleaner.config
                        ){
                        var cleaningSettings = $.extend(true,this.defaultSettings,htmlCleanerConfig);
                        content = bb.jquery.htmlClean(content,cleaningSettings); 
                        /* Replace <p>-->"" and </p> by something else */
                        if(isP){
                            var startPpattern = /<p>/g; 
                            var endPpattern =/<\/p>/g;
                            content = content.replace(startPpattern,""); 
                            content = content.replace(endPpattern,"<br>"); 
                        }
                        
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
    "bbcontentformat/bb5ParagraphCleaner",
    'jquery'],function(Aloha, Plugin, ContentHandlerManager, BbContentHandler,paragraphContentHandler,$){
        /* create contentHandler */
        return Plugin.create('bbcontentformat', {
            init: function(){
                BbContentHandler.enabled = true;
                ContentHandlerManager.register('bb5contentCleaner', BbContentHandler);
            }    
        });          
    });
