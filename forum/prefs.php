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

// Compress the output
require_once("./include/gzipenc.inc.php");

//Check logged in status
require_once("./include/session.inc.php");
require_once("./include/header.inc.php");

if(!bh_session_check()){

    $uri = "./logon.php?final_uri=". urlencode(get_request_uri());
    header_redirect($uri);

}

require_once("./include/html.inc.php");

if($HTTP_COOKIE_VARS['bh_sess_uid'] == 0) {
        html_guest_error();
        exit;
}

require_once("./include/user.inc.php");
require_once("./include/post.inc.php");
require_once("./include/fixhtml.inc.php");
require_once("./include/form.inc.php");
require_once("./include/header.inc.php");

$error_html = "";

$available_styles = array();
$style_names = array();

if ($dir = @opendir('styles')) {
  while (($file = readdir($dir)) !== false) {
    if (is_dir("styles/$file") && $file != '.' && $file != '..') {
      if (@file_exists("./styles/$file/desc.txt")) {
        if ($fp = fopen("./styles/$file/desc.txt", "r")) {
          $available_styles[] = $file;
          $style_names[] = htmlspecialchars(fread($fp, filesize("styles/$file/desc.txt")));
          fclose($fp);
        }else {
          $available_styles[] = $file;
          $style_names[] = $file;
        }
      }
    }
  }
  closedir($dir);
}

array_multisort($style_names, $available_styles);

if(isset($HTTP_POST_VARS['submit'])){

    $valid = true;

    if(isset($HTTP_POST_VARS['pw'])){
        if($HTTP_POST_VARS['pw'] != $HTTP_POST_VARS['cpw']){
            $error_html = "<h2>Passwords do not match</h2>";
            $valid = false;
        }
    }

    if(empty($HTTP_POST_VARS['nickname'])){
        $error_html .= "<h2>Nickname is required</h2>";
        $valid = false;
    }

    if(empty($HTTP_POST_VARS['email'])){
        $error_html .= "<h2>Email address is required</h2>";
        $valid = false;
    }

    if ($valid) {

        if(@$HTTP_POST_VARS['sig_html'] == "Y"){
            $HTTP_POST_VARS['sig_content'] = fix_html($HTTP_POST_VARS['sig_content']);
        }else {
            $HTTP_POST_VARS['sig_content'] = _stripslashes($HTTP_POST_VARS['sig_content']);
        }

        // ALTER TABLE USER_PREFS ADD DOB DATE NOT NULL AFTER LASTNAME

        $user_dob = str_pad(trim($HTTP_POST_VARS['dob_year']),  4, '0', STR_PAD_LEFT). '-';
        $user_dob.= str_pad(trim($HTTP_POST_VARS['dob_month']), 2, '0', STR_PAD_LEFT). '-';
        $user_dob.= str_pad(trim($HTTP_POST_VARS['dob_day']),   2, '0', STR_PAD_LEFT);

        // Update basic settings in USER table

        user_update($HTTP_COOKIE_VARS['bh_sess_uid'],
                    $HTTP_POST_VARS['pw'],
                    $HTTP_POST_VARS['nickname'],
                    $HTTP_POST_VARS['email']);

        // Update USER_PREFS

        user_update_prefs($HTTP_COOKIE_VARS['bh_sess_uid'], $HTTP_POST_VARS['firstname'], $HTTP_POST_VARS['lastname'], $user_dob,
                          $HTTP_POST_VARS['homepage_url'], $HTTP_POST_VARS['pic_url'], @$HTTP_POST_VARS['email_notify'],
                          $HTTP_POST_VARS['timezone'], @$HTTP_POST_VARS['dl_saving'], @$HTTP_POST_VARS['mark_as_of_int'],
                          $HTTP_POST_VARS['posts_per_page'], $HTTP_POST_VARS['font_size'], $HTTP_POST_VARS['style'],
                          @$HTTP_POST_VARS['view_sigs'], $HTTP_POST_VARS['start_page']);

        // Update USER_SIG

        user_update_sig($HTTP_COOKIE_VARS['bh_sess_uid'],
                        $HTTP_POST_VARS['sig_content'],
                        @$HTTP_POST_VARS['sig_html']);

        // Update the User's Session to save them having to logout and back in

        bh_session_init($HTTP_COOKIE_VARS['bh_sess_uid']);

        header_redirect("prefs.php?updated=true");

    }

}

$user = user_get($HTTP_COOKIE_VARS['bh_sess_uid']);
$user_prefs = user_get_prefs($HTTP_COOKIE_VARS['bh_sess_uid']);
user_get_sig($HTTP_COOKIE_VARS['bh_sess_uid'], $user_sig['CONTENT'], $user_sig['HTML']);

// Arrys for Birthday

$birthday_days   = array(' ', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31');
$birthday_months = array(' ', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
$birthday_years  = array_merge(array(' '), range(1900 + (date('Y', mktime()) - 2000), date('Y', mktime())));

// Split the DOB into usable variables.

list($dob_year, $dob_month, $dob_day) = explode('-', $user_prefs['DOB']);

// Start output here

html_draw_top();

echo $user_prefs['DOB'];

echo "<h1>User Preferences</h1>\n";

if(!empty($error_html)) {
    echo $error_html;
}elseif (isset($HTTP_GET_VARS['updated'])) {

    echo "<h2>Preferences were successfully updated.</h2>\n";

        $top_html = "./styles/".(isset($HTTP_COOKIE_VARS['bh_sess_style']) ? $HTTP_COOKIE_VARS['bh_sess_style'] : $default_style) . "/top.html";
        if (!file_exists($top_html)) {
                $top_html = "./top.html";
        }
        echo "<script language=\"Javascript\" type=\"text/javascript\">\n";
        echo "<!--\n";
        echo "top.frames['ftop'].location.replace('$top_html'); top.frames['fnav'].location.reload();\n";
        echo "-->\n";
        echo "</script>";
}

?>
<div class="postbody">
  <form name="prefs" action="<?php echo $HTTP_SERVER_VARS['PHP_SELF']; ?>" method="post">
    <table class="posthead" width="400">
      <tr>
        <td class="subhead" colspan="2">User Details</td>
      </tr>
      <tr>
        <td>New Password</td>
        <td>: <?php echo form_field("pw", "", 37, 0, "password"); ?></td>
      </tr>
      <tr>
        <td>Confirm Password</td>
        <td>: <?php echo form_field("cpw", "", 37, 0, "password"); ?></td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td align="center"><span style="font-size: 10px">(Leave blank to retain current password)</span></td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td>Nickname</td>
        <td>: <?php echo form_field("nickname", $user['NICKNAME'], 37, 32); ?></td>
      </tr>
      <tr>
        <td>Email Address</td>
        <td>: <?php echo form_field("email", $user['EMAIL'], 37, 80); ?></td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td>First name</td>
        <td>: <?php echo form_field("firstname", $user_prefs['FIRSTNAME'], 37, 32); ?></td>
      </tr>
      <tr>
        <td>Last name</td>
        <td>: <?php echo form_field("lastname", $user_prefs['LASTNAME'], 37, 32); ?></td>
      </tr>
      <tr>
        <td>Date of Birth</td>
        <td>: <?php echo form_dropdown_array("dob_day", range (0, 31), $birthday_days, $dob_day); ?>&nbsp;<?php echo form_dropdown_array("dob_month", range (0, 12), $birthday_months, $dob_month); ?>&nbsp;<?php echo form_dropdown_array("dob_year", $birthday_years, $birthday_years, $dob_year); ?></td>
      </tr>
      <tr>
        <td>Homepage URL</td>
        <td>: <?php echo form_field("homepage_url", $user_prefs['HOMEPAGE_URL'], 37, 255); ?></td>
      </tr>
      <tr>
        <td>Picture URL</td>
        <td>: <?php echo form_field("pic_url", $user_prefs['PIC_URL'], 37, 255); ?></td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
    </table>
    <table class="posthead" width="400">
      <tr>
        <td class="subhead">Forum Options</td>
      </tr>
      <tr>
        <td><?php echo form_checkbox("email_notify", "Y", "Notify by email of posts to me", ($user_prefs['EMAIL_NOTIFY'] == "Y")); ?></td>
      </tr>
      <tr>
        <td><?php echo form_checkbox("dl_saving", "Y", "Adjust for daylight saving", ($user_prefs['DL_SAVING'] == "Y")); ?></td>
      </tr>
      <tr>
        <td><?php echo form_checkbox("mark_as_of_int", "Y", "Automatically mark threads I post in as High Interest", ($user_prefs['MARK_AS_OF_INT'] == "Y")); ?></td>
      </tr>
      <tr>
        <td><?php echo form_checkbox("view_sigs", "Y", "Globally ignore user signatures", ($user_prefs['VIEW_SIGS'] == "Y")); ?></td>
      </tr>
      <tr>
        <td>&nbsp;</td>
      </tr>
    </table>
    <table class="posthead" width="400">
      <tr>
        <td width="150">Timezone (from GMT)</td>
        <td><?php echo form_dropdown_array("timezone", range(-11,11), range(-11,11), $user_prefs['TIMEZONE']); ?></td>
      </tr>
      <tr>
        <td>Posts per page</td>
        <td><?php echo form_dropdown_array("posts_per_page", array(5,10,20), array(5,10,20), isset($user_prefs['POSTS_PER_PAGE']) ? $user_prefs['POSTS_PER_PAGE'] : 10); ?></td>
      </tr>
      <tr>
        <td>Font size</td>
        <td>
          <?php

            if ($user_prefs['FONT_SIZE'] == '') {

                echo form_dropdown_array("font_size", range(1,15), array('1pt', '2pt', '3pt', '4pt', '5pt', '6pt', '7pt', '8pt', '9pt', '10pt', '11pt', '12pt', '13pt', '14pt', '15pt'), "10pt");

            }else{

                echo form_dropdown_array("font_size", range(1,15), array('1pt', '2pt', '3pt', '4pt', '5pt', '6pt', '7pt', '8pt', '9pt', '10pt', '11pt', '12pt', '13pt', '14pt', '15pt'), $user_prefs['FONT_SIZE']);

            }

          ?>
        </td>
      </tr>
      <tr>
        <td>Forum Style</td>
        <td>
          <?php

            if (isset($HTTP_COOKIE_VARS['bh_sess_style'])) {
              $selected_style = $HTTP_COOKIE_VARS['bh_sess_style'];
            }else {
              $selected_style = $default_style;
            }

            foreach ($available_styles as $key => $style) {
              if (strtolower($style) == strtolower($selected_style)) {
                break;
              }
            }

            reset($available_styles);

            if (isset($key)) {
              echo form_dropdown_array("style", $available_styles, $style_names, $available_styles[$key]);
            }else {
              echo form_dropdown_array("style", $available_styles, $style_names, $available_styles[0]);
            }

          ?>
        </td>
      </tr>
      <tr>
        <td>Start Page</td>
        <td><?php echo form_dropdown_array("start_page", array(0, 1), array('Start', 'Messages'), isset($user_prefs['START_PAGE']) ? $user_prefs['START_PAGE'] : 0); ?></td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
    </table>
    <table class="posthead" width="400">
      <tr>
        <td class="subhead" colspan="2">Signature</td>
      </tr>
      <tr>
        <td colspan="2"><?php echo form_textarea("sig_content", htmlspecialchars($user_sig['CONTENT']), 4, 60); ?></td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td align="right"><?php echo form_checkbox("sig_html", "Y", "Contains HTML", ($user_sig['HTML'] == "Y")); ?></td>
      </tr>
    </table>
    <?php echo form_submit("submit", "Submit"); ?>
  </form>
</div>
<?php html_draw_bottom(); ?>