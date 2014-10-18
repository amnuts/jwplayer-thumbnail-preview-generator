<?php

/**
 * Video thumbnail preview generator
 *
 * A simple but effective command-line tool for generating thumbnails of a
 * video and corresponding VTT file for use within JW Player.
 *
 * This script requires that ffmpeg is installed on your system.
 *
 * @author Andrew Collington, andy@amnuts.com
 * @license MIT, http://acollington.mit-license.org/
 */

$params = [
    'ffmpeg'      => 'ffmpeg', // the ffmpeg command - full path if needs be
    'input'       => null,     // input video file - specified as command line parameter
    'output'      => __DIR__,  // The output directory
    'timespan'    => 10,       // seconds between each thumbnail
    'thumbWidth'  => 120,      // thumbnail width
    'spriteWidth' => 10        // number of thumbnails per row in sprite sheet
];

$commands = [
    'details' => $params['ffmpeg'] . ' -i %s 2>&1',
    'poster'  => $params['ffmpeg'] . ' -ss %d -i %s -y -vframes 1 "%s/poster.jpg" 2>&1',
    'thumbs'  => $params['ffmpeg'] . ' -ss %0.04f -i %s -y -an -sn -vsync 0 -q:v 5 -threads 1 '
        . '-vf scale=%d:-1,select="not(mod(n\\,%d))" "%s/thumbnails/%s-%%04d.jpg" 2>&1'
];

if (PHP_SAPI !== 'cli') {
    exit('Sorry, you can only use this via the command line.');
}

$opts = getopt('i:o:t:w:hvpd');
if (isset($opts['h']) || !isset($opts['i'])) {
    $usage =<<< EOT
Usage: php thumbnails.php -i "/input/video.mp4" [-o "/output/directory"] [-t <number>] [-w <number>] [-v] [-p] [-d]

    -i: The input file to be used.
    -o: The output directory where the thumbnails and vtt file will be saved
    -t: The time span (in seconds) between each thumbnail (default, {$params['timespan']})
    -w: The max width of the thumbnail (default, {$params['thumbWidth']})
    -v: Verbose - don't coalesce the thumbnails into one image
    -p: Generate poster image from a random frame in the video
    -d: Delete any previous thumbnails that match before generating new images
    -h: Show this message

EOT;
    exit($usage);
}

// process input parameters

$params['input'] = escapeshellarg($opts['i']);
if (isset($opts['o'])) {
    $params['output'] = realpath($opts['o']);
}
if (isset($opts['t']) && (int)$opts['t']) {
    $params['timespan'] = $opts['t'];
}
if (isset($opts['w']) && (int)$opts['w']) {
    $params['thumbWidth'] = $opts['w'];
}

// sanity checks

if (!is_readable($opts['i'])) {
    exit("Cannot read the input file '{$opts['i']}'");
}
if (!is_writable($params['output'])) {
    exit("Cannot write to output directory '{$opts['o']}'");
}
if (!file_exists($params['output'] . '/thumbnails')) {
    if (!mkdir($params['output'] . '/thumbnails')) {
        exit("Could not create thumbnail output directory '{$params['output']}/thumbnails'");
    }
}
$details = shell_exec(sprintf($commands['details'], $params['input']));
if ($details === null || !preg_match('/^(?:\s+)?ffmpeg version ([^\s,]*)/i', $details)) {
    exit("Cannot find ffmpeg - try specifying the path in the \$params variable");
}

// determine some values we need

$time = $tbr = [];
preg_match('/Duration: ((\d+):(\d+):(\d+))\.\d+, start: ([^,]*)/is', $details, $time);
preg_match('/\b(\d+(?:\.\d+)?) tbr\b/', $details, $tbr);
$duration = ($time[2] * 3600) + ($time[3] * 60) + $time[4];
$start = $time[5];
$tbr = $tbr[1];

// generate random poster if required

if (isset($opts['p'])) {
    shell_exec(sprintf($commands['poster'], rand(1, $duration - 1), $opts['i'], $params['output']));
}

// generate all thumbnail images

$name = strtolower(substr(basename($opts['i']), 0, strrpos(basename($opts['i']), '.')));
if (isset($opts['d'])) {
    $files = glob("{$params['output']}/thumbnails/{$name}-*.jpg");
    foreach ($files as $f) {
        unlink($f);
    }
}
shell_exec(sprintf($commands['thumbs'],
    $start + .0001, $params['input'], $params['thumbWidth'],
    $params['timespan'] * $tbr, $params['output'], $name
));
$files = glob("{$params['output']}/thumbnails/{$name}-*.jpg");
if (!($total = count($files))) {
    exit("Could not find any thumbnails matching '{$params['output']}/thumbnails/{$name}-*.jpg'");
}

// create coalesce image if needs be

if (!isset($opts['v'])) {
    $thumbsAcross = min($total, $params['spriteWidth']);
    $sizes = getimagesize($files[0]);
    $rows = ceil($total/$thumbsAcross);
    $w = $sizes[0] * $thumbsAcross;
    $h = $sizes[1] * $rows;
    $coalesce = imagecreatetruecolor($w, $h);
}

// generate vtt file, merge thumbnails if needs be

$vtt = "WEBVTT\n\n";
for ($rx = $ry = $s = $f = 0; $f < $total; $f++) {
    $t1 = sprintf('%02d:%02d:%02d.000', ($s / 3600), ($s / 60 % 60), $s % 60);
    $s += $params['timespan'];
    $t2 = sprintf('%02d:%02d:%02d.000', ($s / 3600), ($s / 60 % 60), $s % 60);
    if (isset($opts['v'])) {
        $vtt .= "{$t1} --> {$t2}\nthumbnails/" . basename($files[$f]);
    } else {
        if ($f && !($f % $thumbsAcross)) {
            $rx = 0;
            ++$ry;
        }
        imagecopymerge($coalesce, imagecreatefromjpeg($files[$f]), $rx * $sizes[0], $ry * $sizes[1], 0, 0, $sizes[0], $sizes[1], 100);
        $vtt .= sprintf("%s --> %s\nthumbnails.jpg#xywh=%d,%d,%d,%d", $t1, $t2, $rx++ * $sizes[0], $ry * $sizes[1],  $sizes[0], $sizes[1]);
    }
    $vtt .= "\n\n";
}

// tidy up

if (!isset($opts['v'])) {
    imagejpeg($coalesce, "{$params['output']}/thumbnails.jpg", 90);
    for ($s = 0, $f = 0; $f < $total; $f++) {
        unlink($files[$f]);
    }
}

file_put_contents("{$params['output']}/thumbnails.vtt", $vtt);
exit("Process completed. Check the output directory '{$params['output']}' for VTT file and images");
