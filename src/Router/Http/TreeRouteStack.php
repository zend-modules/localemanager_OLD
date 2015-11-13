<?php
/**
 * Locale Manager
 * 
 * @author Juan Pedro Gonzalez Gutierrez
 * @copyright Copyright (c) 2015 Juan Pedro Gonzalez Gutierrez
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GPL v3
 */

namespace LocaleManager\Router\Http;

use LocaleManager\LocaleManager;

use Zend\Log\Formatter\Base;

use LocaleManager\Exception;
use LocaleManager\LocaleManagerAwareInterface;
use LocaleManager\LocaleManagerInterface;
use Zend\I18n\Translator\TranslatorAwareInterface;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\Mvc\Router\Http\RouteMatch;
use Zend\Mvc\Router\Http\TreeRouteStack as BaseTreeRouteStack;
use Zend\Stdlib\RequestInterface as Request;
use Zend\Uri\Http as HttpUri;

class TreeRouteStack extends BaseTreeRouteStack implements LocaleManagerAwareInterface, TranslatorAwareInterface
{
    /**
     * The locale manager.
     * 
     * @var LocaleManagerInterface
     */
    protected $localeManager = null;

    /**
     * The locale found in the request path (if any)
     *
     * @var string|null
     */
    protected $pathLocale = null;

    /**
     * Translator used for translatable segments.
     *
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * Whether the translator is enabled.
     *
     * @var bool
     */
    protected $translatorEnabled = true;

    /**
     * Translator text domain to use.
     *
     * @var string
     */
    protected $translatorTextDomain = 'default';

    protected function redirectToUri($uri)
    {
        // HTML code for redirect
        $html  = "<!DOCTYPE HTML>";
        $html .= "<html>";
        $html .= "<head lang=\"en\">";
        $html .= "<meta charset=\"UTF-8\">";
        $html .= "<meta http-equiv=\"refresh\" content=\"1; url=" . $uri . "\" />";
        $html .= "<script>";
        $html .= "window.location.href = \"" . $uri . "\"";
        $html .= "</script>";
        $html .= "<title>Moved Permanently</title>";
        $html .= "</head>";
        $html .= "<body>";
        $html .= "<p>This page has moved and you’ll be automatically redirected to the new location.</p>";
        $html .= "<p>If you are not redirected automatically, follow the <a href=\"" . $uri . "\">link</a>.</p>";
        $html .= "</body>";
        $html .=" </html>";

        if (!headers_sent()) {
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: ' . $uri);
            header('Content-Length: ' . strlen($html));
            
            echo $html;
        } else {
            throw new Exception\RuntimeException('Unable to redirect as headers have been sent.');
        }

        exit();
    }

    protected function detectLocalePath(Request $request, $pathOffset = null)
    {
        if (null === $this->localeManager) {
            return;
        }

        if (!method_exists($request, 'getUri')) {
            return;
        }

        $uri  = $request->getUri();
        $path = $uri->getPath();

        if (null !== $pathOffset) {
            $relativePath = substr($path, $pathOffset);
        } else {
            $relativePath = ·path;
        }

        $slash = substr($relativePath, 0, 1);
        if ($slash !== '/') {
            return;
        }
        
        $nextSlash = strpos($relativePath, '/', 1);
        if ($nextSlash === false) {
            $locale = substr($relativePath, 1, strlen($relativePath) - 1);
        } else {
            $locale = substr($relativePath, 1, $nextSlash - 1);
        }

        if (!$this->localeManager->hasLocale($locale)) {
            return;
        }

        // Validate the format
        $language = \Locale::getPrimaryLanguage($locale);
        $region   = \Locale::getRegion($locale);
        $expected = $language . (!empty($region) ? '-' . $region : '');
        if ($locale !== $expected) {
            return;
        }

        $this->pathLocale = \Locale::canonicalize($locale);
        $this->localeManager->setLocale($locale);

        if ($nextSlash === false) {
            // Make a redirect
            $toUri = clone $uri;
            $toUri->setPath( $uri->getPath() . '/');
            $this->redirectToUri($toUri);
        }

        if ($this->hasTranslator()) {
            if (method_exists($this->translator, 'setLocale')) {
                $this->translator->setLocale($locale);
            }
        }

        return strlen($locale) + 1;
    }

    /**
     * match(): defined by \Zend\Mvc\Router\RouteInterface
     *
     * @see    \Zend\Mvc\Router\RouteInterface::match()
     * @param  Request      $request
     * @param  integer|null $pathOffset
     * @param  array        $options
     * @return RouteMatch|null
     */
    public function match(Request $request, $pathOffset = null, array $options = array())
    {
        if (!method_exists($request, 'getUri')) {
            return;
        }

        if ($this->baseUrl === null && method_exists($request, 'getBaseUrl')) {
            $this->setBaseUrl($request->getBaseUrl());
        }

        $uri           = $request->getUri();
        $baseUrlLength = strlen($this->baseUrl) ?: null;

        if ($pathOffset !== null) {
            $baseUrlLength += $pathOffset;
        }

        if ($this->requestUri === null) {
            $this->setRequestUri($uri);
        }

        if (null !== $this->localeManager) {
            // Detect locale in path
            if (method_exists($uri, 'getPath')) {
                $localePathLength = $this->detectLocalePath($request, $baseUrlLength);
                if (null !== $localePathLength) {
                    $baseUrlLength += $localePathLength;
                }
            }
        }

        if ($baseUrlLength !== null) {
            $pathLength = strlen($uri->getPath()) - $baseUrlLength;
        } else {
            $pathLength = null;
        }

        if ($this->hasTranslator() && $this->isTranslatorEnabled() && !isset($options['translator'])) {
            $options['translator'] = $this->getTranslator();
        }

        if (!isset($options['text_domain'])) {
            $options['text_domain'] = $this->getTranslatorTextDomain();
        }

        foreach ($this->routes as $name => $route) {
            if (
                ($match = $route->match($request, $baseUrlLength, $options)) instanceof RouteMatch
                && ($pathLength === null || $match->getLength() === $pathLength)
            ) {
                $match->setMatchedRouteName($name);

                foreach ($this->defaultParams as $paramName => $value) {
                    if ($match->getParam($paramName) === null) {
                        $match->setParam($paramName, $value);
                    }
                }

                return $match;
            }
        }

        return;
    }

    /**
     * assemble(): defined by \Zend\Mvc\Router\RouteInterface interface.
     *
     * @see    \Zend\Mvc\Router\RouteInterface::assemble()
     * @param  array $params
     * @param  array $options
     * @return mixed
     * @throws \Zend\Mvc\Router\Exception\InvalidArgumentException
     * @throws \Zend\Mvc\Router\Exception\RuntimeException
     */
    public function assemble(array $params = array(), array $options = array())
    {
        if (!isset($options['name'])) {
            throw new Exception\InvalidArgumentException('Missing "name" option');
        }

        $names = explode('/', $options['name'], 2);
        $route = $this->routes->get($names[0]);

        if (!$route) {
            throw new Exception\RuntimeException(sprintf('Route with name "%s" not found', $names[0]));
        }

        if (isset($names[1])) {
            if (!$route instanceof BaseTreeRouteStack) {
                throw new Exception\RuntimeException(sprintf('Route with name "%s" does not have child routes', $names[0]));
            }
            $options['name'] = $names[1];
        } else {
            unset($options['name']);
        }

        if (!isset($options['uri'])) {
            $uri = new HttpUri();

            if (isset($options['force_canonical']) && $options['force_canonical']) {
                if ($this->requestUri === null) {
                    throw new Exception\RuntimeException('Request URI has not been set');
                }

                $uri->setScheme($this->requestUri->getScheme())
                    ->setHost($this->requestUri->getHost())
                    ->setPort($this->requestUri->getPort());
            }

            $options['uri'] = $uri;
        } else {
            $uri = $options['uri'];
        }

        if ($this->hasTranslator() && $this->isTranslatorEnabled() && !isset($options['translator'])) {
            $options['translator'] = $this->getTranslator();
        }

        if (!isset($options['text_domain'])) {
            $options['text_domain'] = $this->getTranslatorTextDomain();
        }

        if (!isset($options['locale']) && (null !== $this->localeManager)) {
            $options['locale'] = $this->localeManager->getLocale();
        }

        if (!isset($options['displayDefaultLocale'])) {
            $options['displayDefaultLocale'] = false;
        }

        // Set path locale
        if (null !== $this->localeManager) {
            $locale        = (isset($options['locale']) ? $options['locale'] : $this->localeManager->getLocale());
            $defaultLocale = $this->localeManager->getDefaultLocale();

            if (strcasecmp($locale, $this->pathLocale) === 0) {
                $options['displayDefaultLocale'] = true;
            }

            if ((strcasecmp($locale, $defaultLocale) !== 0) || $options['displayDefaultLocale']) {
                // The locale is not the default locale
                $language = \Locale::getPrimaryLanguage($locale);
                $region   = \Locale::getRegion($locale);
                $locale   = $language . (!empty($region) ? '-' . $region : '');
        
                $path = $this->baseUrl . '/' . $locale . $route->assemble(array_merge($this->defaultParams, $params), $options);
            } else {
                $path = $this->baseUrl . $route->assemble(array_merge($this->defaultParams, $params), $options);
            }
        } else {
            $path = $this->baseUrl . $route->assemble(array_merge($this->defaultParams, $params), $options);
        }

        if (isset($options['query'])) {
            $uri->setQuery($options['query']);
        }

        if (isset($options['fragment'])) {
            $uri->setFragment($options['fragment']);
        }

        if ((isset($options['force_canonical']) && $options['force_canonical']) || $uri->getHost() !== null || $uri->getScheme() !== null) {
            if (($uri->getHost() === null || $uri->getScheme() === null) && $this->requestUri === null) {
                throw new Exception\RuntimeException('Request URI has not been set');
            }

            if ($uri->getHost() === null) {
                $uri->setHost($this->requestUri->getHost());
            }

            if ($uri->getScheme() === null) {
                $uri->setScheme($this->requestUri->getScheme());
            }

            $uri->setPath($path);

            if (!isset($options['normalize_path']) || $options['normalize_path']) {
                $uri->normalize();
            }

            return $uri->toString();
        } elseif (!$uri->isAbsolute() && $uri->isValidRelative()) {
            $uri->setPath($path);

            if (!isset($options['normalize_path']) || $options['normalize_path']) {
                $uri->normalize();
            }

            return $uri->toString();
        }

        return $path;
    }

    /**
     * Sets translator to use in helper
     *
     * @param  TranslatorInterface $translator  [optional] translator.
     *                                           Default is null, which sets no translator.
     * @param  string              $textDomain  [optional] text domain
     *                                           Default is null, which skips setTranslatorTextDomain
     * @return TreeRouteStack
     */
    public function setTranslator(TranslatorInterface $translator = null, $textDomain = null)
    {
        $this->translator = $translator;

        if ($textDomain !== null) {
            $this->setTranslatorTextDomain($textDomain);
        }

        return $this;
    }

    /**
     * Returns translator used in object
     *
     * @return TranslatorInterface|null
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * Checks if the object has a translator
     *
     * @return bool
     */
    public function hasTranslator()
    {
        return $this->translator !== null;
    }

    /**
     * Sets whether translator is enabled and should be used
     *
     * @param  bool $enabled [optional] whether translator should be used.
     *                       Default is true.
     * @return TreeRouteStack
     */
    public function setTranslatorEnabled($enabled = true)
    {
        $this->translatorEnabled = $enabled;
        return $this;
    }

    /**
     * Returns whether translator is enabled and should be used
     *
     * @return bool
     */
    public function isTranslatorEnabled()
    {
        if (!$this->hasTranslator()) {
            return false;
        }

        return $this->translatorEnabled;
    }

    /**
     * Set translation text domain
     *
     * @param  string $textDomain
     * @return TranslatorAwareInterface
     */
    public function setTranslatorTextDomain($textDomain = 'default')
    {
        $this->translatorTextDomain = $textDomain;

        return $this;
    }

    /**
     * Return the translation text domain
     *
     * @return string
     */
    public function getTranslatorTextDomain()
    {
        return $this->translatorTextDomain;
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
     * @return TreeRouteStack
     */
    public function setLocaleManager(LocaleManagerInterface $localeManager)
    {
        $this->localeManager = $localeManager;
        return $this;
    }
}