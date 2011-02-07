$(document).ready(function() {
    $('#userIdInput').keyup(validField($('#userIdInput'), /\d{5,}/));
    $('#inputForm').submit(function(e) {
        e.preventDefault();
        $.post($(this).attr("action"), $(this).serialize(), function(data) {
            $('#charDiv').show('fast');
            $(data).each(function(i) {
                $('#charSel').
                append($('<option></option>').
                    attr('value',$(this)[0].charid).
                    text($(this)[0].name));
            });
        }, "json");
    });
});

function validField(field, regex) {
    $(field).removeClass('invalidinput validinput');
    var match = $(field).val().match(regex);
    if(match == null){
        $(this).addClass('invalidinput');
    } else {
        $(this).addClass('validinput');
    }
}