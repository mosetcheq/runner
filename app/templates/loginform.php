<form method="post">
    <?= \Rnr\Http\FormHandler::sender('User:loginSubmit', true); ?>
    <input type="text" name="login" id="login">
    <input type="submit" value="Login!">
</form>