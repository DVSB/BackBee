define(
['aloha', 'aloha/jquery', 'aloha/contenthandlermanager'],
function(Aloha, jQuery, ContentHandlerManager) {
    "use strict";
    var MyContentHandler = ContentHandlerManager.createHandler({
        enabled: true,
        handleContent: function( content ) {
             alert("icic");
             console.log(content);
            // do something with the content
            content = $(content).css("border","1px solid blue");
            return $(content).get(0); // return as HTML text not jQuery/DOM object
        }
    });
    ContentHandlerManager.register('radicalContent', MyContentHandler);
  
});