<?php

/**
 * @brief       Files Singleton
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  storm
 * @since       -storm_since_version-
 * @version     -storm_version-
 */

namespace IPS\storm\Profiler;

use IPS\Patterns\Singleton;
use IPS\storm\Editor;
use IPS\Theme;
use UnexpectedValueException;

use function count;
use function defined;
use function get_included_files;
use function header;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class Files extends Singleton
{

    /**
     * @brief   Singleton Instances
     * @note    This needs to be declared in any child class.
     */
    protected static ?Singleton $instance = null;

    /**
     * builds the files button
     *
     * @throws UnexpectedValueException
     */
    public function render(): array
    {
        $files = get_included_files();
        $list = [];
        $i = 1;
        foreach ($files as $key => $file) {
            $url = Editor::i()->replace($file);
            $list[$file] = ['name' => $i . ': ' . $file, 'url' => $url];
            $i++;
        }
        $count = count($list);
        $button = Theme::i()->getTemplate('profiler', 'storm', 'global')->buttons(
            'storm_profiler_files',
            '',
            'storm_profiler_files_panel', //'storm_execution_panel',
            lang('storm_profiler_button_files'),
            'file',
            '#ffff00',
            '#000',
            $count
        );
        $panel = Theme::i()->getTemplate('profiler', 'storm', 'global')->listPanel(
            $list,
            'storm_profiler_files_panel',
            lang('storm_profiler_title_files', false, ['sprintf' => [$count]])
        );

        return [
            'button' => $button,
            'panel' => $panel
        ];
    }

}
