

var bb = (bb) ? bb : {};

(function($){
    
$.loadScript = function(url, options) {
    options = bb.jquery.extend(options || {}, {
        dataType: 'script',
        async: false,
        cache: true,
        url: bb.baseurl+url
    });
    
    return bb.jquery.ajax(options);
};

    bb = {
        jquery: jq172,
        isloaded: false,
        baseurl: '',
        resourcesdir: 'ressources/',
        storedsitepadding: 0,
        fixedelements: [],
        
        i18n: {
            loading: 'Loading...'
        },
        
        libs: [
        'js/libs/requirejs/require.js',
        'js/bb.require.map.js',//js module config
        
        'js/libs/jquery.event.mousestop.js', //fix mouseenter/mouseleave when moving fast
        'js/libs/jquery-ui-1.8.24.custom.min.js',//to fix sortable jitter and flaky effects and fucking datepicker
        'js/libs/jquery.loadmask.min.js',
        'js/libs/jquery.jstree.js',
        'js/libs/jquery.jscrollpane.min.js',
        'js/libs/jquery-ui-timepicker-addon.js',
        'js/libs/json2.js',
        'js/libs/jquery.jsonrpc.js',
        'js/libs/jquery.filedrop.js',
        'js/libs/jquery.templates.js',
        'js/libs/jquery.layout-latest.min.js',
        'js/libs/jquery.fileDownload.js',
            
        'js/colorpicker.js',
        'js/bb.i18n.js',
        'js/less-1.3.0.min.js',
        'js/jquery.ui.selectgroup.js',
        'js/libs/jquery.paging.js',
        'js/libs/bootstrap.min.js',
        'js/jquery.bxSlider.mod.min.js',
        'js/jquery.ui.selectgroup.js',
        'js/script.js',
        'js/Utils.js',
            
        'js/dbmanager.js',
        'js/min/bb.StateManager.js',
        'js/bb.upload.js',
        'js/bb.authmanager.js',
        'js/bb.webservice.js',
        'js/bb.jstree.rpc_data.js',
        'js/bb.jquery-ui.js',
        'js/bb.ui-bbUtilsPager.js',
        'js/bb.ui.bbSearchEngine.js',
        'js/bb.ui.bbPageSelector.js',
        'js/bb.ui.bbLinkSelector.js',
        'js/bb.ui.bbMediaSelector.js',
        'js/bb.ui.bbSelector.js',
        'js/bb.ui.bbPageBrowser.js',
        'js/bb.ui.bbKeywordBrowser.js',
        'js/bb.ui.bbContentSelector.js',
        'js/bb.ui.bbMediaImageUpload.js',
        'js/bb.ui.bbContentTypeBrowser.js',
        'js/bb.contentPluginManager.js',
        'js/bb.ContentWrapper.js',
        'js/min/bb.UserPreferences.js',
        'js/lpTab.js',
        'js/lpContextMenu.js',
        'js/bb.ManagersContainer.js',
            
        'js/AlohaManager.js',
        'js/FilterManager.js',
        'js/LayoutManager.js',
        'js/StatusManager.js',
        'js/ToolsbarManager.js',
        'js/ToolsbarTheme.js',
        'js/ToolsbarEdition.js',
        'js/ToolsbarContent.js',
        'js/ContentManager.js',
        'js/ContentEditionManager.js',
        'js/ToolsbarLayout.js',
        'js/ToolsbarStatus.js',
        'js/ToolsbarBundle.js',
        'js/PopupManager.js',
        'js/bb.FormBuilder.plugins.js',
        'js/FrontApplication.js', 
        'js/i18n/fr.js',
        'js/min/bb.Notify.js'
        ],
        
        config: {
            maxFileSize: 10,
            mediaFileSize:{
                pdf: 15,
                image: 10,
                flash: 15,
                zip: 15
            }
        },
        
        webserviceManagerConfig: {
            endPoint: 'index.php',
            webservices: [{
                name: 'ws_local_user',
                namespace: 'BackBuilder_Services_Local_User'
            }, {
                name: 'ws_local_site',
                namespace: 'BackBuilder_Services_Local_Site'
            }, {
                name: 'ws_local_page',
                namespace: 'BackBuilder_Services_Local_Page'
            }, {
                name: 'ws_local_mediafolder',
                namespace: 'BackBuilder_Services_Local_MediaFolder'
            }, {
                name: 'ws_local_media',
                namespace: 'BackBuilder_Services_Local_Media'
            }, {
                name: 'ws_local_layout',
                namespace: 'BackBuilder_Services_Local_Layout'
            }, {
                name :'ws_local_less',
                namespace:'BackBuilder_Services_Local_Less'
            }, {
                name : 'ws_local_contentBlock',
                namespace:'BackBuilder_Services_Local_ContentBlocks'
            }, {
                name : 'ws_local_revision',
                namespace:'BackBuilder_Services_Local_Revision'
            }, {
                name: "ws_local_classContent",
                namespace:"BackBuilder_Services_Local_ClassContent"
            }, {
                name: "ws_local_bundle",
                namespace:"BackBuilder_Services_Local_Bundle"
            },
            {
                name: "ws_local_keyword",
                namespace:"BackBuilder_Services_Local_Keyword"
            },
             {
                name: "ws_local_keyword",
                namespace:"BackBuilder_Services_Local_Keyword"
            },
            {
                name: "ws_local_config",
                namespace:"BackBuilder_Services_Local_Config"
            }
            ]
        },
                
        uploadManagerConfig: {
            endPoint: 'index.php',
            uploads: [{
                name: 'ws_local_media',
                namespace: 'BackBuilder_Services_Local_Media'
            }]
        },
        
        init: function() {
            if (bb.isloaded)
                return;
          bb.jquery(bb).trigger("bb.loading.start");
           
            baseurl = bb.jquery('#bb5-scripts').attr('src');
            if ('undefined' != typeof(baseurl))
                bb.baseurl = baseurl.replace(bb.resourcesdir+'js/bb.js', '');
            
            bb.loadLibs();

            /*Tree themes*/
            bb.jquery.jstree._themes = bb.baseurl+bb.resourcesdir+'css/jstree/';
            
            /*Scripts*/
            //bb.loadScripts();

            /*AuthManager*/
            bb.authmanager.init();
            
            /*RPC*/
            bb.webserviceManager.setup(bb.webserviceManagerConfig);

            /*Upload*/
            bb.uploadManager.setup(bb.uploadManagerConfig); 
             bb.jquery(bb).trigger("bb.loading.end");
            bb.isloaded = true;
        },
        
        loadLibs: function() {
            jQueryTmp = jQuery;
            jQuery = bb.jquery;
            bb.jquery.each(bb.libs, function(index, script) {
                bb.jquery.loadScript(bb.resourcesdir+script);
            });

            jQuery = jQueryTmp;
        },
        
        loadScripts: function() {
            bb.jquery.each(bb.scripts, function(index, script) {
                bb.jquery.loadScript(bb.resourcesdir+script);
            });
        },
        
        start: function(event) {
            if ('undefined' != typeof(event)) {
                if (!event.altKey || !event.ctrlKey || 66 !== event.keyCode) {
                    return;
                } else {
                    if (!bb.isloaded && document.getElementById('bb-loader')) {
                        document.getElementById('bb-loader').style.display = "block";
                    }
                }
            }
            setTimeout(function(){
                if (!bb.isloaded) {
                    bb.init();
                }
                if (document.getElementById('bb-loader')) {
                    document.getElementById('bb-loader').style.display = "none";
                }
                bb.webserviceManager.getInstance('ws_local_user').request('getUser', {
                    //                useCache: true,
                    //                cacheTags:["userSession"],
                    success: function(response) {
                        if (0 == bb.jquery('#bb5-toolbar-wrapper').length) {
                            document.location.reload();
                            return false;
                        }
                        bb.jquery(bb).trigger('bb.started');
                        bb.frontApplication.init(response.result);                
                    },
                    error: function(result) {
                        bb.end();
                    }
                });
            }, 0);
        },
        
        end: function() {
            bb.jquery(bb).trigger('bb.ended');
            bb.authmanager.logoff();
            
            for(i =0; i< bb.fixedelements.length; i++)
                bb.jquery(bb.fixedelements[i]).css('top', (1*bb.jquery(bb.fixedelements[i]).css('top').replace('px', '') - 3 - 1*bb.jquery('#bb5-toolbar-wrapper').css('height').replace('px', '')) + 'px');
            bb.jquery('#bb5-site-wrapper').css('padding-top', bb.storedsitepadding + 'px');
            bb.jquery('#bb5-toolbar-wrapper').hide();
            
            bb.isloaded = false;
        },
        
        onKeyup: function(event) {
        //console.log(event);
        }
    };
  bb.core = bb.core || {};  


bb.jquery(document).bind('keyup', bb.start);
bb.jquery(document).ready(function() {
    if (-1 != window.location.search.indexOf('bb5-autostart')) {
        window.location.search = window.location.search.replace('bb5-autostart', '');
        bb.start();
    }
});

})(jq172);