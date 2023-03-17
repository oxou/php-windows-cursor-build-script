<?php

// Copyright (C) Nurudin Imsirovic <github.com/oxou>
// Cursor build script for Windows platforms
// Created: 2023-03-16 07:01 PM
// Updated: 2023-03-17 07:59 PM

$imagemagick_exe = "C:\\env\\magick.exe";

// Error checks
if (!file_exists($imagemagick_exe))
    die("ImageMagick not found. Check make.php:8\n");

if (is_dir($imagemagick_exe))
    die("ImageMagick path points to a directory. Check make.php:8\n");

if (!file_exists(__DIR__ . "/out"))
    mkdir(__DIR__ . "/out");

// Helper functions
function __write_cursor_hotspot($file, $x, $y) {
    $data = file_get_contents($file);

    // Fix +1 offset
    $x = abs($x - 1);
    $y = abs($y - 1);

    // Overwrite X and Y position
    $data[0x0A] = hex2bin(str_pad(dechex($x), 2, '0', STR_PAD_LEFT));
    $data[0x0C] = hex2bin(str_pad(dechex($y), 2, '0', STR_PAD_LEFT));

    file_put_contents($file, $data);
}

function __build_cursor($name, $type = "white") {
    $cursor_path = __DIR__ . "/src/" . $type . '/' . $name . ".png";
    $output_path = __DIR__ . "/out/" . $type . '/' . $name . ".cur";

    if (!file_exists($cursor_path)) {
        echo "\x1B[31mError: __build_cursor() cannot find cursor $name of type $type.\x1B[0m\n";
        return -1;
    }

    $imagemagick_exe = $GLOBALS["imagemagick_exe"];
    exec("\"$imagemagick_exe\" \"$cursor_path\" -transparent #F0F \"$output_path\"");
    $hotspot = $GLOBALS["hotspots_list"][$name];
    __write_cursor_hotspot($output_path, $hotspot['x'], $hotspot['y']);
}

// Load hotspots file
$hotspots = file_get_contents(__DIR__ . "/src/hotspots.txt");
$hotspots = str_replace(
    array("\r\n", "\r"),
    array("\n", ''),
    $hotspots
);
$hotspots = explode("\n", $hotspots);

$hotspots_list = [];

// Parse hotspots file
foreach ($hotspots as $line) {
    if (empty($line) || $line[0] === '#')
        continue;

    $parts = explode(',', $line);
    $hotspots_list[$parts[0]] = [];
    $hotspots_list[$parts[0]]['x'] = $parts[1];
    $hotspots_list[$parts[0]]['y'] = $parts[2];
}

// Make cursors
$types = [
    "white",
    "black"
];

$cursors = [
    "alternate-select",
    "busy",
    "diagonal-resize-1",
    "diagonal-resize-2",
    "help-select",
    "horizontal-resize",
    "link-select",
    "move",
    "normal-select",
    "precision-select",
    "text-select",
    "unavailable",
    "vertical-resize",
    "working-in-background"
];

$cursors_count = sizeof($cursors) * sizeof($types);
$cursor_count = 0;

foreach ($types as $type) {
    $exists = file_exists(__DIR__ . "/out/" . $type);

    if ($exists == false) {
        $status = @mkdir(__DIR__ . "/out/" . $type);
        if ($status === false) {
            echo "\x1B[31mError: Building directory for cursor type $type failed.\x1B[0m\n";
            continue;
        }
    }

    foreach ($cursors as $cursor) {
        __build_cursor($cursor, $type);
        printf("\rBuilding cursor %s out of %s", ++$cursor_count, $cursors_count);
    }
}

?>