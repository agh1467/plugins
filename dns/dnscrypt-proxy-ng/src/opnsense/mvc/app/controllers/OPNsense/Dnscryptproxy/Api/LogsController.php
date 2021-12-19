<?php

/**
 *    Copyright (C) 2018 Michael Muenz <m.muenz@gmail.com>
 *
 *    All rights reserved.
 *
 *    Redistribution and use in source and binary forms, with or without
 *    modification, are permitted provided that the following conditions are met:
 *
 *    1. Redistributions of source code must retain the above copyright notice,
 *       this list of conditions and the following disclaimer.
 *
 *    2. Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *
 *    THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 *    INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 *    AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 *    AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 *    OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 *    SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 *    INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 *    CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 *    ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 *    POSSIBILITY OF SUCH DAMAGE.
 *
 */

namespace OPNsense\Dnscryptproxy\Api;

use OPNsense\Base\ApiControllerBase;
use OPNsense\Core\Backend;
use OPNsense\Dnscryptproxy\Settings;

/**
 * An ApiControllerBase class used to perform various diagnostics for
 * dnscrypt-proxy.
 *
 * This class includes the following API actions:
 *
 * `logs`
 *
 * This API is accessible at the following URL endpoint:
 *
 * `/api/dnscryptproxy/logs`
 *
 * @package OPNsense\Dnscryptproxy
 */
class LogsController extends ApiControllerBase
{
    /**
     * Function indexAction() is an API endpoint to call when no parameters are
     * provided for the API. Can be used to test the is working.

     * API endpoint:
     *   /api/dnscryptproxy/logs
     *
     * Usage:
     *   /api/dnscryptproxy/logs
     *
     * Returns an array which gets converted to json.
     *
     * @return array    includes status, saying everything is A-OK
     */
    public function indexAction()
    {
        return array('status' => 'ok');
    }

    /**
     * An API endpoint to execute pre-defined diagnostic commands.
     *
     * API endpoint:
     *   /api/dnscryptproxy/diagnostics/command
     *
     * Usage:
     *   /api/dnscryptproxy/diagnostics/command/show-certs
     *
     * Commands are accessible via the API call by including the desired command
     * after the API endpoint in the URL. The example above calls
     * commandAction() with the $target being "show-certs".
     *
     * The commands available are:
     *   resolve
     *   show-certs
     *   config-check
     *
     * @param  $target  string   command to execute, pre-defined in the function
     * @return          array    status, response (command output), maybe message
     */
    public function mainAction()
    {

        $mySettingsController = new SettingsController();
        $settings = new Settings();
        $result = $mySettingsController->bootgridConfigd($settings->configd_name . ' log main', array('timestamp', 'severity', 'msg'));
        return $result;

    }

}
