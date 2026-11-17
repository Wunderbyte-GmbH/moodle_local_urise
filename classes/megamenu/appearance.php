<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_urise\megamenu;

/**
 * Colours and icons editors add to the mega menu, next to the ones from the design.
 *
 * A custom colour is stored as "x" and its hex value, e.g. x1f7192. Tint and text colour are derived from it.
 * A custom icon is an uploaded SVG, cleaned and stored in the menu as icons[id] = {name, svg}.
 *
 * @package local_urise
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class appearance {
    /** @var int Maximum size of an uploaded icon. */
    public const MAXICONBYTES = 20480;

    /** @var string[] Elements kept in an uploaded icon. */
    private const ELEMENTS = [
        'svg', 'g', 'defs', 'clipPath', 'path', 'circle', 'ellipse', 'rect', 'line', 'polyline', 'polygon',
    ];

    /** @var string[] Elements that draw something. */
    private const SHAPES = ['path', 'circle', 'ellipse', 'rect', 'line', 'polyline', 'polygon'];

    /** @var string[] Attributes kept in an uploaded icon. */
    private const ATTRIBUTES = [
        'id', 'viewBox', 'width', 'height', 'd', 'cx', 'cy', 'r', 'rx', 'ry', 'x', 'y', 'x1', 'y1', 'x2', 'y2', 'points',
        'transform', 'opacity', 'fill', 'stroke', 'fill-rule', 'clip-rule', 'clip-path', 'fill-opacity', 'stroke-opacity',
        'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'stroke-miterlimit', 'stroke-dasharray', 'stroke-dashoffset',
    ];

    /**
     * Returns the id of the custom colour with a hex value.
     *
     * @param string $hex E.g. #1F7192.
     * @return string Empty if the value is not a colour.
     */
    public static function color_id(string $hex): string {
        return preg_match('/^#([0-9a-f]{6})$/i', trim($hex), $matches) ? 'x' . strtolower($matches[1]) : '';
    }

    /**
     * Whether this is the id of a custom colour.
     *
     * @param string $color
     * @return bool
     */
    public static function is_custom_color(string $color): bool {
        return (bool)preg_match('/^x[0-9a-f]{6}$/', $color);
    }

    /**
     * Returns accent, tint and text colour of a preset or a custom colour.
     *
     * @param string $color
     * @return array|null Null if there is no such colour.
     */
    public static function colors(string $color): ?array {
        if (array_key_exists($color, menu::COLORS)) {
            return menu::COLORS[$color];
        }
        if (!self::is_custom_color($color)) {
            return null;
        }
        $accent = array_map('hexdec', str_split(substr($color, 1), 2));
        // The tint is shown on white, the text has to be readable on it.
        $ground = array_map(fn($part) => $part * 0.3 + 255 * 0.7, $accent);
        $text = $accent;
        while (self::contrast($text, $ground) < 4.5 && array_sum($text) > 0) {
            $text = array_map(fn($part) => (int)floor($part * 0.92), $text);
        }
        return [
            'accent' => '#' . strtoupper(substr($color, 1)),
            'tint' => 'rgba(' . implode(', ', $accent) . ', 0.3)',
            'text' => strtoupper(vsprintf('#%02x%02x%02x', $text)),
        ];
    }

    /**
     * Returns the name of a colour for editors.
     *
     * @param string $color
     * @return string
     */
    public static function color_name(string $color): string {
        if (self::is_custom_color($color)) {
            return '#' . strtoupper(substr($color, 1));
        }
        return get_string('megamenu:color_' . $color, 'local_urise');
    }

    /**
     * Returns the custom colours the tabs of a menu use.
     *
     * @param array $menu
     * @return string[]
     */
    public static function custom_colors(array $menu): array {
        $colors = array_column($menu['tabs'] ?? [], 'color');
        return array_values(array_unique(array_filter($colors, [self::class, 'is_custom_color'])));
    }

    /**
     * Returns the styles of custom colours. The presets are part of the theme.
     *
     * @param string[] $colors
     * @return string
     */
    public static function css(array $colors): string {
        $css = '';
        foreach (array_unique($colors) as $color) {
            $values = self::is_custom_color($color) ? self::colors($color) : null;
            if (!$values) {
                continue;
            }
            $css .= ".lexa-mm-color-{$color}{--lexa-mm-accent:{$values['accent']};--lexa-mm-tint:{$values['tint']};"
                . "--lexa-mm-text:{$values['text']}}"
                . ".mm-color-{$color},.mm-pick-{$color},"
                . ".mform:has(input[name=\"color\"][value=\"{$color}\"]:checked) .mm-pick-icon:not(.mm-pick-none)"
                . "{background-color:{$values['accent']}}";
        }
        return $css;
    }

    /**
     * Turns an uploaded SVG into an icon of the menu.
     *
     * @param string $raw
     * @param string $name
     * @return array|null {id, name, svg}, null if the file cannot be used.
     */
    public static function import_icon(string $raw, string $name): ?array {
        $id = 'u' . substr(sha1($raw), 0, 10);
        $svg = self::clean_svg($raw, $id);
        if ($svg === null) {
            return null;
        }
        $name = \core_text::substr(trim(clean_param($name, PARAM_TEXT)), 0, 60);
        return ['id' => $id, 'name' => $name === '' ? 'Icon' : $name, 'svg' => $svg];
    }

    /**
     * Reduces an SVG to plain white shapes: no scripts, styles, links or images.
     *
     * @param string $raw
     * @param string $id Id of the icon, used as prefix of the ids inside.
     * @return string|null Null if nothing usable is left.
     */
    public static function clean_svg(string $raw, string $id): ?string {
        if ($raw === '' || strlen($raw) > self::MAXICONBYTES || stripos($raw, '<!ENTITY') !== false) {
            return null;
        }
        $doc = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $doc->loadXML($raw, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $root = $loaded ? $doc->documentElement : null;
        if (!$root || $root->localName !== 'svg') {
            return null;
        }

        $ids = [];
        foreach ($doc->getElementsByTagName('*') as $element) {
            $old = $element->getAttribute('id');
            if ($old !== '' && !isset($ids[$old])) {
                $ids[$old] = $id . '-' . (count($ids) + 1);
            }
        }
        self::clean_element($root, $ids);

        if (!$root->hasAttribute('viewBox')) {
            $width = (float)$root->getAttribute('width');
            $height = (float)$root->getAttribute('height');
            if ($width <= 0 || $height <= 0) {
                return null;
            }
            $root->setAttribute('viewBox', "0 0 {$width} {$height}");
        }
        $shapes = 0;
        foreach (self::SHAPES as $shape) {
            $shapes += $root->getElementsByTagName($shape)->length;
        }
        if ($shapes === 0) {
            return null;
        }
        $root->setAttribute('width', '38');
        $root->setAttribute('height', '38');
        $root->setAttribute('aria-hidden', 'true');
        $root->setAttribute('focusable', 'false');
        if (!$root->hasAttribute('fill')) {
            $root->setAttribute('fill', 'white');
        }
        return $doc->saveXML($root);
    }

    /**
     * Cleans an element of an uploaded icon and its children.
     *
     * @param \DOMElement $element
     * @param array $ids Old id => new id.
     */
    private static function clean_element(\DOMElement $element, array $ids): void {
        $attributes = [];
        // Presentation set with a style attribute is kept as attributes.
        foreach (explode(';', $element->getAttribute('style')) as $declaration) {
            $parts = array_map('trim', explode(':', $declaration, 2));
            if (count($parts) === 2) {
                $attributes[$parts[0]] = $parts[1];
            }
        }
        foreach (iterator_to_array($element->attributes) as $attribute) {
            if ($attribute->prefix === '' && $attribute->localName !== 'style') {
                $attributes[$attribute->localName] = $attribute->value;
            }
            $element->removeAttributeNode($attribute);
        }
        foreach ($attributes as $name => $value) {
            $value = in_array($name, self::ATTRIBUTES, true) ? self::clean_value($name, trim($value), $ids) : null;
            if ($value !== null) {
                $element->setAttribute($name, $value);
            }
        }

        foreach (iterator_to_array($element->childNodes) as $child) {
            if ($child instanceof \DOMElement && in_array($child->localName, self::ELEMENTS, true) && $child->prefix === '') {
                self::clean_element($child, $ids);
            } else {
                $element->removeChild($child);
            }
        }
    }

    /**
     * Cleans the value of an attribute.
     *
     * @param string $name
     * @param string $value
     * @param array $ids Old id => new id.
     * @return string|null Null to drop the attribute.
     */
    private static function clean_value(string $name, string $value, array $ids): ?string {
        if ($name === 'id') {
            return $ids[$value] ?? null;
        }
        if ($name === 'clip-path') {
            return preg_match('/^url\(#([^)]+)\)$/', $value, $matches) && isset($ids[$matches[1]])
                ? 'url(#' . $ids[$matches[1]] . ')'
                : null;
        }
        if ($name === 'fill' || $name === 'stroke') {
            // Icons are shown in white on the colour of the entry.
            return strtolower($value) === 'none' ? 'none' : 'white';
        }
        return preg_match('/^[\w\s.,%+()-]*$/', $value) ? $value : null;
    }

    /**
     * Contrast ratio of two colours.
     *
     * @param array $first Red, green, blue.
     * @param array $second
     * @return float
     */
    private static function contrast(array $first, array $second): float {
        $luminance = function (array $rgb): float {
            $parts = array_map(function ($part) {
                $part /= 255;
                return $part <= 0.03928 ? $part / 12.92 : (($part + 0.055) / 1.055) ** 2.4;
            }, array_values($rgb));
            return 0.2126 * $parts[0] + 0.7152 * $parts[1] + 0.0722 * $parts[2];
        };
        $values = [$luminance($first), $luminance($second)];
        return (max($values) + 0.05) / (min($values) + 0.05);
    }
}
