<?php

namespace Watson\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HomeController {

    /**
     * Home page controller.
     *
     * @param Application $app Silex application
     */
    public function indexAction(Application $app) {
        $links = $app['dao.link']->findAll();
        return $app['twig']->render('index.html.twig', array('links' => $links));
    }

    /**
     * Link details controller.
     *
     * @param integer $id Link id
     * @param Request $request Incoming request
     * @param Application $app Silex application
     */
    public function linkAction($id, Request $request, Application $app) {
        $link = $app['dao.link']->find($id);
        if ($app['security.authorization_checker']->isGranted('IS_AUTHENTICATED_FULLY')) {
            // A user is fully authenticated : he can add comments
            // Check if he's author for manage link

        }
        return $app['twig']->render('link.html.twig', array(
            'link' => $link
        ));
    }

    /**
     * Links by tag controller.
     *
     * @param integer $id Tag id
     * @param Request $request Incoming request
     * @param Application $app Silex application
     */
    public function tagAction($id, Request $request, Application $app) {
        $links = $app['dao.link']->findAllByTag($id);
        $tag   = $app['dao.tag']->findTagName($id);

        return $app['twig']->render('result_tag.html.twig', array(
            'links' => $links,
            'tag'   => $tag
        ));
    }

    /**
     * User login controller.
     *
     * @param Request $request Incoming request
     * @param Application $app Silex application
     */
    public function loginAction(Request $request, Application $app) {
        return $app['twig']->render('login.html.twig', array(
            'error'         => $app['security.last_error']($request),
            'last_username' => $app['session']->get('_security.last_username'),
            )
        );
    }



      /**
     * Rss .
     *
   
 * @param Request $request Incoming request
 * @param Application $app Silex application
 */
public function getLinks( Application $app)
{
 
    $links = $app['dao.link']->findAll();

 
    $xml = new \SimpleXMLElement('<rss version="2.0"/>');  
    $channel = $xml->addChild('channel');

    $channel->addChild('title', 'My RSS Feed');
    $channel->addChild('link', 'http://example.com/rss');
    $channel->addChild('description', 'Latest links from the database');

 
    $atomLink = $channel->addChild('atom:link', null, 'http://www.w3.org/2005/Atom');
    $atomLink->addAttribute('rel', 'self');
    $atomLink->addAttribute('href', 'http://example.com/rss');  

    foreach ($links as $link) {
        $item = $channel->addChild('item');

        // Add basic fields
        $item->addChild('title', htmlspecialchars($link->getTitle()));
        $item->addChild('link', htmlspecialchars($link->getUrl()));
        $item->addChild('description', htmlspecialchars($link->getDesc()));

        $item->addChild('guid', htmlspecialchars($link->getUrl() . '#' . $link->getId()));

        $tags = $link->getTags();
        if (is_array($tags)) {
            $tagNames = array_map(function ($tag) {
                return htmlspecialchars($tag->getTitle());
            }, $tags); 

            $tagsString = implode(', ', $tagNames); 
            $item->addChild('category', $tagsString);
        }
    }

    return new Response($xml->asXML(), 200, ['Content-Type' => 'application/rss+xml']);
}




}
