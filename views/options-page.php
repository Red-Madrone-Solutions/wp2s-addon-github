<h2>GitHub Deployment Options</h2>

<form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">

    <?php wp_nonce_field($view_params['action'], $view_params['nonce_name']); ?>
    <input name="action" type="hidden" value="<?php echo esc_attr($view_params['action']); ?>">
    <table class="form-table" role="presentation">
        <tbody>
            <?php foreach ( $view_params['option_set'] as $option ) : $description = $option->description(); ?>
                <tr>
                    <th scope="row"><label for="<?php echo esc_attr($option->id()); ?>"><?php echo esc_html($option->label()); ?></label></th>
                    <td>
                    <input name="<?php echo esc_attr($option->name()); ?>" type="<?php echo esc_attr($option->type()); ?>" id="<?php echo esc_attr($option->id()); ?>" class="regular-text" value="<?php echo esc_attr($option->ui_value()); ?>" <?php echo $description ? sprintf('aria-describedby="%s-description"', $option->name()) : ''; ?>>
                        <?php if ( $description ) : ?>
                            <p class="description" id="<?php echo esc_attr($option->name()); ?>-description"><?php echo esc_html($description); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p class="submit">
        <input type="submit" name="submit" id="submit" class="button button-primary" value="Save GitHub Options">
    </p>
</form>

