<?php
namespace BackBee\Site\Fixture;

use BackBee\Site\Site;
use Faker\Factory;

/**
 * @fixture
 */
class SiteFixture extends Site
{
    protected $faker;

    public function __construct($local = Factory::DEFAULT_LOCALE)
    {
        $this->faker = Factory::create($local);
    }

    public function setUp()
    {
        $site = new Site();
        $site->_label = $this->faker->domainWord;
        $site->_server_name = $this->faker->domainName;
        $site->_created = $site->_modified = new \DateTime();

        return $site;
    }
}
