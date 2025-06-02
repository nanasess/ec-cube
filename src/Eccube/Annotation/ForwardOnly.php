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

namespace Eccube\Annotation;

use Eccube\Attribute\ForwardOnly as AttributeForwardOnly;

/**
 * @deprecated Use Eccube\Attribute\ForwardOnly instead
 */
class_alias(AttributeForwardOnly::class, ForwardOnly::class);
