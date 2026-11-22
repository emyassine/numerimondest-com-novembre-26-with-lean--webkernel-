<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Validation\Rules;

class ImageFile extends File
{
    /**
     * Create a new image file rule instance.
     *
     * @param  bool  $allowSvg
     */
    public function __construct($allowSvg = false)
    {
        if ($allowSvg) {
            $this->rules('image:allow_svg');
        } else {
            $this->rules('image');
        }
    }

    /**
     * The dimension constraints for the uploaded file.
     *
     * @param  \Webkernel\Validation\Rules\Dimensions  $dimensions
     * @return $this
     */
    public function dimensions($dimensions)
    {
        $this->rules($dimensions);

        return $this;
    }
}
