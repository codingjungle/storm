<?php

namespace IPS\storm\Proxy\Generator;

use IPS\Application;
use IPS\Content\ModeratorPermissions;
use Throwable;

use function array_keys;
use function array_merge;
use function implode;

class Moderators
{
    public static function run(): void
    {
        $body = Store::i()->read('storm_metadata_final');
        $toggles = [
            'view_future' => [],
            'future_publish' => [],
            'pin' => [],
            'unpin' => [],
            'feature' => [],
            'unfeature' => [],
            'edit' => [],
            'hide' => [],
            'unhide' => [],
            'view_hidden' => [],
            'move' => [],
            'lock' => [],
            'unlock' => [],
            'reply_to_locked' => [],
            'delete' => [],
            'split_merge' => [],
            'feature_comments' => [],
            'unfeature_comments' => [],
            'add_item_message' => [],
            'edit_item_message' => [],
            'delete_item_message' => [],
        ];

        foreach (
            Application::allExtensions(
                'core',
                'ModeratorPermissions',
                false
            ) as $k => $ext
        ) {
            if ($ext instanceof ModeratorPermissions) {
                /**
                 * @var ModeratorPermissions $ext
                 */
                $class = null;

                foreach ($ext->actions as $s) {
                    $class = $ext::$class;
                    $toggles[$s][] = "can_{$s}_{$class::$title}";
                }

                if (isset($class::$commentClass)) {
                    foreach ($ext->commentActions as $s) {
                        $commentClass = $class::$commentClass;
                        $toggles[$s][] = "can_{$s}_{$commentClass::$title}";
                    }
                }

                if (isset($class::$reviewClass)) {
                    foreach ($ext->reviewActions as $s) {
                        $reviewClass = $class::$reviewClass;
                        $toggles[$s][] = "can_{$s}_{$reviewClass::$title}";
                    }
                }
            }
        }

        $apps = Application::appsWithExtension('core', 'ModeratorPermissions');
        $perms = [[]];

        /**
         * @var Application $app
         */
        foreach ($apps as $app) {
            $extensions = $app->extensions('core', 'ModeratorPermissions', true);

            /* @var ModeratorPermissions $extension */
            foreach ($extensions as $extension) {
                try {
                    $perms[] = array_keys($extension->getPermissions($toggles));
                } catch (Throwable) {
                }
            }
        }

        $perms = array_merge(...$perms);

        $toWrite = [];

        foreach ($perms as $key => $val) {
            $toWrite[] = "'" . $val . "'";
        }

        $toWrite = implode(',', $toWrite);
        $body[] = <<<EOF
    registerArgumentsSet('Moderators', {$toWrite});
EOF;

        $methods = [
            ['f' => '\\IPS\\Member::modPermission()', 'i' => 0]
        ];

        foreach ($methods as $m) {
            $body[] = <<<EOF
    expectedArguments({$m['f']}, {$m['i']}, argumentsSet('Moderators'));
EOF;
        }

        Store::i()->write($body, 'storm_metadata_final');
    }
}