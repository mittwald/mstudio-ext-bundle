<?php

namespace Mittwald\MStudio\Bundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Mittwald\MStudio\Bundle\Entity\ExtensionInstance;

/**
 * @method ExtensionInstance|null find($id, $lockMode = null, $lockVersion = null)
 */
class ExtensionInstanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExtensionInstance::class);
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