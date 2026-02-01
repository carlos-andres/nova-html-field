<?php

namespace Vendor\NovaHtmlField\Tests\Unit;

use Illuminate\Support\Fluent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Vendor\NovaHtmlField\HtmlField;

class SanitizationTest extends TestCase
{
    #[Test]
    public function it_sanitizes_html_by_default(): void
    {
        $field = HtmlField::make('Test')
            ->content('<p>Hello</p><script>alert("xss")</script>');

        $resource = new Fluent;
        $field->resolveForDisplay($resource);

        // Script tags should be removed
        $this->assertStringContainsString('Hello', $field->value);
        $this->assertStringNotContainsString('script', $field->value);
        $this->assertStringNotContainsString('alert', $field->value);
    }

    #[Test]
    public function it_removes_javascript_event_handlers(): void
    {
        $field = HtmlField::make('Test')
            ->content('<div onclick="alert(1)">Click me</div>');

        $resource = new Fluent;
        $field->resolveForDisplay($resource);

        $this->assertStringContainsString('Click me', $field->value);
        $this->assertStringNotContainsString('onclick', $field->value);
        $this->assertStringNotContainsString('alert', $field->value);
    }

    #[Test]
    public function it_removes_javascript_urls(): void
    {
        $field = HtmlField::make('Test')
            ->content('<a href="javascript:alert(1)">Link</a>');

        $resource = new Fluent;
        $field->resolveForDisplay($resource);

        $this->assertStringContainsString('Link', $field->value);
        $this->assertStringNotContainsString('javascript:', $field->value);
    }

    #[Test]
    public function it_preserves_safe_html_elements(): void
    {
        $safeHtml = '<p>Paragraph</p><strong>Bold</strong><em>Italic</em><ul><li>Item</li></ul>';

        $field = HtmlField::make('Test')
            ->content($safeHtml);

        $resource = new Fluent;
        $field->resolveForDisplay($resource);

        $this->assertStringContainsString('<p>', $field->value);
        $this->assertStringContainsString('<strong>', $field->value);
        $this->assertStringContainsString('<em>', $field->value);
        $this->assertStringContainsString('<ul>', $field->value);
        $this->assertStringContainsString('<li>', $field->value);
    }

    #[Test]
    public function it_preserves_safe_links(): void
    {
        $field = HtmlField::make('Test')
            ->content('<a href="https://example.com">External Link</a>');

        $resource = new Fluent;
        $field->resolveForDisplay($resource);

        $this->assertStringContainsString('href="https://example.com"', $field->value);
        $this->assertStringContainsString('External Link', $field->value);
    }

    #[Test]
    public function it_preserves_images_with_valid_sources(): void
    {
        $field = HtmlField::make('Test')
            ->content('<img src="https://example.com/image.jpg" alt="Test">');

        $resource = new Fluent;
        $field->resolveForDisplay($resource);

        $this->assertStringContainsString('<img', $field->value);
        $this->assertStringContainsString('src="https://example.com/image.jpg"', $field->value);
    }

    #[Test]
    public function it_can_disable_sanitization(): void
    {
        $unsafeHtml = '<script>alert("test")</script>';

        $field = HtmlField::make('Test')
            ->content($unsafeHtml)
            ->withoutSanitization();

        $resource = new Fluent;
        $field->resolveForDisplay($resource);

        // With sanitization disabled, the script tag should remain
        $this->assertStringContainsString('<script>', $field->value);
    }

    #[Test]
    public function it_accepts_custom_purifier_config(): void
    {
        // By default, HTMLPurifier allows common elements
        // We can restrict to only allow specific elements
        $field = HtmlField::make('Test')
            ->content('<p>Paragraph</p><div>Div</div>')
            ->purifierConfig([
                'HTML.Allowed' => 'p',
            ]);

        $resource = new Fluent;
        $field->resolveForDisplay($resource);

        $this->assertStringContainsString('<p>', $field->value);
        $this->assertStringNotContainsString('<div>', $field->value);
    }

    #[Test]
    public function it_handles_empty_content_gracefully(): void
    {
        $field = HtmlField::make('Test')
            ->content('');

        $resource = new Fluent;
        $field->resolveForDisplay($resource);

        $this->assertEquals('', $field->value);
    }

    #[Test]
    public function it_removes_style_tags(): void
    {
        $field = HtmlField::make('Test')
            ->content('<style>.evil { display: none; }</style><p>Content</p>');

        $resource = new Fluent;
        $field->resolveForDisplay($resource);

        $this->assertStringNotContainsString('<style>', $field->value);
        $this->assertStringNotContainsString('.evil', $field->value);
        $this->assertStringContainsString('Content', $field->value);
    }

    #[Test]
    public function it_removes_object_and_embed_tags(): void
    {
        $field = HtmlField::make('Test')
            ->content('<object data="malware.swf"></object><embed src="malware.swf"><p>Safe</p>');

        $resource = new Fluent;
        $field->resolveForDisplay($resource);

        $this->assertStringNotContainsString('<object', $field->value);
        $this->assertStringNotContainsString('<embed', $field->value);
        $this->assertStringContainsString('Safe', $field->value);
    }

    #[Test]
    public function it_removes_data_urls_from_images(): void
    {
        // HTMLPurifier by default doesn't allow data: URLs for security
        $field = HtmlField::make('Test')
            ->content('<img src="data:image/svg+xml,<svg onload=alert(1)>">');

        $resource = new Fluent;
        $field->resolveForDisplay($resource);

        $this->assertStringNotContainsString('data:', $field->value);
        $this->assertStringNotContainsString('onload', $field->value);
    }

    #[Test]
    public function it_handles_nested_xss_attempts(): void
    {
        $field = HtmlField::make('Test')
            ->content('<p><img src="x" onerror="alert(1)"></p>');

        $resource = new Fluent;
        $field->resolveForDisplay($resource);

        $this->assertStringNotContainsString('onerror', $field->value);
    }

    #[Test]
    public function it_sanitizes_dynamically_resolved_content(): void
    {
        $field = HtmlField::make('Test', fn ($resource) => $resource->unsafe_html);

        $resource = new Fluent([
            'unsafe_html' => '<p>Safe</p><script>alert("xss")</script>',
        ]);
        $field->resolveForDisplay($resource);

        $this->assertStringContainsString('Safe', $field->value);
        $this->assertStringNotContainsString('script', $field->value);
    }
}
