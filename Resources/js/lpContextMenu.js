var bb = bb ||{};
LpContextMenu =  function(userConfig){
    this._settings = {
        contentSelector :null,
        menuActions : [
        {
            btnCls:"bb5-button bb5-ico-info", 
            btnLabel:"Informations", 
            btnCallback:function(e){}
        },

        {
        btnCls:"bb5-button bb5-ico-parameter", 
        btnLabel:"Paramètres", 
        btnCallback:function(e){}
    },
{
    btnCls:"bb5-button bb5-ico-select", 
    btnLabel:"Selectionner", 
    btnCallback:function(e){}
},
{
    btnCls:"bb5-button bb5-ico-lib", 
    btnLabel:"Sélecteur de contenus", 
    btnCallback:function(e){}
},
{
    btnCls:"bb5-button bb5-ico-del", 
    btnLabel:"Effacer", 
    btnCallback:function(e){}
}
            
],
menuCls : "bb5-ui bb5-context-menu",
actionBuilder : null 
};
   
this._isEnabled = false;
this._template = $("<div><ul></ul></div>").clone(); 
this.contextMenu = null;
this.contextMenuTarget = null;
this.beforeShow = null;
    
if(typeof this._init!= "function"){
    LpContextMenu.prototype._init = function(userConfig){
        this._settings = $.extend(true,this._settings,userConfig);
        this.contextMenu = this.buildContextmenu();
        this.beforeShow = (typeof this._settings.beforeShow === "function") ? this._settings.beforeShow : $.noop;
        this._bindEvents();
    } 
}
    
this._applyFilters = function(filters){
    var self = this;
    var filters = ($.isArray(filters)) ? filters : []; 
    $(this.contextMenu).find("li").show();
    $(filters).each(function(i,filter){
        $(self.contextMenu).find("."+filter).eq(0).parent().hide(); //hide li
    });
}
    
/*Default builder*/
this.defaultBuilder = function(btnInfo){
    var btnWrapper = $("<li></li>").clone(); 
    var btnTpl = $("<button></button>");
    $(btnTpl).addClass(btnInfo.btnCls).text(btnInfo.btnLabel);
    var self = this; 
    $(btnTpl).bind("click",function(e){
        btnInfo.btnCallback.call(this,e,self.contextMenuTarget,btnInfo.btnType);
        self.hide();
        return false;
    });
    $(btnTpl).attr("data-type",btnInfo.btnType);
    $(btnTpl).appendTo($(btnWrapper));
    return btnWrapper;
} 
    
   
LpContextMenu.prototype._bindEvents = function(){
    $(this._settings.contentSelector).live("contextmenu",$.proxy(this.show,this));
}
    
LpContextMenu.prototype.setFilters = function(filters){
    this.filters = ($.isArray(filters)) ? filters : [];
}
    
LpContextMenu.prototype.show = function(e,filters){
            
    if(!this._isEnabled) return false;
    this.beforeShow.call(this,e.currentTarget);
    e.preventDefault();
    e.stopPropagation();
    var position = {
        left:e.pageX,
        top:e.pageY
    };
            
    $(this.contextMenu).css({
        position:"absolute",
        left: position.left+"px",
        top: position.top+"px"
    });
            
    this.contextMenuTarget = $(e.currentTarget);
    this._applyFilters(this.filters);
    $(this.contextMenu).show();
}
    
    
LpContextMenu.prototype.hide = function(e){
    $(this.contextMenu).hide();
}

LpContextMenu.prototype.disable = function(){
    this._isEnabled = false;
    $(this.contextMenu).hide();   
}
    
LpContextMenu.prototype.enable = function(){
    this._isEnabled = true;
}
    
LpContextMenu.prototype.setFilters = function(filters){
    this.filters = ($.isArray(filters)) ? filters : [];
        
}
    
LpContextMenu.prototype.buildContextmenu = function(){
    var self = this;
    $(this._template).addClass(this._settings.menuCls);
    var linksContainer = document.createDocumentFragment();
    $.each(this._settings.menuActions,function(btnType,item){
        item.btnType = btnType;
        var item = self.defaultBuilder(item);
        linksContainer.appendChild($(item).get(0));
    });
    $(this._template).find('ul').html(linksContainer);
    $(this._template).hide().appendTo($("body"));
    return $(this._template);   
}  
this._init(userConfig);
}
bb.LpContextMenu = LpContextMenu;