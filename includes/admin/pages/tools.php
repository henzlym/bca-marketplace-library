<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <form action="options.php" method="post">
        <?php
        settings_fields( 'marketplace-library-tools' ); // output security fields for the registered setting "marketplace_settings"
        do_settings_sections( 'marketplace-library-tools' ); // output setting sections and their fields
        submit_button('Save Settings'); // output save settings button
        ?>
    </form>
</div>
