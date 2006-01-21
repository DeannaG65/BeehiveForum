<?php

/*======================================================================
Copyright Project BeehiveForum 2002

This file is part of BeehiveForum.

BeehiveForum is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

BeehiveForum is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Beehive; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307
USA
======================================================================*/

/* $Id: start_main.php,v 1.14 2006-01-21 23:40:35 decoyduck Exp $ */

// An example of what can be done with start_main.php
// As used on: http://www.tehforum.net/forum/

// Compress the output
include_once(BH_INCLUDE_PATH. "gzipenc.inc.php");

// Enable the error handler
include_once(BH_INCLUDE_PATH. "errorhandler.inc.php");

// Installation checking functions
include_once(BH_INCLUDE_PATH. "install.inc.php");

// Check that Beehive is installed correctly
check_install();

include_once(BH_INCLUDE_PATH. "attachments.inc.php");
include_once(BH_INCLUDE_PATH. "form.inc.php");
include_once(BH_INCLUDE_PATH. "format.inc.php");
include_once(BH_INCLUDE_PATH. "header.inc.php");
include_once(BH_INCLUDE_PATH. "html.inc.php");
include_once(BH_INCLUDE_PATH. "lang.inc.php");
include_once(BH_INCLUDE_PATH. "logon.inc.php");
include_once(BH_INCLUDE_PATH. "perm.inc.php");
include_once(BH_INCLUDE_PATH. "session.inc.php");
include_once(BH_INCLUDE_PATH. "user.inc.php");

// Check we're logged in correctly

if (!$user_sess = bh_session_check()) {
    $request_uri = rawurlencode(get_request_uri());
    $webtag = get_webtag($webtag_search);
    header_redirect("./logon.php?webtag=$webtag&final_uri=$request_uri");
}

// Check we have a webtag

if (!$webtag = get_webtag($webtag_search)) {
    $request_uri = rawurlencode(get_request_uri());
    header_redirect("./forums.php?webtag_search=$webtag_search&final_uri=admin.php%3Fpage%3D$request_uri");
}

// User's UID and LOGON for use later in the script.

$uid = bh_session_get_value('UID');
$logon = bh_session_get_value('LOGON');

// Function to format the display of people's names.

function formatname($filename)
{
    return ucfirst(strtolower($filename));
}

// Where are the images stored?

$images_dir = "forums/$webtag/images/forumites";

// Initialise the array to store the images in

$images_array = array();

// Uploading an image?

if (isset($_POST['upload']) && $uid > 0) {

    if (isset($_FILES['userimage']['tmp_name']) && @is_readable($_FILES['userimage']['tmp_name'])) {

        $tempfile = $_FILES['userimage']['tmp_name'];
        $filepath = "$images_dir/$logon";

        if ($image_data = getimagesize($tempfile)) {

            if (@move_uploaded_file($tempfile, $filepath)) {

                if (attachment_create_thumb($filepath, 300, 300)) {

                    unlink($filepath);
                    rename("$filepath.thumb", $filepath);

                    html_draw_top();

                    echo "<h1>Uploaded Image</h1>\n";
                    echo "<div class=\"image\">\n";
                    echo "<p><div align=\"center\"><a href=\"javascript:void(0);\" onclick=\"openProfileByLogon('$logon', '$webtag')\"><img src=\"$images_dir/$logon\" border=\"0\" alt=\"", formatname($logon), "\" title=\"", formatname($logon), "\" /></a></div></p>\n";
                    echo "<p><div align=\"center\"><a href=\"javascript:void(0);\" onclick=\"openProfileByLogon('$logon', '$webtag')\">", formatname($logon), "</a></div></p>\n";
                    echo "<p><div align=\"center\">[<a href=\"{$HTTP_SERVER_VARS['PHP_SELF']}\">Random Image</a> | <a href=\"{$HTTP_SERVER_VARS['PHP_SELF']}?gallery\">Gallery</a> | <a href=\"?upload\">Upload different image</a>]</div></p>\n";
                    echo "</div>\n";

                    html_draw_bottom();
                    exit;
                }
            }
        }

        html_draw_top();
        echo "<h1>Error</h1>\n";
        echo "<p>Something went wrong. You'll be needing to try again.</p>";
        html_draw_bottom();
    }
}

// Get the files here

if (@$dir = opendir($images_dir)) {

    while(($file = readdir($dir)) !== false) {

        if ($file != '.' && $file != '..') {

            $images_array[] = $file;
        }
    }

    closedir($dir);
}

// Sort the files by name ASC

sort($images_array);

// Draw the HTML header

html_draw_top('openprofile.js');

// Delete a selected image

if (isset($_GET['delete'])) {

    $image = basename($_GET['delete']);

    if (perm_has_admin_access() || strtolower($image) == strtolower($logon)) {

        echo "<h1>Delete Image</h1>\n";
        echo "<div class=\"image\">\n";
        echo "<p><div align=\"center\"><a href=\"javascript:void(0);\" onclick=\"openProfileByLogon('$image', '$webtag')\"><img src=\"$images_dir/$image\" border=\"0\" alt=\"", formatname($image), "\" title=\"", formatname($image), "\" /></a></div></p>\n";
        echo "<p><div align=\"center\"><a href=\"javascript:void(0);\" onclick=\"openProfileByLogon('$image', '$webtag')\">", formatname($image), "</a></div></p>\n";
        echo "<p><div align=\"center\">[<a href=\"{$HTTP_SERVER_VARS['PHP_SELF']}?confirm_delete=$image\">Delete</a> | <a href=\"{$HTTP_SERVER_VARS['PHP_SELF']}?gallery\">Cancel</a>]</div></p>\n";
        echo "</div>\n";

        html_draw_bottom();
        exit;
    }

}elseif (isset($_GET['confirm_delete'])) {

    $image = basename($_GET['confirm_delete']);

    if (perm_has_admin_access() || strtolower($image) == strtolower($logon)) {

        if (@file_exists("forums/$webtag/images/forumites/$image")) {

            unlink("forums/$webtag/images/forumites/$image");
        }
    }
}

// Upload / Show Gallery / Random image

if (isset($_GET['upload']) && $uid > 0) {

    echo "<h1>Upload Image</h1>\n";
    echo "<br />\n";
    echo "<form enctype=\"multipart/form-data\" method=\"post\" action=\"start_main.php\">\n";
    echo "  ", form_field("userimage", "", 40, 0, "file"), "\n";
    echo "  ", form_submit("upload", $lang['upload']), "\n";
    echo "  ", form_submit("cancel", $lang['cancel']), "\n";
    echo "</form>\n";

    echo "<p>Some rules:</p>\n";
    echo "<ol>\n";
    echo "  <li>You only get one image, which is stored as your forum logon.</li>\n";
    echo "  <li>Image will be automatically resized down to a max of 300x300px.</li>\n";
    echo "</ol>\n";

}elseif (isset($HTTP_GET_VARS['gallery'])) {

    echo "<h1>Convicts Gallery</h1>\n";
    echo "<div align=\"center\">\n";
    echo "<table border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"500\">\n";

    foreach($images_array as $key => $image) {

        echo "  <tr>\n";
        echo "    <td align=\"center\">\n";
        echo "      <p><a href=\"javascript:void(0);\" onclick=\"openProfileByLogon('$image', '$webtag')\"><img src=\"$images_dir/$image\" border=\"0\" alt=\"", formatname($image), "\" title=\"", formatname($image), "\" /></a></p>\n";
        echo "      <p class=\"bodytext\"><a href=\"javascript:void(0);\" onclick=\"openProfileByLogon('$image', '$webtag')\">", formatname($image), "</a></p>\n";

        if (perm_has_admin_access() || strtolower($image) == strtolower($logon)) {
            echo "      <p class=\"bodytext\"><a href=\"{$HTTP_SERVER_VARS['PHP_SELF']}?delete=$image\">[Delete]</a></p>\n";
        }

        echo "    </td>\n";
        echo "  </tr>\n";
    }

    echo "</table>\n";
    echo "<p><div align=\"center\">[<a href=\"{$HTTP_SERVER_VARS['PHP_SELF']}\">Random Image</a> | <a href=\"{$HTTP_SERVER_VARS['PHP_SELF']}?gallery\">Gallery</a>";
    echo $uid > 0 ? "| <a href=\"?upload\">Upload an image</a>" : "";
    echo "]</div></p>\n";
    echo "</div>\n";

}elseif (is_array($images_array) && sizeof($images_array) > 0) {

    $image = $images_array[array_rand($images_array)];

    echo "<h1>Some random person</h1>\n";
    echo "<div class=\"image\">\n";
    echo "<p><div align=\"center\"><a href=\"javascript:void(0);\" onclick=\"openProfileByLogon('$image', '$webtag')\"><img src=\"$images_dir/$image\" border=\"0\" alt=\"", formatname($image), "\" title=\"", formatname($image), "\" /></a></div></p>\n";
    echo "<p><div align=\"center\"><a href=\"javascript:void(0);\" onclick=\"openProfileByLogon('$image', '$webtag')\">", formatname($image), "</a></div></p>\n";
    echo "<p><div align=\"center\">[<a href=\"{$HTTP_SERVER_VARS['PHP_SELF']}\">Random Image</a> | <a href=\"{$HTTP_SERVER_VARS['PHP_SELF']}?gallery\">Gallery</a>";
    echo $uid > 0 ? "| <a href=\"?upload\">Upload an image</a>" : "";
    echo "]</div></p>\n";
    echo "</div>\n";

}else {

    echo "<h1>Convicts Gallery</h1>\n";

    if ($uid > 0) {
        echo "<p><div align=\"center\">[<a href=\"?upload\">Upload an image</a>]</div></p>\n";
    }else {
        echo "<p><div align=\"center\">Gallery is empty</div></p>\n";
    }
}

html_draw_bottom();

?>