<?php

class ActAuthor extends BaseObject {
  static $SIGNED = 1;
  static $COUNTERSIGNED = 2;
  static $PROMULGATED = 3;

  static $signatureTypes = array(1 => 'semnat', 2 => 'contrasemnat', 3 => 'promulgat');

  function getSignatureTypeName() {
    return self::$signatureTypes[$this->signatureType];
  }

  /**
   * Updates the list of authors for an act, preserving the existing records when possible.
   * Adds ActAuthor records if needed. Deletes leftover records.
   **/
  static function saveAuthors($actId, $authors, $signatureTypes, $notes) {
    $authorMap = Author::loadAllMapByDisplayName();
    $oldAas = Model::factory('ActAuthor')->where('actId', $actId)->order_by_asc('rank')->find_many();

    $rank = 1;
    for ($i = 0; $i < count($authors); $i++) {
      $authorId = array_key_exists($authors[$i], $authorMap) ? $authorMap[$authors[$i]] : false;
      if ($authorId) {
        $aa = empty($oldAas) ? Model::factory('ActAuthor')->create() : array_shift($oldAas);
        $aa->actId = $actId;
        $aa->authorId = $authorId;
        $aa->signatureType = $signatureTypes[$i];
        $aa->note = $notes[$i];
        $aa->rank = $rank++;
        $aa->save();
      }
    }
    foreach ($oldAas as $aa) {
      $aa->delete();
    }
  }

}

?>
