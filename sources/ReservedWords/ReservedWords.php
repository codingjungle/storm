<?php

/**
 * @brief       ReservedWords Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox: Base
 * @since       1.0.0
 * @version     -storm_version-
 */

namespace IPS\storm;

use function in_array;
use function mb_strtolower;

class ReservedWords
{
    public static $bad = [
        'base64_encode',
        'base64_decode',
        '$_GET',
        '$_POST',
        '$_REQUEST',
        '$_COOKIE',
        'curl_init',
        'curl_setopt',
        'eval',
        'assert',
        'preg_replace',
        'create_function',
        'include',
        'require',
        'include_once',
        'require_once',
        'Reflection',
        'passthru',
        'exec',
        'system',
        'shell_exec',
        '`',
        'popen',
        'proc_open',
        'pcntl_exec',
        'ob_start',
        'array_diff_uassoc',
        'array_diff_ukey',
        'array_filter',
        'array_intersect_uassoc',
        'array_intersect_ukey',
        'array_map',
        'array_reduce',
        'array_udiff_assoc',
        'array_udiff_uassoc',
        'array_udiff',
        'array_uintersect_assoc',
        'array_uintersect_uassoc',
        'array_uintersect',
        'array_walk_recursive',
        'array_walk',
        'assert_options',
        'uasort',
        'uksort',
        'usort',
        'preg_replace_callback',
        'spl_autoload_register',
        'iterator_apply',
        'call_user_func',
        'call_user_func_array',
        'register_shutdown_function',
        'register_tick_function',
        'set_error_handler',
        'set_exception_handler',
        'session_set_save_handler',
        'sqlite_create_aggregate',
        'sqlite_create_function',
        'phpinfo',
        'posix_mkfifo',
        'posix_getlogin',
        'posix_ttyname',
        'getenv',
        'get_current_user',
        'proc_get_status',
        'get_cfg_var',
        'disk_free_space',
        'disk_total_space',
        'diskfreespace',
        'getcwd',
        'getlastmo',
        'getmygid',
        'getmyinode',
        'getmypid',
        'getmyuid',
        'fopen',
        'tmpfile',
        'bzopen',
        'gzopen',
        'SplFileObject',
        'chgrp',
        'chmod',
        'chown',
        'copy',
        'file_put_contents',
        'lchgrp',
        'lchown',
        'link',
        'mkdir',
        'move_uploaded_file',
        'rename',
        'rmdir',
        'symlink',
        'tempnam',
        'touch',
        'unlink',
        'imagepng',
        'imagewbmp',
        'image2wbmp',
        'imagejpeg',
        'imagexbm',
        'imagegif',
        'imagegd',
        'imagegd2',
        'iptcembed',
        'ftp_get',
        'ftp_nb_get',
        'file_exists',
        'file_get_contents',
        'file',
        'fileatime',
        'filectime',
        'filegroup',
        'fileinode',
        'filemtime',
        'fileowner',
        'fileperms',
        'filesize',
        'filetype',
        'glob',
        'is_dir',
        'is_executable',
        'is_file',
        'is_link',
        'is_readable',
        'is_uploaded_file',
        'is_writable',
        'is_writeable',
        'linkinfo',
        'lstat',
        'parse_ini_file',
        'pathinfo',
        'readfile',
        'readlink',
        'realpath',
        'stat',
        'gzfile',
        'readgzfile',
        'getimagesize',
        'imagecreatefromgif',
        'imagecreatefromjpeg',
        'imagecreatefrompng',
        'imagecreatefromwbmp',
        'imagecreatefromxbm',
        'imagecreatefromxpm',
        'ftp_put',
        'ftp_nb_put',
        'exif_read_data',
        'read_exif_data',
        'exif_thumbnail',
        'exif_imagetype',
        'hash_file',
        'hash_hmac_file',
        'hash_update_file',
        'md5_file',
        'sha1_file',
        'highlight_file',
        'show_source',
        'php_strip_whitespace',
        'get_meta_tags',
    ];
    /**
     * a list of reserved kw in php, that class names can not be, this isn't an exhaustive list
     *
     * @var array
     */
    public static $reserved = [
        'abstract',
        'and',
        'array',
        'as',
        'break',
        'callable',
        'case',
        'catch',
        'class',
        'clone',
        'const',
        'continue',
        'declare',
        'default',
        'die',
        'do',
        'echo',
        'else',
        'elseif',
        'empty',
        'enddeclare',
        'endfor',
        'endforeach',
        'endif',
        'endswitch',
        'endwhile',
        'eval',
        'exit',
        'extends',
        'final',
        'for',
        'foreach',
        'function',
        'global',
        'goto',
        'if',
        'implements',
        'include',
        'include_once',
        'instanceof',
        'insteadof',
        'interface',
        'isset',
        'list',
        'namespace',
        'new',
        'or',
        'print',
        'private',
        'protected',
        'public',
        'require',
        'require_once',
        'return',
        'static',
        'switch',
        'throw',
        'trait',
        'try',
        'unset',
        'use',
        'var',
        'while',
        'xor',
    ];

    /**
     * ReservedWords constructor.
     */
    public function __construct()
    {
    }

    /**
     * checks to make sure a trait/interface/class name isn't a reserved word in php!
     *
     * @param $data
     *
     * @return bool
     */
    public static function check($data): bool
    {
        if (in_array(mb_strtolower($data), static::$reserved, true)) {
            return true;
        }

        return false;
    }

    /**
     * returns the reserved words list
     *
     * @return array
     */
    public static function get(): array
    {
        return static::$reserved;
    }
}
