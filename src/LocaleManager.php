<?php
/**
 * Locale Manager
 * 
 * @author Juan Pedro Gonzalez Gutierrez
 * @copyright Copyright (c) 2015 Juan Pedro Gonzalez Gutierrez
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GPL v3
 */

namespace LocaleManager;

class LocaleManager implements LocaleManagerInterface
{
    /**
     * The available locales.
     * 
     * @var array
     */
    protected $availableLocales = array();

    /**
     * The current locale.
     * 
     * @var string
     */
    protected $locale = null;

    /**
     * The default locale.
     * 
     * @var string
     */
    protected $defaultLocale = null;

    /**
     * Translators
     * 
     * @var array
     */
    protected $translators = array();

    /**
     * Add an available locale.
     * 
     * @param string $locale The Locale
     * @return LocaleManagerInterface
     */
    public function addLocale($locale)
    {
        $locale = \Locale::canonicalize($locale);
        if (in_array($locale, $this->availableLocales)) {
            return $this;
        }

        // Get the available locales in the ICU library.
        $icuLocales = \ResourceBundle::getLocales(null);
        if (!in_array($locale, $icuLocales)) {
            $icuLocale = \Locale::lookup($icuLocales, $locale, true, null);
            if (null === $icuLocale) {
                throw new Exception\InvalidArgumentException(sprintf(
                    'The locale %s is not available in the ICU library',
                    $locale
                ));
            }
        }

        $this->availableLocales[] = $locale;
        return $this;
    }

    /**
     * Add available locales.
     * 
     * @param array $locales The Locales
     * @return LocaleManagerInterface
     */
    public function addLocales($locales)
    {
        // Get the available locales in the ICU library.
        $icuLocales = \ResourceBundle::getLocales(null);

        foreach ($locales as $locale) {
            $locale = \Locale::canonicalize($locale);
            if (!in_array($locale, $this->availableLocales)) {
                if (in_array($locale, $icuLocales)) {
                    $this->availableLocales[] = $locale;
                } else {
                    $icuLocale = \Locale::lookup($icuLocales, $locale, true, null);
                    if (null === $icuLocale) {
                        throw new Exception\InvalidArgumentException(sprintf(
                            'The locale %s is not available in the ICU library',
                            $locale
                        ));
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Add a translator
     * 
     * @param $translator
     */
    public function addTranslator($translator)
    {
        if (null === $translator) {
            return;
        }

        // TODO: Validate
        $this->translators[] = $translator;
        return $this;
    }

    /**
     * Get all the available locales.
     * 
     * @return array
     */
    public function getAvailableLocales()
    {
        return $this->availableLocales;
    }

    /**
     * Get the default locale.
     * 
     * @return string The locale
     */
    public function getDefaultLocale()
    {
        if (null === $this->defaultLocale) {
            $this->defaultLocale = \Locale::getDefault();
        }
        return $this->defaultLocale;
    }

    /**
     * Get the current locale.
     * 
     * @return string The locale
     */
    public function getLocale()
    {
        if (null === $this->locale) {
            $this->setLocale( $this->getDefaultLocale() );
        }

        return $this->locale;
    }

    /**
     * Check if the locale is available.
     * 
     * @param string $locale
     * @return bool
     */
    public function hasLocale($locale)
    {
        $locale = \Locale::canonicalize($locale);
        return in_array($locale, $this->availableLocales);
    }

    /**
     * Set the default locale
     * 
     * @param string $locale The locale
     */
    public function setDefaultLocale($locale)
    {
        $locale    = \Locale::canonicalize($locale);
        $icuLocale = $locale;
        
        // Get the available locales in the ICU library.
        $icuLocales = \ResourceBundle::getLocales(null);
        if (!in_array($icuLocale, $icuLocales)) {
            $icuLocale = \Locale::lookup($icuLocales, $icuLocale, true, null);
            if (null === $icuLocale) {
                throw new Exception\InvalidArgumentException(sprintf(
                    'The locale %s is not available in the ICU library',
                    $locale
                ));
            }
        }

        // Update translators
        foreach ($this->translators as $translator) {
            $translator->setFallbackLocale($locale);
        }

        // Set the locale
        $this->defaultLocale = $locale;
        return $this;
    }

    /**
     * Set the current locale
     * 
     * @param string $locale The locale
     */
    public function setLocale($locale)
    {
        $locale    = \Locale::canonicalize($locale);
        $icuLocale = $locale;
        
        // Get the available locales in the ICU library.
        $icuLocales = \ResourceBundle::getLocales(null);
        if (!in_array($icuLocale, $icuLocales)) {
            $icuLocale = \Locale::lookup($icuLocales, $icuLocale, true, null);
            if (null === $icuLocale) {
                throw new Exception\InvalidArgumentException(sprintf(
                    'The locale %s is not available in the ICU library',
                    $locale
                ));
            }
        }

        // Set the locale
        $language = \Locale::getPrimaryLanguage($icuLocale);
        $lcLocales = array(
            $icuLocale . '.UTF-8',
            $language . '.UTF-8',
            $icuLocale,
            $language,
            \Locale::getDisplayLanguage($icuLocale, 'en')
        );

        $setLocale = setlocale(LC_ALL, $lcLocales);
        if (!$setLocale) {
            throw new Exception\InvalidArgumentException(sprintf(
                'The locale %s is not available in the system',
                $locale
            ));
        }

        // Update translators
        foreach ($this->translators as $translator) {
            $translator->setLocale($locale);
        }

        // Set the locale
        $this->locale = $locale;
        return $this;
    }
}