{##
 # OPNsense® is Copyright © 2022 by Deciso B.V.
 # Copyright (C) 2022 agh1467@protonmail.com
 # All rights reserved.
 #
 # Redistribution and use in source and binary forms, with or without modification,
 # are permitted provided that the following conditions are met:
 #
 # 1. Redistributions of source code must retain the above copyright notice,
 #    this list of conditions and the following disclaimer.
 #
 # 2. Redistributions in binary form must reproduce the above copyright notice,
 #    this list of conditions and the following disclaimer in the documentation
 #    and/or other materials provided with the distribution.
 #
 # THIS SOFTWARE IS PROVIDED "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 # INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 # AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 # AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 # OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 # SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 # INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 # CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 # ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 # POSSIBILITY OF SUCH DAMAGE.
 #}

{##
 # This partial is for building a form, including all fields. It's called
 # by other volt scipts and to build tabs, and boxes. The array 'this_part'
 # should be the tab, or box (or possibly other structure) being drawn.
 #
 # This is called by the following functions:
 # _macros::build_tabs()
 # _macros::
 #
 # The array named "this_part" should contain:
 #
 # this_part['id']          : 'id' attribute on 'tab' element in form XML,
 #                            intended to be unique on the page
 # this_part['description'] : 'description' attribute on 'tab' element in form XML
 #                            used as 'data-title' to set on form HTML element
 # this_part.field          : array of fields on this tab
 #}

{# Here we iterate through the children in order rather than use the this_part.field
   in order to keep the order when a model definition may be present. #}
{%  for child_node in this_part.children() %}
{%      if child_node.getName() == 'field' %}
{%          if child_node.type == 'header' %}
{#              close table and start new one with header #}
        </tbody>
    </table>
</div>
<div class="table-responsive {{ child_node.style|default('') }}">
    <table class="table table-striped table-condensed table-responsive">
        <colgroup>
            <col class="col-md-3"/>
            <col class="col-md-4"/>
            <col class="col-md-5"/>
        </colgroup>
        <thead>
            <tr {% if child_node.advanced|default(false)=='true' %} data-advanced="true"{% endif %}>
                <th colspan="3">
                    <h2>
{%              if child_node.help %}
{%                  if child_node.id is not defined and child_node.label is defined %} {# Use the header or label, whichever is defined #}
{# Swap out all non-valid characters for an underscorw, hopefully the result will be unique. #}
<?php $safe_label = preg_replace('/[^a-zA-Z0-9_-]/','_',$field->label); ?>
{%                      set header_id = safe_label %}
{%                  elseif child_node.id is defined %}
{%                      set header_id = child_node.id %}
{%                  endif %}
                        <a id="help_for_{{ header_id|default('') }}" href="#" class="showhelp">
                            <i class="fa fa-info-circle"></i>
                        </a>
{%              elseif child_node.help|default(false) == false %}
                        <i class="fa fa-info-circle text-muted"></i>
{%              endif %}
                        {{ child_node.label }}
                </h2>
{%              if child_node.help %}
                        <div class="hidden" data-for="help_for_{{ header_id|default('') }}">
                            <small>{{child_node.help}}</small>
                        </div>
{%              endif %}
                </th>
            </tr>
        </thead>
        <tbody>
{%          elseif child_node.type == 'separator' %}
{# close the table that was started earlier, start a new table, and put an empty row #}
        </tbody>
    </table>
</div>
<!-- Table Separator -->
<div class="table-responsive">
    <table class="table table-striped table-condensed">
        <colgroup>  {# We need to define again the column groups #}
            <col class="col-md-3"/>
            <col class="col-md-4"/>
            <col class="col-md-5"/>
        </colgroup>
            <tr>
                <th colspan="3"> {# This header should span all three columns #}
                    <br> {# This is just an empty header to create a visual space #}
                </th>
            </tr>
        <tbody>
{%          elseif child_node.type == 'bootgrid' %}
{# We hijack the type field for the bootgrid so we can inject it
   as a whole row instead of with form_intput_tr
   Technically doesn't have to be a separate partial, but just
   keeping it separate for now since it's so large.
   Load in our bootgrid partial #}
{{              partial("OPNsense/Mullvad/layout_partials/form_bootgrid_tr",[
                    'this_field':child_node
                ]) }}
{%          elseif child_node.type == 'button' %}
{#  We hijack the type field again for injecting a button
    this button intends to be attached to by SimpleActionButton() #}
<tr>
    <td colspan="3">
        <button
            class="btn btn-primary" id="{{ child_node.id|default('') }}"
            data-label="{{ lang._('%s') | format(child_node.label) }}"
{# /usr/local/opnsense/www/js/opnsense_ui.js:SimpleActionButton() #}
{# These fields are expected by the SimpleActionButton() to label, and attach click event. #}
            data-endpoint="{{ child_node.api|default('') }}"
            data-error-title="{{ lang._('%s') | format(child_node.error|default('')) }}"
            data-service-widget="{{child_node.widget|default('')}}"
            type="button"
        ></button>
    </td>
</tr>
{# {%                      endif %} #}
{# {%                  endif %} #}
{%          elseif child_node.type == 'commandoutput' %}
{# We're putting this here because we need the command output to be wider than any single column. #}
{%              if child_node.id|default('') != '' %}
<tr>
    <td colspan="3">
        <pre
            id="pre_{{ child_node.id|default('') }}_output"
            style="white-space: pre-wrap;"
        >{{ child_node.text|default('') }}</pre>
    </td>
</tr>
{%              endif %}
{%          elseif child_node.type == 'span_content' %}
{# XXX Maybe use info field type instead? #}
<tr>
    <td colspan="3">
        {{ child_node.content|default('') }}
    </td>
</tr>
{%          else %}
{# Draw all of the regular field types which can be drawn in the 3 column style. #}
{{              partial("OPNsense/Mullvad/layout_partials/form_input_tr",[
                    'this_field':child_node,
                    'this_model':this_model_name,
                    'this_model_endpoint':child_node['endpoint']
                ]) }}
{%          endif %}
{%      elseif child_node.getName() == 'model' %}
{# if we encountere a model, let's start a new table to specify the model and API endpoint
   we'll then close this table, and start a new one afterwards. #}
        </tbody>
    </table>
</div>
<div id="mdl_{{ child_node['id'] }}"
     class="table-responsive"
     data-model-name="{{ child_node['name'] }}"
     data-model-endpoint="{{ child_node['endpoint'] }}">
    <table class="table table-striped table-condensed">
        <colgroup>
            <col class="col-md-3"/>
            <col class="col-md-4"/>
            <col class="col-md-5"/>
        </colgroup>
{{              partial("OPNsense/Mullvad/layout_partials/base_table",[
                    'this_part':child_node,
                    'this_model_name':child_node['name'],
                    'this_model_endpoint':child_node['endpoint']
                ]) }}
    </table>
</div>
<div class="table-responsive">
    <table class="table table-striped table-condensed">
        <colgroup>
            <col class="col-md-3"/>
            <col class="col-md-4"/>
            <col class="col-md-5"/>
        </colgroup>
        <thead>
            <tr {% if child_node.advanced|default(false)=='true' %} data-advanced="true"{% endif %}>
                <th colspan="3">
                    <h2>
{%      endif %}
{%  endfor %}
