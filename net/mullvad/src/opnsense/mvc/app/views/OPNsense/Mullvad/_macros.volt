{#
 # OPNsense® is Copyright © 2022 by Deciso B.V.
 # Copyright (C) 2022 agh1467@protonmail.com
 # All rights reserved.
 #
 # Redistribution and use in source and binary forms, with or without modification,
 # are permitted provided that the following conditions are met:
 #
 # 1.  Redistributions of source code must retain the above copyright notice,
 #     this list of conditions and the following disclaimer.
 #
 # 2.  Redistributions in binary form must reproduce the above copyright notice,
 #     this list of conditions and the following disclaimer in the documentation
 #     and/or other materials provided with the distribution.
 #
 # THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
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
 # This function builds tabs using a different approach than the original.
 # See in Core: src/opnsense/mvc/app/views/layout_partials/base_tabs_header.volt
 #
 # Instead of walking the tree, and building each tab or subtab as it goes,
 # it uses XPath to locate all subtabs, and tabs without subtabs and then
 # builds them out afterwards. This has the potential to define them out of order
 # in the HTML depending on the sturcture of the XML, but won't affect functionality.
 #
 # I expect this method should be more efficient than the tree walk as it only selects
 # nodes that we care about, and it doesn't have to evaluate each node individually.
 #
 # @xml_data SimpleXMLObject the XML data to look through for tabs
 #}
{%  macro build_tab_contents(xml_data, active_tab = null, this_model = null, root_form = null) %}
{%      if xml_data %}
{%          set subtabs = xml_data.xpath('/*/tab/subtab') %}
{%          set tabs = xml_data.xpath('/*/tab[not(subtab)]') %}
<?php $all_tabs = array_merge($subtabs, $tabs) ?>
{%          for tab in all_tabs %}
{# Use the name of the element to specify the prefix, this will be 'tab' or 'subtab' #}
<div id="{{ tab.getName() }}_{{ tab['id'] }}"
     class="tab-pane fade in{% if active_tab == tab['id'] %} active{% endif %}">
{{              partial("OPNsense/Mullvad/layout_partials/base_form",[
                    'this_part':tab,
                    'this_model':this_model,
                    'root_form':root_form
                ]) }}
</div>
{%          endfor %}
{%      endif %}
{%  endmacro %}


{##
 # This function builds box contents for each defined box in the XML. Similar
 # to tabs. Supports individual model definitions for each box with base_form.
 #
 #}
{%  macro build_box_contents(xml_data, this_model = null, root_form = null) %}
{%      for box_element in xml_data.box %}
<section class="col-xs-12">
  <div class="content-box">
{{              partial("OPNsense/Mullvad/layout_partials/base_form",[
                    'this_part':box_element,
                    'this_model':this_model,
                    'root_form':root_form
                ]) }}
  </div>
</section>
{%      endfor %}
{%  endmacro %}


{##
 # This macro builds a page using the form data as input.
 #
 # This is a super macro that builds pages with or without tabs, the tab
 # headers, tab contents, and the bootgrid_dialogs all at once.
 #
 # This is to save on having to put all of these commands in the main volt, and
 # to put the div definition in the right place on the page.
 #
 # this_form SimpleXMLObject from which to build the page
 #}

{%  macro build_page(this_form, plugin_safe_name = null, plugin_label = null, lang = null) %}
{%      if this_form %}
{# Add hidden apply changes box, shown when configuration changed, but unsaved. #}
<div class="col-xs-12" id="alt_{{ plugin_safe_name }}_apply_changes" style="display: none;">
  <div class="alert alert-info"
       id="alt_{{ plugin_safe_name }}_apply_changes_info"
       style="min-height: 65px;">
    <form method="post">
        <button type="button"
                id="btn_{{ plugin_safe_name }}_apply_changes"
                class="btn btn-primary pull-right">
            <b>Apply changes</b>
{#          # Progress spinner to activate when applying changes. #}
            <i id="btn_{{ plugin_safe_name }}_apply_changes_progress" class=""></i>
        </button>
    </form>
    <div style="margin-top: 8px;">
        {{ lang._('The %s configuration has been changed. You must apply the changes in order for them to take effect.')|format(plugin_label) }}
    </div>
  </div>
</div>
{# Grab the model specification now. #}
{%      if this_form['model'] %}
{%          set this_model = this_form['model'].__toString() %}
{%          set root_form = true %}
{# Set up our form DOM to house all of the settings for this form. #}
<form id="frm_root_{{ plugin_safe_name }}"
    data-model="{{ this_form['model']|default('') }}">
{%      else %}
{%          set root_form = false %}
{%          set this_model = '' %}
{%      endif %}
{# Grab the active_tab specification if it exists. #}
{%      if this_form['active_tab'] %}
{%          set active_tab = this_form['active_tab'].__toString() %}
{%      else %}
{%          set active_tab = '' %}
{%      endif %}
{# Draw the page based on the form XML SimpleXMLObject. #}
{%          for tab_element in this_form.tab %}
{%              if loop.first %}
{# Create unordered list for the tabs, and try to pick an active tab, only on the first loop. #}
<ul class="nav nav-tabs" role="tablist" id="maintabs">
{# If we have no active_tab defined, if we have no subtabs, pick self, else pick first subtab. #}
{%                  if active_tab == '' %}
{%                      if !(tab_element.subtab) %}
{%                          set active_tab = tab_element['id']|default() %}
{%                      else %}
{%                          set active_tab = tab_element.subtab[0]['id']|default() %}
{%                      endif %}
{%                  endif %}
{%              endif %}
{# If we have subtabs, then let's accommodate them. #}
{%              if tab_element.subtab %}
{# We need to look forward to understand if one of our subtabs is the assigned active_tab from the form. #}
{%                  set active_subtab = false %}
{%                  for node in tab_element.xpath('subtab/@id') %}
{%                      if node.__toString() == active_tab %}
{%                          set active_subtab = true %}
{%                      endif %}
{%                  endfor %}
{# Since we have a subtab, we need to accommodate it with an appropriate dropdown button to display the menu. #}
{# If one of our subtabs is active_tab, then we need to set this tab as active also. #}
<li role="presentation" class="dropdown{% if active_subtab == true %} active{% endif %}">
  <a data-toggle="dropdown"
     href="#"
     class="dropdown-toggle
            pull-right
            visible-lg-inline-block
            visible-md-inline-block
            visible-xs-inline-block
            visible-sm-inline-block"
     role="button">
    <b><span class="caret"></span></b>
  </a>
{# The onclick sets the tab to be selected when the tab itself is clicked. #}
{# If one is defined in the XML, then use that, else pick the first subtab. #}
{%                  set tab_onclick = tab_element['on_click']|default(tab_element.subtab[0]['id']) %}
  <a data-toggle="tab"
     onclick="$('#subtab_item_{{ tab_onclick }}').click();"
     class="visible-lg-inline-block
            visible-md-inline-block
            visible-xs-inline-block
            visible-sm-inline-block"
     style="border-right:0px;">
{# This is the parent tab of the subtabs #}
     <b>{{ tab_element['description'] }}</b>
  </a>
  <ul class="dropdown-menu" role="menu">
{# Now we specify each subtab, iterate through the subtabs for this tab if present. #}
{%                  for subtab_element in tab_element.subtab %}
{%                      if loop.first %}
{# Assume the first subtab should be active if no active_tab is set. #}
{%                          if active_tab == '' %}
{%                              set active_tab = subtab_element['id']|default() %}
{%                          endif %}
{%                      endif %}
<li class="{% if active_tab == subtab_element['id'] %}active{% endif %}">
  <a data-toggle="tab"
     id="subtab_item_{{ subtab_element['id'] }}"
     href="#subtab_{{ subtab_element['id'] }}"
{%                      if subtab_element.style %}
           style="{{ subtab_element.style }}"
{%                      endif %}>{{ subtab_element['description'] }}
  </a>
</li>
{%                  endfor %}
    </ul>
  </li>
{%              else %} {# No subtabs, standard tab, no dropdown#}
<li {% if active_tab == tab_element['id'] %} class="active" {% endif %}>
  <a data-toggle="tab"
     id="tab_header_{{ tab_element['id'] }}"
     href="#tab_{{ tab_element['id'] }}"
{%                  if tab_element.style %}
     style="{{ tab_element.style }}"
{%                  endif %}>
    <b>{{ tab_element['description'] }}</b>
  </a>
</li>
{%              endif %}
{%              if loop.last %}
{# Close the unordered list only on the last loop. #}
</ul>
{%              endif %}
{%          endfor %}

{# Build Tab Contents, if we have tabs. #}
{%          if this_form.tab %}
<div class="tab-content content-box tab-content">
{{              build_tab_contents(this_form, active_tab, this_model, root_form) }}
</div>
{%          endif %}

{%          if this_form.box %}
{# Build any boxes, if we have any. #}
{{              build_box_contents(this_form, this_model, root_form) }}
{%          endif %}

{# Build any fields #}
{%          if this_form.field %}
{#  Since we have only fields, call the partial directly,
    we'll just put them in one box for now. It looks OK.
    Supports model definition via the room XML element. #}
<div class="content-box">
{{              partial("OPNsense/Mullvad/layout_partials/base_form",[
                    'this_part':this_form,
                    'this_model':this_model,
                    'root_form':root_form
                ]) }}
</div>
{%          endif %}
{%          if root_form == true %}
{# Close out the form if one was opened due to a model defiition at the XML root node.
   We need to draw our dialogs, they have their own forms and can't be nested. #}
</form>
{%          endif %}

{# Build our dialogs for any bootgrids now. #}
{%          for bootgrid_field in this_form.xpath('//*/field[type="bootgrid"][dialog]') %}
{{              partial("OPNsense/Mullvad/layout_partials/base_dialog",[
                    'this_grid':bootgrid_field
                ]) }}
{%          endfor %}

{#  # Conditionally display buttons at the bottom of the page. #}
{%          if this_form.button %}
<section class="page-content-main">
{# Alert class used to get padding to look good.
   Maybe there is another class that can be used. #}
  <div class="alert alert-info" role="alert">
{%              for button_element in this_form.button %}
{%                  if button_element['type']|default('primary') in ['primary', 'group' ] %} {# Assume primary if not defined #}
{%                      if button_element['type']|default('') == 'primary' and
                           button_element['action'] %}
    <button class="btn btn-primary"
            id="btn_{{ plugin_safe_name }}_{{ button_element['action'] }}"
            type="button">
      <i class="{{ button_element['icon']|default('') }}"></i>
      &nbsp
      <b>{{ lang._('%s') | format(button_element.__toString()) }}</b>
      <i id="btn_{{ plugin_safe_name }}_progress"></i>
    </button>
{%                      elseif button_element['type'] == 'group' %}
{#  We set our own style here to put the button in the right place. #}
    <div class="btn-group"
         {{ (button_element['id']|default('') != '') ?
             'id="'~button_element['id']~'"' : '' }}>
      <button type="button"
              class="btn btn-default dropdown-toggle"
              data-toggle="dropdown">
        <i class="{{ button_element['icon'] }}"></i>
        &nbsp
        <b>{{ lang._('%s') | format(button_element['label']) }}</b>
        <i id="btn_{{ plugin_safe_name }}_progress"></i>
        &nbsp
        <i class="caret"></i>
      </button>
{%                          if button_element.dropdown %}
      <ul class="dropdown-menu" role="menu">
{%                              for dropdown_element in button_element.dropdown %}
        <li>
          <a id="drp_{{ plugin_safe_name }}_{{ dropdown_element['action'] }}">
            <i class="{{ button_element['icon'] }}"></i>
            &nbsp
            {{ lang._('%s') | format(dropdown_element.__toString()) }}
          </a>
        </li>
{%                              endfor %}
      </ul>
{%                          endif %}
    </div>
{%                      endif %}
{%                  endif %}
{%              endfor %}
  </div>
</section>
{%      endif %}
{%    endif %}
{%  endmacro %}


{##
 # This is a super macro to be used in a <script> element to define all of the
 # attachments necessary for opteration.
 #
 # Takes form, lang for some text fields, and plugin_api_name for some API
 # call definitions as input. Recurses through tabs/subtabs, and boxes.
 # Iterates through all of the fields utilizing a series of
 # if/elseif's to create attachments for those specific fields.
 #
 # Provides attachements for field types:
 #
 # bootgrid
 # command
 # checkbox
 # radio
 # managefile
 #}
{%  macro build_attachments(this_node = null, lang, plugin_api_name, this_model = '') %} {# Have to pass lang into the macro scope #}
{#      This whole structure is designed to arbitrate between input
        being the form data with all the tabs/subtabs/boxes, and just
        tabs/subtabs/boxes. It's a very roundabout approach. #}
{# Need to figure out the model specifications,
   it's crude, but it's a way to keep model/field relationship intact. #}
{# I'd much rather just xpath the fields. #}
{%      if this_node['model'] %}
{%          set this_model = this_node['model'].__toString() %}
{%      endif %}
{# If we're not looking at fields, then we need to recurse. #}
{%      if not this_node.field %}
{%          for node in this_node %}
{{              build_attachments(node, lang, plugin_api_name, this_model) }}
{%          endfor %}
{%      endif %}
{# Getting here means that this_node has a field node.
   Now we're really to loop through fields. #}
{%      for field in this_node.field %}
{# Formulate this field's id combined with the model as designated. #}
{%          set field_id = this_model~'.'~field.id %}
{# =============================================================================
 # bootgrid: import button
 # =============================================================================
 # Allows importing a list into a field
 #}
{%          if field.type == "bootgrid" and
               field.target and
               field.api.import and
               field.label %}
{#  # From the Firewall alias plugin
    #   Since base_dialog() only has buttons for Save, Close, and Cancel,
    #   we build our own dialog using some wrapper functions, and
    #   perform validation on the data to be imported. #}
{#      Create an id derived from the target, escaping periods. #}
{# XXX Need to macro this. #}
<?php $safe_id = preg_replace('/\./','_',$field->target); ?>
    $('#btn_bootgrid_' + $.escapeSelector("{{ safe_id }}") + '_import').click(function(){
        let $msg = $("<div/>");
        let $imp_file = $("<input type='file' id='btn_bootgrid_{{ safe_id }}_select' />");
        let $table = $("<table class='table table-condensed'/>");
        let $tbody = $("<tbody/>");
        $table.append(
          $("<thead/>").append(
            $("<tr>").append(
              $("<th/>").text('{{ lang._("Source") }}')
            ).append(
              $("<th/>").text('{{ lang._("Message") }}')
            )
          )
        );
        $table.append($tbody);
        $table.append(
          $("<tfoot/>").append(
            $("<tr/>").append($("<td colspan='2'/>").text(
              '{{ lang._('Errors were encountered, no records were imported.') }}'
            ))
          )
        );

        $imp_file.click(function(){
{#          # Make sure upload resets when new file is provided
            # (bug in some browsers) #}
            this.value = null;
        });
        $msg.append($imp_file);
        $msg.append($("<hr/>"));
        $msg.append($table);
        $table.hide();
{#      # Show the dialog to the user for importing -#}
        BootstrapDialog.show({
          title: "{{ lang._('Import %s')|format(field.label) }}",
          message: $msg,
          type: BootstrapDialog.TYPE_INFO,
          draggable: true,
          buttons: [{
              label: '<i class="fa fa-cloud-upload" aria-hidden="true"></i>',
              action: function(sender){
                  $table.hide();
                  $tbody.empty();
                  if ($imp_file[0].files[0] !== undefined) {
                      const reader = new FileReader();
                      reader.readAsBinaryString($imp_file[0].files[0]);
                      reader.onload = function(readerEvt) {
                          let import_data = null;
                          try {
                              import_data = JSON.parse(readerEvt.target.result);
                          } catch (error) {
                              $tbody.append(
                                $("<tr/>").append(
                                  $("<td>").text("*")
                                ).append(
                                  $("<td>").text(error)
                                )
                              );
                              $table.show();
                          }
                          if (import_data !== null) {
                              ajaxCall("{{ field.api.import }}", {'data': import_data,'target': '{{ field.target }}' }, function(data,status) {
                                  if (data.validations !== undefined) {
                                      Object.keys(data.validations).forEach(function(key) {
                                          $tbody.append(
                                            $("<tr/>").append(
                                              $("<td>").text(key)
                                            ).append(
                                              $("<td>").text(data.validations[key])
                                            )
                                          );
                                      });
                                      $table.show();
                                  } else {
                                      std_bootgrid_reload('bootgrid_{{ safe_id }}')
                                      sender.close();
                                  }
                              });
                          }
                      }
                  }
              }
          },{
             label:  "{{ lang._('Cancel') }}",
             action: function(sender){
                sender.close();
             }
           }]
        });
    });
{%          endif %}
{# =============================================================================
 # bootgrid: export button
 # =============================================================================
 # Allows exporting a list out for external storage or manupulation
 #
 # Mostly came from the firewall plugin.
 #}
{%          if field.type == "bootgrid" and
               field.target and
               field.api.export %}
{#      Create an id derived from the target, escaping periods. #}
<?php $safe_id = preg_replace('/\./','_',$field->target); ?>
    $("#btn_bootgrid_{{ safe_id }}_export").click(function(){
{#      Make ajax call to URL. #}
        return $.ajax({
            type: 'GET',
            url: "{{ field.api.export }}",
            complete: function(data,status) {
                if (data) {
                    var output_data = '';
                    var ext = '';
                    try {
                        var response = jQuery.parseJSON(data);
                        output_data = JSON.stringify(data, null, 2);
                        ext = 'json';

                    } catch {
                        // Assume text
                        output_data = data['responseText'];
                        ext = 'txt';
                    }
                    let a_tag = $('<a></a>').attr('href','data:application/json;charset=utf8,' + encodeURIComponent(output_data))
                        .attr('download','{{ field.target }}_export.' + ext).appendTo('body');

                    a_tag.ready(function() {
                        if ( window.navigator.msSaveOrOpenBlob && window.Blob ) {
                            var blob = new Blob( [ output_data ], { type: "text/csv" } );
                            navigator.msSaveOrOpenBlob( blob, '{{ field.target }}_export.' + ext );
                        } else {
                            a_tag.get(0).click();
                        }
                    });
                }
            },
            data: { "target": "{{ field.target }}"}
        });
    });
{%          endif %}
{# =============================================================================
 # bootgrid: clear button
 # =============================================================================
 # Allows clearing the log file that the bootgrid is displaying the contents of.
 #
 #}
{%          if field.type == "bootgrid" and
               field.target and
               field.api.clear %}
<?php $safe_id = preg_replace('/\./','_',$field->target); ?>
    $("#btn_bootgrid_{{ safe_id }}_clear").click(function(){
        event.preventDefault();
        BootstrapDialog.show({
            type: BootstrapDialog.TYPE_DANGER,
            title: "Log",
            message: "Do you really want to flush this log?",
            buttons: [{
                label: "No",
                action: function(dialogRef) {
                    dialogRef.close();
                }
            }, {
                label: "Yes",
                action: function(dialogRef) {
                    ajaxCall("{{ field.api.clear }}", {}, function(){
                        dialogRef.close();
                        $('#bootgrid_{{ safe_id }}').bootgrid('reload');
                    });
                }
            }]
        });
    });
{%          endif %}
{# =============================================================================
 # bootgrid: UIBootgrid attachments (API definition)
 # =============================================================================
 # Builds out the UIBootgrid attachments according to form definition
 #}
{%          if field.type == "bootgrid" and
               field.target %}
{#      Create an id derived from the target, escaping periods. #}
<?php $safe_id = preg_replace('/\./','_',$field->target); ?>
    $('#' + 'bootgrid_' + $.escapeSelector("{{ safe_id }}")).UIBootgrid(
        {
{%              if field.api.search %}
            'search':'{{ field.api.search }}/{{ field.target }}/',
{%              endif %}
{%-             if field.api.get %}
            'get':'{{ field.api.get }}/{{ field.target }}/',
{%              endif %}
{%-             if field.api.set %}
            'set':'{{ field.api.set }}/{{ field.target }}/',
{%              endif %}
{%-             if field.api.add %}
            'add':'{{ field.api.add }}/{{ field.target }}/',
{%              endif %}
{%-             if field.api.del %}
            'del':'{{ field.api.del }}/{{ field.target }}/',
{%              endif %}
{%-             if field.api.info %}
            'info':'{{ field.api.info }}/{{ field.target }}/',
{%              endif %}
{%-             if field.api.toggle %}
            'toggle':'{{ field.api.toggle }}/{{ field.target }}/',
{%              endif %}
            'options':{ 'selection':
{# XXX needs to be changed to builtin instead of class #}
{%-             if (field.class == 'logs') -%}
                            false
{%-             else -%}
                            {{- field.columns['selection']|default('false') }}
{%-             endif %}
{%              if field.row_count %},
                        'rowCount':[{{ field.row_count }}]
{%              endif %}
{%              if field.grid_options %},
                        {{- field.grid_options }}
{%              endif %}
            }
        }
    );
{%          endif %} {#
{# =============================================================================
 # command: attachments for command field types
 # =============================================================================
 # Attachs to the command button sets up the classes and
 # defines the API to be called when clicked
 #}
{%          if field.type == "command" and
               field.id and
               field.api %}
    $('#btn_{{ field_id }}_command').click(function(){
        var command_input;
{%              if field.style == "input" %}
        command_input = $("#inpt_" + $.escapeSelector("{{ field_id }}_command")).val();
{%              elseif field.style == "selectpicker" %}
        command_input = $("button[data-id=" + $.escapeSelector("{{ field_id }}")).attr('title');
{%              endif %}
{%              if field.output %}
        $('#pre_{{ field.output }}_output').text("Executing...");
{%              endif %}
        $("#btn_{{ field_id }}_progress").addClass("fa fa-spinner fa-pulse");
        ajaxCall(url='{{ field.api }}', sendData={'command_input':command_input}, callback=function(data,status) {
            if (data['status'] != "ok") {
{%              if field.output %}
                $('#pre_{{ field.output }}_output').text(data['status']);
{%              endif %}
            } else {
{%              if field.output %}
                $('#pre_{{ field.output }}_output').text(data['response']);
{%              endif %}
            }
            $("#btn_{{ field_id }}_progress").removeClass("fa fa-spinner fa-pulse");
        });
    });
{%          endif %}
{# =============================================================================
 # checkbox, radio, dropdown: toggle functionality
 # =============================================================================
 # A toggle function for checkboxes, radio buttons, and dropdown menus.
 #}
{%          if ((field.type == "checkbox" or
                 field.type == "radio" or
                 field.type == "onoff" or
                 field.type == "dropdown") and
                 field.id) and
                 field.control %}
{%              if field.control.action %}
{#  Attach to the element associated with the field id,
    or the text field associated with the radio buttons #}
    $("#" + $.escapeSelector("{{ field_id }}")).change(function(e){
{#  This prevents the field from acting out if it is in a disabled state. #}
        if ($(this).hasClass("disabled") == false) {
{#  This pulls the on_set key values out of all of the field's attributes,
    and then creates an array of the unique values. #}
{%                  set on_set_values_xml = field.control.xpath('action/@on_set') %}
{%                  set value_list = [] %}
{%                  set value_list_array = [] %}
{%                  for xml_node in on_set_values_xml %}
<?php $value_list_array[] = $xml_node->__toString() ?>
{%                  endfor %}
<?php $value_list = array_unique($value_list_array); ?>
{#  Iterate through the values we found to start building our if blocks. #}
{%                  for on_set in value_list %}
{#  Start if statments looking at different value based on field type #}
{%                      if field.type == "checkbox" %}
            if ($(this).prop("checked") == {{ on_set }} ) {
{%                      elseif field.type == "radio" or
                               field.type == "onoff" or
                               field.type == "dropdown" %}
            if ($(this).val() == "{{ on_set }}") {
{%                      endif %}
{#  Iterate through the fields only if the "on_set" value matches that of the current for loop's "on_set" variable. #}
{%                      for target_field in field.control.action if target_field['on_set'] == on_set %}
{#  We use the field's value so we don't have to have a line of code for each version, check first that they're OK. #}
{%                          if target_field['do_state'] in [ "disabled", "enabled", "hidden", "visible" ] %}
                toggle("{{ target_field }}", "{{ target_field['type'] }}", "{{ target_field['do_state'] }}");
{%                          endif %}
{%                      endfor %}
            }
{%                  endfor %}
        }
    });
{%              endif %}
{%          endif %}
{# =============================================================================
 # radio: click activities
 # =============================================================================
 # Click event for radio type objects
 #}
{%          if ((field.type == "radio" or
                 field.type == "onoff") and
                 field.id) %}
{# XXX having to manually write this PHP becuase the Volt doesn't pick up the {% else %} #}
{# It's something to do with being inside the above if statement. #}
<?php if ($field->builtin == 'button-group' || $field->type == 'onoff') { ?>
    $('input[name=rdo_' + $.escapeSelector("{{ field_id }}") + ']').parent('label').click(function () {
<?php } elseif ($field->builtin == "legacy" || !$field->builtin) { ?>
    $('input[name=rdo_' + $.escapeSelector("{{ field_id }}") + ']').click(function () {
<?php } ?>
{#      # Store which radio button was selected, since this value will be
        # dynamic depending on which radio button is clicked.
        # This looks a bit strange because all of the radio input tags have
        # the same name attribute, and differ in the content of the
        # surrounding <label> tag, and value attribute.
        # So when this is clicked, it sets the value of the field to be the same
        # same as the value of the radio button that was selected.
        # Then we trigger a change event to set any enable/disabled fields. #}
{# XXX having to manually write this PHP becuase the Volt doesn't pick up the {% else %} #}
<?php if ($field->builtin == 'button-group' || $field->type == 'onoff') { ?>
        $('#' + $.escapeSelector("{{ field_id }}")).val($(this).children('input').val());
<?php } elseif ($field->builtin == "legacy" || !$field->builtin) { ?>
        $('#' + $.escapeSelector("{{ field_id }}")).val($(this).val());
<?php } ?>
        $('#' + $.escapeSelector("{{ field_id }}")).trigger("change");;
    });
{%          endif %}
{# =============================================================================
 # radio: change activities
 # =============================================================================
 # Change function which updates the values of the approprite radio button.
 #}
{%          if (field.type == "radio" or
                field.type == "onoff") %}
    $('#' + $.escapeSelector("{{ field_id }}")).change(function(e){
{#      # Set whichever radiobutton accordingly, may already be selected.
        # This covers the initial page load situation. #}
        var field_value = $('#' + $.escapeSelector("{{ field_id }}")).val();
        {# This catches the first pass, if change event is initiated before the
           value of the target field is set by mapDataToFormUI() #}
        if (field_value != "") {
{# XXX having to manually write this PHP becuase the Volt doesn't pick up the {% else %} #}
<?php if ($field->builtin == 'button-group' || $field->type == 'onoff') { ?>
            $('input[name=rdo_' + $.escapeSelector("{{ field_id }}") + '][value=' + field_value + ']').parent('label').addClass("active");
<?php } elseif ($field->builtin == "legacy" || !$field->builtin) { ?>
            $('input[name=rdo_' + $.escapeSelector("{{ field_id }}") + '][value=' + field_value + ']').prop("checked", true);
<?php } ?>
        }
    });
{%          endif %}
{# =============================================================================
 # managefile: file selection
 # =============================================================================
 # Catching when a file is selected for upload.
 #
 # Requires creation of "this_namespace" object earlier in script.
 #
 # I think this mostly came from the Web Proxy plugin.
 #}
{%          if field.type == "managefile" and
               field.id and
               field.api.upload %}
    $("input[for=" + $.escapeSelector("{{ field_id }}") + "][type=file]").change(function(evt) {
{#      Check browser compatibility #}
        if (window.File && window.FileReader && window.FileList && window.Blob) {
             var file_event = evt.target.files[0];
{#          If a file has been selected, let's get the content and file name. #}
            if (file_event) {
                var reader = new FileReader();
                reader.onload = function(readerEvt) {
{#                  Store these in our namespace for use in the upload function.
                    This namespace was created at the beginning of the script section. #}
                    this_namespace.upload_file_content = readerEvt.target.result;
                    this_namespace.upload_file_name = file_event.name;
{#                  Set the value of the input box we created to store the file name. #}
                    if ($("label[id='lbl_" + $.escapeSelector("{{ field_id }}") + "']").length){
                        $("label[id='lbl_" +
                          $.escapeSelector("{{ field_id }}") +
                          "']").text("Current: " +
                          this_namespace.upload_file_name);
                    } else {
                        $("#" + $.escapeSelector("{{ field_id }}") +
                          ",input[for=" + $.escapeSelector("{{ field_id }}") +
                          "][type=text]").val(this_namespace.upload_file_name);
                    }
                };
{#              Actually get the file, explicitly reading as text. #}
                reader.readAsText(file_event);
            }
        } else {
{#          Maybe do something else if support isn't available for this API. #}
            alert("Your browser is too old to support HTML5 File's API.");
        }
    });
{# Attach to the ready event for the field to trigger and update to the value
   of the visible elements. #}
    $('#' + $.escapeSelector('{{ field_id }}')).change(function(e){
        var file_name = $('#' + $.escapeSelector('{{ field_id }}')).val();
{#      Modern style #}
        if ($('label[id="lbl_' + $.escapeSelector('{{ field_id }}') + '"]').length) {
            $('label[id="lbl_' + $.escapeSelector('{{ field_id }}') + '"]').text("Current: " + file_name);
        }
{#      Classic style #}
        if ($('input[for="' + $.escapeSelector('{{ field_id }}') + '"][type=text]').length) {
            $('input[for="' + $.escapeSelector('{{ field_id }}') + '"][type=text]').val(file_name);
        }
    });
{%          endif %}
{# =============================================================================
 # managefile: file upload
 # =============================================================================
 # Upload activity of the selected file.
 #
 # Requires creation of "this_namespace" object earlier in script.
 #}
{%          if field.type == "managefile" and
               field.id and
               field.api.upload %}
    $("#btn_" + $.escapeSelector("{{ field_id }}" + "_upload")).click(function(){
{#      Check that we have the file content. #}
        if (this_namespace.upload_file_content) {
            ajaxCall("{{ field.api.upload }}", {'content': this_namespace.upload_file_content,'target': '{{ field_id }}'}, function(data,status) {
                if (data['error'] !== undefined) {
{#                      error saving #}
                        stdDialogInform(
                            "Status: " + data['status'],
                            data['error'],
                            "OK",
                            function(){},
                            "warning"
                        );
                } else {
{#                  Clear the file content since we're done, then save, reload, and tell user. #}
                    this_namespace.upload_file_content = null;
                    saveFormAndReconfigure($("#btn_" + $.escapeSelector("{{ field_id }}" + "_upload")));
                    stdDialogInform(
                        "File Upload",
                        "Upload of "+ this_namespace.upload_file_name + " was successful.",
                        "Ok"
                    );
{#                  No error occurred, so let set the setting for storage in the config. #}
                    $("#" + $.escapeSelector("{{ field_id }}")).val(this_namespace.upload_file_name);
                }
            });
        }
    });
{%          endif %}
{# =============================================================================
 # managefile: file download
 # =============================================================================
 # Download activity of the file that was uploaded.
 #}
{%          if field.type == "managefile" and
               field.id and
               field.api.download %}
    $("#btn_" + $.escapeSelector("{{ field_id }}") + "_download").click(function(){
       window.open('{{ field.api.download }}/{{ field_id }}');
{#      # Use blur() to force the button to lose focus.
        # This addresses a UI bug where after clicking the button, and after dismissing
        # the save dialog (either save or cancel), upon returning to the browser window
        # the button lights up, and displays the tooltip. It then gets stuck like that
        # after the user clicks somewhere in the browser window.
        # This appears to only happen on the download activity. #}
        $(this).blur()
    });
{%          endif %}
{# =============================================================================
 # managefile: file remove
 # =============================================================================
 # Removing a file that was uploaded.
 #
 # Dialog structure came from the web proxy plugin.
 #}
{%          if field.type == "managefile" and
               field.id and
               field.api.remove %}
        $("#btn_" + $.escapeSelector("{{ field_id }}") + "_remove").click(function() {
            BootstrapDialog.show({
                type:BootstrapDialog.TYPE_DANGER,
                title: '{{ lang._('Remove File') }} ',
                message: '{{ lang._('Are you sure you want to remove this file?') }}',
                buttons: [{
                    label: '{{ lang._('Yes') }}',
                    cssClass: 'btn-primary',
                    action: function(dlg){
                        dlg.close();
                        ajaxCall("{{ field.api.remove }}", {'field': '{{ field_id }}'}, function(data,status) {
                            if (data['error'] !== undefined) {
                                stdDialogInform(
                                    data['error'],
                                    "API Returned:\n" + data['status'],
                                    "OK",
                                    function(){},
                                    "warning"
                                );
                            } else {
                                if ($("label[id='lbl_" + $.escapeSelector("{{ field_id }}") + "']").length){
                                    $("label[id='lbl_" + $.escapeSelector("{{ field_id }}") + "']").text("Current: ");
                                } else {
                                    $("#" + $.escapeSelector("{{ field_id }}") +
                                      ",input[for=" + $.escapeSelector("{{ field_id }}") +
                                      "][type=text]").val("");
                                }
                                saveFormAndReconfigure($("#btn_" + $.escapeSelector("{{ field_id }}") + "_remove"));
                                stdDialogInform(
                                    "Remove file",
                                    "Remove file was successful.",
                                    "Ok"
                                );
                            }
                        });
                    }
                }, {
                    label: '{{ lang._('No') }}',
                    action: function(dlg){
                        dlg.close();
                    }
                }]
            });
        });

{%          endif %}
{%          if field.type == "status" and
               field.id %}
    $('span[id=' + $.escapeSelector("{{ field_id }}") + ']').change(function(e){
{#      # This covers the initial page load situation. #}
        var field_value = $(this).text();
        {# This catches the first pass, if change event is initiated before the
           value of the target field is set by mapDataToFormUI() #}
        if (field_value != "") {
{%              for this_label in field.labels.children() %}
             if (field_value == "{{ this_label.__toString() }}") {
                $(this).addClass("label-{{ this_label.getName() }}")
            }
{%              endfor %}
        }
    });
{%           endif %}
{%      endfor %}
{%  endmacro %}
