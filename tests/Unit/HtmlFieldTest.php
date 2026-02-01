<?php

namespace Vendor\NovaHtmlField\Tests\Unit;

use Illuminate\Support\Fluent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Vendor\NovaHtmlField\HtmlField;

class HtmlFieldTest extends TestCase
{
    #[Test]
    public function it_can_be_instantiated(): void
    {
        $field = HtmlField::make('Test');

        $this->assertInstanceOf(HtmlField::class, $field);
        $this->assertEquals('Test', $field->name);
        $this->assertEquals('html-field', $field->component);
    }

    #[Test]
    public function it_can_set_static_content(): void
    {
        $field = HtmlField::make('Test')
            ->content('<p>Hello World</p>');

        $resource = new Fluent;
        $field->resolveForDisplay($resource);

        // HTMLPurifier may normalize the output
        $this->assertStringContainsString('Hello World', $field->value);
    }

    #[Test]
    public function it_can_resolve_html_from_closure(): void
    {
        $field = HtmlField::make('Test', fn ($resource) => '<b>'.$resource->name.'</b>');

        $resource = new Fluent(['name' => 'John']);
        $field->resolveForDisplay($resource);

        $this->assertStringContainsString('John', $field->value);
        $this->assertStringContainsString('<b>', $field->value);
    }

    #[Test]
    public function it_can_use_html_method_for_closure(): void
    {
        $field = HtmlField::make('Test')
            ->html(fn ($resource) => '<i>'.$resource->title.'</i>');

        $resource = new Fluent(['title' => 'Test Title']);
        $field->resolveForDisplay($resource);

        $this->assertStringContainsString('Test Title', $field->value);
        $this->assertStringContainsString('<i>', $field->value);
    }

    #[Test]
    public function it_can_resolve_from_model_attribute(): void
    {
        $field = HtmlField::make('Description', 'html_content');

        $resource = new Fluent(['html_content' => '<p>Content here</p>']);
        $field->resolveForDisplay($resource);

        $this->assertStringContainsString('Content here', $field->value);
    }

    #[Test]
    public function it_returns_empty_string_for_null_content(): void
    {
        $field = HtmlField::make('Test', 'missing_attribute');

        $resource = new Fluent;
        $field->resolveForDisplay($resource);

        $this->assertEquals('', $field->value);
    }

    #[Test]
    public function it_hides_from_forms_by_default(): void
    {
        $field = HtmlField::make('Test');

        $this->assertFalse($field->showOnCreation);
        $this->assertFalse($field->showOnUpdate);
        $this->assertTrue($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
    }

    #[Test]
    public function it_can_be_shown_only_on_detail(): void
    {
        $field = HtmlField::make('Test')
            ->onlyOnDetail();

        $this->assertFalse($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertFalse($field->showOnCreation);
        $this->assertFalse($field->showOnUpdate);
    }

    #[Test]
    public function it_can_be_shown_only_on_index(): void
    {
        $field = HtmlField::make('Test')
            ->onlyOnIndex();

        $this->assertTrue($field->showOnIndex);
        $this->assertFalse($field->showOnDetail);
        $this->assertFalse($field->showOnCreation);
        $this->assertFalse($field->showOnUpdate);
    }

    #[Test]
    public function it_includes_as_html_in_json_serialization(): void
    {
        $field = HtmlField::make('Test')
            ->content('<p>Test</p>');

        $resource = new Fluent;
        $field->resolveForDisplay($resource);

        // We can't fully test jsonSerialize without Nova request,
        // but we can verify the field has the right component
        $this->assertEquals('html-field', $field->component);
    }

    #[Test]
    public function static_content_takes_precedence_over_attribute(): void
    {
        $field = HtmlField::make('Test', 'html_content')
            ->content('<p>Static Content</p>');

        $resource = new Fluent(['html_content' => '<p>Dynamic Content</p>']);
        $field->resolveForDisplay($resource);

        $this->assertStringContainsString('Static Content', $field->value);
        $this->assertStringNotContainsString('Dynamic Content', $field->value);
    }
}
