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


/* How to test this RSS system
npm install -g localtunnel
dans le terminal lt --port the local port of my app 
it gives you a temporary public link to your local app
you type the link in your browser 
a page is shown where you have to enter the password provided in the same page just click on a link "https://loca.lt/mytunnelpassword"
enter the password 
then the watson app get displayed 
go to the rss route , the xml document containing 15 last links being published 

FOR TESTING
 we used FEED Validator  https://www.feedvalidator.org/
 we pass the public link of our app there and then we got if our feed is validated or not 
*/

public function getLinks(Application $app)
{
    // Retrieve the last 15 links from the database
    $links = $app['dao.link']->getLast15Links();

    // Create the root RSS XML element
    $xml = new \SimpleXMLElement('<rss version="2.0"/>');

    // Add the <channel> element to the RSS feed
    $channel = $xml->addChild('channel');
    $channel->addChild('title', 'My RSS Feed'); // Set the title of the RSS feed
    $channel->addChild('link', 'http://chubby-ideas-suffer.loca.lt/rss'); // Correct the link to the RSS feed
    $channel->addChild('description', 'Last 15 published links on Watson'); // Set the description of the RSS feed

    // Add Atom link metadata for self-referencing the feed
    $atomLink = $channel->addChild('atom:link', null, 'http://www.w3.org/2005/Atom');
    $atomLink->addAttribute('rel', 'self'); // Define the link relation as self
    $atomLink->addAttribute('href', 'http://chubby-ideas-suffer.loca.lt/rss'); // Correct the feed URL to match the actual location

    // Loop through the retrieved links and add them to the RSS feed
    foreach ($links as $link) {
        // Create an <item> element for each link
        $item = $channel->addChild('item');

        // Add the title of the link
        $item->addChild('title', htmlspecialchars($link->getTitle()));
        // Add the URL of the link
        $item->addChild('link', htmlspecialchars($link->getUrl()));
        // Add the description of the link
        $item->addChild('description', htmlspecialchars($link->getDesc()));
        // Add a globally unique identifier (GUID) for the link
        $item->addChild('guid', htmlspecialchars($link->getUrl() . '#' . $link->getId()));

        // If the link has associated tags, add them as <category> elements
        $tags = $link->getTags();
        if (is_array($tags)) {
            // Extract tag titles and encode them to prevent XML injection
            $tagNames = array_map(fn($tag) => htmlspecialchars($tag->getTitle()), $tags);
            // Combine tags into a comma-separated string and add as a <category> element
            $item->addChild('category', implode(', ', $tagNames));
        }
    }

    // Create a new DOMDocument for better formatting of the RSS XML
    $dom = new \DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false; // Remove unnecessary whitespace
    $dom->formatOutput = true; // Enable pretty-printing for easier readability

    // Import the SimpleXMLElement into the DOMDocument
    $importedNode = $dom->importNode(dom_import_simplexml($xml), true);
    $dom->appendChild($importedNode); // Append the imported node to the DOMDocument

    // Return the formatted XML as a response with the correct content type
    return new Response($dom->saveXML(), 200, ['Content-Type' => 'application/rss+xml']);
}

}
