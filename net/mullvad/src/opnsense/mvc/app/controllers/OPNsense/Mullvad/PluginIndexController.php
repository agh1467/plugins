<?php

/*
* OPNsense® is Copyright © 2022 by Deciso B.V.
* Copyright (C) 2022 agh1467@protonmail.com
* All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 * AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 * OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace OPNsense\Mullvad;
use OPNsense\Core\Backend;
use OPNsense\Mullvad\Plugin;


/**
 * Class PluginIndexController extends Core's OPNsense\Base\IndexController.
 *
 * All controllers for this plugin, should extend from this one.
 *
 *
 *
 *
 *
 * @package OPNsense\Mullvad
 */
class PluginIndexController extends \OPNsense\Base\IndexController
{

    /**
     * This is a special function which is executed after
     * @param $formname
     * @return array
     * @throws \Exception
     */
    public function afterExecuteRoute($dispatcher)
    {
        // Create plugin object to get some settings for in the view.
        $plugin = new Plugin;

        // Set in the view our plugin settings.
        $this->view->setVars($plugin->getSettings());

        // We derive the plugin_api_name from the namespace of this PHP class.
        // This assumes that the namespace will be something like: OPNsense\Dnscryptproxy
        $plugin_api_name = preg_replace('/^.*\\\/','',strtolower($this->router->getNamespaceName()));

        // Set the plugin_name in the view.
        $this->view->setVar('plugin_api_name', $plugin_api_name);
    }




}
