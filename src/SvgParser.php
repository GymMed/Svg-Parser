<?php

namespace GymMed\SvgParser;

class SvgParser
{
    public function __construct() {}

    public static function formatSvg(string $svgPath, array $cssDocuments): \Exception | string
    {
        self::checkDocument($svgPath);
        $svg = file_get_contents($svgPath);

        foreach ($cssDocuments as $cssDocument) {
            self::checkDocument($cssDocument);
            $css = file_get_contents($cssDocument);
            $svg = self::getParsedCSSToSvg($svg, $css);
        }

        return $svg;
    }

    public static function getParsedCSSToSvgFromPath(string $svgPath, string $css): \Exception | string
    {
        self::checkDocument($svgPath);
        $svg = file_get_contents($svgPath);
        return self::getParsedCSSToSvg($svg, $css);
    }

    public static function getParsedCSSToSvg(string $svg, string $css): \Exception | string
    {
        $styleContent = self::changeVariablesInCSS($css);
        $pattern = '/(<svg[^>]*>)\s*(<style>)/i';

        if (!preg_match($pattern, $svg)) {
            $patternWithoutStyle = '/(<svg[^>]*>)/i';
            $svg = preg_replace($patternWithoutStyle, '$1<style>' . $styleContent . '</style>', $svg);
        }
        $replacement = '$1$2' . $styleContent;

        $svg = preg_replace($pattern, $replacement, $svg);

        return $svg;
    }

    private static function changeVariablesInCSS(string $css): string
    {
        $matches = self::getVariablesFromDocument($css);

        $variables = [];
        foreach ($matches[1] as $index => $varName) {
            $variables[$varName] = $matches[2][$index];
        }

        $modifiedCssContent = preg_replace_callback('/var\(--([a-zA-Z0-9\-]+)\)/', function ($match) use ($variables) {
            $varName = $match[1];
            return isset($variables[$varName]) ? $variables[$varName] : $match[0];
        }, $css);

        return $modifiedCssContent;
    }

    private static function getVariablesFromDocument(string $css): array
    {
        $matches = [];

        preg_match_all('/--([a-zA-Z0-9\-]+)\s*:\s*([^;]+);/', $css, $matches);
        return $matches;
    }

    private static function checkDocument(string $path): void
    {
        if (!file_exists($path)) {
            throw new \Exception("Couldn't find document in path: " . $path);
        }
    }
}
