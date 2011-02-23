<script type="text/javascript" charset="utf-8">
    $.fn.preload = function() {
        this.each(function(){
            $('<img />')[0].src = this;
        });
    }
    $(['<?= base_url() ?>images/ajax-loader.gif']).preload();
    $(document).ready(function() {
        $('#submitButton').button();
        $('#resetButton').button();
        $('#resetButton').click(function(){
            //window.location.reload();
            $(':input').removeClass('invalidinput validinput');
            $('#charDiv').slideUp('slow');
            $('#validError').slideUp('slow');
            $('#userIdInput').val('');
            $('#apiInput').val('');
        });
        $('#dialog').dialog({ autoOpen: false,
            close: function() {window.location.reload()} });
        $('#inputForm').submit(function(e) {
            e.preventDefault();
            $(':input').removeClass('invalidinput validinput');
            $('#validError').hide();
            $('#validError').empty();
            var uid = $.trim($('#userIdInput').val());
            $('#userIdInput').val(uid);
            var key = $.trim($('#apiInput').val());
            $('#apiInput').val(key);
            var validUserID = validField('#userIdInput', /^\d{5,}$/);
            var validApiKey = validField('#apiInput', /^[A-Za-z0-9]{64}$/);
            var validPassword1 = validField('#password1', /^\w{5,}$/);
            var validForm = true;
            if(!validUserID) {
                $('#userIdInput').addClass('invalidinput');
                $('#validError').append($('<div><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span>\n\
                                <strong>Invalid UserID</strong><br />Must be at least 5 numerical digits.</div>').
                                                    attr({
                                                    'class': 'ui-state-error ui-corner-all',
                                                    style: 'padding: .7em .7em'
                                                }));
                                                $('#validError').append('<br />');
                                                $('#userIdInput').removeAttr('disabled');
                                                $('#validError').slideDown('slow');
                                                validForm = false;
                                            }
                                            if(!validApiKey) {
                                                $('#apiInput').addClass('invalidinput');
                                                $('#validError').append($('<div><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span>\n\
                                <strong>Invalid API Key</strong><br />Must be exactly 64 characters. Letters and numbers only.</div>').
                                                    attr({
                                                    'class': 'ui-state-error ui-corner-all',
                                                    style: 'padding: .7em .7em'
                                                }));
                                                $('#validError').append('<br />');
                                                $('#apiInput').removeAttr('disabled');
                                                $('#validError').slideDown('slow');
                                                validForm = false;
                                            }
                                            if(!validPassword1 && $('#charDiv').is(':visible')) {
                                                $('#password1').addClass('invalidinput');
                                                $('#password2').addClass('invalidinput');
                                                $('#validError').append($('<div><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span>\n\
                                <strong>Invalid password</strong><br />Must be at least 5 characters. Letters, numbers and underscore only.</div>').
                                                    attr({
                                                    'class': 'ui-state-error ui-corner-all',
                                                    style: 'padding: .7em .7em'
                                                }));
                                                $('#validError').append('<br />');
                                                $('#validError').slideDown('slow');
                                                validForm = false;
                                            }
                                            if($('#password1').val() != $('#password2').val() && $('#charDiv').is(':visible')) {
                                                $('#password2').addClass('invalidinput');
                                                $('#validError').append($('<div><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span>\n\
                                <strong>Passwords don\'t match.<strong></div>').
                                                    attr({
                                                    'class': 'ui-state-error ui-corner-all',
                                                    style: 'padding: .7em .7em'
                                                }));
                                                $('#validError').append('<br />');
                                                $('#validError').slideDown('slow');
                                                validForm = false;
                                            }
                                            if (validForm) {
                                                $('#loadingProgress').slideDown(100);
                                                $.post($(this).attr("action"), $(this).serialize(), function(data) {
                                                    $('#loadingProgress').slideUp('slow');
                                                    $('#userIdInput').attr('disabled', 'disabled');
                                                    $('#apiInput').attr('disabled', 'disabled');
                                                    if(data.hasOwnProperty('type')){
                                                        if(data.type == 'success') {
                                                            html = '<p>Click <a href=' + data.message + '>here</a> once you have Mumble installed</p>\n\
                                        <p>Once connected go to Server->Connect, hit Add New..., give it a label (e.g. Comms) then \n\
                                        click OK to save the connection as a favourite</p><p>Your corp ticker will be prepended to your \n\
                                        username automatically within 5 minutes and will take effect next time you login</p>';
                                                                    $('#dialog').append(html);
                                                                    $('#dialog').dialog('open');
                                                                } else {
                                                                    $('#validError').append($('<div><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span>\n\
                                            <strong>' + data.message + '</strong></div>').
                                                                            attr({
                                                                            'class': 'ui-state-error ui-corner-all',
                                                                            style: 'padding: .7em .7em'
                                                                        }));
                                                                        $('#validError').append('<br />');
                                                                        $('#validError').slideDown('slow');
                                                                    }
                                                                }
                                                                $('#password1').removeClass('invalidinput validinput');
                                                                $('#password2').removeClass('invalidinput validinput');
                                                                $('#charDiv').slideDown('slow');
                                                                $('#charSel').empty();
                                                                $(data).each(function(i) {
                                                                    $('#charSel').
                                                                        append($('<option></option>').
                                                                        attr('value',$(this)[0].charid).
                                                                        text($(this)[0].name));
                                                                });
                                                            }, "json");
                                                        }
                                                    });
                                                });

                                                function validField(field, regex) {
                                                    $(field).removeClass('invalidinput validinput');
                                                    var match = $(field).val().match(regex);
                                                    if(match == null){
                                                        $(field).addClass('invalidinput');
                                                        return false;
                                                    } else {
                                                        $(field).addClass('validinput');
                                                        return true;
                                                    }
                                                }

                                                function trimmer() {
                                                    $('body :text').each(function() {
                                                        $(this).val() = $(this).val().trim();
                                                    });
                                                }
</script>
<form name="input" id="inputForm" action="<?= base_url() ?>register/submit">
    <div id="apicontent">
        <h1>Mumble Registration</h1>

        <div class="ui-state-highlight ui-corner-all" style="padding: 0 0 .7em .7em;">
            <p><span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span><a href="http://mumble.sourceforge.net" target="_blank">Download Mumble</a></p>
        </div>
        <br />

        <div class="ui-state-highlight ui-corner-all" style="padding: 0 0 .7em .7em;">
            <p><span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span><a href="http://www.eveonline.com/api/" target="_blank">Get your limited API Key</a></p>
        </div>

        <p>User ID:</p>
        <input type="text" class="userinput" name="userid" id="userIdInput">

        <p>Limited API:</p>
        <input type="text" class="userinput" name="apikey" id="apiInput">

        <div id="charDiv" style="display: none;">
            <p>Character: </p>
            <select class="userselect" name="charid" id="charSel">
            </select>
            <p>Password:</p>
            <input type="password" class="userinput" name="password" id="password1">
            <p>Confim Password:</p>
            <input type="password" class="userinput" name="password2" id="password2">
        </div>

        <p></p>

        <div id="validError" style="display: none;">
        </div>

        <div id="loadingProgress" style="display: none;">
            <img src="<?= base_url() ?>images/ajax-loader.gif" alt="loading..." style="display: block; margin-left: auto; margin-right: auto;" />
        </div>
        <button name="save" type="submit" id="submitButton" style="align: left;">Submit</button>
        <button name="reset" type="button"id="resetButton" style="align: right;">Reset</button>
    </div>

    <div id="dialog" title="Registration successful">
    </div>

</form>