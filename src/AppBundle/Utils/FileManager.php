<?php
/**
 * Created by PhpStorm.
 * User: Maksim
 * Date: 14.09.2016
 * Time: 18:52
 */

namespace AppBundle\Utils;


class FileManager
{
    public function listDir($dir)
    {
        $files = scandir($dir);
        $files = array_slice($files, 2);

        return $files;
    }

    public function getMeta($filename)
    {
        if (!file_exists($filename)) return null;

        $meta = stat($filename);
        return array(
            'dev' => $meta['dev'],
            'ino' => $meta['ino'],
            'mode' => $meta['mode'],
            'nlink' => $meta['nlink'],
            'uid' => $meta['uid'],
            'gid' => $meta['gid'],
            'rdev' => $meta['rdev'],
            'size' => $meta['size'],
            'atime' => $meta['atime'],
            'mtime' => $meta['mtime'],
            'ctime' => $meta['ctime'],
            'blksize' => $meta['blksize'],
            'blocks' => $meta['blocks'],
        );
    }

    public function getContent($filename)
    {
        if (!file_exists($filename)) return null;
        return file_get_contents($filename);
    }

    public function getMIME($filename)
    {
        if (!file_exists($filename)) return null;

        $f = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($f, $filename);
        finfo_close($f);

        return $mime;
    }

    public function saveFile($file, $dir, $name)
    {
        $file->move($dir, $name);
    }
}
