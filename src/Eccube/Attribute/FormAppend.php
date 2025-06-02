<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class FormAppend
{
    public function __construct(
        public bool $auto_render = false,
        public ?string $form_theme = null,
        public ?string $type = null,
        public array $options = [],
        public ?string $style_class = null
    ) {
    }
}
