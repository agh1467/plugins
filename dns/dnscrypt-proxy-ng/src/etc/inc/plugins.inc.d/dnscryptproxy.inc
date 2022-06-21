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

/**
 * This performs an evaluation to understand if the plugin is enabled or not.
 *
 * This is split into a separate function because it makes it easier to have
 * different ways of performing this evaluation. Not all plugin models will be
 * structured the same.
 *
 * @return boolean evaluation of the enabled state of the plugin
*/
function dnscryptproxy_enabled()
{
    // Create a model object which includes the settings from the config.
    $model = new \OPNsense\Dnscryptproxy\Settings();
    return $model->enabled->__toString() == "1";
}

/**
 * Function to add dnscrypt-proxy plugin to the services of OPNsense.
 *
 * This function must be prefixed with the same name as this file.
 *
 * This add the service to the System: Diagnostics: Services page at
 *   http://<opnsense>/status_services.php
 *
 * And the service widget that appears at the top of the page using
 *   updateServiceControlUI();
 *
 * References:
 * src/www/status_services.php
 * src/www/widgets/widgets/services_status.widget.php
 *
 * Both of these call plugins_services() which calls plugins_scan()
 * Which scans the plugins.inc.d path for .inc files to include (this one).
 *
 * plugin_services() will then assume that a function exists ending in "_services"
 * for each .inc file and put them together in an array. That array is then
 * returned to be used as the list of services on the system.
 *
 * The function is then called by name, to populate information
 * on the pages where displayed.
 *
 * @return array the services array
*/
function dnscryptproxy_services()
{

    $services = array();

    // If the plugin isn't enabled, just return the empty array.
    if (!dnscryptproxy_enabled()) {
        return $services;
    }

    // Create a Plugin object with values for use in the services array.
    $plugin = new \OPNsense\Dnscryptproxy\Plugin();

    $services[] = array(
        'description' => gettext($plugin->getSettings()['plugin_label']), // Description column
        'configd' => array(
            'restart' => array($plugin->getSettings()['configd_name'] . ' restart'),
            'start' => array($plugin->getSettings()['configd_name'] . ' start'),
            'stop' => array($plugin->getSettings()['configd_name'] . ' stop'),
        ),
        'name' => $plugin->getSettings()['plugin_name'], // Service column
        'pid' => '/var/run/dnscrypt-proxy.pid',
    );

    return $services;
}
