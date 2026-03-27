<?php

namespace Swilen\Http;

use Psr\Http\Message\StreamInterface;
use Swilen\Http\Exception\IncorrectStreamPositionException;
use Swilen\Http\Exception\NotReadableStreamException;
use Swilen\Http\Exception\NotSeekableStreamException;
use Swilen\Http\Exception\NotWritableStreamException;
use Swilen\Http\Exception\UnableToSeekException;

class Stream implements StreamInterface
{
    /** @var resource|null */
    private $stream;
    private ?bool $seekable = null;
    private ?bool $readable = null;
    private ?bool $writable = null;
    private ?int $size = null;
    private $uri = null;

    /**
     * @param resource $stream
     */
    public function __construct($stream)
    {
        if (!is_resource($stream)) {
            throw new \InvalidArgumentException('Stream must be a valid resource');
        }
        $this->stream = $stream;
    }


    /**
     * @param resource|string|array|StreamInterface $body
     */
    public static function new($body = ''): StreamInterface
    {
        if ($body instanceof StreamInterface) {
            return $body;
        }

        if (is_resource($body)) {
            return new self($body);
        }

        $resource = fopen('php://temp/maxmemory:2097152', 'rw+');
        if (false === $resource) {
            throw new \RuntimeException('Could not open temporary stream');
        }

        if (is_array($body)) {
            fwrite($resource, json_encode($body));
        } elseif (is_string($body) && $body !== '') {
            if ($body === 'php://input') {
                return new self(fopen('php://input', 'r'));
            }
            fwrite($resource, $body);
        }

        fseek($resource, 0);

        return new self($resource);
    }

    public function __toString(): string
    {
        try {
            if ($this->isSeekable()) {
                $this->rewind();
            }
            return $this->getContents();
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        if (isset($this->stream)) {
            if (is_resource($this->stream)) {
                fclose($this->stream);
            }
            $this->detach();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        $result = $this->stream;
        $this->stream = null;
        $this->size = $this->uri = null;
        $this->readable = $this->writable = $this->seekable = false;

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize(): ?int
    {
        if ($this->size !== null) {
            return $this->size;
        }

        if (!isset($this->stream)) {
            return null;
        }

        if ($this->uri) {
            clearstatcache(true, (string) $this->uri);
        }

        $stats = fstat($this->stream);
        return $this->size = $stats['size'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function tell(): int
    {
        if (!isset($this->stream) || false === $result = ftell($this->stream)) {
            throw new IncorrectStreamPositionException();
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function eof(): bool
    {
        return !isset($this->stream) || feof($this->stream);
    }

    /**
     * {@inheritdoc}
     */
    public function isSeekable(): bool
    {
        if ($this->seekable !== null) {
            return $this->seekable;
        }
        return $this->seekable = $this->getMetadata('seekable') ?? false;
    }

    /**
     * {@inheritdoc}
     */
    public function seek($offset, $whence = SEEK_SET): void
    {
        if (!$this->isSeekable()) {
            throw new NotSeekableStreamException();
        }

        if (-1 === fseek($this->stream, $offset, $whence)) {
            throw new UnableToSeekException($offset, $whence);
        }
    }

    /**
     * {@inheritdoc}
     */

    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable(): bool
    {
        if ($this->writable !== null) {
            return $this->writable;
        }
        $mode = $this->getMetadata('mode');
        return $this->writable = (strpbrk($mode, 'waxc+') !== false);
    }

    /**
     * {@inheritdoc}
     */
    public function write($string): int
    {
        if (!$this->isWritable()) {
            throw new NotWritableStreamException();
        }

        $this->size = null;
        $result = fwrite($this->stream, $string);

        if (false === $result) {
            throw new \RuntimeException('Unable to write to stream');
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable(): bool
    {
        if ($this->readable !== null) {
            return $this->readable;
        }
        $mode = $this->getMetadata('mode');
        return $this->readable = (strpbrk($mode, 'r+') !== false);
    }

    /**
     * {@inheritdoc}
     */
    public function read($length): string
    {
        if (!$this->isReadable()) {
            throw new NotReadableStreamException();
        }

        $result = fread($this->stream, $length);
        if (false === $result) {
            throw new \RuntimeException('Unable to read from stream');
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getContents(): string
    {
        if (!$this->isReadable()) {
            throw new NotReadableStreamException();
        }

        $contents = stream_get_contents($this->stream);
        if (false === $contents) {
            throw new \RuntimeException('Unable to read stream contents');
        }

        return $contents;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($key = null)
    {
        if (!isset($this->stream)) {
            return $key ? null : [];
        }

        $meta = stream_get_meta_data($this->stream);

        if ($this->uri === null) {
            $this->uri = $meta['uri'] ?? false;
        }

        if (null === $key) {
            return $meta;
        }

        return $meta[$key] ?? null;
    }
}
