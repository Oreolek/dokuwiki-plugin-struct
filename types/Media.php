<?php
namespace plugin\struct\types;

use plugin\struct\meta\ValidationException;

class Media extends AbstractBaseType {

    protected $config = array(
        'mime' => 'image/',
        'width' => 150,
        'height' => '',
        'agg_width' => '',
        'agg_height' => ''
    );

    /**
     * Checks against the allowed mime types
     *
     * @param string $value
     */
    public function validate($value) {
        if(!trim($this->config['mime'])) return;
        $allows = explode(',', $this->config['mime']);
        $allows = array_map('trim', $allows);
        $allows = array_filter($allows);

        list(, $mime,) = mimetype($value, false);
        foreach($allows as $allow) {
            if(strpos($mime, $allow) === 0) return;
        }

        throw new ValidationException('Media mime type', $mime, $this->config['mime']);
    }

    /**
     * Output the stored data
     *
     * If outputted in an aggregation we collect the images into a gallery.
     *
     * @param string|int $value the value stored in the database
     * @param \Doku_Renderer $R the renderer currently used to render the data
     * @param string $mode The mode the output is rendered in (eg. XHTML)
     * @return bool true if $mode could be satisfied
     */
    public function renderValue($value, \Doku_Renderer $R, $mode) {
        // get width and height from config
        $width = null;
        $height = null;
        if($this->config['width']) $width = $this->config['width'];
        if($this->config['height']) $height = $this->config['height'];
        if(!empty($R->info['struct_table_hash'])) {
            // this is an aggregation, check for special values
            if($this->config['agg_width']) $width = $this->config['agg_width'];
            if($this->config['agg_height']) $height = $this->config['agg_height'];
        }

        // depending on renderer type directly output or get value from it
        $returnLink = null;
        $html = '';
        if(!media_isexternal($value)) {
            if(is_a($R, '\Doku_Renderer_xhtml')) {
                /** @var \Doku_Renderer_xhtml $R */
                $html = $R->internalmedia($value, null, null, $width, $height, null, 'direct', true);
            } else {
                $R->internalmedia($value, null, null, $width, $height, null, 'direct');
            }
        } else {
            if(is_a($R, '\Doku_Renderer_xhtml')) {
                /** @var \Doku_Renderer_xhtml $R */
                $html = $R->externalmedia($value, null, null, $width, $height, null, 'direct', true);
            } else {
                $R->externalmedia($value, null, null, $width, $height, null, 'direct');
            }
        }

        // add gallery meta data in XHTML
        if($mode == 'xhtml') {
            list(, $mime,) = mimetype($value, false);
            if(substr($mime, 0, 6) == 'image/') {
                $hash = !empty($R->info['struct_table_hash']) ? "[gal-" . $R->info['struct_table_hash'] . "]" : '';
                $html = str_replace('href', "rel=\"lightbox$hash\" href", $html);
            }
            $R->doc .= $html;
        }

        return true;
    }

    /**
     * Return the editor to edit a single value
     *
     * @param string $name the form name where this has to be stored
     * @param string $value the current value
     * @return string html
     */
    public function valueEditor($name, $value) {
        $name = hsc($name);
        $value = hsc($value);

        $id = 'struct__' . md5($name);

        $html = "<input id=\"$id\" class=\"struct_media\"  name=\"$name\" value=\"$value\" />";
        $html .= "<button type=\"button\" class=\"struct_media\">";
        $html .= "<img src=\"" . DOKU_BASE . "lib/images/toolbar/image.png\" height=\"16\" width=\"16\">";
        $html .= "</button>";
        return $html;
    }
}
