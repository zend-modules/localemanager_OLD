<?php
return array(
    'locale_manager' => array(
        /**
         * The default locale
         */
        'locale' => 'fr_FR',
        /**
         * Available locales
         */
        'available_locales' => array(
            'ar_JO', 'ar_SY', 'cs_CZ', 'de_DE', 'en_US', 'es_ES', 
            'fr_CA', 'fr_FR', 'it_IT', 'ja_JP', 'nb_NO', 'nl_NL', 
            'pl_PL', 'pt_BR', 'ru_RU', 'sk_SK', 'sl_SI', 'tr_TR', 
            'uk_UA', 'vi_VN', 'zh_CN', 'zh_TW'
        ),
        /**
         * Default locales for domains
         * 
         * You may also set the environment variable "LOCALE" 
         * to the appropiate value in the Virtual Host which is
         * preffered for perfomance. But, in case it is imposible,
         * then you may do it here.
         */
        'domains' => array(
            'localhost'       => 'it_IT',
            'yourhost.es'     => 'es_ES',
            'yourhost.com'    => 'en_US',
            'es.yourhost.com' => 'es_ES',
        ),
    ),
    'router' => array(
        'router_class' => 'LocaleManager\Router\Http\TreeRouteStack',
    ),
    'service_manager' => array(
        'factories' => array(
            'LocaleManager' => 'LocaleManager\Service\LocaleManagerFactory',
        )
    ),
    'view_helpers' => array(
        'invokables'=> array(
            'language' => 'LocaleManager\View\Helper\Language'  
        )
    ),
);