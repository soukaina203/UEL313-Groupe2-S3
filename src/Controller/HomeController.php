<?php

namespace Watson\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HomeController
{

    /**
     * Home page controller.
     *
     * @param Application $app Silex application
     */
    public function indexAction(Application $app)
    {
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
    public function linkAction($id, Request $request, Application $app)
    {
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
    public function tagAction($id, Request $request, Application $app)
    {
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
    public function loginAction(Request $request, Application $app)
    {
        return $app['twig']->render(
            'login.html.twig',
            array(
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


    /* 
How to test this RSS system:

1. Install `localtunnel` globally using npm:
   npm install -g localtunnel

2. In the terminal, run the following command to create a temporary public link for your local app:
   lt --port [your-local-app-port]

3. After running the command, you'll get a public URL (e.g., https://your-app.loca.lt). 
   Open this link in your browser.

4. A page will appear where you need to enter the password provided on the same page.
   To get the password, click the link: "https://loca.lt/mytunnelpassword"

5. Enter the password, and your Watson app will be displayed in the browser.

6. Navigate to the RSS route in the app.
   The XML document containing the last 15 published links will be shown.

For Testing:
- We used the **Feed Validator** and W3C tool to validate the RSS feed:
  - Visit: https://www.feedvalidator.org/
  - Enter the public link of your app in the Feed Validator to check if your RSS feed is valid.
*/

public function getLinks(Application $app)
{
    // Retrieve the last 15 links from the database using the DAO (Data Access Object)
    $links = $app['dao.link']->getLast15Links();

    // Create the root RSS XML element with version 2.0
    $xml = new \SimpleXMLElement('<rss version="2.0"/>');

    // Add the <channel> element to the RSS feed
    $channel = $xml->addChild('channel');
    $channel->addChild('title', 'My RSS Feed');
    $channel->addChild('link', 'http://chubby-ideas-suffer.loca.lt/rss');
    $channel->addChild('description', 'Last 15 published links on Watson');

    // Get the actual URL of the feed dynamically for the atom:link self-reference
    $feedUrl = $app['url_generator']->generate('rss_feed_route', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);

    // Add Atom link metadata for self-referencing the feed with the correct URL
    $atomLink = $channel->addChild('atom:link', null, 'http://www.w3.org/2005/Atom');
    $atomLink->addAttribute('rel', 'self');
    $atomLink->addAttribute('href', $feedUrl);

    // Loop through the retrieved links and add each as an <item> in the RSS feed
    foreach ($links as $link) {
        $item = $channel->addChild('item');
        $item->addChild('title', htmlspecialchars($link->getTitle()));
        $item->addChild('link', htmlspecialchars($link->getUrl()));
        $item->addChild('description', htmlspecialchars($link->getDesc()));
        $item->addChild('guid', htmlspecialchars($link->getUrl() . '#' . $link->getId()));

        // If the link has associated tags, add them as <category> elements
        $tags = $link->getTags();
        if (is_array($tags)) {
            $tagNames = array_map(fn ($tag) => htmlspecialchars($tag->getTitle()), $tags);
            $item->addChild('category', implode(', ', $tagNames));
        }
    }

    // Create a new DOMDocument for better formatting of the RSS XML
    $dom = new \DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;

    $importedNode = $dom->importNode(dom_import_simplexml($xml), true);
    $dom->appendChild($importedNode);

    return new Response($dom->saveXML(), 200, ['Content-Type' => 'application/rss+xml']);
}


}
