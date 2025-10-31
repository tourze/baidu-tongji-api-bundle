<?php

declare(strict_types=1);

namespace Tourze\BaiduTongjiApiBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\BaiduTongjiApiBundle\Entity\FactTrafficTrend;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<FactTrafficTrend>
 */
#[AsRepository(entityClass: FactTrafficTrend::class)]
class FactTrafficTrendRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FactTrafficTrend::class);
    }

    /**
     * @return array<FactTrafficTrend>
     */
    public function findBySiteAndDateRange(
        string $siteId,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
    ): array {
        // 由于date字段是DATE_IMMUTABLE类型，不包含时间信息，直接比较即可
        $result = $this->createQueryBuilder('f')
            ->where('f.siteId = :siteId')
            ->andWhere('f.date >= :startDate')
            ->andWhere('f.date <= :endDate')
            ->setParameter('siteId', $siteId)
            ->setParameter('startDate', $startDate, Types::DATE_IMMUTABLE)
            ->setParameter('endDate', $endDate, Types::DATE_IMMUTABLE)
            ->orderBy('f.date', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_filter($result, fn ($item) => $item instanceof FactTrafficTrend);
    }

    /**
     * 保存实体到数据库
     */
    public function save(FactTrafficTrend $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 从数据库删除实体
     */
    public function remove(FactTrafficTrend $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
