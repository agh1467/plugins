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

use Exception;
use ReflectionClass;

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
    protected $plugin_ini = '/usr/local/opnsense/data/mullvad/plugin.ini';

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
            'plugin_name' => 'mullvad',
            // A safe name to use in DOM id's.
            'plugin_safe_name' => 'mullvad',
            // A common (stylized) label for the plugin.
            'plugin_label' => 'Mullvad VPN',
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

    /**
     * @param $formname
     * @return array
     * @throws \Exception
     */
    public function getFormXml($formname)
    {
        $class_info = new \ReflectionClass($this);
        // XXX Need to fix this so it's dynamic based on the caller (not $this)
        //$filename = dirname($class_info->getFileName()) . '/forms/' . $formname . '.xml';
        $filename = '/usr/local/opnsense/mvc/app/controllers/OPNsense/Mullvad/forms/' . $formname . '.xml';
        if (file_exists($filename)) {
            $formXml = simplexml_load_file($filename);
            if ($formXml === false) {
                //$formXml = '<pre>XML file ' . $filename . ' is invalid.</pre>';
                throw new \Exception('form xml ' . $filename . ' not valid');
            }
        } else {
            // Set an empty XML document to return since we don't have an XML to parse.
            $formXml = simplexml_load_string('<root></root>');
        }
        return $formXml;
    }

}
