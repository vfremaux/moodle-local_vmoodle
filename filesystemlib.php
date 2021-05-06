<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * File System Abstract Layer
 * Support library
 *
 * @author Valery Fremaux (France) (valery.fremaux@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package extralibs
 * @category third-party libs
 */

defined('MOODLE_INTERNAL') || die;

// Avoids reloading the lib when keeped in third party plugin.
if (!function_exists('filesystem_create_dir')) {

    define('FS_RECURSIVE', true);
    define('FS_NON_RECURSIVE', false);

    define('FS_SHOW_HIDDEN', true);
    define('FS_IGNORE_HIDDEN', false);

    define('FS_NO_DIRS', 2);
    define('FS_ONLY_DIRS', 1);
    define('FS_ALL_ENTRIES', 0);

    define('FS_FULL_DELETE', true);
    define('FS_CLEAR_CONTENT', false);

    /**
     * creates a dir in file system optionally creating all pathes on the way
     * @param string $path the relative path from dataroot
     * @param boolean $recursive if true, creates recursively all path elements
     * @param string $pathbase the base path
     */
    function filesystem_create_dir($path, $recursive = 0, $pathbase = null) {
        global $CFG;

        if (is_null($pathbase)) {
            $pathbase = $CFG->dataroot.'/';
        } else if ($pathbase === '') {
            $pathbase = '';
        } else {
            $pathbase = $pathbase.'/';
        }

        $result = true;
        if (!$recursive) {
            if (@$CFG->filedebug) {
                mtrace("creating dir <i>{$path}</i><br/>");
            }
            $oldmask = umask(0);
            if (!filesystem_is_dir($path, $pathbase)) {
                $result = @mkdir($pathbase . $path, 0777);
            }
            umask($oldmask);
            return $result;
        } else {
            $parts = explode('/', $path);
            $pathto = '';
            for ($i = 0; $i < count($parts) && $result; $i++) {
                $pathto .= '/' . $parts[$i];
                $result = filesystem_create_dir($pathto, 0, $pathbase);
            }
            return $result;
        }
    }

    /**
     * tests if path is a dir. A simple wrapper to is_dir
     * @param string $relativepath the path from dataroot
     * @param string $pathbase the base path
     */
    function filesystem_is_dir($relativepath, $pathbase = null) {
        global $CFG;

        if (is_null($pathbase)) {
            $pathbase = $CFG->dataroot.'/';
        } else if ($pathbase === '') {
            $pathbase = '';
        } else {
            $pathbase = $pathbase.'/';
        }

        if (@$CFG->filedebug) {
            mtrace("is dir <i>$pathbase$relativepath</i><br/>");
        }
        return is_dir($pathbase . $relativepath);
    }

    /**
     * checks if file (or dir) exists. A simple wrapper to file_exists
     * @param string $relativepath the path from dataroot
     * @param string $pathbase the base path
     */
    function filesystem_file_exists($relativepath, $pathbase = null) {
        global $CFG;

        if (is_null($pathbase)) {
            $pathbase = $CFG->dataroot.'/';
        } else if ($pathbase === '') {
            $pathbase = '';
        } else {
            $pathbase = $pathbase.'/';
        }

        if (@$CFG->filedebug) {
            mtrace("file exists <i>$pathbase$relativepath</i><br/>");
        }
        return file_exists($pathbase . $relativepath);
    }

    /**
     * scans for entries within a directory
     * @param string $relativepath the path from dataroot
     * @param boolean $hiddens shows or hide hidden files
     * @param int $what selects only dirs, files or both
     * @param string $pathbase the base path
     * @return an array of entries wich are local names in path
     */
    function filesystem_scan_dir($relativepath, $hiddens = 0, $what = 0, $pathbase = null) {
        global $CFG;

        if (is_null($pathbase)) {
            $pathbase = $CFG->dataroot.'/';
        } else if ($pathbase === '') {
            $pathbase = '';
        } else {
            $pathbase = $pathbase.'/';
        }

        if (@$CFG->filedebug) {
            mtrace("scanning <i>$pathbase$relativepath</i><br/>");
        }
        $dir = opendir($pathbase . $relativepath);
        $entries = array();
        while ($anentry = readdir($dir)) {
            if ($what == FS_ONLY_DIRS) {
                $subpath = $relativepath . '/' . $anentry;
                $subpath = preg_replace("/^\//", "", $subpath);
                if (!filesystem_is_dir($subpath, $pathbase)) {
                    continue;
                }
            }
            if ($what == FS_NO_DIRS) {
                $subpath = $relativepath . '/' . $anentry;
                $subpath = preg_replace("/^\//", "", $subpath);
                if (filesystem_is_dir($subpath, $pathbase)) {
                    continue;
                }
            }
            if ($hiddens) {
                if (($anentry != '.') && ($anentry != '..')) {
                    $entries[] = $anentry;
                }
            } else {
                if (!preg_match("/^\./", $anentry)) {
                    $entries[] = $anentry;
                }
            }
        }
        closedir($dir);
        return $entries;
    }

    /**
     * clears and removes an entire dir
     * @param string $relativepath the path from dataroot
     * @param boolean $fulldelete if true, removes the dir root either
     * @param string $pathbase the base path
     * @return an array of entries wich are local names in path
     */
    function filesystem_clear_dir($relativepath, $fulldelete = false, $pathbase = null) {
        global $CFG;

        if (is_null($pathbase)) {
            $pathbase = $CFG->dataroot.'/';
        } else if ($pathbase === '') {
            $pathbase = '';
        } else {
            $pathbase = $pathbase.'/';
        }

        if (@$CFG->filedebug) {
            mtrace("clearing dir <i>$pathbase$relativepath</i><br/>");
        }
        $exists = filesystem_is_dir($relativepath, $pathbase);
        if (!$exists && !$fulldelete) {
            return filesystem_create_dir($relativepath, $pathbase);
        }
        if (!$exists && $fulldelete) {
            return true;
        }
        $files = filesystem_scan_dir($relativepath, FS_SHOW_HIDDEN, FS_ALL_ENTRIES, $pathbase);
        foreach ($files as $afile) {
            if ($afile == "." || $afile == "..") {
                continue;
            }
            if (filesystem_is_dir("{$relativepath}/{$afile}", $pathbase)) {
                filesystem_clear_dir("{$relativepath}/{$afile}", FS_FULL_DELETE, $pathbase);
            } else {
                filesystem_delete_file("{$relativepath}/{$afile}", $pathbase);
            }
        }
        if (file_exists($pathbase . $relativepath) && $fulldelete) {
            return filesystem_remove_dir($relativepath, $pathbase);
        }
        return false;
    }

    /**
     * copies recursively a subtree from a location to another
     * @param string $source the source path from dataroot
     * @param string $dest the dest path from dataroot
     * @param string $pathbase the base path
     * @return void
     */
    function filesystem_copy_tree($source, $dest, $pathbase = null, $excludepatterns = null) {
        global $CFG;

        if (is_null($pathbase)) {
            $pathbase = $CFG->dataroot.'/';
        } else if ($pathbase === '') {
            $pathbase = '';
        } else {
            $pathbase = $pathbase.'/';
        }

        if (@$CFG->filedebug) {
            mtrace("copying tree <i>$pathbase$source</i> to <i>$pathbase$dest</i><br/>");
        }

        if (file_exists($dest) && !filesystem_is_dir($dest, $pathbase)) {
            return;
        }
        if (!filesystem_is_dir($dest, $pathbase)) {
            filesystem_create_dir($dest, FS_RECURSIVE, $pathbase);
        }
        $files = array();
        $files = filesystem_scan_dir($source, FS_SHOW_HIDDEN, FS_ALL_ENTRIES, $pathbase);
        foreach ($files as $afile) {
            if ($afile == '.' || $afile == '..') {
                continue;
            }
            if (!empty($excludepatterns)) {
                if (is_array($excludepatterns)) {
                    foreach ($excludepatterns as $apattern) {
                        if (preg_match("/$apattern/", $afile)) {
                            continue 2;
                        }
                    }
                } else {
                    if (preg_match("/$excludepatterns/", $afile)) {
                        continue;
                    }
                }
            }
            if (filesystem_is_dir("{$source}/{$afile}", $pathbase)) {
                filesystem_create_dir("{$dest}/{$afile}", FS_NON_RECURSIVE, $pathbase);
                if (count(filesystem_get_file_list("{$dest}/{$afile}", '', $pathbase)) > 0) {
                    filesystem_copy_tree("{$source}/{$afile}", "{$dest}/{$afile}", $pathbase, $excludepatterns);
                }
            } else {
                filesystem_copy_file("{$source}/{$afile}", "{$dest}/{$afile}", $pathbase);
            }
        }
    }

    /**
     * stores a file content in the file system, creating on the way directories if needed
     * @param string $relativepath the path from dataroot
     * @param string $data the data to store in
     * @param string $pathbase the base path
     */
    function filesystem_store_file($relativepath, $data, $pathbase = null) {
        global $CFG;

        if (is_null($pathbase)) {
            $pathbase = $CFG->dataroot.'/';
        } else if ($pathbase === '') {
            $pathbase = '';
        } else {
            $pathbase = $pathbase.'/';
        }

        if (@$CFG->filedebug) {
            mtrace("storing <i>$pathbase$relativepath</i><br/>");
        }
        $parts = pathinfo($relativepath);
        if (!filesystem_is_dir($parts['dirname'], $pathbase)) {
            filesystem_create_dir($parts['dirname'], $pathbase);
        }
        $file = fopen($pathbase . $relativepath, "w");
        if (!$file) {
            return false;
        }
        fwrite ($file, $data);
        fclose($file);
        return true;
    }

    /**
     * reads a file content and returns scalar string
     * @param string $relativepath the path from dataroot
     * @param string $pathbase the base path
     * @return the data as a string
     */
    function filesystem_read_a_file($relativepath, $pathbase = null) {
        global $CFG;

        if (is_null($pathbase)) {
            $pathbase = $CFG->dataroot.'/';
        } else if ($pathbase === '') {
            $pathbase = '';
        } else {
            $pathbase = $pathbase.'/';
        }

        if (@$CFG->filedebug) {
            mtrace("reading <i>$pathbase$relativepath</i><br/>");
        }
        $fullpath = $pathbase.$relativepath;
        if (file_exists($fullPath)) {
            $file = file($fullpath);
            return implode('', $file);
        }
        return false;
    }

    /**
     * deletes a file. Simple wrapper to unlink
     * @param string $relativepath the path from dataroot
     * @param string $pathbase the base path
     * @return the data as a string
     */
    function filesystem_delete_file($relativepath, $pathbase = null) {
        global $CFG;

        if (is_null($pathbase)) {
            $pathbase = $CFG->dataroot.'/';
        } else if ($pathbase === '') {
            $pathbase = '';
        } else {
            $pathbase = $pathbase.'/';
        }

        if (@$CFG->filedebug) {
            mtrace("deleting file <i>$relativepath</i><br/>");
        }
        if (filesystem_file_exists($relativepath, $pathbase) && !filesystem_is_dir($relativepath, $pathbase)) {
            return unlink($pathbase . $relativepath);
        }
        return false;
    }

    /**
     * removes an empty dir. Simple wrapper to rmdir
     * @param string $relativepath the path from dataroot
     * @param string $pathbase the base path
     */
    function filesystem_remove_dir($relativepath, $pathbase = null) {
        global $CFG;

        if (is_null($pathbase)) {
            $pathbase = $CFG->dataroot.'/';
        } else if ($pathbase === '') {
            $pathbase = '';
        } else {
            $pathbase = $pathbase.'/';
        }

        if (@$CFG->filedebug) {
            mtrace("deleting dir <i>$relativepath</i><br/>");
        }
        if (filesystem_file_exists($relativepath, $pathbase)) {
            return rmdir($pathbase . $relativepath);
        }
    }

    /**
     * renames a file. Simple wrapper to rename
     * @param string $relativepath the path from dataroot
     * @param string $pathbase the base path
     */
    function filesystem_move_file($source, $dest, $pathbase = null) {
        global $CFG;

        if (is_null($pathbase)) {
            $pathbase = $CFG->dataroot.'/';
        } else if ($pathbase === '') {
            $pathbase = '';
        } else {
            $pathbase = $pathbase.'/';
        }

        if (filesystem_file_exists($source, $pathbase)) {
            if (@$CFG->filedebug) {
                mtrace("moving file/dir <i>$source</i> to <i>$dest</i><br/>");
            }
            return rename($pathbase.$source, $pathbase.'/'.$dest);
        }
        return false;
    }

    /**
     * copy a file creating all path on the way if needed
     * @param string $source the source path from dataroot
     * @param string $dest the dest path from dataroot
     * @param string $pathbase the base path
     */
    function filesystem_copy_file($source, $dest, $pathbase = null) {
        global $CFG;

        if (is_null($pathbase)) {
            $pathbase = $CFG->dataroot.'/';
        } else if ($pathbase === '') {
            $pathbase = '';
        } else {
            $pathbase = $pathbase.'/';
        }

        if (@$CFG->filedebug) {
            mtrace("copying file <i>$pathbase$source</i> to <i>$pathbase$dest</i><br/>");
        }
        if (!filesystem_file_exists($source, $pathbase)) {
            return -1;
        }
        $parts = pathinfo($dest);
        if (!filesystem_is_dir($parts['dirname'], $pathbase)) {
            filesystem_create_dir($parts['dirname'], $pathbase);
        }
        return copy($pathbase . $source, $pathbase . $dest);
    }

    /**
     * gets a filtered list of files
     * @param string $path the path from dataroot
     * @param string $filemask the filemask for filtering
     * @param string $pathbase the base path
     */
    function filesystem_get_file_list($path, $filemask = "*.*", $pathbase = null) {
        global $CFG;

        if (is_null($pathbase)) {
            $pathbase = $CFG->dataroot.'/';
        } else if ($pathbase === '') {
            $pathbase = '';
        } else {
            $pathbase = $pathbase.'/';
        }

        if (preg_match("/(.*)\/$/", $path, $matches)) {
            $path = $matches[1];
        }
        $files = glob($pathbase . "{$path}/{$filemask}");
        return $files;
    }

}
