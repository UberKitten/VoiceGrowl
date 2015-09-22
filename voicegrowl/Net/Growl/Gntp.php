<?php
/**
 * Copyright (c) 2009, Laurent Laville <pear@laurent-laville.org>
 *                     Bertrand Mansion <bmansion@mamasam.com>
 *
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the authors nor the names of its contributors
 *       may be used to endorse or promote products derived from this software
 *       without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS
 * BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * PHP version 5
 *
 * @category Networking
 * @package  Net_Growl
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @author   Bertrand Mansion <bmansion@mamasam.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD
 * @version  CVS: $Id:$
 * @link     http://growl.laurent-laville.org/
 * @since    File available since Release 0.9.0
 */

/**
 * Growl implements GNTP 1.0 protocol
 *
 * @category Networking
 * @package  Net_Growl
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @author   Bertrand Mansion <bmansion@mamasam.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD
 * @version  Release: 2.0.0b2
 * @link     http://growl.laurent-laville.org/
 * @link     http://www.growlforwindows.com/gfw/ Growl for Windows Homepage
 * @since    Class available since Release 0.9.0
 */
class Net_Growl_Gntp extends Net_Growl
{
    /**
     * Class constructor
     *
     * @param mixed  &$application  Can be either a Net_Growl_Application object
     *                              or the application name string
     * @param array  $notifications Array of notifications
     * @param string $password      (optional) password for Growl
     * @param array  $options       (optional) Array of options : 'host', 'port',
     *                              'protocol' for Growl socket server
     */
    public function __construct(&$application, $notifications = array(),
        $password = '', $options = array()
    ) {
        parent::__construct($application, $notifications, $password, $options);
    }

    /**
     * Sends the REGISTER message type
     *
     * @return true
     * @throws Growl_Exception if remote server communication failure
     */
    public function sendRegister()
    {
        $binaries      = array();
        $growl_logo    = $this->getDefaultGrowlIcon();
        $growl_logo_id = md5($growl_logo);
        $password      = $this->getApplication()->getGrowlPassword();

        if (empty($password)) {
            $data  = "GNTP/1.0 REGISTER NONE\r\n";
        } else {
            $password = utf8_encode($password);
            list($hash, $salt) = $this->genKey($password);

            $crypt = $this->genEncryption($salt);
            $data = "GNTP/1.0 REGISTER $crypt $hash\r\n";
        }

        // Application-Name: <string>
        // Required - The name of the application that is registering
        $data .= "Application-Name: "
              .  utf8_encode($this->getApplication()->getGrowlName())
              .  "\r\n";

        // Application-Icon: <url> | <uniqueid>
        // Optional - The icon of the application
        $icon  = $this->getApplication()->getGrowlIcon();
        if (!empty($icon)) {
            $fp = @fopen($icon, 'rb');
            if ($fp === false) {
                $this->debug("Invalid Application Icon URL '$icon'", 'warning');
                // invalid Application Icon URL; force to use default growl logo
                $icon = '';
            } else {
                fclose($fp);
            }
        }
        if (empty($icon)) {
            $icon = "x-growl-resource://" . $growl_logo_id;
            $binaries[] = $growl_logo_id;
        }
        $data .= "Application-Icon: " . $icon . "\r\n";

        // Notifications-Count: <int>
        // Required - The number of notifications being registered
        $notifications = $this->getApplication()->getGrowlNotifications();
        $data .= "Notifications-Count: " . count($notifications) . "\r\n";

        foreach ($notifications as $name => $options) {
            $data .= "\r\n";

            // Notification-Name: <string>
            // Required - The name (type) of the notification being registered
            $data .= "Notification-Name: " . utf8_encode($name) . "\r\n";

            // Notification-Display-Name: <string>
            // Optional - The name of the notification that is displayed to the user
            // (defaults to the same value as Notification-Name)
            if (is_array($options) && isset($options['display'])) {
                $data .= "Notification-Display-Name: "
                      .  $options['display']
                      .  "\r\n";
            }

            // Notification-Enabled: <boolean>
            // Optional - Indicates if the notification should be enabled by default
            // (defaults to False)
            if (is_array($options) && isset($options['enabled'])) {
                $data .= "Notification-Enabled: "
                      .  $this->_toBool($options['enabled'])
                      .  "\r\n";
            }

            // Notification-Icon: <url> | <uniqueid>
            // Optional - The default icon to use for notifications of this type
            if (is_array($options) && isset($options['icon'])) {
                $icon = $options['icon'];
                $fp = @fopen($icon, 'rb');
                if ($fp === false) {
                    $this->debug("Invalid Notification Icon URL '$icon'", 'warning');
                    // invalid Notification Icon URL; force to use default growl logo
                    $icon = '';
                } else {
                    fclose($fp);
                }
                if (empty($icon)) {
                    $icon = "x-growl-resource://" . $growl_logo_id;
                    $binaries[] = $growl_logo_id;
                }
                $data .= "Notification-Icon: " . $icon . "\r\n";
            }

            // Notification-Sticky: <boolean>
            if (is_array($options) && isset($options['sticky'])) {
                $sticky = $options['sticky'];
            } else {
                $sticky = true;
            }
            $data .= "Notification-Sticky: " . $this->_toBool($sticky) . "\r\n";

            // Notification-Priority: <int>
            if (is_array($options) && isset($options['priority'])) {
                $priority = $options['priority'];
            } else {
                $priority = self::PRIORITY_NORMAL;
            }
            $data .= "Notification-Priority: " . $priority . "\r\n";
        }

        // binary section
        foreach ($binaries as $bin) {
            $data .= "\r\n";
            $data .= "Identifier: " . $bin . "\r\n";
            $data .= "Length: " . strlen($growl_logo) . "\r\n";
            $data .= "\r\n";
            $data .= $growl_logo;
        }

        // message termination
        // A GNTP request must end with <CRLF><CRLF> (two blank lines)
        $data .= "\r\n";
        $data .= "\r\n";

        return $this->sendRequest('REGISTER', $data);
    }

    /**
     * Sends the NOTIFY message type
     *
     * @param string $name        Notification name
     * @param string $title       Notification title
     * @param string $description Notification description
     * @param string $options     Notification options
     *
     * @return true
     * @throws Growl_Exception if remote server communication failure
     */
    public function sendNotify($name, $title, $description, $options)
    {
        $appName     = utf8_encode($this->getApplication()->getGrowlName());
        $password    = $this->getApplication()->getGrowlPassword();
        $name        = utf8_encode($name);
        $title       = utf8_encode($title);
        $description = utf8_encode($description);
        $priority    = isset($options['priority'])
            ? $options['priority'] : self::PRIORITY_NORMAL;
        $icon        = isset($options['icon']) ? $options['icon'] : '';

        if (!empty($icon)) {
            // check if valid icon URL
            $fp = @fopen($icon, 'rb');
            if ($fp === false) {
                $this->debug("Invalid Notification Icon URL '$icon'", 'warning');
                $icon = '';
            } else {
                fclose($fp);
            }
        }
        $password = $this->getApplication()->getGrowlPassword();

        if (empty($password)) {
            $data  = "GNTP/1.0 NOTIFY NONE\r\n";
        } else {
            $password = utf8_encode($password);
            list($hash, $salt) = $this->genKey($password);

            $crypt = $this->genEncryption($salt);
            $data = "GNTP/1.0 NOTIFY $crypt $hash\r\n";
        }

        // Application-Name: <string>
        // Required - The name of the application that sending the notification
        // (must match a previously registered application)
        $data .= "Application-Name: " . $appName . "\r\n";

        // Notification-Name: <string>
        // Required - The name (type) of the notification (must match a previously
        // registered notification name registered by the application specified
        // in Application-Name)
        $data .= "Notification-Name: " . $name . "\r\n";

        if (is_array($options) && isset($options['display'])) {
            $data .= "Notification-Display-Name: " . $options['display'] . "\r\n";
        }

        // Notification-Title: <string>
        // Required - The notification's title
        $data .= "Notification-Title: " . $title . "\r\n";

        // Notification-Text: <string>
        // Optional - The notification's text. (defaults to "")
        $data .= "Notification-Text: " . $description . "\r\n";

        // Notification-Priority: <int>
        // Optional - A higher number indicates a higher priority.
        // This is a display hint for the receiver which may be ignored.
        $data .= "Notification-Priority: " . $priority . "\r\n";

        if (!empty($icon)) {
            // Notification-Icon: <url>
            // Optional - The icon to display with the notification.
            $data .= "Notification-Icon: " . $icon . "\r\n";
        }

        // Notification-Sticky: <boolean>
        // Optional - Indicates if the notification should remain displayed
        // until dismissed by the user. (default to False)
        if (is_array($options) && isset($options['sticky'])) {
            $sticky = $options['sticky'];
            $data .= "Notification-Sticky: " .  $this->_toBool($sticky) .  "\r\n";
        }

        // Notification-ID: <string>
        // Optional - A unique ID for the notification. If present, serves as a hint
        // to the notification system that this notification should replace any
        // existing on-screen notification with the same ID. This can be used
        // to update an existing notification.
        // The notification system may ignore this hint.
        if (is_array($options) && isset($options['ID'])) {
            $data .= "Notification-ID: " .  $options['ID'] .  "\r\n";
        }


        // Notification-Callback-Context: <string>
        // Optional - Any data (will be passed back in the callback unmodified)

        // Notification-Callback-Context-Type: <string>
        // Optional, but Required if 'Notification-Callback-Context' is passed.
        // The type of data being passed in Notification-Callback-Context
        // (will be passed back in the callback unmodified). This does not need
        // to be of any pre-defined type, it is only a convenience
        // to the sending application.
        if (is_array($options)
            && (isset($options['CallbackContext'])
            || isset($options['CallbackTarget']))
        ) {
            $data .= "Notification-Callback-Context: "
                  .  $options['CallbackContext']
                  .  "\r\n";
            $data .= "Notification-Callback-Context-Type: "
                  .  $options['CallbackContextType']
                  .  "\r\n";
        }

        // Notification-Callback-Target: <string>
        // Optional - An alternate target for callbacks from this notification.
        // If passed, the standard behavior of performing the callback over the
        // original socket will be ignored and the callback data will be passed
        // to this target instead.
        if (is_array($options)
            && isset($options['CallbackTarget'])
        ) {
            $query = '';
            if (is_array($options) && isset($options['ID'])) {
                $query .= '&NotificationID='
                       .  urlencode($options['ID']);
            }
            if (is_array($options) && isset($options['ID'])) {
                $query .= '&NotificationContext='
                       .  urlencode($options['CallbackContext']);
            }

            $callbackTarget = $options['CallbackTarget'] ;

            if (strpos($options['CallbackTarget'], '?') === false) {
                $callbackTarget .= '?' . substr($query, 1);
            } else {
                $callbackTarget .= $query;
            }

            // BOTH methods are provided here for GfW compatibility.
            $data .= "Notification-Callback-Context-Target: "
                  .  $callbackTarget
                  .  "\r\n";
            // header kept for compatibility - @todo remove on final version
            $data .= "Notification-Callback-Context-Target-Method: GET \r\n";

            // Only those ones should be keep on final version
            $data .= "Notification-Callback-Target: "
                  .  $callbackTarget
                  .  "\r\n";
            // header kept for compatibility - @todo remove on final version
            $data .= "Notification-Callback-Target-Method: GET \r\n";
        }

        // message termination
        // A GNTP request must end with <CRLF><CRLF> (two blank lines)
        $data .= "\r\n";
        $data .= "\r\n";

        $res = $this->sendRequest('NOTIFY', $data);
        if ($res
            && is_array($options) && isset($options['CallbackFunction'])
            && is_callable($options['CallbackFunction'])
        ) {
            // handle Socket Callbacks
            call_user_func_array(
                $options['CallbackFunction'],
                $this->growlNotificationCallback
            );
        }
        return $res;
    }

    /**
     * Translates boolean value to comprehensible text for GNTP messages
     *
     * @param mixed $value Compatible Boolean String or value to translate
     *
     * @return string
     */
    private function _toBool($value)
    {
        if (preg_match('/^([Tt]rue|[Yy]es)$/', $value)) {
            return 'True';
        }
        if (preg_match('/^([Ff]alse|[Nn]o)$/', $value)) {
            return 'False';
        }
        if ((bool)$value === true) {
            return 'True';
        }
        return 'False';
    }
}
?>