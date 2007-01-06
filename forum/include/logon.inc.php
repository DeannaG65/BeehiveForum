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

/* $Id: logon.inc.php,v 1.53 2007-01-06 18:51:54 decoyduck Exp $ */

// We shouldn't be accessing this file directly.

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header("Request-URI: ../index.php");
    header("Content-Location: ../index.php");
    header("Location: ../index.php");
    exit;
}

include_once(BH_INCLUDE_PATH. "forum.inc.php");
include_once(BH_INCLUDE_PATH. "lang.inc.php");

function perform_logon($logon_main)
{
    $lang = load_language_file();

    $webtag = get_webtag($webtag_search);

    // Retrieve existing cookie data if any

    // Username array

    if (isset($_COOKIE['bh_remember_username']) && is_array($_COOKIE['bh_remember_username'])) {
        $username_array = $_COOKIE['bh_remember_username'];
    }elseif (isset($_COOKIE['bh_remember_username']) && strlen($_COOKIE['bh_remember_username']) > 0) {
        $username_array = explode(",", $_COOKIE['bh_remember_username']);
    }else {
        $username_array = array();
    }

    // Password array

    if (isset($_COOKIE['bh_remember_password']) && is_array($_COOKIE['bh_remember_password'])) {
        $password_array = $_COOKIE['bh_remember_password'];
    }elseif (isset($_COOKIE['bh_remember_password']) && strlen($_COOKIE['bh_remember_password']) > 0) {
        $password_array = explode(",", $_COOKIE['bh_remember_password']);
    }else {
        $password_array = array();
    }

    // Passhash array

    if (isset($_COOKIE['bh_remember_passhash']) && is_array($_COOKIE['bh_remember_passhash'])) {
        $passhash_array = $_COOKIE['bh_remember_passhash'];
    }elseif (isset($_COOKIE['bh_remember_passhash']) && strlen($_COOKIE['bh_remember_passhash']) > 0) {
        $passhash_array = explode(",", $_COOKIE['bh_remember_passhash']);
    }else {
        $passhash_array = array();
    }

    if (isset($_POST['guest_logon'])) {

        if (user_guest_enabled()) {

            bh_setcookie("bh_thread_mode", "1", time() - YEAR_IN_SECONDS);
            bh_setcookie("bh_logon", "1", time() - YEAR_IN_SECONDS);
            bh_session_init(0);
            return true;
        }

    }else if (isset($_POST['user_logon']) && isset($_POST['user_password'])) {

        $logon = _stripslashes($_POST['user_logon']);
        $passw = _stripslashes($_POST['user_password']);

        if (strlen(trim($passw)) > 0) {

            $passh = md5($passw);

        }else {
            
            if (isset($_POST['user_passhash']) && is_md5(_stripslashes($_POST['user_passhash']))) {
                $passh = _stripslashes($_POST['user_passhash']);
            }else {
                return false;
            }
        }

        if ($luid = user_logon($logon, $passh)) {

            bh_setcookie("bh_thread_mode", "1", time() - YEAR_IN_SECONDS);
            bh_setcookie("bh_logon", "1", time() - YEAR_IN_SECONDS);

            bh_session_init($luid);

            // set / update the username and password cookies

            if (defined('BEEHIVEMODE_LIGHT')) {

                if (isset($_POST['remember_user']) && ($_POST['remember_user'] == 'Y')) {

                    bh_setcookie("bh_light_remember_username", $logon, time() + YEAR_IN_SECONDS);
                    bh_setcookie("bh_light_remember_password", $passw, time() + YEAR_IN_SECONDS);

                }else {

                    bh_setcookie("bh_light_remember_username", $logon, time() - YEAR_IN_SECONDS);
                    bh_setcookie("bh_light_remember_password", "", time() - YEAR_IN_SECONDS);
                }

            }else {

                $passw = str_repeat(' ', strlen($passw));

                if (($key = _array_search($logon, $username_array)) !== false) {

                    unset($username_array[$key]);
                    unset($password_array[$key]);
                    unset($passhash_array[$key]);
                }

                array_unshift($username_array, $logon);

                if (isset($_POST['remember_user']) && ($_POST['remember_user'] == 'Y')) {

                    array_unshift($password_array, $passw);
                    array_unshift($passhash_array, $passh);

                }else {

                    array_unshift($password_array, "");
                    array_unshift($passhash_array, "");
                }

                // Remove old 0.7.1 and older cookies

                for ($i = 0; $i < sizeof($username_array); $i++) {

                    bh_setcookie("bh_remember_username[$i]", '', time() - YEAR_IN_SECONDS);
                    bh_setcookie("bh_remember_password[$i]", '', time() - YEAR_IN_SECONDS);
                    bh_setcookie("bh_remember_passhash[$i]", '', time() - YEAR_IN_SECONDS);
                }

                // New format cookies for 0.7.2 for better compatibility with more browsers.

                $username_cookie = implode(",", $username_array);
                $password_cookie = implode(",", $password_array);
                $passhash_cookie = implode(",", $passhash_array);

                // Set the cookies.

                bh_setcookie("bh_remember_username", $username_cookie, time() + YEAR_IN_SECONDS);
                bh_setcookie("bh_remember_password", $password_cookie, time() + YEAR_IN_SECONDS);
                bh_setcookie("bh_remember_passhash", $passhash_cookie, time() + YEAR_IN_SECONDS);
            }

            return true;
        }
    }

    if ($logon_main) return false;

    echo "<div align=\"center\">\n";
    echo "<h2>{$lang['usernameorpasswdnotvalid']}</h2>\n";
    echo "<h2>{$lang['pleasereenterpasswd']}</h2>\n";

    return false;
}

function draw_logon_form($logon_main, $other_logon = false)
{
    global $frame_top_target;
   
    $lang = load_language_file();

    $webtag = get_webtag($webtag_search);

    // Retrieve existing cookie data if any

    // Username array

    if (isset($_COOKIE['bh_remember_username']) && is_array($_COOKIE['bh_remember_username'])) {
        $username_array = $_COOKIE['bh_remember_username'];
    }elseif (isset($_COOKIE['bh_remember_username']) && strlen($_COOKIE['bh_remember_username']) > 0) {
        $username_array = explode(",", $_COOKIE['bh_remember_username']);
    }else {
        $username_array = array();
    }

    // Password array

    if (isset($_COOKIE['bh_remember_password']) && is_array($_COOKIE['bh_remember_password'])) {
        $password_array = $_COOKIE['bh_remember_password'];
    }elseif (isset($_COOKIE['bh_remember_password']) && strlen($_COOKIE['bh_remember_password']) > 0) {
        $password_array = explode(",", $_COOKIE['bh_remember_password']);
    }else {
        $password_array = array();
    }

    // Passhash array

    if (isset($_COOKIE['bh_remember_passhash']) && is_array($_COOKIE['bh_remember_passhash'])) {
        $passhash_array = $_COOKIE['bh_remember_passhash'];
    }elseif (isset($_COOKIE['bh_remember_passhash']) && strlen($_COOKIE['bh_remember_passhash']) > 0) {
        $passhash_array = explode(",", $_COOKIE['bh_remember_passhash']);
    }else {
        $passhash_array = array();
    }

    if (isset($_COOKIE['bh_logon_failed'])) {

        bh_setcookie("bh_logon_failed", "1", time() - YEAR_IN_SECONDS);

        echo "<h2>{$lang['usernameorpasswdnotvalid']}</h2>\n";
        echo "<h2>{$lang['pleasereenterpasswd']}</h2>\n";
    }

    if ($logon_main) {

        echo "  <br />\n";

        if (isset($frame_top_target) && strlen($frame_top_target) > 0) {
            echo "  <form name=\"logonform\" action=\"", get_request_uri(), "\" method=\"post\" target=\"$frame_top_target\">\n";
        }else {
            echo "  <form name=\"logonform\" action=\"", get_request_uri(), "\" method=\"post\" target=\"_top\">\n";
        }

    }else {

        $request_uri = get_request_uri();

        echo "  <h2>Your session has expired. You will need to login again to continue.</h2>\n";
        echo "  <br />\n";

        if (stristr($request_uri, 'logon.php')) {
            if (isset($frame_top_target) && strlen($frame_top_target) > 0) {
                echo "<form name=\"logonform\" method=\"post\" action=\"$request_uri\" target=\"$frame_top_target\">\n";
            }else {
                echo "<form name=\"logonform\" method=\"post\" action=\"$request_uri\" target=\"_top\">\n";
            }
        }else {
            echo "<form name=\"logonform\" method=\"post\" action=\"$request_uri\" target=\"_self\">\n";
        }

        $ignore_keys = array('user_logon', 'user_password', 'user_passhash', 'remember_user', 'webtag');

        echo form_input_hidden_array($_POST, $ignore_keys);
    }

    echo "    ", form_input_hidden('webtag', $webtag), "\n";
    echo "    <table cellpadding=\"0\" cellspacing=\"0\" width=\"265\">\n";
    echo "      <tr>\n";
    echo "        <td align=\"left\">\n";
    echo "          <table class=\"box\" width=\"100%\">\n";
    echo "            <tr>\n";
    echo "              <td align=\"left\" class=\"posthead\">\n";
    echo "                <table class=\"posthead\" width=\"100%\">\n";
    echo "                  <tr>\n";
    echo "                    <td align=\"left\" class=\"subhead\">{$lang['logon']}</td>\n";
    echo "                  </tr>\n";
    echo "                </table>\n";
    echo "                <table class=\"posthead\" width=\"100%\">\n";
    echo "                  <tr>\n";
    echo "                    <td align=\"center\">\n";
    echo "                      <table class=\"posthead\" width=\"95%\">\n";

    if ((sizeof($username_array) > 1) && $other_logon === false) {

        echo "                        <tr>\n";
        echo "                          <td align=\"right\">{$lang['username']}:</td>\n";
        echo "                          <td align=\"left\" nowrap=\"nowrap\">";

        foreach ($username_array as $key => $value) {
            $usernames[$key] = _stripslashes($value);
        }

        reset($username_array);
        reset($password_array);
        reset($passhash_array);

        $current_logon = key($username_array);

        echo form_dropdown_array("logonarray", $usernames, $usernames, "", "onchange=\"changepassword()\" autocomplete=\"off\"", "logon_dropdown");
        echo form_input_hidden("user_logon", $username_array[$current_logon]);

        foreach($username_array as $key => $logon) {

            if (isset($password_array[$key]) && strlen($password_array[$key]) > 0) {

                if (isset($passhash_array[$key]) && is_md5($passhash_array[$key])) {

                    echo form_input_hidden("user_password$key", $password_array[$key]);
                    echo form_input_hidden("user_passhash$key", $passhash_array[$key]);

                }else {

                    echo form_input_hidden("user_password$key", "");
                    echo form_input_hidden("user_passhash$key", "");
                }

            }else {

                echo form_input_hidden("user_password$key", "");
                echo form_input_hidden("user_passhash$key", "");
            }
        }

        echo "              ", form_submit("other", $lang['otherbutton']), "</td>\n";
        echo "                        </tr>\n";
        echo "                        <tr>\n";
        echo "                          <td align=\"right\">{$lang['passwd']}:</td>\n";

        if (isset($password_array[$current_logon]) && strlen($password_array[$current_logon]) > 0) {

            if (isset($passhash_array[$current_logon]) && is_md5($passhash_array[$current_logon])) {

                echo "                          <td align=\"left\">", form_input_password("user_password", $password_array[$current_logon], 24, false, "autocomplete=\"off\""), form_input_hidden("user_passhash", $passhash_array[$current_logon]), "</td>\n";

            }else {

                echo "                          <td align=\"left\">", form_input_password("user_password", "", 24, false, "autocomplete=\"off\""), form_input_hidden("user_passhash", ""), "</td>\n";
            }

        }else {

            echo "                          <td align=\"left\">", form_input_password("user_password", "", 24, false, "autocomplete=\"off\""), form_input_hidden("user_passhash", ""), "</td>\n";
        }

        echo "                        </tr>\n";

    }else {

        if ($other_logon === true) {

            echo "                        <tr>\n";
            echo "                          <td align=\"right\">{$lang['username']}:</td>\n";
            echo "                          <td align=\"left\">", form_input_text("user_logon", "", 24, false, "autocomplete=\"off\""), "</td>\n";
            echo "                        </tr>\n";
            echo "                        <tr>\n";
            echo "                          <td align=\"right\">{$lang['passwd']}:</td>\n";
            echo "                          <td align=\"left\">", form_input_password("user_password", "", 24, false, "autocomplete=\"off\""), "</td>\n";
            echo "                        </tr>\n";

        }else {

            echo "                        <tr>\n";
            echo "                          <td align=\"right\">{$lang['username']}:</td>\n";
            echo "                          <td align=\"left\">", form_input_text("user_logon", (isset($username_array[0]) ? $username_array[0] : ""), 24, false, "autocomplete=\"off\""), "</td>\n";
            echo "                        </tr>\n";
            echo "                        <tr>\n";
            echo "                          <td align=\"right\">{$lang['passwd']}:</td>\n";
            echo "                          <td align=\"left\">", form_input_password("user_password", (isset($password_array[0]) ? $password_array[0] : ""), 24, false, "autocomplete=\"off\""), form_input_hidden("user_passhash", (isset($passhash_array[0]) ? $passhash_array[0] : "")), "</td>\n";
            echo "                        </tr>\n";
        }
    }

    echo "                        <tr>\n";
    echo "                          <td align=\"left\">&nbsp;</td>\n";
    echo "                          <td align=\"left\">", form_checkbox("remember_user", "Y", $lang['rememberpasswds'], (isset($password_array[0]) && isset($passhash_array[0]) && $other_logon === false), "autocomplete=\"off\""), "</td>\n";
    echo "                        </tr>\n";
    echo "                        <tr>\n";
    echo "                          <td align=\"center\" colspan=\"2\">", form_submit('logon', $lang['logonbutton']), "</td>\n";
    echo "                        </tr>\n";
    echo "                      </table>\n";
    echo "                    </td>\n";
    echo "                  </tr>\n";
    echo "                </table>\n";
    echo "              </td>\n";
    echo "            </tr>\n";
    echo "          </table>\n";
    echo "        </td>\n";
    echo "      </tr>\n";
    echo "    </table>\n";
    echo "  </form>\n";
}

function logon_unset_post_data()
{
    if (isset($_COOKIE['bh_remember_username']) && is_array($_COOKIE['bh_remember_username'])) {
        $username_array = $_COOKIE['bh_remember_username'];
    }elseif (isset($_COOKIE['bh_remember_username']) && strlen($_COOKIE['bh_remember_username']) > 0) {
        $username_array = explode(",", $_COOKIE['bh_remember_username']);
    }else {
        $username_array = array();
    }

    for ($i = 0; $i < sizeof($username_array); $i++) {
        unset($_POST["user_password$i"], $_POST["user_passhash$i"]);
    }

    unset($_POST['user_logon'], $_POST['user_password'], $_POST['user_passhash']);
    unset($_POST['remember_user'], $_POST['logon'], $_POST['logonarray'], $_POST['webtag']);
}

?>