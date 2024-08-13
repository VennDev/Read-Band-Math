<?php

$mathLists = [
    '(DN*(Gain)+(Bias))/(sin(-sun elevation angle)*0.9)',
    'L = (DN*(Gain))+(Bias)'
];

$sun_elevation_angle = 0;
$band_formulas = [];

$dir = __DIR__ . '/folder';

function showIntro()
{
    echo "--- Welcome to the Band Metadata Reader ---\n";
    echo "This script will read all the .txt files in the folder and extract the metadata from them.\n";
    echo "Version: 1.0\n";
    echo "Author: VennDev\n";
    echo "-------------------------------------------\n\n";
}

function callAllTxtFiles(string $directory, callable $callable) : void
{
    foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory)) as $file) {
        if ($file->isDir()) continue;
        if ($file->getExtension() === 'txt') $callable($file->getPathName());
    }
}

function remove_spaces($line)
{
    return preg_replace('/\s+/', '', $line);
}

function update_band_formula($band, $formula, $value)
{
    global $band_formulas;
    !isset($band_formulas[$band]) ? $band_formulas[$band] = [] : $band_formulas[$band][$formula] = $value;;
}

callAllTxtFiles($dir, function($path) use (&$band_formulas, &$sun_elevation_angle)
{
    $file = fopen($path, 'r');

    while (!feof($file)) {
        $line = fgets($file);

        if (preg_match('/\s*(REFLECTANCE_MULT_BAND_([^=]+))\s*=([^=]+)\s*/', $line, $matches)) {
            $name_band = remove_spaces($matches[2]);
            $band_formulas[$name_band]['DN'] = $name_band;
            $band_formulas[$name_band]['Gain'] = remove_spaces($matches[3]);
        }

        if (preg_match('/\s*(REFLECTANCE_ADD_BAND_([^=]+))\s*=([^=]+)\s*/', $line, $matches)) {
            $name_band = remove_spaces($matches[2]);
            $band_formulas[$name_band]['DN'] = $name_band;
            $band_formulas[$name_band]['Bias'] = remove_spaces($matches[3]);
        }

        if (preg_match('/\s*(SUN_ELEVATION)\s*=\s*([^=]+)\s*/', $line, $matches)) $sun_elevation_angle = remove_spaces($matches[2]);
    }

    fclose($file);
});

showIntro();

foreach ($band_formulas as $band => $formula) {
    foreach ($mathLists as $math) {
        $realmath = $math;
        $math = str_replace('DN', "B" . $formula['DN'], $math);
        $math = str_replace('Gain', $formula['Gain'], $math);
        $math = str_replace('Bias', $formula['Bias'], $math);
        $math = str_replace('sun elevation angle', $sun_elevation_angle, $math);
        echo "--------------------------------------------------\n";
        echo "\t\tBand " . $formula['DN'] .  "\n> " . $realmath . "\n==> " . $math . "\n";
        echo "--------------------------------------------------\n\n";
    }
}

showIntro();