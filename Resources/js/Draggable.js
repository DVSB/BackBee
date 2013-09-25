/* 
 * Draggable Object
 *	Gère un élément draggable
 */

var Draggable = function(dataItemID, typeID, dragEnabled, minWidthDroppable, maxWidthDroppable){
    var _dataItemID = typeof(dataItemID) == 'undefined' ? -1 : dataItemID;
    var _typeID = typeof(typeID) == 'undefined' ? -1 : typeID;
    var _dragEnabled = typeof(dragEnabled) == 'undefined' ? true : dragEnabled;

	
    var _minWidthDroppable = typeof(minWidthDroppable) == 'undefined' ? -1 : parseInt(minWidthDroppable); // The minimum columns that droppable area must have to get this draggable. If "-1", there is no minimum column.
    var _maxWidthDroppable = typeof(maxWidthDroppable) == 'undefined' ? -1 : parseInt(maxWidthDroppable);
	
    this._isDroppable = false; // If true, this element could receive draggables
	
	
    this._init = function(){
    // Init droppables areas and draggables items by parsing the dom.
    	
    }
   
    
    // Called when draggable start being drag
    this.onDragStart = function(){
    	
    }
    
    /** 
     * Called when item is over a new droppable area
     *	NewDroppable: Droppable Object
     */
    this.onDragOver = function(NewDroppable) {
    // TODO: test largeur min max
    	
    }
    
    // Accesseurs
    this.getItemID = function() {
        return _dataItemID;
    }
    this.getTypeID = function() {
        return _typeID;
    }
    
    this.getMinWidthDroppable = function() {
        return _minWidthDroppable;
    }
    this.getMaxWidthDroppable = function() {
        return _maxWidthDroppable;
    }
}


