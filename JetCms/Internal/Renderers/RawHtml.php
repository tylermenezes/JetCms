<?php

namespace JetCms\Internal\Renderers;

/**
 * Renders raw HTML files
 *
 * @author      Tyler Menezes <tylermenezes@gmail.com>
 * @copyright   Copyright (c) Tyler Menezes. Released under the Perl Artistic License 2.0.
 *
 * @package     JetCms\Internal\Renderers
 */
class RawHtml implements IRenderer
{
    protected $path;
    public function __construct($path, $relative_path)
    {
        $this->path = $path;
    }
    public function render()
    {
        return $this->get_file_parts()->content;
    }

    public function get_info()
    {
        return $this->get_file_parts()->info;
    }

    public static function get_supported_extensions()
    {
        return ['html'];
    }

    public function get_file_parts()
    {
        $file = file_get_contents($this->path);
        $file = str_replace("\r\n", "\n", $file);
        $file = str_replace("\r", "\n", $file);

        $info = [];
        $content = $file;

        $tag_whitelist = ['title', 'meta'];
        $garbage_length = 0;
        foreach (explode("\n", $file) as $line) {
            if (substr($line, 0, 1) === '<') {
                $tag = substr($line, 1, strpos(substr($line, 1), '>'));
                if (in_array($tag, $tag_whitelist)) {
                    $content = substr($line, strlen($tag) + 2, strlen($line) - ((2 * strlen($tag)) + 5));
                    $info[$tag] = $content;
                    $garbage_length += strlen($line) + 1;
                    continue;
                }
            }
            break;
        }

        $content = substr($file, $garbage_length);

        return (Object)[
            'info' => $info,
            'content' => $content
        ];
    }
}
