<?php

require_once 'securitycheck.civix.php';
require_once 'Security.php';
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
 * 
 *Displays Security Error Messages On Civicrm Administrator page
 *
 */
function securitycheck_civicrm_pageRun( &$page ){
    if($page->getvar('_name') == "CRM_Admin_Page_Admin") {
        if (CRM_Core_Permission::check('administer CiviCRM')) {
            $check = CRM_Utils_Check_Security::singleton();
            $messages = $check->checkAll();
            $config = CRM_Core_Config::singleton();
            $config->cleanup(0, FALSE);
            foreach ($messages as $message) {
                CRM_Core_Session::setStatus($message, ts('Security Warning'));
            }
        }
    }
}