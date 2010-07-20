<?php

/*======================================================================
Copyright Project Beehive Forum 2002

This file is part of Beehive Forum.

Beehive Forum is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
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

/* $Id$ */

// Set the default timezone

date_default_timezone_set('UTC');

// Constant to define where the include files are

define("BH_INCLUDE_PATH", "include/");

// Server checking functions

include_once(BH_INCLUDE_PATH. "server.inc.php");

// Caching functions

include_once(BH_INCLUDE_PATH. "cache.inc.php");

// Disable PHP's register_globals

unregister_globals();

// Disable caching if on AOL

cache_disable_aol();

// Disable caching if proxy server detected.

cache_disable_proxy();

// Compress the output

include_once(BH_INCLUDE_PATH. "gzipenc.inc.php");

// Enable the error handler

include_once(BH_INCLUDE_PATH. "errorhandler.inc.php");

// Installation checking functions

include_once(BH_INCLUDE_PATH. "install.inc.php");

// Check that Beehive is installed correctly

check_install();

// Multiple forum support

include_once(BH_INCLUDE_PATH. "forum.inc.php");

// Fetch Forum Settings

$forum_settings = forum_get_settings();

// Fetch Global Forum Settings

$forum_global_settings = forum_get_global_settings();

include_once(BH_INCLUDE_PATH. "format.inc.php");
include_once(BH_INCLUDE_PATH. "header.inc.php");
include_once(BH_INCLUDE_PATH. "html.inc.php");
include_once(BH_INCLUDE_PATH. "lang.inc.php");
include_once(BH_INCLUDE_PATH. "logon.inc.php");
include_once(BH_INCLUDE_PATH. "messages.inc.php");
include_once(BH_INCLUDE_PATH. "session.inc.php");
include_once(BH_INCLUDE_PATH. "user.inc.php");

// Get Webtag

$webtag = get_webtag();

// Check we're logged in correctly

if (!$user_sess = bh_session_check()) {
    $request_uri = rawurlencode(get_request_uri());
    header_redirect("logon.php?webtag=$webtag&final_uri=$request_uri");
}

// Check to see if the user is banned.

if (bh_session_user_banned()) {

    html_user_banned();
    exit;
}

// Check to see if the user has been approved.

if (!bh_session_user_approved()) {

    html_user_require_approval();
    exit;
}

// Check we have a webtag

if (!forum_check_webtag_available($webtag)) {
    $request_uri = rawurlencode(get_request_uri(false));
    header_redirect("forums.php?webtag_error&final_uri=$request_uri");
}

// Load language file

$lang = load_language_file();

// Check that we have access to this forum

if (!forum_check_access_level()) {
    $request_uri = rawurlencode(get_request_uri());
    header_redirect("forums.php?webtag_error&final_uri=$request_uri");
}

// Get the start page

if (($top_banner = forum_get_setting('top_banner'))) {

    // Get the start page CSS
    
    if (($top_banner_css = forum_get_setting('top_banner_css'))) {

        html_draw_top("inline_css=$top_banner_css");
        echo message_apply_formatting($top_banner);
        html_draw_bottom();
        exit;
    
    } else {
        
        html_draw_top();
        echo message_apply_formatting($top_banner);
        html_draw_bottom();
        exit;
    }

}else {

    // Output the generic top banner
    
    html_draw_top();
    
    echo "<body style=\"margin: 0px 10px 0px 0px; background-color: rgb(36,55,74);\">\n";
    echo "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">\n";
    echo "  <tr>\n";
    echo "    <td align=\"left\" valign=\"middle\">\n";
    echo "      <a href=\"http://www.beehiveforum.net/\" target=\"_blank\">\n";
    echo "        <img src=\"", style_image('beehive_logo.png'), "\" border=\"0\" alt=\"Beehive Logo\" />\n";
    echo "      </a>\n";
    echo "    </td>\n";
    echo "    <td align=\"right\" valign=\"middle\">\n";
    echo "      <a href=\"http://sourceforge.net\" target=\"_top\">\n";
    echo "        <img src=\"http://sourceforge.net/sflogo.php?group_id=50772&amp;type=2\" width=\"125\" height=\"37\" border=\"0\" alt=\"SourceForge.net Logo\" />\n";
    echo "      </a>";
    echo "    </td>\n";
    echo "  </tr>\n";
    echo "</table>\n";
    
    html_draw_bottom();
}

?>