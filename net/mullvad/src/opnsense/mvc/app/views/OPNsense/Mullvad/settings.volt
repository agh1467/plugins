{##
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
 ##}

{##
 # This is the template for the about page.
 #
 # Variables sent in by the controller:
 # plugin_name            string  name of this plugin, used for API calls
 # plugin_version         string  version of this plugin
 # dnscrypt_proxy_version string  version of dnscrypt-proxy
 # this_form              array   the form XML in an array
 ##}

{% extends 'OPNsense/Mullvad/plugin_main.volt' %}


{% block script %}
{#/* This attachment does the following:
     1. Run saveFormToEntpoint for the nearest form.
     1a. Check if save returns anything but result = saved,
     1b. If not, display a message box, clear the button, end there.
     1c. If there are ny validation errors, callback_fail is executed, clear the button, end there.
     2a. Run the login API
     2b. Check if the response is OK,
     2c. if not display a message box.
     2d. Clear the button status. */#}
    $('button[id="btn_' + $.escapeSelector("settings.account_number") + '_login_command"]').click(
        function(){
{#/*        create a button object for use throughout this function. */#}
            const this_button = $(this);
            busyButton(this_button);
            saveFormToEndpoint(
                url="/api/mullvad/settings/set",
                formid=this_button.closest('form').attr("id"),
                callback_ok=
                    function(data){
                        if (
                            (data['result'].toLowerCase().trim() != 'saved') &&
                            data['status']
                            ) {
                            BootstrapDialog.show({
                                type: BootstrapDialog.TYPE_WARNING,
                                title: this_button.data('error-title'),
                                message: data['status'],
                                draggable: true
                            });
                            clearButton(this_button);
                        } else {
                            ajaxCall(
                                url="/api/mullvad/account/login",
                                sendData={},
                                callback=
{#/* These callbacks can probably be moved into named functions. */#}
                                    function(data,status) {
                                        if (
                                            (status != "success" || data['status'].toLowerCase().trim() != 'ok') &&
                                            data['status']
                                            ) {
                                            BootstrapDialog.show({
                                                type: BootstrapDialog.TYPE_WARNING,
                                                title: this_button.data('error-title'),
                                                message: data['status_msg'] ? data['status_msg'] : data['status'],
                                                draggable: true
                                            });
                                        } else {
                                            refreshFields();
                                        }
                                        clearButton(this_button);
                                    }
                            );
                        }
                    },
                false,
                callback_fail=
                    function() {
                        clearButton(this_button);
                    }
            );
        }
    );

{#/*
    This function will generally be used but can be overriden with a block statement. */#}
    mapDataToUI().done(function(){
{#/*
    Update the fields using the tokenizer style. */#}
        formatTokenizersUI();
{#/*
    Refresh the data for the select picker fields. */#}
        $('.selectpicker').selectpicker('refresh');
{#/*
    Dismiss our loading dialog */#}
        $('div[class^="modal bootstrap-dialog"]').modal('toggle');
    });
{% endblock %}
