<?php

/*======================================================================
Copyright Project Beehive Forum 2002

This file is part of Beehive Forum.

Beehive Forum is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

Beehive Forum is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Beehive; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307
USA
======================================================================*/

// We shouldn't be accessing this file directly.
if (basename($_SERVER['SCRIPT_NAME']) == basename(__FILE__)) {
    header("Request-URI: ../index.php");
    header("Content-Location: ../index.php");
    header("Location: ../index.php");
    exit;
}

require_once BH_INCLUDE_PATH. 'forum.inc.php';

function image_enable_transparency($im)
{
    if (!function_exists('imageantialias')) return $im;
    if (!function_exists('imagealphablending')) return $im;
    if (!function_exists('imagesavealpha')) return $im;

    imageantialias($im, true);
    imagealphablending($im, false);
    imagesavealpha($im, true);

    $im_width  = imagesx($im);
    $im_height = imagesy($im);

    $transparent = imagecolorallocatealpha($im, 255, 255, 255, 0);

    for ($x = 0; $x < $im_width; $x++) {
        for ($y = 0;$y < $im_height; $y++) {
            imagesetpixel($im, $x, $y, $transparent);
        }
    }

    return $im;
}

function image_resize($src, $dest, $max_width = 150, $max_height = 150)
{
    // Check attachment thumbnails are enabled.
    if (forum_get_setting('attachment_thumbnails', 'N')) return false;

    // Get the thumbnail method.
    $attachment_thumbnail_method = forum_get_setting('attachment_thumbnail_method');

    // Different function for each method.
    switch ($attachment_thumbnail_method) {

        // Use external ImageMagick binary.
        case ATTACHMENT_THUMBNAIL_IMAGEMAGICK:

            return image_resize_imagemagick($src, $dest, $max_width, $max_height);
            break;

        // Use PHP's GD image library.
        default:

            return image_resize_gd($src, $dest, $max_width, $max_height);
            break;
    }
}

function image_resize_imagemagick($src, $dest, $max_width = 150, $max_height = 150)
{
    if (!is_numeric($max_width)) return false;
    if (!is_numeric($max_height)) return false;

    // Get the imagemagick path from settings.
    if (!($imagemagick_path = forum_get_global_setting('attachment_imagemagick_path'))) return false;
    
    // Check that imagemagick is executable.
    if (!is_executable($imagemagick_path)) return false;
    
    // Check the file exists and we can get some image data from it.
    if (!file_exists($src) || !($image_info = @getimagesize($src))) return false;
    
    // Check the destination directory is writable.
    if (!is_writable(dirname($dest))) return false;

    // If we're dealing with a GIF image, we need to
    // process it to correctly resize all the frames it
    // might contain - in the case of animated gifs.
    if (($image_info[2] == IMAGETYPE_GIF)) {

        // Resize the gif, dropping all but the first frame.
        exec(sprintf(
            'convert %s[0] -resize "%dx%d>" %s', 
            escapeshellarg($src),
            $max_width,
            $max_height,
            escapeshellarg($dest)
        ));

    } else {

        // It's not a gif, so carry on and resize the image.
        exec(sprintf(
            'convert %s -resize "%dx%d>" %s', 
            escapeshellarg($src),
            $max_width,
            $max_height,
            escapeshellarg($dest)
        ));
    }

    // if imagemagick fails, it won't create the final image
    // so we test that exists before returning the result.
    if (!file_exists($dest)) return false;

    // Success
    return true;
}

function image_resize_gd($src, $dest, $max_width, $max_height)
{
    if (!is_numeric($max_width)) return false;
    if (!is_numeric($max_height)) return false;

    // Required PHP image create from functions
    $required_read_functions  = array(
        1 => 'imagecreatefromgif',
        2 => 'imagecreatefromjpeg',
        3 => 'imagecreatefrompng'
    );

    // Required PHP image output functions
    $required_write_functions = array(
        1 => 'imagegif',
        2 => 'imagejpeg',
        3 => 'imagepng'
    );

    // Required GD read support
    $required_read_support = array(
        1 => array('GIF Read Support'),
        2 => array('JPG Support', 'JPEG Support'),
        3 => array('PNG Support')
    );

    // Required GD write support
    $required_write_support = array(
        1 => array('GIF Create Support'),
        2 => array('JPG Support', 'JPEG Support'),
        3 => array('PNG Support')
    );

    // Check the file exists and we can get some image data from it.
    if (!file_exists($src) || !($image_info = @getimagesize($src))) return false;
    
    // Check the destination directory is writable.
    if (!is_writable(dirname($dest))) return false;    

    // Check the gd_info function exists
    if (!function_exists('gd_info') || !($gd_info = gd_info())) return false;

    // Check 1: Is the image format in our list of supported image types.
    if (!isset($required_read_support[$image_info[2]])) return false;
    if (!isset($required_write_support[$image_info[2]])) return false;

    // Check 2: Check gd_info function indicates support for the image type.
    if (!image_check_gd_info_key($required_read_support[$image_info[2]])) return false;
    if (!image_check_gd_info_key($required_write_support[$image_info[2]])) return false;

    // Check 3: Even if GD says it supports the image format check the php functions actually exist!
    if (!function_exists($required_read_functions[$image_info[2]])) return false;
    if (!function_exists($required_write_functions[$image_info[2]])) return false;

    if (!($src_im = @$required_read_functions[$image_info[2]]($src))) return false;

    $target_width  = $image_info[0];
    $target_height = $image_info[1];

    while ($target_width > $max_width || $target_height > $max_height) {

        $target_width--;
        $target_height = $target_width * ($image_info[1] / $image_info[0]);
    }

    if (strcmp($gd_info['GD Version'], '2.0') > -1) {

        $dest_im = imagecreatetruecolor($target_width, $target_height);

        $dest_im = image_enable_transparency($dest_im);

        imagecopyresampled($dest_im, $src_im, 0, 0, 0, 0, $target_width, $target_height, $image_info[0], $image_info[1]);

    } else {

        $dest_im = imagecreate($target_width, $target_height);

        $dest_im = image_enable_transparency($dest_im);

        imagecopyresized($dest_im, $src_im, 0, 0, 0, 0, $target_width, $target_height, $image_info[0], $image_info[1]);
    }

    return $required_write_functions[$image_info[2]]($dest_im, $dest);
}

function image_check_gd_info_key($image_types_array)
{
    if (!function_exists('gd_info') || !($gd_info = gd_info())) return false;

    if (!is_array($image_types_array)) return false;

    foreach ($image_types_array as $gd_info_key) {
        if (isset($gd_info[$gd_info_key]) && ($gd_info[$gd_info_key])) return true;
    }

    return false;
}
  
?>