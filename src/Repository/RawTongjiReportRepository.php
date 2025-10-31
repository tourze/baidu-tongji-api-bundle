<?php

declare(strict_types=1);

namespace Tourze\BaiduTongjiApiBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\BaiduTongjiApiBundle\Entity\RawTongjiReport;
use Tourze\BaiduTongjiApiBundle\Exception\TongjiApiException;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<RawTongjiReport>
 */
#[AsRepository(entityClass: RawTongjiReport::class)]
class RawTongjiReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RawTongjiReport::class);
    }

    public function findByParamsHash(
        string $siteId,
        string $method,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        string $responseHash,
    ): ?RawTongjiReport {
        return $this->findOneBy([
            'siteId' => $siteId,
            'method' => $method,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'responseHash' => $responseHash,
        ]);
    }

    /**
     * @param array<string, mixed> $params
     * @param array<string, mixed> $responseData
     */
    public function generateResponseHash(array $params, array $responseData): string
    {
        $hashData = [
            'params' => $params,
            'data' => $responseData,
        ];

        $jsonString = json_encode($hashData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (false === $jsonString) {
            throw TongjiApiException::invalidResponse('Failed to encode data to JSON');
        }

        return hash('sha256', $jsonString);
    }

    /**
     * 保存实体到数据库
     */
    public function save(RawTongjiReport $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 从数据库删除实体
     */
    public function remove(RawTongjiReport $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
