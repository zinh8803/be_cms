<?php

namespace app\controllers;

use Yii;
use app\models\Post;
use yii\web\Controller;

/**
 * SitemapController handles dynamic SEO sitemap requests.
 */
class SitemapController extends Controller
{
    const SITEMAP_CACHE_KEY = 'sitemap_xml_v2';
    const SITEMAP_CACHE_TTL = 3600; // 1 hour

    /**
     * GET /sitemap.xml
     * Generates a dynamic XML sitemap containing all published blog articles.
     * Cached in Redis for 1 hour to avoid heavy DB reads on every crawl.
     */
    public function actionIndex()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'application/xml; charset=utf-8');
        Yii::$app->response->headers->set('Cache-Control', 'public, max-age=3600');

        // Serve from cache if available
        $cached = Yii::$app->cache->get(self::SITEMAP_CACHE_KEY);
        if ($cached !== false) {
            return $cached;
        }

        // Fetch only the columns needed for sitemap generation
        $posts = Post::find()
            ->select(['slug', 'updated_at', 'created_at'])
            ->publicActive()
            ->orderBy(['id' => SORT_DESC])
            ->asArray()
            ->all();

        $siteUrl = 'https://blogcuavinh.id.vn';

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Homepage
        $xml .= "  <url>\n";
        $xml .= "    <loc>{$siteUrl}/</loc>\n";
        $xml .= '    <lastmod>' . date('Y-m-d') . "</lastmod>\n";
        $xml .= "    <changefreq>daily</changefreq>\n";
        $xml .= "    <priority>1.0</priority>\n";
        $xml .= "  </url>\n";

        // Blog posts only (login/register are noindex — should NOT be in sitemap)
        foreach ($posts as $post) {
            $lastmodTimestamp = $post['updated_at'] ?: $post['created_at'];
            $lastmodDate      = date('Y-m-d', $lastmodTimestamp);

            // Dynamic changefreq: weekly if updated within last 30 days, else monthly
            $daysSinceUpdate = (time() - $lastmodTimestamp) / 86400;
            $changefreq      = $daysSinceUpdate <= 30 ? 'weekly' : 'monthly';

            $xml .= "  <url>\n";
            $xml .= '    <loc>' . $siteUrl . '/posts/' . htmlspecialchars($post['slug']) . "</loc>\n";
            $xml .= "    <lastmod>{$lastmodDate}</lastmod>\n";
            $xml .= "    <changefreq>{$changefreq}</changefreq>\n";
            $xml .= "    <priority>0.8</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        // Store in cache
        Yii::$app->cache->set(self::SITEMAP_CACHE_KEY, $xml, self::SITEMAP_CACHE_TTL);

        return $xml;
    }
}

