<?php

namespace BackBee\Rest\Controller;

use BackBee\NestedNode\MediaFolder;
use BackBee\Utils;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraints as Assert;
use BackBee\Rest\Controller\Annotations as Rest;

/**
 * Description of MediaController
 *
 * @author h.baptiste
 */
class MediaController extends AbstractRestController
{


    public function getCollectionAction() {
        $medias = $this->generateMedia(100);
        return $this->createResponse(json_encode($medias));
    }

    public function deleteAction(){}

    public function putAction(){}

    public function postAction(){}


    private function generateMedia($nb = 100) {
       $list = array();
       $i = 0;
       while($i <= $nb) {
          $media = new \stdClass();
          $media->uid = md5(uniqid(rand(), true));
          $media->media_folder = md5(uniqid(rand(), true));
          $media->image = 'resource/img/test.jpeg';
          $media->title = uniqid('title_');
          $media->date = new \DateTime();
          $media->content = new \StdClass();
          $list[] = $media;
         $i++;
       }
       return $list;

    }






}