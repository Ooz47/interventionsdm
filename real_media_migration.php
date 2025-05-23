<?php

/**
 * Script de migration des fichiers (File entities) vers Media entities
 * 
 * Ce script permet de migrer les entités File vers des entités Media.
 * Compatible avec Drupal 9, 10 et 11.
 * 
 * Utilisation :
 * 1. Configurer $types_to_fields selon vos types de contenu
 * 2. Vérifier que $dry_run est à true pour un test
 * 3. Exécuter le script via drush scr real_media_migration.php
 * 
 * @requires Drupal >= 9
 * @requires Media module
 */

use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;

// Configuration globale pour la performance et la sécurité
define('BATCH_SIZE', 50); // Nombre de nodes à traiter par lot
define('MAX_EXECUTION_TIME', 0); // 0 = illimité
set_time_limit(MAX_EXECUTION_TIME);

// Mode simulation pour tester sans modifier la base de données
$dry_run = true;

// Configuration : Types de contenu et leurs champs fichiers associés
// Format: 'type_de_contenu' => ['champ_fichier1', 'champ_fichier2']
$types_to_fields = [
  'article' => ['field_fichier_pdf', 'field_image'],
  'documents' => ['field_fichier_pdf', 'field_image'],
  // Ajoutez vos types de contenu et champs ici
];

// Fonction utilitaire pour les logs avec horodatage
function log_message($message, $type = 'info') {
  echo date('[Y-m-d H:i:s]') . " [$type] $message\n";
}

/**
 * Détermine le type de média approprié selon le type MIME du fichier
 * Retourne la configuration du bundle média ou null si non supporté
 */
function get_media_bundle($file) {
  $mime = $file->getMimeType();
  // Liste des types MIME supportés et leur configuration média associée
  $supported_types = [
    'image/' => ['bundle' => 'image', 'field_target' => 'field_media_image'],
    'application/pdf' => ['bundle' => 'document', 'field_target' => 'field_media_document'],
  ];

  foreach ($supported_types as $mime_prefix => $config) {
    if (str_starts_with($mime, $mime_prefix)) {
      return $config;
    }
  }

  log_message("Type MIME non supporté : $mime", 'warning');
  return null;
}

// Initialisation des compteurs pour les statistiques
$created_count = 0;
$skipped_count = 0;
$error_count = 0;

// PARTIE 1 : Migration des fichiers attachés aux nodes
log_message("Début de la migration des fichiers attachés");

// Parcours de chaque type de contenu et ses champs
foreach ($types_to_fields as $type => $fields) {
  log_message("Traitement du type de contenu : $type");

  foreach ($fields as $field_name) {
    try {
      // Recherche des nodes ayant des fichiers dans le champ spécifié
      $nids = \Drupal::entityQuery('node')
        ->accessCheck(FALSE)
        ->condition('type', $type)
        ->condition($field_name, NULL, 'IS NOT NULL')
        ->execute();

      log_message("- Champ $field_name : " . count($nids) . " nodes trouvés");

      // Traitement de chaque node
      $nodes = Node::loadMultiple($nids);
      foreach ($nodes as $node) {
        $file = $node->get($field_name)->entity;
        if (!$file instanceof File) {
          $skipped_count++;
          log_message("Fichier ignoré : non trouvé dans $field_name pour le node " . $node->id(), 'warning');
          continue;
        }

        $media_info = get_media_bundle($file); // Déplacer AVANT la vérification
        if (!$media_info) {
          $skipped_count++;
          log_message("Fichier ignoré : type MIME non supporté pour " . $file->getFilename(), 'warning');
          continue;
        }

        // Vérifier si un média existe déjà
        $existing_media = \Drupal::entityQuery('media')
          ->accessCheck(FALSE)
          ->condition($media_info['field_target'], $file->id())
          ->execute();

        if (!empty($existing_media)) {
          $skipped_count++;
          log_message("Media déjà existant pour le fichier " . $file->getFilename(), 'info');
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
          echo "[Dry-run] Media non sauvegardés.\n";
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
    } catch (\Exception $e) {
      log_message("Erreur lors du traitement de $type/$field_name : " . $e->getMessage(), 'error');
      $error_count++;
    }
  }
}

// PARTIE 2 : Migration des fichiers orphelins
log_message("Début de la migration des fichiers orphelins");

// Récupération des IDs de fichiers utilisés dans des nodes
$used_fids = \Drupal::database()->select('file_usage', 'fu')
  ->fields('fu', ['fid'])
  ->condition('type', 'node')
  ->execute()
  ->fetchCol();

// Récupération de tous les IDs de fichiers existants
$all_fids = \Drupal::database()->select('file_managed', 'fm')
  ->fields('fm', ['fid'])
  ->execute()
  ->fetchCol();

// Calcul des fichiers orphelins (présents dans file_managed mais pas utilisés)
$orphans = array_diff($all_fids, $used_fids);
log_message("Nombre de fichiers orphelins trouvés : " . count($orphans));

// Traitement de chaque fichier orphelin
foreach ($orphans as $fid) {
  $file = File::load($fid);
  if (!$file instanceof File) {
    $skipped_count++;
    log_message("Fichier orphelin ignoré : impossible de charger le fid $fid", 'warning');
    continue;
  }

  $media_info = get_media_bundle($file);
  if (!$media_info) {
    $skipped_count++;
    log_message("Fichier orphelin ignoré : type MIME non supporté pour " . $file->getFilename(), 'warning');
    continue;
  }

  // Vérifier si un média existe déjà
  $existing_media = \Drupal::entityQuery('media')
    ->accessCheck(FALSE)
    ->condition($media_info['field_target'], $file->id())
    ->execute();

  if (!empty($existing_media)) {
    $skipped_count++;
    log_message("Media déjà existant pour le fichier orphelin " . $file->getFilename(), 'info');
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

// Affichage du résumé final avec statistiques
log_message("Migration terminée en " . (microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]) . " secondes");
log_message("- $created_count médias créés");
log_message("- $skipped_count fichiers ignorés");
log_message("- $error_count erreurs rencontrées");
