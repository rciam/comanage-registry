// Generate a dialog box confirming <txt>.  On confirmation, forward to <url>.
// txt                - body text           (string, required)
// url                - forward url         (string, required)
// confirmbtxt        - confirm button text (string, optional)
// cancelbtxt         - cancel button text  (string, optional)
// titletxt           - dialog title text   (string, optional)
// tokenReplacements  - strings to replace tokens in dialog body text (array, optional)
function js_form_generic(infotext, url, submitbtxt, cancelbtxt, titletxt) {
    $("#formLegend").html(infotext);
    $("#formDialog").dialog({
        modal: true,
        title: titletxt,
        buttons: [{
            text: submitbtxt,
            "id": "btnSubmit",
            click: function () {
                code=$(this).find('input[name="code"]').val();
                forwardUrl = url + "?code=" + code;
                window.location = forwardUrl;
            },
        }, {
            text: cancelbtxt,
            "id": "btnCancel",
            click: function () {
                //cancelCallback();
                $(this).dialog('close');
            },
        }],
        close: function () {
            //do something
        }
    });
}