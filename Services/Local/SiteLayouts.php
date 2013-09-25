<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 * 
 * Renvoie la liste des templates 
 * 
 * {ui:1,templateKey:"1_2",templateTitle:"4 colonnes",templateLayouts:[],picPath:""},
 * 
 * 
 */
namespace BackBuilder\Services\Local;
use BackBuilder\Services\Local\AbstractServiceLocal;
class SiteLayouts  extends AbstractServiceLocal {
    
    
    /**
     * @exposed(secured=true)
     */
    public function getSiteLayouts(){
        $sitesLayoutContainer = array();
        /*layout 1*/
        $layoutItem = new \stdClass();
        $layoutItem->uid = uniqid("layout_");
        $layoutItem->templateTitle = "This is my  first layout";
        $layoutItem->templateLayouts = array();
        $layoutItem->picPath = "";
        $sitesLayoutContainer[] = $layoutItem; 
        /*layout 2*/
        $layoutItem = new \stdClass();
        $layoutItem->uid = uniqid("layout_");
        $layoutItem->templateTitle = "This is my second layout";
        $layoutItem->templateLayouts = array();
        $layoutItem->picPath = "";
        $sitesLayoutContainer[] = $layoutItem; 
        /*layout 3*/
        $layoutItem = new \stdClass();
        $layoutItem->uid = uniqid("layout_");
        $layoutItem->templateTitle = "This is my theùird layout";
        $layoutItem->templateLayouts = array();
        $layoutItem->picPath = "";
        $sitesLayoutContainer[] = $layoutItem; 
        /*layout 4*/
        $layoutItem = new \stdClass();
        $layoutItem->uid = uniqid("layout_");
        $layoutItem->templateTitle = "This is my fourth layout";
        $layoutItem->templateLayouts = array();
        $layoutItem->picPath = "";
        $sitesLayoutContainer[] = $layoutItem; 
        /*layout 5*/
        $layoutItem = new \stdClass();
        $layoutItem->uid = uniqid("layout_");
        $layoutItem->templateTitle = "This is é my fith";
        $layoutItem->templateLayouts = array();
        $layoutItem->picPath = "";
        $sitesLayoutContainer[] = $layoutItem;
        return $sitesLayoutContainer;
    }
    
    /**
     * @exposed(secured=true)
     */
    public function getCompleteName($firstname="Harris",$lastname="Baptiste"){
        return "completeName : ".$firstname." ". $lastname;
    }
    
    
    
    
}
?>
