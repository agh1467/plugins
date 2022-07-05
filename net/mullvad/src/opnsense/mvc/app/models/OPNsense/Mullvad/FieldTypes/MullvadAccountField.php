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

namespace OPNsense\Mullvad\FieldTypes;

use OPNsense\Base\FieldTypes\TextField;
use OPNsense\Phalcon\Filter\Validation\Validator\Regex;

class MullvadAccountField extends TextField
{

    /**
     * @var bool marks if this is a data node or a container
     */
    protected $internalIsContainer = false;

    /**
     * @var null|string validation mask (regex)
     */
    protected $internalMask = '/^\d{4} ?\d{4} ?\d{4} ?\d{4}$/';

    /**
     * @var string default validation message string
     */
    protected $internalValidationMessage = "enter a Mullvad account number in the format: xxxx xxxx xxxx xxxx";

    /**
     * This is
     * translate ModelRelationFields to their proper value.
     *
     * This is used instead of the standard BaseField::getNodes() approach.
     * @param $parent_node BaseField node to reverse
     */
    public function getNodeData($output = 'pretty')
    {
        // Return our data, but with our pretty filter.
        if ($output == 'pretty') {
            return $this->applyFilterPretty();
        } else {
            return (string)$this;
        }
    }

    /**
     * default setter
     * @param string $value set field value
     */
    public function setValue($value)
    {
        // Store the value.
        $this->internalValue = $value;

        // Apply the filter.
        $this->applyFilterClean();
    }

    /**
     * Function which applies a filter to the $internalValue.
     *
     * The filter is designed to remove spaces from the account number,
     * as is typical for Mullvad account number to include them as a
     * a visual delimiter.
     *
     * This is called by setValue()
     *
     */
    private function applyFilterClean()
    {
        // Remove spaces to store just the account number itself in the config.
        $this->internalValue = str_replace(' ', '', $this->internalValue);
    }

    /**
     * Function which applies a filter to the $internalValue.
     *
     * The filter is designed to remove spaces from the account number,
     * as is typical for Mullvad account number to include them as a
     * a visual delimiter.
     *
     * This is called by setValue()
     *
     */
    private function applyFilterPretty()
    {
        // Remove spaces to store just the account number itself in the config.
        return trim(chunk_split((string)$this, 4, ' '));
    }
}
