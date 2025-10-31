<?php

declare(strict_types=1);

namespace Tourze\BaiduTongjiApiBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\BaiduTongjiApiBundle\Repository\FactTrafficTrendRepository;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

#[ORM\Entity(repositoryClass: FactTrafficTrendRepository::class)]
#[ORM\Table(name: 'fact_traffic_trend', options: ['comment' => '趋势分析事实表'])]
#[ORM\UniqueConstraint(name: 'uk_site_date_gran_source_device', columns: ['site_id', 'date', 'gran', 'source_type', 'device', 'area_scope'])]
class FactTrafficTrend implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private int $id = 0;

    #[ORM\Column(type: Types::STRING, length: 128, options: ['comment' => '站点ID'])]
    #[IndexColumn]
    #[Assert\NotBlank]
    private string $siteId = '';

    #[ORM\Column(type: Types::DATE_IMMUTABLE, options: ['comment' => '日期'])]
    #[IndexColumn]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column(type: Types::STRING, length: 32, options: ['comment' => '时间粒度: day/week/month'])]
    #[Assert\Choice(choices: ['day', 'week', 'month'])]
    private string $gran = '';

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true, options: ['comment' => '流量类型'])]
    #[Assert\Length(max: 64)]
    private ?string $sourceType = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true, options: ['comment' => '设备类型: pc/mobile'])]
    #[Assert\Choice(choices: ['pc', 'mobile'])]
    private ?string $device = null;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true, options: ['comment' => '区域范围'])]
    #[Assert\Length(max: 64)]
    private ?string $areaScope = null;

    #[ORM\Column(type: Types::BIGINT, nullable: true, options: ['comment' => '页面浏览量'])]
    #[Assert\PositiveOrZero]
    private ?int $pvCount = null;

    #[ORM\Column(type: Types::BIGINT, nullable: true, options: ['comment' => '访问次数'])]
    #[Assert\PositiveOrZero]
    private ?int $visitCount = null;

    #[ORM\Column(type: Types::BIGINT, nullable: true, options: ['comment' => '访客数(UV)'])]
    #[Assert\PositiveOrZero]
    private ?int $visitorCount = null;

    #[ORM\Column(type: Types::BIGINT, nullable: true, options: ['comment' => 'IP数'])]
    #[Assert\PositiveOrZero]
    private ?int $ipCount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true, options: ['comment' => '跳出率(%)'])]
    #[Assert\Range(min: 0, max: 100)]
    private ?string $bounceRatio = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '平均访问时长(秒)'])]
    #[Assert\PositiveOrZero]
    private ?int $avgVisitTime = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true, options: ['comment' => '平均访问页数'])]
    #[Assert\PositiveOrZero]
    private ?string $avgVisitPages = null;

    #[ORM\Column(type: Types::BIGINT, nullable: true, options: ['comment' => '转化次数'])]
    #[Assert\PositiveOrZero]
    private ?int $transCount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true, options: ['comment' => '转化率(%)'])]
    #[Assert\Range(min: 0, max: 100)]
    private ?string $transRatio = null;

    public function __construct()
    {
    }

    public function __toString(): string
    {
        $dateStr = null !== $this->date ? $this->date->format('Y-m-d') : 'N/A';

        return sprintf('FactTrafficTrend[%s] %s/%s', $this->siteId, $dateStr, $this->gran);
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

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): void
    {
        $this->date = $date instanceof \DateTimeImmutable
            ? $date
            : \DateTimeImmutable::createFromInterface($date);
    }

    public function getGran(): string
    {
        return $this->gran;
    }

    public function setGran(string $gran): void
    {
        $this->gran = $gran;
    }

    public function getSourceType(): ?string
    {
        return $this->sourceType;
    }

    public function setSourceType(?string $sourceType): void
    {
        $this->sourceType = $sourceType;
    }

    public function getDevice(): ?string
    {
        return $this->device;
    }

    public function setDevice(?string $device): void
    {
        $this->device = $device;
    }

    public function getAreaScope(): ?string
    {
        return $this->areaScope;
    }

    public function setAreaScope(?string $areaScope): void
    {
        $this->areaScope = $areaScope;
    }

    public function getPvCount(): ?int
    {
        return $this->pvCount;
    }

    public function setPvCount(?int $pvCount): void
    {
        $this->pvCount = $pvCount;
    }

    public function getVisitCount(): ?int
    {
        return $this->visitCount;
    }

    public function setVisitCount(?int $visitCount): void
    {
        $this->visitCount = $visitCount;
    }

    public function getVisitorCount(): ?int
    {
        return $this->visitorCount;
    }

    public function setVisitorCount(?int $visitorCount): void
    {
        $this->visitorCount = $visitorCount;
    }

    public function getIpCount(): ?int
    {
        return $this->ipCount;
    }

    public function setIpCount(?int $ipCount): void
    {
        $this->ipCount = $ipCount;
    }

    public function getBounceRatio(): ?string
    {
        return $this->bounceRatio;
    }

    public function setBounceRatio(?string $bounceRatio): void
    {
        $this->bounceRatio = $bounceRatio;
    }

    public function getAvgVisitTime(): ?int
    {
        return $this->avgVisitTime;
    }

    public function setAvgVisitTime(?int $avgVisitTime): void
    {
        $this->avgVisitTime = $avgVisitTime;
    }

    public function getAvgVisitPages(): ?string
    {
        return $this->avgVisitPages;
    }

    public function setAvgVisitPages(?string $avgVisitPages): void
    {
        $this->avgVisitPages = $avgVisitPages;
    }

    public function getTransCount(): ?int
    {
        return $this->transCount;
    }

    public function setTransCount(?int $transCount): void
    {
        $this->transCount = $transCount;
    }

    public function getTransRatio(): ?string
    {
        return $this->transRatio;
    }

    public function setTransRatio(?string $transRatio): void
    {
        $this->transRatio = $transRatio;
    }
}
