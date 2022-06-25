<?php

/*
 * Copyright (C) 2016 IT-assistans Sverige AB
 * Copyright (C) 2016 Deciso B.V.
 * Copyright (C) 2018 Fabian Franz
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

namespace OPNsense\Dnscryptproxy\Api;

use OPNsense\Base\ApiControllerBase;
//use OPNsense\Core\Backend;
//use OPNsense\Dnscryptproxy\Plugin;
//use OPNsense\Dnscryptproxy\PluginApiMutableModelControllerBase;

/**
 * Class AboutController, inherits ApiControllerBase
 *
 * This class offers an API interface which is designed to be able to return
 * a combination of data that does not exist in a model with data that is in a model.
 *
 * This is useful for data which is more "live" and not appropriate to store in the
 * configuration, but should instead come through an API interface on demand.
 *
 * @package OPNsense\Dnscryptproxy
 */
class TestController extends ApiControllerBase
{
    public function searchAction()
    {
        $result = array();

        $result['rows'][] = array("col1" => "value1", "col2" => array("label" => "success", "text" => "Success"), "col3" => "value3");
        // We're all done, so now return what we have in a way bootgrid expects.
        $result['rowCount'] = count($result['rows']);
        $result['total'] = 1;
        $result['current'] = 1;
        $result['status'] = 'ok';
        $result['POST'] = $_POST;

        return $result;
    }
}
