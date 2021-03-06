<?php
/**
 * @package admin
 * @copyright Copyright 2003-2016 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version GIT: $Id:  Modified in v1.6.0 $
 */

  require('includes/application_top.php');
  require_once('includes/template/common/tplHtmlHeadLegacy.php');
  require_once('includes/template/common/tplHtmlHead.php');

  if (file_exists(DIR_FS_CATALOG . 'includes/classes/dbencdata.php')) require_once(DIR_FS_CATALOG . 'includes/classes/dbencdata.php');

  $set = (isset($_GET['set']) ? $_GET['set'] : (isset($_POST['set']) ? $_POST['set'] : ''));

  $is_ssl_protected = ($request_type == 'SSL');
  $file_extension = '.php';

  if (zen_not_null($set)) {
    switch ($set) {
      case 'shipping':
        $module_type = 'shipping';
        $module_directory = DIR_FS_CATALOG_MODULES . 'shipping/';
        $module_key = 'MODULE_SHIPPING_INSTALLED';
        define('HEADING_TITLE', HEADING_TITLE_MODULES_SHIPPING);
        $shipping_errors = '';
        if (zen_get_configuration_key_value('SHIPPING_ORIGIN_ZIP') == 'NONE' or zen_get_configuration_key_value('SHIPPING_ORIGIN_ZIP') == '') {
          $shipping_errors .= '<br />' . ERROR_SHIPPING_ORIGIN_ZIP;
        }
        if (zen_get_configuration_key_value('ORDER_WEIGHT_ZERO_STATUS') == '1' and !defined('MODULE_SHIPPING_FREESHIPPER_STATUS')) {
          $shipping_errors .= '<br />' . ERROR_ORDER_WEIGHT_ZERO_STATUS;
        }
        if (defined('MODULE_SHIPPING_USPS_STATUS') and (MODULE_SHIPPING_USPS_USERID=='NONE' or MODULE_SHIPPING_USPS_SERVER == 'test')) {
          $shipping_errors .= '<br />' . ERROR_USPS_STATUS;
        }
        if ($shipping_errors != '') {
          $messageStack->add(ERROR_SHIPPING_CONFIGURATION . $shipping_errors, 'caution');
        }
        break;
      case 'ordertotal':
        $module_type = 'order_total';
        $module_directory = DIR_FS_CATALOG_MODULES . 'order_total/';
        $module_key = 'MODULE_ORDER_TOTAL_INSTALLED';
        define('HEADING_TITLE', HEADING_TITLE_MODULES_ORDER_TOTAL);
        break;
      case 'payment':
      default:
        $module_type = 'payment';
        $module_directory = DIR_FS_CATALOG_MODULES . 'payment/';
        $module_key = 'MODULE_PAYMENT_INSTALLED';
        define('HEADING_TITLE', HEADING_TITLE_MODULES_PAYMENT);
        break;
    }
  }

  $action = (isset($_GET['action']) ? $_GET['action'] : '');

  if (zen_not_null($action)) {
    $admname = '{' . preg_replace('/[^\d\w]/', '*', zen_get_admin_name()) . '[' . (int)$_SESSION['admin_id'] . ']}';
    switch ($action) {
      case 'save':
        if (!$is_ssl_protected && in_array($class, array('paypaldp', 'authorizenet_aim', 'authorizenet_echeck'))) break;
        foreach($_POST['configuration'] as $key => $value) {
          if (is_array( $value ) ) {
            $value = implode( ", ", $value);
            $value = preg_replace ("/, --none--/", "", $value);
          }
          if (function_exists('dbenc_encrypt') && function_exists('dbenc_is_encrypted_value_key') && dbenc_is_encrypted_value_key($key)) {
            $value = dbenc_encrypt($value);
          }

          $db->Execute("update " . TABLE_CONFIGURATION . "
                        set configuration_value = '" . zen_db_input($value) . "'
                        where configuration_key = '" . zen_db_input($key) . "'");
        }
        $msg = sprintf(TEXT_EMAIL_MESSAGE_ADMIN_SETTINGS_CHANGED, preg_replace('/[^\d\w]/', '*', $_GET['module']), $admname);
        zen_record_admin_activity($msg, 'warning');
        $zco_notifier->notify('ADMIN_MODULE_CHANGED', $_GET['module'], $admname, $key, $value);
        $msg .= "\n\n" . sprintf(TEXT_EMAIL_ALERT_IP_ADDRESS, $_SERVER['REMOTE_ADDR']) . "\n";
        zen_mail(STORE_OWNER_EMAIL_ADDRESS, STORE_OWNER_EMAIL_ADDRESS, TEXT_EMAIL_SUBJECT_ADMIN_SETTINGS_CHANGED, $msg, STORE_NAME, EMAIL_FROM, array('EMAIL_MESSAGE_HTML'=>$msg), 'admin_settings_changed');
        zen_redirect(zen_admin_href_link(FILENAME_MODULES, 'set=' . $set . ($_GET['module'] != '' ? '&module=' . $_GET['module'] : '')));
        break;
      case 'install':
        $class = basename($_POST['module']);
        if (!$is_ssl_protected && in_array($class, array('paypaldp', 'authorizenet_aim', 'authorizenet_echeck'))) break;
        if (file_exists($module_directory . $class . $file_extension)) {
          include($module_directory . $class . $file_extension);
          $module = new $class;
          $msg = sprintf(TEXT_EMAIL_MESSAGE_ADMIN_MODULE_INSTALLED, preg_replace('/[^\d\w]/', '*', $_POST['module']), $admname);
          zen_record_admin_activity($msg, 'warning');
          $zco_notifier->notify('ADMIN_MODULE_ADDED', $_POST['module'], $admname);
          $msg .= "\n\n" . sprintf(TEXT_EMAIL_ALERT_IP_ADDRESS, $_SERVER['REMOTE_ADDR']) . "\n";
          zen_mail(STORE_OWNER_EMAIL_ADDRESS, STORE_OWNER_EMAIL_ADDRESS, TEXT_EMAIL_SUBJECT_ADMIN_SETTINGS_CHANGED, $msg, STORE_NAME, EMAIL_FROM, array('EMAIL_MESSAGE_HTML'=>$msg), 'admin_settings_changed');
          $result = $module->install();
        }
        if ($result != 'failed') {
          zen_redirect(zen_admin_href_link(FILENAME_MODULES, 'set=' . $set . '&module=' . $class . '&action=edit'));
        }
       break;
      case 'removeconfirm':
        $class = basename($_POST['module']);
        if (file_exists($module_directory . $class . $file_extension)) {
          include($module_directory . $class . $file_extension);
          $module = new $class;
          $msg = sprintf(TEXT_EMAIL_MESSAGE_ADMIN_MODULE_REMOVED, preg_replace('/[^\d\w]/', '*', $_POST['module']), $admname);
          zen_record_admin_activity($msg, 'warning');
          $zco_notifier->notify('ADMIN_MODULE_REMOVED', $_POST['module'], $admname);
          $msg .= "\n\n" . sprintf(TEXT_EMAIL_ALERT_IP_ADDRESS, $_SERVER['REMOTE_ADDR']) . "\n";
          zen_mail(STORE_OWNER_EMAIL_ADDRESS, STORE_OWNER_EMAIL_ADDRESS, TEXT_EMAIL_SUBJECT_ADMIN_SETTINGS_CHANGED, $msg, STORE_NAME, EMAIL_FROM, array('EMAIL_MESSAGE_HTML'=>$msg), 'admin_settings_changed');
          $result = $module->remove();
        }
        zen_redirect(zen_admin_href_link(FILENAME_MODULES, 'set=' . $set . '&module=' . $class));
       break;
      case 'commtest':
        $class = basename($_GET['module']);
        if (file_exists($module_directory . $class . $file_extension)) {
          include($module_directory . $class . $file_extension);
          $module = new $class;
          if (method_exists($module, 'testCommunications')) {
            $result = $module->testCommunications();
            $messageStack->add_session($result['text'], $result['type']);
            $zco_notifier->notify('ADMIN_MODULE_COMMUNICATIONS_TEST', $_GET['module'], $result['text'], $result['type']);
          } else {
            $result = TEXT_ERROR_NO_COMMTEST_OPTION_AVAILABLE;
            $messageStack->add_session($result, 'caution');
          }
        }
        zen_redirect(zen_admin_href_link(FILENAME_MODULES, 'set=' . $set . '&module=' . $class));
       break;
    }
  }
?>
</head>
<body>
<!-- header //-->
<?php require('includes/template/common/tplHeader.php'); ?>
<!-- header_eof //-->

<!-- body //-->
<div class="container-fluid">
<!-- body_text //-->

    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-6">
            <span class="pageHeading"><?php echo HEADING_TITLE; ?></span>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-9 col-lg-9 configurationColumnLeft">
            <table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_MODULES; ?></td>
                <td class="dataTableHeadingContent">&nbsp;</td>
                    <td class="dataTableHeadingContent"
                        align="right"><?php echo TABLE_HEADING_SORT_ORDER; ?></td>
                <td class="dataTableHeadingContent" colspan="2"><?php echo TEXT_MODULE_STATE; ?></td>
<?php
  if ($set == 'payment') {
?>
                        <td class="dataTableHeadingContent" align="center"
                            width="100"><?php echo TABLE_HEADING_ORDERS_STATUS; ?></td>
<?php } ?>
                    <td class="dataTableHeadingContent"
                        align="right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
              </tr>
<?php
  $directory_array = array();
  if ($dir = @dir($module_directory)) {
    while ($file = $dir->read()) {
      if (!is_dir($module_directory . $file)) {
        if (preg_match('~^[^\._].*\.php$~i', $file) > 0) {
          $directory_array[] = $file;
        }
      }
    }
    sort($directory_array);
    $dir->close();
  }

  $installed_modules = $temp_for_sort = array();
  for ($i=0, $n=sizeof($directory_array); $i<$n; $i++) {
    $file = $directory_array[$i];
    if (file_exists(DIR_FS_CATALOG_LANGUAGES . $_SESSION['language'] . '/modules/' . $module_type . '/' . $file)) {
      include(DIR_FS_CATALOG_LANGUAGES . $_SESSION['language'] . '/modules/' . $module_type . '/' . $file);
      include($module_directory . $file);
      $class = substr($file, 0, strrpos($file, '.'));
      if (class_exists($class)) {
        $module = new $class;
        // check if module passes the "check()" test (ie: enabled and valid, determined by each module individually)
        if ($check = $module->check()) {
           // determine sort orders (using up to 6 digits, then filename) and add to list of installed modules
          $temp_for_sort[$file] = str_pad((int)$module->sort_order, 6, "0", STR_PAD_LEFT) . $file;
          asort($temp_for_sort);
          $installed_modules = array_flip($temp_for_sort);
          if (method_exists($module, 'check_enabled_for_zone') && $module->enabled) $module->check_enabled_for_zone();
          if (method_exists($module, 'check_enabled') && $module->enabled) $module->check_enabled_for_zone();
        }

        //test for missing keys
        $keycount = 0;
        foreach ($module->keys() as $test) {
          if (defined($test)) {
            $keycount++;
          }
        }
        if ($keycount > 0 && $keycount != sizeof($module->keys())) {
           $module->title .= ' ' . WARNING_MODULES_MISSING_KEYS;
        }

        if (in_array($set, array('payment', 'shipping'))) {
        //   $moduleStatusIcon = ((!empty($module->enabled) && is_numeric($module->sort_order)) ? zen_image(DIR_WS_IMAGES . 'icon_status_green.gif') : ((empty($module->enabled) && is_numeric($module->sort_order)) ? zen_image(DIR_WS_IMAGES . 'icon_status_yellow.gif') : zen_image(DIR_WS_IMAGES . 'icon_status_red.gif')));
          if (!empty($module->enabled) && is_numeric($module->sort_order)) {
             $moduleStatusIcon = zen_image(DIR_WS_IMAGES . 'icon_status_green.gif');
             $moduleStatusText = TEXT_MODULE_STATUS_ENABLED;
          } else if (!$module->enabled && is_numeric($module->sort_order)) {
             $moduleStatusIcon = zen_image(DIR_WS_IMAGES . 'icon_status_black.gif'); 
             $moduleStatusText = TEXT_MODULE_STATUS_DISABLED;
          } else if (empty($module->enabled) && is_numeric($module->sort_order)) {
             $moduleStatusIcon = zen_image(DIR_WS_IMAGES . 'icon_status_yellow.gif'); 
             $moduleStatusText = TEXT_MODULE_STATUS_AMBER;
          } else { 
            $moduleStatusIcon = zen_image(DIR_WS_IMAGES . 'icon_status_red.gif');
             $moduleStatusText = '';

          }
        } else {
          $moduleStatusIcon = (is_numeric($module->sort_order) ? zen_image(DIR_WS_IMAGES . 'icon_status_green.gif') : zen_image(DIR_WS_IMAGES . 'icon_status_red.gif'));
          $moduleStatusText = (is_numeric($module->sort_order) ? TEXT_MODULE_STATUS_ENABLED : '');
        }

        if ((!isset($_GET['module']) || (isset($_GET['module']) && ($_GET['module'] == $class))) && !isset($mInfo)) {
          $module_info = array('code' => $module->code,
                               'title' => $module->title,
                               'description' => $module->description,
                               'status' => $check);
          $module_keys = $module->keys();
          $keys_extra = array();
          for ($j=0, $k=sizeof($module_keys); $j<$k; $j++) {
            $key_value = $db->Execute("select configuration_title, configuration_value, configuration_key,
                                          configuration_description, use_function, set_function
                                          from " . TABLE_CONFIGURATION . "
                                          where configuration_key = '" . zen_db_input($module_keys[$j]) . "'");
            if (!$key_value->EOF){
              $keys_extra[$module_keys[$j]]['title'] = $key_value->fields['configuration_title'];
              $keys_extra[$module_keys[$j]]['value'] = $key_value->fields['configuration_value'];
              $keys_extra[$module_keys[$j]]['description'] = $key_value->fields['configuration_description'];
              $keys_extra[$module_keys[$j]]['use_function'] = $key_value->fields['use_function'];
              $keys_extra[$module_keys[$j]]['set_function'] = $key_value->fields['set_function'];
            }
          }
          $module_info['keys'] = $keys_extra;
          $mInfo = new objectInfo($module_info);
        }

        if (isset($mInfo) && is_object($mInfo) && ($class == $mInfo->code) ) {
          if ($check > 0) {
            echo '              <tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . zen_admin_href_link(FILENAME_MODULES, 'set=' . $set . '&module=' . $class . '&action=edit') . '\'">' . "\n";
          } else {
            echo '              <tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">' . "\n";
          }
        } else {
          echo '              <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . zen_admin_href_link(FILENAME_MODULES, 'set=' . $set . '&module=' . $class) . '\'">' . "\n";
        }
//print_r($module) . '<br><BR>';
//echo (!empty($module->enabled) ? 'ENABLED' : 'NOT ENABLED') . ' vs ' . (is_numeric($module->sort_order) ? 'ON' : 'OFF') . '<BR><BR>' ;
?>
                <td class="dataTableContent"><?php echo $module->title; ?></td>
                <td class="dataTableContent"><?php echo (strstr($module->code, 'paypal') ? 'PayPal' : $module->code); ?></td>
                            <td class="dataTableContent" align="right"><?php if (is_numeric($module->sort_order)) echo $module->sort_order; ?></td>
                <td class="dataTableContent" align="right"><?php echo $moduleStatusIcon; ?></td>
                <td class="dataTableContent"><?php echo $moduleStatusText; ?></td>
<?php
  if ($set == 'payment') {
    $orders_status_name = $db->Execute("select orders_status_id, orders_status_name from " . TABLE_ORDERS_STATUS . " where orders_status_id='" . (int)$module->order_status . "' and language_id='" . (int)$_SESSION['languages_id'] . "'");
?>
                <td class="dataTableContent" align="left">&nbsp;&nbsp;&nbsp;<?php echo (is_numeric($module->sort_order) ? (($orders_status_name->fields['orders_status_id'] < 1) ? TEXT_DEFAULT : $orders_status_name->fields['orders_status_name']) : ''); ?>&nbsp;&nbsp;&nbsp;</td>
<?php } ?>
                <td class="dataTableContent" align="right">
<?php
  if (method_exists($module, 'testCommunications')) { echo '<a href="' . zen_admin_href_link(FILENAME_MODULES, 'set=' . $set . '&module=' . $class . '&action=commtest') . '" title="' . IMAGE_ICON_COMM . '"><i class="fa fa-bolt fa-fw"></i></a>'; }
  if (isset($mInfo) && is_object($mInfo) && ($class == $mInfo->code) ) { echo '<i class="fa fa-chevron-circle-right fa-fw"></i>'; } else { echo '<a href="' . zen_admin_href_link(FILENAME_MODULES, 'set=' . $set . '&module=' . $class) . '" title="' . IMAGE_ICON_INFO . '">' . '<i class="fa fa-exclamation-circle fa-fw"></i>' . '</a>'; }
?>
                </td>
              </tr>
<?php
      }
    } else {
      echo ERROR_MODULE_FILE_NOT_FOUND . DIR_FS_CATALOG_LANGUAGES . $_SESSION['language'] . '/modules/' . $module_type . '/' . $file . '<br />';
    }
  }
  ksort($installed_modules);
  $check = $db->Execute("select configuration_value
                         from " . TABLE_CONFIGURATION . "
                         where configuration_key = '" . zen_db_input($module_key) . "'");

  if ($check->RecordCount() > 0) {
    if ($check->fields['configuration_value'] != implode(';', $installed_modules)) {
      $db->Execute("update " . TABLE_CONFIGURATION . "
                    set configuration_value = '" . zen_db_input(implode(';', $installed_modules)) . "', last_modified = now()
                    where configuration_key = '" . zen_db_input($module_key) . "'");
    }
  } else {
    $db->Execute("insert into " . TABLE_CONFIGURATION . "
                (configuration_title, configuration_key, configuration_value,
                 configuration_description, configuration_group_id, sort_order, date_added)
                 values ('Installed Modules', '" . zen_db_input($module_key) . "', '" . zen_db_input(implode(';', $installed_modules)) . "',
                         'This is automatically updated. No need to edit.', '6', '0', now())");
  }
?>
              <tr>
                <td colspan="3" class="smallText"><?php echo TEXT_MODULE_DIRECTORY . ' ' . $module_directory; ?></td>
              </tr>
            </table></td>
<?php
  $heading = array();
  $contents = array();
  switch ($action) {
    case 'remove':
      $heading[] = array('text' => '<b>' . $mInfo->title . '</b>');

      $contents = array('form' => zen_draw_form('module_delete', FILENAME_MODULES, '&action=removeconfirm'));
      $contents[] = array('text' => '<input type="hidden" name="set" value="' . (isset($_GET['set']) ? $_GET['set'] : "") . '" />');
      $contents[] = array('text' => '<input type="hidden" name="module" value="' . (isset($_GET['module']) ? $_GET['module'] : "") . '"/>');
      $contents[] = array('text' => TEXT_DELETE_INTRO);

      $contents[] = array('align' => 'center', 'text' => '<br>' . zen_image_submit('button_remove.gif', IMAGE_DELETE, 'name="removeButton"') . ' <a href="' . zen_admin_href_link(FILENAME_MODULES, 'set=' . $set . ($_GET['module'] != '' ? '&module=' . $_GET['module'] : '')) . '">' . zen_image_button('button_cancel.gif', IMAGE_CANCEL, 'name="cancelButton"') . '</a>');
      break;
    case 'edit':
      if (!$is_ssl_protected && in_array($_GET['module'], array('paypaldp', 'authorizenet_aim', 'authorizenet_echeck'))) break;
      $keys = '';
      foreach($mInfo->keys as $key => $value) {
        $keys .= '<b>' . $value['title'] . '</b><br>' . $value['description'] . '<br>';
        if ($value['set_function']) {
          eval('$keys .= ' . $value['set_function'] . "'" . $value['value'] . "', '" . $key . "');");
        } else {
          $keys .= zen_draw_input_field('configuration[' . $key . ']', htmlspecialchars($value['value'], ENT_COMPAT, CHARSET, TRUE));
        }
        $keys .= '<br><br>';
      }
      $keys = substr($keys, 0, strrpos($keys, '<br><br>'));
      $heading[] = array('text' => '<b>' . $mInfo->title . '</b>');
      $contents = array('form' => zen_draw_form('modules', FILENAME_MODULES, 'set=' . $set . ($_GET['module'] != '' ? '&module=' . $_GET['module'] : '') . '&action=save', 'post', '', true));
      if (ADMIN_CONFIGURATION_KEY_ON == 1) {
        $contents[] = array('text' => '<strong>Key: ' . $mInfo->code . '</strong><br />');
      }
      $contents[] = array('text' => $keys);
      $contents[] = array('align' => 'center', 'text' => '<br>' . zen_image_submit('button_update.gif', IMAGE_UPDATE, 'name="saveButton"') . ' <a href="' . zen_admin_href_link(FILENAME_MODULES, 'set=' . $set . ($_GET['module'] != '' ? '&module=' . $_GET['module'] : '')) . '">' . zen_image_button('button_cancel.gif', IMAGE_CANCEL, 'name="cancelButton"') . '</a>');
      break;
    default:
      $heading[] = array('text' => '<b>' . $mInfo->title . '</b>');

      if ($mInfo->status == '1') {
        $keys = '';
        foreach($mInfo->keys as $value) {
          $keys .= '<b>' . $value['title'] . '</b><br>';
          if ($value['use_function']) {
            $use_function = $value['use_function'];
            if (preg_match('/->/', $use_function)) {
              $class_method = explode('->', $use_function);
              if (!class_exists($class_method[0])) include_once(DIR_WS_CLASSES . $class_method[0] . '.php');
              if (!is_object(${$class_method[0]})) ${$class_method[0]} = new $class_method[0]();
              $keys .= zen_call_function($class_method[1], $value['value'], ${$class_method[0]});
            } else {
              $keys .= zen_call_function($use_function, $value['value']);
            }
          } else {
            $keys .= $value['value'];
          }
          $keys .= '<br><br>';
        }

        if (ADMIN_CONFIGURATION_KEY_ON == 1) {
          $contents[] = array('text' => '<strong>Key: ' . $mInfo->code . '</strong><br />');
        }
        $keys = substr($keys, 0, strrpos($keys, '<br><br>'));
        if (!(!$is_ssl_protected && in_array($mInfo->code, array('paypaldp', 'authorizenet_aim', 'authorizenet_echeck')))) {
          $contents[] = array('align' => 'center', 'text' => '<a href="' . zen_admin_href_link(FILENAME_MODULES, 'set=' . $set . (isset($_GET['module']) ? '&module=' . $_GET['module'] : '') . '&action=edit') . '">' . zen_image_button('button_edit.gif', IMAGE_EDIT, 'name="editButton"') . '</a>');
        } else {
          $contents[] = array('align' => 'center', 'text' => TEXT_WARNING_SSL_EDIT);
        }
        $contents[] = array('align' => 'center', 'text' => '<a href="' . zen_admin_href_link(FILENAME_MODULES, 'set=' . $set . '&module=' . $mInfo->code . '&action=remove') . '">' . zen_image_button('button_module_remove.gif', IMAGE_MODULE_REMOVE, 'name="removeButton"') . '</a>');
        $contents[] = array('text' => '<br>' . $mInfo->description);
        $contents[] = array('text' => '<br>' . $keys);
      } else {
        if (!(!$is_ssl_protected && in_array($mInfo->code, array('paypaldp', 'authorizenet_aim', 'authorizenet_echeck')))) {
          $contents[] = array('align' => 'center', 'text' => zen_draw_form('install_module', FILENAME_MODULES, 'set=' . $set . '&action=install') . '<input type="hidden" name="module" value="' . $mInfo->code . '" />' . zen_image_submit('button_module_install.gif', IMAGE_MODULE_INSTALL, 'name="installButton"') . '</form>');
        } else {
          $contents[] = array('align' => 'center', 'text' => TEXT_WARNING_SSL_INSTALL);
        }
        $contents[] = array('text' => '<br>' . $mInfo->description);
      }
      break;
  }
            ?>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-3 col-lg-3 configurationColumnRight">
            <?php
  if ( (zen_not_null($heading)) && (zen_not_null($contents)) ) {
    $box = new box;
    echo $box->infoBox($heading, $contents);
  }
?>
        </div>
    </div>
<!-- body_text_eof //-->
</div>
<!-- body_eof //-->
<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<br>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
