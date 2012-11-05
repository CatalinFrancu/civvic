<?php

class Author extends BaseObject {

  static function loadAllMapByDisplayName() {
    $authors = Model::factory('Author')->find_many();
    $map = array();
    foreach ($authors as $a) {
      $map[$a->getDisplayName()] = $a->id;
    }
    return $map;
  }

  static function getForActId($actId) {
    $actAuthors = Model::factory('ActAuthor')->where('actId', $actId)->order_by_asc('rank')->find_many();
    $authors = array();
    foreach ($actAuthors as $aa) {
      $authors[] = self::get_by_id($aa->authorId);
    }
    return $authors;
  }

  function getDisplayName() {
    $bits = array();
    if ($this->institution) {
      $bits[] = $this->institution;
    }
    if ($this->position) {
      $bits[] = $this->position;
    }
    if ($this->title) {
      $bits[] = $this->title;
    }
    if ($this->name) {
      $bits[] = $this->name;
    }
    return implode(', ', $bits);
  }

  /** Returns an error message, or null on success. **/
  function validate() {
    if (!$this->institution && !$this->position && !$this->title && !$this->name) {
      return 'Cel puțin unul din câmpuri trebue să fie nevid.';
    }
    return false;
  }

  function canDelete() {
    $count = Model::factory('ActAuthor')->where('authorId', $this->id)->count();
    error_log("id: $this->id, count: $count");
    return $count == 0;
  }

  function delete() {
    if ($this->canDelete()) {
      return parent::delete();
    }
    return false;
  }

  function __toString() {
    $s = '';
    if ($this->name) {
      $s .= "[nume:{$this->name}] ";
    }
    if ($this->title) {
      $s .= "[titlu:{$this->title}] ";
    }
    if ($this->position) {
      $s .= "[funcție:{$this->position}] ";
    }
    if ($this->institution) {
      $s .= "[instituție:{$this->institution}] ";
    }
    return trim($s);
  }

}

?>
