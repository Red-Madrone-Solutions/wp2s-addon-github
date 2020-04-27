<h2>GitHub Deployment Options</h2>

<form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">

    <?php wp_nonce_field($view_params['action'], $view_params['nonce_name']); ?>
    <input name="action" type="hidden" value="<?php echo esc_attr($view_params['action']); ?>">
    <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th scope="row"><label for="account">Account</label></th>
                <td><input name="account" type="text" id="account" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="repository">Repository</label></th>
                <td><input name="repository" type="text" id="repository" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="branch">Branch</label></th>
                <td><input name="branch" type="text" id="branch" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="personal_access_token">Personal Access Token</label></th>
                <td><input name="personal_access_token" type="password" id="personal_access_token" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="subdirectory">Subdirectory</label></th>
                <td><input name="subdirectory" type="text" id="subdirectory" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="commit_message">Commit Message</label></th>
                <td><input name="commit_message" type="text" id="commit_message" class="regular-text"></td>
            </tr>
        </tbody>
    </table>
    <p class="submit">
        <input type="submit" name="submit" id="submit" class="button button-primary" value="Save GitHub Options">
    </p>
</form>

