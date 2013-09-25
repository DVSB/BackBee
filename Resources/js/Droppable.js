/* 
 * Droppable
 * 	Zone de drop d'éléments draggable
 */

var Droppable = function(droppable_id, accepted_items, nb_max_item){
    var self = this;
	
    var _droppable_id = droppable_id;	
    var _accepted_items = typeof(accepted_items) == 'undefined' ? false : accepted_items; // Array containing Ids of accepted items - If value = "-1", accept all items
    var _nb_max_item = typeof(nb_max_item) == 'undefined' ? -1 : nb_max_item; 
    var _width = -1; // Width of the draggable (integer). 
    
    this.t_Draggables = [];
    var nb_draggable = 0;
	
    this._init = function(){
        _width = typeof(jQuery('[' + DroppableManager.D_DROPPABLE_ID +'='+ _droppable_id + ']').attr(DroppableManager.D_DROPPABLE_WIDTH)) == 'undefined' ? -1 : parseInt(jQuery('[' + DroppableManager.D_DROPPABLE_ID +'='+ _droppable_id + ']').attr(DroppableManager.D_DROPPABLE_WIDTH));
    	
        	
        // Parcours le droppable pour construire le tableau de Draggable
        jQuery('[' + DroppableManager.D_DROPPABLE_ID +'='+ _droppable_id + ']').children(DroppableManager.S_DRAGGABLE_ITEM).each(function(i, element){
            var dataItemID = DroppableManager.CURRENT_ITEM_ID++; // TODO: item id incrémenté par droppable manager
            var typeID = jQuery(element).attr(DroppableManager.D_ITEM_TYPE);
            var dragEnabled = typeof(jQuery(element).attr(DroppableManager.D_DRAG_ENABLED)) != 'undefined' ? jQuery(element).attr(DroppableManager.D_DRAG_ENABLED) : true;
            var minWidthDroppable = typeof(jQuery(element).attr(DroppableManager.D_MIN_WIDTH_DROPPABLE)) != 'undefined' ? jQuery(element).attr(DroppableManager.D_MIN_WIDTH_DROPPABLE) : -1;
            var maxWidthDroppable = typeof(jQuery(element).attr(DroppableManager.D_MAX_WIDTH_DROPPABLE)) != 'undefined' ? jQuery(element).attr(DroppableManager.D_MAX_WIDTH_DROPPABLE) : -1; 
			
            jQuery(element).attr(DroppableManager.D_ITEM_ID, dataItemID);
			
            self.t_Draggables[nb_draggable] = new Draggable(dataItemID, typeID, dragEnabled, minWidthDroppable, maxWidthDroppable);
            self.t_Draggables[nb_draggable++]._init();
        });
    }
    
    /** 
     * Called when a dragged item is over this droppable area
     *	Draggable: The currently dragged item
     */
    this.onDragOver = function(Draggable) {
        // Gestion des largeurs si le draggable a des largeur min et/ou max de défini
        if(_width > -1)
        {
            var minW = Draggable.getMinWidthDroppable();
            var maxW = Draggable.getMaxWidthDroppable();
			
            jQuery('#debug_area').html(minW + '|' + maxW + ' --- this = ' + _width);
			
            if(minW > -1 && _width < minW)
            {
                jQuery('.' + DroppableManager.CL_PLACEHOLDER).css("display", 'none');
                return;
            }
            else
                jQuery('.' + DroppableManager.CL_PLACEHOLDER).css("display", 'block');
	    		
            if(maxW > -1 && _width > maxW)
            {
                jQuery('.' + DroppableManager.CL_PLACEHOLDER).css("display", 'none');
                return;
            }
            else
                jQuery('.' + DroppableManager.CL_PLACEHOLDER).css("display", 'block');
        }
        else
            jQuery('.' + DroppableManager.CL_PLACEHOLDER).css("display", 'block');
    				
        return;	
        // Gestion des éléments acceptés	
        if(_accepted_items == false) 
        {
            jQuery('.' + DroppableManager.CL_PLACEHOLDER).css("display", 'block');
            return;
        }
    		
        draggableTypeID = Draggable.getTypeID();
        if(draggableTypeID > -1 && jQuery.inArray(draggableTypeID, _accepted_items) == -1)
        {
            jQuery('.' + DroppableManager.CL_PLACEHOLDER).css("display", 'none');
        }
        else
        {
            jQuery('.' + DroppableManager.CL_PLACEHOLDER).css("display", 'block');
        }
    }
    
    /**
     *	When an item is dropped
     *		Id Draggable is not allowed, returns it back to his original droppable area
     */
    this.onDropItem = function(Draggable, uiSender) {
        if(_width > -1)
        {
            var minW = Draggable.getMinWidthDroppable();
            var maxW = Draggable.getMaxWidthDroppable();
	
            if(minW > -1 && _width < minW)
            {
                jQuery(uiSender).sortable('cancel');
                return;
            }
	    		
            if(maxW > -1 && _width > maxW)
            {
                jQuery(uiSender).sortable('cancel');
                return;
            }
        }
        if(_accepted_items == false) 
            return;
    		
        draggableTypeID = Draggable.getTypeID();
        if(draggableTypeID > -1 && jQuery.inArray(draggableTypeID, _accepted_items) == -1)
            jQuery(uiSender).sortable('cancel');
    }
    
    /**
     *	Disable the droppable 
     */
    this.disable = function() {
        jQuery('[' + DroppableManager.D_DROPPABLE_ID +'='+ _droppable_id + ']').sortable('cancel');
    }
    
    this.getNbDraggable = function() {
        return nb_draggable;
    }
    this.getDroppableId = function() {
        return _droppable_id;
    }
}