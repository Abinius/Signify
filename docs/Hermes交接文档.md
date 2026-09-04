# Signify · Hermes 交接文档

> 交接对象：**Hermes**（下一轮协作方）
> 交接时间：2026-08-15
> 仓库：`https://github.com/Abinius/Signify`（原 AbinCheungCom 已迁移）
> 分支：`main`（已与 `origin/main` 同步，HEAD = `63086b7`）
> 技术栈：Laravel 11 + Blade + Alpine.js + 静态 Tailwind + Cropper.js（服务器零构建）
> 官方站点：https://vour.cn

---

## 1. 本会话改动（提交链 `ef498bb → 63086b7`）

| commit | 内容 | 涉及文件 |
|---|---|---|
| `7615311` | 上一轮 Hermes 交接文档入库 | `docs/Hermes交接文档.md` |
| `2f52f23` | **四期功能上线**：汉堡菜单（前后台全屏弹窗）/ 申请推荐（理由必填+15天冷却）/ 退出账户 / 系统设置（settings 表） | `routes/web.php`、`layouts/app+admin.blade.php`、`Entrepreneur/Setting` 模型、`MyProfileController`、`AdminController`、迁移 `000001/000002`、`tests/*` |
| `556b1a3` | **社交链接**（social_platform/social_url）+ **iOS 聚焦缩放彻底修复**（viewport maximum-scale + 输入框 16px）+ 个人中心退出入口移除 + bio 4→8 行 | 迁移 `000003`、`Entrepreneur`、`ProfileUpdateRequest`、`show.blade.php`、`layouts/*`、`public/icons/*`(8 图标)、composer.lock、storage gitignore 骨架 |
| `2819542` | **头像/形象照裁剪改造（方案 B）**：Cropper.js 4:5 主裁自动派生 1:1 头像、二维码 1:1@512、新增 `portrait` 字段；社交平台整合为**单网址输入+域名识别图标**（品牌域名 logo.svg）；个人中心布局优化 | 迁移 `000004`、`cropper.min.js/css`、`logo.svg`、`profile/edit.blade.php`、`show.blade.php`、`Entrepreneur::socialIconForUrl()` |
| `c677ca2` | 服务器智能体交接清单入库 | `docs/05-服务器智能体交接清单.md` |
| `3d95939` | README 中英文双语重写 + 官方站点 vour.cn | `README.md` |
| `63086b7` | 系统设置分享卡片图片改为上传+裁剪（1.91:1 / 1200×630） | `admin/settings.blade.php`、`layouts/admin.blade.php`(@stack)、`SettingsTest` |

**分支状态**：`main` 已推送远程同步。本地另有 `database/database.sqlite`（测试库，已 gitignore）。

---

## 2. 系统当前状态

| 维度 | 现状 |
|---|---|
| 导航 | 前台+后台统一右上角固定悬浮汉堡 + 全屏弹窗；已删除 LOGO/顶部工具条 |
| 认证 | 档案认证 pending/approved/rejected；推荐申请独立状态(理由+15天冷却)；个人中心显示「已收录」 |
| 申请推荐 | 个人中心一键申请（理由弹窗必填）→ 后台「认证/推荐」双标签审核 → 通过后进智库 |
| 社交链接 | 单个网址输入，**按域名识别黑色图标**（品牌域 logo.svg、平台域各图标、未知域 google.svg）；协议白名单 http(s) |
| 图片 | 形象照(4:5)+头像(1:1 自动派生)+二维码(1:1@512)+分享卡片图(1.91:1@1200×630) 全部浏览器端裁剪（Cropper.js），服务器只存 |
| 系统设置 | settings 表（key-value + 缓存）：站点名/描述/分享卡片/footer/ICP；分享图走上传裁剪 |
| iOS 缩放 | viewport `maximum-scale=1,user-scalable=no` + 输入框 16px，聚焦不再放大 |
| 测试 | **62 用例 / 146 断言全绿**（本地 PHP 8.2 + sqlite） |

---

## 3. 待办事项（上线前必须做）

按顺序执行：

```bash
# 1. ⚠️ Composer ≥2.10 需先全局关闭安全公告拦截（Laravel 11 已 EOL）
composer config --global policy.advisories.block false

# 2. 安装依赖（缺 fileinfo 需忽略平台要求）
composer install --no-dev --optimize-autoloader --ignore-platform-req=ext-fileinfo

# 3. 迁移（新增 portrait 等，应用全部 000001~000004）
php artisan migrate --force

# 4. 存储软链（头像/形象照/二维码/分享图目录）
php artisan storage:link

# 5. 刷新缓存
php artisan config:cache
php artisan route:cache
```

---

## 4. 重要背景与坑（Hermes 必读）

### 4.1 服务器硬约束
1.8G 内存 · 无 Docker · **缺 fileinfo** · **服务器零构建**。
- 所有前端产物（`public/css/app.css`、`public/js/cropper.min.js`、`public/css/cropper.min.css`、`public/icons/*.svg`）已入库，服务器只 `git pull`，**绝不跑 npm**。
- 头像上传校验已绕开 finfo（扩展名白名单 + GD 兜底）。

### 4.2 Laravel 11 已 EOL ⚠️
Composer ≥2.10 默认拦截 `laravel/framework ^11.0` 安全公告，`composer install` 会直接报错。部署前必须 `composer config --global policy.advisories.block false`。**中长期应规划升级 Laravel 12。**

### 4.3 域名匹配用边界判断（勿改回子串）
`socialIconForUrl()` 用 `$host === $domain || str_ends_with($host, '.'.$domain)`，避免仿冒域名（`61lm.com.cn` 等）误判。**不要把 `str_contains` 改回来。**

### 4.4 `{{ @yield(...) }}` 会导致全站 500 💀
Blade 的 `@yield` 放在 `{{ }}` 里不会被编译，页面直接 500。og/twitter meta 里的用户内容转义必须在 `@section` 源头用 `e()`，layout 保持裸 `@yield`。

### 4.5 `social_platform` / `social_url` 列已删除
迁移 `000003` 曾建单链接列 social_platform + social_url；迁移 `000001` 改用 `social_links` JSON 数组后旧列不再读写。迁移 `2026_09_05_000001` 已 drop 这两列，`Entrepreneur` fillable 同步移除。勿据此字段渲染图标。

### 4.6 抖音图标为占位
开源图标库无抖音官方字形，`public/icons/douyin.svg` 用的是 TikTok 风格占位。有官方 SVG 可直接替换同名文件。

### 4.7 MySQL 5.7 兼容
迁移全部用 `string` 替代 enum；`after()` 在 MySQL 生效（sqlite 为 no-op）。

---

## 5. 安全隐患 ⚠️⚠️（最高优先级）

- 上轮（08-14）已记录：GitHub PAT（`ghp_S8…` 开头）曾在会话记录中**明文泄露**，要求吊销/轮换。**请确认已处理；如未处理，立即到 GitHub Settings → Developer settings → Personal access tokens 吊销。**
- 任何情况下：不要把 token 写进 skill 文件、代码、配置文件。推送走 Windows 凭据管理器 / `git credential manager`。
- 交接后若发现残留明文 token 的会话文件，先擦除再分享。

---

## 6. 给 Hermes 的下一步

1. `git pull` 对齐 `main`（`63086b7`），核对 `git remote -v` 为 `https://github.com/Abinius/Signify.git`
2. 按 §3 部署：composer 关公告拦截 → install → migrate → storage:link → config:cache
3. 确认 §5 token 已轮换
4. 上线后按 `docs/05-服务器智能体交接清单.md` §5 做浏览器验证（裁剪弹窗、社交图标、推荐申请、系统设置、移动端缩放）
5. 评估 Laravel 12 升级（§4.2）

---

*本交接文档随下一轮协作结束更新。*
