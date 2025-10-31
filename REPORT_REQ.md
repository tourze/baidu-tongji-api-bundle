# 百度统计（Tongji）报告数据采集与存储需求说明

本文档基于百度统计开放平台文档“获取报告数据”章节梳理采集范围、指标与维度、接口与参数、落库模型与同步策略，作为后续实现同步命令与数据产品的设计依据。

- 接口文档入口：`https://tongji.baidu.com/api/manual/Chapter1/getData.html`
- 获取站点列表：`https://tongji.baidu.com/api/manual/Chapter1/getSiteList.html`
- 报告数据统一接口（百度账号）：`https://openapi.baidu.com/rest/2.0/tongji/report/getData`
- 说明：所有报告均通过 `report/getData`，用 `method` 指定报告类型；不同报告允许的 `metrics`、可选过滤参数不同。以下各报告条目均给出对应的“接口文档页”和 `method` 取值。

## 目标与范围

- 目标：稳定、可回溯地拉取百度统计主要报表，形成一致口径的本地数据资产，支持趋势分析、来源分析、页面分析、地域分析以及推广相关分析。
- 范围：覆盖“获取报告数据”下列出的各报告：趋势分析、实时访客、推广方式/趋势、来源（全部/引擎/搜索词/外链/指定广告跟踪）、页面（受访/入口/域名）、地域（省市/世界）、网站概况（趋势/地域分布/常用轨迹）。

## 统一参数与约定

- 必填参数：`site_id`、`method`、`start_date`、`end_date`（部分报告如实时访客仅要求 `metrics` 与排序/条数）。
- 指标选择：除个别专项指标外，建议通用采集以下标准指标（若报告不提供则置空）：
  - `pv_count` 页面浏览量
  - `visit_count` 访问次数
  - `visitor_count` 访客数（UV）
  - `ip_count` IP 数
  - `bounce_ratio` 跳出率（%）
  - `avg_visit_time` 平均访问时长（秒）
  - `avg_visit_pages` 平均访问页数
  - `trans_count` 转化次数
  - `trans_ratio` 转化率（%）
  - 推广专项：`show_count` 展现量
  - 实时专项：`visit_time` 访问时间、`visit_pages` 访问页数
- 过滤/细分常用参数（按报告支持情况传递）：
  - `gran` 粒度：`day|week|month`（趋势类）
  - `source` 流量类型：如 `through` 等（趋势/实时）
  - `clientDevice` 设备：`pc|mobile`
  - `area` 区域范围：如 `china`
  - `order` 排序、`max_results` 返回数量（实时）
  - `flag` 自定义广告跟踪的筛选（如 `plan`）

## 报告清单与采集口径

以下每条包含：名称、接口文档、`method`、关键指标、常用维度/过滤、建议抓取频率与粒度。

1) 趋势分析
- 文档：`https://tongji.baidu.com/api/manual/Chapter1/trend_time_a.html`
- method：`trend/time/a`
- 指标：`pv_count, visit_count, visitor_count, ip_count, bounce_ratio, avg_visit_time, avg_visit_pages, trans_count, trans_ratio`（示例含 `pv_count, avg_visit_time`）
- 维度/过滤：`gran`(day|week|month)、`source`、`clientDevice`、`area`
- 频率/粒度：日/周/月；每日增量拉取并支持回刷（近7天滚动）

2) 实时访客
- 文档：`https://tongji.baidu.com/api/manual/Chapter1/trend_latest_a.html`
- method：`trend/latest/a`
- 指标：`area, searchword, visit_time, visit_pages`（实时特有）
- 维度/过滤：`order`（如 `visit_pages,desc`）、`max_results`、`source`、`area`
- 频率/粒度：按需（例如每5分钟轮询）；作为事件明细落库

3) 推广方式
- 文档：`https://tongji.baidu.com/api/manual/Chapter1/pro_product_a.html`
- method：`pro/product/a`
- 指标：`show_count, pv_count, bounce_ratio` + 通用转化指标（若支持）
- 维度/过滤：可按推广方式细分（以返回项为准）
- 频率/粒度：日；每日增量

4) 百度推广趋势
- 文档：`https://tongji.baidu.com/api/manual/Chapter1/pro_hour_a.html`
- method：`pro/hour/a`
- 指标：`show_count, pv_count, bounce_ratio` + 通用转化指标（若支持）
- 维度/过滤：小时粒度（以返回项为准）
- 频率/粒度：小时；建议每日回刷近3天避免延迟

5) 全部来源
- 文档：`https://tongji.baidu.com/api/manual/Chapter1/source_all_a.html`
- method：`source/all/a`
- 指标：标准通用指标优先（示例：`pv_count, visit_count, visitor_count`）
- 维度/过滤：`viewType`（`type|site`）、`clientDevice`、`visitor`（新/老访客）
- 频率/粒度：日

6) 搜索引擎
- 文档：`https://tongji.baidu.com/api/manual/Chapter1/source_engine_a.html`
- method：`source/engine/a`
- 指标：标准通用指标
- 维度/过滤：搜索引擎名称/类别（以返回项为准）
- 频率/粒度：日

7) 搜索词
- 文档：`https://tongji.baidu.com/api/manual/Chapter1/source_searchword_a.html`
- method：`source/searchword/a`
- 指标：标准通用指标
- 维度/过滤：`searchword`（关键词）
- 频率/粒度：日

8) 外部链接
- 文档：`https://tongji.baidu.com/api/manual/Chapter1/source_link_a.html`
- method：`source/link/a`
- 指标：标准通用指标
- 维度/过滤：来源域名/URL（以返回项为准）
- 频率/粒度：日

9) 指定广告跟踪
- 文档：`https://tongji.baidu.com/api/manual/Chapter1/custom_media_a.html`
- method：`custom/media/a`
- 指标：`visit_count, visitor_count` + 其他通用指标若支持
- 维度/过滤：`flag`（示例：`plan`），以及媒介/活动等维度（以返回项为准）
- 频率/粒度：日

10) 受访页面
- 文档：`https://tongji.baidu.com/api/manual/Chapter1/visit_toppage_a.html`
- method：`visit/toppage/a`
- 指标：`pv_count, visitor_count` + 通用指标
- 维度/过滤：页面 URL/标题
- 频率/粒度：日

11) 入口页面
- 文档：`https://tongji.baidu.com/api/manual/Chapter1/visit_landingpage_a.html`
- method：`visit/landingpage/a`
- 指标：`visit_count, visitor_count` + 通用指标
- 维度/过滤：页面 URL/标题
- 频率/粒度：日

12) 受访域名
- 文档：`https://tongji.baidu.com/api/manual/Chapter1/visit_topdomain_a.html`
- method：`visit/topdomain/a`
- 指标：标准通用指标
- 维度/过滤：域名
- 频率/粒度：日

13) 地域分布（中国）
- 文档：`https://tongji.baidu.com/api/manual/Chapter1/visit_district_a.html`
- method：`visit/district/a`
- 指标：标准通用指标
- 维度/过滤：省/市（以返回项为准）
- 频率/粒度：日

14) 世界地域分布
- 文档：`https://tongji.baidu.com/api/manual/Chapter1/visit_world_a.html`
- method：`visit/world/a`
- 指标：标准通用指标
- 维度/过滤：国家/地区
- 频率/粒度：日

15) 网站概况（趋势数据）
- 文档：`https://tongji.baidu.com/api/manual/Chapter1/overview_getTimeTrendRpt.html`
- method：`overview/getTimeTrendRpt`
- 指标：示例含 `pv_count, visitor_count, ip_count`，补充采集通用指标
- 维度/过滤：时间粒度（日/周/月）
- 频率/粒度：日/周/月

16) 网站概况（地域分布）
- 文档：`https://tongji.baidu.com/api/manual/Chapter1/overview_getDistrictRpt.html`
- method：`overview/getDistrictRpt`
- 指标：通用指标
- 维度/过滤：省/市
- 频率/粒度：日

17) 网站概况（来源网站、搜索词、入口页面、受访页面）
- 文档：`https://tongji.baidu.com/api/manual/Chapter1/overview_getCommonTrackRpt.html`
- method：`overview/getCommonTrackRpt`
- 指标：通用指标
- 维度/过滤：来源网站/搜索词/入口/受访（由返回项区分）
- 频率/粒度：日

备注：网站概况三类报告与“来源/页面/地域”类存在口径交集，主要用于快速总览；在落库与分析上建议优先使用细分报告作为主数据源，概况报告用于校验与补齐。

## 数据模型设计

为兼顾 KISS/DRY 与分析性能，采用“原始层 + 维度表 + 事实表”的三层结构：

1) 原始层（Raw）
- 表：`raw_tongji_report`
- 字段：
  - `id` 自增
  - `site_id` 站点 ID（`getSiteList` 获取，文档：`https://tongji.baidu.com/api/manual/Chapter1/getSiteList.html`）
  - `method` 报告标识（如 `source/all/a`）
  - `start_date`、`end_date`
  - `params_json` 附加参数（`gran/source/clientDevice/area/order/max_results/flag` 等）
  - `metrics` 逗号分隔
  - `data_json` 原始响应 JSON（完整保留）
  - `response_hash` 响应体哈希（去重/幂等等价）
  - `fetched_at` 拉取时间

2) 维度表（Dim）
- `dim_site(site_id, domain, site_name, ... )`
- `dim_date(date)`、`dim_hour(date, hour)`
- `dim_device(code, name)`：pc/mobile
- `dim_visitor_type(code, name)`：new/old
- `dim_source_type(code, name)`：直达/搜索/外链等（与 API 返回一致）
- `dim_engine(name)`、`dim_keyword(word)`
- `dim_domain(domain)`、`dim_page(url, title, path)`
- `dim_area_cn(province, city)`、`dim_area_world(country, region)`
- `dim_ad(tag, plan, unit, idea, ... )`（自定义广告跟踪，按返回项扩展）

3) 事实表（Fact）
- 统一指标列：
  - `pv_count, visit_count, visitor_count, ip_count, bounce_ratio, avg_visit_time, avg_visit_pages, trans_count, trans_ratio`
  - 专项列：`show_count`（推广）、`visit_time, visit_pages`（实时）
- 建议事实表：
  - `fact_traffic_trend(site_id, date, gran, source_type, device, area_scope, <统一指标>)`（`trend/time/a`）
  - `fact_realtime_visitors(site_id, event_time, area, searchword, visit_pages, page_url, source_type, device)`（`trend/latest/a`）
  - `fact_sources(site_id, date, source_type, engine, keyword, ref_domain, <统一指标>)`（整合 `source/*`，字段留空即不适用）
  - `fact_pages(site_id, date, page_url, page_title, is_landing, domain, <统一指标>)`（`visit/toppage/a` 与 `visit/landingpage/a`）
  - `fact_domains(site_id, date, domain, <统一指标>)`（`visit/topdomain/a`）
  - `fact_geo_cn(site_id, date, province, city, <统一指标>)`（`visit/district/a`）
  - `fact_geo_world(site_id, date, country, region, <统一指标>)`（`visit/world/a`）
  - `fact_pro(site_id, stat_time, gran, pro_type, <统一指标+show_count>)`（`pro/product_a`、`pro/hour_a`）
  - `fact_overview_*`（可选：用于与概况报告对账，非必需，推荐只保留 Raw 层）

## 同步策略（Command 实现参考）

- 站点发现：先同步 `getSiteList`，缓存到 `dim_site`。
- 初始化：按站点与报告，回溯近 N 个月（可配置），按天/周/月粒度拉取；趋势/来源/页面/地域类建议日粒度。
- 增量：每日定时任务，拉取 T-1（必要时含 T-7 回刷窗口，修正数据迟到/口径变动）。
- 实时：`trend/latest/a` 以滚动窗口（如 5 分钟）增量写入事件表，设置去重键（`site_id + visit_time + 关键维度哈希`）。
- 幂等：以 `site_id + method + start_date + end_date + params_hash + metrics` 作为逻辑主键；若 `response_hash` 未变化则跳过入库。
- 失败重试：指数退避；状态记录到任务表，支持人工重跑。
- 限流/配额：遵循官方限制（参考文档调试工具 `https://tongji.baidu.com/api/debug/`）；实现请求间隔与并发上限。

## 数据校验

- 跨表对账：
  - 趋势总量（`pv_count/visit_count/visitor_count`）与概况趋势对比，误差应在极小范围内。
  - 来源/页面/地域分项之和 ≈ 总量（允许四舍五入/隐私去标致的微差）。
- 断点续传：每日拉取完成记录校验标记，失败项记录并补偿。

## 字段与口径说明（按报告）

以下列出各报告关键参数与示例，便于对接与调试。完整字段以各自文档为准。

- 趋势分析（`trend/time/a`）
  - 文档：`https://tongji.baidu.com/api/manual/Chapter1/trend_time_a.html`
  - 示例参数：`gran=day`、`source=through`、`clientDevice=pc`、`area=china`
  - 示例指标：`pv_count, avg_visit_time`

- 实时访客（`trend/latest/a`）
  - 文档：`https://tongji.baidu.com/api/manual/Chapter1/trend_latest_a.html`
  - 示例参数：`order=visit_pages,desc`、`max_results=100`、`source=through`、`area=china`
  - 示例指标：`area, searchword, visit_time, visit_pages`

- 推广方式（`pro/product/a`）
  - 文档：`https://tongji.baidu.com/api/manual/Chapter1/pro_product_a.html`
  - 示例指标：`show_count, pv_count, bounce_ratio`

- 百度推广趋势（`pro/hour/a`）
  - 文档：`https://tongji.baidu.com/api/manual/Chapter1/pro_hour_a.html`
  - 示例指标：`show_count, pv_count, bounce_ratio`

- 全部来源（`source/all/a`）
  - 文档：`https://tongji.baidu.com/api/manual/Chapter1/source_all_a.html`
  - 示例指标：`pv_count, visit_count, visitor_count`
  - 常用维度：`viewType=type|site`、`clientDevice`、`visitor`

- 搜索引擎（`source/engine/a`）
  - 文档：`https://tongji.baidu.com/api/manual/Chapter1/source_engine_a.html`
  - 示例指标：`pv_count, visit_count, visitor_count`

- 搜索词（`source/searchword/a`）
  - 文档：`https://tongji.baidu.com/api/manual/Chapter1/source_searchword_a.html`
  - 示例指标：`pv_count, visit_count, visitor_count`

- 外部链接（`source/link/a`）
  - 文档：`https://tongji.baidu.com/api/manual/Chapter1/source_link_a.html`
  - 示例指标：`pv_count, visit_count, visitor_count`

- 指定广告跟踪（`custom/media/a`）
  - 文档：`https://tongji.baidu.com/api/manual/Chapter1/custom_media_a.html`
  - 示例参数：`flag=plan`
  - 示例指标：`visit_count, visitor_count`

- 受访页面（`visit/toppage/a`）
  - 文档：`https://tongji.baidu.com/api/manual/Chapter1/visit_toppage_a.html`
  - 示例指标：`pv_count, visitor_count`

- 入口页面（`visit/landingpage/a`）
  - 文档：`https://tongji.baidu.com/api/manual/Chapter1/visit_landingpage_a.html`
  - 示例指标：`visit_count, visitor_count`

- 受访域名（`visit/topdomain/a`）
  - 文档：`https://tongji.baidu.com/api/manual/Chapter1/visit_topdomain_a.html`
  - 示例指标：`pv_count, visit_count, visitor_count`

- 地域分布（`visit/district/a`）
  - 文档：`https://tongji.baidu.com/api/manual/Chapter1/visit_district_a.html`
  - 示例指标：`pv_count, visit_count, visitor_count`

- 世界地域分布（`visit/world/a`）
  - 文档：`https://tongji.baidu.com/api/manual/Chapter1/visit_world_a.html`
  - 示例指标：`pv_count, visit_count, visitor_count`

- 网站概况（趋势数据）（`overview/getTimeTrendRpt`）
  - 文档：`https://tongji.baidu.com/api/manual/Chapter1/overview_getTimeTrendRpt.html`
  - 示例指标：`pv_count, visitor_count, ip_count`（补充通用指标）

- 网站概况（地域分布）（`overview/getDistrictRpt`）
  - 文档：`https://tongji.baidu.com/api/manual/Chapter1/overview_getDistrictRpt.html`

- 网站概况（来源网站、搜索词、入口页面、受访页面）（`overview/getCommonTrackRpt`）
  - 文档：`https://tongji.baidu.com/api/manual/Chapter1/overview_getCommonTrackRpt.html`

## 索引与性能建议

- 原始层：`(site_id, method, start_date, end_date, response_hash)` 复合唯一；`fetched_at` 倒序索引。
- 事实表：常用查询维度建立联合索引，如 `(site_id, date)`、`(site_id, date, source_type)`、`(site_id, date, page_url)`。
- 分区：大表按 `date` 或 `date, site_id` 分区；实时表按月分表。

## 权限与安全

- OAuth 令牌管理复用 `tourze/baidu-oauth2-integrate-bundle`；在请求失败（401/403）时自动刷新令牌并重试一次。
- 审计：记录每次调用的 `request_id`、耗时、HTTP 状态码、错误消息摘要。

## 后续工作

- 实现 Console Command：
  - `tongji:sync:site-list` 同步站点维度（`config/getSiteList`）。
  - `tongji:sync:report --method=... --site=... --start=... --end=... [--params=JSON]` 通用同步器。
  - 计划任务封装：按上述频率自动调度趋势/来源/页面/地域与实时报告。
- 原始层到事实层的解析/映射器：按 `method` 解析 `result.fields/items`，填充事实表并关联各维度键。

以上方案严格基于百度统计“获取报告数据”各子页面与示例参数设计，后续若官方调整 metrics/维度/参数，请以各文档页为准并同步更新本说明。

