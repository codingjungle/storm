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

use IPS\Api\Controller;
use IPS\Content\Api\CommentController;
use IPS\Content\Api\ItemController;
use IPS\Node\Api\NodeController;

class Api extends GeneratorAbstract
{
    /**
     * @inheritdoc
     */
    protected function bodyGenerator(): void
    {
        $this->brief = 'API';
        $this->namespace = 'IPS\\' . $this->app . '\\api';
        switch ($this->apiType) {
            case 's':
                $this->extends = Controller::class;
                break;
            case 'i':
                $this->extends = ItemController::class;
                break;
            case 'c':
                $this->extends = CommentController::class;
                break;
            case 'n':
                $this->extends = NodeController::class;
                break;
        }
    }
}
