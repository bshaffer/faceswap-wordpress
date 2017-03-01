Faceswap Wordpress
==================

1. Download Wordpress
1. Set up Wordpress
1. Copy all the files from this repository into your wordpress install

    ```
    cp -R /path/to/faceswap-wordpress/* /path/to/wordpress
    ```
1. run `composer install`
1. Install the [Insert PHP](https://wordpress.org/plugins/insert-php/) plugin
1. Add the following code to any page to render the faceswap form and functionality:

    ```
    [insert_php]
    render_faceswap_form();
    [/insert_php]
    ```