<h2>GitHub Deployment Options</h2>

<form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">

    <?php wp_nonce_field($view_params['action'], $view_params['nonce_name']); ?>
    <input name="action" type="hidden" value="<?php echo esc_attr($view_params['action']); ?>">
    <table class="form-table" role="presentation">
        <tbody>
            <?php foreach ( $view_params['option_set'] as $option ) : ?>
                <tr>
                    <th scope="row"><label for="<?php echo $option->id(); ?>"><?php echo $option->label(); ?></label></th>
                    <td><input name="<?php echo $option->name(); ?>" type="<?php echo $option->type(); ?>" id="<?php echo $option->id(); ?>" class="regular-text"></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p class="submit">
        <input type="submit" name="submit" id="submit" class="button button-primary" value="Save GitHub Options">
    </p>
</form>
