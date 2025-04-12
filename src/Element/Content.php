<?php

declare(strict_types=1);

namespace Core\View\Element;

use Stringable;
use function Support\normalize_whitespace;

final class Content implements Stringable
{
    /** @var array<array-key, null|scalar|Stringable> */
    private array $content;

    /**
     * @param null|scalar|Stringable ...$content
     */
    public function __construct( string|float|int|bool|Stringable|null ...$content )
    {
        $this->content = $content;
    }

    /**
     * Will not override any existing named keys.
     *
     * @param null|scalar|Stringable ...$content
     *
     * @return $this
     */
    public function add( null|string|float|int|bool|Stringable ...$content ) : self
    {
        foreach ( $content as $key => $value ) {
            if ( \is_string( $key ) ) {
                $this->content[$key] ??= $value;
            }
            else {
                $this->content[] = $value;
            }
        }

        return $this;
    }

    /**
     * Overrides existing named keys.
     *
     * @param null|array<array-key, null|scalar|Stringable>|scalar|Stringable $content
     * @param null|int|string                                                 $key
     *
     * @return $this
     */
    public function set(
        array|string|float|int|bool|Stringable|null $content,
        null|int|string                             $key = null,
    ) : self {
        if ( $key === null ) {
            $this->content = \is_array( $content ) ? $content : [$content];
        }
        elseif ( \is_array( $content ) ) {
            $keys = \array_keys( $this->content );
            $find = \array_search( $key, $keys );
            if ( $find === false ) {
                $this->content = [...$this->content, ...$content];
            }
            else {
                // Remove the key from the original array
                $before = \array_slice( $this->content, 0, $find, true );
                $after  = \array_slice( $this->content, $find + 1, null, true );

                // Merge the pieces
                $this->content = $before + $content + $after;
            }
        }
        else {
            $this->content[$key] = $content;
        }

        return $this;
    }

    public function __toString() : string
    {
        return \implode( PHP_EOL, $this->content );
    }

    public function prepend( string|float|int|bool|Stringable|null ...$content ) : void
    {
        $this->content = [
            ...\array_map( fn( $item ) => (string) $item, $content ),
            ...$this->content,
        ];
    }

    public function append( string|float|int|bool|Stringable|null ...$content ) : void
    {
        $this->content = [
            ...$this->content,
            ...\array_map( fn( $item ) => (string) $item, $content ),
        ];
    }

    public function getString( string $separator = '' ) : string
    {
        return \implode( $separator, $this->content );
    }

    /**
     * @return null[]|scalar[]|Stringable[]
     */
    public function getArray() : array
    {
        return $this->content;
    }

    /**
     * @param bool $normalize
     *
     * @return string
     */
    public function getTextContent( bool $normalize = true ) : string
    {
        $textContent = \strip_tags( \implode( ' ', $this->content ) );

        return $normalize ? normalize_whitespace( $textContent ) : $textContent;
    }
}
