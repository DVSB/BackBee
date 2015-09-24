<?php

namespace BackBee\Rest\Tests\Controller;

use BackBee\NestedNode\KeyWord;
use BackBee\Rest\Test\RestTestCase;
use BackBee\Security\Group;
use BackBee\Site\Site;

/**
 * Test Keyword class
 */
class KeywordControllerTest extends RestTestCase {

    private $em;
    private $user;
    private $site;

    protected function setUp() {
        $this->initAutoload();
        $bbapp = $this->getBBApp();
        $bbapp->setIsStarted(true);
        $this->initDb($bbapp);
        $this->initAcl();

        $this->em = $this->getBBApp()->getEntityManager();
        $this->site = new Site();
        $this->site->setLabel('Test Site')->setServerName('test_server');

        $this->groupEditor = new Group();
        $this->groupEditor->setName('groupName');
        $this->groupEditor->setSite($this->site);

        $this->getBBApp()->getContainer()->set('site', $this->site);

        $this->em->persist($this->site);
        $this->em->persist($this->groupEditor);

        $this->em->flush();

        $this->user = $this->createAuthUser($this->groupEditor->getId());
        $this->em->persist($this->user);
        $this->em->flush();

        $this->createKeywordTree();
    }

    private function createKeywordTree() {
        $root = $this->createAKeyword("root");
        $this->createAKeyword("backbee",$root);
        $this->createAKeyword("patrov", $root);
        $this->createAKeyword("patrovski", $root);

    }

    public function testGetCollectionAction()
    {
        $url = '/rest/2/keyword';
        $response = $this->sendRequest(self::requestGet($url));
        $this->assertTrue($response->isOk(), sprintf('HTTP 200 expected, HTTP %s returned.', $response->getStatusCode()));
        $keywordCollection = json_decode($response->getContent(), true);
        $this->assertInternalType('array', $keywordCollection);
        $this->assertCount(1, $keywordCollection); //return only root
    }

    public function testGetCollectionWithTerm()
    {
      $url = '/rest/2/keyword';
      $response = $this->sendRequest(self::requestGet($url, ["term" => "pat"]));
      $this->assertTrue($response->isOk(), sprintf('HTTP 200 expected, HTTP %s returned.', $response->getStatusCode()));
      $keywordCollection = json_decode($response->getContent(), true);
      $this->assertInternalType('array', $keywordCollection);
      $this->assertCount(2, $keywordCollection);

      $url = '/rest/2/keyword';
      $response = $this->sendRequest(self::requestGet($url, ["term" => "toto"]));
      $this->assertTrue($response->isOk(), sprintf('HTTP 200 expected, HTTP %s returned.', $response->getStatusCode()));
      $keywordCollection = json_decode($response->getContent(), true);
      $this->assertInternalType('array', $keywordCollection);
      $this->assertCount(0, $keywordCollection);
    }

    private function createAKeyword($label, KeyWord $parent = null) {
        $keyword = new KeyWord();
        $keyword->setKeyWord($label);
        if ($parent instanceof KeyWord) {
            $keyword->setParent($parent);
        }
        $this->em->persist($keyword);
        $this->em->flush($keyword);

        return $keyword;
    }

    protected function tearDown() {
        $this->dropDb($this->getBBApp());
        $this->getBBApp()->stop();
    }

}

?>