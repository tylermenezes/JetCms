<?php

namespace JetCms\Internal;


/**
 * Renders a given request
 * 
 * @author      Tyler Menezes <tylermenezes@gmail.com>
 * @copyright   Copyright (c) Tyler Menezes. Released under the Perl Artistic License 2.0.
 *
 * @package JetCms\Internal
 */
class ContentRenderer {
    protected $content_base;
    protected $template_base;
    protected $twig;
    protected $supported_renderers;

    public function __construct($supported_renderers, $content_base, $template_base, $default_template = null, $twig = null)
    {
        $this->content_base = $content_base;
        $this->template_base = $template_base;
        $this->supported_renderers = $supported_renderers;
        $this->default_template = $default_template;

        if (isset($twig)) {
            $this->twig = $twig;
        } else {
            $this->load_twig();
        }
    }

    protected function load_twig()
    {
        $loader = new \Twig_Loader_Filesystem($this->template_base);
        $this->twig = new \Twig_Environment($loader, []);
    }

    protected function get_all_supported_extensions()
    {
        $extensions = [];
        foreach ($this->supported_renderers as $renderer_name) {
            $loader = '\\JetCms\\Internal\\Renderers\\' . $renderer_name;
            $extensions = array_merge($extensions, $loader::get_supported_extensions());
        }

        return $extensions;
    }

    protected function get_renderer_for_extension($extension)
    {
        foreach ($this->supported_renderers as $renderer_name) {
            $loader = '\\JetCms\\Internal\\Renderers\\' . $renderer_name;
            if (in_array($extension, $loader::get_supported_extensions())) {
                return $loader;
            }
        }

        throw new \Exception('Renderer not found for extension '.$extension);
    }

    protected function get_file_info($file)
    {
        $renderer_name = $this->get_renderer_for_extension(pathinfo($file, PATHINFO_EXTENSION));
        $renderer = new $renderer_name($file, $file);
        return $renderer->get_info();
    }

    protected function get_files($dir, $current_file = null)
    {
        $files = [];
        foreach (new \DirectoryIterator($dir) as $file_info) {
            if (substr($file_info->getFilename(), 0, 1) === '.') {
                continue;
            }

            if (!in_array($file_info->getExtension(), $this->get_all_supported_extensions())) {
                continue;
            }

            if ($file_info->isFile()) {
                $files[] = (object)[
                    'filename' => $file_info->getFilename(),
                    'info' => $this->get_file_info($file_info->getPathname()),
                    'active' => $current_file === $file_info->getFilename()
                ];
            }
        }

        return $files;
    }

    protected function get_best_matching_template($unmatched_path)
    {
        $path_parts = explode('/', $unmatched_path);

        $continue = true;
        do {
            if (count($path_parts) === 0) {
                $continue = false;
            }

            $check_path = implode(DIRECTORY_SEPARATOR, $path_parts);

            // Make sure this isn't a file name
            if (is_file(implode(DIRECTORY_SEPARATOR, [$this->template_base, $this->content_base, $check_path]))) {
                continue;
            }

            // Check if the directory has a template.html.twig file
            if (file_exists(implode(DIRECTORY_SEPARATOR, [$this->template_base, $this->content_base, $check_path,
                'template.html.twig']))) {
                return implode(DIRECTORY_SEPARATOR, [$this->content_base, $check_path, 'template.html.twig']);
            }

            array_pop($path_parts);
        } while($continue);

        return $this->default_template;
    }

    protected function get_renderable_file($unmatched_path)
    {
        $supported_file_locations = [
            implode(DIRECTORY_SEPARATOR, [$unmatched_path, 'index']),
            implode(DIRECTORY_SEPARATOR, [$unmatched_path])
        ];

        foreach ($supported_file_locations as $loc) {
            foreach ($this->get_all_supported_extensions() as $ext) {
                $check_file = implode('.', [$loc, $ext]);
                if (file_exists(implode(DIRECTORY_SEPARATOR, [$this->template_base, $this->content_base, $check_file]))) {
                    return $check_file;
                }
            }
        }

        return null;
    }

    public function render($unmatched_path)
    {
        $render_file = $this->get_renderable_file($unmatched_path);
        if ($render_file === null) {
            throw new \CuteControllers\HttpError(404);
        }

        $render_full_path = implode(DIRECTORY_SEPARATOR, [$this->template_base, $this->content_base, $render_file]);

        $renderer_name = $this->get_renderer_for_extension(pathinfo($render_file, PATHINFO_EXTENSION));
        $renderer = new $renderer_name($render_full_path, $render_file);
        $files = $this->get_files(dirname($render_full_path), basename($render_file));
        $info = $renderer->get_info();
        $rendered = $renderer->render();

        $template = $this->get_best_matching_template($unmatched_path);
        if ($template === null) {
            throw new \CuteControllers\HttpError(404);
        }

        $file_path_no_ext = pathify(dirname($render_full_path), pathinfo($render_full_path, PATHINFO_FILENAME));
        $template_dir =dirname(pathify($this->template_base, $template));

        $relativefile = ltrim(substr($file_path_no_ext, strlen($template_dir)), '/');

        if ($relativefile === 'index' && $unmatched_path === '') {
            $relativefile = '';
        }

        echo $this->twig->render($template, [
            'info' => $info,
            'file' => basename($render_file),
            'relativefile' => $relativefile,
            'siblings' => $files,
            'path' => $unmatched_path,
            'html' => $rendered
        ]);
    }
}
