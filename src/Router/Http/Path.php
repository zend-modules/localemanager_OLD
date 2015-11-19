<?php
/**
 * Locale Manager
 * 
 * @author Juan Pedro Gonzalez Gutierrez
 * @copyright Copyright (c) 2015 Juan Pedro Gonzalez Gutierrez
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GPL v3
 */

namespace LocaleManager\Router\Http;

use LocaleManager\Listener\LocaleRouteListener;
use LocaleManager\LocaleManager;
use LocaleManager\LocaleManagerAwareInterface;
use LocaleManager\LocaleManagerInterface;
use Zend\Mvc\Router\Http\RouteInterface;
use Zend\Mvc\Router\Http\RouteMatch;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Stdlib\RequestInterface as Request;

class Path implements
    LocaleManagerAwareInterface,
    RouteInterface,
    ServiceLocatorAwareInterface
{
    /**
     * Default values.
     *
     * @var array
     */
    protected $defaults;

    /**
     * The locale manager.
     * 
     * @var LocaleManagerInterface
     */
    protected $localeManager = null;

    /**
     * The route plugin manager.
     * 
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator = null;

    public function __construct($defaults = array())
    {
        $this->defaults = $defaults;
    }

    /**
     * Get the locale manager.
     * 
     * @return LocaleManagerInterface|null
     */
    public function getLocaleManager()
    {
        return $this->localeManager;
    }

    /**
     * Set the locale manager.
     * 
     * @param LocaleManagerInterface $localeManager
     * @return Path
     */
    public function setLocaleManager(LocaleManagerInterface $localeManager)
    {
        $this->localeManager = $localeManager;
        return $this;
    }

    /**
     * Create a new route with given options.
     *
     * @param  array|\Traversable $options
     * @return void
     */
    public static function factory($options = array())
    {
        if (!isset($options['defaults'])) {
            $options['defaults'] = array();
        }

        return new self($options['defaults']);
    }

    /**
     * Match a given request.
     *
     * @param  Request $request
     * @return RouteMatch|null
     */
    public function match(Request $request, $pathOffset = null)
    {
        if (!method_exists($request, 'getUri')) {
            return;
        }

        $uri  = $request->getUri();
        $path = $uri->getPath();

        if ($pathOffset !== null) {
            $pathLength = strlen($path);

            if (($pathOffset < 0) || ($pathLength < $pathOffset)) {
                return;
            }
            
            if (strpos($path, '/', $pathOffset) !== $pathOffset) {
                return;
            }

            // Check if no locale was given and default should be used.
            if ($pathLength === $pathOffset + 1) {
                return new RouteMatch($this->defaults, 1);
            }
        } elseif ($path === '/') {
            return new RouteMatch($this->defaults, 1);
        } elseif (strpos($path, '/') !== 0) {
            return;
        }

        // The path starts with '/'; however, if we don't have a locale manager
        // we can not detect locales, so we can just reply the route has been
        // matched as '/'
        if (null === $this->localeManager) {
            return RouteMatch($this->defaults, 1);
        }

        // Grab the first path segment as it should be the locale.
        $startOffset = $pathOffset + 1;
        $endOffset   = strpos($path, '/', $startOffset);

        if ($endOffset !== false) {
            $localeLength = $endOffset - $startOffset;
        } else {
            $localeLength = strlen($path) - $startOffset;
        }

        // Locale minimum length is 2 characters
        if ($localeLength < 2) {
            return;
        }

        // Get the locale
        $detected = substr($path, $startOffset, $localeLength);

        if (!$this->localeManager->hasLocale($detected)) {
            // Locale manager does not contain that locale.
            // Either it is the default path or an error.
            // We return a match for '/'.
            return new RouteMatch($this->defaults, 1);
        }

        // Set the locale in the locale manager.
        // This is mainly used for error messages.
        $this->localeManager->setLocale($detected);

        // If no ending slash has been detected we must redirect
        if (!$endOffset) {
            // 301 Moved Permanently
            $uri->setPath($path . '/');
            header('HTTP/1.1 301 Moved Permanently', true);
            header('Location: ' . $uri, true, 301);
            exit();
        }

        // We have matched the route
        // The +2 takes care of the leading and trailing slashes.
        return new RouteMatch(array_merge($this->defaults, array(LocaleRouteListener::LOCALE => \Locale::canonicalize($detected))), strlen($detected) + 2);
    }

    /**
     * Assemble the route.
     *
     * @param  array $params
     * @param  array $options
     * @return mixed
     */
    public function assemble(array $params = array(), array $options = array())
    {
        if (null !== $this->localeManager) {
            $locale = isset($options['locale']) ? $options['locale'] : $this->localeManager->getLocale();
            $defaultLocale = $this->localeManager->getDefaultLocale();

            if (!$this->localeManager->isDefaultLocale($locale)) {
                return '/' . LocaleManager::standarizeLocale($locale) . '/';
            }

            $visible = isset($options['displayDefaultLocale']) ? $options['displayDefaultLocale'] : false;
            if ($visible) {
                return '/' . LocaleManager::standarizeLocale($locale) . '/';
            }
        }

        return '/';
    }

    /**
     * Get a list of parameters used while assembling.
     *
     * @return array
     */
    public function getAssembledParams()
    {
        return array();
    }

    /**
     * Set service locator
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return Path
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;

        // try to get the service manager
        $serviceManager = null;
        if ($serviceLocator instanceof \Zend\Mvc\Router\RoutePluginManager) {
            if ($serviceLocator->has('LocaleManager')) {
                $this->setLocaleManager( $serviceLocator->get('LocaleManager') );
            } else {
                $serviceManager = $serviceLocator->getServiceLocator();
                if ($serviceManager instanceof ServiceLocatorInterface) {
                    if ($serviceManager->has('LocaleManager')) {
                        $this->setLocaleManager( $serviceManager->get('LocaleManager') );
                    }
                }
            }
        } elseif ($serviceLocator instanceof ServiceLocatorInterface) {
            if ($serviceLocator->has('LocaleManager')) {
                $this->setLocaleManager( $serviceLocator->get('LocaleManager') );
            }
        }

        return $this;
    }

    /**
     * Get service locator
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }
}