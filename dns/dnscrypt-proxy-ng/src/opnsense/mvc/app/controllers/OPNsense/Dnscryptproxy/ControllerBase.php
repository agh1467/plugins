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

use OPNsense\Base\ControllerRoot;

/**
 * Class ControllerBase implements core controller for OPNsense framework
 *
 * This is a special version of ControllerBase which parses the model XML
 * differently than the standard ControllerBase.
 *
 * It provides primarily a single function from the original, and is only used
 * in the SettingsController class to parse the form XML into an array.
 *
 * @package OPNsense\Dnscryptproxy
 */
class ControllerBase extends ControllerRoot
{
    /**
     * Special version of parseFromNode() which recurses deeper (infinite),
     * and supports arrays at all levels, and attributes at levels deeper than
     * than original.
     *
     * It allows for a lot more flexibility in the design of the form XMLs.
     *
     * @param $xmlNode
     * @return array
     */
    private function parseFormNode($xmlNode)
    {
        $result = array();
        foreach ($xmlNode as $key => $node) {
            $element = array();
            $nodes = $node->children();
            $nodes_count = $nodes->count();
            $attributes = $node->attributes();

            switch ($key) {
                case 'tab':
                    if (! array_key_exists('tabs', $result)) {
                        $result['tabs'] = array();
                    }
                    $tab = array();
                    $tab[] = $node->attributes()->id;
                    $tab[] = gettext((string) $node->attributes()->description);
                    if (isset($node->subtab)) {
                        $tab['subtabs'] = $this->parseFormNode($node);
                    } else {
                        $tab[] = $this->parseFormNode($node);
                    }
                    $result['tabs'][] = $tab;

                    break;
                case 'subtab':
                    $subtab = array();
                    $subtab[] = $node->attributes()->id;
                    $subtab[] = gettext((string) $node->attributes()->description);
                    $subtab[] = $this->parseFormNode($node);
                    $result[] = $subtab;

                    break;
                case 'box':
                    $box = array();
                    $box[] = $node->attributes()->id;
                    $box[] = gettext((string) $node->attributes()->description);
                    $box[] = $this->parseFormNode($node);
                    $result['boxes'][] = $box;

                    break;
                case 'help':
                case 'hint':
                case 'label':
                    $result[$key] = gettext((string) $node);

                    break;
                default:

                    // There's primarily two structures we need to build here:
                    // XML:
                    //   <model>settings</model>
                    // PHP Array:
                    //   ["model"]=> string(8) "settings"
                    //
                    // This is the simplest structure consisting of a single element with a value.
                    //
                    // A more complex structure consists of nested elements
                    // with attributes (including single elements with attributes):
                    // XML:
                    //  <columns>
                    //        <select>true</select>
                    //        <column id="expression" width="" size="" type="string" visible="true" data-formatter="">Expression</column>
                    //        <column id="schedule" width="" size="" type="string" visible="true" data-formatter="">Schedule</column>
                    //        <column id="comment" width="" size="" type="string" visible="true" data-formatter="">Comment</column>
                    //    </columns>
                    // PHP Array:
                    //    ["columns"]=> array(2) {
                    //        ["select"]=> string(4) "true"
                    //        ["column"]=> array(3) {
                    //            [0]=> array(2) {
                    //                ["@attributes"]=> array(6) {
                    //                    ["id"]=>             string(10) "expression"
                    //                    ["width"]=>          string(0) ""
                    //                    ["size"]=>           string(0) ""
                    //                    ["type"]=>           string(6) "string"
                    //                    ["visible"]=>        string(4) "true"
                    //                    ["data-formatter"]=> string(0) ""
                    //                }
                    //                [0]=> string(10) "Expression"
                    //            }
                    //            [1]-> ...
                    //        }
                    //    }
                    //
                    // This converts each 'column' element into an index in the array named 'column'.
                    //
                    // Here's another nested structure example with attirbutes at multiple levels:
                    // XML:
                    //  <button type="group" icon="fa fa-floppy-o" label="Save Basic Settings" id="save_actions">
                    //      <dropdown action="save" icon="fa fa-floppy-o">Save Only</dropdown>
                    //      <dropdown action="save_apply" icon="fa fa-floppy-o">Save and Apply</dropdown>
                    //  </button>
                    // PHP Array:
                    // ["button"]=> array(2) {
                    //     ["dropdown"]=> array(2) {
                    //         [0]=> array(2) {
                    //             ["@attributes"]=> array(2) {
                    //                 ["action"]=>             string(4) "save"
                    //                 ["icon"]=>               string(14) "fa fa-floppy-o"
                    //           }
                    //           [0]=>                          string(9) "Save Only"
                    //         }
                    //         [1]=> array(2) {
                    //             ["@attributes"]=> array(2) {
                    //                 ["action"]=>             string(10) "save_apply"
                    //                 ["icon"]=>               string(14) "fa fa-floppy-o"
                    //             }
                    //             [0]=>                        string(14) "Save and Apply"
                    //         }
                    //     }
                    //     ["@attributes"]=> array(4) {
                    //         ["type"]=>                       string(5) "group"
                    //         ["icon"]=>                       string(14) "fa fa-floppy-o"
                    //         ["label"]=>                      string(19) "Save Basic Settings"
                    //         ["id"]=>                         string(11) "save_actions"
                    //     }
                    // }


                    if (count($attributes) !== 0) { // If there are attributes, let's grab them.
                        foreach ($attributes as $attr_name => $attr_value) {
                            // Create an array with each key named after the attribute name, and store its value accordingly.
                            $my_attributes[$attr_name] = $attr_value->__tostring();
                        }
                        // Store the attributes to a named index in the element array.
                        $element['@attributes'] = $my_attributes;
                    }

                    // If there are no children, then we've reached the end of this branch.
                    if ($nodes_count === 0) {
                        if ($node->attributes()) {
                            // If there are other nodes that have the same key name,
                            // then we need put this node into an array of the same key name.
                            // It will be one of the indexes in the array.
                            if (count($node->xpath('../' . $key)) > 1) {
                                $element[] = $node->__toString();
                                $result[$key][] = $element;
                            } else {
                                // Since this is the only key with this name then we're not
                                // creating an array, we're just naming the key after it.
                                $element[] = $node->__toString();
                                $result[$key] = $element;
                            }
                        } else {
                            // If we have no attributes to attach, and we have multiple nodes then add to array.
                            if (count($node->xpath('../' . $key)) > 1) {
                                $result[$key][] = $node->__toString();
                            } else {
                                // No multiple nodes, and no attributes, just set the value.
                                $result[$key] = $node->__toString();
                            }
                        }

                        break;
                    }

                    // If we have 1 key, then lets set the value, but merge it with element if there are attributes.
                    if (count($node->xpath('../' . $key)) < 2) {
                        $result[$key] = array_merge($this->parseFormNode($node), $element);
                        break;
                    }
                    // Nothing else to do, so let's recurse, but also add attirbutes if there are any.
                    $result[$key][] = array_merge($this->parseFormNode($node), $element);
                }
        }

        return $result;
    }

    /**
     * parse an xml type form
     * @param $formname
     * @return array
     * @throws \Exception
     */
    public function getForm($formname)
    {
        $class_info = new \ReflectionClass($this);
        $filename = dirname($class_info->getFileName()) . '/forms/' . $formname . '.xml';
        if (! file_exists($filename)) {
            throw new \Exception('form xml ' . $filename . ' missing');
        }
        $formXml = simplexml_load_file($filename);
        if ($formXml === false) {
            throw new \Exception('form xml ' . $filename . ' not valid');
        }

        return $this->parseFormNode($formXml);
    }
}
