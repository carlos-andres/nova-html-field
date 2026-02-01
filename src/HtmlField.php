<?php

namespace Vendor\NovaHtmlField;

use Closure;
use HTMLPurifier;
use HTMLPurifier_Config;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;

/**
 * @method static static make(mixed $name, string|\Closure|callable|object|null $attribute = null, callable|null $resolveCallback = null)
 */
class HtmlField extends Field
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'html-field';

    /**
     * Indicates if the element should be shown on the index view.
     *
     * @var (callable():(bool))|bool
     */
    public $showOnIndex = true;

    /**
     * Indicates if the element should be shown on the detail view.
     *
     * @var (callable():(bool))|bool
     */
    public $showOnDetail = true;

    /**
     * Static HTML content to display.
     *
     * @var string|null
     */
    protected $staticContent = null;

    /**
     * Whether to sanitize HTML output.
     */
    protected bool $sanitize = true;

    /**
     * Custom HTMLPurifier configuration.
     *
     * @var array<string, mixed>
     */
    protected array $purifierConfig = [];

    /**
     * The callback used to determine if the field should be displayed for conditional rendering.
     *
     * @var (\Closure(\Laravel\Nova\Http\Requests\NovaRequest):(bool))|null
     */
    protected $showWhenCallback = null;

    /**
     * Create a new field.
     *
     * @param  string  $name
     * @param  string|\Closure|callable|object|null  $attribute
     * @param  (callable(mixed, mixed, ?string):(mixed))|null  $resolveCallback
     * @return void
     */
    public function __construct($name, $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        // Default: hidden on forms since this is a display-only field
        $this->exceptOnForms();
    }

    /**
     * Set static HTML content.
     *
     * @return $this
     */
    public function content(string $html): static
    {
        $this->staticContent = $html;

        return $this;
    }

    /**
     * Set HTML content via closure.
     *
     * @param  \Closure(mixed):(string|null)  $callback
     * @return $this
     */
    public function html(Closure $callback): static
    {
        $this->resolveCallback = function ($value, $resource, $attribute) use ($callback) {
            return call_user_func($callback, $resource);
        };

        return $this;
    }

    /**
     * Disable HTML sanitization for trusted content.
     *
     * WARNING: Only use this for content you completely trust.
     * Disabling sanitization may expose your application to XSS attacks.
     *
     * @return $this
     */
    public function withoutSanitization(): static
    {
        $this->sanitize = false;

        return $this;
    }

    /**
     * Set custom HTMLPurifier configuration.
     *
     * @param  array<string, mixed>  $config
     * @return $this
     *
     * @see http://htmlpurifier.org/live/configdoc/plain.html
     */
    public function purifierConfig(array $config): static
    {
        $this->purifierConfig = $config;

        return $this;
    }

    /**
     * Only display the field when the given condition is truthy.
     *
     * @param  \Closure(\Laravel\Nova\Http\Requests\NovaRequest):(bool)  $callback
     * @return $this
     */
    public function when(Closure $callback): static
    {
        $this->showWhenCallback = $callback;

        return $this;
    }

    /**
     * Only display the field unless the given condition is truthy.
     *
     * @param  \Closure(\Laravel\Nova\Http\Requests\NovaRequest):(bool)  $callback
     * @return $this
     */
    public function unless(Closure $callback): static
    {
        $this->showWhenCallback = function ($request) use ($callback) {
            return ! call_user_func($callback, $request);
        };

        return $this;
    }

    /**
     * Resolve the field's value for display.
     *
     * @param  mixed  $resource
     * @param  string|null  $attribute
     * @return void
     */
    public function resolveForDisplay($resource, $attribute = null)
    {
        $this->resource = $resource;

        $html = $this->resolveHtmlContent($resource, $attribute);

        if ($this->sanitize && $html !== '') {
            $html = $this->sanitizeHtml($html);
        }

        $this->value = $html;
    }

    /**
     * Resolve the field's value.
     *
     * @param  mixed  $resource
     * @param  string|null  $attribute
     * @return void
     */
    public function resolve($resource, $attribute = null)
    {
        $this->resolveForDisplay($resource, $attribute);
    }

    /**
     * Resolve the HTML content from the resource.
     *
     * @param  mixed  $resource
     * @param  string|null  $attribute
     */
    protected function resolveHtmlContent($resource, $attribute = null): string
    {
        // Static content takes precedence
        if ($this->staticContent !== null) {
            return $this->staticContent;
        }

        // Computed field (closure as second parameter)
        if ($this->attribute === 'ComputedField' && $this->computedCallback !== null) {
            return (string) (call_user_func($this->computedCallback, $resource) ?? '');
        }

        // Resolve callback
        if ($this->resolveCallback !== null) {
            $attribute = $attribute ?? $this->attribute;
            $value = $this->resolveAttribute($resource, $attribute);

            return (string) (call_user_func($this->resolveCallback, $value, $resource, $attribute) ?? '');
        }

        // Direct attribute access
        $attribute = $attribute ?? $this->attribute;

        if ($attribute && $attribute !== 'ComputedField') {
            return (string) ($this->resolveAttribute($resource, $attribute) ?? '');
        }

        return '';
    }

    /**
     * Sanitize HTML using HTMLPurifier.
     */
    protected function sanitizeHtml(string $html): string
    {
        $config = HTMLPurifier_Config::createDefault();

        // Set cache directory
        $cachePath = $this->getPurifierCachePath();
        if ($cachePath !== null) {
            $config->set('Cache.SerializerPath', $cachePath);
        } else {
            $config->set('Cache.DefinitionImpl', null);
        }

        // Apply custom configuration
        foreach ($this->purifierConfig as $key => $value) {
            $config->set($key, $value);
        }

        return (new HTMLPurifier($config))->purify($html);
    }

    /**
     * Get the HTMLPurifier cache path.
     */
    protected function getPurifierCachePath(): ?string
    {
        if (! function_exists('storage_path')) {
            return null;
        }

        $path = storage_path('app/purifier');

        if (! is_dir($path)) {
            @mkdir($path, 0755, true);
        }

        return is_dir($path) && is_writable($path) ? $path : null;
    }

    /**
     * Determine if the field should be displayed for the given request.
     */
    public function authorize(\Illuminate\Http\Request $request): bool
    {
        if ($this->showWhenCallback !== null && $request instanceof NovaRequest) {
            if (! call_user_func($this->showWhenCallback, $request)) {
                return false;
            }
        }

        return parent::authorize($request);
    }

    /**
     * Prepare the element for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'asHtml' => true, // Always render as HTML
        ]);
    }
}
