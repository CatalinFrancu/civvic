<?php

define('ACT_STATUS_VALID', 1);
define('ACT_STATUS_REPEALED', 2);

class Act extends BaseObject {
  static $statuses = array(ACT_STATUS_VALID => 'valabil',
                           ACT_STATUS_REPEALED => 'abrogat');

  function validate() {
    if (mb_strlen($this->name) < 3) {
      FlashMessage::add('Numele trebuie să aibă minim trei caractere.');
    }
    if (!StringUtil::isValidYear($this->year)) {
      FlashMessage::add('Anul trebuie să fie între 1800 și 2100.');
    }
    if (!$this->actTypeId) {
      FlashMessage::add('Actul trebuie să aibă un tip.');
    }
    if ($this->year && $this->number) {
      $otherAct = Model::factory('Act')->where('actTypeId', $this->actTypeId)->where('year', $this->year)->where('number', $this->number);
      $otherAct = $this->issueDate
        ? $otherAct->where('issueDate', $this->issueDate)
        : $otherAct->where_null('issueDate');
      $otherAct = $otherAct->find_one();
      if ($otherAct && $otherAct->id != $this->id) {
        FlashMessage::add('Există deja un act cu acest tip, număr, an și dată.');
      }
    }
    return !FlashMessage::getMessage();
  }

  function save() {
    $needsReassociation = $this->is_dirty('actTypeId') || $this->is_dirty('number') || $this->is_dirty('year') || $this->is_dirty('issueDate');
    if ($this->issueDate == '') {
      $this->issueDate = null;
    }
    if ($needsReassociation) {
      $oldAct = Act::get_by_id($this->id);
    }
    parent::save();
    // The HTML has changed for all the actVersions this act modifies, and all future versions from those acts
    $modifiedAvs = Model::factory('ActVersion')->where('modifyingActId', $this->id)->find_many();
    foreach ($modifiedAvs as $modifiedAv) {
      $avs = Model::factory('ActVersion')->where('actId', $modifiedAv->actId)->where_gte('versionNumber', $modifiedAv->versionNumber)->find_many();
      foreach ($avs as $av) {
        $av->htmlContents = MediaWikiParser::wikiToHtml($av);
        $av->save();
      }
    }

    if ($needsReassociation) {
      $actVersionIdMap = array();
      if ($oldAct) {
        ActReference::reassociate($oldAct->actTypeId, $oldAct->number, $oldAct->year, $actVersionIdMap);
      }
      ActReference::reassociate($this->actTypeId, $this->number, $this->year, $actVersionIdMap);
      ActVersion::reconvertMap($actVersionIdMap);
    }
  }

  function countVersions() {
    return Model::factory('ActVersion')->where('actId', $this->id)->count();
  }

  static function listYears($actTypeName) {
    $actType = ActType::get_by_name($actTypeName);
    $acts = Model::factory('Act')->select('year')->distinct()->where('actTypeId', $actType->id)->order_by_desc('year')->find_many();
    $results = array();
    foreach ($acts as $a) {
      $results[] = $a->year;
    }
    return $results;
  }

  function getDisplayId() {
    $at = ActType::get_by_id($this->actTypeId);
    $result = $at->artName . ' ';
    $result .= ($this->year && $this->number) ? "{$this->number} / {$this->year}" : "din {$this->issueDate}";
    return $result;
  }

  // Class to use when linking to this act
  function getDisplayClass() {
    $version = Model::factory('ActVersion')->select('status')->where('actId', $this->id)->where('current', true)->find_one();
    return ($version && $version->status == ACT_STATUS_VALID) ? 'valid' : 'repealed';
  }

  function estimateIssueDate() {
    if ($this->issueDate) {
      return $this->issueDate;
    }
    $monitor = Monitor::get_by_id($this->monitorId);
    if ($monitor && $monitor->issueDate) {
      return $monitor->issueDate;
    }
    return "{$this->year}-12-31";
  }

  /**
   * Returns the most likely act with the given actTypeId, number and year. When there are multiple matches:
   * - When a specific issueDate is given,
   *   - Return the act with the exact issueDate if it exists
   *   - Return any act with a null issueDate if one exists
   *   - Return null (do not return any act with a different issueDate)
   * - When no specific issueDate is given,
   *   - Return the act with the largest issueDate lower than the referring act's issueDate if one exists
   *   - Return any act with a null issueDate if one exists
   *   - Return the act with the smallest issueDate
   **/
  static function getReferredAct($ar, $referringIssueDate) {
    if ($ar->issueDate) {
      $act = Model::factory('Act')->where('actTypeId', $ar->actTypeId)->where('number', $ar->number)->where('year', $ar->year)
        ->where('issueDate', $ar->issueDate)->find_one();
      if (!$act) {
        $act = Model::factory('Act')->where('actTypeId', $ar->actTypeId)->where('number', $ar->number)->where('year', $ar->year)
          ->where_null('issueDate')->find_one();
      }
    } else {
      $act = Model::factory('Act')->where('actTypeId', $ar->actTypeId)->where('number', $ar->number)->where('year', $ar->year)
        ->where_lte('issueDate', $referringIssueDate)->order_by_desc('issueDate')->find_one();
      if (!$act) {
        $act = Model::factory('Act')->where('actTypeId', $ar->actTypeId)->where('number', $ar->number)->where('year', $ar->year)
          ->where_null('issueDate')->find_one();
      }
      if (!$act) {
        $act = Model::factory('Act')->where('actTypeId', $ar->actTypeId)->where('number', $ar->number)->where('year', $ar->year)
          ->order_by_asc('issueDate')->find_one();
      }
    }
    return $act;
  }

  static function getLink($act, $actReference, $text) {
    if (!$act) {
      return sprintf('<a class="actLink undefined" href="http://civvic.ro/act-inexistent?data=%s:%s:%s">%s</a>',
                     $actReference->actTypeId, $actReference->number, $actReference->year, $text);
    }

    $class = $act->getDisplayClass();
    return sprintf('<a class="actLink %s" href="http://civvic.ro/act?id=%s">%s</a>', $class, $act->id, $text);
  }

  /* Returns a map of versionNumber -> modifying act for that version */
  static function getModifyingActs($actId) {
    $map = array();
    $avs = Model::factory('ActVersion')->select('versionNumber')->select('modifyingActId')->where('actId', $actId)->find_many();
    foreach ($avs as $av) {
      $map[$av->versionNumber] = Act::get_by_id($av->modifyingActId);
    }
    return $map;
  }

  function delete() {
    $count = Model::factory('ActVersion')->where_not_equal('actId', $this->id)->where('modifyingActId', $this->id)->count();
    if ($count) {
      FlashMessage::add("Actul '{$this->getDisplayId()}' nu poate fi șters, deoarece el modifică alte acte.");
      return false;
    }

    $avs = Model::factory('ActVersion')->where('actId', $this->id)->order_by_desc('versionNumber')->find_many();
    foreach ($avs as $av) {
      $av->delete();
    }

    $actAuthors = Model::factory('ActAuthor')->where('actId', $this->id)->find_many();
    foreach ($actAuthors as $aa) {
      $aa->delete();
    }

    $oldAct = Act::get($this->id);
    parent::delete();
    $actVersionIdMap = array();
    ActReference::reassociate($oldAct->actTypeId, $oldAct->number, $oldAct->year, $actVersionIdMap);
    ActVersion::reconvertMap($actVersionIdMap);
    return true;
  }
}

?>
