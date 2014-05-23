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
require_once BH_INCLUDE_PATH . 'constants.inc.php';
require_once BH_INCLUDE_PATH . 'form.inc.php';
require_once BH_INCLUDE_PATH . 'format.inc.php';
require_once BH_INCLUDE_PATH . 'header.inc.php';
require_once BH_INCLUDE_PATH . 'html.inc.php';
require_once BH_INCLUDE_PATH . 'session.inc.php';
require_once BH_INCLUDE_PATH . 'thread.inc.php';
require_once BH_INCLUDE_PATH . 'threads.inc.php';
require_once BH_INCLUDE_PATH . 'word_filter.inc.php';
// End Required includes

// Check we're logged in correctly
if (!session::logged_in()) {
    html_guest_error();
}

$error_msg_array = array();

if (isset($_POST['save'])) {

    $valid = true;

    if (isset($_POST['set_interest']) && is_array($_POST['set_interest'])) {

        foreach ($_POST['set_interest'] as $thread) {

            if ($valid && is_numeric($thread)) {

                if (!thread_set_interest($thread, 0)) {

                    $thread_title = thread_get_title($thread);
                    $error_msg_array[] = sprintf(gettext("Could not update interest on thread '%s'"), $thread_title);
                    $valid = false;
                }
            }
        }

        if ($valid) {

            header_redirect("edit_subscriptions.php?webtag=$webtag&updated=true");
            exit;
        }
    }
}

if (isset($_GET['page']) && is_numeric($_GET['page'])) {
    $page = $_GET['page'];
} else if (isset($_POST['page']) && is_numeric($_POST['page'])) {
    $page = $_POST['page'];
} else {
    $page = 1;
}

if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $view = $_GET['view'];
} else if (isset($_POST['view']) && is_numeric($_POST['view'])) {
    $view = $_POST['view'];
} else {
    $view = THREAD_INTERESTED;
}

if (isset($_POST['search_keyword']) && strlen(trim($_POST['search_keyword'])) > 0) {

    $page = 1;

    $search_keyword = trim($_POST['search_keyword']);

} else if (isset($_GET['search_keyword']) && strlen(trim($_GET['search_keyword'])) > 0) {

    $search_keyword = trim($_GET['search_keyword']);

} else {

    $search_keyword = '';
}

if (isset($_POST['clear'])) {
    $search_keyword = "";
}

$header_text_array = array(
    THREAD_IGNORED => gettext("Ignored Threads"),
    THREAD_INTERESTED => gettext("High Interest Threads"),
    THREAD_SUBSCRIBED => gettext("Subscribed Threads")
);

$interest_level_array = array(
    THREAD_IGNORED => gettext("Ignored"),
    THREAD_INTERESTED => gettext("Interested"),
    THREAD_SUBSCRIBED => gettext("Subscribe")
);

if (isset($search_keyword) && strlen(trim($search_keyword)) > 0) {
    $thread_subscriptions = threads_search_user_subscriptions($search_keyword, $view, $page);
} else {
    $thread_subscriptions = threads_get_user_subscriptions($view, $page);
}

html_draw_top(
    array(
        'title' => gettext('My Controls - Thread Subscriptions'),
        'js' => array(
            'js/edit_subscriptions.js'
        ),
        'class' => 'window_title'
    )
);

echo "<h1>", gettext("Thread Subscriptions"), html_style_image('separator'), "{$header_text_array[$view]}</h1>\n";

if (isset($error_msg_array) && sizeof($error_msg_array) > 0) {

    html_display_error_array($error_msg_array, '700', 'left');

} else if (isset($_GET['updated'])) {

    html_display_success_msg(gettext("Thread interests updated successfully"), '700', 'left');

} else if (sizeof($thread_subscriptions['thread_array']) < 1) {

    if (isset($search_keyword) && strlen(trim($search_keyword)) > 0) {

        html_display_warning_msg(gettext("Search Returned No Results"), '700', 'left');

    } else if ($view == THREAD_IGNORED) {

        html_display_warning_msg(gettext("You are not ignoring any threads."), '700', 'left');

    } else if ($view == THREAD_INTERESTED) {

        html_display_warning_msg(gettext("You have no high interest threads."), '700', 'left');

    } else {

        html_display_warning_msg(gettext("You are not subscribed to any threads."), '700', 'left');
    }
}

echo "<br />\n";
echo "<form accept-charset=\"utf-8\" name=\"subscriptions\" action=\"edit_subscriptions.php\" method=\"post\" target=\"_self\">\n";
echo "  ", form_input_hidden('webtag', htmlentities_array($webtag)), "\n";
echo "  ", form_input_hidden("page", htmlentities_array($page)), "\n";
echo "  ", form_input_hidden("search_keyword", htmlentities_array($search_keyword)), "\n";
echo "  <table cellpadding=\"0\" cellspacing=\"0\" width=\"700\">\n";
echo "    <tr>\n";
echo "      <td align=\"left\" colspan=\"3\">\n";
echo "        <table class=\"box\" width=\"100%\">\n";
echo "          <tr>\n";
echo "            <td align=\"left\" class=\"posthead\">\n";
echo "              <table class=\"posthead\" width=\"100%\">\n";

if (sizeof($thread_subscriptions['thread_array']) > 0) {

    echo "                <tr>\n";
    echo "                  <td align=\"center\" class=\"subhead_checkbox\" width=\"1%\">", form_checkbox("toggle_all", "toggle_all"), "</td>\n";
    echo "                  <td align=\"left\" class=\"subhead\" width=\"450\">", gettext("Thread title"), "</td>\n";
    echo "                  <td align=\"center\" class=\"subhead\" width=\"150\">", gettext("Current Interest"), "</td>\n";
    echo "                </tr>\n";

    foreach ($thread_subscriptions['thread_array'] as $thread) {

        echo "                <tr>\n";
        echo "                  <td align=\"center\" style=\"white-space: nowrap\">", form_checkbox('set_interest[]', $thread['TID'], null), "</td>\n";
        echo "                  <td align=\"left\"><a href=\"index.php?webtag=$webtag&amp;msg={$thread['TID']}.1\" target=\"_blank\">", word_filter_add_ob_tags($thread['TITLE'], true), "</a></td>\n";

        if (isset($interest_level_array[$thread['INTEREST']])) {
            echo "                  <td align=\"center\">{$interest_level_array[$thread['INTEREST']]}</td>\n";
        } else {
            echo "                  <td align=\"center\">", gettext("none"), "</td>\n";
        }

        echo "                </tr>\n";
    }

} else {

    echo "                <tr>\n";
    echo "                  <td align=\"left\" class=\"subhead\" width=\"20\">&nbsp;</td>\n";
    echo "                  <td align=\"left\" class=\"subhead\" width=\"450\">", gettext("Thread title"), "</td>\n";
    echo "                  <td align=\"center\" class=\"subhead\" width=\"150\">", gettext("Current Interest"), "</td>\n";
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
echo "      <td class=\"postbody\" align=\"center\">";

html_page_links("edit_subscriptions.php?webtag=$webtag&search_keyword=$search_keyword&view=$view", $page, $thread_subscriptions['thread_count'], 20, "page");

echo "      </td>\n";
echo "      <td align=\"right\" width=\"33%\">", gettext("View"), ":&nbsp;", form_dropdown_array('view', array(THREAD_IGNORED => gettext("Ignored"), THREAD_INTERESTED => gettext("Interested"), THREAD_SUBSCRIBED => gettext("Subscribed")), $view), "&nbsp;", form_submit("view_submit", gettext("Go!")), "</td>\n";
echo "    </tr>\n";

if (sizeof($thread_subscriptions['thread_array']) > 0) {

    echo "    <tr>\n";
    echo "      <td align=\"left\">&nbsp;</td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td align=\"center\" colspan=\"3\">", form_submit("save", gettext("Reset Selected")), "</td>\n";
    echo "    </tr>\n";
}

echo "  </table>\n";
echo "</form>\n";
echo "<br />\n";
echo "<form accept-charset=\"utf-8\" method=\"post\" action=\"edit_subscriptions.php\" target=\"_self\">\n";
echo "  ", form_input_hidden('webtag', htmlentities_array($webtag)), "\n";
echo "  ", form_input_hidden("page", htmlentities_array($page)), "\n";
echo "  ", form_input_hidden("view", htmlentities_array($view)), "\n";
echo "  <table cellpadding=\"0\" cellspacing=\"0\" width=\"700\">\n";
echo "    <tr>\n";
echo "      <td align=\"left\" class=\"posthead\">\n";
echo "        <table class=\"box\" width=\"100%\">\n";
echo "          <tr>\n";
echo "            <td align=\"left\" class=\"posthead\">\n";
echo "              <table class=\"posthead\" width=\"100%\">\n";
echo "                <tr>\n";
echo "                  <td class=\"subhead\" align=\"left\">", gettext("Search"), "</td>\n";
echo "                </tr>\n";
echo "              </table>\n";
echo "              <table class=\"posthead\" width=\"100%\">\n";
echo "                <tr>\n";
echo "                  <td align=\"center\">\n";
echo "                    <table class=\"posthead\" width=\"95%\">\n";
echo "                      <tr>\n";
echo "                        <td class=\"posthead\" align=\"left\">\n";
echo "                          ", gettext("Thread title"), ": ", form_input_text("search_keyword", isset($search_keyword) ? htmlentities_array($search_keyword) : "", 30, 64), " ", form_submit('search', gettext("Search")), "&nbsp;", form_submit('clear', gettext("Clear")), "\n";
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