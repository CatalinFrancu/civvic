<?php

class ActReference extends BaseObject {

  static function deleteByActVersionId($avId) {
    $refs = Model::factory('ActReference')->where('actVersionId', $avId)->find_many();
    foreach ($refs as $ref) {
      $ref->delete();
    }
  }

  static function deleteByActTypeId($actTypeId) {
    $refs = Model::factory('ActReference')->where('actTypeId', $actTypeId)->find_many();
    foreach ($refs as $ref) {
      $ref->delete();
    }
  }

  static function saveByActVersionId($references, $avId) {
    foreach ($references as $ref) {
      $ref->actVersionId = $avId;
      $ref->save();
    }
  }

  static function reassociate($actTypeId, $number, $year, &$actVersionIdMap = null) {
    $actType = ActType::get_by_id($actTypeId);
    if ($actType->hasNumbers) {
      $refs = Model::factory('ActReference')->where('actTypeId', $actTypeId)->where('number', $number)->where('year', $year)->find_many();
    } else {
      $refs = Model::factory('ActReference')->where('actTypeId', $actTypeId)->find_many();
    }
    foreach ($refs as $ref) {
      $actVersion = ActVersion::get_by_id($ref->actVersionId);
      $act = Act::get_by_id($actVersion->actId);
      $referredAct = Act::getReferredAct($ref, $act->estimateIssueDate());
      $referredActId = $referredAct ? $referredAct->id : null;
      if ($referredActId != $ref->referredActId) {
        $ref->referredActId = $referredActId;
        $ref->save();
        if ($actVersionIdMap !== null) {
          $actVersionIdMap[$ref->actVersionId] = true;
        }
      }
    }
  }

}

?>
