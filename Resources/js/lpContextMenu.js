var bb = bb ||{};

(function($) {

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
this._template = bb.jquery("<div><ul></ul></div>").clone(); 
this.contextMenu = null;
this.contextMenuTarget = null;
this.beforeShow = null;
    
if(typeof this._init!= "function"){
    LpContextMenu.prototype._init = function(userConfig){
        this._settings = bb.jquery.extend(true,this._settings,userConfig);
        this.contextMenu = this.buildContextmenu();
        this.beforeShow = (typeof this._settings.beforeShow === "function") ? this._settings.beforeShow : bb.jquery.noop;
        this._bindEvents();
    } 
}
    
this._applyFilters = function(filters){
    var self = this;
    var filters = (bb.jquery.isArray(filters)) ? filters : []; 
    bb.jquery(this.contextMenu).find("li").show();
    bb.jquery(filters).each(function(i,filter){
        bb.jquery(self.contextMenu).find("."+filter).eq(0).parent().hide(); //hide li
    });
}
    
/*Default builder*/
this.defaultBuilder = function(btnInfo){
    var btnWrapper = bb.jquery("<li></li>").clone(); 
    var btnTpl = bb.jquery("<button></button>");
    bb.jquery(btnTpl).addClass(btnInfo.btnCls).text(btnInfo.btnLabel);
    var self = this; 
    bb.jquery(btnTpl).bind("click",function(e){
        btnInfo.btnCallback.call(this,e,self.contextMenuTarget,btnInfo.btnType);
        self.hide();
        return false;
    });
    bb.jquery(btnTpl).attr("data-type",btnInfo.btnType);
    bb.jquery(btnTpl).appendTo(bb.jquery(btnWrapper));
    return btnWrapper;
} 
    
   
LpContextMenu.prototype._bindEvents = function(){
    bb.jquery(this._settings.contentSelector).live("contextmenu",bb.jquery.proxy(this.show,this));
}
    
LpContextMenu.prototype.setFilters = function(filters){
    this.filters = (bb.jquery.isArray(filters)) ? filters : [];
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
            
    bb.jquery(this.contextMenu).css({
        position:"absolute",
        left: position.left+"px",
        top: position.top+"px"
    });
            
    this.contextMenuTarget = bb.jquery(e.currentTarget);
    this._applyFilters(this.filters);
    bb.jquery(this.contextMenu).show();
}
    
    
LpContextMenu.prototype.hide = function(e){
    bb.jquery(this.contextMenu).hide();
}

LpContextMenu.prototype.disable = function(){
    this._isEnabled = false;
    bb.jquery(this.contextMenu).hide();   
}
    
LpContextMenu.prototype.enable = function(){
    this._isEnabled = true;
}
    
LpContextMenu.prototype.setFilters = function(filters){
    this.filters = (bb.jquery.isArray(filters)) ? filters : [];
        
}
    
LpContextMenu.prototype.buildContextmenu = function(){
    var self = this;
    bb.jquery(this._template).addClass(this._settings.menuCls);
    var linksContainer = document.createDocumentFragment();
    bb.jquery.each(this._settings.menuActions,function(btnType,item){
        item.btnType = btnType;
        var item = self.defaultBuilder(item);
        linksContainer.appendChild(bb.jquery(item).get(0));
    });
    bb.jquery(this._template).find('ul').html(linksContainer);
    bb.jquery(this._template).hide().appendTo(bb.jquery("body"));
    return bb.jquery(this._template);   
}  
this._init(userConfig);
}
bb.LpContextMenu = LpContextMenu;

}) (bb.jquery);