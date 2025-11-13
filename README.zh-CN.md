# 百度统计 API Bundle

[English](README.md) | [中文](README.zh-CN.md)

用于将百度统计 API 集成到您的 Symfony 应用程序中的 Bundle。此 Bundle 提供全面的功能来管理百度统计站点、获取分析报告以及与本地存储同步数据。

## 功能特性

- **站点管理**：同步和管理百度统计站点及子目录
- **报告获取**：访问各种类型的分析报告（趋势、来源、访问等）
- **数据存储**：使用 Doctrine ORM 实体本地存储报告数据
- **CLI 命令**：便捷的控制台命令进行数据同步
- **管理界面**：EasyAdmin 集成用于数据管理
- **OAuth2 集成**：与百度 OAuth2 认证无缝集成

## 系统要求

- PHP 8.1+
- Symfony 6.4+
- Doctrine ORM
- `tourze/baidu-oauth2-integrate-bundle` - 用于百度 OAuth2 认证

## 安装

使用 Composer 安装 Bundle：

```bash
composer require tourze/baidu-tongji-api-bundle
```

## 配置

在您的 Symfony 应用程序中启用 Bundle：

```php
// config/bundles.php
return [
    // ...
    Tourze\BaiduTongjiApiBundle\BaiduTongjiApiBundle::class => ['all' => true],
];
```

## 控制台命令

### tongji:sync-sites

为用户同步百度统计站点。

#### 基本用法

```bash
# 同步所有用户的站点
php bin/console tongji:sync-sites

# 同步指定用户的站点
php bin/console tongji:sync-sites --user-id=123

# 强制同步，忽略 token 过期检查
php bin/console tongji:sync-sites --force
```

#### 选项参数

- `--user-id, -u`: 指定用户 ID，不指定则同步所有用户
- `--force, -f`: 强制同步，忽略 token 过期检查

#### 使用示例

```bash
# 同步所有用户站点
php bin/console tongji:sync-sites

# 同步指定用户站点
php bin/console tongji:sync-sites -u 12345

# 强制同步站点并更新 token
php bin/console tongji:sync-sites --force
```

### tongji:sync-report

同步百度统计分析报告。

#### 基本用法

```bash
# 同步所有站点的趋势报告
php bin/console tongji:sync-report trend/time/a --start-date=2024-01-01 --end-date=2024-01-31

# 同步指定站点的报告
php bin/console tongji:sync-report trend/time/a --site-id=123456 --start-date=2024-01-01 --end-date=2024-01-31

# 强制同步，忽略 token 过期检查
php bin/console tongji:sync-report trend/time/a --start-date=2024-01-01 --end-date=2024-01-31 --force
```

#### 参数说明

**必需参数：**
- `method`: 要获取的报告方法（见下表）

**可选参数：**
- `--site-id, -s`: 指定站点 ID，不指定则同步所有站点
- `--start-date`: 开始日期，格式：YYYY-MM-DD
- `--end-date`: 结束日期，格式：YYYY-MM-DD
- `--params, -p`: 额外参数，JSON 格式，默认：`{}`
- `--force, -f`: 强制同步，忽略 token 过期检查

#### 可用的报告方法

| 方法 | 描述 |
|------|------|
| `trend/time/a` | 趋势分析 |
| `trend/latest/a` | 实时访客 |
| `pro/product/a` | 推广方式 |
| `pro/hour/a` | 百度推广趋势 |
| `source/all/a` | 全部来源 |
| `source/engine/a` | 搜索引擎 |
| `source/searchword/a` | 搜索词 |
| `source/link/a` | 外部链接 |
| `custom/media/a` | 指定广告跟踪 |
| `visit/toppage/a` | 受访页面 |
| `visit/landingpage/a` | 入口页面 |
| `visit/topdomain/a` | 受访域名 |
| `visit/district/a` | 地域分布（中国） |
| `visit/world/a` | 世界地域分布 |
| `overview/getTimeTrendRpt` | 网站概况（趋势数据） |
| `overview/getDistrictRpt` | 网站概况（地域分布） |
| `overview/getCommonTrackRpt` | 网站概况（常用轨迹） |

#### 使用示例

```bash
# 同步所有站点的趋势报告
php bin/console tongji:sync-report trend/time/a --start-date=2024-01-01 --end-date=2024-01-31

# 同步指定站点的搜索引擎报告
php bin/console tongji:sync-report source/engine/a -s 123456 --start-date=2024-01-01 --end-date=2024-01-31

# 使用自定义参数同步报告
php bin/console tongji:sync-report trend/time/a --start-date=2024-01-01 --end-date=2024-01-31 -p '{"gran":"day"}'

# 强制同步报告并更新 token
php bin/console tongji:sync-report trend/time/a --start-date=2024-01-01 --end-date=2024-01-31 --force
```

## 实体类

### TongjiSite

表示百度统计站点，包含以下属性：

- `siteId`: 百度站点 ID
- `domain`: 站点域名
- `status`: 站点状态（0：正常，1：暂停）
- `user`: 关联的百度 OAuth2 用户
- `subDirectories`: 子目录集合
- `siteCreateTime`: 站点创建时间
- `rawData`: 原始 API 响应数据

### TongjiSubDirectory

表示百度统计子目录，包含以下属性：

- `subDirId`: 子目录 ID
- `subDir`: 子目录路径
- `status`: 子目录状态
- `site`: 关联的站点

### FactTrafficTrend

存储聚合的流量趋势数据：

- `siteId`: 站点 ID
- `date`: 报告日期
- `pageViewPv`: 页面浏览量
- `visitorUv`: 独立访客数
- `ipCount`: IP 数量
- `bounceRatio`: 跳出率
- `avgVisitTime`: 平均访问时长

### RawTongjiReport

存储来自百度统计 API 的原始报告数据：

- `reportMethod`: 报告方法名称
- `siteId`: 站点 ID
- `reportDate`: 报告日期
- `rawData`: 来自 API 的原始 JSON 数据

## 服务类

### TongjiApiClient

用于与百度统计 API 端点通信的核心 API 客户端。

### TongjiSiteService

管理站点同步和站点相关操作。

### TongjiReportSyncService

处理报告数据同步和处理。

### UserManagementService

管理用户相关操作和认证。

## 测试

运行测试套件：

```bash
vendor/bin/phpunit packages/baidu-tongji-api-bundle/tests/
```

运行静态分析：

```bash
vendor/bin/phpstan analyse packages/baidu-tongji-api-bundle/src/ --level=8
```

## 许可证

MIT License