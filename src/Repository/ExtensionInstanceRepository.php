<?php

namespace Mittwald\MStudio\Bundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Mittwald\MStudio\Bundle\Entity\ExtensionInstance;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<ExtensionInstance>
 */
class ExtensionInstanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExtensionInstance::class);
    }

    public function mustFind(string $id): ExtensionInstance
    {
        $instance = $this->find(Uuid::fromString($id));
        if (is_null($instance)) {
            throw new \InvalidArgumentException("extension instance {$id} does not exist");
        }

        return $instance;
    }

    public function persist(ExtensionInstance $instance): void
    {
        $this->getEntityManager()->persist($instance);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function remove(ExtensionInstance $instance): void
    {
        $this->getEntityManager()->remove($instance);
    }
}