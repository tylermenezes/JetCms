<?php

namespace JetCms\Internal\Renderers;

/**
 * Interface for classes which can render files.
 *
 * @author      Tyler Menezes <tylermenezes@gmail.com>
 * @copyright   Copyright (c) Tyler Menezes. Released under the Perl Artistic License 2.0.
 *
 * @package     JetCms\Internal\Renderers
 */
interface IRenderer
{
    public function __construct($path, $relative_path);
    public function render();
    public function get_info();
    public static function get_supported_extensions();
}
