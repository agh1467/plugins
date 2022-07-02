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

{%  set this_node_id = this_part['id']|default('none') %}
{%  set this_node_type = this_part.getName() %}
{# This evaluates if there are any fields in this part which require help or advanced #}
{%  set help = this_part.xpath('//*/field/help') ? true : false %}
{%  set advanced = this_part.xpath('//*/field/advanced') ? true : false %}
{# If there is a root form already defined in the parent, we can't build a form here since they can't be nested.
   Any model definitions here won't matter if one is defined in the parent. #}
{%      if root_form == false %}
{%          if this_model_name %}
{%              if this_model_endpoint %}
{# Set up our form DOM to house all of the settings for this form. #}
<form id="frm_{{ this_node_type }}_{{ this_node_id }}"
      class="form-inline"
      data-title="{{ this_part['descrption'] }}"
      data-model="{{ this_model_name }}"
      data-model-endpoint="{{ this_model_endpoint }}">
{%              endif %}
{%          endif %}
{%      endif %}
{# Start building the table for the fields. #}
<div class="table-responsive">
  <table class="table table-striped table-condensed">
    <colgroup>
      <col class="col-md-3"/>
      <col class="col-md-4"/>
      <col class="col-md-5"/>
    </colgroup>
    <tbody>
{# Draw the help row if we have to draw the help or advanced switch. #}
{%  if advanced or help %}
{% include "OPNsense/Mullvad/layout_partials/rows/help_or_advanced.volt" %}
{%  endif %}
{# Draw the field table with fields for this node. #}
{{              partial("OPNsense/Mullvad/layout_partials/base_table",[
                    'this_part':this_part,
                    'this_model_name':this_model_name,
                    'this_model_endpoint':this_model_endpoint
                ]) }}

{# Draw any buttons as defined. #}
{%  if this_part.button %}
            <tr>
                <td colspan="3">
{#              # We set our own style here to put the button in the right place. #}
                    <div style="padding-left: 10px;">
{%      for button in this_part.button %}
{%          if button['type']|default('primary') in ['primary', 'group' ] %} {# Assume primary if not defined #}
{%              if button['type'] == 'primary' and button['action'] != '' %}
                    <button class="btn btn-primary"
                            id="btn_frm_{{ this_node_id }}_{{ button['action'] }}"
                            type="button">
                        <i class="{{ button['icon']|default('') }}"></i>
                        &nbsp<b>{{ lang._('%s') | format(button['id']) }}</b>
                        <i id="btn_frm_{{ this_node_id }}_progress"></i>
                    </button>
{%              elseif button['type'] == 'group' %}
{#              # We set our own style here to put the button in the right place. #}
                <div class="btn-group"
                    {{ (button['id']|default('') != '') ?
                        'id="'~button['id']~'"' : '' }}>
                    <button type="button"
                            class="btn btn-default dropdown-toggle"
                            data-toggle="dropdown">
                        <i class="{{ button['icon'] }}"></i>
                        &nbsp<b>{{ lang._('%s') | format(button['label']) }}</b>
                        <i id="btn_frm_{{ this_node_id }}_progress"></i>
                        &nbsp<i class="caret"></i>
                    </button>
{%                  if button.dropdown %}
                        <ul class="dropdown-menu" role="menu">
{%                      for dropdown in button.dropdown %}
                            <li>
                                <a id="drp_frm_{{ this_node_id }}_{{ dropdown['action'] }}">
                                    <i class="{{ button['icon'] }}"></i>
                                    &nbsp{{ lang._('%s') | format(dropdown[0]) }}
                                </a>
                            </li>
{%                      endfor %}
                        </ul>
{%                  endif %}
                    </div>
{%              endif %}
{%          endif %}
{%      endfor %}
                        </div>
                    </td>
                </tr>
{%  endif %}
            </tbody>
        </thead>
    </table>
  </div>
{%      if root_form == false %}
{# Close our our form here if we need to. #}
</form>
{%      endif %}
