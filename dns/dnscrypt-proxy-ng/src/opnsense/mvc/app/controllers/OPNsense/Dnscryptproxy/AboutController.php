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

use OPNsense\Dnscryptproxy\Plugin;

/**
 * An IndexController-based class that creates an endpoint to display the
 * About page in the UI.
 *
 * @package OPNsense\Dnscryptproxy
 */
class AboutController extends PluginIndexController
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

        // Pull the name of this api from the Phalcon router to use in getFormXml call.
        $this_api_name = $this->router->getMatches()[1];            // "about"

        $this->view->setVars(
            [
                // Derive the API path from the UI path of the view, swapping out the leading "/ui/" for "/api/".
                // This is crude, but it will work until I discover a more reliable way to do it in the view.
                'plugin_api_path' => preg_replace("/^\/ui\//", "/api/", $this->router->getMatches()[0]),
                //'plugin_version' => $this->invokeConfigdRun('plugin_version'),  // "2.0.45.1"
                //'dnscrypt_proxy_version' => $this->invokeConfigdRun('version'), // "2.0.45"
                'this_xml' => $this->getFormXml($this_api_name)                 // controllers/OPNsense/Dnscryptproxy/forms/about.xml
            ]
        );

        // pick the template as the next view to render
        $this->view->pick('OPNsense/Dnscryptproxy/about');
        // views/OPNsense/Dnscryptproxy/diagnostics.volt
    }

}
