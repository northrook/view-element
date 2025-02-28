<?php

declare(strict_types=1);

namespace Core\View;

use Core\View\Element\{Attributes, Content, StaticElements, Tag};
use Core\Interface\View;
use Stringable;
use UnitEnum;

class Element extends View
{
    use StaticElements;

    private ?string $html = null;

    public readonly Tag $tag;

    public readonly Attributes $attributes;

    public readonly Content $content;

    /**
     * @param string|Tag                                                               $tag
     * @param null|array<string, null|string|Stringable>|string|Stringable             $content
     * @param null|array<array-key, ?string>|Attributes|bool|float|int|string|UnitEnum ...$attributes
     */
    public function __construct(
        string|Tag                                              $tag = 'div',
        null|string|array|Stringable                            $content = null,
        Attributes|array|null|bool|float|int|string|UnitEnum ...$attributes,
    ) {
        $this->tag        = $tag instanceof Tag ? $tag : Tag::from( $tag );
        $this->content    = new Content( ...( \is_array( $content ) ? $content : [$content] ?? [] ) );
        $this->attributes = new Attributes( ...$attributes );
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
     * @param null|array<array-key, string>|bool|string ...$set
     *
     * @return $this
     */
    final public function attributes( null|array|bool|string ...$set ) : self
    {
        $this->attributes->add( $set );

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
