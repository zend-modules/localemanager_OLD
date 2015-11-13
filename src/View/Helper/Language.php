<?php
/**
 * Locale Manager
 * 
 * @author Juan Pedro Gonzalez Gutierrez
 * @copyright Copyright (c) 2015 Juan Pedro Gonzalez Gutierrez
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GPL v3
 */

namespace LocaleManager\View\Helper;

use Zend\View\Helper\AbstractHelper;

class Language extends AbstractHelper
{
    /**
     * The locale manager.
     * 
     * @var LocaleManagerInterface
     */
    protected $locale = null;

    /**
     * Retrieve the current language.
     * 
     * @param bool $includeRegion
     * @return string
     */
    public function __invoke($includeRegion = false)
    {
        if (null === $this->locale) {
            $this->locale = \Locale::getDefault();
        }

        $language = \Locale::getPrimaryLanguage($this->locale);

        if ($includeRegion) {
            $region = \Locale::getRegion($this->locale);
            if (!empty($region)) {
                $language = $language . '-' . $region;
            }   
        }

        return $language;
    }

    /**
     * Set the locale.
     * 
     * @param string $locale
     * @return Language
     */
    public function setLocale($locale) 
    {
        $this->locale = \Locale::canonicalize($locale);
        return $this;
    }
}