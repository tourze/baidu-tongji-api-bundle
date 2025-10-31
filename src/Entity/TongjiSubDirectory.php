<?php

declare(strict_types=1);

namespace Tourze\BaiduTongjiApiBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\BaiduTongjiApiBundle\Repository\TongjiSubDirectoryRepository;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

#[ORM\Entity(repositoryClass: TongjiSubDirectoryRepository::class)]
#[ORM\Table(name: 'baidu_tongji_sub_directory', options: ['comment' => '百度统计子目录表'])]
class TongjiSubDirectory implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private int $id = 0;

    #[ORM\Column(type: Types::STRING, length: 128, options: ['comment' => '子目录ID'])]
    #[IndexColumn]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128)]
    private string $subDirId = '';

    #[ORM\Column(type: Types::STRING, length: 512, options: ['comment' => '子目录路径'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 512)]
    private string $subDir = '';

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '子目录状态 0:正常 1:暂停'])]
    #[Assert\Choice(choices: [0, 1])]
    private int $status = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '子目录创建时间'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    private ?\DateTimeImmutable $subDirCreateTime = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '原始API返回数据'])]
    #[Assert\Type(type: 'array')]
    private ?array $rawData = null;

    #[ORM\ManyToOne(targetEntity: TongjiSite::class, inversedBy: 'subDirectories')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TongjiSite $site = null;

    public function __construct()
    {
    }

    public function __toString(): string
    {
        return sprintf('TongjiSubDir[%s] %s', $this->subDirId, $this->subDir);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSubDirId(): string
    {
        return $this->subDirId;
    }

    public function setSubDirId(string $subDirId): void
    {
        $this->subDirId = $subDirId;
    }

    public function getSubDir(): string
    {
        return $this->subDir;
    }

    public function setSubDir(string $subDir): void
    {
        $this->subDir = $subDir;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    public function getSubDirCreateTime(): ?\DateTimeImmutable
    {
        return $this->subDirCreateTime;
    }

    public function setSubDirCreateTime(?\DateTimeInterface $createTime): void
    {
        $this->subDirCreateTime = $createTime instanceof \DateTimeImmutable
            ? $createTime
            : (null !== $createTime ? \DateTimeImmutable::createFromInterface($createTime) : null);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRawData(): ?array
    {
        return $this->rawData;
    }

    /**
     * @param array<string, mixed>|null $rawData
     */
    public function setRawData(?array $rawData): void
    {
        $this->rawData = $rawData;
    }

    public function getSite(): ?TongjiSite
    {
        return $this->site;
    }

    public function setSite(?TongjiSite $site): void
    {
        $this->site = $site;
    }
}
