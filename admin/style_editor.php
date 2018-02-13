<?php
/*
  styles editor based on define_languages.php
  author John Ferguson @BrockleyJohn john@sewebsites.net
  Nov 2017

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2010 osCommerce

  Released under the GNU General Public License
*/

  require('includes/application_top.php');
  $stylefile = DIR_FS_CATALOG . 'user.css';
/*
  function tep_opendir($path) {
    $path = rtrim($path, '/') . '/';

    $exclude_array = array('.', '..', '.DS_Store', 'Thumbs.db');

    $result = array();

    if ($handle = opendir($path)) {
      while (false !== ($filename = readdir($handle))) {
        if (!in_array($filename, $exclude_array)) {
          $file = array('name' => $path . $filename,
                        'is_dir' => is_dir($path . $filename),
                        'writable' => tep_is_writable($path . $filename),
                        'size' => filesize($path . $filename),
                        'last_modified' => strftime(DATE_TIME_FORMAT, filemtime($path . $filename)));

          $result[] = $file;

          if ($file['is_dir'] == true) {
            $result = array_merge($result, tep_opendir($path . $filename));
          }
        }
      }

      closedir($handle);
    }

    return $result;
  }

  if (!isset($_GET['lngdir'])) $_GET['lngdir'] = $language;

  $languages_array = array();
  $languages = tep_get_languages();
  $lng_exists = false;
  for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
    if ($languages[$i]['directory'] == $_GET['lngdir']) $lng_exists = true;

    $languages_array[] = array('id' => $languages[$i]['directory'],
                               'text' => $languages[$i]['name']);
  }

  if (!$lng_exists) $_GET['lngdir'] = $language;

  if (isset($_GET['filename'])) {
    $file_edit = realpath(DIR_FS_CATALOG_LANGUAGES . $_GET['filename']);

    if (substr($file_edit, 0, strlen(DIR_FS_CATALOG_LANGUAGES)) != DIR_FS_CATALOG_LANGUAGES) {
      tep_redirect(tep_href_link('define_language.php', 'lngdir=' . $_GET['lngdir']));
    }
  }
*/
  $action = (isset($_GET['action']) ? $_GET['action'] : '');

  if (tep_not_null($action)) {
    switch ($action) {
      case 'save':
          $file = $stylefile;

          if (file_exists($file) && tep_is_writable($file)) {
            $new_file = fopen($file, 'w');
            $file_contents = stripslashes($_POST['file_contents']);
            fwrite($new_file, $file_contents, strlen($file_contents));
            fclose($new_file);
          }

			$messageStack->add_session(STYLES_EDITED, 'success');
			tep_redirect(tep_href_link('style_editor.php'));

        break;
    }
  }

  require('includes/template_top.php');
?>

    <table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', '1', HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
<?php
    $file = $stylefile;

    if (file_exists($file)) {
      $file_array = file($file);
      $contents = implode('', $file_array);

      $file_writeable = true;
      if (!tep_is_writable($file)) {
        $file_writeable = false;
        $messageStack->reset();
        $messageStack->add(sprintf(ERROR_FILE_NOT_WRITEABLE, $file), 'error');
        echo $messageStack->output();
      }

?>
          <tr><?php echo tep_draw_form('styles', 'style_editor.php', 'action=save'); ?>
            <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td class="main"><?php echo tep_draw_textarea_field('file_contents', 'soft', '80', '25', $contents, (($file_writeable) ? '' : 'readonly') . ' style="width: 100%;"'); ?></td>
              </tr>
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td class="smallText" align="right"><?php if ($file_writeable == true) { echo tep_draw_button(IMAGE_SAVE, 'disk', null, 'primary') . tep_draw_button(IMAGE_CANCEL, 'close', tep_href_link('style_editor.php')); } else { echo tep_draw_button(IMAGE_BACK, 'arrow-1-w', tep_href_link('style_editor.php')); } ?></td>
              </tr>
            </table></td>
          </form></tr>
          <tr>
            <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_EDIT_NOTE; ?></td>
          </tr>
<?php
    } else {
?>
          <tr>
            <td class="main"><strong><?php echo TEXT_FILE_DOES_NOT_EXIST; ?></strong></td>
          </tr>
          <tr>
            <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td><?php echo tep_draw_button(IMAGE_BACK, 'arrow-1-w', tep_href_link('style_editor.php')); ?></td>
          </tr>
<?php
    }
?>
        </table></td>
      </tr>
    </table>

<?php
  require('includes/template_bottom.php');
  require('includes/application_bottom.php');
?>
