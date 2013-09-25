<?php
namespace BackBuilder\Services\Local;

use BackBuilder\Services\Local\AbstractServiceLocal,
    BackBuilder\Services\Exception\ServicesException,
    BackBuilder\Site\Layout as SiteLayout,
    BackBuilder\Site\Site;

/**
 * Description of Layout
 *
 * @author Nicolas BREMONT<nicolas.bremont@group-lp.com>
 */
class Layout extends AbstractServiceLocal {
    /**
     * @exposed(secured=true)
     * 
     */
    public function getLayoutsFromSite($siteId = NULL) {
        if (NULL === $this->bbapp)
            throw new ServicesException("None BackBuilder Application provided", ServicesException::UNDEFINED_APP);
        
        $site = $this->bbapp->getSite();
        if (NULL === $siteId)
            $site = $this->bbapp->getEntityManager()->getRepository('\BackBuilder\Site\Site')->find($siteId);
        
        if (NULL === $site)
            throw new ServicesException("Current BackBuilder Application has to be start with a valid site", ServicesException::UNDEFINED_SITE);
        
        $response = array();
        foreach ($site->getLayouts() as $layout)
            $response[] = $layout->__toJson();
        
        return $response;
    }
    
    /**
     * @exposed(secured=true)
     * 
     */
    public function getModels() {
        if (NULL === $this->bbapp)
            throw new ServicesException("None BackBuilder Application provided", ServicesException::UNDEFINED_APP);
        
        $response = array();
        $layouts = $this->bbapp->getEntityManager()->getRepository('\BackBuilder\Site\Layout')->getModels();
        foreach ($layouts as $layout)
            $response[] = $layout->__toJson();

        return $response;
    }

    /**
     * @exposed(secured=true)
     * 
     */
    public function putTemplate($data) {
        if (NULL === $this->bbapp)
            throw new ServicesException("None BackBuilder Application provided", ServicesException::UNDEFINED_APP);
        
        $em = $this->bbapp->getEntityManager();
        $layout = $em->find('\BackBuilder\Site\Layout', $data['uid']);
        if (NULL === $layout) {
            $layout = new SiteLayout();
        }
        
        $layout->setLabel($data['templateTitle']);
        $std = new \stdClass();
        $std->templateLayouts = $data['templateLayouts'];
        $layout->setDataObject($std);
        $currentSiteUid = (isset($data['site']["uid"]))? $data['site']["uid"]: ($this->bbapp->getContainer()->has('site') ? $this->bbapp->getContainer()->get('site')->getUid() : null);
        $layout->setSite($em->find('\BackBuilder\Site\Site',$currentSiteUid));
        $layout->setPicPath($data['picpath']);
        
        $em->persist($layout);
        $em->flush();
        
        return $layout->__toJson();
    }

    /**
     * @exposed(secured=true)
     * 
     */
    public function getLayoutFromUid($uid) {
        if (NULL === $this->bbapp)
            throw new ServicesException("None BackBuilder Application provided", ServicesException::UNDEFINED_APP);
        
        $em = $this->bbapp->getEntityManager();
        $layout = $em->find('\BackBuilder\Site\Layout', $uid);

        if (NULL === $layout)
            throw new ServicesException(sptrinf('Unfound Layout with uid %s', $uid));
        
        return $layout->__toJson();
    }

    /**
     * @exposed(secured=true)
     * 
     */
    public function deleteLayout($uid) {
        if (NULL === $this->bbapp)
            throw new ServicesException("None BackBuilder Application provided", ServicesException::UNDEFINED_APP);
        
        try {
            $em = $this->bbapp->getEntityManager();
            $layout = $em->find('\BackBuilder\Site\Layout', $uid);
            
            $em->remove($layout);
            $em->flush();
        } catch (\PDOException $e) {
            if (23000 == $e->getCode())
                throw new ServicesException("One or more pages use this layout, you have to previously remove them");
        } catch (\Exception $e) {
            //Nothing to do
        }
        
        return "layout : uid=" . $uid . " has been removed";
    }

}