<?php
/**
 * Copyright 2015 Google Inc.
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

use GuzzleHttp\Client;
use Symfony\Component\Form\Forms;

$twig = get_twig();
$storage = get_cloud_storage();
$datastore = get_cloud_datastore();
$options = get_option('faceswap');
$projectId = $options['project_id'];
$bucketName = $options['bucket_name'];
$formFactory = Forms::createFormFactory();
$form = $formFactory
    ->createBuilder('form')
    ->add('Image1', 'file')
    ->add('Image2', 'file')
    ->getForm();
$form->handleRequest();
$content = '';
if ($form->isValid()) {
    // upload the images to Google Cloud Storage
    $files = $_FILES['form'];
    $bucket = $storage->bucket($bucketName);
    $prefix = 'tmp-' . time() . '/';

    $img1 = 'uploads/' . $prefix . $files['name']['Image1'];
    $img2 = 'uploads/' . $prefix . $files['name']['Image2'];
    $bucket->upload(
        fopen($files['tmp_name']['Image1'], 'r'),
        ['name' => $img1]
    );
    $bucket->upload(
        fopen($files['tmp_name']['Image2'], 'r'),
        ['name' => $img2]
    );

    // make the call to the faceswap app
    $http = new Client([
        'base_uri' => sprintf(
            'https://worker-dot-%s.appspot.com/',
            $projectId
        ),
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
