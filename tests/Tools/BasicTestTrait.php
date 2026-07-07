<?php

namespace App\Tests\Tools;

use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Faker\Generator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

trait BasicTestTrait
{
    private ?EntityManagerInterface $entityManager = null;
    private static ?Generator $faker = null;

    abstract protected function getContainerInterface(): ContainerInterface;

    protected function getUserPasswordHasherInterface(): UserPasswordHasherInterface
    {
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $this->getContainerInterface()->get(UserPasswordHasherInterface::class);

        return $hasher;
    }

    protected function getGenerator(): Generator
    {
        if (null !== static::$faker) {
            return static::$faker;
        }

        static::$faker = Factory::create('fr_FR');

        return static::$faker;
    }

    /**
     * @throws \Exception
     */
    protected function getService(string $class): object
    {
        $service = $this->getContainerInterface()->get($class);

        if (empty($service)) {
            throw new \Exception('Service doesnt exist');
        }

        return $service;
    }

    protected function setService(string $class, mixed $object): static
    {
        $this->getContainerInterface()->set($class, $object);

        return $this;
    }

    /**
     * @throws \Exception
     */
    protected function getEntityManager(): EntityManagerInterface
    {
        if (null === $this->entityManager) {
            /** @var EntityManagerInterface $em */
            $em = $this->getService('doctrine.orm.entity_manager');
            $this->entityManager = $em;
        }

        return $this->entityManager;
    }

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    /**
     * @throws \Throwable
     */
    protected function wrapInRollback(callable $callable): void
    {
        try {
            $this->getEntityManager()->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($callable) {
                $entityManager->commit();
                $callable($entityManager);

                throw new \Exception('Force transaction rollback');
            });
        } catch (\Throwable $e) {
            if ('Force transaction rollback' == $e->getMessage()) {
                return;
            }

            $this->getContainerInterface()->get(LoggerInterface::class)->error($e->getMessage());

            throw $e;
        }
    }

    protected function generateUrl(string $route, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_URL): string
    {
        return $this->getContainerInterface()->get('router')->generate($route, $parameters, $referenceType);
    }
}
