{##
 #
 # OPNsense® is Copyright © 2014 – 2018 by Deciso B.V.
 # This file is Copyright © 2018 by Michael Muenz <m.muenz@gmail.com>
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
 # THIS SOFTWARE IS PROVIDED “AS IS” AND ANY EXPRESS OR IMPLIED WARRANTIES,
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
 # This is the template for the settings page.
 #
 # This is the main page for this plugin.
 #
 # Variables sent in by the controller:
 # plugin_api_name  string  name of this plugin, used for API calls
 # this_form        array   the form XML in an array
 #}

{% extends 'OPNsense/Dnscryptproxy/plugin_main.volt' %}

{% block script %}
{#/* global Object container, used by file upload/download functions.*/#}
{# XXX we should be able to conditionally add this if needed for upload/download usage. #}
    window.this_namespace = {};

{# XXX need to address the model specification here, needs to be dynamically populated from the form XML data. #}
{#/* Create an event hanlder for whenever a create/update/delete call is made to the bootgrid API.
     This isn't truly ideal but was the first successful method I've discovered. */#}
    $(document).on("ajaxSuccess", function(event, jqxhr, settings) {
        if ((settings.url.startsWith("/api/{{ plugin_api_name }}/settings/bootgrid/set") ||
             settings.url.startsWith("/api/{{ plugin_api_name }}/settings/bootgrid/del") ||
             settings.url.startsWith("/api/{{ plugin_api_name }}/settings/bootgrid/add")
            )) {
            // Run the toggle for the apply changes pane. Won't show if config isn't dirty.
            toggleApplyChanges();
        }
    });

{#/*
    Attachment to trigger restoring the default sources via api. */#}
    $("#btn_restoreSourcesAct").SimpleActionButton({
{#/*    We're defining onPreAction here in order to display a confirm dialog
        before executing the button's API call. */#}
        onPreAction: function () {
{#/*        We create a defferred object here to hold the function
            from completing before input is received from the user. */#}
            const dfObj = new $.Deferred();
{#/*        stdDialogConfirm() doesn't return the result, i.e. cal1back()
            If the user clicks cancel it doesn't execute callback(), so
            so it never comes back to this function. There is no way to
            clean up the spinner on the button if the user clicks cancel.
            So we're using the wrapper BootstrapDialog.confirm() directly. */#}
            BootstrapDialog.confirm({
                title: '{{ lang._('Confirm restore sources to default') }} ',
                message: '{{ lang._('Are you sure you want to remove all sources, and restore the defaults?') }}',
                type: BootstrapDialog.TYPE_WARNING,
                btnOKLabel: '{{ lang._('Yes') }}',
                callback: function (result) {
                    if (result) {
{#/*                    User answered yes, we can resolve dfObj now. */#}
                        dfObj.resolve();
                    } else {
{#/*                    User answered no, clean up the spinner added by SimpleActionButton(), and then do nothing. */#}
                        $("#btn_restoreSourcesAct").find('.reload_progress').removeClass("fa fa-spinner fa-pulse");
                    }
                }
            });
{#/*        This is used to prevent the function from completeing before
            getting input from the user first. Only gets returned after
            the dialog box has been dismissed. */#}
            return dfObj;
        },
        onAction: function(data, status){
{#/*        Toggle the Apply Changes area since the configuration changed, it's dirty now. */#}
            toggleApplyChanges();
{#/*        This executes after the API call is complete.
            We need to refresh the grid since the data has changed. */#}
            std_bootgrid_reload("bootgrid_sources_source"); {#/* id attribute of the bootgrid HTML element. */#}
        }
    });

{#/*
    # This mapDataToFormUI() uses a callback to perform first time configuration. */#}
    mapDataToFormUI(setDataGetMap()).done(function(){
{#/*    # Do the first time setup, pre-configuring some default settings and such. */#}
        if ($('#' + $.escapeSelector('settings.first_time_setup')).prop("checked") == false) {
{#/*        # Set up default sources */#}
            const dfObj = new $.Deferred();
            var element = $('#' + $.escapeSelector('settings.first_time_setup'));
            var this_frm = $(element).closest("form");
            var frm_id = this_frm.attr("id");
            var frm_model = this_frm.attr("data-model");
            var api_url="/api/{{ plugin_api_name }}/" + frm_model + "/set";
{#/*        # Dismiss the loading dialog, and then display a new First-Time setup dialog */#}
            $('div[class^="modal bootstrap-dialog"]').modal('toggle');
            BootstrapDialog.show({
                title: 'First Time Configuration',
                closeable: false,
                message:
                    '{{ lang._("Performing first time configuration. Please wait... ") }}' +
                    '&nbsp&nbsp<i class="fa fa-cog fa-spin"></i>'
            });
            ajaxCall("/api/dnscryptproxy/settings/restoreSources", {}, function(){
                dfObj.resolve();
{#/*            # flip bit for first time set up */#}
                $('#' + $.escapeSelector('settings.first_time_setup')).prop("checked", true)
                saveFormToEndpoint(url=api_url, formid=frm_id, callback_ok=function(){
                    ajaxCall(url="/api/{{ plugin_api_name }}/service/reconfigure", sendData={}, function(data,status){
{#/*                # Force a page reload to reload dropdowns and such. */#}
                    window.location.reload();
                    });
                });
                return dfObj;
            });
        };
{#/*
    Update the fields using the tokenizer style. */#}
        formatTokenizersUI();
{#/*
    Refresh the data for the select picker fields. */#}
        $('.selectpicker').selectpicker('refresh');
{#/*
    Toggle the apply changes message for when the config is dirty/clean. */#}
        toggleApplyChanges();
{#/*
    Dismiss our loading dialog */#}
        $('div[class^="modal bootstrap-dialog"]').modal('toggle');
    });
{% endblock %}
