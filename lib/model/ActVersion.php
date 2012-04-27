<?php

class ActVersion extends BaseObject {

  private static function getEmptyAnnotation() {
    return array('lines' => array(), 'history' => array());
  }

  static function insertVersion($act, $before, $otherVersion) {
    $newAv = Model::factory('ActVersion')->create();
    $newAv->actId = $act->id;
    $newAv->modifyingActId = null;
    $newAv->status = ACT_STATUS_VALID;
    $newAv->diff = '';
    $newAv->versionNumber = $before ? $otherVersion : ($otherVersion + 1);
    $newAv->current = true;

    $avs = Model::factory('ActVersion')->where('actId', $act->id)->find_many();
    foreach ($avs as $av) {
      if ($av->versionNumber >= $newAv->versionNumber) {
        $av->versionNumber++;
      }

      if ($av->versionNumber < $newAv->versionNumber) {
        $av->current = false;
      } else {
        $newAv->current = false;
      }

      $av->save();
    }

    $copyVersion = ($newAv->versionNumber == 1) ? 2 : ($newAv->versionNumber - 1);
    $copyAv = Model::factory('ActVersion')->where('actId', $act->id)->where('versionNumber', $copyVersion)->find_one();
    $newAv->contents = $copyAv->contents;
    $newAv->annotated = $copyAv->annotated;
    $newAv->htmlContents = $copyAv->htmlContents;

    return $newAv;
  }

  static function createVersionOne($act) {
    $av = Model::factory('ActVersion')->create();
    $av->actId = $act->id;
    $av->modifyingActId = $act->id;
    $av->status = ACT_STATUS_VALID;
    $av->contents = '';
    $av->annotated = json_encode(self::getEmptyAnnotation());
    $av->htmlContents = '';
    $av->diff = '';
    $av->versionNumber = 1;
    $av->current = true;
    return $av;
  }

  function annotate($previousVersion = null) {
    $lines = explode("\n", $this->contents);
    $ann = self::getEmptyAnnotation();
    require_once 'Act.php'; // So that ACT_STATUS_REPUBLISHED is defined
    if (!$previousVersion || $this->status == ACT_STATUS_REPUBLISHED) {
      $ann['lines'] = $lines;
      $ann['history'] = array_fill(0, count($lines), 'a1'); // Hacky, but 'a' . $this->versionNumber will show in green
    } else {
      $oldAnn = json_decode($previousVersion->annotated, true);
      $diff = SimpleDiff::commonLinesDiff($oldAnn['lines'], $lines);
      $diff[count($oldAnn['lines'])] = count($lines);

      $oldPrev = -1;
      $newPrev = -1;
      foreach ($diff as $oldKey => $newKey) {
        // $old[$oldPrev + 1 ... $oldKey - 1] was replaced by $new[$newPrev + 1 ... $newKey - 1]
        if ($oldPrev + 1 == $oldKey && $newPrev + 1 == $newKey) {
          // Both regions are empty (consecutive matching lines)
        } else if ($oldPrev + 1 == $oldKey && $newPrev + 1 < $newKey) {
          // Added lines
          for ($i = $newPrev + 1; $i < $newKey; $i++) {
            $ann['lines'][] = $lines[$i];
            $ann['history'][] = "a{$this->versionNumber}";
          }
        } else if ($oldPrev + 1 < $oldKey && $newPrev + 1 == $newKey) {
          // Deleted lines -- keep them!
          for ($i = $oldPrev + 1; $i < $oldKey; $i++) {
            $ann['lines'][] = $oldAnn['lines'][$i];
            $ann['history'][] = StringUtil::startsWith($oldAnn['history'][$i], 'd') ? $oldAnn['history'][$i] : "d{$this->versionNumber}";
          }
        } else {
          // Modified lines
          for ($i = $newPrev + 1; $i < $newKey; $i++) {
            $ann['lines'][] = $lines[$i];
            $ann['history'][] = "m{$this->versionNumber}";
          }
        }
        if ($newKey < count($lines)) {
          $ann['lines'][] = $lines[$newKey];
          $ann['history'][] = StringUtil::startsWith($oldAnn['history'][$oldKey], 'd')
            ? "a{$this->versionNumber}"      // Text was readded
            : $oldAnn['history'][$oldKey];
        }
        $oldPrev = $oldKey;
        $newPrev = $newKey;
      }
    }
    $this->annotated = json_encode($ann);
  }

  function validate() {
    $ma = Act::get_by_id($this->modifyingActId);
    if (!$ma) {
      FlashMessage::add('Actul modificator nu a fost găsit.');
    } else if ($this->versionNumber == 1 && $ma->id != $this->actId) {
      FlashMessage::add('Pentru versiunea 1, actul modificator trebuie să fie actul însuși.');
    } else if ($this->versionNumber > 1 && $ma->id == $this->actId) {
      FlashMessage::add('Pentru versiuni mai mari ca 1, actul modificator nu poate fi actul însuși.');
    }
    if (!$this->status) {
      FlashMessage::add('Actul trebuie să aibă o stare.');
    }
    if ($this->status == ACT_STATUS_REPUBLISHED && !$this->monitorId) {
      FlashMessage::add('Pentru republicări trebuie introdus și monitorul.');
    } else if ($this->status != ACT_STATUS_REPUBLISHED && $this->monitorId) {
      FlashMessage::add('Monitorul este folosit numai pentru republicări.');
    }
    return !FlashMessage::hasErrors();
  }

  function save() {
    $contentsChanged = $this->is_dirty('contents');
    $annotatedChanged = $this->is_dirty('annotated');
    $validityChanged = $this->is_dirty('status') || $this->is_dirty('current');
    if ($contentsChanged) {
      // Recompute the annotated field
      $previousAv = Model::factory('ActVersion')->where('actId', $this->actId)->where('versionNumber', $this->versionNumber - 1)->find_one();
      $this->annotate($previousAv);

      // Recompute the annotated field for all future versions
      $futureAvs =  Model::factory('ActVersion')->where('actId', $this->actId)->where_gt('versionNumber', $this->versionNumber)
        ->order_by_asc('versionNumber')->find_many();
      $prev = $this;
      foreach ($futureAvs as $av) {
        $av->annotate($prev);
        $av->save();
        $prev = $av;
      }
    }

    if ($contentsChanged || $annotatedChanged) {
      $actReferences = array();
      $monitorReferences = array();
      $this->htmlContents = MediaWikiParser::wikiToHtml($this, $actReferences, $monitorReferences);
    }

    if ($contentsChanged) {
      // Recompute the diff from the previous version
      if ($previousAv) {
        $this->diff = json_encode(SimpleDiff::lineDiff($previousAv->contents, $this->contents));
      }

      // Recompute the next version's diff from us
      $nextAv = Model::factory('ActVersion')->where('actId', $this->actId)->where('versionNumber', $this->versionNumber + 1)->find_one();
      if ($nextAv) {
        $nextAv->diff = json_encode(SimpleDiff::lineDiff($this->contents, $nextAv->contents));
        $nextAv->save();
      }
    }

    parent::save();

    if ($contentsChanged) {
      ActReference::deleteByActVersionId($this->id);
      ActReference::saveByActVersionId($actReferences, $this->id);
      MonitorReference::deleteByActVersionId($this->id);
      MonitorReference::saveByActVersionId($monitorReferences, $this->id);
    }
    if ($validityChanged) {
      // The act references haven't changed, but the link class might have
      ActVersion::reconvertByReferredActId($this->actId);
    }
  }

  static function reconvertByReferredActId($referredActId) {
    $actVersionIdMap = array();
    $refs = ActReference::get_all_by_referredActId($referredActId);
    foreach ($refs as $ref) {
      $actVersionIdMap[$ref->actVersionId] = true;
    }
    ActVersion::reconvertMap($actVersionIdMap);
  }

  static function reconvertMap($actVersionIdMap) {
    foreach ($actVersionIdMap as $actVersionId => $ignored) {
      $av = ActVersion::get_by_id($actVersionId);
      $av->htmlContents = MediaWikiParser::wikiToHtml($av);
      $av->save(); // Note that we haven't touched $av->contents
    }
  }

  function delete() {
    $prev = Model::factory('ActVersion')->where('actId', $this->actId)->where('versionNumber', $this->versionNumber - 1)->find_one();
    $next = Model::factory('ActVersion')->where('actId', $this->actId)->where('versionNumber', $this->versionNumber + 1)->find_one();

    if ($next) {
      $next->diff = $prev ? json_encode(SimpleDiff::lineDiff($prev->contents, $next->contents)) : '';
      $next->save();
    }

    if ($this->current && $prev) {
      $prev->current = true;
      $prev->save();
    }

    $avs = Model::factory('ActVersion')->where('actId', $this->actId)->where_gt('versionNumber', $this->versionNumber)->find_many();
    foreach ($avs as $av) {
      $av->versionNumber--;
      $av->annotate($prev);
      $av->save();
      $prev = $av;
    }

    ActReference::deleteByActVersionId($this->id);
    MonitorReference::deleteByActVersionId($this->id);

    $wasCurrent = $this->current;
    $oldActId = $this->actId;
    parent::delete();

    if ($wasCurrent) {
      // The act references haven't changed, but the link class might have
      ActVersion::reconvertByReferredActId($oldActId);
    }
    return true;
  }

}

?>
