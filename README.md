# JWPlayer thumbnail preview generator

A simple but effective command-line tool for generating thumbnails of a video and corresponding VTT file for use within JW Player to generate the toolbar thumbnail previews.

[![Flattr this git repo](http://api.flattr.com/button/flattr-badge-large.png)](https://flattr.com/submit/auto?user_id=acollington&url=https://github.com/amnuts/jwplayer-thumbnail-preview-generator&title=JWPlayer%20thumbnail%20preview%20generator&language=&tags=github&category=software)

### Requirements

FFmpeg is required to generate the thumbnails.  If you have libav-tools install and the binary is a different name to ffmpeg (or even if you have ffmpeg but installed in a different location) then you can modify the `$params` information to point to your particular ffmpeg install.

PHP 5.4+ is required as the code uses short array syntax and the CallbackFilterIterator.

### Getting started

There are two ways to getting started using this script:

1. Simply to copy/paste or download the thumbnails.php to your server
2. Install via composer by running the command:
```bash
composer require amnuts/jwplayer-thumbnail-preview-generator
```

### How to use the generator

#### Simplest example

Typical usage would look like:

```bash
php thumbnails.php -i "/input/video.mp4"
```

This would generate the thumbnails for the video given coalesced into one file (a sprite sheet) and the required VTT file that specifies which thumbnail to display and where in the sprite sheet it is located.

#### Verbose output

If you do not wish to generate a sprite sheet then include the `-v` switch to generate verbose output - ie, all the thumbnails are separate files and the VTT file points to each.

```bash
php thumbnails.php -i "/input/video.mp4" -v
```

It is recommended that you use the default coalesced file as this is more optimal when loading than individual images.

#### Changing output directory

You can change the output directory with the `-o` switch:

```bash
php thumbnails.php -i "/input/video.mp4" -o "/output/directory"
```

This will write the images and VTT file in the provided directory (default is just to write into the same directory as the generator script).

#### Change time between thumbnails

To alter the default time between thumbnails use the `-t` switch with the number of seconds you'd like between each:

```bash
php thumbnails.php -i "/input/video.mp4" -t 30
```

That will generate one thumbnail for every 30 seconds of video.

#### Change thumbnail width

To change the width of the thumbnail use the `-w` switch with the size in pixels:

```bash
php thumbnails.php -i "/input/video.mp4" -w 75
```

That will generate thumbnails that are 75 pixels in width.  The height is automatic and proportional to the video size.

#### Generate poster image

The tool also provides the ability to generate a poster file of the video from a random frame of the video at the same time it's generating the thumbnails.  To do this, use the `-p` switch:

```bash
php thumbnails.php -i "/input/video.mp4" -p
```

### How to include in JW Player

The code you would use for JW Player would be something like:

```html
<div id="video">video loading...</div>
<script>
    jwplayer("video").setup({
        playlist: [{
            file: "/input/video.mp4",
            image: "poster.jpg",
            tracks: [{
                file: "thumbnails.vtt",
                kind: "thumbnails"
            }]
        }]
    });
</script>
```

# License

MIT: http://acollington.mit-license.org/
