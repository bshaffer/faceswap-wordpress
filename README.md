Faceswap Wordpress
==================

1. Download and set up Wordpress
1. Copy all the files from this repository into your wordpress install

    ```
    cp -R /path/to/faceswap-wordpress/* /path/to/wordpress
    ```
1. run `composer install`.
1. Enable the [Insert PHP](https://wordpress.org/plugins/insert-php/) plugin.
1. Enable the `Faceswap` plugin.
1. Go to the `Faceswap Settings` page and fill in all the settings.
1. Add the following code to any page to render the faceswap form and functionality:

    ```
    [insert_php]
    render_faceswap_form();
    [/insert_php]
    ```
1. Browse to the page and profit!