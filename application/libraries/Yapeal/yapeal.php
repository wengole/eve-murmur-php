#!/usr/bin/php -Cq
<?php
/**
 * Used to get information from Eve-online API and store in database.
 *
 * This script expects to be ran from a command line or from a crontab job that
 * can optionally pass a config file name with -c option.
 *
 * PHP version 5
 *
 * LICENSE: This file is part of Yet Another Php Eve Api library also know
 * as Yapeal.
 *
 *  Yapeal is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Yapeal is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Lesser General Public License for more details.
 *
 *  You should have received a copy of the GNU Lesser General Public License
 *  along with Yapeal. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author     Michael Cummings <mgcummings@yahoo.com>
 * @copyright  Copyright (c) 2008-2010, Michael Cummings
 * @license    http://www.gnu.org/copyleft/lesser.html GNU LGPL
 * @package    Yapeal
 * @link       http://code.google.com/p/yapeal/
 * @link       http://www.eve-online.com/
 * @since      revision 561
 */
/**
 * @internal Allow viewing of the source code in web browser.
 */
if (isset($_REQUEST['viewSource'])) {
  highlight_file(__FILE__);
  exit();
};
// Used to over come path issues caused by how script is ran on server.
$dir = str_replace('\\', '/', realpath(dirname(__FILE__)));
chdir($dir);
// Define short name for directory separator which always uses unix '/'.
if (!defined('DS')) {
  define('DS', '/');
};
// Pull in Yapeal revision constants.
require_once $dir . DS . 'revision.php';
// If function getopts available get any command line parameters.
if (function_exists('getopt')) {
  $options = getopt('hVc:');
  if (!empty($options)) {
    foreach ($options as $opt => $value) {
      switch ($opt) {
        case 'c':
          $iniFile = str_replace('\\', '/', realpath($value));
          break;
        case 'h':
          usage();
          exit;
        case 'V':
          $mess = $argv[0] . ' ' . YAPEAL_VERSION . ' (' . YAPEAL_STABILITY . ') ';
          $mess .= YAPEAL_DATE . PHP_EOL;
          $mess .= 'Copyright (C) 2008-2010, Michael Cummings' . PHP_EOL;
          $mess .= 'This program comes with ABSOLUTELY NO WARRANTY.' . PHP_EOL;
          $mess .= 'Licensed under the GNU LPGL 3.0 License.' . PHP_EOL;
          $mess .= 'See COPYING and COPYING-LESSER for more details.' . PHP_EOL;
          fwrite(STDOUT, $mess);
          exit;
        default:
          $mess = 'Unknown option ' . $opt . PHP_EOL;
          usage();
          exit(1);
      };// switch $opt
    };// foreach $options...
  };// if !empty $options ...
};// if function_exists getopt ...
// Move down to 'inc' directory to read common_backend.php
$path = str_replace('\\', '/', realpath($dir . DS . 'inc' . DS . 'common_backend.php'));
require_once $path;
try {
  /**
   * Give ourself a 'soft' limit of 10 minutes to finish.
   */
  define('YAPEAL_MAX_EXECUTE', strtotime('10 minutes'));
  /**
   * This is used to have the same time on all APIs that error out and need to
   * be tried again.
   */
  define('YAPEAL_START_TIME', gmdate('Y-m-d H:i:s', YAPEAL_MAX_EXECUTE));
  /* ************************************************************************
   * Generate section list
   * ************************************************************************/
  $sectionList = FilterFileFinder::getStrippedFiles(YAPEAL_CLASS, 'Section');
  if (count($sectionList) == 0) {
    $mess = 'No section classes were found check path setting';
    trigger_error($mess, E_USER_ERROR);
  };
  // Randomize order in which API sections are tried if there is a list.
  if (count($sectionList) > 1) {
    shuffle($sectionList);
  };
  // Now take the list of sections and call each in turn.
  foreach ($sectionList as $sec) {
    try {
      // Have to use the following to work around lcfirst() being PHP 5.3 or
      // above only.
      $section = new Sections(strtolower(substr($sec, 0, 1)) . substr($sec, 1), FALSE);
    }
    catch (Exception $e) {
      // Section does not exist in utilSections or other error occurred.
      continue;
    }
    if ($section->isActive == 0) {
      // Skip inactive sections.
      continue;
    }
    $apis = explode(' ', $section->activeAPI);
    // Skip if there's no active APIs for this section.
    if (count($apis) == 0) {
      continue;
    };
    $class = 'Section' . ucfirst($sec);
    try {
      $instance = new $class($apis);
      $instance->pullXML();
    }
    catch (ADODB_Exception $e) {
      // Do nothing use observers to log info
    }
    // Going to sleep for a second to let DB time to flush etc between sections.
    sleep(1);
  };// foreach $section ...
  /* ************************************************************************
   * Final admin stuff
   * ************************************************************************/
  // Release all the ADOdb connections.
  YapealDBConnection::releaseAll();
}
catch (Exception $e) {
  require_once YAPEAL_CLASS . 'YapealErrorHandler.php';
  $mess = 'Uncaught exception in ' . basename(__FILE__);
  YapealErrorHandler::elog($mess, YAPEAL_ERROR_LOG);
  $message = 'EXCEPTION:' . PHP_EOL;
  $message .= '     Code: ' . $e->getCode() . PHP_EOL;
  $message .= '  Message: ' . $e->getMessage() . PHP_EOL;
  $message .= '     File: ' . $e->getFile() . PHP_EOL;
  $message .= '     Line: ' . $e->getLine() . PHP_EOL;
  $message .= 'Backtrace:' . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
  $message .= '        --- END TRACE ---' . PHP_EOL;
  YapealErrorHandler::elog($message, YAPEAL_ERROR_LOG);
}
//trigger_error('Peak memory used:' . memory_get_peak_usage(TRUE), E_USER_NOTICE);
exit;
/**
 * Function use to show the usage message on command line.
 */
function usage() {
  $scriptname = basename($GLOBALS['argv'][0]);
  $mess = 'Usage: ' . $scriptname;
  $mess .= ' [-V | [-h] | [-c <config.ini>] [-d <logfile.log>]]' . PHP_EOL;
  $mess .= 'Options:' . PHP_EOL;
  $mess .= '  -c config.ini                        ';
  $mess .= "Read configation from 'config.ini'." . PHP_EOL;
  $mess .= '  -d logfile.log                       ';
  $mess .= "Save debugging log to 'logfile.log'." . PHP_EOL;
  $mess .= '  -h                                   ';
  $mess .= 'Show this help.' . PHP_EOL;
  $mess .= '  -V                                   ';
  $mess .= 'Show version and license of ' . $scriptname . PHP_EOL;
  $mess .= 'Version ' . YAPEAL_VERSION . ' (' . YAPEAL_STABILITY . ') ';
  $mess .= YAPEAL_DATE . PHP_EOL;
  fwrite(STDOUT, $mess);
};
?>
