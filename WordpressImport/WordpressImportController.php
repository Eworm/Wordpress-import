<?php

namespace Statamic\Addons\WordpressImport;

use Illuminate\Http\Request;
use Statamic\API\Entry;
use Statamic\API\Page;
use Statamic\API\Term;
use Statamic\API\Collection;
use Statamic\API\User;
use Statamic\Console\Please;
use Statamic\API\File;
use Statamic\API\AssetContainer;
use Statamic\Extend\Controller;

class WordpressImportController extends Controller
{
    /**
     * Maps to your route definition in routes.yaml
     *
     * @return mixed
     */
    public function index()
    {
        return $this->view('index');
    }


    /**
     * Maps to your route definition in routes.yaml
     *
     * @return mixed
     */
    public function map(Request $request)
    {
        $xml = simplexml_load_file($_FILES['file']['tmp_name']);
        $attachments = [];
        $posts = [];

        // Add authors as new users
        foreach ($xml->channel->children('wp', true)->author as $author) {
            $with = [];
            $username = (string)$author->children('wp', true)->author_login;
            $email = (string)$author->children('wp', true)->author_email;
            $with['first_name'] = (string)$author->children('wp', true)->author_first_name;
            $with['last_name'] = (string)$author->children('wp', true)->author_last_name;

            if (!User::whereUsername($username)) {
                User::create($username)
                    ->username($username)
                    ->email($email)
                    ->with($with)
                    ->save();
            }
        }

        // Loop over the content
        foreach ($xml->channel->item as $item) {
            $with = [];
            $cats = [];
            $status = (string)$item->children('wp', true)->status;
            $postname = (string)$item->children('wp', true)->post_name;
            $pubdate = date('Y-m-d', strtotime((string)$item->pubDate));
            $type = (string)$item->children('wp', true)->post_type;
            $attachment_url = (string)$item->children('wp', true)->attachment_url;

            $with['title'] = trim(preg_replace('/\t+/', '', (string)$item->title));
            $with['excerpt'] = trim(preg_replace('/\t+/', '', (string)$item->children('excerpt', true)));
            $with['content'] = trim(preg_replace('/\t+/', '', (string)$item->children('content', true)));
            $with['author'] = trim(preg_replace('/\t+/', '', (string)$item->children('dc', true)));

            // Add post thumbnails
            foreach ($item->children('wp', true)->postmeta as $thumbnail) {
                $id = (string)$thumbnail->children('wp', true)->meta_value;

                if ((string)$thumbnail->children('wp', true)->meta_key == '_thumbnail_id') {

                    // Find the matching item using the thumbnail id
                    foreach ($xml->channel->item as $newitem) {
                        if ((string)$newitem->children('wp', true)->post_id == $id) {
                            $with['thumbnail'] = $attachment_url;
                        }
                    }
                }
            }

            // Add meta values
            foreach ($item->children('wp', true)->postmeta as $meta) {
                $key = (string)$meta->children('wp', true)->meta_key;
                $value = (string)$meta->children('wp', true)->meta_value;

                if (substr($key, 0, 1) != '_') {
                    $with[(string)$meta->children('wp', true)->meta_key] = (string)$meta->children('wp', true)->meta_value;
                }
            }

            // Add entries
            if ($type != 'page' && $type != 'acf-field' && $type != 'attachment' && $type != 'acf-field-group' && $type != 'nav_menu_item') {

                // Add categories as new terms
                foreach ($item->category as $cat) {
                    $cats[] = (string)$cat;
                    $this->save_terms((string)$cat, 'tags');
                }
                if (!empty($cats)) {
                    $with['tags'] = $cats;
                }

                // Add post types as new collections
                if (!Collection::handleExists($type)) {
                    Collection::create($type)
                        ->save();
                }

                // Add posts as entries
                if ($status == 'publish') {
                    if (isset($postname)) {
                        Entry::create($postname)
                            ->collection($type)
                            ->with($with)
                            ->date($pubdate)
                            ->save();
                    } else {
                        Entry::create($postname)
                            ->collection($type)
                            ->with($with)
                            ->published(false)
                            ->date($pubdate)
                            ->save();
                    }
                }
            }

            // Add pages
            if ($type == 'page') {
                if ($status == 'publish') {
                    Page::create($postname)
                        ->with($with)
                        ->save();
                } else {
                    Page::create($postname)
                        ->with($with)
                        ->published(false)
                        ->save();
                }
            }

            // Download images
            if ($type == 'attachment') {
                $this->grab_image($attachment_url, '/');
            }

            $posts[] = array(
                'title' => (string)$item->title,
                'status' => $status,
                'post_name' => $postname,
                'link' => (string)$item->link,
                'excerpt' => (string)$item->children('excerpt', true),
                'pubdate' => $pubdate,
                'creator' => (string)$item->children('dc', true)->creator,
                'content' => (string)$item->children('content', true),
                'type' => $type,
                'attachment_url' => (string)$item->children('wp', true)->attachment_url,
                'categories' => $cats
            );
        }

        // Clear the stache
        Please::call('clear:stache');

        return $this->view('map');
    }


    /**
     * Saves terms
     *
     * @return nothing
     */
    private function save_terms($tag, $taxonomy)
    {
        if (!Term::slugExists(slugify($tag), $taxonomy)) {
            Term::create(slugify($tag))
                ->taxonomy($taxonomy)
                ->save();
        }
    }


    /**
     * Downloads an image to an asset container
     *
     * @return info
     */
    public function grab_image($url, $path)
    {
        $container = AssetContainer::wherePath($path);
        $basename = basename($url);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        // Required to be false
        curl_setopt($ch, CURLOPT_HEADER, 0);
        //Required for http(s)
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        // Required to be true
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        $data = curl_exec($ch);

        File::disk('local')->put('assets/wp/' . $basename, $data);
        curl_close($ch);
    }
}
