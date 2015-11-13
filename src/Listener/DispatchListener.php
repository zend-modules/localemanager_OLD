<?php
/**
 * Locale Manager
 * 
 * @author Juan Pedro Gonzalez Gutierrez
 * @copyright Copyright (c) 2015 Juan Pedro Gonzalez Gutierrez
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GPL v3
 */

namespace LocaleManager\Listener;

use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\MvcEvent;

class DispatchListener extends AbstractListenerAggregate
{
    /**
     * Attach listeners to an event manager
     *
     * @param  EventManagerInterface $events
     * @return void
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH, array($this, 'onDispatch'));
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, array($this, 'onDispatchError'));
    }

    /**
     * Set the Content-Language header in response.
     * 
     * @param MvcEvent $e
     */
    protected function setContentLanguage(MvcEvent $e)
    {
        $serviceManager = $e->getApplication()->getServiceManager();

        if (!$serviceManager->has('LocaleManager')) {
            return;
        }

        $response = $e->getResponse();
        if (!method_exists($response, 'getHeaders')) {
            return;
        }

        $localeManager = $serviceManager->get('LocaleManager');
        $locale = $localeManager->getLocale();

        $primaryLanguage = \Locale::getPrimaryLanguage($locale);
        $region          = \Locale::getRegion($locale);
        $locale          = $primaryLanguage . (!empty($region) ? '-' . $region : '');

        $response->getHeaders()->addHeaderLine('Content-Language', $locale);
    }

    /**
     * Listen to the "dispatch" event
     *
     * @param  MvcEvent $e
     * @return mixed
     */
    public function onDispatch(MvcEvent $e)
    {
        $this->setContentLanguage($e);
    }

    /**
     * Listen to the "dispatch.error" event
     *
     * @param  MvcEvent $e
     * @return mixed
     */
    public function onDispatchError(MvcEvent $e)
    {
        $this->setContentLanguage($e);
    }
}