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

// Required includes
// End Required includes

if (isset($_GET['uid']) && is_numeric($_GET['uid'])) {
    $uid = $_GET['uid'];
} else if (isset($_GET['u']) && is_numeric($_GET['u'])) {
    $uid = $_GET['u'];
}

if (isset($_GET['h']) && is_md5($_GET['h'])) {
    $key = $_GET['h'];
}

if (isset($_GET['resend']) && isset($uid)) {

    if (email_send_user_confirmation($uid)) {

        html_draw_top(sprintf('title=%s', gettext("Email confirmation")), 'class=window_title');
        html_display_msg(gettext("Email confirmation"), gettext("Confirmation email has been resent."));
        html_draw_bottom();
        exit;
    }
    html_draw_error(gettext("Confirmation email failed to send. Please contact the forum owner to rectify this."));
}

if (!isset($uid) || !isset($key)) {
    html_draw_error(gettext("Required information not found"));
}

$frame_top_target = html_get_top_frame_name();

if (($user = user_get_by_passhash($uid, $key)) !== false) {

    if (perm_user_cancel_email_confirmation($uid)) {

        html_draw_top(sprintf('title=%s', gettext("Email confirmation")), 'class=window_title');
        html_display_msg(gettext("Email confirmation"), gettext("Thank you for confirming your email address. You may now login and start posting immediately."), 'index.php', 'post', array('submit' => gettext("Continue")), false, $frame_top_target, 'center');
        html_draw_bottom();

    } else {

        html_draw_top(sprintf("title=%s", gettext("Error")));
        html_display_msg(gettext("Email confirmation"), gettext("Email confirmation has failed, please try again later. If you encounter this error multiple times please contact the forum owner or a moderator for assistance."), 'index.php', 'post', array('submit' => gettext("Continue")), false, $frame_top_target, 'center');
        html_draw_bottom();
    }

    html_draw_bottom();

} else {

    html_draw_error(gettext("Required information not found"));
}

?>