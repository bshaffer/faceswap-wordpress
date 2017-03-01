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
    if (empty($projectId) || empty($bucketName) || empty($serviceUrl)) {
        die('You must set the Project ID, ' .
            'Bucket Name, and Service URL in Faceswap Settings');
    }
    // upload the images to Google Cloud Storage
    $files = $_FILES['form'];
    $bucket = $storage->bucket($bucketName);
    $prefix = 'tmp-' . time() . '/';
    $img1 = 'uploads/' . $prefix . $files['name']['base_image'];
    $img2 = 'uploads/' . $prefix . $files['name']['face_image'];
    $bucket->upload(
        fopen($files['tmp_name']['base_image'], 'r'),
        ['name' => $img1]
    );
    $bucket->upload(
        fopen($files['tmp_name']['face_image'], 'r'),
        ['name' => $img2]
    );

    $serviceUrl = $serviceUrl ?: sprintf(
        'https://worker-dot-%s.appspot.com/',
        $projectId
    );

    // make the call to the faceswap app
    $http = new Client([
        'base_uri' => $serviceUrl
    ]);

    $response = $http->get('/', ['query' => [
        'image1' => $img1,
        'image2' => $img2,
        'bucket' => $bucketName
    ]]);

    $object = $bucket->object('output.jpg');
    $content .= sprintf('<img src="data:image/jpeg;base64, %s" />',
        base64_encode($object->downloadAsString()));
}

return $content . $twig->render('faceswap.html.twig', [
    'form' => $form->createView()
]);
