<?php

namespace PHPIntel\Completions;

use \Exception;

/*
* Formatter
* formats entities for sublime
*/
class Formatter
{
    public function formatEntitiesAsCompletions($entities) {
        $out = array();
        foreach($entities as $entity) {
            $out[] = array($entity['name']."\t".$this->formatTypeForSublime($entity), $this->escapeForSublime($entity['completion']));
        }
        return $out;
    }

    protected function formatTypeForSublime($entity) {
        // *   type: method, variable, constant
        // *   visibility: public, protected, private
        // *   scope: instance or static

        switch ($entity['type']) {
            case 'method':
                return $this->abbreviatedVisibilityAndScope($entity['visibility'], $entity['scope']).' func';
            break;
            case 'variable':
                return $this->abbreviatedVisibilityAndScope($entity['visibility'], $entity['scope']).' var';
            break;
            case 'constant':
                return $this->abbreviatedVisibilityAndScope($entity['visibility'], $entity['scope']).' const';
            break;
        }

        return 'unknown';
    }

    protected function abbreviatedVisibilityAndScope($full_visibility, $full_scope) {
        switch ($full_scope) {
            case 'instance':
                $scope_prefix = '';
                break;
            case 'static':
                $scope_prefix = ' st';
                break;
        }

        switch ($full_visibility) {
            case 'public':
                return 'pub'.$scope_prefix;
            break;
            case 'protected':
                return 'pro'.$scope_prefix;
            break;
            case 'private':
                return 'priv'.$scope_prefix;
            break;
        }

        return '';
    }

    protected function escapeForSublime($text) {
      return str_replace('$','\\$', $text);
    }
}
