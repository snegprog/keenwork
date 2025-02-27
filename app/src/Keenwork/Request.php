<?php

declare(strict_types=1);

namespace App\Keenwork;

use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

class Request extends GuzzleRequest implements ServerRequestInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $attributes;

    /**
     * @var array<int, string>
     */
    private array $cookieParams;

    /**
     * @var array<string>|object|null - The deserialized body parameters, if any.
     *                                These will typically be an array or object.
     */
    private $parsedBody;

    /**
     * @var array<int|string, mixed[]|string>
     */
    private array $queryParams;

    /**
     * @var array<string, string>
     */
    private array $serverParams;

    /**
     * @var UploadedFileInterface[]
     */
    private array $uploadedFiles;

    /**
     * @param string                            $method       - HTTP method
     * @param string|UriInterface               $uri          - URI
     * @param array<int, string>                $headers      - Request headers
     * @param string                            $body         - Request body
     * @param string                            $version      - Protocol version
     * @param array<string, string>             $serverParams - Typically the $_SERVER superglobal
     * @param array<int, string>                $cookies      Request cookies
     * @param UploadedFileInterface[]           $files        Request files
     * @param array<int|string, mixed[]|string> $query        Query Params
     */
    public function __construct(
        string $method,
        string|UriInterface $uri,
        array $headers,
        string $body,
        string $version,
        array $serverParams,
        array $cookies,
        array $files,
        array $query,
    ) {
        $this->serverParams = $serverParams;
        $this->cookieParams = $cookies;
        $this->uploadedFiles = $files;
        $this->queryParams = $query;
        $this->attributes = [];
        $this->parsedBody = null;

        parent::__construct($method, $uri, $headers, $body, $version);
    }

    /**
     * @return array<string, string>
     */
    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    /**
     * @return UploadedFileInterface[] An array tree of UploadedFileInterface instances
     */
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    /**
     * @param UploadedFileInterface[] $uploadedFiles an array tree of UploadedFileInterface instances
     */
    public function withUploadedFiles(array $uploadedFiles): Request
    {
        // @phpstan-ignore-next-line
        return (clone $this)->setUploadedFiles($uploadedFiles);
    }

    /**
     * @return array<int, string>
     */
    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    /**
     * @param array<int, string> $cookies array of key/value pairs representing cookies
     */
    public function withCookieParams(array $cookies): Request
    {
        // @phpstan-ignore-next-line
        return (clone $this)->setCookieParams($cookies);
    }

    /**
     * @return array<int|string, mixed[]|string>
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * @param array<string> $query - Array of query string arguments, typically from $_GET
     */
    public function withQueryParams(array $query): Request
    {
        // @phpstan-ignore-next-line
        return (clone $this)->setQueryParams($query);
    }

    /**
     * @return array<string>|object|null The deserialized body parameters, if any.
     *                                   These will typically be an array or object.
     */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    /**
     * @param array<string>|object|null $data - The deserialized body data. This will
     *                                        typically be in an array or object.
     */
    public function withParsedBody($data): Request
    {
        // @phpstan-ignore-next-line
        return (clone $this)->setParsedBody($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $name, mixed $default = null)
    {
        if (!isset($this->getAttributes()[$name])) {
            return $default;
        }

        return $this->getAttributes()[$name];
    }

    public function withAttribute(string $name, mixed $value): Request
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException('ERROR: Request::withAttribute(): invalid argument [attribute]');
        }

        return (clone $this)->addAttribute($name, $value);
    }

    public function withoutAttribute(string $name): Request
    {
        if (!isset($this->getAttributes()[$name])) {
            return $this;
        }

        // @phpstan-ignore-next-line
        return (clone $this)->unsetAttribute($name);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function setAttributes(array $attributes): self
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * @param array<int, string> $cookieParams
     */
    private function setCookieParams(array $cookieParams): self
    {
        $this->cookieParams = $cookieParams;

        return $this;
    }

    /**
     * @param array<string>|object|null $parsedBody - Десериализованные данные тела. Это будет
     *                                              обычно находится в массиве или объекте.
     */
    private function setParsedBody($parsedBody): self
    {
        $this->parsedBody = $parsedBody;

        return $this;
    }

    /**
     * @param array<int|string, array<string|int>|string> $queryParams
     */
    private function setQueryParams(array $queryParams): self
    {
        $this->queryParams = $queryParams;

        return $this;
    }

    /**
     * @param UploadedFileInterface[] $uploadedFiles
     */
    private function setUploadedFiles(array $uploadedFiles): self
    {
        $this->uploadedFiles = $uploadedFiles;

        return $this;
    }

    /**
     * Add attribute to this.
     *
     * @return $this
     */
    private function addAttribute(string $attribute, mixed $value): self
    {
        $this->attributes[$attribute] = $value;

        return $this;
    }

    private function unsetAttribute(string $name): self
    {
        if (isset($this->attributes[$name])) {
            unset($this->attributes[$name]);
        }

        return $this;
    }
}
