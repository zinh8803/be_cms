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
    /**
     * GET /sitemap.xml
     * Generates a dynamic XML sitemap containing all published blog articles.
     */
    public function actionIndex()
    {
        // 1. Fetch all published posts (not deleted, published, and public visibility)
        $posts = Post::find()
            ->publicActive()
            ->orderBy(['id' => SORT_DESC])
            ->all();

        // 2. Build the XML response
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        // Homepage
        $xml .= "  <url>\n";
        $xml .= "    <loc>https://blogcuavinh.id.vn/</loc>\n";
        $xml .= "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
        $xml .= "    <changefreq>daily</changefreq>\n";
        $xml .= "    <priority>1.0</priority>\n";
        $xml .= "  </url>\n";

        // Login Page
        $xml .= "  <url>\n";
        $xml .= "    <loc>https://blogcuavinh.id.vn/login</loc>\n";
        $xml .= "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
        $xml .= "    <changefreq>monthly</changefreq>\n";
        $xml .= "    <priority>0.5</priority>\n";
        $xml .= "  </url>\n";

        // Register Page
        $xml .= "  <url>\n";
        $xml .= "    <loc>https://blogcuavinh.id.vn/register</loc>\n";
        $xml .= "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
        $xml .= "    <changefreq>monthly</changefreq>\n";
        $xml .= "    <priority>0.5</priority>\n";
        $xml .= "  </url>\n";

        // Loop posts
        foreach ($posts as $post) {
            $lastmodTimestamp = $post->updated_at ?: $post->created_at;
            $lastmodDate = date('Y-m-d', $lastmodTimestamp);
            
            $xml .= "  <url>\n";
            $xml .= "    <loc>https://blogcuavinh.id.vn/posts/" . htmlspecialchars($post->slug) . "</loc>\n";
            $xml .= "    <lastmod>" . $lastmodDate . "</lastmod>\n";
            $xml .= "    <changefreq>monthly</changefreq>\n";
            $xml .= "    <priority>0.8</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        // 3. Return as raw XML response
        Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'application/xml; charset=utf-8');
        
        return $xml;
    }
}
