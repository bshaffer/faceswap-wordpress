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

use Symfony\Component\Form\Forms;

// register the stylesheet
wp_enqueue_style('faceswap');

// get the twig service
$twig = get_twig();
$pubsub = get_pubsub_client();
$topic = $pubsub->topic('process-faceswap');
$formFactory = Forms::createFormFactory();
$form = $formFactory
    ->createBuilder('form')
    ->add('face_image', 'file', [
        'attr' => ['accept' => 'images/*']
    ])
    ->add('base_images', 'file', [
        'attr' => ['accept' => 'images/*'],
        'multiple' => true
    ])
    ->getForm();
$form->handleRequest();
$error = null;
if ($form->isValid()) {
    try {
        // upload the images to Google Cloud Storage
        $files = $_FILES['form']['tmp_name'];
        if (empty($files['base_images']) || empty($files['face_image'])) {
            throw new InvalidArgumentException('One or more files failed to ' .
                'upload.');
        }
        $documentId = create_new_firebase_document();
        $faceImageJpeg = convert_image_to_jpeg($files['face_image']);
        $faceImage = base64_encode(file_get_contents($faceImageJpeg));
        $baseImages = [];
        foreach((array) $files['base_images'] as $i => $baseImagePath) {
            $baseImageJpeg = convert_image_to_jpeg($baseImagePath);
            $baseImage = base64_encode(file_get_contents($baseImageJpeg));
            $topic->publish([
                'data' => json_encode([
                    'faceImage' => $faceImage,
                    'baseImage' => $baseImage,
                ]),
                'attributes' => [
                    'imageName' => (string) $i,
                    'documentId' => $documentId,
                ]
            ]);
            $baseImages[$i] = $baseImage;
        }

        return $twig->render('faceswap.html.twig', [
            'form' => $form->createView(),
            'faceImage' => $faceImage,
            'baseImages' => $baseImages,
            'documentId' => $documentId,
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
