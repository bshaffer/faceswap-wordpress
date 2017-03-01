<?php
/**
 * Copyright 2017 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Faceswap;

class SettingsPage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private static $options;

    /**
     * Start up
     */
    public static function register()
    {
        $settings = new SettingsPage();
        add_action('admin_menu', array($settings, 'add_plugin_page'));
        add_action('admin_init', array($settings, 'page_init'));
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
            array($this, 'create_admin_page')
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        print('<div class="wrap">');
        print('<form method="post" action="options.php">');
        settings_fields('faceswap_option_group');
        do_settings_sections('faceswap-setting-admin');
        submit_button();
        print('</form>');
        print('</div>');
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {
        register_setting(
            'faceswap_option_group', // Option group
            'faceswap', // Option name
            array($this, 'sanitize') // Sanitize
        );

        add_settings_section(
            'setting_section_id', // ID
            'My Custom Settings', // Title
            array($this, 'print_section_info'), // Callback
            'faceswap-setting-admin' // Page
        );

        add_settings_field(
            'project_id', // ID
            'Google Cloud Project ID', // Title
            array($this, 'project_id_callback'), // Callback
            'faceswap-setting-admin', // Page
            'setting_section_id' // Section
        );

        add_settings_field(
            'bucket_name',
            'Google Cloud Bucket Name',
            array($this, 'bucket_name_callback'),
            'faceswap-setting-admin',
            'setting_section_id'
        );

        add_settings_field(
            'service_url',
            'Faceswap Service URL (optional)',
            array($this, 'service_url_callback'),
            'faceswap-setting-admin',
            'setting_section_id'
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize($input)
    {
        $new_input = array();
        if (isset($input['project_id'])) {
            $new_input['project_id'] = sanitize_text_field($input['project_id']);
        }

        if (isset($input['bucket_name'])) {
            $new_input['bucket_name'] = sanitize_text_field($input['bucket_name']);
        }

        if (isset($input['service_url'])) {
            $new_input['service_url'] = sanitize_text_field($input['service_url']);
        }

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
            esc_attr(self::getProjectId())
        );
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function bucket_name_callback()
    {
        printf(
            '<input type="text" id="bucket_name" name="faceswap[bucket_name]" value="%s" />',
            esc_attr(self::getBucketName())
        );
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function service_url_callback()
    {
        printf(
            '<input type="text" id="service_url" name="faceswap[service_url]" value="%s" />',
            esc_attr(self::getServiceUrl())
        );
    }

    public static function getProjectId()
    {
        self::registerOptions();
        return self::$options['project_id'];
    }

    public static function getBucketName()
    {
        self::registerOptions();
        return self::$options['bucket_name'];
    }

    public static function getServiceUrl()
    {
        self::registerOptions();
        return self::$options['service_url'];
    }

    private static function registerOptions()
    {
        if (is_null(self::$options)) {
            self::$options = get_option('faceswap');
        }
    }
}
