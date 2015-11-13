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
use Zend\EventManager\SharedEventManagerInterface;
use Zend\EventManager\SharedListenerAggregateInterface;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\ViewEvent;

class ViewRenderListener implements SharedListenerAggregateInterface
{
    /**
     * @var \Zend\Stdlib\CallbackHandler[]
     */
    protected $listeners = array();

    /**
     * @var LocaleManagerInterface
     */
    protected $localeManager = null;

    /**
     * Attach one or more listeners
     *
     * Implementors may add an optional $priority argument; the SharedEventManager
     * implementation will pass this to the aggregate.
     *
     * @param SharedEventManagerInterface $events
     */
    public function attachShared(SharedEventManagerInterface $events)
    {
        $this->listeners[] = $events->attach('Zend\View\View', ViewEvent::EVENT_RENDERER_POST, array($this, 'onRendererPost'));
    }

    /**
     * Detach all previously attached listeners
     *
     * @param SharedEventManagerInterface $events
     */
    public function detachShared(SharedEventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach('Zend\View\View', $listener)) {
                unset($this->listeners[$index]);
            }
        }
    }

    /**
     * Listen to the "renderer.post" event
     *
     * @param  MvcEvent $e
     * @return mixed
     */
    public function onRendererPost(ViewEvent $event)
    {
        $renderer = $event->getRenderer();
        if ($renderer instanceof PhpRenderer) {
            if (null !== $this->localeManager) {
                $renderer->plugin("language")->setLocale($this->localeManager->getLocale());
            }
        }
    }

    /**
     * Set the locale manager.
     * 
     * @param LocaleManagerInterface $localeManager
     * @return self
     */
    public function setLocaleManager(LocaleManagerInterface $localeManager)
    {
        $this->localeManager = $localeManager;
        return $this;
    }
}