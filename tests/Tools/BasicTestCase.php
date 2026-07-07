<?php

namespace App\Tests\Tools;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class BasicTestCase extends KernelTestCase
{
    use EntityTrait;
    use BasicTestTrait;
    private static ContainerInterface $containerInterface;

    /**
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        static::$containerInterface = new TestContainer(static::$kernel);
    }

    protected function getContainerInterface(): ContainerInterface
    {
        return static::$containerInterface;
    }
}
