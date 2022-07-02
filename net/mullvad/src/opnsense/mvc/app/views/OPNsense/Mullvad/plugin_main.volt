{##
 #
 # OPNsense® is Copyright © 2022 – 2018 by Deciso B.V.
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
#}

{##
 # This is the main template for this plugin, and service as the base for all
 # of its pages. All of the other volt templates extend this one, allowing
 # for easy updates, and a consistent user experience.
 #
 # There are variables that should be provided in the view.
 # These are commonly set in the calling controller.
 #
 # Variables:
 # plugin_safe_name string           a safe name for the plugin
 #                                   that doesn't include any unusual characters.
 # plugin_label     string           a plain language label for the plugin
 #                                   for use in dialog titles, and such
 # this_xml         SimpleXMLObject  this is the SimpleXMLObject of the form to render
 #                                   commonly set by the calling controller
 #}

<?php ob_start(); ?>

{# Pull in our macro definitions. #}
{% include "OPNsense/Mullvad/_macros.volt" %}
{# Define some styles. #}
{% include "OPNsense/Mullvad/_styles.volt" %}

{% block body %}
{# Build the entire page including:
    tab headers,
    tabs content (include fields and bootgrids),
    and all bootgrid dialogs #}
{{ build_page(this_xml, plugin_safe_name, plugin_label, lang) }}
{% endblock %}

<script>
$( document ).ready(function() {

{#/*
    Add in any dynamic script content, functions, and . */#}
{%   include "OPNsense/Mullvad/layout_partials/base_script_content.volt" %}

{#/*
    Dynamically build any of the necessary attachments for things like bootgrids, etc. */#}
{{  build_attachments(this_xml, lang, plugin_api_name) }}

{#/*
    Populate data_get_map for use in mapDataToFormUI() function later. */#}
    var data_get_map = setDataGetMap();

{#/*
    Conditionally display this dialog only if we actually have data to load in mapDataToFormUI() */#}
    if (Object.keys(data_get_map).length) {
        BootstrapDialog.show({
            title: 'Loading settings',
            closable: false,
            message:
                '{{ lang._("Please wait while settings are loaded...") }}' +
                '&nbsp&nbsp<i class="fa fa-cog fa-spin"></i>'
            });
    }

{% block script %}
    refreshFields();
{% endblock %}


});
</script>

{# Clean up the blank lines, probably inefficient, but makes things look nice. #}
<?php  echo join("\n", array_filter(array_map(function ($i) { $o = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "", $i); if (!empty(trim($o))) {return $o;} }, explode("\n", ob_get_clean()))));  ?>
