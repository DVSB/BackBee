<?php
require(dirname(__DIR__) . "/vendor/autoload.php");
require_once(__DIR__ . '/Test/TestCase.php');

$back_builder_unit_test = new \BackBuilder\Test\TestCase();
$back_builder_unit_test->initAutoload();
