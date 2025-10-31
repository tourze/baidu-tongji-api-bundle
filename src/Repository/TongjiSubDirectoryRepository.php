<?php

declare(strict_types=1);

namespace Tourze\BaiduTongjiApiBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\BaiduTongjiApiBundle\Entity\TongjiSite;
use Tourze\BaiduTongjiApiBundle\Entity\TongjiSubDirectory;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<TongjiSubDirectory>
 */
#[AsRepository(entityClass: TongjiSubDirectory::class)]
class TongjiSubDirectoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TongjiSubDirectory::class);
    }

    /**
     * @return TongjiSubDirectory[]
     */
    public function findBySite(TongjiSite $site): array
    {
        $result = $this->createQueryBuilder('sd')
            ->where('sd.site = :site')
            ->setParameter('site', $site)
            ->orderBy('sd.subDir', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return is_array($result) ? array_filter($result, fn ($item) => $item instanceof TongjiSubDirectory) : [];
    }

    public function findBySubDirId(string $subDirId): ?TongjiSubDirectory
    {
        return $this->findOneBy(['subDirId' => $subDirId]);
    }

    /**
     * @return TongjiSubDirectory[]
     */
    public function findActiveBysite(TongjiSite $site): array
    {
        $result = $this->createQueryBuilder('sd')
            ->where('sd.site = :site')
            ->andWhere('sd.status = 0')
            ->setParameter('site', $site)
            ->orderBy('sd.subDir', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return is_array($result) ? array_filter($result, fn ($item) => $item instanceof TongjiSubDirectory) : [];
    }

    public function save(TongjiSubDirectory $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TongjiSubDirectory $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
