<?php/*======================================================================Copyright Project BeehiveForum 2002This file is part of BeehiveForum.BeehiveForum is free software; you can redistribute it and/or modifyit under the terms of the GNU General Public License as published bythe Free Software Foundation; either version 2 of the License, or(at your option) any later version.BeehiveForum is distributed in the hope that it will be useful,but WITHOUT ANY WARRANTY; without even the implied warranty ofMERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See theGNU General Public License for more details.You should have received a copy of the GNU General Public Licensealong with Beehive; if not, write to the Free SoftwareFoundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307USA======================================================================*/// Frameset for thread list and messages//Check logged in statusrequire_once("./include/session.inc.php");require_once("./include/header.inc.php");require_once("./include/form.inc.php");if(!bh_session_check()){    $uri = "http://".$HTTP_SERVER_VARS['HTTP_HOST'];    $uri.= dirname($HTTP_SERVER_VARS['PHP_SELF']);    $uri.= "/logon.php?final_uri=";    $uri.= urlencode($HTTP_SERVER_VARS['REQUEST_URI']);    header_redirect($uri);}$uid = $HTTP_COOKIE_VARS['bh_sess_uid'];require_once("./include/perm.inc.php");require_once("./include/html.inc.php");require_once("./include/constants.inc.php");require_once("./include/db.inc.php");require_once("./include/format.inc.php");require_once("./include/thread.inc.php");html_draw_top_script();echo "<table class=\"posthead\" border=\"0\" width=\"200\" cellpadding=\"0\" cellspacing=\"0\">";echo "<tr><td class=\"subhead\">Recent threads</td></tr>";$db = db_connect();// Get most recent threads$sql = "select T.TID, T.TITLE, T.LENGTH, UT.LAST_READ, UT.INTEREST ";$sql.= "from ".forum_table("THREAD")." T left join ".forum_table("USER_THREAD")." UT ";$sql.= "on (T.TID = UT.TID and UT.UID = $uid) ";$sql.= "order by T.MODIFIED desc ";$sql.= "limit 0, 10";$result = db_query($sql, $db);echo "<tr><td><table class=\"posthead\" border=\"0\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\">\n";while($row = db_fetch_array($result)){    $tid = $row['TID'];    if($row['LAST_READ'] && $row['LENGTH'] > $row['LAST_READ']){        $pid = $row['LAST_READ'] + 1;    } else {        $pid = 1;    }    echo "<tr><td valign=\"top\" align=\"middle\" nowrap=\"nowrap\">";        if (($row['LAST_READ'] == 0) || ($row['LAST_READ'] < $row['LENGTH'])) {        echo "<img src=\"./images/star.png\" name=\"t".$row['TID']."\" align=\"absmiddle\" />";    } elseif ($row['LAST_READ'] == $row['LENGTH']) {        echo "<img src=\"./images/bullet.png\" name=\"t".$row['TID']."\" align=\"absmiddle\" />";    }        $thread_author = thread_get_author($tid);        // With status mouseover: echo "&nbsp;</td><td><a href=\"discussion.php?msg=$tid.$pid\" target=\"main\"onmouseOver=\"status='#$tid Started by $thread_author';return true\" onmouseOut=\"window.status='';return true\" title=\"#$tid Started by $thread_author\">";    echo "&nbsp;</td><td><a href=\"discussion.php?msg=$tid.$pid\" target=\"main\" title=\"#$tid Started by $thread_author\">";    echo stripslashes($row['TITLE'])."</a>&nbsp;";    if ($row['INTEREST'] == 1) echo "<img src=\"./images/high_interest.png\" alt=\"High Interest\" align=\"middle\">";    if ($row['INTEREST'] == 2) echo "<img src=\"./images/subscribe.png\" alt=\"Subscribed\" align=\"middle\">";    echo "</td></tr>\n";}echo "</table></td></tr><tr><td>&nbsp;</td></tr>\n";// Display "Start Reading" buttonecho "<tr><td align=\"center\">\n";echo form_quick_button("discussion.php","Start reading >>", 0, 0, "main");echo "</td></tr>\n";echo "<tr><td>&nbsp;</td></tr>\n";echo "<tr><td class=\"subhead\">Recent visitors</td></tr>";// Get recent visitors$sql = "select U.UID, U.LOGON, U.NICKNAME, UNIX_TIMESTAMP(U.LAST_LOGON) as LAST_LOGON ";$sql.= "from ".forum_table("USER")." U ";$sql.= "order by U.LAST_LOGON desc ";$sql.= "limit 0, 10";$result = db_query($sql, $db);echo "<tr><td><table class=\"posthead\" border=\"0\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\">";while($row = db_fetch_array($result)){    echo "<tr><td valign=\"top\" align=\"middle\" nowrap=\"nowrap\"><img src=\"images/bullet.png\" width=\"12\" height=\"16\" /></td>";    echo "<td><a href=\"#\" target=\"_self\" onclick=\"openProfile(".$row['UID'].")\">";    echo format_user_name($row['LOGON'], $row['NICKNAME']) . "</a>";    echo "</td><td align=\"right\" nowrap=\"nowrap\">". format_time($row['LAST_LOGON']). "&nbsp;</td></tr>\n";}echo "</table></td></tr></table>\n";html_draw_bottom();?>