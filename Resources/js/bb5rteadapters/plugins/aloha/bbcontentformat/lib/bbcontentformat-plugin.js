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
    
    

define([
    'aloha',
    'aloha/plugin',
    'aloha/contenthandlermanager',
    "bbcontentformat/bb5contenthandler",
    'jquery'],function(Aloha,Plugin,ContentHandlerManager,BbContentHandler,$){
    
        /* create contentHandler */
        return Plugin.create('bbcontentformat', {
            init: function(){
                BbContentHandler.enabled = true;
                ContentHandlerManager.register('bb5contentCleaner', BbContentHandler);
            }    
        });
          
    });