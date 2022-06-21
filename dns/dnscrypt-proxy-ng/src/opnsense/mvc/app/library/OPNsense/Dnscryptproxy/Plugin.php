<?php

/*
 * Copyright (C) 2015 Deciso B.V.
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

namespace OPNsense\Dnscryptproxy;

/**
 * Class PluginIndexController extends Core's OPNsense\Base\IndexController.
 *
 * All controllers for this plugin, should extend from this one.
 *
 *
 *
 *
 *
 * @package OPNsense\Dnscryptproxy
 */
class Plugin
{
    /**
     * Array for this plugin's settings.
     *
     * @var array $settings
     */
    protected $settings = array();

    /**
     * Array for this plugin's settings.
     *
     * @var array $settings
     */
    protected $plugin_ini = '/usr/local/opnsense/data/dnscryptproxy/plugin.ini';

    /**
     * Array for this plugin's settings.
     *
     * @var array $settings
     */
    public function __construct()
    {
        // Set values for the protected settings array.
        $this->settings = [
            // The common name of the plugin.
            'plugin_name' => 'dnscrypt-proxy',
            // A safe name to use in DOM id's.
            'plugin_safe_name' => 'dnscryptproxy',
            // A common (stylized) label for the plugin.
            'plugin_label' => 'DNSCrypt Proxy',
            // The service name that this plugin has.
            'plugin_service_name' => 'dnscryptproxy',
            // The name of the configd module for this plugin.
            //'plugin_configd_name' => 'dnscryptproxy',
            // The name of the log directory, assumed to be in /var/log
            'plugin_log_dir_name' => 'dnscrypt-proxy'
        ];
    }

    /**
     * Function to get the settings out of the class.
     *
     * @var array $settings
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Returns the name of the configd for this plugin.
     *
     * @var array $settings
     */
    public function getConfigdName()
    {
        $ini = parse_ini_file($this->plugin_ini, true);
        if (array_key_exists('plugin', $ini)) {
            if (array_key_exists('configd_name', $ini['plugin'])) {
                return $ini['plugin']['configd_name'];
            }
        }
    }

}
