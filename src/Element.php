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
use function Support\is_stringable;

class Element extends View
{
    use StaticElements;

    private ?string $html = null;

    public readonly Tag $tag;

    public readonly Attributes $attributes;

    public readonly Content $content;

    /**
     * @param string|Tag                                                $tag
     * @param null|array<array-key, ?string>|scalar|Stringable|UnitEnum ...$set
     */
    public function __construct(
        string|Tag $tag = 'div',
        mixed   ...$set,
    ) {
        $this->tag        = $tag instanceof Tag ? $tag : Tag::from( $tag );
        $this->content    = new Content();
        $this->attributes = new Attributes();

        /** @var array<array-key, null|array<array-key, ?string>|scalar|Stringable|UnitEnum> $set */
        foreach ( $set as $name => $argument ) {
            if ( $argument instanceof Attributes ) {
                $this->attributes->merge( $argument );
            }
            elseif ( $argument instanceof Content ) {
                $this->content->add( ...$argument->getArray() );
            }
            elseif ( $name === 'content' && ( is_stringable( $argument ) ) ) {
                $this->content->add( $argument );
            }
            else {
                $this->attributes->add( $name, $argument );
            }

            unset( $set[$name] );
        }
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
     * @param null|array<array-key, string>|bool|string ...$add
     *
     * @return $this
     */
    final public function attributes(
        ?string                   $id = null,
        ?string                   $class = null,
        ?string                   $style = null,
        null|array|bool|string ...$add,
    ) : self {
        $add        = \get_defined_vars();
        $attributes = [...\array_pop( $add ), ...$add];
        // dd( $attributes );
        $this->attributes->add( $attributes );

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
