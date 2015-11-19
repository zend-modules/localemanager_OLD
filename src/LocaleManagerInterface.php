<?php
/**
 * Locale Manager
 * 
 * @author Juan Pedro Gonzalez Gutierrez
 * @copyright Copyright (c) 2015 Juan Pedro Gonzalez Gutierrez
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GPL v3
 */

namespace LocaleManager;

interface LocaleManagerInterface
{
    /**
     * Add an available locale.
     * 
     * @param string $locale The Locale
     * @return LocaleManagerInterface
     */
    public function addLocale($locale);

    /**
     * Add available locales.
     * 
     * @param array $locales The Locales
     * @return LocaleManagerInterface
     */
    public function addLocales($locales);

    /**
     * Add a translator
     * 
     * @param $translator
     */
    public function addTranslator($translator);

    /**
     * Get all the available locales.
     * 
     * @return array
     */
    public function getAvailableLocales();

    /**
     * Get the default locale.
     * 
     * @return string The locale
     */
    public function getDefaultLocale();

    /**
     * Get the current locale.
     * 
     * @return string The locale
     */
    public function getLocale();

    /**
     * Check if the locale is available.
     * 
     * @param string $locale
     * @return bool
     */
    public function hasLocale($locale);

    /**
     * Check if the given locale is the default locale.
     *
     * @param string $locale
     * @return bool
     */
    public function isDefaultLocale($locale);

    /**
     * Set the default locale
     * 
     * @param string $locale The locale
     */
    public function setDefaultLocale($locale);

    /**
     * Set the current locale
     * 
     * @param string $locale The locale
     */
    public function setLocale($locale);
}