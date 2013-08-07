<?php
namespace JetCms;

require_once(pathify('Internal', 'require.php'));

use \JetCms\Internal;

abstract class Controller
{
    protected $supported_renderers = ['Markdown', 'RawHtml'];

    public function __construct()
    {
        $this->template_base = pathify(\Jetpack\App::$dir->webroot, \Jetpack\App::$config->twig->dir);
    }

    public function cc_route()
    {
        $content_renderer = new Internal\ContentRenderer($this->supported_renderers, $this->content_base, $this->template_base,
            isset($this->default_template) ? $this->default_template : null, isset($this->twig) ? $this->twig : null);

        $content_renderer->render($this->routing_information->unmatched_path);
    }
}
