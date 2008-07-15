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

/* $Id: folder_subscriptions.php,v 1.1 2008-07-14 18:05:18 decoyduck Exp $ */

// Constant to define where the include files are
define("BH_INCLUDE_PATH", "include/");

// Server checking functions
include_once(BH_INCLUDE_PATH. "server.inc.php");

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

include_once(BH_INCLUDE_PATH. "constants.inc.php");
include_once(BH_INCLUDE_PATH. "fixhtml.inc.php");
include_once(BH_INCLUDE_PATH. "form.inc.php");
include_once(BH_INCLUDE_PATH. "format.inc.php");
include_once(BH_INCLUDE_PATH. "header.inc.php");
include_once(BH_INCLUDE_PATH. "html.inc.php");
include_once(BH_INCLUDE_PATH. "lang.inc.php");
include_once(BH_INCLUDE_PATH. "logon.inc.php");
include_once(BH_INCLUDE_PATH. "post.inc.php");
include_once(BH_INCLUDE_PATH. "session.inc.php");
include_once(BH_INCLUDE_PATH. "folder.inc.php");
include_once(BH_INCLUDE_PATH. "user.inc.php");
include_once(BH_INCLUDE_PATH. "user_rel.inc.php");
include_once(BH_INCLUDE_PATH. "word_filter.inc.php");

// Check we're logged in correctly

if (!$user_sess = bh_session_check()) {
    $request_uri = rawurlencode(get_request_uri());
    $webtag = get_webtag($webtag_search);
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

if (!$webtag = get_webtag($webtag_search)) {
    $request_uri = rawurlencode(get_request_uri(false));
    header_redirect("forums.php?webtag_search=$webtag_search&final_uri=$request_uri");
}

// Load language file

$lang = load_language_file();

// Check that we have access to this forum

if (!forum_check_access_level()) {
    $request_uri = rawurlencode(get_request_uri());
    header_redirect("forums.php?webtag_search=$webtag_search&final_uri=$request_uri");
}

if (user_is_guest()) {

    html_guest_error();
    exit;
}

// Array to store error messages.

$error_msg_array = array();

// User pressed Save button

if (isset($_POST['save'])) {

    $valid = true;

    if (isset($_POST['set_interest']) && is_array($_POST['set_interest'])) {

        foreach ($_POST['set_interest'] as $folder) {

            if ($valid && is_numeric($folder)) {

                if (!user_set_folder_interest($folder, 0)) {

                    $folder_title = folder_get_title($folder);
                    $error_msg_array[] = sprintf("{$lang['couldnotupdateinterestonfolder']}", $folder_title);
                    $valid = false;
                }
            }
        }

        if ($valid) {

            header_redirect("folder_subscriptions.php?webtag=$webtag&updated=true");
            exit;
        }
    }
}

// Page links.

if (isset($_GET['main_page']) && is_numeric($_GET['main_page'])) {
    $main_page = $_GET['main_page'];
    $start_main = floor($main_page - 1) * 20;
}else if (isset($_POST['main_page']) && is_numeric($_POST['main_page'])) {
    $main_page = $_POST['main_page'];
    $start_main = floor($main_page - 1) * 20;
}else {
    $main_page = 1;
    $start_main = 0;
}

// Search links.

if (isset($_GET['search_page']) && is_numeric($_GET['search_page'])) {
    $search_page = $_GET['search_page'];
    $start_search = floor($search_page - 1) * 20;
}else if (isset($_POST['search_page']) && is_numeric($_POST['search_page'])) {
    $search_page = $_POST['search_page'];
    $start_search = floor($search_page - 1) * 20;
}else {
    $search_page = 1;
    $start_search = 0;
}

// Folder search keywords.

if (isset($_GET['foldersearch']) && strlen(trim(_stripslashes($_GET['foldersearch']))) > 0) {
    $foldersearch = trim(_stripslashes($_GET['foldersearch']));
}else if (isset($_POST['foldersearch']) && strlen(trim(_stripslashes($_POST['foldersearch']))) > 0) {
    $foldersearch = trim(_stripslashes($_POST['foldersearch']));
}else {
    $foldersearch = "";
}

// View filter

if (isset($_GET['view_filter']) && is_numeric($_GET['view_filter'])) {
    $view_filter = $_GET['view_filter'];
}else if (isset($_POST['view_filter']) && is_numeric($_POST['view_filter'])) {
    $view_filter = $_POST['view_filter'];
}else {
    $view_filter = FOLDER_NOINTEREST;
}

// Clear search?

if (isset($_POST['clear'])) {
    $foldersearch = "";
}

// User UID

$uid = bh_session_get_value('UID');

// Save button text and header text change depending on view selected.

$header_text_array = array(FOLDER_NOINTEREST => $lang['allfoldertypes'], FOLDER_IGNORED => $lang['ignoredfolders'], FOLDER_SUBSCRIBED => $lang['subscribedfolders']);

$interest_level_array = array(FOLDER_IGNORED => $lang['ignored'], FOLDER_NOINTEREST => $lang['normal'], FOLDER_SUBSCRIBED => $lang['subscribe']);

// Check if we're searching or displaying the existing subscriptions.

if (isset($foldersearch) && strlen(trim($foldersearch)) > 0) {
    $folder_subscriptions = folders_search_user_subscriptions($foldersearch, $view_filter, $start_search);
}else {
    $folder_subscriptions = folders_get_user_subscriptions($view_filter, $start_main);
}

// Start output here

html_draw_top('folder_subscriptions.js');

echo "<h1>{$lang['foldersubscriptions']} &raquo; {$header_text_array[$view_filter]}</h1>\n";

if (isset($error_msg_array) && sizeof($error_msg_array) > 0) {

    html_display_error_array($error_msg_array, '600', 'left');

}else if (isset($_GET['updated'])) {

    html_display_success_msg($lang['folderinterestsupdatedsuccessfully'], '600', 'left');

}else if (sizeof($folder_subscriptions['folder_array']) < 1) {

    if (isset($foldersearch) && strlen(trim($foldersearch)) > 0) {

        html_display_warning_msg($lang['searchreturnednoresults'], '600', 'left');

    }else {

        html_display_warning_msg($lang['nofoldersubscriptions'], '600', 'left');
    }
}

echo "<br />\n";
echo "<form name=\"subscriptions\" action=\"folder_subscriptions.php\" method=\"post\" target=\"_self\">\n";
echo "  ", form_input_hidden('webtag', _htmlentities($webtag)), "\n";
echo "  ", form_input_hidden("main_page", _htmlentities($main_page)), "\n";
echo "  ", form_input_hidden("search_page", _htmlentities($search_page)), "\n";
echo "  ", form_input_hidden("foldersearch", _htmlentities($foldersearch)), "\n";
echo "  <table cellpadding=\"0\" cellspacing=\"0\" width=\"600\">\n";
echo "    <tr>\n";
echo "      <td align=\"left\" colspan=\"3\">\n";
echo "        <table class=\"box\" width=\"100%\">\n";
echo "          <tr>\n";
echo "            <td align=\"left\" class=\"posthead\">\n";
echo "              <table class=\"posthead\" width=\"100%\">\n";

if (sizeof($folder_subscriptions['folder_array']) > 0) {

    echo "                <tr>\n";
    echo "                  <td align=\"center\" class=\"subhead_checkbox\" width=\"1%\">", form_checkbox("toggle_all", "toggle_all", "", false, "onclick=\"subscriptionsToggleAll();\""), "</td>\n";
    echo "                  <td align=\"left\" class=\"subhead\" width=\"450\">{$lang['foldertitle']}</td>\n";
    echo "                  <td align=\"center\" class=\"subhead\" width=\"150\">{$lang['currentinterest']}</td>\n";
    echo "                </tr>\n";

    foreach ($folder_subscriptions['folder_array'] as $folder) {

        echo "                <tr>\n";
        echo "                  <td align=\"center\" nowrap=\"nowrap\">", form_checkbox('set_interest[]', $folder['FID'], ''), "</td>\n";
        echo "                  <td align=\"left\"><a href=\"index.php?webtag=$webtag&amp;folder={$folder['FID']}\" target=\"_blank\">", word_filter_add_ob_tags(_htmlentities($folder['TITLE'])), "</a></td>\n";

        if (isset($interest_level_array[$folder['INTEREST']])) {
            echo "                  <td align=\"center\">{$interest_level_array[$folder['INTEREST']]}</td>\n";
        }else {
            echo "                  <td align=\"center\">{$lang['normal']}</td>\n";
        }

        echo "                </tr>\n";
    }

}else {

    echo "                <tr>\n";
    echo "                  <td align=\"left\" class=\"subhead\" width=\"20\">&nbsp;</td>\n";
    echo "                  <td align=\"left\" class=\"subhead\" width=\"450\">{$lang['foldertitle']}</td>\n";
    echo "                  <td align=\"center\" class=\"subhead\" width=\"150\">{$lang['currentinterest']}</td>\n";
    echo "                </tr>\n";
}

echo "                <tr>\n";
echo "                  <td align=\"left\">&nbsp;</td>\n";
echo "                </tr>\n";
echo "              </table>\n";
echo "            </td>\n";
echo "          </tr>\n";
echo "        </table>\n";
echo "      </td>\n";
echo "    </tr>\n";
echo "    <tr>\n";
echo "      <td align=\"left\">&nbsp;</td>\n";
echo "    </tr>\n";
echo "    <tr>\n";
echo "      <td align=\"left\" width=\"33%\">&nbsp;</td>\n";
echo "      <td class=\"postbody\" align=\"center\">", page_links("folder_subscriptions.php?webtag=$webtag&foldersearch=$foldersearch&search_page=$search_page&view_filter=$view_filter", $start_main, $folder_subscriptions['folder_count'], 20, "main_page"), "</td>\n";
echo "      <td align=\"right\" width=\"33%\">{$lang['view']}:&nbsp;", form_dropdown_array('view_filter', array(FOLDER_NOINTEREST => $lang['all'], FOLDER_IGNORED => $lang['ignored'], FOLDER_SUBSCRIBED => $lang['subscribed']), $view_filter), "&nbsp;", form_submit("view_submit", $lang['goexcmark']), "</td>\n";
echo "    </tr>\n";

if (sizeof($folder_subscriptions['folder_array']) > 0) {

    echo "    <tr>\n";
    echo "      <td align=\"left\">&nbsp;</td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td align=\"center\" colspan=\"3\">", form_submit("save", $lang['resetselected']), "</td>\n";
    echo "    </tr>\n";
}

echo "  </table>\n";
echo "</form>\n";
echo "<br />\n";
echo "<form method=\"post\" action=\"folder_subscriptions.php\" target=\"_self\">\n";
echo "  ", form_input_hidden('webtag', _htmlentities($webtag)), "\n";
echo "  ", form_input_hidden("main_page", _htmlentities($main_page)), "\n";
echo "  ", form_input_hidden("search_page", _htmlentities($search_page)), "\n";
echo "  ", form_input_hidden("main_page", _htmlentities($main_page)), "\n";
echo "  <table cellpadding=\"0\" cellspacing=\"0\" width=\"600\">\n";
echo "    <tr>\n";
echo "      <td align=\"left\" class=\"posthead\">\n";
echo "        <table class=\"box\" width=\"100%\">\n";
echo "          <tr>\n";
echo "            <td align=\"left\" class=\"posthead\">\n";
echo "              <table class=\"posthead\" width=\"100%\">\n";
echo "                <tr>\n";
echo "                  <td class=\"subhead\" align=\"left\">{$lang['search']}</td>\n";
echo "                </tr>\n";
echo "              </table>\n";
echo "              <table class=\"posthead\" width=\"100%\">\n";
echo "                <tr>\n";
echo "                  <td align=\"center\">\n";
echo "                    <table class=\"posthead\" width=\"95%\">\n";
echo "                      <tr>\n";
echo "                        <td class=\"posthead\" align=\"left\">\n";
echo "                          {$lang['foldertitle']}: ", form_input_text("foldersearch", isset($foldersearch) ? _htmlentities($foldersearch) : "", 30, 64), " ", form_submit('search', $lang['search']), "&nbsp;", form_submit('clear', $lang['clear']), "\n";
echo "                        </td>\n";
echo "                      </tr>\n";
echo "                      <tr>\n";
echo "                        <td align=\"left\">&nbsp;</td>\n";
echo "                      </tr>\n";
echo "                    </table>\n";
echo "                  </td>\n";
echo "                </tr>\n";
echo "              </table>\n";
echo "            </td>\n";
echo "          </tr>\n";
echo "        </table>\n";
echo "      </td>\n";
echo "    </tr>\n";
echo "  </table>\n";
echo "</form>\n";

html_draw_bottom();

?>