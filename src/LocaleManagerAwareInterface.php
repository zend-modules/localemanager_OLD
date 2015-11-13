<?php
/**
 * Locale Manager
 * 
 * @author Juan Pedro Gonzalez Gutierrez
 * @copyright Copyright (c) 2015 Juan Pedro Gonzalez Gutierrez
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GPL v3
 */

namespace LocaleManager;

interface LocaleManagerAwareInterface
{
    /**
     * Get the locale manager.
     * 
     * @return LocaleManagerInterface|null
     */
    public function getLocaleManager();

    /**
     * Set the locale manager.
     * 
     * @param LocaleManagerInterface $localeManager
     * @return LocaleManagerAwareInterface
     */
    public function setLocaleManager(LocaleManagerInterface $localeManager);
}