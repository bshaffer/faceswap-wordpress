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
    ->add('base_image', 'file')
    ->add('face_image', 'file')
    ->getForm();
$form->handleRequest();
$content = '';
if ($form->isValid()) {
    try {
        if (empty($serviceUrl)) {
            throw new LogicException('You must set the Service URL in ' .
                'Faceswap Settings.');
        }
        // upload the images to Google Cloud Storage
        $files = $_FILES['form']['tmp_name'];
        if (empty($files['base_image']) || empty($files['face_image'])) {
            throw new InvalidArgumentException('One or more files failed to ' .
                'upload.');
        }
        $image1Path = convert_image_to_jpeg($files['base_image']);
        $image2Path = convert_image_to_jpeg($files['face_image']);
        $img1 = file_get_contents($image1Path);
        $img2 = file_get_contents($image2Path);

        // make the call to the faceswap app
        $http = new Client([
            'base_uri' => $serviceUrl
        ]);
        $response = $http->post('/', ['json' => [
            'image1' => base64_encode($img1),
            'image2' => base64_encode($img2)
        ]]);

        if (!trim($imageBase64 = $response->getBody())) {
            throw new Exception('A face was not found in one or ' .
                'more of the uploaded images!');
        }

        $content .= sprintf(
            '<img src="data:image/jpeg;base64, %s" />',
            $imageBase64
        );
    } catch (Exception $e) {
        $content .= '<div class="error">' . $e->getMessage() . '</div>';
    }
}

return $content . $twig->render('faceswap.html.twig', [
    'form' => $form->createView()
]);
