<?php

use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;

$dry_run = false; // ⬅️ Mets à false pour exécuter réellement

$types_to_fields = [
  'article' => ['field_fichier_pdf', 'field_image'],
  'documents' => ['field_fichier_pdf', 'field_image'],
  'revue_de_presse' => ['field_fichier_pdf', 'field_image'],
  'actualit_' => ['field_fichier_pdf', 'field_image'],
  'notes_de_lectures' => ['field_fichier_pdf', 'field_image'],
];

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

$created_count = 0;

// 1. Fichiers liés à des nodes
foreach ($types_to_fields as $type => $fields) {
  foreach ($fields as $field_name) {
    $nids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', $type)
      ->condition($field_name, NULL, 'IS NOT NULL')
      ->execute();

    $nodes = Node::loadMultiple($nids);

    foreach ($nodes as $node) {
      $file = $node->get($field_name)->entity;
      if (!$file instanceof File) {
        continue;
      }

      $media_info = get_media_bundle($file);
      if (!$media_info) {
        continue;
      }

      $media = Media::create([
        'bundle' => $media_info['bundle'],
        'uid' => $node->getOwnerId(),
        'status' => 1,
        'name' => $file->getFilename(),
        $media_info['field_target'] => [
          'target_id' => $file->id(),
        ],
      ]);
      if (!$dry_run) {
        $media->save();
      } else {
        echo "[Dry-run] Medianon sauvegardés.\n";
      }

      $node->set($media_info['field_target'], [
        'target_id' => $media->id(),
      ]);
      if (!$dry_run) {
      
        $node->save();
      } else {
        echo "[Dry-run] node non sauvegardés.\n";
      }

      $created_count++;
    }
  }
}

// 2. Fichiers orphelins
$used_fids = \Drupal::database()->select('file_usage', 'fu')
  ->fields('fu', ['fid'])
  ->condition('type', 'node')
  ->execute()
  ->fetchCol();

$all_fids = \Drupal::database()->select('file_managed', 'fm')
  ->fields('fm', ['fid'])
  ->execute()
  ->fetchCol();

$orphans = array_diff($all_fids, $used_fids);

foreach ($orphans as $fid) {
  $file = File::load($fid);
  if (!$file instanceof File) {
    continue;
  }

  $media_info = get_media_bundle($file);
  if (!$media_info) {
    continue;
  }

  $media = Media::create([
    'bundle' => $media_info['bundle'],
    'uid' => 1,
    'status' => 1,
    'name' => $file->getFilename(),
    $media_info['field_target'] => [
      'target_id' => $file->id(),
    ],
  ]);
  if (!$dry_run) {
    $media->save();

  } else {
    echo "[Dry-run] Media non sauvegardés.\n";
  }

  $created_count++;
}

print "Nombre total de médias créés : $created_count\n";