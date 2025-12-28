# Flight Ticket 机票监控

[English README](README.md) | [中文文档](README.zh_Hans.md)

![demo.png](demo.png)

这是一个用于监控携程 `flights.ctrip.com` 票价并通过邮件通知订阅者的精简 PHP 服务。

## 简介
- 用户可以通过简单的网页界面（`index.php`）订阅单程航线（IATA 代码）和日期。
- 每小时运行的 worker 会抓取（可选使用 Puppeteer 渲染）搜索页面，提取票价、排序并通过 PHPMailer 发送邮件。
- 发送的邮件包含带 HMAC 签名的退订链接，允许收件人安全退订。

## 功能
- 网页订阅 UI：填写出发/到达机场、日期和收件邮箱（`index.php`）。
- 订阅保存在本地 SQLite 数据库（`subscriptions.db`）。
- worker（`worker.php`）负责构建检索 URL，调用可选的 Node/Puppeteer 渲染器（`fetcher.js`），并提取票价。
- 使用 PHPMailer + SMTP 发送邮件（通过环境变量配置）。
- 安全退订端点：`unsubscribe.php`（带 HMAC 签名的退订链接）。

## 依赖
- PHP 7.4+（需启用 `pdo_sqlite`）
- Composer（用于安装 PHPMailer）
- Node.js（可选，用于 `fetcher.js` 与 Puppeteer）

## 快速开始
1. 克隆仓库并进入项目目录。
2. 安装 PHP 依赖：

```bash
composer install
```

3. （可选）安装 Node 依赖以使用 fetcher：

```bash
npm init -y
npm install puppeteer
```

4. 初始化数据库（只需运行一次）：

```bash
php db_init.php
```

5. 在浏览器打开 `index.php` 创建订阅。

6. 运行 worker（手动或通过 cron）：

```bash
php worker.php
```

## 配置（.env）
在项目根创建 `.env`（参考 `.env.example`），填写 SMTP 凭据及其它可选项。示例：

```
# SMTP 服务
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USER=your_smtp_user@example.com
SMTP_PASS=your_smtp_password
SMTP_SECURE=tls
SMTP_FROM=monitor@example.com
SMTP_FROM_NAME="Flight Monitor"

# 可选：Node / fetcher
NODE_PATH=/usr/local/bin/node
FETCHER_SCRIPT=fetcher.js

# SQLite DB 文件路径
DB_FILE=subscriptions.db

# 退订 HMAC 秘钥（请使用强随机字符串）
UNSUBSCRIBE_SECRET=replace_this_with_a_strong_random_value
```

## 安全说明
- 请勿将 `.env` 或 `.unsubscribe_secret` 提交到 git（项目已在 `.gitignore` 中忽略 `.unsubscribe_secret`，建议也忽略 `.env`）。
- 你可以在 `.env` 中设置 `UNSUBSCRIBE_SECRET`，也可以让程序第一次运行时自动生成并保存到 `.unsubscribe_secret` 文件中。

本地测试（快速清单）
- 安装 Composer 并安装依赖：`composer install`。
- 初始化数据库：`php db_init.php`。
- 插入测试订阅（辅助脚本）：

```bash
php scripts/insert_test.php
```

- 生成订阅对应的退订 URL/Token：

```bash
php scripts/gen_token.php 1
```

- 本地测试退订处理（CLI 调用）：

```bash
php scripts/run_unsubscribe.php 1 test@example.com <token>
php scripts/list_subs.php  # 确认记录已删除
```

- 若要在不发送真实邮件的情况下测试 `worker.php`，可以配置本地 SMTP，或修改 `worker.php` 将 `$body` 写入文件以便查看。

## 常用脚本
- `scripts/insert_test.php` — 插入测试订阅
- `scripts/gen_token.php` — 打印订阅的退订 token 与 URL
- `scripts/run_unsubscribe.php` — 从 CLI 调用 `unsubscribe.php`（用于测试）
- `scripts/list_subs.php` — 列出数据库中订阅记录

## 文件一览
- `index.php` — 订阅 UI
- `subscribe.php` — 保存订阅
- `db_init.php` — 创建 SQLite 数据库
- `worker.php` — 定时抓取与发送邮件的 worker
- `unsubscribe.php` — 安全退订端点
- `lib/unsubscribe.php` — 签名/校验辅助函数
- `fetcher.js` — 可选的 Puppeteer 渲染器
- `subscriptions.db` — SQLite 数据库文件（由 `db_init.php` 创建）

## 定时执行（示例）

```cron
0 * * * * /usr/bin/php /full/path/to/worker.php >> /full/path/to/worker.log 2>&1
```

## 故障排查
- 未找到 PHPMailer：在项目根运行 `composer install`。
- Node/Puppeteer 报错：按 Puppeteer 文档安装 Chromium 及所需系统库。
- 无法解析价格：请抓取原始 HTML（使用 `fetcher.js`）并调整 `worker.php` 中的 `parse_prices()`。

## 贡献
- 可改进的方向：更健壮的解析规则、带过期时间的退订 token、UI 优化等。
- 请勿提交任何秘密信息到代码仓库。

## 许可证
- MIT