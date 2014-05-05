/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Exposes a new contentHandler
 * 
 **/

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
    
    


/*
function pasteHtmlAtCaret(html, selectPastedContent) {
    var sel, range;
    if (window.getSelection) {
        // IE9 and non-IE
        sel = window.getSelection();
        if (sel.getRangeAt && sel.rangeCount) {
            range = sel.getRangeAt(0);
            range.deleteContents();

            // Range.createContextualFragment() would be useful here but is
            // only relatively recently standardized and is not supported in
            // some browsers (IE9, for one)
            var el = document.createElement("div");
            el.innerHTML = html;
            var frag = document.createDocumentFragment(), node, lastNode;
            while ( (node = el.firstChild) ) {
                lastNode = frag.appendChild(node);
            }
            var firstNode = frag.firstChild;
            range.insertNode(frag);

            // Preserve the selection
            if (lastNode) {
                range = range.cloneRange();
                range.setStartAfter(lastNode);
                if (selectPastedContent) {
                    range.setStartBefore(firstNode);
                } else {
                    range.collapse(true);
                }
                sel.removeAllRanges();
                sel.addRange(range);
            }
        }
    } else if ( (sel = document.selection) && sel.type != "Control") {
        // IE < 9
        var originalRange = sel.createRange();
        originalRange.collapse(true);
        sel.createRange().pasteHTML(html);
        if (selectPastedContent) {
            range = sel.createRange();
            range.setEndPoint("StartToStart", originalRange);
            range.select();
        }
    }
}
*/
