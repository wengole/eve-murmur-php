<div id="apicontent">
    <h1>Mumble Registration</h1>
    <p>1. <a href="http://mumble.sourceforge.net" target="_blank">Download Mumble</a></p>
    <p>2. <a href="http://www.eveonline.com/api/" target="_blank">Get your limited API Key</a></p>
    <br />
    <?= form_open('register/add'); ?>
    <p>User ID:</p>
    <?= form_input(array('name' => 'userid', 'id' => 'userid', 'class' => 'userinput', 'value' => $userID)); ?>
    <p>Limited API:</p>
    <?= form_input(array('name' => 'apikey', 'id' => 'apikey', 'class' => 'userinput', 'value' => $apiKey)); ?>
    <?php if (!empty($unameArray)): ?>
        <p>Pick Character:</p>
        <?= form_dropdown('username', $unameArray, $selectedUser, 'class="userselect"'); ?>
        <p>Choose a password:</p>
        <?= form_password(array('name' => 'password', 'class' => 'userinput')); ?>
        <p>Confirm password:</p>
        <?= form_password(array('name' => 'password2', 'class' => 'userinput')); ?>
    <?php endif; ?>
    <?php if (!empty($errorMessage)): ?>
        <?= $errorMessage ?>
    <?php endif; ?>
    <div class='buttons'>
    <?= form_button(array(
            'name' => 'save',
            'type' => 'submit',
            'content' => img('images/apply2.png').'Submit',
            'class' => 'positive')
        ); ?>
    </div>
    <?= form_close(); ?>
</div>
<?= "Time: " . $this->benchmark->elapsed_time(); ?>
<?= "\tMemory: " . $this->benchmark->memory_usage(); ?>