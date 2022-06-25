<?php

/*
    Copyright (C) 2022 agh1467@protonmail.com
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

namespace OPNsense\Mullvad;

use OPNsense\Mullvad\Plugin;

/**
 * An IndexController-based class that creates an endpoint to display the Settings
 * page in the UI.
 *
 * @package OPNsense\Mullvad
 */
 class SettingsController extends PluginIndexController
 {
     /**
      * This function creates an endpoint in the UI for the Settings Controller.
      *
      * UI endpoint:
      * `/ui/mullvad/settings`
      *
      * indexAction() analogous to index.html, it's the default if no action is provided.
      */
     public function indexAction()
     {
         // Pull the name of this api from the Phalcon router to use in getFormXml call.
         //$this_api_name = $this->view->getNamespaceName();            // "about"

         $plugin = new Plugin;

         $this->view->setVars(
             [
                 // Derive the API path from the UI path of the view, swapping out the leading "/ui/" for "/api/".
                 // This is crude, but it will work until I discover a more reliable way to do it in the view.
                 'plugin_api_path' => preg_replace("/^\/ui\//", "/api/", $this->router->getMatches()[0]),
                 'this_xml' => $plugin->getFormXml('settings'),
                 // controllers/OPNsense/Mullvad/forms/settings.xml
             ]
         );
         // Since the directory structure of OPNsense's plugins isn't conducive to automatically loading the template,
         // pick the specific template we want to load. Relative to /usr/local/opnsense/mvc/app/views, no file extension
         $this->view->pick('OPNsense/Mullvad/settings');
         // views/OPNsense/Mullvad/settings.volt
     }
 }
