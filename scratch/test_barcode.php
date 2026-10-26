<?php

class BarcodeGenerator {
    private static $patterns = [
        '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213', // 0-9
        '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132', // 10-19
        '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211', // 20-29
        '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313', // 30-39
        '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331', // 40-49
        '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111', // 50-59
        '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214', // 60-69
        '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111', // 70-79
        '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141', // 80-89
        '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141', // 90-99
        '114131', '311141', '411131', '211412', '211214', '211232', '2331112' // 100-106 (104=Start B, 106=Stop)
    ];

    public static function generateSvg(string $text, int $height = 36, int $barWidth = 2, string $color = '#000000'): string {
        $text = trim($text);
        if ($text === '') $text = 'EPS-CARD';
        
        // Start with Code B (104)
        $symbols = [104];
        $sum = 104;
        
        for ($i = 0; $i < strlen($text); $i++) {
            $char = ord($text[$i]);
            $val = $char - 32;
            if ($val < 0 || $val > 95) $val = 0; // fallback to space
            $symbols[] = $val;
            $sum += $val * ($i + 1);
        }
        
        $checksum = $sum % 103;
        $symbols[] = $checksum;
        $symbols[] = 106; // Stop code
        
        // Build pattern of modules (bars and spaces)
        $modules = '';
        foreach ($symbols as $sym) {
            $pattern = self::$patterns[$sym];
            $isBar = true;
            for ($j = 0; $j < strlen($pattern); $j++) {
                $width = (int)$pattern[$j];
                $modules .= str_repeat($isBar ? '1' : '0', $width);
                $isBar = !$isBar;
            }
        }
        
        // Convert modules to SVG rects
        $totalModules = strlen($modules);
        $svgWidth = $totalModules * $barWidth;
        
        $rects = [];
        $currentBarWidth = 0;
        $currentBarStart = 0;
        
        for ($m = 0; $m < $totalModules; $m++) {
            if ($modules[$m] === '1') {
                if ($currentBarWidth === 0) {
                    $currentBarStart = $m * $barWidth;
                }
                $currentBarWidth += $barWidth;
            } else {
                if ($currentBarWidth > 0) {
                    $rects[] = "<rect x=\"{$currentBarStart}\" y=\"0\" width=\"{$currentBarWidth}\" height=\"{$height}\" fill=\"{$color}\" />";
                    $currentBarWidth = 0;
                }
            }
        }
        if ($currentBarWidth > 0) {
            $rects[] = "<rect x=\"{$currentBarStart}\" y=\"0\" width=\"{$currentBarWidth}\" height=\"{$height}\" fill=\"{$color}\" />";
        }
        
        $rectStr = implode('', $rects);
        return "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 {$svgWidth} {$height}\" width=\"100%\" height=\"{$height}\" preserveAspectRatio=\"none\">{$rectStr}</svg>";
    }
}

$svg = BarcodeGenerator::generateSvg('EPS-ROLL-08', 36, 2);
echo "SVG Length: " . strlen($svg) . "\n";
echo "SVG Preview: " . substr($svg, 0, 150) . "...\n";
