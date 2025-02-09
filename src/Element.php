<?php

declare(strict_types=1);

namespace Core\View;

use Core\View\Element\{Attributes, Content, StaticElements, Tag};
use Core\Interface\View;
use Stringable;

class Element extends View
{
    use StaticElements;

    private ?string $html = null;

    public readonly Tag $tag;

    public readonly Attributes $attributes;

    public readonly Content $content;

    /**
     * @param string|Tag                                                             $tag
     * @param array<array-key, null|array<array-key, string>|bool|string>|Attributes $attributes
     * @param null|string|Stringable                                                 ...$content
     */
    public function __construct(
        string|Tag                $tag = 'div',
        array|Attributes          $attributes = [],
        null|string|Stringable ...$content,
    ) {
        $this->tag        = $tag instanceof Tag ? $tag : Tag::from( $tag );
        $this->attributes = $attributes instanceof Attributes ? $attributes : new Attributes( $attributes );
        $this->content    = new Content( ...$content );
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
