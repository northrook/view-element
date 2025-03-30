<?php

declare(strict_types=1);

namespace Core\View\Element;

use Stringable;
use function Support\normalize_whitespace;

final class Content implements Stringable
{
    /** @var array<array-key, null|string|Stringable> */
    private array $content = [];

    /**
     * @param null|array<array-key, null|string|Stringable>|string|Stringable ...$content
     */
    public function __construct( null|string|array|Stringable ...$content )
    {
        $this->addContent( $content );
    }

    /**
     * @param null|array<array-key, null|string|Stringable>|array<array-key,null|array<array-key, null|string|Stringable>|string|Stringable>|string|Stringable $content
     * @param null|int|string                                                                                                                                  $key
     *
     * @return void
     */
    public function addContent( null|string|array|Stringable $content, null|int|string $key = null ) : void
    {
        if ( \is_array( $content ) ) {
            foreach ( $content as $key => $value ) {
                $this->addContent( $value, $key );
            }
            return;
        }

        if ( $key ) {
            $this->content[$key] = $content;
        }
        else {
            $this->content[] = $content;
        }
    }

    public function __toString() : string
    {
        return \implode( PHP_EOL, $this->content );
    }

    public function set( string $key, string|Stringable|null $value ) : void
    {
        $this->content[$key] = $value;
    }

    public function prepend( null|string|Stringable ...$content ) : void
    {
        $this->content = [
            ...\array_map( fn( $item ) => (string) $item, $content ),
            ...$this->content,
        ];
    }

    public function append( null|string|Stringable ...$content ) : void
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
     * @return null[]|string[]|Stringable[]
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
