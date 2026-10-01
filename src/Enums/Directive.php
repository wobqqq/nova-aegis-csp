<?php

declare(strict_types=1);

namespace Wobqqq\AegisCsp\Enums;

enum Directive: string
{
    case DEFAULT_SRC = 'default-src';
    case SCRIPT_SRC = 'script-src';
    case STYLE_SRC = 'style-src';
    case IMG_SRC = 'img-src';
    case FONT_SRC = 'font-src';
    case CONNECT_SRC = 'connect-src';
    case MEDIA_SRC = 'media-src';
    case FRAME_SRC = 'frame-src';
    case OBJECT_SRC = 'object-src';
    case BASE_URI = 'base-uri';
    case FORM_ACTION = 'form-action';
    case FRAME_ANCESTORS = 'frame-ancestors';

    /**
     * The setting holding this directive's sources for the scope, such as `site_script_src`.
     */
    public function setting(Scope $scope): string
    {
        return $scope->value . '_' . str_replace('-', '_', $this->value);
    }
}
