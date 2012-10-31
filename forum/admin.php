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

// Bootstrap
require_once 'boot.php';

// Includes required by this page.
require_once BH_INCLUDE_PATH. 'cache.inc.php';
require_once BH_INCLUDE_PATH. 'constants.inc.php';
require_once BH_INCLUDE_PATH. 'format.inc.php';
require_once BH_INCLUDE_PATH. 'header.inc.php';
require_once BH_INCLUDE_PATH. 'html.inc.php';
require_once BH_INCLUDE_PATH. 'lang.inc.php';
require_once BH_INCLUDE_PATH. 'logon.inc.php';
require_once BH_INCLUDE_PATH. 'session.inc.php';

// Don't cache this page
cache_disable();

// Check we're logged in correctly
if (!session::logged_in()) {
    html_guest_error();
}

// Check we have Admin / Moderator access
if ((!session::check_perm(USER_PERM_ADMIN_TOOLS, 0) && !session::check_perm(USER_PERM_FORUM_TOOLS, 0, 0) && !session::get_folders_by_perm(USER_PERM_FOLDER_MODERATE))) {
    html_draw_error(gettext("You do not have permission to use this section."));
}

// Perform additional admin login.
admin_check_credentials();

// Get the user's saved left frame width.
if (($left_frame_width = session::get_value('LEFT_FRAME_WIDTH')) === false) {
    $left_frame_width = 280;
}

// Prevent the frame width from being less than 100px
$left_frame_width = max(100, $left_frame_width);

// Output starts here
html_draw_top('frame_set_html', 'pm_popup_disabled');

$frameset = new html_frameset_cols('admin', "$left_frame_width,*");

if (isset($_GET['page']) && strlen(trim($_GET['page'])) > 0) {

    $requested_page = trim($_GET['page']);

    $available_pages_preg = implode("|^", array_map('preg_quote_callback', get_available_admin_files()));

    if (preg_match("/^$available_pages_preg/u", basename($requested_page)) > 0) {

        $requested_page = href_cleanup_query_keys($requested_page);

        $frameset->html_frame("admin_menu.php?webtag=$webtag", html_get_frame_name('left'));
        $frameset->html_frame($requested_page, html_get_frame_name('right'));

        $frameset->output_html();

        html_draw_bottom(true);
        exit;
    }
}

$frameset->html_frame("admin_menu.php?webtag=$webtag", html_get_frame_name('left'));

if (session::check_perm(USER_PERM_ADMIN_TOOLS, 0)) {
    $frameset->html_frame("admin_users.php?webtag=$webtag", html_get_frame_name('right'));
} else {
    $frameset->html_frame("admin_forums.php?webtag=$webtag", html_get_frame_name('right'));
}

$frameset->output_html();

html_draw_bottom(true);

?>