<?php

declare(strict_types=1);

namespace Core\View;

use Core\Interface\ViewInterface;
use Stringable;

class ViewElement extends Element implements ViewInterface
{
    /**
     * Return a {@see ViewInterface} as {@see Stringable} or `string`.
     *
     * Pass `true` to return as `string`.
     *
     * @param bool $string [false]
     *
     * @return Stringable {@see \Latte\Runtime\Html} if available
     *
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    final public function getHtml( bool $string = false ) : string|Stringable
    {
        if ( \class_exists( \Latte\Runtime\Html::class ) ) {
            return new \Latte\Runtime\Html( $this->__toString() );
        }
        return $string ? $this->__toString() : $this;
    }
}
