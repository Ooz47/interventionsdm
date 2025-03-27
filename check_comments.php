<?php
$comment_storage = \Drupal::entityTypeManager()->getStorage('comment');
$comment_types = \Drupal::entityTypeManager()->getStorage('comment_type')->loadMultiple();

foreach ($comment_types as $id => $type) {
  $count = $comment_storage->getQuery()
  ->accessCheck(FALSE)
    ->condition('comment_type', $id)
    ->count()
    ->execute();
  echo "Type de commentaire : $id => $count commentaires\n";
}
