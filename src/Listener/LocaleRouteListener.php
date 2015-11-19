<?php
/**
 * Locale Manager
 * 
 * @author Juan Pedro Gonzalez Gutierrez
 * @copyright Copyright (c) 2015 Juan Pedro Gonzalez Gutierrez
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GPL v3
 */

namespace LocaleManager\Listener;

use LocaleManager\LocaleManagerInterface;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\I18n\Translator\TranslatorAwareInterface;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\Http\RouteMatch;
use Zend\ServiceManager\ServiceLocatorInterface;

class LocaleRouteListener extends AbstractListenerAggregate
{
    const LOCALE    = '__LOCALE__';

    /**
     * Attach to an event manager
     *
     * @param  EventManagerInterface $events
     * @param  int $priority
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, array($this, 'onRoute'), $priority);
    }

    /**
     * Listen to the "route" event and determine if the module namespace should
     * be prepended to the controller name.
     *
     * If the route match contains a parameter key matching the MODULE_NAMESPACE
     * constant, that value will be prepended, with a namespace separator, to
     * the matched controller parameter.
     *
     * @param  MvcEvent $e
     * @return null
     */
    public function onRoute(MvcEvent $e)
    {
        $matches = $e->getRouteMatch();
        if (!$matches instanceof RouteMatch) {
            // Can't do anything without a route match
            return;
        }

        $locale = $matches->getParam(self::LOCALE, false);
        if (!$locale) {
            // No locale found; nothing to do
            return;
        }

        // Set the locale in the locale manager
        $serviceManager = $e->getApplication()->getServiceManager();
        if ($serviceManager instanceof ServiceLocatorInterface) {
            if ($serviceManager->has('LocaleManager')) {
                $localeManager = $serviceManager->get('LocaleManager');
                if ($localeManager instanceof LocaleManagerInterface) {
                    $localeManager->setLocale($locale);
                }
            }
        }

        // Set the router translator's locale
        $router = $e->getRouter();
        if ($router instanceof TranslatorAwareInterface) {
            $translator = $router->getTranslator();
            if (method_exists($translator, 'setLocale')) {
                $translator->setLocale($locale);
            }
        }

        // Set the locale in the matches
        $matches->setParam('locale', \Locale::canonicalize($locale));
    }
}