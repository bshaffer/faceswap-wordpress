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

use Faceswap\SettingsPage;
use GuzzleHttp\Client;
use Symfony\Component\Form\Forms;

$twig = get_twig();
$storage = get_cloud_storage();
$projectId = SettingsPage::getProjectId();
$bucketName = SettingsPage::getBucketName();
$serviceUrl = SettingsPage::getServiceUrl();
$formFactory = Forms::createFormFactory();
$form = $formFactory
    ->createBuilder('form')
    ->add('face_image', 'file')
    ->add('base_images', 'file', [
        'multiple' => true
    ])
    ->getForm();
$form->handleRequest();
$error = null;
if ($form->isValid()) {
    try {
        if (empty($serviceUrl)) {
            if (!getenv('FACESWAP_WORKER_SERVICE_HOST')) {
                throw new LogicException('You must set the Service URL in ' .
                    'Faceswap Settings or deploy a "faceswap-worker" service ' .
                    'with Kubernetes.');
            }
            $serviceUrl = sprintf(
                '%s:%s',
                getenv('FACESWAP_WORKER_SERVICE_HOST'),
                getenv('FACESWAP_WORKER_SERVICE_PORT')
            );
        }
        // upload the images to Google Cloud Storage
        $files = $_FILES['form']['tmp_name'];
        if (empty($files['base_images']) || empty($files['face_image'])) {
            throw new InvalidArgumentException('One or more files failed to ' .
                'upload.');
        }
        $baseImages = [];
        foreach((array) $files['base_images'] as $baseImagePath) {
            $baseImageJpeg = convert_image_to_jpeg($baseImagePath);
            $baseImages[] = base64_encode(file_get_contents($baseImageJpeg));
        }
        $faceImageJpeg = convert_image_to_jpeg($files['face_image']);
        $faceImage = base64_encode(file_get_contents($faceImageJpeg));;;
        return $twig->render('faceswap.html.twig', [
            'form' => $form->createView(),
            'faceImage' => $faceImage,
            'baseImages' => $baseImages,
            'serviceUrl' => $serviceUrl,
            'cols' => ceil(sqrt(count($baseImages))),
        ]);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

return $twig->render('faceswap.html.twig', [
    'form' => $form->createView(),
    'error' => $error
]);
