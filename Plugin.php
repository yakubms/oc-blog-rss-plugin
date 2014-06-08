<?php namespace SoBoRed\Rss;

use DB;
use DateTime;
use File;
use Backend;
use Controller;
use Event;
use System\Classes\PluginBase;
use SoBoRed\Rss\Models\Settings;

class Plugin extends PluginBase
{

    public function pluginDetails()
    {
        return [
            'name'        => 'Blog RSS Feed',
            'description' => 'An RSS feed generator for the RainLab Blog plugin',
            'author'      => 'Josh Hall',
            'icon'        => 'icon-rss'
        ];
    }

    public function registerComponents()
    {
        return [
            'SoBoRed\Rss\Components\Link' => 'rssLink'
        ];
    }

    public function boot()
    {
        // Event Listeners for RainLab Blog
        Event::listen('eloquent.created: RainLab\Blog\Models\Post', function($model) {
            $this->createRss();
        });
        Event::listen('eloquent.saved: RainLab\Blog\Models\Post', function($model) {
            $this->createRss();
        });
        Event::listen('eloquent.deleted: RainLab\Blog\Models\Post', function($model) {
            $this->createRss();
        });

        // Event Listeners for SoBoRed settings
        Event::listen('eloquent.saved: SoBoRed\Rss\Models\Settings', function($model) {
            $this->createRss();
        });
    }

    public function registerSettings()
    {
        return [
            'settings' => [
                'label'       => 'Blog RSS Settings',
                'description' => 'Manage the Blog RSS settings.',
                'category'    => 'Blog',
                'icon'        => 'icon-rss',
                'class'       => 'SoBoRed\Rss\Models\Settings',
                'order'       => 100
            ]
        ];
    }

    protected function createRss()
    {
        $fileContents = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n" .
                        "<rss version=\"2.0\">\n".
                        "\t<channel>\n".
                        "\t\t<title>" . Settings::get('title') . "</title>\n" .
                        "\t\t<link>" . Settings::get('link') . "</link>\n" .
                        "\t\t<description>" . Settings::get('description') . "</description>\n\n";


        foreach($this->loadPosts() as $post)
        {
            $published = DateTime::createFromFormat('Y-m-d H:i:s', $post->published_at);

            $fileContents .= "\t\t<item>\n" .
                             "\t\t\t<title>" . $post->title . "</title>\n" .
                             "\t\t\t<link>" . Settings::get('link') . Settings::get('postPage') . "/" . $post->slug . "</link>\n" .
                             "\t\t\t<guid>" . Settings::get('link') . Settings::get('postPage') . "/" . $post->slug . "</guid>\n" .
                             "\t\t\t<pubDate>" . $published->format('M d, Y') . "</pubDate>\n" .
                             "\t\t\t<description>" . $post->excerpt . "</description>\n" .
                             "\t\t</item>\n";

        }

        $fileContents .= "\t</channel>\n";
        $fileContents .= "</rss>\n";

        $file = File::put('rss.xml', $fileContents);

        return $file;
    }

    protected function loadPosts()
    {
        $posts = Db::table('rainlab_blog_posts')
                     ->orderBy('published_at', 'desc')
                     ->having('published', '=', '1')
                     ->get();

        return $posts;
    }
}