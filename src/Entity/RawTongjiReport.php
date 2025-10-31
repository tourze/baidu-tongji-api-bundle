<?php

declare(strict_types=1);

namespace Tourze\BaiduTongjiApiBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\BaiduTongjiApiBundle\Repository\RawTongjiReportRepository;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

#[ORM\Entity(repositoryClass: RawTongjiReportRepository::class)]
#[ORM\Table(name: 'raw_tongji_report', options: ['comment' => '百度统计报告原始数据'])]
#[ORM\UniqueConstraint(name: 'uk_site_method_dates_hash', columns: ['site_id', 'method', 'start_date', 'end_date', 'response_hash'])]
class RawTongjiReport implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private int $id = 0;

    #[ORM\Column(type: Types::STRING, length: 128, options: ['comment' => '站点ID'])]
    #[IndexColumn]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128)]
    private string $siteId = '';

    #[ORM\Column(type: Types::STRING, length: 128, options: ['comment' => '报告方法'])]
    #[IndexColumn]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128)]
    private string $method = '';

    #[ORM\Column(type: Types::DATE_IMMUTABLE, options: ['comment' => '开始日期'])]
    #[IndexColumn]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, options: ['comment' => '结束日期'])]
    #[IndexColumn]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $endDate = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '附加参数JSON'])]
    #[Assert\Type(type: 'array')]
    private ?array $paramsJson = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '指标列表(逗号分隔)'])]
    #[Assert\Type(type: 'string')]
    private ?string $metrics = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '原始响应JSON'])]
    #[Assert\Type(type: 'array')]
    private ?array $dataJson = null;

    #[ORM\Column(type: Types::STRING, length: 64, options: ['comment' => '响应体哈希'])]
    #[IndexColumn]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    private string $responseHash = '';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '拉取时间'])]
    #[IndexColumn]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $fetchedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '处理时间'])]
    #[Assert\Type(type: \DateTimeInterface::class)]
    private ?\DateTimeImmutable $processedAt = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '同步状态'])]
    #[Assert\Type(type: 'integer')]
    private ?int $syncStatus = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '错误信息'])]
    #[Assert\Type(type: 'string')]
    private ?string $errorMessage = null;

    public function __construct()
    {
    }

    public function __toString(): string
    {
        $startDateStr = null !== $this->startDate ? $this->startDate->format('Y-m-d') : 'N/A';
        $endDateStr = null !== $this->endDate ? $this->endDate->format('Y-m-d') : 'N/A';

        return sprintf('RawTongjiReport[%s] %s %s~%s', $this->siteId, $this->method, $startDateStr, $endDateStr);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSiteId(): string
    {
        return $this->siteId;
    }

    public function setSiteId(string $siteId): void
    {
        $this->siteId = $siteId;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): void
    {
        $this->startDate = $startDate instanceof \DateTimeImmutable
            ? $startDate
            : \DateTimeImmutable::createFromInterface($startDate);
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): void
    {
        $this->endDate = $endDate instanceof \DateTimeImmutable
            ? $endDate
            : \DateTimeImmutable::createFromInterface($endDate);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getParamsJson(): ?array
    {
        return $this->paramsJson;
    }

    /**
     * @param array<string, mixed>|null $paramsJson
     */
    public function setParamsJson(?array $paramsJson): void
    {
        $this->paramsJson = $paramsJson;
    }

    public function getMetrics(): ?string
    {
        return $this->metrics;
    }

    public function setMetrics(?string $metrics): void
    {
        $this->metrics = $metrics;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDataJson(): ?array
    {
        return $this->dataJson;
    }

    /**
     * @param array<string, mixed>|null $dataJson
     */
    public function setDataJson(?array $dataJson): void
    {
        $this->dataJson = $dataJson;
    }

    public function getResponseHash(): string
    {
        return $this->responseHash;
    }

    public function setResponseHash(string $responseHash): void
    {
        $this->responseHash = $responseHash;
    }

    public function getFetchedAt(): ?\DateTimeImmutable
    {
        return $this->fetchedAt;
    }

    public function setFetchedAt(\DateTimeInterface $fetchedAt): void
    {
        $this->fetchedAt = $fetchedAt instanceof \DateTimeImmutable
            ? $fetchedAt
            : \DateTimeImmutable::createFromInterface($fetchedAt);
    }

    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function setProcessedAt(?\DateTimeImmutable $processedAt): void
    {
        $this->processedAt = $processedAt;
    }

    public function getSyncStatus(): ?int
    {
        return $this->syncStatus;
    }

    public function setSyncStatus(?int $syncStatus): void
    {
        $this->syncStatus = $syncStatus;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
    }
}
