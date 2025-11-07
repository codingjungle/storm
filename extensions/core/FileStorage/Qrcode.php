<?php

/**
 * @brief       File Storage Extension: Qrcode
 * @author      <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright   (c) Invision Power Services, Inc.
 * @license     https://www.invisioncommunity.com/legal/standards/
 * @package     Invision Community
 * @subpackage  Storm: Dev Toolbox
 * @since       07 Nov 2025
 */

namespace IPS\storm\extensions\core\FileStorage;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Extensions\FileStorageAbstract;
use IPS\File as SystemFile;
use UnderflowException;

use function defined;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden');
    exit;
}

/**
 * File Storage Extension: Qrcode
 */
class Qrcode extends FileStorageAbstract
{
    /**
     * Count stored files
     *
     * @return  int
     */
    public function count(): int
    {
        return 0;
    }

    /**
     * Move stored files
     *
     * @param   int         $offset                 This will be sent starting with 0, increasing to get all files stored by this extension
     * @param   int         $storageConfiguration   New storage configuration ID
     * @param   int|NULL    $oldConfiguration       Old storage configuration ID
     * @throws  UnderflowException                  When file record doesn't exist. Indicating there are no more files to move
     * @return  void
     */
    public function move(int $offset, int $storageConfiguration, int $oldConfiguration = null): void
    {

        throw new UnderflowException();
    }

    /**
     * Check if a file is valid
     *
     * @param   SystemFile|string   $file       The file path to check
     * @return  bool
     */
    public function isValidFile(SystemFile|string $file): bool
    {
        return true;
    }

    /**
     * Delete all stored files
     *
     * @return  void
     */
    public function delete(): void
    {
    }
}
