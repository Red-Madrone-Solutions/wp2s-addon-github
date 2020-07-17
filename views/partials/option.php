<input
    name="<?php echo esc_attr($option->name()); ?>"
    type="<?php echo esc_attr($option->type()); ?>"
    id="<?php echo esc_attr($option->id()); ?>"
    class="regular-text"
    value="<?php echo esc_attr($option->ui_value()); ?>"
    <?php echo $description ? sprintf('aria-describedby="%s-description"', esc_attr($option->name())) : ''; ?>
    <?php echo $option->formatAttrs(); // phpcs:ignore ?>
>
<?php if ( $description ) : ?>
    <p class="description" id="<?php echo esc_attr($option->name()); ?>-description"><?php echo esc_html($description); ?></p>
<?php endif; ?>
