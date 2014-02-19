<?php

require_once 'securitycheck.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function securitycheck_civicrm_config(&$config) {
  _securitycheck_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function securitycheck_civicrm_xmlMenu(&$files) {
  _securitycheck_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function securitycheck_civicrm_install() {
  return _securitycheck_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function securitycheck_civicrm_uninstall() {
  return _securitycheck_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function securitycheck_civicrm_enable() {
  return _securitycheck_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function securitycheck_civicrm_disable() {
  return _securitycheck_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function securitycheck_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _securitycheck_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function securitycheck_civicrm_managed(&$entities) {
  return _securitycheck_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 */
function securitycheck_civicrm_caseTypes(&$caseTypes) {
  _securitycheck_civix_civicrm_caseTypes($caseTypes);
}

/**
 * CMS have a different pattern to their default file path and URL.
 *
 * @TODO This function might be better shared in CRM_Utils_Check
 * class, but that class doesn't yet exist.
 */
function getFilePathMarker() {
  $config = CRM_Core_Config::singleton();
  switch ($config->userFramework) {
    case 'Joomla':
      return '/media/';
    default:
      return '/files/';
  }
}

function createDocUrl($topic) {
  return CRM_Utils_System::getWikiBaseURL() . $topic;
}

/**
 * Check if our logfile is directly accessible.
 *
 * Per CiviCRM default the logfile sits in a folder which is
 * web-accessible, and is protected by a default .htaccess
 * configuration. If server config causes the .htaccess not to
 * function as intended, there may be information disclosure.
 *
 * The debug log may be jam-packed with sensitive data, we don't
 * want that.
 *
 * Being able to be retrieved directly doesn't mean the logfile
 * is browseable or visible to search engines; it means it can be
 * requested directly.
 *
 * @return array of messages
 * @see CRM-14091
 */
function checkLogFileIsNotAccessible() {
  $messages = array();
  $config = CRM_Core_Config::singleton();
  $log = CRM_Core_Error::createDebugLogger();
  $log_filename = $log->_filename;
  $filePathMarker = getFilePathMarker();
  // Hazard a guess at the URL of the logfile, based on common
  // CiviCRM layouts.
  if ($upload_url = explode($filePathMarker, $config->imageUploadURL)) {
    $url[] = $upload_url[0];
    if ($log_path = explode($filePathMarker, $log_filename)) {
      $url[] = $log_path[1];
      $log_url = implode($filePathMarker, $url);
      $docs_url = createDocUrl('checkLogFileIsNotAccessible');
      if ($log = @file_get_contents($log_url)) {
        $msg = 'The <a href="%1">CiviCRM debug log</a> should not be downloadable.'
          . '<br />' .
          '<a href="%2">Read more about this warning</a>';
        $messages[] = ts($msg, array(1 => $log_url, 2 => $docs_url));
      }
    }
  }

  return $messages;
}

/**
 * Check if our uploads directory has accessible files.
 *
 * We'll test a handful of files randomly. Hazard a guess at the URL
 * of the uploads dir, based on common CiviCRM layouts. Try and
 * request the files, and if any are successfully retrieved, warn.
 *
 * Being retrievable doesn't mean the files are browseable or visible
 * to search engines; it only means they can be requested directly.
 *
 * @return array of messages
 * @see CRM-14091
 *
 * @TODO: Test with WordPress, Joomla.
 */
function checkUploadsAreNotAccessible() {
  $messages = array();
  $config = CRM_Core_Config::singleton();
  $filePathMarker = getFilePathMarker();
  
  if ($upload_url = explode($filePathMarker, $config->imageUploadURL)) {
    if ($files = glob($config->uploadDir . '/*')) {
      for ($i = 0;$i < 3; $i++) {
        $f = array_rand($files);
        if ($file_path = explode($filePathMarker, $files[$f])) {
          $url = implode($filePathMarker, array($upload_url[0], $file_path[1]));
          if ($file = @file_get_contents($url)) {
            $msg = 'Files in the upload directory should not be downloadable.'
              . '<br />' .
              '<a href="%1">Read more about this warning</a>';
            $docs_url = createDocUrl('checkUploadsAreNotAccessible');
            $messages[] = ts($msg, array(1 => $docs_url));
            
          }
        }
      }
    }
  }
  
  return $messages;
}

/**
 * Determine whether $url is a public, browsable listing for $dir
 *
 * @param string $dir local dir path
 * @param string $url public URL
 * @return bool
 */
function isBrowsable($dir, $url) {
  if (empty($dir) || empty($url) || !is_dir($dir)) {
    return FALSE;
  }
  
  $result = FALSE;
  $file = 'delete-this-' . CRM_Utils_String::createRandom(10, CRM_Utils_String::ALPHANUMERIC);
  
  // this could be a new system with no uploads (yet) -- so we'll make a file
  file_put_contents("$dir/$file", "delete me");
  $content = @file_get_contents("$url");
  if (stristr($content, $file)) {
    $result = TRUE;
  }
  unlink("$dir/$file");
  
  return $result;
}

/**
 * Restrict remote users from browsing the given directory.
 *
 * @param $publicDir
 */
function restrictBrowsing($publicDir) {
  if (!is_dir($publicDir) || !is_writable($publicDir)) {
    return;
  }
  
  // base dir
  $nobrowse = realpath($publicDir) . '/index.html';
  if (!file_exists($nobrowse)) {
    @file_put_contents($nobrowse, '');
  }
  
  // child dirs
  $dir = new RecursiveDirectoryIterator($publicDir);
  foreach ($dir as $name => $object) {
    if (is_dir($name) && $name != '..') {
      $nobrowse = realpath($name) . '/index.html';
      if (!file_exists($nobrowse)) {
        @file_put_contents($nobrowse, '');
      }
    }
  }
}

/**
 * Check if our uploads or ConfigAndLog directories have browseable
 * listings.
 *
 * Retrieve a listing of files from the local filesystem, and the
 * corresponding path via HTTP. Then check and see if the local
 * files are represented in the HTTP result; if so then warn. This
 * MAY trigger false positives (if you have files named 'a', 'e'
 * we'll probably match that).
 *
 * @return array of messages
 * @see CRM-14091
 *
 * @TODO: Test with WordPress, Joomla.
 */
function checkDirectoriesAreNotBrowseable() {
  $messages = array();
  $config = CRM_Core_Config::singleton();
  $publicDirs = array(
    $config->imageUploadDir => $config->imageUploadURL,
  );
  
  // Setup index.html files to prevent browsing
  foreach ($publicDirs as $publicDir => $publicUrl) {
    restrictBrowsing($publicDir);
  }
  
  // Test that $publicDir is not browsable
  foreach ($publicDirs as $publicDir => $publicUrl) {
    if (isBrowsable($publicDir, $publicUrl)) {
      $msg = 'Directory <a href="%1">%2</a> should not be browseable via the web.'
        . '<br />' .
        '<a href="%3">Read more about this warning</a>';
      $docs_url = createDocUrl('checkDirectoriesAreNotBrowseable');
      $messages[] = ts($msg, array(1 => $publicDir, 2 => $publicDir, 3 => $docs_url));
      
    }
  }
  return $messages;
}

function checkAll() {
  $messages = array_merge(checkLogFileIsNotAccessible(),
              checkUploadsAreNotAccessible(),
              checkDirectoriesAreNotBrowseable()
  );
  
  return $messages;
}

function securitycheck_civicrm_pageRun( &$page ){
  if($page->getvar('_name') == "CRM_Admin_Page_Admin") {
    if (CRM_Core_Permission::check('administer CiviCRM')) {
      $session = CRM_Core_Session::singleton();
      // Best attempt at re-securing folders
      $config = CRM_Core_Config::singleton();
      $config->cleanup(0, FALSE);
      foreach (checkAll() as $message) {
        CRM_Core_Session::setStatus($message, ts('Security Warning'));
      }
    }
  }
}