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
use Google\Cloud\Datastore\DatastoreClient;
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

function get_cloud_storage()
{
    return new StorageClient([
        'projectId' => SettingsPage::getProjectId(),
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

function render_faceswap_form()
{
    $content = include __DIR__ . '/app.php';
    print($content);
}
