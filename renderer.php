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
 * @package     local_vmoodle
 * @category    local
 */
defined('MOODLE_INTERNAL') || die;

class local_vmoodle_renderer extends plugin_renderer_base {

    public function image_url($image, $subplugin = null) {
<<<<<<< HEAD
        global $CFG, $OUTPUT;

        if (!$subplugin) {
            return $OUTPUT->image_url($image, 'local_vmoodle');
=======
        global $CFG;

        if ($subplugin == 'moodle' || $subplugin == 'core') {
            return $this->output->image_url($image, 'moodle');
        }

        if (!$subplugin) {
            return $this->output->image_url($image, 'local_vmoodle');
>>>>>>> f0e8ce055c5d6b1708c2f90d0e41c0191910aa31
        }

        list($type, $plugin) = explode('_', $subplugin);

        $parts = pathinfo($image);

        $filepath = $CFG->dirroot.'/local/vmoodle/plugins/'.$plugin.'/pix/'.$parts['filename'];

        // We do not support SVG.
        $realpath = local_vmoodle_renderer::image_exists($filepath, false);
        $parts = pathinfo($realpath);

        return $CFG->wwwroot.'/local/vmoodle/plugins/'.$plugin.'/pix/'.$parts['filename'].'.'.$parts['extension'];
    }

    /**
     * Checks if file with any image extension exists.
     *
     * The order to these images was adjusted prior to the release of 2.4
     * At that point the were the following image counts in Moodle core:
     *
     *     - png = 667 in pix dirs (1499 total)
     *     - gif = 385 in pix dirs (606 total)
     *     - jpg = 62  in pix dirs (74 total)
     *     - jpeg = 0  in pix dirs (1 total)
     *
     * There is work in progress to move towards SVG presently hence that has been prioritiesed.
     *
     * @param string $filepath
     * @param bool $svg If set to true SVG images will also be looked for.
     * @return string image name with extension
     */
    private static function image_exists($filepath, $svg = false) {
        if ($svg && file_exists("$filepath.svg")) {
            return "$filepath.svg";
        } else if (file_exists("$filepath.png")) {
            return "$filepath.png";
        } else if (file_exists("$filepath.gif")) {
            return "$filepath.gif";
        } else if (file_exists("$filepath.jpg")) {
            return "$filepath.jpg";
        } else if (file_exists("$filepath.jpeg")) {
            return "$filepath.jpeg";
        } else {
            return false;
        }
    }

    /**
     * Print the start of a collapsable block.
     * @param string $id The id of the block.
     * @param string $caption The caption of the block.
     * @param string $content The HTMLcontent of the block.
     * @param string $classes The CSS classes of the block.
     * @param string $displayed True if the block is displayed by default, false otherwise.
     */
    public function collapsable_block($id, $caption, $content, $classes = '', $displayed = true) {
<<<<<<< HEAD
        global $OUTPUT;
=======
>>>>>>> f0e8ce055c5d6b1708c2f90d0e41c0191910aa31
        static $i = 0;

        $i++;

<<<<<<< HEAD
        $template = new StdClass;
=======
        $template = new StdClass();
>>>>>>> f0e8ce055c5d6b1708c2f90d0e41c0191910aa31
        $template->id = $id;
        $template->i = $i;

        $template->caption = $caption;
        $template->captionnotags = strip_tags($caption);

        $pixpath = ($displayed) ? '/t/expanded' : '/t/collapsed';
<<<<<<< HEAD
        $template->pixpathurl = $OUTPUT->image_url($pixpath);
=======
        $template->pixpathurl = $this->image_url($pixpath, 'core');
>>>>>>> f0e8ce055c5d6b1708c2f90d0e41c0191910aa31
        $template->showctlalt = ($displayed) ? get_string('hide') : get_string('show');
        $template->hideclass = ($displayed) ? '' : ' vmoodle-hidden';
        $template->blockcontent = $content;

        return $this->output->render_from_template('local_vmoodle/collapsibleblock', $template);
    }
<<<<<<< HEAD
=======

    public function namefilter($current) {
        $template = new StdClass;
        $template->namefilter = $current;
        $template->filterurl = new moodle_url('/local/vmoodle/view.php', ['view' => 'management', 'what' => 'list', 'vpage' => 0]);
        return $this->output->render_from_template('local_vmoodle/namefilter', $template);
    }

    public function success_hosts_report($hosts, $command) {
        $template = new StdClass;

        $i = 0;
        foreach ($hosts as $hostroot => $hostname) {
            $hosttpl = new StdClass;
            $hosttpl->i = $i;
            $hosttpl->hostname = $hostname;
            $status = $command->get_result($hostroot, 'status');
            $hosttpl->rpcstatus = get_string('rpcstatus'.$status, 'local_vmoodle');
            $hosttpl->resultmessage = $command->get_result($hostroot, 'message');
            $template->hosts[] = $hosttpl;
            $i = ($i + 1) % 2;
        }

        return $this->output->render_from_template('local_vmoodle/success_hosts_report', $template);
    }

    public function failed_hosts_report($hosts, $command) {
        $template = new StdClass;

        $i = 0;
        foreach ($hosts as $hostroot => $hostname) {
            $hosttpl = new StdClass;
            $hosttpl->i = $i;
            $hosttpl->hostname = $hostname;
            $hosttpl->rpcstatus = get_string('rpcstatus'.$command->get_result($hostroot, 'status'), 'local_vmoodle');
            if ($command->get_result($hostroot, 'status') > 200 && $command->get_result($hostroot, 'status') < 520) {
                $params = array('view' => 'sadmin', 'what' => 'runcmdagain', 'platform' => urlencode($hostroot));
                $btnurl = new moodle_url('view.php', $params);
                $label = get_string('runcmdagain', 'local_vmoodle');
                $hosttpl->runagainbutton = $this->output->single_button($btnurl, $label, 'get');
            }
            $hosttpl->errors = implode('<br/>', $command->get_result($hostroot, 'errors'));
            $template->hosts[] = $hosttpl;
            $i = ($i + 1) % 2;
        }

        return $this->output->render_from_template('local_vmoodle/failed_hosts_report', $template);
    }
>>>>>>> f0e8ce055c5d6b1708c2f90d0e41c0191910aa31
}
