<?php

use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\file\Entity\FileUsage;

// Fonction pour déterminer le type de media
function get_media_bundle($file) {
  $mime = $file->getMimeType();
  if (str_starts_with($mime, 'image/')) {
    return ['bundle' => 'image', 'field_target' => 'field_media_image'];
  }
  if ($mime === 'application/pdf') {
    return ['bundle' => 'document', 'field_target' => 'field_media_document'];
  }
  return null;
}

// Initialisation des compteurs
$total_files = 0;
$linked_files = 0;
$unlinked_files = 0;

$fids_done = [];

// 1. Fichiers liés à des nodes
$types_to_fields = [
  'article' => ['field_fichier_pdf', 'field_image'],
  'documents' => ['field_fichier_pdf', 'field_image'],
  'revue_de_presse' => ['field_fichier_pdf', 'field_image'],
  'actualit_' => ['field_fichier_pdf', 'field_image'],
  'notes_de_lectures' => ['field_fichier_pdf', 'field_image'],
];

foreach ($types_to_fields as $type => $fields) {
  foreach ($fields as $file_field) {
    $nids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', $type)
      ->condition($file_field, NULL, 'IS NOT NULL')
      ->execute();

    $nodes = Node::loadMultiple($nids);

    foreach ($nodes as $node) {
      /** @var \Drupal\node\Entity\Node $node */
      $file = $node->get($file_field)->entity;
      if (!$file instanceof File) {
        continue;
      }

      $fid = $file->id();
      $fids_done[$fid] = true;
      $linked_files++;

      $media_info = get_media_bundle($file);
      if (!$media_info) {
        continue;
      }

      $simulated_media = [
        'bundle' => $media_info['bundle'],
        'uid' => $node->getOwnerId(),
        'status' => 1,
        'name' => $file->getFilename(),
        'target_file_id' => $fid,
      ];

      print_r([
        'source' => 'node',
        'node_id' => $node->id(),
        'node_type' => $type,
        'original_field' => $file_field,
        'target_media_field' => $media_info['field_target'],
        'simulated_media' => $simulated_media,
      ]);
    }
  }
}

// 2. Fichiers restants (non liés à des nodes)
$all_files = File::loadMultiple();
foreach ($all_files as $file) {
  /** @var FileInterface $file */
  $fid = $file->id();
  if (isset($fids_done[$fid])) {
    continue; // déjà traité
  }

  $media_info = get_media_bundle($file);
  if (!$media_info) {
    continue;
  }

  $simulated_media = [
    'bundle' => $media_info['bundle'],
    'uid' => 1, // admin par défaut
    'status' => 1,
    'name' => $file->getFilename(),
    'target_file_id' => $fid,
  ];

  print_r([
    'source' => 'isolated',
    'file_id' => $fid,
    'filename' => $file->getFilename(),
    'target_media_field' => $media_info['field_target'],
    'simulated_media' => $simulated_media,
  ]);

  $unlinked_files++;
}

// 3. Bilan
$total_files = count($all_files);

echo "\n-------------------------\n";
echo "Fichiers totaux : $total_files\n";
echo "Fichiers liés à des nodes : $linked_files\n";
echo "Fichiers isolés traités : $unlinked_files\n";
echo "-------------------------\n";
