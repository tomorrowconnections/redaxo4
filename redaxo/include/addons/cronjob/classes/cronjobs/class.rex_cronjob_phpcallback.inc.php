<?php

/**
 * Cronjob Addon
 *
 * @author gharlan[at]web[dot]de Gregor Harlan
 *
 * @package redaxo4
 * @version svn:$Id$
 */

class rex_cronjob_phpcallback extends rex_cronjob
{ 
  /*public*/ function execute()
  {
    if (preg_match('/^\s*(?:(.*?)\:\:)?(.*?)(?:\((.*?)\))?\;?\s*$/', $this->getContent(), $matches))
    {
      $callback = $matches[2];
      if ($matches[1] != '')
      {
        $callback = array($matches[1], $callback);
      }
      if(!is_callable($callback))
        return false;
      $params = array();
      if($matches[3] != '') 
      {
        $params = explode(',', $matches[3]);
        foreach($params as $i => $param)
        {
          $param = preg_replace('/^(\\\'|\")?(.*?)\\1$/', '$2', trim($param));
          $params[$i] = $param;
        }
      }
      return call_user_func_array($callback, $params) !== false;
    }
    return false;
  }
}