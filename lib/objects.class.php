<?php
/*
* @version 0.1 (auto-set)
*/


/**
* Title
*
* Description
*
* @access public
*/
 function getObject($name) {
  $qry='1';
  if (preg_match('/^(.+?)\.(.+?)$/', $name, $m)) {
   $class_name=$m[1];
   $object_name=$m[2];
   $rec=SQLSelectOne("SELECT objects.* FROM objects LEFT JOIN classes ON objects.CLASS_ID=classes.ID WHERE objects.TITLE LIKE '".DBSafe($object_name)."' AND classes.TITLE LIKE '".DBSafe($class_name)."'");
  } else {
   $rec=SQLSelectOne("SELECT objects.* FROM objects WHERE TITLE LIKE '".DBSafe($name)."'");
  }
  if ($rec['ID']) {
   include_once(DIR_MODULES.'objects/objects.class.php');
   $obj=new objects();
   $obj->id=$rec['ID'];
   $obj->loadObject($rec['ID']);
   return $obj;
  }
  return 0;
 }


/**
* Title
*
* Description
*
* @access public
*/
 function getObjectsByClass($class_name) {
  $class_record=SQLSelectOne("SELECT ID FROM classes WHERE TITLE LIKE '".DBSafe(trim($class_name))."'");
  if (!$class_record['ID']) {
   return 0;
  }

  $objects=SQLSelect("SELECT ID, TITLE FROM objects WHERE CLASS_ID='".$class_record['ID']."'");

  $sub_classes=SQLSelect("SELECT ID, TITLE FROM classes WHERE PARENT_ID='".$class_record['ID']."'");
  if ($sub_classes[0]['ID']) {
   $total=count($sub_classes);
   for($i=0;$i<$total;$i++) {
    $sub_objects=getObjectsByClass($sub_classes[$i]['TITLE']);
    if ($sub_objects[0]['ID']) {
     foreach($sub_objects as $obj) {
      $objects[]=$obj;
     }
    }
   }
  }

  /*
  $total=count($objects);
  for($i=0;$i<$total;$i++) {
   $objects[$i]=getObject($objects[$i]['TITLE'])
  }
  */

  return $objects;


 }


/**
* Title
*
* Description
*
* @access public
*/
 function getGlobal($varname) {

  $value=SQLSelectOne("SELECT VALUE FROM pvalues WHERE PROPERTY_NAME = '".DBSafe($varname)."'");
  if (isset($value['VALUE'])) {
   return $value['VALUE'];
  }

  $tmp=explode('.', $varname);
  if ($tmp[2]) {
   $object_name=$tmp[0].'.'.$tmp[1];
   $varname=$tmp[2];
  } elseif ($tmp[1]) {
   $object_name=$tmp[0];
   $varname=$tmp[1];
  } else {
   $object_name='ThisComputer';
  }
  $obj=getObject($object_name);
  if ($obj) {
   return $obj->getProperty($varname);
  } else {
   return 0;
  }
 }

/**
* Title
*
* Description
*
* @access public
*/
 function setGlobal($varname, $value, $no_linked=0) {
  $tmp=explode('.', $varname);
  if ($tmp[2]) {
   $object_name=$tmp[0].'.'.$tmp[1];
   $varname=$tmp[2];
  } elseif ($tmp[1]) {
   $object_name=$tmp[0];
   $varname=$tmp[1];
  } else {
   $object_name='ThisComputer';
  }
  $obj=getObject($object_name);
  if ($obj) {
   return $obj->setProperty($varname, $value, $no_linked);
  } else {
   return 0;
  }
 }

/**
* Title
*
* Description
*
* @access public
*/
 function callMethod($method_name, $params=0) {
  $tmp=explode('.', $method_name);
  if ($tmp[2]) {
   $object_name=$tmp[0].'.'.$tmp[1];
   $varname=$tmp[2];
  } elseif ($tmp[1]) {
   $object_name=$tmp[0];
   $method_name=$tmp[1];
  } else {
   $object_name='ThisComputer';
  }
  $obj=getObject($object_name);
  if ($obj) {
   return $obj->callMethod($method_name, $params);
  } else {
   return 0;
  }
 
 }

/**
* processTitle
*
* Description
*
* @access public
*/
  function processTitle($title, $object=0) {

   if (!$title) {
    return $title;
   }

   startMeasure('processTitle');

   $in_title=substr($title, 0, 100);

   startMeasure('processTitle ['.$in_title.']');

   if ($in_title!='') {


   if (preg_match('/\[#.+?#\]/is', $title)) {
    if ($object) {
     $jTempl=new jTemplate($title, $object->data, $object);
    } else {
     $jTempl=new jTemplate($title, $data, $this);
    }
    $title=$jTempl->result;
   }


   $title=preg_replace('/%rand%/is', rand(), $title);
   if (preg_match_all('/%([\w\d\.]+?)\.([\w\d\.]+?)%/is', $title, $m)) {
    $total=count($m[0]);
    for($i=0;$i<$total;$i++) {
     $title=str_replace($m[0][$i], getGlobal($m[1][$i].'.'.$m[2][$i]), $title);
    }
   } elseif (preg_match_all('/%([\w\d\.]+?)%/is', $title, $m)) {
    $total=count($m[0]);
    for($i=0;$i<$total;$i++) {
     $title=str_replace($m[0][$i], getGlobal($m[1][$i]), $title);
    }
   }

   if (preg_match_all('/<#LANG_(\w+?)#>/is', $title, $m)) {
    $total=count($m[0]);
    for($i=0;$i<$total;$i++) {
     $title=str_replace($m[0][$i], constant('LANG_'.$m[1][$i]), $title);
    }
   }

   if (preg_match_all('/\&#060#LANG_(.+?)#\&#062/is', $title, $m)) {
    $total=count($m[0]);
    for($i=0;$i<$total;$i++) {
     $title=str_replace($m[0][$i], constant('LANG_'.$m[1][$i]), $title);
    }
   }

   }

   endMeasure('processTitle ['.$in_title.']', 1);
   endMeasure('processTitle', 1);
   return $title;
  }


// SHORT ALIAS *****************************

 function sg($varname, $value, $no_linked=0) {
  return setGlobal($varname, $value, $no_linked);
 }

 function gg($varname) {
  return getGlobal($varname, $value);
 }

 function cm($method_name, $params=0) {
  return callMethod($method_name, $params);
 }

 function runMethod($method_name, $params=0) {
  return callMethod($method_name, $params);
 }

 function rs($script_id, $params=0) {
  return runScript($script_id, $params);
 }



?>