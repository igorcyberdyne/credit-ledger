<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @template T of object
 *
 * @extends PersistentObjectFactory<T>
 */
abstract class BaseFactory extends PersistentObjectFactory
{
    private array $services = [];

    protected function getService(string $id): ?object
    {
        return $this->services[$id] ?? throw new ServiceNotFoundException($id);
    }

    public function setServices(array $services): self
    {
        $this->services = $services;

        return $this;
    }

    /**
     * Crée une entité.
     */
    public function createOneEntity(array $attributes = [])
    {
        return $this
            ->with($attributes)
            ->create();
    }

    /**
     * Crée plusieurs entités.
     */
    public function createManyEntities(int $count, array $attributes = []): array
    {
        return $this
            ->with($attributes)
            ->many($count)
            ->create();
    }

    /**
     * Persiste sans retourner le proxy.
     */
    public function object(array $attributes = []): object
    {
        return $this
            ->createOne($attributes)
            ->object();
    }

    /**
     * Persiste plusieurs objets.
     *
     * @return list<object>
     */
    public function objects(int $count, array $attributes = []): array
    {
        return array_map(
            static fn ($proxy) => $proxy->object(),
            $this->createManyEntities($count, $attributes)
        );
    }
}
