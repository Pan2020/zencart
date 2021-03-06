<?php
/**
 * functions_general.php
 * General functions used throughout Zen Cart
 *
 * @package functions
 * @copyright Copyright 2003-2017 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Author: zcwilt  Fri Apr 22 22:16:43 2015 +0000 Modified in v1.5.5 $
 */
/**
 * Stop from parsing any further PHP code
*/
  function zen_exit() {
   session_write_close();
   exit();
  }

/**
 * Redirect to another page or site
 *
 * @param string $url url to redirect to
 * @param string $httpResponseCode
 */
function zen_redirect($url, $httpResponseCode = '')
{
    global $request_type;
    // Are we loading an SSL page?
    if ((ENABLE_SSL == 'true') && ($request_type == 'SSL')) {
        // yes, but a NONSSL url was supplied
        if (substr($url, 0, strlen(HTTP_SERVER . DIR_WS_CATALOG)) == HTTP_SERVER . DIR_WS_CATALOG) {
            // So, change it to SSL, based on site's configuration for SSL
            $url = HTTPS_SERVER . DIR_WS_HTTPS_CATALOG . substr($url, strlen(HTTP_SERVER . DIR_WS_CATALOG));
        }
    }

    // clean up URL before executing it
    $url = preg_replace('/&{2,}/', '&', $url);
    $url = preg_replace('/(&amp;)+/', '&amp;', $url);
    // header locates should not have the &amp; in the address it breaks things
    $url = preg_replace('/(&amp;)+/', '&', $url);

    if ($httpResponseCode == '') {
        session_write_close();
        header('Location: ' . $url);
    } else {
        session_write_close();
        header('Location: ' . $url, true, (int)$httpResponseCode);
    }

    exit();
}

/**
 * Parse the data used in the html tags to ensure the tags will not break.
 * Basically just an extension to the php strtr function
 *
 * @param string $data string to be parsed
 * @param array $parse needle to find
 * @return string
 */
function zen_parse_input_field_data($data, array $parse)
{
    return strtr(trim($data), $parse);
}

/**
 * Returns a string with conversions for security.
 *
 * @param string $string string to be parsed
 * @param array|false $translate contains a string to be translated, otherwise just quote is translated
 * @param boolean $protected Do we run htmlspecialchars over the string
 * @return string
 */
function zen_output_string($string, $translate = false, $protected = false)
{
    if ($protected == true) {
        return htmlspecialchars($string, ENT_COMPAT, CHARSET, true);
    }

    if ($translate === false) {
        return zen_parse_input_field_data($string, ['"' => '&quot;']);
    }

    return zen_parse_input_field_data($string, $translate);
}

/**
 * Returns a string with conversions for security.
 *
 * Simply calls the zen_ouput_string function
 * with parameters that run htmlspecialchars over the string
 * and converts quotes to html entities
 *
 * @param string $string string to be parsed
 * @return string
 */
function zen_output_string_protected($string)
{
    return zen_output_string($string, false, true);
}

/**
 * Returns a string with conversions for security.
 *
 * @param string $string string to be parsed
 * @return mixed
 */

function zen_sanitize_string($string)
{
    $string = preg_replace('/ +/', ' ', $string);

    return preg_replace("/[<>]/", '_', $string);
}

/**
 * Break a word in a string if it is longer than a specified length ($len)
 *
 * @param string $string string to be broken up
 * @param int $len maximum length allowed
 * @param string $break_char character to use at the end of the broken line
 * @return string
 */
function zen_break_string($string, $len, $break_char = '-')
{
    $l = 0;
    $output = '';
    for ($i = 0, $n = strlen($string); $i < $n; $i++) {
        $char = substr($string, $i, 1);
        if ($char != ' ') {
            $l++;
        } else {
            $l = 0;
        }
        if ($l > $len) {
            $l = 1;
            $output .= $break_char;
        }
        $output .= $char;
    }

    return $output;
}

/**
 * Return all HTTP GET variables, except those passed as a parameter
 *
 * The return is a urlencoded string
 *
 * @param mixed $exclude_array either a single or array of parameter names to be excluded from output
 * @return mixed|string
 */
function zen_get_all_get_params($exclude_array = [])
{
    if (!is_array($exclude_array)) {
        $exclude_array = [];
    }
    $exclude_array = array_merge($exclude_array, ['main_page', 'cmd', 'error', 'x', 'y']); // de-duplicating this is less performant than just letting it repeat the loop on duplicates
    if (function_exists('zen_session_name')) {
        $exclude_array[] = zen_session_name();
    }
    $get_url = '';
    if (is_array($_GET) && (count($_GET) > 0)) {
        foreach ($_GET as $key => $value) {
            if (!in_array($key, $exclude_array)) {
                if (!is_array($value)) {
                    if (strlen($value) > 0) {
                        $get_url .= zen_sanitize_string($key) . '=' . rawurlencode(stripslashes($value)) . '&';
                    }
                } else {
                    foreach (array_filter($value) as $arr) {
                        $get_url .= zen_sanitize_string($key) . '[]=' . rawurlencode(stripslashes($arr)) . '&';
                    }
                }
            }
        }
    }

    $get_url = preg_replace('/&{2,}/', '&', $get_url);
    $get_url = preg_replace('/(&amp;)+/', '&amp;', $get_url);

    return $get_url;
}

/**
 * Return all GET params as (usually hidden) POST params
 *
 * @param array $exclude_array parameters to exclude
 * @param boolean $hidden post as hidden
 * @return string
 */
function zen_post_all_get_params($exclude_array = [], $hidden = true)
{
    if (!is_array($exclude_array)) {
        $exclude_array = [];
    }
    $exclude_array = array_merge($exclude_array, [zen_session_name(), 'cmd', 'error', 'x', 'y']);
    $fields = '';
    if (is_array($_GET) && (count($_GET) > 0)) {
        foreach ($_GET as $key => $value) {
            if (!in_array($key, $exclude_array)) {
                if (!is_array($value)) {
                    if (strlen($value) > 0) {
                        if ($hidden) {
                            $fields .= zen_draw_hidden_field($key, $value);
                        } else {
                            $fields .= zen_draw_input_field($key, $value);
                        }
                    }
                } else {
                    foreach (array_filter($value) as $arr) {
                        if ($hidden) {
                            $fields .= zen_draw_hidden_field($key . '[]', $arr);
                        } else {
                            $fields .= zen_draw_input_field($key . '[]', $arr);
                        }
                    }
                }
            }
        }
    }

    return $fields;
}

/**
 * Returns details about the visitor's browser: pass the string you want to detect and this will return true/false
 *
 * @param string $component feature to report
 * @return string
 */
function zen_browser_detect($component)
{
    return stristr($_SERVER['HTTP_USER_AGENT'], $component);
}

/**
 * Wrapper function for round()
 *
 * @param float $value
 * @param int $precision
 * @return float
 */
function zen_round($value, $precision)
{
    $value = round($value * pow(10, $precision), 0);
    $value = $value / pow(10, $precision);

    return $value;
}

/**
 * Return table heading with sorting capabilities
 * @param string $sortby
 * @param string $colnum
 * @param string $heading
 * @return string
 */
  function zen_create_sort_heading($sortby, $colnum, $heading) {
    global $mainPage;
    $sort_prefix = '';
    $sort_suffix = '';

    if ($sortby) {
      $sort_prefix = '<a href="' . zen_href_link($mainPage, zen_get_all_get_params(array('page', 'info', 'sort')) . 'page=1&sort=' . $colnum . ($sortby == $colnum . 'a' ? 'd' : 'a')) . '" title="' . zen_output_string(TEXT_SORT_PRODUCTS . ($sortby == $colnum . 'd' || substr($sortby, 0, 1) != $colnum ? TEXT_ASCENDINGLY : TEXT_DESCENDINGLY) . TEXT_BY . $heading) . '" class="productListing-heading">' ;
      $sort_suffix = (substr($sortby, 0, 1) == $colnum ? (substr($sortby, 1, 1) == 'a' ? PRODUCT_LIST_SORT_ORDER_ASCENDING : PRODUCT_LIST_SORT_ORDER_DESCENDING) : '') . '</a>';
    }

    return $sort_prefix . $heading . $sort_suffix;
  }


/**
 * Return a product ID with attributes hash
 * @param string|int $prid
 * @param array|string $params
 * @return string
 */
  function zen_get_uprid($prid, $params) {
    $uprid = $prid;
    if ( !is_array($params) || strstr($prid, ':')) return $prid;

    foreach($params as $option => $value) {
      if (is_array($value)) {
        foreach($value as $opt => $val) {
          $uprid = $uprid . '{' . $option . '}' . trim($opt);
        }
      } else {
        $uprid = $uprid . '{' . $option . '}' . trim($value);
      }
    }

    $md_uprid = md5($uprid);
    return $prid . ':' . $md_uprid;
  }


/**
 * Return a product ID from a product ID with attributes
 * Alternate: simply (int) the product id
 * @param string $uprid   ie: '11:abcdef12345'
 * @return mixed
 */
  function zen_get_prid($uprid) {
    $pieces = explode(':', $uprid);
    return (int)$pieces[0];
  }


/**
 * Get the number of times a word/character is present in a string
 * @param string $string
 * @param string $needle
 * @return int
 */
  function zen_word_count($string, $needle) {
    $temp_array = preg_split('/'.$needle.'/', $string);

    return sizeof($temp_array);
  }


/**
 * Provide a count of the number of modules listed in the constant/string that is passed in (a value that is cached from the admin)
 * This is used to determine whether any payment/shipping/ot modules are enabled, which affects display on checkout etc
 * @param string $modules  Usually a constant which contains a cached value that the Admin UI updates automatically
 * @return int
 */
  function zen_count_modules($modules = '') {
    $count = 0;

    if (empty($modules)) return $count;

    $modules_array = preg_split('/;/', $modules);

    for ($i=0, $n=sizeof($modules_array); $i<$n; $i++) {
      $class = substr($modules_array[$i], 0, strrpos($modules_array[$i], '.'));

      if (isset($GLOBALS[$class]) && is_object($GLOBALS[$class])) {
        if ($GLOBALS[$class]->enabled) {
          $count++;
        }
      }
    }
    return $count;
  }

/**
 * Helper function to zen_count_modules
 * @return int
 */
  function zen_count_payment_modules() {
    return zen_count_modules(MODULE_PAYMENT_INSTALLED);
  }

/**
 * Helper function to zen_count_modules
 * @return int
 */
  function zen_count_shipping_modules() {
    return zen_count_modules(MODULE_SHIPPING_INSTALLED);
  }


/**
 * Convert array to string
 * @param array $array
 * @param string $exclude
 * @param string $equals
 * @param string $separator
 * @return string
 */
  function zen_array_to_string($array, $exclude = '', $equals = '=', $separator = '&') {
    if (!is_array($exclude)) $exclude = array();
    if (!is_array($array)) $array = array();

    $get_string = '';
    if (sizeof($array) > 0) {
      foreach($array as $key => $value) {
        if ( (!in_array($key, $exclude)) && ($key != 'x') && ($key != 'y') ) {
          $get_string .= $key . $equals . $value . $separator;
        }
      }
      $remove_chars = strlen($separator);
      $get_string = substr($get_string, 0, -$remove_chars);
    }

    return $get_string;
  }

/**
 * Determine whether the passed string/array is "not empty/0/NULL"
 * @param $value
 * @return bool
 */
  function zen_not_null($value) {
    if (is_array($value)) {
      if (sizeof($value) > 0) {
        return true;
      } else {
        return false;
      }
    } elseif( is_a( $value, 'queryFactoryResult' ) ) {
      if (sizeof($value->result) > 0) {
        return true;
      } else {
        return false;
      }
    } else {
      if ($value != '' && $value != 'NULL' && strlen(trim($value)) > 0) {
        return true;
      } else {
        return false;
      }
    }
  }

/**
 * @param string $string
 * @return int
 */
  function zen_string_to_int($string) {
    return (int)$string;
  }

/**
 * Return a random value
 *
 * @param int $min
 * @param int $max
 * @return int|null
 */
  function zen_rand($min = null, $max = null) {
    static $seeded;

    if (!isset($seeded)) {
      mt_srand((double)microtime()*1000000);
      $seeded = true;
    }

    if (isset($min, $max)) {
      if ($min >= $max) {
        return $min;
      }
      return mt_rand($min, $max);
    }

    return mt_rand();
  }

/**
 * Extract the TLD from the specified URL
 * @param string $url
 * @return bool|mixed|string
 */
  function zen_get_top_level_domain($url) {
    if (strpos($url, '://')) {
      $url = parse_url($url);
      $url = $url['host'];
    }
    $domain_array = explode('.', $url);
    $domain_size = count($domain_array);
    if ($domain_size > 1) {
      if (SESSION_USE_FQDN == 'True') return $url;
      if (is_numeric($domain_array[$domain_size-2]) && is_numeric($domain_array[$domain_size-1])) {
        return false;
      }

        $tld = '';
        foreach ($domain_array as $dPart)
        {
          if ($dPart !== 'www') $tld = $tld . '.' . $dPart;
        }

        return substr($tld, 1);
    }

      return false;
  }

  /**
   * Determine visitor's IP address, resolving any proxies where possible.
   *
   * @return string
   */
  function zen_get_ip_address() {
    $ip = '';
    /**
     * resolve any proxies
     */
    if (isset($_SERVER)) {
      if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
      } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
      } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED'];
      } elseif (isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
      } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_FORWARDED_FOR'];
      } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
        $ip = $_SERVER['HTTP_FORWARDED'];
      } else {
        $ip = $_SERVER['REMOTE_ADDR'];
      }
    }
    if (empty($ip)) {
      if (getenv('HTTP_X_FORWARDED_FOR')) {
        $ip = getenv('HTTP_X_FORWARDED_FOR');
      } elseif (getenv('HTTP_CLIENT_IP')) {
        $ip = getenv('HTTP_CLIENT_IP');
      } else {
        $ip = getenv('REMOTE_ADDR');
      }
    }

    /**
     * sanitize for validity as an IPv4 or IPv6 address
     */
    $ip = preg_replace('~[^a-fA-F0-9.:%/,]~', '', $ip);

    /**
     *  if it's still blank, set to a single dot
     */
    if (empty($ip)) $ip = '.';

    return $ip;
  }

/**
 * Determine the specified product+coupon combo has any restrictions associated
 * @param int $product_id
 * @param int $coupon_id
 * @return bool
 * @TODO - rename this function for more logical meaning such as including "coupon" and "restrictions"
 */
  function is_product_valid($product_id, $coupon_id) {
    global $db;
    $coupons_query = "SELECT * FROM " . TABLE_COUPON_RESTRICT . "
                      WHERE coupon_id = " . (int)$coupon_id . "
                      ORDER BY coupon_restrict ASC";

    $coupons = $db->Execute($coupons_query);

    $product_query = "SELECT products_model FROM " . TABLE_PRODUCTS . "
                      WHERE products_id = " . (int)$product_id;

    $product = $db->Execute($product_query);

    if (preg_match('/^GIFT/', $product->fields['products_model'])) {
      return false;
    }

// modified to manage restrictions better - leave commented for now
    if ($coupons->RecordCount() == 0) return true;
    if ($coupons->RecordCount() == 1) {
// If product is restricted(deny) and is same as tested product, deny
      if (($coupons->fields['product_id'] != 0) && $coupons->fields['product_id'] == (int)$product_id && $coupons->fields['coupon_restrict']=='Y') return false;
// If product is not restricted(allow) and is not same as tested prodcut deny
      if (($coupons->fields['product_id'] != 0) && $coupons->fields['product_id'] != (int)$product_id && $coupons->fields['coupon_restrict']=='N') return false;
// if category is restricted(deny) and product in category deny
      if (($coupons->fields['category_id'] !=0) && (zen_product_in_category($product_id, $coupons->fields['category_id'])) && ($coupons->fields['coupon_restrict']=='Y')) return false;
// if category is not restricted(allow) and product not in category deny
      if (($coupons->fields['category_id'] !=0) && (!zen_product_in_category($product_id, $coupons->fields['category_id'])) && ($coupons->fields['coupon_restrict']=='N')) return false;
      return true;
    }
    $allow_for_category = validate_for_category($product_id, $coupon_id);
    $allow_for_product = validate_for_product($product_id, $coupon_id);
//    echo '#'.$product_id . '#' . $allow_for_category;
//    echo '#'.$product_id . '#' . $allow_for_product;
    if ($allow_for_category === 'none') {
      if ($allow_for_product === 'none') return true;
      if ($allow_for_product === true) return true;
      if ($allow_for_product === false) return false;
    }
    if ($allow_for_category === true) {
      if ($allow_for_product === 'none') return true;
      if ($allow_for_product === true) return true;
      if ($allow_for_product === false) return false;
    }
    if ($allow_for_category === false) {
      if ($allow_for_product === 'none') return false;
      if ($allow_for_product === true) return true;
      if ($allow_for_product === false) return false;
    }
    return false; //should never get here
  }

/**
 * Return whether the specified product+coupon combo has any category restrictions associated
 * @param int $product_id
 * @param int $coupon_id
 * @return bool|string
 * @TODO rename this function to mention "coupon" and "restrictions"
 */
  function validate_for_category($product_id, $coupon_id) {
    global $db;
    $productCatPath = zen_get_product_path($product_id);
    $catPathArray = array_reverse(explode('_', $productCatPath));
    $sql = "SELECT count(*) AS total
            FROM " . TABLE_COUPON_RESTRICT . "
            WHERE category_id = -1
            AND coupon_restrict = 'Y'
            AND coupon_id = " . (int)$coupon_id . " LIMIT 1";
    $checkQuery = $db->execute($sql);
    foreach ($catPathArray as $catPath) {
      $sql = "SELECT * FROM " . TABLE_COUPON_RESTRICT . "
              WHERE category_id = " . (int)$catPath . "
              AND coupon_id = " . (int)$coupon_id;
      $result = $db->execute($sql);
      if ($result->recordCount() > 0 && $result->fields['coupon_restrict'] == 'N') return true;
      if ($result->recordCount() > 0 && $result->fields['coupon_restrict'] == 'Y') return false;
    }
    if ($checkQuery->fields['total'] > 0) {
      return false;
    }

    return 'none';
  }

/**
 * Return whether the specified product+coupon combo has any product restrictions associated
 * @param int $product_id
 * @param int $coupon_id
 * @return bool|string
 * @TODO rename this function to mention "coupon" and "restrictions"
 */
  function validate_for_product($product_id, $coupon_id) {
    global $db;
    $sql = "SELECT * FROM " . TABLE_COUPON_RESTRICT . "
            WHERE product_id = " . (int)$product_id . "
            AND coupon_id = " . (int)$coupon_id . " LIMIT 1";
    $result = $db->execute($sql);
    if ($result->recordCount() > 0) {
      if ($result->fields['coupon_restrict'] == 'N') return true;
      if ($result->fields['coupon_restrict'] == 'Y') return false;
    } else {
      return 'none';
    }
  }

/**
 * is coupon valid for specials and sales
 * @param int $product_id
 * @param int $coupon_id
 * @return bool
 */
  function is_coupon_valid_for_sales($product_id, $coupon_id) {
    global $db;
    $sql = "SELECT coupon_id, coupon_is_valid_for_sales
            FROM " . TABLE_COUPONS . "
            WHERE coupon_id = " . (int)$coupon_id;

    $result = $db->Execute($sql);

    // check whether coupon has been flagged as valid for sales
    if ($result->fields['coupon_is_valid_for_sales']) {
      return true;
    }

    // check for any special on $product_id
    $chk_product_on_sale = zen_get_products_special_price($product_id, true);
    if (!$chk_product_on_sale) {
      // check for any sale on $product_id
      $chk_product_on_sale = zen_get_products_special_price($product_id, false);
    }
    if ($chk_product_on_sale) {
      return false;
    }
    return true; // is not on special or sale
  }

/**
 * Alias to $db->prepareInput() for sanitizing db inserts
 * @param string $string
 * @return string
 */
  function zen_db_input($string) {
    global $db;
    return $db->prepareInput($string);
  }

/**
 * Recursively apply sanitizations to the string
 * USAGE: Instead of this function, normally one should use zen_output_string_protected() for data destined for the browser, and zen_db_input() for data destined to the db
 * @param string $string
 * @param bool $trimspace
 * @return string
 */
  function zen_db_prepare_input($string, $trimspace = true) {
    if (is_string($string)) {
      if (IS_ADMIN_FLAG === true && $trimspace == true) {
        return trim(stripslashes($string));
      } else {
        return trim(zen_sanitize_string(stripslashes($string)));
      }
    } elseif (is_array($string)) {
      foreach($string as $key => $value) {
        $string[$key] = zen_db_prepare_input($value);
      }
    }
    return $string;
  }

/**
 * Perform a bulk insert/update of the specified array of fields into one record
 * @param string $table
 * @param array $data
 * @param string $action
 * @param string $parameters
 * @return queryFactoryResult
 */
  function zen_db_perform($table, $data, $action = 'insert', $parameters = '') {
    global $db;
    if (strtolower($action) == 'insert') {
      $query = 'INSERT INTO ' . $table . ' (';
      foreach($data as $columns => $value) {
        $query .= $columns . ', ';
      }
      $query = substr($query, 0, -2) . ') VALUES (';
      foreach($data as $value) {
        switch ((string)$value) {
          case 'now()':
            $query .= 'now(), ';
            break;
          case 'NULL':
            $query .= 'null, ';
            break;
          default:
            $query .= '\'' . zen_db_input($value) . '\', ';
            break;
        }
      }
      $query = substr($query, 0, -2) . ')';
    } elseif (strtolower($action) == 'update') {
      $query = 'UPDATE ' . $table . ' SET ';
      foreach($data as $columns => $value) {
        switch ((string)$value) {
          case 'now()':
            $query .= $columns . ' = now(), ';
            break;
          case 'NULL':
            $query .= $columns . ' = null, ';
            break;
          default:
            $query .= $columns . ' = \'' . zen_db_input($value) . '\', ';
            break;
        }
      }
      $query = substr($query, 0, -2) . ' WHERE ' . $parameters;
    }

    return $db->Execute($query);
  }

/**
 * Retrieve the autoincrement ID of the last record inserted into the db
 * @return int
 */
  function zen_db_insert_id() {
    global $db;
    return $db->insert_ID();
  }

/**
 * return field type of db field
 *
 * @param string $tbl table name
 * @param string $fld field name
 * @return string
 */
  function zen_field_type($tbl, $fld) {
    global $db;
    $rs = $db->MetaColumns($tbl);
    $type = $rs[strtoupper($fld)]->type;
    return $type;
  }

/**
 * function to return field length
 * @param string $tbl table name
 * @param string $fld field name
 * @return string
 */
  function zen_field_length($tbl, $fld) {
    global $db;
    $rs = $db->MetaColumns($tbl);
    $length = $rs[strtoupper($fld)]->max_length;
    return $length;
  }

/**
 * return the size and maxlength settings in the form size="blah" maxlength="blah" based on maximum size being 50
 * example: zen_set_field_length(TABLE_CATEGORIES_DESCRIPTION, 'categories_name')
 *
 * @param string $tbl
 * @param string $fld
 * @param int $max
 * @param bool $override
 * @return string
 */
  function zen_set_field_length($tbl, $fld, $max=50, $override=false) {
    $field_length= zen_field_length($tbl, $fld);
    switch (true) {
      case (($override == false and $field_length > $max)):
        $length= 'size = "' . ($max+1) . '" maxlength= "' . $field_length . '"';
        break;
      default:
        $length= 'size = "' . ($field_length+1) . '" maxlength = "' . $field_length . '"';
        break;
    }
    return $length;
  }

/**
 * Set back button
 * @param bool $link_only
 * @return string
 */
  function zen_back_link($link_only = false) {
    if (sizeof($_SESSION['navigation']->path)-2 >= 0) {
      $back = sizeof($_SESSION['navigation']->path)-2;
      $link = zen_href_link($_SESSION['navigation']->path[$back]['page'], zen_array_to_string($_SESSION['navigation']->path[$back]['get'], array('action')), $_SESSION['navigation']->path[$back]['mode']);
    } else {
      if (isset($_SERVER['HTTP_REFERER']) && preg_match("~^".HTTP_SERVER."~i", $_SERVER['HTTP_REFERER']) ) {
      //if (isset($_SERVER['HTTP_REFERER']) && strstr($_SERVER['HTTP_REFERER'], str_replace(array('http://', 'https://'), '', HTTP_SERVER) ) ) {
        $link= $_SERVER['HTTP_REFERER'];
      } else {
        $link = zen_href_link(FILENAME_DEFAULT);
      }
      $_SESSION['navigation'] = new navigationHistory;
    }

    if ($link_only == true) {
      return $link;
    } else {
      return '<a class="btn-backlink" href="' . $link . '">';
    }
  }

/**
 * Return a random row from a database query resultset
 * @param string $query
 * @return queryFactoryResult
 */
  function zen_random_select($query) {
    global $db;
    $random_product = '';
    $random_query = $db->Execute($query);
    $num_rows = $random_query->RecordCount();
    if ($num_rows > 1) {
      $random_row = zen_rand(0, ($num_rows - 1));
      $random_query->Move($random_row);
    }
    return $random_query;
  }


/**
 * Truncate a string to a specified length, and optionally append a "more..." elipsis
 * @param string $str
 * @param int $len
 * @param string $more
 * @return string
 */
  function zen_trunc_string($str = "", $len = 150, $more = 'true') {
    if ($str == "") return $str;
    if (is_array($str)) return $str;
    $str = trim($str);
    $len = (int)$len;
    if ($len == 0) return '';
    // if it's les than the size given, then return it
    if (strlen($str) <= $len) return $str;
    // else get that size of text
    $str = substr($str, 0, $len);
    // backtrack to the end of a word
    if ($str != "") {
      // check to see if there are any spaces left
      if (!substr_count($str , " ")) {
        if ($more == 'true') $str .= "...";
        return $str;
      }
      // backtrack
      while(strlen($str) && ($str[strlen($str)-1] != " ")) {
        $str = substr($str, 0, -1);
      }
      $str = substr($str, 0, -1);
      if ($more == 'true') $str .= "...";
      if ($more != 'true' and $more != 'false') $str .= $more;
    }
    return $str;
  }


/**
 * Trims the passed filename to remove underscores and trailing '.php'
 * Used by sidebox builders to set current box id string
 * @param $box_id
 * @return mixed
 */
  function zen_get_box_id($box_id) {
    $box_id = str_replace('_', '', $box_id);
    $box_id = str_replace('.php', '', $box_id);
    return $box_id;
  }


/**
 * Switch buy now button based on call for price sold out etc.
 * @param int $product_id
 * @param string $link
 * @param bool $additional_link
 * @return bool|string
 */
  function zen_get_buy_now_button($product_id, $link, $additional_link = false) {
    global $db;

// show case only superceeds all other settings
    if (STORE_STATUS != '0') {
      return '<a class="btn-contactus" href="' . zen_href_link(FILENAME_CONTACT_US, '', 'SSL') . '">' .  TEXT_SHOWCASE_ONLY . '</a>';
    }

// 0 = normal shopping
// 1 = Login to shop
// 2 = Can browse but no prices
    // verify display of prices
      switch (true) {
        case (CUSTOMERS_APPROVAL == '1' and $_SESSION['customer_id'] == ''):
        // customer must be logged in to browse
        $login_for_price = '<a class="btn-login" href="' . zen_href_link(FILENAME_LOGIN, '', 'SSL') . '">' .  TEXT_LOGIN_FOR_PRICE_BUTTON_REPLACE . '</a>';
        return $login_for_price;
        case (CUSTOMERS_APPROVAL == '2' and $_SESSION['customer_id'] == ''):
        if (TEXT_LOGIN_FOR_PRICE_PRICE == '') {
          // show room only
          return TEXT_LOGIN_FOR_PRICE_BUTTON_REPLACE;
        } else {
          // customer may browse but no prices
          $login_for_price = '<a class="btn-login" href="' . zen_href_link(FILENAME_LOGIN, '', 'SSL') . '">' .  TEXT_LOGIN_FOR_PRICE_BUTTON_REPLACE . '</a>';
        }
        return $login_for_price;
        // show room only
        case (CUSTOMERS_APPROVAL == '3'):
        $login_for_price = TEXT_LOGIN_FOR_PRICE_BUTTON_REPLACE_SHOWROOM;
        return $login_for_price;
        case ((CUSTOMERS_APPROVAL_AUTHORIZATION != '0' and CUSTOMERS_APPROVAL_AUTHORIZATION != '3') and $_SESSION['customer_id'] == ''):
        // customer must be logged in to browse
        $login_for_price = TEXT_AUTHORIZATION_PENDING_BUTTON_REPLACE;
        return $login_for_price;
        case ((CUSTOMERS_APPROVAL_AUTHORIZATION == '3') and $_SESSION['customer_id'] == ''):
        // customer must be logged in and approved to add to cart
        $login_for_price = '<a class="btn-login" href="' . zen_href_link(FILENAME_LOGIN, '', 'SSL') . '">' .  TEXT_LOGIN_TO_SHOP_BUTTON_REPLACE . '</a>';
        return $login_for_price;
        case (CUSTOMERS_APPROVAL_AUTHORIZATION != '0' and $_SESSION['customers_authorization'] > '0'):
        // customer must be logged in to browse
        $login_for_price = TEXT_AUTHORIZATION_PENDING_BUTTON_REPLACE;
        return $login_for_price;
        case ((int)$_SESSION['customers_authorization'] >= 2):
        // customer is logged in and was changed to must be approved to buy
        $login_for_price = TEXT_AUTHORIZATION_PENDING_BUTTON_REPLACE;
        return $login_for_price;
      }

    $button_check = $db->Execute("select product_is_call, products_quantity from " . TABLE_PRODUCTS . " where products_id = " . (int)$product_id);
    switch (true) {
// cannot be added to the cart
    case (zen_get_products_allow_add_to_cart($product_id) == 'N'):
      return $additional_link;
    case ($button_check->fields['product_is_call'] == '1'):
      $return_button = '<a class="btn-callforprice" href="' . zen_href_link(FILENAME_CONTACT_US, '', 'SSL') . '">' . TEXT_CALL_FOR_PRICE . '</a>';
//      $return_button = '';
      break;
    case ($button_check->fields['products_quantity'] <= 0 and SHOW_PRODUCTS_SOLD_OUT_IMAGE == '1'):
      if ($_GET['main_page'] == zen_get_info_page($product_id)) {
        $return_button = zen_image_button(BUTTON_IMAGE_SOLD_OUT, BUTTON_SOLD_OUT_ALT);
      } else {
        $return_button = zen_image_button(BUTTON_IMAGE_SOLD_OUT_SMALL, BUTTON_SOLD_OUT_SMALL_ALT);
      }
      break;
    default:
      $return_button = $link;
      break;
    }
    if ($return_button != $link and $additional_link != false) {
      return $additional_link . '<br />' . $return_button;
    } else {
      return $return_button;
    }
  }


/**
 * check to see if free shipping rules allow the specified shipping module to be enabled or to disable it in lieu of being free
 *
 * @param string $shipping_module
 * @return bool
 */
  function zen_get_shipping_enabled($shipping_module) {
    global $zcRequest;

    // for admin always true if installed
    if (IS_ADMIN_FLAG === true && $zcRequest->readGet('cmd') == FILENAME_MODULES) {
      return true;
    }

    $check_cart_free = $_SESSION['cart']->in_cart_check('product_is_always_free_shipping','1');
    $check_cart_cnt = $_SESSION['cart']->count_contents();
    $check_cart_weight = $_SESSION['cart']->show_weight();

    switch(true) {
      // for admin always true if installed
      // left for future expansion
      case (IS_ADMIN_FLAG === true && $zcRequest->readGet('cmd') == FILENAME_MODULES):
        return true;
      // Free Shipping when 0 weight - enable freeshipper - ORDER_WEIGHT_ZERO_STATUS must be on
      case (ORDER_WEIGHT_ZERO_STATUS == '1' and ($check_cart_weight == 0 and $shipping_module == 'freeshipper')):
        return true;
      // Free Shipping when 0 weight - disable everyone - ORDER_WEIGHT_ZERO_STATUS must be on
      case (ORDER_WEIGHT_ZERO_STATUS == '1' and ($check_cart_weight == 0 and $shipping_module != 'freeshipper')):
        return false;
      case (($_SESSION['cart']->free_shipping_items() == $check_cart_cnt) and $shipping_module == 'freeshipper'):
        return true;
      case (($_SESSION['cart']->free_shipping_items() == $check_cart_cnt) and $shipping_module != 'freeshipper'):
        return false;
      // Always free shipping only true - enable freeshipper
      case (($check_cart_free == $check_cart_cnt) and $shipping_module == 'freeshipper'):
        return true;
      // Always free shipping only true - disable everyone
      case (($check_cart_free == $check_cart_cnt) and $shipping_module != 'freeshipper'):
        return false;
      // Always free shipping only is false - disable freeshipper
      case (($check_cart_free != $check_cart_cnt) and $shipping_module == 'freeshipper'):
        return false;
      default:
        return true;
    }
  }

/**
 * remove common HTML from text for display as paragraph
 *
 * @param string $clean_it
 * @param string $extraTags
 * @return mixed|string
 */
  function zen_clean_html($clean_it, $extraTags = '') {
    if (!is_array($extraTags)) $extraTags = array($extraTags);

    // remove any embedded javascript
    $clean_it = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $clean_it);

    $clean_it = preg_replace('/\r/', ' ', $clean_it);
    $clean_it = preg_replace('/\t/', ' ', $clean_it);
    $clean_it = preg_replace('/\n/', ' ', $clean_it);

    $clean_it= nl2br($clean_it);

  // update breaks with a space for text displays in all listings with descriptions
    $clean_it = preg_replace('~(<br ?/?>|</?p>)~', ' ', $clean_it);

// temporary fix more for reviews than anything else
    $clean_it = str_replace('<span class="smallText">', ' ', $clean_it);
    $clean_it = str_replace('</span>', ' ', $clean_it);

// clean general and specific tags:
    $taglist = array('strong','b','u','i','em');
    $taglist = array_merge($taglist, (is_array($extraTags) ? $extraTags : array($extraTags)));
    foreach ($taglist as $tofind) {
      if ($tofind != '') $clean_it = preg_replace("/<[\/\!]*?" . $tofind . "[^<>]*?>/si", ' ', $clean_it);
    }

// remove any double-spaces created by cleanups:
    $clean_it = preg_replace('/[ ]+/', ' ', $clean_it);

// remove other html code to prevent problems on display of text
    $clean_it = strip_tags($clean_it);
    return $clean_it;
  }

/**
 * template-helper to find module directory based on whether template overrides exist for the specified module
 * include template specific immediate /modules files
 * new_products, products_new_listing, featured_products, featured_products_listing, product_listing, specials_index, upcoming,
 * products_all_listing, products_discount_prices, also_purchased_products
 * @param string $check_file
 * @param string $dir_only
 * @return string
 */
  function zen_get_module_directory($check_file, $dir_only = 'false') {
    global $template_dir;

    $zv_filename = $check_file;
    if (!strstr($zv_filename, '.php')) $zv_filename .= '.php';

    if (file_exists(DIR_WS_MODULES . $template_dir . '/' . $zv_filename)) {
      $template_dir_select = $template_dir . '/';
    } else if (file_exists(DIR_WS_MODULES . 'shared' . '/' . $zv_filename)) {
      $template_dir_select = 'shared/';
    } else {
      $template_dir_select = '';
    }

    if ($dir_only == 'true') {
      return $template_dir_select;
    } else {
      return $template_dir_select . $zv_filename;
    }
  }


/**
 * find override template file or default to shared or template_default
 *
 * @param string $check_directory
 * @param string $check_file
 * @param string $dir_only
 * @return string
 */
  function zen_get_file_directory($check_directory, $check_file, $dir_only = 'false') {
    global $template_dir;

    $zv_filename = $check_file;
    if (!strstr($zv_filename, '.php')) $zv_filename .= '.php';

    if (file_exists($check_directory . $template_dir . '/' . $zv_filename)) {
      $zv_directory = $check_directory . $template_dir . '/';
    } else if (file_exists($check_directory . 'shared' . '/' . $zv_filename)) {
      $zv_directory = $check_directory . 'shared' . '/';
    } else {
      $zv_directory = $check_directory;
    }

    if ($dir_only == 'true') {
      return $zv_directory;
    } else {
      return $zv_directory . $zv_filename;
    }
  }

/**
 * check to see if database stored GET terms are in the URL as $_GET parameters
 * This is used to determine which filters should be applied
 * @return bool
 */
  function zen_check_url_get_terms() {
    global $db;
    $sql = "select * from " . TABLE_GET_TERMS_TO_FILTER;
    $query_result = $db->Execute($sql);
    $retVal = false;
    foreach ($query_result as $row) {
      if (isset($_GET[$row['get_term_name']]) && zen_not_null($_GET[$row['get_term_name']])) $retVal = true;
    }
    return $retVal;
  }

/**
 * replacement for fmod to manage values < 1
 *
 * @param float $x
 * @param float $y
 * @return int|number
 */
  function fmod_round($x, $y) {
    if ($y == 0) {
      return 0;
    }
    $x = strval($x);
    $y = strval($y);
    $zc_round = ($x*1000)/($y*1000);
    $zc_round_ceil = round($zc_round,0);
    $multiplier = $zc_round_ceil * $y;
    $results = abs(round($x - $multiplier, 6));
     return $results;
  }

/**
 * return truncated paragraph
 * @param string $paragraph
 * @param int $size
 * @param string $word
 * @return string
 */
  function zen_truncate_paragraph($paragraph, $size = 100, $word = ' ') {
    $zv_paragraph = "";
    $word = explode(" ", $paragraph);
    $zv_total = count($word);
    if ($zv_total > $size) {
      for ($x=0; $x < $size; $x++) {
        $zv_paragraph = $zv_paragraph . $word[$x] . " ";
      }
      $zv_paragraph = trim($zv_paragraph);
    } else {
      $zv_paragraph = trim($paragraph);
    }
    return $zv_paragraph;
  }


/**
 * returns a pulldown array with zones defined for the specified country
 * used by zen_prepare_country_zones_pull_down()
 *
 * @param int $country_id
 * @return array for pulldown
 */
  function zen_get_country_zones($country_id) {
    global $db;
    $zones_array = array();
    $zones = $db->Execute("select zone_id, zone_name
                           from " . TABLE_ZONES . "
                           where zone_country_id = " . (int)$country_id . "
                           order by zone_name");
    foreach ($zones as $zone) {
      $zones_array[] = array('id' => $zone['zone_id'], 'text' => $zone['zone_name']);
    }

    return $zones_array;
  }

/**
 * return an array with country names and matching zones to be used in pulldown menus
 *
 * @TODO - review the Netscape and IE support here
 *
 * @param string $country_id
 * @return array
 */
  function zen_prepare_country_zones_pull_down($country_id = '') {
// preset the width of the drop-down for Netscape
    $pre = '';
    if ( (!zen_browser_detect('MSIE')) && (zen_browser_detect('Mozilla/4')) ) {
      for ($i=0; $i<45; $i++) $pre .= '&nbsp;';
    }

    $zones = zen_get_country_zones($country_id);

    if (sizeof($zones) > 0) {
      $zones_select = array(array('id' => '', 'text' => PLEASE_SELECT));
      $zones = array_merge($zones_select, $zones);
    } else {
      $zones = array(array('id' => '', 'text' => TYPE_BELOW));
// create dummy options for Netscape to preset the height of the drop-down
      if ( (!zen_browser_detect('MSIE')) && (zen_browser_detect('Mozilla/4')) ) {
        for ($i=0; $i<9; $i++) {
          $zones[] = array('id' => '', 'text' => $pre);
        }
      }
    }

    return $zones;
  }

/**
 * builds javascript to dynamically update the states/provinces list when the country is changed
 * TABLES: zones
 *
 * return string
 *
 * @param int $country
 * @param string $form
 * @param string $field
 * @param bool $showTextField
 * @return string
 */
  function zen_js_zone_list($country, $form, $field, $showTextField = true) {
    global $db;
    $countries = $db->Execute("select distinct zone_country_id
                               from " . TABLE_ZONES . "
                               order by zone_country_id");
    $num_country = 1;
    $output_string = '';
    while (!$countries->EOF) {
      if ($num_country == 1) {
        $output_string .= '  if (' . $country . ' == "' . $countries->fields['zone_country_id'] . '") {' . "\n";
      } else {
        $output_string .= '  } else if (' . $country . ' == "' . $countries->fields['zone_country_id'] . '") {' . "\n";
      }

      $states = $db->Execute("select zone_name, zone_id
                              from " . TABLE_ZONES . "
                              where zone_country_id = '" . $countries->fields['zone_country_id'] . "'
                              order by zone_name");
      $num_state = 1;
      while (!$states->EOF) {
        if ($num_state == '1') $output_string .= '    ' . $form . '.' . $field . '.options[0] = new Option("' . PLEASE_SELECT . '", "");' . "\n";
        $output_string .= '    ' . $form . '.' . $field . '.options[' . $num_state . '] = new Option("' . $states->fields['zone_name'] . '", "' . $states->fields['zone_id'] . '"';
        if($states->fields['zone_id'] == $zone_id) $output_string .= ',1,1';
        $output_string .= ');' . "\n";
        $num_state++;
        $states->MoveNext();
      }
      $num_country++;
      $countries->MoveNext();
      if (IS_ADMIN_FLAG === false) $output_string .= '    hideStateField(' . $form . ');' . "\n" ;
    }
    if (IS_ADMIN_FLAG === false) {
      $output_string .= '  } else {' . "\n" .
                        '    ' . $form . '.' . $field . '.options[0] = new Option("' . TYPE_BELOW . '", "");' . "\n" .
                        '    showStateField(' . $form . ');' . "\n" .
                        '  }' . "\n";
      return $output_string;
    }
      $output_string .= '  }';
      if ($showTextField) {
          $output_string .= ' else {' . "\n" .
                      '    ' . $form . '.' . $field . '.options[0] = new Option("' . TYPE_BELOW . '", "");' . "\n" .
                      '  }' . "\n";
      }
    return $output_string;
  }

/**
 * strip out accented characters to reasonable approximations of english equivalents
 *
 * @param string $s
 * @return string
 */
  function replace_accents($s) {
    $skipPreg = (defined('OVERRIDE_REPLACE_ACCENTS_WITH_HTMLENTITIES') && OVERRIDE_REPLACE_ACCENTS_WITH_HTMLENTITIES == 'TRUE') ? TRUE : FALSE;
    $s = htmlentities($s, ENT_COMPAT, CHARSET);
    if ($skipPreg == FALSE) {
      $s = preg_replace ('/&([a-zA-Z])(uml|acute|elig|grave|circ|tilde|cedil|ring|quest|slash|caron);/', '$1', $s);
    }
    $s = html_entity_decode($s);
    return $s;
  }

/**
 * function to override PHP's is_writable() which can occasionally be unreliable due to O/S and F/S differences
 * attempts to open the specified file for writing. Returns true if successful, false if not.
 * if a directory is specified, uses PHP's is_writable() anyway
 *
 * @var string $filepath
 * @param bool $make_unwritable
 * @return bool
 */
  function is__writeable($filepath, $make_unwritable = true) {
    if (is_dir($filepath)) return is_writable($filepath);
    $fp = @fopen($filepath, 'a');
    if ($fp) {
      @fclose($fp);
//       if ($make_unwritable) set_unwritable($filepath);
      $fp = @fopen($filepath, 'a');
      if ($fp) {
        @fclose($fp);
        return true;
      }
    }
    return false;
  }
/**
 * attempts to make the specified file read-only
 *
 * @var string $filepath
 * @return boolean
 */
  function set_unwritable($filepath) {
    return @chmod($filepath, 0444);
  }

/**
 * convert supplied string to UTF-8, dropping any symbols which cannot be translated easily
 * useful for submitting cleaned-up data to payment gateways or other external services, esp if the data was copy+pasted from windows docs via windows browser to store in database
 *
 * @param string $string
 * @return string
 */
  function charsetConvertWinToUtf8($string) {
    if (function_exists('iconv')) $string = iconv("Windows-1252", "ISO-8859-1//IGNORE", $string);
    $string = htmlentities($string, ENT_QUOTES, 'UTF-8');
    return $string;
  }

/**
 * Convert supplied string to/from entities between charsets, to sanitize data from payment gateway
 * @param string $string
 * @return string
 */
  function charsetClean($string) {
    if (preg_replace('/[^a-z0-9]/', '', strtolower(CHARSET)) == 'utf8') return $string;
    if (function_exists('iconv')) $string = iconv("Windows-1252", CHARSET . "//IGNORE", $string);
    $string = htmlentities($string, ENT_QUOTES, 'UTF-8');
    $string = html_entity_decode($string, ENT_QUOTES, CHARSET);
    return $string;
  }

/**
 * Helper function to check whether the current instance is using SSL or not.
 * @return string SSL or NONSSL
 */
  function getConnectionType() {
    global $request_type;
    return $request_type;
  }

/**
 * Dump the requested data to log or screen
 * @param string $mode
 * @param string $out
 */
  function utilDumpRequest($mode='p', $out = 'log') {
    if ($mode =='p') {
      $val = '<pre>DEBUG request: ' . print_r($_REQUEST, TRUE);
    } else {
      @ob_start();
      var_dump('DEBUG request: ', $_REQUEST);
      $val = @ob_get_contents();
      @ob_end_clean();
    }
    if ($out == 'log' || $out == 'l') {
      error_log($val);
    } else if ($out == 'die' || $out == 'd') {
      die($val);
    } else if ($out == 'echo' || $out == 'e') {
      echo $val;
    }
  }

/**
 * Prepend 'http://' to the specified URL if not already http:// or https://
 * @param string $url
 * @return string
 */
  function fixup_url($url)
  {
    if (!preg_match('#^https?://#', $url)) {
      $url = 'http://' . $url;
    }
    return $url;
  }
  /**
   * function issetorArray
   *
   * returns an array[key] or default value if key does not exist
   *
   * @param array $array
   * @param $key
   * @param null $default
   * @return mixed
   */
  function issetorArray(array $array, $key, $default = null) {
      return isset($array[$key]) ? $array[$key] : $default;
  }

  /**
   * Recursively apply htmlentities on the passed string
   * Useful for preparing json output and ajax responses
   *
   * @param string|array $mixed_value
   * @param int $flags
   * @param string $encoding
   * @param bool $double_encode
   * @return array|string
   */
  function htmlentities_recurse($mixed_value, $flags = ENT_QUOTES, $encoding = 'utf-8', $double_encode = true) {
      $result = array();
      if (!is_array ($mixed_value)) {
          return htmlentities ((string)$mixed_value, $flags, $encoding, $double_encode);
      }
      if (is_array($mixed_value)) {
          $result = array ();
          foreach ($mixed_value as $key => $value) {
              $result[$key] = htmlentities_recurse ($value, $flags, $encoding, $double_encode);
          }
      }
      return $result;
  }

/////////////////////////////////////////////
////
// call additional function files
// prices and quantities
  require DIR_FS_CATALOG . DIR_WS_FUNCTIONS . 'functions_prices.php';
// taxes
  require DIR_FS_CATALOG . DIR_WS_FUNCTIONS . 'functions_taxes.php';
// gv and coupons
  require DIR_FS_CATALOG . DIR_WS_FUNCTIONS . 'functions_gvcoupons.php';
// categories, paths, pulldowns
  require DIR_FS_CATALOG . DIR_WS_FUNCTIONS . 'functions_categories.php';
// customers and addresses
  require DIR_FS_CATALOG . DIR_WS_FUNCTIONS . 'functions_customers.php';
// lookup information
  require DIR_FS_CATALOG . DIR_WS_FUNCTIONS . 'functions_lookups.php';

  require DIR_FS_CATALOG . DIR_WS_FUNCTIONS . 'functions_dates.php';

  require DIR_FS_CATALOG . DIR_WS_FUNCTIONS . 'functions_search.php';
////
/////////////////////////////////////////////
