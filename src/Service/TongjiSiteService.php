<?php

declare(strict_types=1);

namespace Tourze\BaiduTongjiApiBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2User;
use Tourze\BaiduTongjiApiBundle\Entity\TongjiSite;
use Tourze\BaiduTongjiApiBundle\Entity\TongjiSubDirectory;
use Tourze\BaiduTongjiApiBundle\Repository\TongjiSiteRepository;
use Tourze\BaiduTongjiApiBundle\Repository\TongjiSubDirectoryRepository;

#[WithMonologChannel(channel: 'baidu_tongji_api')]
class TongjiSiteService
{
    public function __construct(
        private TongjiApiClient $apiClient,
        private TongjiSiteRepository $siteRepository,
        private TongjiSubDirectoryRepository $subDirRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return TongjiSite[]
     */
    public function syncUserSites(BaiduOAuth2User $user): array
    {
        $this->logger->info('Starting site sync for user', ['user_id' => $user->getBaiduUid()]);

        $apiData = $this->apiClient->getSiteList($user);

        if (!isset($apiData['list']) || !is_array($apiData['list'])) {
            $this->logger->warning('No sites found in API response', ['user_id' => $user->getBaiduUid()]);

            return [];
        }

        $sites = [];

        foreach ($apiData['list'] as $siteData) {
            if (!is_array($siteData)) {
                $this->logger->warning('Invalid site data received - not an array', ['site_data' => $siteData]);
                continue;
            }

            // Convert mixed array to string-keyed array
            $stringKeySiteData = $this->convertToStringKeyArray($siteData);

            if (!$this->isValidSiteData($stringKeySiteData)) {
                $this->logger->warning('Invalid site data structure', ['site_data' => $stringKeySiteData]);
                continue;
            }

            $site = $this->createOrUpdateSite($user, $stringKeySiteData);

            // Convert sub_dir_list to proper format
            $subDirList = [];
            if (isset($stringKeySiteData['sub_dir_list']) && is_array($stringKeySiteData['sub_dir_list'])) {
                $subDirList = $this->convertSubDirListToValidFormat($stringKeySiteData['sub_dir_list']);
            }
            $this->syncSubDirectories($site, $subDirList);

            $sites[] = $site;
        }

        $this->entityManager->flush();

        $this->logger->info('Site sync completed', [
            'user_id' => $user->getBaiduUid(),
            'sites_count' => count($sites),
        ]);

        return $sites;
    }

    /**
     * @param array<string, mixed> $siteData
     */
    private function isValidSiteData(array $siteData): bool
    {
        return isset($siteData['site_id'])
               && isset($siteData['domain'])
               && is_string($siteData['site_id'])
               && is_string($siteData['domain']);
    }

    /**
     * @param array<string, mixed> $siteData
     */
    private function createOrUpdateSite(BaiduOAuth2User $user, array $siteData): TongjiSite
    {
        $siteId = is_string($siteData['site_id']) ? $siteData['site_id'] : '';
        $domain = is_string($siteData['domain']) ? $siteData['domain'] : '';

        $site = $this->siteRepository->findBySiteId($siteId);

        if (null === $site) {
            $site = new TongjiSite();
            $site->setSiteId($siteId);
            $site->setDomain($domain);
            $site->setUser($user);
            $this->logger->debug('Creating new site', ['site_id' => $siteId]);
        } else {
            $this->logger->debug('Updating existing site', ['site_id' => $siteId]);
        }

        $site->setDomain($domain);
        $site->setStatus(isset($siteData['status']) && is_int($siteData['status']) ? $siteData['status'] : 0);
        $site->setRawData($siteData);

        $this->setTimestampFromUnixTime($site, $siteData['create_time'] ?? null, 'setSiteCreateTime');

        $this->siteRepository->save($site);

        return $site;
    }

    /**
     * @param array<int, array<string, mixed>> $subDirList
     */
    private function syncSubDirectories(TongjiSite $site, array $subDirList): void
    {
        $existingSubDirs = $this->subDirRepository->findBySite($site);
        $existingSubDirIds = array_map(fn ($sd) => $sd->getSubDirId(), $existingSubDirs);
        $processedSubDirIds = [];

        foreach ($subDirList as $subDirData) {
            if (!$this->isValidSubDirData($subDirData)) {
                continue;
            }

            $processedId = $this->processSubDirectory($site, $subDirData);
            $processedSubDirIds[] = $processedId;
        }

        $orphanedSubDirIds = array_diff($existingSubDirIds, $processedSubDirIds);
        $this->cleanupOrphanedSubDirectories($site, $orphanedSubDirIds);
    }

    /**
     * @param array<string, mixed> $subDirData
     */
    private function processSubDirectory(TongjiSite $site, array $subDirData): string
    {
        $subDirId = is_string($subDirData['sub_dir_id']) ? $subDirData['sub_dir_id'] : '';
        $subDirName = is_string($subDirData['sub_dir']) ? $subDirData['sub_dir'] : '';

        $subDir = $this->subDirRepository->findBySubDirId($subDirId);

        if (null === $subDir) {
            $subDir = new TongjiSubDirectory();
            $subDir->setSubDirId($subDirId);
            $subDir->setSubDir($subDirName);
            $site->addSubDirectory($subDir);
            $this->logger->debug('Creating new sub directory', ['sub_dir_id' => $subDirId]);
        } else {
            $this->logger->debug('Updating existing sub directory', ['sub_dir_id' => $subDirId]);
        }

        $subDir->setSubDir($subDirName);
        $subDir->setStatus(isset($subDirData['status']) && is_int($subDirData['status']) ? $subDirData['status'] : 0);
        $subDir->setRawData($subDirData);

        $this->setTimestampFromUnixTime($subDir, $subDirData['create_time'] ?? null, 'setSubDirCreateTime');

        $this->subDirRepository->save($subDir);

        return $subDir->getSubDirId();
    }

    /**
     * 将混合类型数组转换为字符串键数组
     * @param array<mixed, mixed> $mixedArray
     * @return array<string, mixed>
     */
    private function convertToStringKeyArray(array $mixedArray): array
    {
        $result = [];
        foreach ($mixedArray as $key => $value) {
            $stringKey = is_string($key) ? $key : (string) $key;
            $result[$stringKey] = $value;
        }

        return $result;
    }

    /**
     * 将子目录列表转换为有效格式
     * @param array<mixed, mixed> $subDirList
     * @return array<int, array<string, mixed>>
     */
    private function convertSubDirListToValidFormat(array $subDirList): array
    {
        $result = [];
        foreach ($subDirList as $subDirData) {
            if (is_array($subDirData)) {
                $result[] = $this->convertToStringKeyArray($subDirData);
            }
        }

        return $result;
    }

    /**
     * @param array<string> $orphanedSubDirIds
     */
    private function cleanupOrphanedSubDirectories(TongjiSite $site, array $orphanedSubDirIds): void
    {
        foreach ($orphanedSubDirIds as $orphanedSubDirId) {
            $orphanedSubDir = $this->subDirRepository->findBySubDirId($orphanedSubDirId);
            if (null !== $orphanedSubDir && $orphanedSubDir->getSite() === $site) {
                $this->logger->info('Removing orphaned sub directory', ['sub_dir_id' => $orphanedSubDirId]);
                $site->removeSubDirectory($orphanedSubDir);
                $this->subDirRepository->remove($orphanedSubDir);
            }
        }
    }

    /**
     * @param array<string, mixed> $subDirData
     */
    private function isValidSubDirData(array $subDirData): bool
    {
        return isset($subDirData['sub_dir_id'])
               && isset($subDirData['sub_dir'])
               && is_string($subDirData['sub_dir_id'])
               && is_string($subDirData['sub_dir']);
    }

    /**
     * 通用时间戳转换方法，将Unix时间戳转换为DateTimeImmutable并调用指定方法
     *
     * @param TongjiSite|TongjiSubDirectory $entity
     */
    private function setTimestampFromUnixTime(object $entity, mixed $unixTime, string $setterMethod): void
    {
        if (!isset($unixTime) || !is_numeric($unixTime)) {
            return;
        }

        $dateTime = \DateTimeImmutable::createFromFormat('U', (string) $unixTime);
        if ($dateTime instanceof \DateTimeImmutable) {
            if ('setSiteCreateTime' === $setterMethod && $entity instanceof TongjiSite) {
                $entity->setSiteCreateTime($dateTime);
            } elseif ('setSubDirCreateTime' === $setterMethod && $entity instanceof TongjiSubDirectory) {
                $entity->setSubDirCreateTime($dateTime);
            }
        }
    }

    /**
     * @return TongjiSite[]
     */
    public function getUserSites(BaiduOAuth2User $user): array
    {
        return $this->siteRepository->findByUser($user);
    }

    /**
     * @return TongjiSite[]
     */
    public function getActiveSites(BaiduOAuth2User $user): array
    {
        return $this->siteRepository->findActiveSitesByUser($user);
    }

    public function getSiteBySiteId(string $siteId): ?TongjiSite
    {
        return $this->siteRepository->findBySiteId($siteId);
    }
}
