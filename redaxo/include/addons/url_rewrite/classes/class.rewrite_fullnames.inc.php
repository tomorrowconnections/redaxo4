<?php

define('FULLNAMES_PATHLIST', $REX['INCLUDE_PATH'].'/generated/files/pathlist.php');

/**
 * URL-Rewrite Addon
 * @author staab[at]public-4u[dot]de Markus Staab
 * @author <a href="http://www.public-4u.de">www.public-4u.de</a>
 * @package redaxo3
 * @version $Id: class.rewrite_fullnames.inc.php 90 2008-12-11 14:09:45Z ssh-68390 $
 */

/**
 * URL Fullnames Rewrite Anleitung:
 *
 *   1) .htaccess file in das root verzeichnis:
 *     #RewriteCond %{HTTP_HOST} ^domain.tld [NC]
 *     #RewriteRule ^(.*)$ http://www.domain.tld/$1 [L,R=301]
 *     RewriteEngine On
 *     #RewriteBase /
 *     RewriteCond %{REQUEST_FILENAME} !-f
 *     RewriteCond %{REQUEST_FILENAME} !-d
 *     RewriteCond %{REQUEST_FILENAME} !-l
 *     RewriteCond %{REQUEST_URI} !redaxo/.*
 *     RewriteCond %{REQUEST_URI} !files/.*
 *     RewriteRule ^(.*)$ index.php?%{QUERY_STRING} [L]
 *
 *   2) .htaccess file in das redaxo/ verzeichnis:
 *     RewriteEngine Off
 *
 *   3) im Template folgende Zeile AM ANFANG des <head> erg�nzen:
 *   <base href="htttp://www.meine_domain.de/pfad/zum/frontend" />
 *
 *   4) Specials->Regenerate All starten
 *
 *   5) ggf. Rewrite-Base der .htaccess Datei anpassen
 *
 * @author staab[at]public-4u[dot]de Markus Staab
 * @author <a href="http://www.redaxo.de">www.redaxo.de</a>
 *
 * @author office[at]vscope[dot]at Wolfgang Huttegger
 * @author <a href="http://www.vscope.at/">vscope new media</a>
 *
 * @author rn[at]gn2-netwerk[dot]de R�diger Nitzsche
 * @author <a href="http://www.gn2-netwerk.de/">GN2 Netwerk</a>
 */

class myUrlRewriter extends rexUrlRewriter
{
  var $use_levenshtein;
  var $use_params_rewrite;

  // Konstruktor
  function myUrlRewriter($use_levenshtein = false, $use_params_rewrite = false)
  {
    $this->use_levenshtein = $use_levenshtein;
    $this->use_params_rewrite = $use_params_rewrite;

    // Parent Konstruktor aufrufen
    parent::rexUrlRewriter();
  }

  // Parameter aus der URL f�r das Script verarbeiten
  function prepare()
  {
    global $article_id, $clang, $REX, $REXPATH;

    if (!$REX['REDAXO'])
    {
      if(!file_exists(FULLNAMES_PATHLIST))
      {
         rex_rewriter_generate_pathnames();
      }

      require_once (FULLNAMES_PATHLIST);

      $script_path = dirname($_SERVER['PHP_SELF']);
      $length = strlen($script_path);
      $path = substr($_SERVER['REQUEST_URI'], $length);

      // Parameter z�hlen nicht zum Pfad -> abschneiden
      if(($pos = strpos($path, '?')) !== false)
      $path = substr($path, 0, $pos);

      // Anker z�hlen nicht zum Pfad -> abschneiden
      if(($pos = strpos($path, '#')) !== false)
      $path = substr($path, 0, $pos);

      if ($path == '')
      {
        $article_id = $REX['START_ARTICLE_ID'];
        return true;
      }

      // konvertiert params zu GET/REQUEST Variablen
      if($this->use_params_rewrite)
      {
        if(strstr($path,'/+/'))
        {
          $tmp = explode('/+/',$path);
          $path = $tmp[0].'/';
          $vars = explode('/',$tmp[1]);
          for($c=0;$c<count($vars);$c+=2)
          {
            if($vars[$c]!='')
            {
              $_GET[$vars[$c]] = $vars[$c+1];
              $_REQUEST[$vars[$c]] = $vars[$c+1];
            }
          }
        }
      }


      foreach ($REXPATH as $key => $var)
      {
        foreach ($var as $k => $v)
        {
          if ($path == $v)
          {
            $article_id = $key;
            $clang = $k;
          }
        }
      }

      // Check Clang StartArtikel
      if (!$article_id)
      {
        foreach ($REX['CLANG'] as $key => $var)
        {
          if ($var.'/' == $path)
          {
            $clang = $key;
          }
        }
      }

      // Check levenshtein
      if ($this->use_levenshtein && !$article_id)
      {
        foreach ($REXPATH as $key => $var)
        {
          foreach ($var as $k => $v)
          {
            $levenshtein[levenshtein($path, $v)] = $key.'#'.$k;
          }
        }

        ksort($levenshtein);
        $best = explode('#', array_shift($levenshtein));
        $article_id = $best[0];
        $clang = $best[1];
      }

      if (!$article_id) {
        $article_id = $REX['NOTFOUND_ARTICLE_ID'];
      }
    }
  }

  // Url neu schreiben
  function rewrite($params)
  {
    // Url wurde von einer anderen Extension bereits gesetzt
    if($params['subject'] != '')
  		return $params['subject'];

    global $REXPATH;

    $id = $params['id'];
    $name = $params['name'];
    $clang = $params['clang'];
    $params = $params['params'];
    $divider = $params['divider'];

    // params umformatieren neue Syntax suchmaschienen freundlich
    if($this->use_params_rewrite)
    {
      $params = str_replace($divider,'/',$params);
      $params = str_replace('=','/',$params);
      $params = $params == '' ? '' : '+'.$params.'/';
    }
    else
    {
      $params = $params == '' ? '' : '?'.$params;
    }

    $params = str_replace('/amp;','/',$params);
    $url = $REXPATH[$id][$clang].$params;
    
    return $url;
  }
}

if ($REX['REDAXO'])
{
  // Die Pathnames bei folgenden Extension Points aktualisieren
  $extension = 'rex_rewriter_generate_pathnames';
  $extensionPoints = array(
    'CAT_ADDED',   /*'CAT_UPDATED',*/   'CAT_DELETED',
    'ART_ADDED',   'ART_UPDATED',   'ART_DELETED',
    /*'CLANG_ADDED', 'CLANG_UPDATED', 'CLANG_DELETED',*/
    'ALL_GENERATED');

  foreach($extensionPoints as $extensionPoint)
  rex_register_extension($extensionPoint, $extension);
}

function rex_rewriter_generate_pathnames($params = array ())
{
  global $REX, $REXPATH;

  if(file_exists(FULLNAMES_PATHLIST))
  {
    require_once (FULLNAMES_PATHLIST);
  }
  
  if(!isset($REXPATH)) 
    $REXPATH = array();
    
  $where = '';
  switch($params['extension_point'])
  {
    // ------- sprachabh�ngig, einen artikel aktualisieren
    case 'CAT_DELETED':
    case 'ART_DELETED':
      unset($REXPATH[$params['id']]);
      break;
    case 'CAT_ADDED':
    // CAT_UPDATED nicht notwendig, da nur artikelnamen in urls gebraucht werden!
    // case 'CAT_UPDATED':
    case 'ART_ADDED':
    case 'ART_UPDATED':
      $where = '(id='. $params['id'] .' AND clang='. $params['clang'] .') OR (path LIKE "%|'. $params['id'] .'|%" AND clang='. $params['clang'] .')';
      break;
    // ------- sprachabh�ngig, alle artikel aktualisieren
    // CLANG_* nicht notwendig, da immer von ALL_GENERATED gefolgt!
    /*
    case 'CLANG_ADDED':
    case 'CLANG_UPDATED':
    case 'CLANG_DELETED':
      $where = 'clang='. $params['id'];
      break;
    */
    // ------- alles aktualisieren
    case 'ALL_GENERATED':
      $where = '1=1';
      break;
  }

  
  if($where != '')
  {
    $db = new rex_sql();
  //  $db->debugsql=true;
    $db->setQuery('SELECT id,name,clang,path FROM '. $REX['TABLE_PREFIX'] .'article WHERE '. $where);
    
    while($db->hasNext())
    {
      $clang = $db->getValue('clang');
      $pathname = '';
      if (count($REX['CLANG']) > 1)
      {
        $pathname = $REX['CLANG'][$clang].'/';
      }
      
      $path = explode('|', $db->getValue('path'));
      $path[] = $db->getValue('id');
      foreach ($path as $p)
      {
        if ($p != '')
        {
          $ooa = OOArticle::getArticleById($p, $clang);
          $name = $ooa->getName();
          unset($ooa); 
          if ($name != '')
          {
            $name = str_replace('&','und',$name);
            $name = strtolower(rex_parse_article_name($name));
            $pathname .= $name.'/';
          }
        }
      }
  
      $pathname = substr($pathname,0,strlen($pathname)-1).'.html';
      $REXPATH[$db->getValue('id')][$db->getValue('clang')] = $pathname;
      
      $db->next();
    }
  }
  
  rex_put_file_contents(FULLNAMES_PATHLIST, "<?php\n\$REXPATH = ". var_export($REXPATH, true) ."\n?>");
}