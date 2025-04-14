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
use Stringable, UnitEnum;
use function Support\variadic_argument;

// /**
//  * @param string|Tag                                                $tag
//  * @param null|array|string|Stringable                              $content
//  * @param null|array<array-key, ?string>|scalar|Stringable|UnitEnum ...$set
//  */
// public function __construct(
//         string|Tag $tag = 'div',
//         mixed   ...$set,
// ) {

/**
 * @phpstan-type AttributeShape null|array<array-key, ?string>|scalar|Stringable|UnitEnum
 */
class Element extends View
{
    use StaticElements;

    private ?string $html = null;

    public readonly Tag $tag;

    public readonly Attributes $attributes;

    public readonly Content $content;

    /**
     * @param string|Tag                                          $tag
     * @param null|null[]|scalar|scalar[]|Stringable|Stringable[] $content
     * @param array<string,mixed>|Attributes                      $attributes
     * @param mixed                                               ...$set
     */
    public function __construct(
        string|Tag                              $tag = 'div',
        null|array|string|float|bool|Stringable $content = null,
        null|array|Attributes                   $attributes = null,
        mixed                                ...$set,
    ) {
        $this->tag        = $tag instanceof Tag ? $tag : Tag::from( $tag );
        $content          = \is_array( $content ) ? $content : [$content];
        $this->content    = new Content( ...$content );
        $this->attributes = ( new Attributes( $attributes ) )->merge( $set );
    }

    protected function build( string $separator = '' ) : string
    {
        if ( $this->tag->isSelfClosing() ) {
            return $this->tag->getOpeningTag( $this->attributes );
        }

        return \implode(
            $separator,
            [
                $this->tag->getOpeningTag( $this->attributes ),
                ...$this->content->getArray(),
                $this->tag->getClosingTag(),
            ],
        );
    }

    final public function render( bool $rebuild = false ) : string
    {
        if ( $rebuild ) {
            $this->html = null;
        }

        return $this->html ??= $this->build();
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
     * @param ?string                                   $id
     * @param ?string                                   $class
     * @param ?string                                   $style
     * @param null|array<array-key, string>|bool|string ...$attributes
     *
     * @return $this
     */
    final public function attributes(
        ?string  $id = null,
        ?string  $class = null,
        ?string  $style = null,
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

        if ( ! \is_array( $content ) ) {
            $content = [$content];
        }

        if ( $prepend ) {
            $this->content->prepend( ...$content );
        }
        else {
            $this->content->append( ...$content );
        }

        return $this;
    }
}
