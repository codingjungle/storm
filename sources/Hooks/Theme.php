<?php

namespace IPS;

use InvalidArgumentException;
use IPS\Dispatcher;
use IPS\IPS;
use IPS\storm\Application;
use IPS\storm\ThemeHooks\CoreAdminGlobalGlobalTemplate;
use OutOfRangeException;
use RuntimeException;
use Wa72\HtmlPageDom\HtmlPage;

class Theme extends \IPS\_Theme
{
    //theme_<app>_<templateLocation>_<templateGroup>_<templateName>
    protected static array $themeHooks = [
        'theme_core_admin_global_globalTemplate' => [
            [
                'type' => 'after',
                'selector' => '#acpMainLayout',
                'class' => CoreAdminGlobalGlobalTemplate::class
            ]
        ]
    ];

    protected static array $themeOverrides = [
        'theme_core_global_global_includeCSS' =>  \IPS\ROOT_PATH .
            '/applications/storm/sources/ThemeOverrides/includeCss.php',
        'theme_core_global_global_includeJS' =>  \IPS\ROOT_PATH .
            '/applications/storm/sources/ThemeOverrides/includeJS.php',
        'theme_core_front_global_queryLog' =>  \IPS\ROOT_PATH .
            '/applications/storm/sources/ThemeOverrides/queryLog.php',
    ];

    public static function mutate(string $templateString, array $hookData)
    {
        Application::initAutoloader();
        /* Encode any {{PHP code}}, {$var}s and {tag=""} tags to stop phpQuery encoding it */
        $phpQueryI = 0;
        $phpQueryStore = [];
        $jsonAttrI = 0;
        $jsonAttrStore = [];
        $pseudoSelectors = [
            'active',
            'checked',
            'disabled',
            'empty',
            'enabled',
            'first-child',
            'first-of-type',
            'focus',
            'hover',
            'in-range',
            'invalid',
            'lang',
            'last-child',
            'last-of-type',
            'link',
            'eq',
            'not',
            'nth-child',
            'nth-last-child',
            'nth-last-of-type',
            'only-of-type',
            'only-child',
            'optional',
            'out-of-range',
            'read-only',
            'read-write',
            'required',
            'root',
            'target',
            'valid',
            'visited',
            'after',
            'before',
            'first-letter',
            'first-line',
            'selection'
        ];

        /* We sometimes need to use single quotes as the data attr contains json */
        $content = preg_replace_callback(
            "/([\d\w0-9-]+?)='\{([^']+?)\|raw\}'/",
            function ($matches) use (&$jsonAttrI, &$jsonAttrStore) {
                $jsonAttrStore[ ++$jsonAttrI ] = $matches;
                return $matches[1] . '="json--' . $jsonAttrI . '--"';
            },
            $templateString
        );

        /* Remove raw JS as this can cause a timeout if there is a lot of it */
        $content = preg_replace_callback(
            '#<script\b[^>]*>([\s\S]*?)<\/script>#',
            function ($matches) use (&$phpQueryI, &$phpQueryStore) {
                $phpQueryStore[ ++$phpQueryI ] = $matches[0];
                return 'he-' . $phpQueryI . '--';
            },
            $content
        );

        $content = preg_replace_callback(
            [ '/{{?(?>[^{}]|(?R))*}?}/', '/\{([a-z]+?=([\'"]).+?\\2 ?+)}/' ],
            function ($matches) use (&$phpQueryI, &$phpQueryStore) {
                $phpQueryStore[ ++$phpQueryI ] = $matches[0];
                return 'he-' . $phpQueryI . '--';
            },
            $content
        );

        /* Make custom classes like ipsPadding_top:half selectable by PQ */
        preg_match_all('#class\s?=\s?[\'"]([^\'"]+?)[\'"]#', $content, $classes, PREG_SET_ORDER);

        foreach ($classes as $data) {
            $cleaned = preg_replace_callback(
                "/([\d\w0-9-]+?):(.+?)(\.|\s|\(|$)/",
                function ($matches) use ($pseudoSelectors) {
                    foreach ($pseudoSelectors as $selector) {
                        if ($matches[2] == $selector) {
                            /* It's a pseudo selector */
                            return $matches[0];
                        }
                    }

                /* Still here? */
                    return str_replace(':', '---cln---', $matches[0]);
                },
                $data[1]
            );

            $content = str_replace($data[1], $cleaned, $content);
        }

        /* Swap out certain tags that confuse phpQuery */
        $content = preg_replace('/<(\/)?(html|head|body)(>| (.+?))/', '<$1temp$2$3', $content);
        $content = str_replace('<!DOCTYPE html>', '<tempdoctype></tempdoctype>', $content);

        $domCrawler = new HtmlPage('<div id="ipsContentMutationsNow">' . $content . "</div>");
        //libxml_use_internal_errors(true);

        /* Loop through all the hooks on this template bit */
        foreach ($hookData as $hook) {
            $hookContent = null;
            if (isset($hook['class'])) {
                $c = $hook['class'];
                $cc = new $c();
                $hookContent = $cc->content();
            } elseif (isset($hook['content'])) {
                $hookContent = $hook['content'];
            }

            /* Temporarily adjust class names with : in it that are not also psuedo-selectors */
            if (isset($hook['selector'])) {
                $hook['selector'] = preg_replace_callback(
                    "/([\d\w0-9-]+?):(.+?)(\.|\s|\(|$)/",
                    function ($matches) use ($pseudoSelectors) {
                        foreach ($pseudoSelectors as $selector) {
                            if ($matches[2] == $selector) {
                                /* It's a pseudo selector */
                                return $matches[0];
                            }
                        }

                    /* Still here? */
                        return str_replace(':', '---cln---', $matches[0]);
                    },
                    $hook['selector']
                );
            }

            /* Encode */
            if (isset($hookContent)) {
                $hookContent = preg_replace_callback(
                    ['/{{?.+?}?}/', '/\{([a-z]+?=([\'"]).+?\\2 ?+)}/' ],
                    function ($matches) use (&$phpQueryI, &$phpQueryStore) {
                        $phpQueryStore[ ++$phpQueryI ] = $matches[0];
                        return 'he-' . $phpQueryI . '--';
                    },
                    $hookContent
                );
            }

            $hook['selector'] = preg_replace_callback(
                '/\[([^\s\/<>\'"=]+)(=("[^"&]*"|\'[^\'&]*\'|[^\s=\'"<>`]*))?\]/i',
                function ($matches) {
                    return '[' . mb_strtolower($matches[1]) . ( isset($matches[2]) ? $matches[2] : '' ) . ']';
                },
                $hook['selector']
            );

            /* Do stuff */
            $results = $domCrawler->filter(preg_replace('/\b(html|head|body)\b/', 'temp$1', $hook['selector']));

            switch ($hook['type']) {
                case 'add_before':
                    $results->before($hook['content']);
                    break;

                case 'add_inside_start':
                    $results->prepend($hook['content']);
                    break;

                case 'append':
                    $results->append($hookContent);
                    break;

                case 'after':
                    $results->after($hookContent);
                    break;

                case 'add_class':
                    foreach ($hook['css_classes'] as $cssClass) {
                        $results->addClass($cssClass);
                    }
                    break;

                case 'remove_class':
                    foreach ($hook['css_classes'] as $cssClass) {
                        $results->removeClass($cssClass);
                    }
                    break;

                case 'add_attribute':
                    foreach ($hook['attributes_add'] as $attribute) {
                        $results->attr($attribute['key'], $attribute['value']);
                    }
                    break;

                case 'remove_attribute':
                    foreach ($hook['attributes_remove'] as $attr) {
                        $results->removeAttr($attr);
                    }
                    break;

                case 'replace':
                    $results->replaceWith($hook['content']);
                    break;
            }
        }

        $return =  $domCrawler->filter('#ipsContentMutationsNow')->html();
        //file_put_contents('/Volumes/Storagecus/Sites/ips5/foo.html', $return);
        /* Put our single quoted data back */
        foreach ($jsonAttrStore as $id => $matches) {
            $return = preg_replace('#' . $matches[1] . '="json--' . $id . '--"#', $matches[0], $return);
        }

        /* Fix ---cln--- classes */
        preg_match_all('#class\s?=\s?[\'"]([^\'"]+?)[\'"]#', $return, $classes, PREG_SET_ORDER);

        foreach ($classes as $idx => $data) {
            $return = str_replace($data[1], str_replace('---cln---', ':', $data[1]), $return);
        }

        /* Put our {{PHP code}} back */
        $return = preg_replace_callback('/he-(.+?)--/', function ($matches) use ($phpQueryStore) {
            return isset($phpQueryStore[ $matches[1] ]) ? $phpQueryStore[ $matches[1] ] : '';
        }, $return);

        /* Swap back certain tags that confuse phpQuery */
        $return = preg_replace('/<(\/)?temp(html|head|body)(.*?)>/', '<$1$2$3>', $return);
        $return = str_replace('<tempdoctype></tempdoctype>', '<!DOCTYPE html>', $return);

        //file_put_contents('/Volumes/Storagecus/Sites/ips5/foo2.html', $return);
        /* Return */
        return $return;
    }

    public static function makeProcessFunction(string $content, string $functionName, string $params = '', bool $isHTML = true, bool $isCSS = false): void
    {
        if (isset(static::$themeHooks[$functionName])) {
            $content = static::mutate($content, static::$themeHooks[$functionName]);
        }
        if (isset(static::$themeOverrides[$functionName])) {
            $ogContent = $content;
            $ogFunction = static::compileTemplate($ogContent, $functionName . '_original', $params, $isHTML, $isCSS);
            static::runProcessFunction($ogFunction, $functionName . '_original');

            $content = include static::$themeOverrides[$functionName];
            $compiled = <<<eof
			function {$functionName}( {$params} ) { 
				{$content} 
			}
eof;
        } else {
            $compiled = static::compileTemplate($content, $functionName, $params, $isHTML, $isCSS);
        }

        static::runProcessFunction($compiled, $functionName);
    }
}
