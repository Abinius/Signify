<?php

namespace App\Http\Controllers;

use App\Models\Entrepreneur;
use App\Models\Setting;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    /**
     * robots.txt：放行全部，引用 sitemap（Google/必应均支持）。
     */
    public function robots(): Response
    {
        $base = rtrim(url('/'), '/');
        $body = "User-agent: *\nAllow: /\n\nSitemap: {$base}/sitemap.xml\n";

        return response($body)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * sitemap.xml：首页 + 智库 + 全部已认证名片页（动态，含 lastmod）。
     */
    public function sitemap(): Response
    {
        $base = rtrim(url('/'), '/');
        $urls = [
            ['loc' => $base.'/', 'freq' => 'daily', 'priority' => '1.0'],
            ['loc' => $base.'/entrepreneurs', 'freq' => 'daily', 'priority' => '0.9'],
        ];

        Entrepreneur::where('status', Entrepreneur::STATUS_APPROVED)
            ->select('id', 'share_slug', 'updated_at')
            ->get()
            ->each(function (Entrepreneur $e) use (&$urls, $base) {
                $urls[] = [
                    'loc' => $e->url,
                    'freq' => 'weekly',
                    'priority' => '0.8',
                    'lastmod' => $e->updated_at?->toAtomString(),
                ];
            });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $u) {
            $xml .= "  <url><loc>{$u['loc']}</loc><changefreq>{$u['freq']}</changefreq><priority>{$u['priority']}</priority>";
            if (!empty($u['lastmod'])) {
                $xml .= "<lastmod>{$u['lastmod']}</lastmod>";
            }
            $xml .= "</url>\n";
        }
        $xml .= '</urlset>';

        return response($xml)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * llms.txt（GEO）：站点摘要 + 主要页面 + 名片列表，供生成式/AI 引擎消费。
     */
    public function llms(): Response
    {
        $base = rtrim(url('/'), '/');
        $siteName = Setting::get('site_name', 'SIGNIFY');
        $desc = Setting::get('site_description', 'SIGNIFY — 用数字化技术放大企业家的个人价值。');

        $lines = [
            "# {$siteName}",
            '',
            $desc,
            '',
            '## Main',
            "- [Home]({$base}/)",
            "- [智库 / Figures]({$base}/entrepreneurs)",
            '',
            '## Figures',
        ];

        Entrepreneur::where('status', Entrepreneur::STATUS_APPROVED)
            ->select('id', 'share_slug', 'name', 'industry')
            ->get()
            ->each(function (Entrepreneur $e) use (&$lines, $base) {
                $label = $e->industry ? "{$e->name} ({$e->industry})" : $e->name;
                $lines[] = "- [{$label}]({$e->url})";
            });

        return response(implode("\n", $lines)."\n")
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
