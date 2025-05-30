<?php

declare(strict_types=1);

namespace Core\View;

use Core\View\Element\{
    Attributes,
    Content,
    StaticElements,
    Tag,
};
use Core\Interface\View;
use Stringable;
use function Support\variadic_argument;

/**
 *
 */
class Element extends View
{
    use StaticElements;

    private ?string $html = null;

    public Tag $tag;

    public Attributes $attributes;

    public Content $content;

    /**
     * @param string|Tag                                                     $tag
     * @param null|array<array-key,null|scalar|Stringable>|scalar|Stringable $content
     * @param Attributes|mixed                                               ...$attributes
     */
    public function __construct(
        string|Tag                                  $tag = 'div',
        array|null|string|int|float|bool|Stringable $content = null,
        mixed                                    ...$attributes,
    ) {
        $this->tag        = $tag instanceof Tag ? $tag : Tag::from( $tag );
        $this->content    = new Content( $content );
        $this->attributes = new Attributes( ...$attributes );
    }

    protected function build() : void {}

    /**
     * @param string $separator
     * @param bool   $rebuild
     *
     * @return string
     */
    final public function render(
        string $separator = EMPTY_STRING,
        bool   $rebuild = false,
    ) : string {
        if ( $rebuild ) {
            $this->html = null;
        }

        if ( ! $this->html ) {
            $this->build();
        }

        if ( $this->tag->isSelfClosing() ) {
            return $this->html ??= $this->tag->getOpeningTag( $this->attributes );
        }

        return $this->html ??= \implode(
            $separator,
            [
                $this->tag->getOpeningTag( $this->attributes ),
                ...$this->content->getArray(),
                $this->tag->getClosingTag(),
            ],
        );
    }

    public function __toString() : string
    {
        return $this->render();
    }

    final public function tag( string $set ) : self
    {
        $this->tag->set( $set );
        return $this;
    }

    /**
     * Add attributes using named arguments.
     *
     * Underscores get converted to hyphens.
     *
     * @param mixed $id
     * @param mixed $class
     * @param mixed $style
     * @param mixed ...$attributes
     *
     * @return $this
     */
    final public function attributes(
        mixed    $id = null,
        mixed    $class = null,
        mixed    $style = null,
        mixed ...$attributes,
    ) : self {
        $this->attributes->merge( ...variadic_argument( \get_defined_vars() ) );

        return $this;
    }

    /**
     * @param null|array<array-key, string|Stringable>|string|Stringable $content
     * @param bool                                                       $prepend
     *
     * @return $this
     */
    final public function content( string|array|Stringable|null $content, bool $prepend = false ) : self
    {
        if ( $content === null ) {
            return $this;
        }

        $array = [];

        foreach ( \is_array( $content ) ? $content : [$content] as $key => $value ) {
            if ( \is_int( $key ) ) {
                $type = $value instanceof Stringable ? $value::class : \gettype( $value );
                $key  = "{$key}:({$type})";
            }
            $array[$key] = $value;
        }

        if ( $prepend ) {
            $this->content->prepend( ...$array );
        }
        else {
            $this->content->append( ...$array );
        }

        return $this;
    }

    final public function hasContent() : bool
    {
        return $this->content->has();
    }
}
