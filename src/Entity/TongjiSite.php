<?php

declare(strict_types=1);

namespace Tourze\BaiduTongjiApiBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2User;
use Tourze\BaiduTongjiApiBundle\Repository\TongjiSiteRepository;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

#[ORM\Entity(repositoryClass: TongjiSiteRepository::class)]
#[ORM\Table(name: 'baidu_tongji_site', options: ['comment' => '百度统计站点表'])]
class TongjiSite implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private int $id = 0;

    #[ORM\Column(type: Types::STRING, length: 128, options: ['comment' => '百度站点ID'])]
    #[IndexColumn]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128)]
    private string $siteId = '';

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '站点域名'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $domain = '';

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '站点状态 0:正常 1:暂停'])]
    #[Assert\Choice(choices: [0, 1])]
    private int $status = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '站点创建时间'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    private ?\DateTimeImmutable $siteCreateTime = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '原始API返回数据'])]
    #[Assert\Type(type: 'array')]
    private ?array $rawData = null;

    #[ORM\ManyToOne(targetEntity: BaiduOAuth2User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?BaiduOAuth2User $user = null;

    /**
     * @var Collection<int, TongjiSubDirectory>
     */
    #[ORM\OneToMany(mappedBy: 'site', targetEntity: TongjiSubDirectory::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $subDirectories;

    public function __construct()
    {
        $this->subDirectories = new ArrayCollection();
    }

    public function __toString(): string
    {
        return sprintf('TongjiSite[%s] %s', $this->siteId, $this->domain);
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

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function setDomain(string $domain): void
    {
        $this->domain = $domain;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    public function getSiteCreateTime(): ?\DateTimeImmutable
    {
        return $this->siteCreateTime;
    }

    public function setSiteCreateTime(?\DateTimeInterface $createTime): void
    {
        $this->siteCreateTime = $createTime instanceof \DateTimeImmutable
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

    public function getUser(): ?BaiduOAuth2User
    {
        return $this->user;
    }

    public function setUser(BaiduOAuth2User $user): void
    {
        $this->user = $user;
    }

    /**
     * @return Collection<int, TongjiSubDirectory>
     */
    public function getSubDirectories(): Collection
    {
        return $this->subDirectories;
    }

    public function addSubDirectory(TongjiSubDirectory $subDirectory): void
    {
        if (!$this->subDirectories->contains($subDirectory)) {
            $this->subDirectories->add($subDirectory);
            $subDirectory->setSite($this);
        }
    }

    public function removeSubDirectory(TongjiSubDirectory $subDirectory): void
    {
        if ($this->subDirectories->removeElement($subDirectory)) {
            if ($subDirectory->getSite() === $this) {
                $subDirectory->setSite(null);
            }
        }
    }
}
