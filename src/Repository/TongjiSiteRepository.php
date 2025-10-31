<?php

declare(strict_types=1);

namespace Tourze\BaiduTongjiApiBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2User;
use Tourze\BaiduTongjiApiBundle\Entity\TongjiSite;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<TongjiSite>
 */
#[AsRepository(entityClass: TongjiSite::class)]
class TongjiSiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TongjiSite::class);
    }

    /**
     * @return TongjiSite[]
     */
    public function findByUser(BaiduOAuth2User $user): array
    {
        $result = $this->createQueryBuilder('s')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->orderBy('s.domain', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return is_array($result) ? array_filter($result, fn ($item) => $item instanceof TongjiSite) : [];
    }

    public function findBySiteId(string $siteId): ?TongjiSite
    {
        return $this->findOneBy(['siteId' => $siteId]);
    }

    /**
     * @return TongjiSite[]
     */
    public function findActiveSitesByUser(BaiduOAuth2User $user): array
    {
        $result = $this->createQueryBuilder('s')
            ->where('s.user = :user')
            ->andWhere('s.status = 0')
            ->setParameter('user', $user)
            ->orderBy('s.domain', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return is_array($result) ? array_filter($result, fn ($item) => $item instanceof TongjiSite) : [];
    }

    public function save(TongjiSite $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TongjiSite $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
