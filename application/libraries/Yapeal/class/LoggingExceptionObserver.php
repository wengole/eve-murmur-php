<?php
/**
 * Contains LoggingExceptionObserver class.
 *
 * PHP version 5
 *
 * LICENSE: This file is part of Yet Another Php Eve Api library also know
 * as Yapeal which will be used to refer to it in the rest of this license.
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
 */
/**
 * @internal Allow viewing of the source code in web browser.
 */
if (isset($_REQUEST['viewSource'])) {
  highlight_file(__FILE__);
  exit();
};
/**
 * @internal Only let this code be included or required not ran directly.
 */
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
  exit();
};
/**
 * Logs any exceptions its observing to a log file.
 *
 * @package Yapeal
 * @subpackage Observer
 * @uses IYapealObserver
 * @uses YapealErrorHandler::elog()
 */
class LoggingExceptionObserver implements IYapealObserver {
  /**
   * @var string Holds the name of the log file to use.
   */
  private $file;
  /**
   * Constructor
   *
   * @param string $filename Optional filename to log messages to.
   */
  public function __construct($filename = YAPEAL_ERROR_LOG) {
    if (!empty($filename) && is_string($filename)) {
      $this->file = $filename;
    };
  }// function __construct
  /**
   * Method the 'object' calls to let us know something has happened.
   *
   * @param object $e The 'object' we're observing.
   */
  public function YapealUpdate(IYapealSubject $e) {
    $mess = 'EXCEPTION:' . PHP_EOL;
    $mess .= '     Code: ' . $e->getCode() . PHP_EOL;
    $mess .= '  Message: ' . $e->getMessage() . PHP_EOL;
    $mess .= '     File: ' . $e->getFile() . '(' . $e->getLine() . ')' . PHP_EOL;
    $mess .= 'Backtrace:' . PHP_EOL;
    $mess .= $e->getTraceAsString() . PHP_EOL;
    $mess .= str_pad(' END TRACE ', 30, '-', STR_PAD_BOTH);
    YapealErrorHandler::elog($mess, $this->file);
  }// function YapealUpdate
}
?>
