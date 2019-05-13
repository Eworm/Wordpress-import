<?php

namespace Statamic\Addons\WordpressImport;

use Statamic\API\Nav;
use Statamic\Extend\Listener;

class WordpressImportListener extends Listener
{
    /**
     * The events to be listened for, and the methods to call.
     *
     * @var array
     */
     public $events = [
         'cp.nav.created' => 'addNavItems',
     ];

     public function addNavItems($nav)
     {
         // Create the first level navigation item
         // Note: by using route('store'), it assumes you've set up a route named 'store'.
         $menus = Nav::item('Wordpress import')->route('addons.wordpress_import')->icon('rocket');

         // Finally, add our first level navigation item
         // to the navigation under the 'tools' section.
         $nav->addTo('tools', $menus);
     }
}
