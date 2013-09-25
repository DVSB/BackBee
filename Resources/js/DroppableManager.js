/* 
 * DroppableManager
 *	Gère l'ensemble des élements droppables, qui eux même contiennent les élements draggables
 */

var DroppableManager = function(){
	
    /* Statics  - */
    DroppableManager.S_DROPPABLE =           ".droppable_area";
    DroppableManager.S_DRAGGABLE_ITEM =      ".draggable_item";
    DroppableManager.S_DRAGGABLE_HANDLER =   ".move_handler";
    DroppableManager.CL_PLACEHOLDER =        "item_placeholder";
	
    DroppableManager.PRE_DOM_ID =            "droppable_area_";     // Prefix for droppable area dom id
	
    // HTML Attributes on droppable and draggable items
    DroppableManager.D_MAX_ITEM =            "data-max_item";       // Define the max number of item accepted by a droppable area
    DroppableManager.D_ACCEPTED_ITEM =       "data-accepted_items"; // Define a list of item type accepted by a droppable area (items's id are separeted by ","
    DroppableManager.D_ITEM_TYPE =           "data-item_type";      // Define the item type of a draggable element
    DroppableManager.D_ITEM_ID =             "data-item_id";
    DroppableManager.D_DROPPABLE_ID =        "data-droppable_id";
    DroppableManager.D_DROPPABLE_WIDTH =     "data-droppable_width";
    DroppableManager.D_MIN_WIDTH_DROPPABLE = "data-min_width_droppable"; 
    DroppableManager.D_MAX_WIDTH_DROPPABLE = "data-max_width_droppable";
    DroppableManager.D_DRAG_ENABLED =        "data-drag_enabled";
    DroppableManager.D_DROPPABLE_ID =        "data-droppable_id";
	
	
    // Counter for item IDs
    DroppableManager.CURRENT_ITEM_ID = 0;
	
    var t_Droppables = [];
    var nb_droppable = 0;
	
    this._init = function(){
        // Init droppables areas and draggables items by parsing the dom.
        jQuery(DroppableManager.S_DROPPABLE).each(function(i, element){
    		
            // Affect a "data-droppable-id" to each droppable area. This id match the index of droppable in the droppable array this.t_Droppable
            jQuery(element).attr(DroppableManager.D_DROPPABLE_ID, i).sortable({
                connectWith: DroppableManager.S_DROPPABLE,
                placeholder: DroppableManager.CL_PLACEHOLDER,
                forcePlaceholderSize: true
            });
    		
            // Create Droppable Objects and build t_Droppables array
            var accepted_items = jQuery(element).attr(DroppableManager.D_ACCEPTED_ITEM);    		
            if(accepted_items)
                accepted_items = accepted_items.split(",");
            else
                accepted_items = false;
    		
            var nb_max_item = jQuery(element).attr(DroppableManager.D_MAX_ITEM);
    		
            jQuery(element).attr(DroppableManager.D_DROPPABLE_ID , nb_droppable); // Unique Id for droppable area
    		
            t_Droppables[nb_droppable] = new Droppable(nb_droppable, accepted_items, nb_max_item);
            t_Droppables[nb_droppable++]._init(); 
        });
    	
    	
        /** Create bridges between drag/drop events and Draggables/Droppables methods treating those events **/
    	
        // On drag start
        jQuery(DroppableManager.S_DROPPABLE).bind('sortstart', {
            DM_object: this
        }, function(event, ui){
            var dragged_item_id = jQuery(ui.item).attr(DroppableManager.D_ITEM_ID);
            var DraggableObject = event.data.DM_object.getDraggableByItemID(dragged_item_id);
    			
            // Call the sortstart event method of the Draggable item in dragging
            DraggableObject.onDragStart();
        });
    	
    	
        // On drag over (when the dragged item is over a new droppable area)
        jQuery(DroppableManager.S_DROPPABLE).bind('sortover', {
            DM_object: this
        }, function(event, ui){
            var dragged_item_id = jQuery(ui.item).attr(DroppableManager.D_ITEM_ID);
            var droppable_id = jQuery(event.target).attr(DroppableManager.D_DROPPABLE_ID);
    		
            var DraggableObject = event.data.DM_object.getDraggableByItemID(dragged_item_id);
            var DroppableObject = event.data.DM_object.getDroppableByID(droppable_id);
    		
            // Call the sortover event method of the Draggable item in dragging
            DraggableObject.onDragOver(DroppableObject);
  			
            // Call sortover metho of Droppable object
            DroppableObject.onDragOver(DraggableObject);
        });
    	
        // On drop Item
        jQuery(DroppableManager.S_DROPPABLE).bind('sortreceive', {
            DM_object: this
        }, function(event, ui){
            var dragged_item_id = jQuery(ui.item).attr(DroppableManager.D_ITEM_ID);
            var droppable_id = jQuery(event.target).attr(DroppableManager.D_DROPPABLE_ID);
    		
            var DraggableObject = event.data.DM_object.getDraggableByItemID(dragged_item_id);
            var DroppableObject = event.data.DM_object.getDroppableByID(droppable_id);
    		
            // Call the sortover event method of the Draggable item in dragging
            //DraggableObject.onDropped(DroppableObject);
  			
            // Call sortover metho of Droppable object
            DroppableObject.onDropItem(DraggableObject, ui.sender);
        });
    }
    
    /** 
     * Retourne un draggable item object à partir de son item id
     *	Return: Draggable Object reference if matched.
     *			If not found: return false
     */
    this.getDraggableByItemID = function(draggable_item_id) {
        // Parsing Droppables object array
        for(var i = 0; i < t_Droppables.length; ++i)
        {
            // Parsing Draggable object array
            for(var j = 0; j < t_Droppables[i].getNbDraggable(); ++j)
            {
                // if item_id matched, return reference to object
                if(t_Droppables[i].t_Draggables[j].getItemID() == draggable_item_id)
                    return t_Droppables[i].t_Draggables[j];	
            }
        }
        return false;
    }
    
    /** 
     * Retourne un droppable object à partir de son droppable id
     *	Return: Draggable Object reference if matched.
     *			If not found: return false
     */
    this.getDroppableByID = function(droppable_id) {
        // Parsing Droppables object array
        for(var i = 0; i < t_Droppables.length; ++i)
        {
            // if item_id matched, return reference to object
            if(t_Droppables[i].getDroppableId() == droppable_id) 
                return t_Droppables[i];	
        }
        return false;
    }
}


