<?php

/*
    Copyright (C) 2018 Michael Muenz <m.muenz@gmail.com>
    All rights reserved.

    Redistribution and use in source and binary forms, with or without
    modification, are permitted provided that the following conditions are met:

    1. Redistributions of source code must retain the above copyright notice,
       this list of conditions and the following disclaimer.

    2. Redistributions in binary form must reproduce the above copyright
       notice, this list of conditions and the following disclaimer in the
       documentation and/or other materials provided with the distribution.

    THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
    INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
    AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
    AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
    OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
    SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
    INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
    CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
    ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
    POSSIBILITY OF SUCH DAMAGE.
*/

namespace OPNsense\Dnscryptproxy;

use OPNsense\Core\Backend;

/**
 * An IndexController-based class that creates an endpoint to display the
 * About page in the UI.
 *
 * @package OPNsense\Dnscryptproxy
 */
class AboutController extends \OPNsense\Base\IndexController
{
    /**
     * This function creates an endpoint in the UI for the About Controller.
     *
     * UI endpoint:
     * `/ui/dnscryptproxy/about`
     *
     * This is the default action when no parameters are provided.
     */
    public function indexAction()
    {
        // Create a model object to get some variables.
        $thisModel = new Settings();

        // Create our own instance of a Controller to use getForm().
        $myController = new ControllerBase();

        $this->view->setVars(
            [
                'plugin_name' => $thisModel->api_name,
                'plugin_version' => $this->invokeConfigdRun('plugin_version'),
                'dnscrypt_proxy_version' => $this->invokeConfigdRun('version'),
                'this_form' => $myController->getForm('about'),
                // controllers/OPNsense/Dnscryptproxy/forms/about.xml
            ]
        );

        // pick the template as the next view to render
        $this->view->pick('OPNsense/Dnscryptproxy/about');
        // views/OPNsense/Dnscryptproxy/diagnostics.volt
    }

    /**
     * This function will call configd, using a specific action.
     *
     * @param string  $action   The action to call in configd
     * @return string version of this plugin
     */
    private function invokeConfigdRun($action)
    {
        if (
            in_array($action, array(
                'version',
                'plugin_version',
            ))
        ) { // Check that we only operate on valid actions.
            $settings = new Settings();
            $result = trim((new Backend())->configdRun($settings->configd_name . ' ' . $action));

            return $result !== null ? $result : (object) [];
        }
    }
}
