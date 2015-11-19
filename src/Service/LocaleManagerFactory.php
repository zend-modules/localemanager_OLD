<?php
/**
 * Locale Manager
 * 
 * @author Juan Pedro Gonzalez Gutierrez
 * @copyright Copyright (c) 2015 Juan Pedro Gonzalez Gutierrez
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GPL v3
 */

namespace LocaleManager\Service;

use LocaleManager\Listener\DispatchListener;
use LocaleManager\Listener\LocaleRouteListener;
use LocaleManager\Listener\ViewRenderListener;
use LocaleManager\LocaleManager;
use LocaleManager\LocaleManagerAwareInterface;
use LocaleManager\LocaleManagerInterface;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\I18n\Translator\TranslatorAwareInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class LocaleManagerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        $config = isset($config['locale_manager']) ? $config['locale_manager'] : array();

        $localeManager = new LocaleManager();
        
        // Load the available locales
        if (isset($config['available_locales'])) {
            if (is_array($config['available_locales']) && !empty($config['available_locales'])) {
                $localeManager->addLocales($config['available_locales']);
            }
        }

        // Get the locale from the environment
        // This allows us to define a default locale per vhost
        $locale = getenv('LOCALE');
        if (empty($locale)) {
            // Get the default locale for the domain
            if (isset($config['domains'])) {
                if (is_array($config['domains']) && $serviceLocator->has('Request')) {
                    $request = $serviceLocator->get('Request');
                    if (method_exists($request, 'getUri')) {
                        $uri  = $request->getUri();
                        $host = $uri->getHost();
                        $hostParams = explode('.', $host);
                        $domainCheck = '';
                                   
                        while (null !== ($domainItem = array_pop($hostParams))) {
                            $domainCheck = $domainItem . (!empty($domainCheck) ? '.' : '') . $domainCheck;
                            if (isset($config['domains'][$domainCheck])) {
                                $locale = $config['domains'][$domainCheck];
                            }
                        }
                    }
                }
            }

            if (empty($locale)) {
                if (isset($config['locale'])) {
                    $locale = $config['locale'];
                } elseif ($serviceLocator->has('Translator')) {
                    $translator = $serviceLocator->get('Translator');
                    if (method_exists($translator, 'getLocale')) {
                        $locale = $translator->getLocale();
                    }
                }
            }

            if (empty($locale)) {
                $locale = \Locale::getDefault();
            }
        }

        $localeManager->addLocale( $locale );
        $localeManager->setDefaultLocale( $locale );
                
        // Add the default translator to the translators' list
        if ($serviceLocator->has('Translator')) {
            $translator = $serviceLocator->get('Translator');
            $localeManager->addTranslator($translator);
        }

        //if ($serviceLocator->has('Router')) {
        //    $router = $serviceLocator->get('Router');
        //    if ($router instanceof LocaleManagerAwareInterface) {
        //        $router->setLocaleManager($localeManager);
        //        $localeManager->addTranslator( $router->getTranslator() );
        //    } elseif ($router instanceof TranslatorAwareInterface) {
        //        $localeManager->addTranslator( $router->getTranslator() );
        //    }
        //}

        // Listeners
        if ($serviceLocator->has('Application')) {
            $application = $serviceLocator->get('Application');
            if ($application instanceof EventManagerAwareInterface) {
                $eventManager = $application->getEventManager();
                if ($eventManager instanceof EventManagerInterface) {
                    $this->attachDefaultListeners($eventManager);
                    $this->attachSharedListeners($eventManager, $localeManager);
                }
            }
        } elseif ($serviceLocator->has('EventManager')) {
            $eventManager = $serviceLocator->get('EventManager');
            if ($eventManager instanceof EventManagerInterface) {
                $this->attachDefaultListeners($eventManager);
                $this->attachSharedListeners($eventManager, $localeManager);
            }
        }

        return $localeManager;
    }

    protected function attachDefaultListeners(EventManagerInterface $events)
    {
        $listener = new LocaleRouteListener();
        $events->attach( $listener );

        $listener = new DispatchListener();
        $events->attach( $listener );
    }

    protected function attachSharedListeners(EventManagerInterface $events, LocaleManagerInterface $localeManager)
    {
        $sharedEvents = $events->getSharedManager();

        $listener = new ViewRenderListener();
        $listener->setLocaleManager($localeManager);
        $sharedEvents->attachAggregate($listener);
    }
}