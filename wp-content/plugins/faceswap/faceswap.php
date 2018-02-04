<?php
/**
 * Plugin Name: Faceswap
 * Plugin URI: https://github.com/bshaffer/faceswap-plugin
 * Description: Adds faceswap functionality
 * Author: Brent Shaffer
 * Author URI: https://betterbrent.com/
 * Version: 1.0
 * Text Domain: faceswap
 *
 * Copyright: (c) 2017 Brentertainment
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @author    Brent Shaffer
 * @copyright Copyright (c) 2017 Brentertainment
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 *
 */

use Google\Cloud\Storage\StorageClient;
use Google\Cloud\PubSub\PubSubClient;
use Google\Auth\CredentialsLoader;
use Google\Auth\ApplicationDefaultCredentials;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Form\TwigRenderer;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Component\Translation\Translator;
use Faceswap\SettingsPage;

if (! defined('ABSPATH')) {
    // Exit if accessed directly
    exit;
}

// autoload vendors
require_once __DIR__ . '/../../../vendor/autoload.php';

if (is_admin()) {
    SettingsPage::register();
}

wp_register_style('faceswap', plugins_url('css/faceswap.css', __FILE__ ));

function get_gcloud_project_id()
{
    return getenv('GCLOUD_PROJECT');
}

function get_cloud_storage()
{
    return new StorageClient([
        'projectId' => get_gcloud_project_id(),
    ]);
}

function get_pubsub_client()
{
    return new PubSubClient([
        'projectId' => get_gcloud_project_id(),
    ]);
}

function get_twig()
{
    $defaultFormTheme = 'form_div_layout.html.twig';

    $vendorDir = realpath(__DIR__ . '/../../../vendor');
    // the path to TwigBridge library so Twig can locate the
    // form_div_layout.html.twig file
    $appVariableReflection = new \ReflectionClass('\Symfony\Bridge\Twig\AppVariable');
    $vendorTwigBridgeDir = dirname($appVariableReflection->getFileName());
    // the path to your other templates
    $viewsDir = realpath(__DIR__);

    $twig = new Twig_Environment(new Twig_Loader_Filesystem(array(
        $viewsDir,
        $vendorTwigBridgeDir . '/Resources/views/Form',
    )));
    $formEngine = new TwigRendererEngine(array($defaultFormTheme));
    $twig->addRuntimeLoader(new \Twig_FactoryRuntimeLoader(array(
        TwigRenderer::class => function () use ($formEngine, $csrfManager) {
            return new TwigRenderer($formEngine, $csrfManager);
        },
    )));
    $twig->addExtension(
        new FormExtension(new TwigRenderer($formEngine))
    );
    $twig->addExtension(
        new TranslationExtension(new Translator('en'))
    );
    return $twig;
}

function create_new_firebase_document()
{
    // create firebase document
    $http = CredentialsLoader::makeHttpClient(
        ApplicationDefaultCredentials::getCredentials(
            'https://www.googleapis.com/auth/datastore'
        )
    );
    $baseUrl = 'firestore.googleapis.com';
    $path = sprintf(
        'https://%s/v1beta1/projects/%s/databases/%s/documents/faceswap',
        $baseUrl,
        get_gcloud_project_id(),
        '(default)'
    );
    $response = $http->request('post', $path);
    $json = json_decode((string) $response->getBody(), true);
    // return the document ID
    return @end(explode('/', $json['name']));
}

function convert_image_to_jpeg($imagePath)
{
    // jpg, png, gif or bmp?
    if (false === $imgInfo = getimagesize($imagePath)) {
        throw new InvalidArgumentException('Image not found or not an image');
    }

    switch ($imgInfo[2]) {
        case IMAGETYPE_GIF:
            $src = imagecreatefromgif($imagePath);
            break;
        case IMAGETYPE_JPEG:
            // Do nothing! We are already JPEG format
            $src = imagecreatefromjpeg($imagePath);
            break;
        case IMAGETYPE_PNG:
            $src = imagecreatefrompng($imagePath);
            break;
        default:
            throw new InvalidArgumentException('Unsupported filetype');
    }

    $src = imagescale($src, 700);
    imagejpeg($src, $imagePath . '.jpg');
    imagedestroy($src);
    return $imagePath . '.jpg';
}

function render_faceswap_form()
{
    $content = include __DIR__ . '/app.php';
    print($content);
}
