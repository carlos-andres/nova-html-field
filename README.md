# Nova HTML Field

[![Latest Version on Packagist](https://img.shields.io/packagist/v/carlos-andres/nova-html-field.svg?style=flat-square)](https://packagist.org/packages/carlos-andres/nova-html-field)
[![Total Downloads](https://img.shields.io/packagist/dt/carlos-andres/nova-html-field.svg?style=flat-square)](https://packagist.org/packages/carlos-andres/nova-html-field)

A Laravel Nova 4/5 field for rendering HTML content with built-in XSS protection via HTMLPurifier.

## Features

- **XSS Protection** - HTMLPurifier sanitization enabled by default
- **Dynamic Content** - Resolve HTML from model attributes or closures
- **Inline Styles** - Full support for inline CSS styling
- **View Control** - Standard Nova visibility methods
- **Conditional Display** - Show/hide based on request conditions

## Requirements

- PHP 8.1+
- Laravel 10+
- Nova 4+ or Nova 5+

## Installation

```bash
composer require carlos-andres/nova-html-field
```

No build step required - works out of the box.

## Quick Start

```php
use Vendor\NovaHtmlField\HtmlField;

// Static content
HtmlField::make('Notice')
    ->content('<p style="color: #059669;">Settings saved successfully</p>');

// Dynamic from model
HtmlField::make('Preview')
    ->html(fn ($model) => '<strong>'.e($model->title).'</strong>');

// From model attribute
HtmlField::make('Description', 'html_content');
```

## Usage

### Static HTML with `content()`

```php
HtmlField::make('Info Banner')
    ->content('
        <div style="background: #dbeafe; border-radius: 8px; padding: 16px;">
            <h3 style="margin: 0 0 4px 0; font-weight: 600; color: #1e40af;">
                Configuration
            </h3>
            <p style="margin: 0; font-size: 14px; color: #3b82f6;">
                Manage your settings below
            </p>
        </div>
    ')
    ->onlyOnForms();
```

### Dynamic Content with `html()`

```php
// Status badge that changes based on model state
HtmlField::make('Status')
    ->html(fn ($model) => '
        <span style="
            background: '.($model->is_active ? '#d1fae5' : '#fee2e2').';
            color: '.($model->is_active ? '#065f46' : '#991b1b').';
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 500;
        ">
            '.($model->is_active ? 'Active' : 'Inactive').'
        </span>
    ')
    ->onlyOnIndex();

// Image preview
HtmlField::make('Thumbnail')
    ->html(fn ($model) => $model->image_url
        ? '<img src="'.e($model->image_url).'" style="max-width: 100px; border-radius: 4px;">'
        : '<span style="color: #9ca3af;">No image</span>'
    );
```

### From Model Attribute

```php
// Direct attribute (sanitized automatically)
HtmlField::make('Body', 'html_content');

// With transform callback
HtmlField::make('Formatted', 'raw_content', function ($value) {
    return '<div style="white-space: pre-wrap;">'.e($value).'</div>';
});
```

### View Visibility

```php
HtmlField::make('Details')
    ->content('<p>Only visible on detail view</p>')
    ->onlyOnDetail();

HtmlField::make('Summary')
    ->html(fn ($m) => $m->summary_html)
    ->showOnIndex()
    ->hideFromDetail();

HtmlField::make('Form Help')
    ->content('<p style="color: #6b7280;">Fill in all required fields</p>')
    ->onlyOnForms();
```

### Conditional Rendering

```php
// Show only for admins
HtmlField::make('Admin Panel')
    ->content('<div>Admin-only content</div>')
    ->when(fn ($request) => $request->user()->isAdmin());

// Hide for admins
HtmlField::make('User Notice')
    ->content('<p>Contact admin for changes</p>')
    ->unless(fn ($request) => $request->user()->isAdmin());
```

## Styling Guide

### Use Inline Styles (Recommended)

Inline styles are the most reliable way to style HtmlField content:

```php
HtmlField::make('Alert')
    ->content('
        <div style="
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        ">
            <span style="font-size: 18px;">Warning</span>
            <span style="color: #92400e;">Please review before saving</span>
        </div>
    ');
```

### Tailwind CSS Limitations

Tailwind utility classes (e.g., `bg-blue-500`, `p-4`, `rounded-lg`) **will not render** unless they are already included in Nova's compiled CSS bundle. Nova only includes the Tailwind classes it uses internally.

```php
// This may NOT work (classes might not exist in Nova's CSS)
HtmlField::make('Card')
    ->content('<div class="bg-blue-100 p-4 rounded-lg">Hello</div>');

// This WILL work (inline styles always render)
HtmlField::make('Card')
    ->content('<div style="background: #dbeafe; padding: 16px; border-radius: 8px;">Hello</div>');
```

### Icons and Emojis

HTMLPurifier strips SVG elements by default. Use emoji or Unicode symbols instead:

```php
// Using emoji (works)
HtmlField::make('Files')
    ->content('<h3>📁 Files Section</h3>');

HtmlField::make('Images')
    ->content('<h3>🖼️ Images Section</h3>');

// SVG will be stripped (won't work without disabling sanitization)
HtmlField::make('Files')
    ->content('<svg>...</svg> Files Section');
```

## Security

### Default Protection

All HTML is sanitized using [HTMLPurifier](http://htmlpurifier.org/):

| Threat | Protection |
|--------|------------|
| `<script>` tags | Removed |
| Event handlers (`onclick`, `onerror`) | Removed |
| `javascript:` URLs | Blocked |
| `<style>`, `<object>`, `<embed>` | Removed |
| `data:` URLs in images | Blocked |
| Safe HTML elements | Preserved |
| Inline styles | Preserved |

### Best Practices

**Always escape dynamic content:**

```php
// Good - escaped
HtmlField::make('Title')
    ->html(fn ($m) => '<strong>'.e($m->title).'</strong>');

// Bad - XSS vulnerable
HtmlField::make('Title')
    ->html(fn ($m) => '<strong>'.$m->title.'</strong>');
```

### Disable Sanitization (Trusted Content Only)

```php
// Only for content you completely control
HtmlField::make('Trusted HTML')
    ->html(fn ($m) => $m->trusted_html)
    ->withoutSanitization();
```

### Custom Purifier Configuration

```php
// Restrict allowed elements
HtmlField::make('Simple')
    ->html(fn ($m) => $m->html)
    ->purifierConfig([
        'HTML.Allowed' => 'p,b,i,a[href]',
    ]);

// Allow target="_blank" on links
HtmlField::make('Links')
    ->html(fn ($m) => $m->html)
    ->purifierConfig([
        'Attr.AllowedFrameTargets' => ['_blank'],
    ]);
```

See [HTMLPurifier docs](http://htmlpurifier.org/live/configdoc/plain.html) for all options.

## API Reference

| Method | Description |
|--------|-------------|
| `content(string $html)` | Set static HTML content |
| `html(Closure $callback)` | Set HTML via closure (receives model) |
| `withoutSanitization()` | Disable HTMLPurifier (use with caution) |
| `purifierConfig(array $config)` | Custom HTMLPurifier settings |
| `when(Closure $callback)` | Show when condition is true |
| `unless(Closure $callback)` | Show unless condition is true |

### Inherited Nova Methods

- `onlyOnIndex()`, `onlyOnDetail()`, `onlyOnForms()`
- `showOnIndex()`, `showOnDetail()`, `showOnCreating()`, `showOnUpdating()`
- `hideFromIndex()`, `hideFromDetail()`, `hideWhenCreating()`, `hideWhenUpdating()`
- `exceptOnForms()`
- `canSee(Closure $callback)`
- `fullWidth()`
- `help(string $text)`

## Testing

```bash
composer test
```

**Note:** Tests require Nova classes. Run from within a Laravel project that has Nova installed, or the tests will fail with "Class not found" errors.

## Changelog

future implementation.

## License

MIT License. See [LICENSE](LICENSE) for details.
