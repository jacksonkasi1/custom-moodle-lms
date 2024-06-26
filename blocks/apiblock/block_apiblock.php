<?php
class block_apiblock extends block_base {
    function init() {
        $this->title = get_string('pluginname', 'block_apiblock');
    }

    function get_content() {
        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '
            <div class="text-center">
                <button id="apiButton" class="btn btn-primary">' . get_string('fetchapidata', 'block_apiblock') . '</button>
                <div id="apiResponse" class="mt-3"></div>
            </div>';
        $this->content->footer = '';

        // Include the JavaScript file
        $this->page->requires->js_call_amd('block_apiblock/api', 'init');

        return $this->content;
    }
}
