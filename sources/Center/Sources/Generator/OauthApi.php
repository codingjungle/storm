<?php

/**
 * @brief       ActiveRecord Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev storm: Dev Center Plus
 * @since       1.0.0
 * @version     -storm_version-
 */

namespace IPS\storm\Center\Sources\Generator;

use ClassGenerator;
use Exception;
use IPS\Http\Response;
use IPS\Http\Url;
use IPS\Patterns\Singleton;
use IPS\storm\Api\ApiException;
use IPS\storm\Api\Oauth;
use Laminas\Code\Reflection\ClassReflection;
use Throwable;

use function class_exists;
use function ltrim;
use function rtrim;
use function trim;


class OauthApi extends GeneratorAbstract
{
    protected bool $includeConstructor = false;

    /**
     * @inheritdoc
     */
    protected function bodyGenerator()
    {
        $ns = '\\IPS\\' . $this->application->directory . '\\Api';
        $check = $ns . '\\ApiException';
        if (!class_exists($check)) {
            $code = (new ClassReflection(ApiException::class))->getParentClass();
            $content = $code->getContents(false);
            $content = trim($content);
            $content = ltrim($content, '{');
            $content = rtrim($content, "}");
            $content = trim($content);
            $gen = new ClassGenerator();
            $gen->addHeaderCatch();
            $gen->addClassBody("\n    " . $content);
            $gen->addImport(Exception::class);
            $gen->addImport(Throwable::class);
            $gen->addImport(\IPS\Member::class);
            $gen->addExtends(Exception::class);
            $gen->addMixin($check);
            $dir = $this->application->getApplicationPath() . '/sources/Api/';
            $gen->addPath($dir);
            $gen->isProxy = false;
            $doc = [
                '@brief      ApiException Class',
                '@author     -storm_author-',
                '@copyright  -storm_copyright-',
                '@package    IPS Social Suite',
                '@subpackage ' . $this->app,
                '@since      ' . $this->application->version ?? '1.0.0',
                '@version    -storm_version-',
            ];
            $gen->setDocumentComment($doc);
            $gen->addClassComments(['ApiException Class']);
            $gen->addClassName('_ApiException');
            $gen->addFileName('ApiException');
            $gen->addNameSpace('IPS\\' . $this->application->directory . '\\Api');
            $gen->save();
        }
        $check = $ns . '\\Oauth';
        if (!class_exists($check)) {
            $code = (new ClassReflection(Oauth::class))->getParentClass();
            $content = $code->getContents(false);
            $content = trim($content);
            $content = ltrim($content, '{');
            $content = rtrim($content, "}");
            $content = trim($content);
            $gen = new ClassGenerator();
            $gen->addClassBody("\n    " . $content);
            $gen->addImport(Exception::class);
            $gen->addImport(Url::class);
            $gen->addImport(Response::class);
            $gen->addImport(Singleton::class);
            $gen->addImportFunction('json_encode');
            $gen->addHeaderCatch();
            $gen->addExtends(Singleton::class);
            $gen->addMixin($check);
            $dir = $this->application->getApplicationPath() . '/sources/Api/';
            $gen->addPath($dir);
            $gen->isProxy = false;
            $doc = [
                '@brief      Oauth Class',
                '@author     -storm_author-',
                '@copyright  -storm_copyright-',
                '@package    IPS Social Suite',
                '@subpackage ' . $this->app,
                '@since      ' . $this->application->version ?? '1.0.0',
                '@version    -storm_version-',
            ];

            $gen->setDocumentComment($doc);
            $gen->addClassComment(['Oauth Class'], true);
            $gen->addClassName('_Oauth');
            $gen->addFileName('Oauth');
            $gen->addNameSpace('IPS\\' . $this->application->directory . '\\Api');
            $gen->makeAbstract();
            $gen->save();
        }
        $body = <<<'eof'

    protected static $instance;
    
    protected function setup(): void
    {
        $this->client = '';
        $this->secret = '';
        $this->token = '';
        $this->url = '';
        $this->scopes = '';
    }
eof;
        $this->brief = 'Class';
        $this->generator->addClassBody($body);
        $this->generator->addExtends($check);
    }
}
