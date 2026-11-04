# Signify · 企业家形象资产数字化系统
# Signify · Entrepreneur Digital Asset Platform

[![License: MIT](https://img.shields.io/github/license/Abinius/Signify?style=flat-square)](LICENSE)
[![Tests](https://github.com/Abinius/Signify/actions/workflows/tests.yml/badge.svg)](https://github.com/Abinius/Signify/actions/workflows/tests.yml)
[![Website](https://img.shields.io/website?url=https://vour.cn&up_message=online&down_message=offline&label=vour.cn&style=flat-square)](https://vour.cn)
[![Last Commit](https://img.shields.io/github/last-commit/Abinius/Signify?style=flat-square)](https://github.com/Abinius/Signify/commits/main)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white&style=flat-square)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?logo=laravel&logoColor=white&style=flat-square)](https://laravel.com/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?logo=mysql&logoColor=white&style=flat-square)](https://www.mysql.com/)
[![Docker](https://img.shields.io/badge/Docker-available-2496ED?logo=docker&logoColor=white&style=flat-square)](docker-compose.yml)

> **每一份引领行业的商业远见，都值得被更广泛地看见。**
> **Every leading industry vision deserves to be seen by more people.**

不用复杂定义，只用数字化技术，把企业家的个人价值放大成看得见的核心竞争力。
No complex definitions — just digital technology that turns an entrepreneur's personal value into visible core competitiveness.

**官方网站 / Official Website: [https://vour.cn](https://vour.cn)**

Laravel 11 + Blade + Alpine.js · 服务端渲染，服务器零构建 / Server-side rendered, zero build

---

## 核心功能 / Features

| 模块 Module | 说明 Description |
|---|---|
| **首页** Home | 登录墙：登录后直达个人名片，无档案引导创建 Login wall: redirects to own card or onboarding |
| **企业家库** Directory | 搜索 / 筛选 / 分页，仅收录已获「推荐」的认证档案 Search / filter / paginate, featured profiles only |
| **个人中心** Profile | 形象照裁剪（4:5 自动派生 1:1）、二维码、社交链接、信息编辑，Policy 保护；创建档案需先验证邮箱 Portrait crop (4:5 → 1:1), QR, social link, info editing, Policy-protected; email verification required to create |
| **推荐申请** Recommend | 企业家自助申请推荐（必填理由），管理员审核，被拒 15 天冷却 Self-service request (reason required), admin review, 15-day cooldown after rejection |
| **管理后台** Admin | 认证 / 推荐双标签审核、批量操作、系统设置 Approve/recommend tabs, batch actions, system settings |
| **品牌分享** Sharing | 名片分享图 og 标签，微信 / 社交可预览 og tags for card sharing, previewable in WeChat/social |
| **社交链接** Social Link | 单个网址输入，按域名自动识别平台黑色图标（品牌域名用官方 logo）Single URL input, domain-based black icon auto-recognition (brand logo for official domains) |
| **一键安装** Installer | 数据库连接测试 + 自动建库 DB connection test + auto-create database |

## 技术栈 / Tech Stack

| 层级 Layer | 技术 Technology |
|---|---|
| 后端 Backend | Laravel 11（PHP 8.2+） |
| 前端 Frontend | Blade + Alpine.js（服务端渲染，零构建 / server-rendered, zero build） |
| 样式 Style | Tailwind CSS（高端编辑 / 杂志风 editorial/magazine） |
| 数据库 Database | MySQL 5.7+（string 替代 enum，兼容 / enum-replaced with string） |
| 认证 Auth | Laravel Breeze |
| 服务器 Server | Nginx 1.28+ / Apache 2.4+ |

---

## 环境要求 / Requirements（Traditional Server）

| 项目 Item | 版本 Version | 说明 Notes |
|---|---|---|
| PHP | 8.2+ | Laravel 11 最低要求 / Laravel 11 minimum |
| MySQL | 5.7+ | 需 InnoDB / InnoDB required |
| Nginx | 1.28+ | 或 Apache 2.4+ |
| phpMyAdmin | 5.0+ | 可选 / optional |

**PHP 扩展 / Extensions（必须 required）：** `pdo_mysql`、`mbstring`、`openssl`、`curl`、`gd`
（`fileinfo` **非必需 / not required** —— 头像上传已做兼容，缺 fileinfo 也能正常跑 / avatar upload is fileinfo-compatible）

> ⚠️ MySQL 5.7 不支持 Laravel 默认 enum，项目已改用 `string` 类型，迁移完全兼容。
> MySQL 5.7 doesn't support Laravel's default enum; the project uses `string` instead, migrations fully compatible.

---

## 一键部署 / One-Click Deployment（宝塔 / BT Panel）

### 1. 创建站点和数据库 / Create site & database

1. 宝塔面板 → **网站** → **添加站点** / BT Panel → Websites → Add site
2. 配置：**域名**、**根目录** `/www/wwwroot/signify`、**PHP 版本** 8.0/8.1、**创建数据库**（MySQL 5.7）

### 2. 上传代码 / Upload code

```bash
cd /www/wwwroot/signify
rm -rf ./*
git clone https://github.com/Abinius/Signify.git .
```

### 3. 配置 .env / Configure .env

```bash
cp .env.example .env
```
```env
APP_NAME="Signify"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_db
DB_USERNAME=your_user
DB_PASSWORD=your_password

FILESYSTEM_DISK=public
SESSION_DRIVER=file
```

### 4. 安装依赖 / Install dependencies（SSH）

```bash
cd /www/wwwroot/signify
composer install --no-dev --optimize-autoloader --ignore-platform-req=ext-fileinfo
php artisan key:generate
php artisan storage:link
php artisan migrate --force
php artisan config:cache
```

> ⚠️ **Composer ≥2.10 用户**：Laravel 11 已 EOL，`composer install` 可能被安全公告策略拦截。
> 如报 "affected by security advisories"，执行 `composer config --global policy.advisories.block false` 后重试。

### 5. 目录权限 / Permissions

```bash
chown -R www:www /www/wwwroot/signify
chmod -R 755 /www/wwwroot/signify/storage
chmod -R 755 /www/wwwroot/signify/bootstrap/cache
```

### 6. Nginx 配置 / Nginx config

根目录 `/www/wwwroot/signify/public`，参考仓库 `deploy/nginx.conf`（含安全响应头、`.env` 禁止访问、静态缓存、Gzip）。

### 7. SSL 证书 / SSL（强烈推荐 recommended）

宝塔 → SSL → **Let's Encrypt** → 强制 HTTPS。

### 8. 创建管理员 / Create admin

**方式 A**：访问 `https://your-domain.com/setup` 一键安装。
**方式 B**：`php artisan tinker`，创建 `is_admin => true` 的用户。

### 验证 / Verify

- `https://your-domain.com/login` → 登录页 Login page
- 登录后 `/admin/dashboard` → 管理后台 Admin dashboard

---

## 🔐 数据安全设计 / Data Security

| 安全措施 Security | 实现方式 Implementation |
|---|---|
| 数据隔离 Data isolation | `user_id` UNIQUE + `EntrepreneurPolicy` 验证 |
| 防注入 SQL injection | LIKE 查询 `addcslashes()` 转义 |
| 防越权 Authorization | 路由模型绑定显式 `approved()` 查询 |
| 头像安全 Upload safety | 扩展名白名单 + GD 校验 + 安全文件名 |
| 管理员授权 Admin auth | 中间件 + Policy 双重保护 |
| 安装器锁定 Installer lock | 安装完成写入 `storage/app/installed.lock`，安装接口永久 403 + 限流 Install lock file + rate limiting |
| 敏感文件 Sensitive files | Nginx 禁止访问 `.env` |
| 生产模式 Production | `APP_DEBUG=false` |

---

## 📋 一键重装 / Reinstall（保留数据库 keep DB）

```bash
cd /www/wwwroot/signify
git pull
composer install --no-dev --optimize-autoloader --ignore-platform-req=ext-fileinfo
php artisan key:generate
php artisan config:cache
chown -R www:www /www/wwwroot/signify
```

---

## 🐳 Docker 部署 / Docker（可选 optional，本地/演示环境）

```bash
cd Signify
docker compose up -d --build
# 首次启动自动完成 key:generate / migrate / storage:link
# 访问 http://localhost （数据库默认不对外暴露端口）
```

---

## 项目结构 / Project Structure

```
Signify/
├── app/
│   ├── Http/Controllers/     # 控制器 Controllers
│   ├── Models/               # 数据模型 Models
│   ├── Policies/             # 权限策略 Policies
│   └── Middleware/           # 中间件 Middleware
├── config/                   # Laravel 配置 Config
├── database/
│   ├── migrations/           # 数据库迁移 Migrations（兼容 MySQL 5.7）
│   ├── factories/            # 测试工厂 Factories
│   └── seeders/              # 种子 Seeders
├── deploy/
│   ├── nginx.conf            # Nginx 配置模板
│   └── 宝塔面板部署教程.md     # BT panel guide
├── resources/views/          # Blade 视图 Views
├── public/
│   ├── js/cropper.min.js     # 图片裁剪 Image crop
│   ├── css/cropper.min.css
│   └── icons/                # 社交平台黑色图标 Social icons
├── routes/                   # 路由 Routes
├── tests/Feature/            # 功能测试 Feature tests
├── docs/                     # 项目文档 Docs
└── .env.example              # 环境变量模板 Env template
```

---

## 更新日志 / Changelog

### v1.0.1（2025）

> Release: https://github.com/Abinius/Signify/releases/tag/v1.0.1

**功能 / Features**
- 社交链接多条化：最多 5 条、缺协议自动补 `http://`、去空去重 / Multi social links (max 5, auto-prefix `http://`)
- 详情页访客浏览量（会话 24h 去重、本人不计、超 10 才显示）/ Page views on profile (24h session dedup, excludes owner)
- 系统设置分享卡片图片支持上传 + 裁剪（1.91:1 / 1200×630，Cropper.js）/ Share-card image upload & crop
- 站点名称/描述统一从系统设置读取，去除硬编码 / Site name/description from settings

**界面 / UI**
- 全站页面标题统一居中（详情页、智库页、个人中心、后台等）/ Centered page titles site-wide
- 智库页卡片姓名/领域/城市居中 + 推荐徽标改奖章图标 / Card layout centering + recommend badge
- 详情页头部布局细化 + 小红书官方 logo 黑色版 / Detail header polish + XHS official logo

**修复 / Fixes**
- 修复 `og:title` / `<title>` 双重转义与存储型 XSS 隐患（新增回归测试）/ XSS escaping regression fix
- 移除建档邮箱验证门槛（恢复注册即建档）/ Removed email-verification gate for profile creation
- docker-compose 可运行化：新增 Dockerfile + 容器内 nginx 配置 / Working docker-compose
- 安装器加固：安装锁文件 + 限流 + 错误信息收敛 / Hardened installer (lock file + rate limit)
- PHP 版本声明对齐 `^8.2`，nginx 静态资源补 webp/avif / PHP `^8.2`, nginx webp/avif

**技术 / Tech**
- 社交域名图标映射收敛到 `config/social-icons.php` / Centralized social icon map
- 批量操作 ids 上限 100，防巨型 `whereIn` / Batch ids cap 100
- 62 个测试全绿 / 62 tests green

---

## License

MIT · [GitHub](https://github.com/Abinius/Signify) · [官方站点 / Official: vour.cn](https://vour.cn)
