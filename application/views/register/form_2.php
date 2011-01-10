    <?php if (!empty($unameArray)): ?>
        <p>Pick Character:</p>
        <?= form_dropdown('username', $unameArray, $selectedUser, 'class="userselect"'); ?>
        <p>Choose a password:</p>
        <?= form_password(array('name' => 'password', 'class' => 'userinput')); ?>
        <p>Confirm password:</p>
        <?= form_password(array('name' => 'password2', 'class' => 'userinput')); ?>
    <?php endif; ?>