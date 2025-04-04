<?php

use Drupal\node\Entity\Node;

// Associer chaque type de contenu à l'ID du média par défaut.
$default_media_ids = [
  'actualit_' => 196,
  'article' => 197,
  'documents' => 198,
  'notes_de_lectures' => 199,
  'revue_de_presse' => 200,
];

$field_name = 'field_media_image';

foreach ($default_media_ids as $bundle => $media_id) {
  $nids = \Drupal::entityQuery('node')
    ->accessCheck(FALSE)
    ->condition('type', $bundle)
    ->notExists($field_name)
    ->execute();

  echo "Traitement de {$bundle} : " . count($nids) . " nœuds sans image trouvés\n";

  $nodes = Node::loadMultiple($nids);

  foreach ($nodes as $node) {
    $node->set($field_name, ['target_id' => $media_id]);
    $node->save();
    echo " → Node ID: {$node->id()} ({$bundle}) mis à jour avec le média {$media_id}\n";
  }
}
