<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class Hak7allSmokeTest extends KernelTestCase
{
    public function testKernelBoots(): void
    {
        self::bootKernel();
        \->assertTrue(self::getContainer()->get('kernel')->isBooted());
    }
}
