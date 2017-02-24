<?php
class MySettingsPage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin',
            'Faceswap Settings',
            'manage_options',
            'faceswap-setting-admin',
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'faceswap' );
        ?>
        <div class="wrap">
            <h1>My Settings</h1>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'faceswap_option_group' );
                do_settings_sections( 'faceswap-setting-admin' );
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {
        register_setting(
            'faceswap_option_group', // Option group
            'faceswap', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'setting_section_id', // ID
            'My Custom Settings', // Title
            array( $this, 'print_section_info' ), // Callback
            'faceswap-setting-admin' // Page
        );

        add_settings_field(
            'project_id', // ID
            'Google Cloud Project ID', // Title
            array( $this, 'project_id_callback' ), // Callback
            'faceswap-setting-admin', // Page
            'setting_section_id' // Section
        );

        add_settings_field(
            'bucket_name',
            'Google Cloud Bucket Name',
            array( $this, 'bucket_name_callback' ),
            'faceswap-setting-admin',
            'setting_section_id'
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
        if( isset( $input['project_id'] ) )
            $new_input['project_id'] = sanitize_text_field( $input['project_id'] );

        if( isset( $input['bucket_name'] ) )
            $new_input['bucket_name'] = sanitize_text_field( $input['bucket_name'] );

        return $new_input;
    }

    /**
     * Print the Section text
     */
    public function print_section_info()
    {
        print 'Enter your settings below:';
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function project_id_callback()
    {
        printf(
            '<input type="text" id="project_id" name="faceswap[project_id]" value="%s" />',
            isset( $this->options['project_id'] ) ? esc_attr( $this->options['project_id']) : ''
        );
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function bucket_name_callback()
    {
        printf(
            '<input type="text" id="bucket_name" name="faceswap[bucket_name]" value="%s" />',
            isset( $this->options['bucket_name'] ) ? esc_attr( $this->options['bucket_name']) : ''
        );
    }
}

if( is_admin() )
    $my_settings_page = new MySettingsPage();