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

require_once("./include/html.inc.php");
require_once("./include/user.inc.php");
require_once("./include/constants.inc.php");
require_once("./include/session.inc.php");
require_once("./include/form.inc.php");
require_once("./include/format.inc.php");

// Where are we going after we've logged on?

if(isset($HTTP_GET_VARS['final_uri'])) {
    $final_uri = urldecode($HTTP_GET_VARS['final_uri']);
} else {
    $final_uri = dirname($HTTP_SERVER_VARS['PHP_SELF']) . "/";
}

if(isset($HTTP_COOKIE_VARS['bh_sess_uid'])){

    html_draw_top();

    echo "<div align=\"center\">\n";
    echo "<p>User ID " . $HTTP_COOKIE_VARS['bh_sess_uid'] . " already logged in.</p>\n";
    echo "<p><a href=\"$final_uri\" target=\"_top\">Continue</a></p>\n";
    echo "</div>\n";

    html_draw_bottom();
    exit;
}

$valid = true;
$error_html = "";

if(isset($HTTP_POST_VARS['submit'])) {

  if(!empty($HTTP_POST_VARS['logon'])) {
      if (htmlentities(strtoupper($HTTP_POST_VARS['logon'])) != strtoupper($HTTP_POST_VARS['logon'])) {
        $error_html.= "<h2>Username must not contain HTML tags</h2>\n";
        $valid = false;
      }
      if (strlen($HTTP_POST_VARS['logon']) < 2) {
        $error_html.= "<h2>Username must be a minimum of 2 characters long</h2>\n";
        $valid = false;
      }
      if (strlen($HTTP_POST_VARS['logon']) > 15) {
        $error_html.= "<h2>Username must be a maximum of 15 characters long</h2>\n";
        $valid = false;
      }
  }else {
      $error_html.= "<h2>A logon name is required</h2>\n";
      $valid = false;
  }

  if(!empty($HTTP_POST_VARS['pw'])) {
      if (htmlentities($HTTP_POST_VARS['pw']) != $HTTP_POST_VARS['pw']) {
        $error_html.= "<h2>Password must not contain HTML tags</h2>\n";
        $valid = false;
      }
      if (strlen($HTTP_POST_VARS['pw']) < 6) {
        $error_html.= "<h2>Password must be a minimum of 6 characters long</h2>\n";
        $valid = false;
      }
  }else {
      $error_html.= "<h2>A password is required</h2>\n";
      $valid.= false;
  }

  if(!empty($HTTP_POST_VARS['cpw'])) {
      if (htmlentities($HTTP_POST_VARS['cpw']) != $HTTP_POST_VARS['cpw']) {
        $error_html.= "<h2>Password must not contain HTML tags</h2>\n";
        $valid = false;
      }
  }else {
      $error_html.= "<h2>A confirmation password is required</h2>\n";
      $valid = false;
  }

  if(!empty($HTTP_POST_VARS['nickname'])) {
      if (htmlentities($HTTP_POST_VARS['nickname']) != $HTTP_POST_VARS['nickname']) {
        $error_html.= "<h2>Nickname must not contain HTML tags</h2>\n";
        $valid = false;
      }
  }else {
      $error_html.= "<h2>A nickname is required</h2>\n";
      $valid = false;
  }

  if(!empty($HTTP_POST_VARS['email'])) {
      if (htmlentities($HTTP_POST_VARS['email']) != $HTTP_POST_VARS['email']) {
        $error_html.= "<h2>Email must not contain HTML tags</h2>\n";
        $valid = false;
      }
  }else {
      $error_html.= "<h2>An email address is required</h2>\n";
      $valid = false;
  }

  if($valid) {
      if(user_exists(strtoupper($HTTP_POST_VARS['logon']))) {
          $error_html.= "<h2>Sorry, a user with that name already exists</h2>\n";
          $valid = false;
      }
  }

  if($valid) {
      if($HTTP_POST_VARS['pw'] != $HTTP_POST_VARS['cpw']) {
          $error_html.= "<h2>Passwords do not match</h2>\n";
          $valid = false;
      }
  }

  if($valid) {

      $new_uid = user_create(strtoupper($HTTP_POST_VARS['logon']), $HTTP_POST_VARS['pw'], $HTTP_POST_VARS['nickname'], $HTTP_POST_VARS['email']);

      if($new_uid > -1) {

          bh_session_init($luid);

          if (isset($HTTP_COOKIE_VARS['bh_remember_user'])) {

            if (is_array($HTTP_COOKIE_VARS['bh_remember_user'])) {

              $usernames = $HTTP_COOKIE_VARS['bh_remember_user'];
              $passwords = $HTTP_COOKIE_VARS['bh_remember_password'];

            }else {

              $usernames = array(0 => $HTTP_COOKIE_VARS['bh_remember_user']);
              $passwords = array(0 => $HTTP_COOKIE_VARS['bh_remember_password']);

            }

          }else {

            $usernames = array();
            $passwords = array();

          }

          if (!in_array($HTTP_POST_VARS['logon'], $usernames)) {

            array_unshift($usernames, $HTTP_POST_VARS['logon']);

	    if(isset($HTTP_POST_VARS['remember_user'])) {
	      array_unshift($passwords, $HTTP_POST_VARS['password']);
            }else {
	      array_unshift($passwords, str_repeat(chr(255), 4));
            }

          }else {

            if (($key = array_search($HTTP_POST_VARS['logon'], $usernames)) !== false) {

	      array_splice($usernames, $key, 1);
	      array_splice($passwords, $key, 1);

              array_unshift($usernames, $HTTP_POST_VARS['logon']);

              if(isset($HTTP_POST_VARS['remember_user'])) {
	        array_unshift($passwords, $HTTP_POST_VARS['password']);
	      }else {
	        array_unshift($passwords, str_repeat(chr(255), 4));
	      }

	    }

	  }

          for ($i = 0; $i < sizeof($usernames); $i++) {

            setcookie("bh_remember_user[$i]", _stripslashes($usernames[$i]), time() + YEAR_IN_SECONDS, dirname($HTTP_SERVER_VARS['PHP_SELF']). '/logon.php');
            setcookie("bh_remember_password[$i]", _stripslashes($passwords[$i]), time() + YEAR_IN_SECONDS, dirname($HTTP_SERVER_VARS['PHP_SELF']). '/logon.php');

          }

          html_draw_top();

          echo "<div align=\"center\">\n";
          echo "<p>Huzzah! Your user record has been created successfully!</p>\n";
          echo form_quick_button($final_uri, "Continue", 0, 0, "_top");
          echo "</div>\n";

          html_draw_bottom();
          exit;

      } else {

          $error_html.= "<h2>Error creating user record</h2>\n";
          $valid = false;

      }
  }

}

html_draw_top();

echo "<h1>User Registration</h1>\n";

if (isset($error_html)) echo $error_html;

?>

<div align="center">
<form name="register" action="<?php echo $HTTP_SERVER_VARS['PHP_SELF']; ?>" method="POST">
  <table class="box" cellpadding="0" cellspacing="0" align="center">
    <tr>
      <td>
        <table class="subhead" width="100%">
          <tr>
            <td>Register</td>
          </tr>
        </table>
        <table class="posthead" width="100%">
          <tr>
            <td align="right" class="posthead">Login Name&nbsp;</td>
            <td><?php echo form_field("logon", _stripslashes($HTTP_POST_VARS['logon']), 32, 32); ?></td>
          </tr>
          <tr>
            <td align="right" class="posthead">Password&nbsp;</td>
            <td><?php echo form_field("pw", _stripslashes($HTTP_POST_VARS['pw']), 32, 32,"password"); ?></td>
          </tr>
          <tr>
            <td align="right" class="posthead">Confirm&nbsp;</td>
            <td><?php echo form_field("cpw", _stripslashes($HTTP_POST_VARS['cpw']), 32, 32,"password"); ?></td>
          </tr>
          <tr>
            <td align="right" class="posthead">Nickname&nbsp;</td>
            <td><?php echo form_field("nickname", _stripslashes($HTTP_POST_VARS['nickname']), 32, 32); ?></td>
          </tr>
          <tr>
            <td align="right" class="posthead">Email&nbsp;</td>
            <td><?php echo form_field("email", _stripslashes($HTTP_POST_VARS['email']), 32, 80); ?></td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td><?php echo form_checkbox("remember_user", "Y", "Remember password", ($HTTP_POST_VARS['remember_user'] == "Y")); ?></td>
          </tr>
        </table>
        <table class="posthead" width="100%">
          <tr>
            <td align="center"><?php echo form_submit("submit", "Register"); ?></td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</form>
</div>

<?php

html_draw_bottom();

?>
