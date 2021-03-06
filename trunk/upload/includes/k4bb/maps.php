<?php
/**
* k4 Bulletin Board, maps.inc.php
*
* Copyright (c) 2005, Peter Goodman
*
* This library is free software; you can redistribute it and/orextension=php_gd2.dll
* modify it under the terms of the GNU Lesser General Public
* License as published by the Free Software Foundation; either
* version 2.1 of the License, or (at your option) any later version.
* 
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
* Lesser General Public License for more details.
* 
* Licensed under the LGPL license
* http://www.gnu.org/copyleft/lesser.html
*
* @author Peter Goodman
* @version $Id: maps.php 144 2005-07-05 02:29:07Z Peter Goodman $
* @package k42
*/



if(!defined('IN_K4')) {
	return;
}

/**
 * Recursively get MAPs
 */
function get_recursive_maps(&$request, &$all_maps, $parents, &$maps, $start_level) {
	
	while($maps->next()) {
		$map = $maps->current();
		
		if($map['row_level']-$start_level > 0) {
			$map['level']	= str_repeat('<img src="Images/'. $request['template']->getVar('IMG_DIR') .'/Icons/threaded_bit.gif" alt="" border="0" />', $map['row_level']-$start_level);
		}

		$all_maps[]		= $map;
		
		$is_expanded	= !$parents && !is_array($parents) ? TRUE : in_array($map['id'], $parents); 

		if($is_expanded && $map['num_children'] > 0) {
			
			// reset it if needed
			$map['expanded'] = 1;
			$all_maps[count($all_maps)-1] = $map;
			
			$n_maps = $request['dba']->executeQuery("SELECT * FROM ". K4MAPS ." WHERE parent_id = ". intval($map['id']) ." ORDER BY name ASC");
			get_recursive_maps($request, $all_maps, $parents, $n_maps, $start_level);
		}

	}
}

/**
 * Get a MAP
 */
function get_map($varname, $method, $args) {
	
	global $_MAPS;

	$perm_needed = -1;
	
	/* Simple global MAP request */
	if(is_array($args) && empty($args)) {
		$perm_needed		= isset($_MAPS[$varname][$method]) ? $_MAPS[$varname][$method] : $perm_needed;
	} else {
		
		$usergroups = $_SESSION['user']->get('usergroups') != '' ? explode('|', $_SESSION['user']->get('usergroups')) : array();
		
		/* Forum */
		if(isset($args['forum_id']) && intval($args['forum_id']) != 0) {
			
			// do basic
			if($varname != '') {
				$perm_needed	= isset($_MAPS['forums'][$args['forum_id']][$varname][$method]) ? $_MAPS['forums'][$args['forum_id']][$varname][$method] : $perm_needed;
			} else {
				$perm_needed	= isset($_MAPS['forums'][$args['forum_id']][$method]) ? $_MAPS['forums'][$args['forum_id']][$method] : $perm_needed;
			}
			
			/* If a permission hasn't been found, look in the master forum permission set */
			if($perm_needed == -1) {
				if($varname != '') {
					$perm_needed	= isset($_MAPS['forums'][0][$varname][$method]) ? $_MAPS['forums'][0][$varname][$method] : 0;
				} else {
					$perm_needed	= isset($_MAPS['forums'][0][$method]) ? $_MAPS['forums'][0][$method] : 0;
				}
			}
			
			/* Still no found perm? oh well. */
			if($perm_needed == -1)
				$perm_needed = 0;
			
			// now compare with permission masks
			foreach($usergroups as $id) {
				
				// does the mask for this variable exist under this group?
				if(isset($_MAPS['groups'][$id]['forums'][$args['forum_id']])) {
					
					// if so, get a new permission needed
					if($varname != '') {
						$i_perm_needed	= isset($_MAPS['groups'][$id]['forums'][$args['forum_id']][$varname][$method]) ? $_MAPS['groups'][$id]['forums'][$args['forum_id']][$varname][$method] : 0;
					} else {
						$i_perm_needed	= isset($_MAPS['groups'][$id]['forums'][$args['forum_id']][$method]) ?$_MAPS['groups'][$id]['forums'][$args['forum_id']][$method] : 0;
					}

					// if there is a perm that is > than what normally is needed, set the level higher.
					if($i_perm_needed > $perm_needed) {
						$perm_needed = $i_perm_needed;
						break;
					}
				}
			}

		/* Group */
		} else if(isset($args['group_id']) && intval($args['group_id']) != 0) {
			
			$perm_needed	= isset($_MAPS['groups'][$args['group_id']][$varname][$method]) ? $_MAPS['groups'][$args['group_id']][$varname][$method] : 0;
		
		/* User */
		} else if(isset($args['user_id']) && intval($args['user_id']) != 0) {
			$perm_needed	= isset($_MAPS['users'][$args['user_id']][$varname][$method]) ? $_MAPS['groups'][$args['group_id']][$varname][$method] : 0;
		
//		/* Category */
//		} else if(isset($args['category_id']) && intval($args['category_id']) != 0) {
//			
//			if($varname != '') {
//				$perm_needed	= isset($_MAPS['categories'][$args['category_id']][$varname][$method]) ? $_MAPS['categories'][$args['category_id']][$varname][$method] : 0;
//			} else {
//				$perm_needed	= isset($_MAPS['categories'][$args['category_id']][$method]) ? $_MAPS['categories'][$args['category_id']][$method] : 0;
//			}
		/* Blog */
		} else if(isset($args['blog']) && $args['blog'] == TRUE) {
			$perm_needed	= isset($_MAPS['blog'][$varname][$method]) ? $_MAPS['blog'][$varname][$method] : 0;
		
		/* Global */
		} else {
			$perm_needed	= isset($_MAPS[$varname][$method]) ? $_MAPS[$varname][$method] : 0;
		}
	}

	return $perm_needed;
}

function get_maps(&$dba) {
	
	$maps	= array();
	
	/* Get everything from the maps table, this is only executed once per cache */
	$query	= "SELECT * FROM ". K4MAPS;
	
	$result = $dba->executeQuery($query);

	while($result->next()) {
		$val = $result->current();

		if($val['varname'] != '') {
			
			/**
			 * Deal with the master forum permission set.. it is somewhat complicated
			 */
			if($val['varname'] == 'forum0' || (isset($maps['forums'][0]['id']) && $val['parent_id'] == $maps['forums'][0]['id'])) {
				if($val['varname'] == 'forum0') {
					$maps['forums'][0] = isset($maps['forums'][0]) ? array_merge($maps['forums'][0], $val) : $val;
				} else {
					$maps['forums'][0][$val['varname']] = $val;
				}
			
			/**
			 * Deal with all other permissions.. order is CRUCIAL
			 */
			} else {
				
				/* If the group_id equals zero */
				if($val['group_id'] == 0) {
					
					/* Forums */
					if($val['forum_id'] != 0) {
						if( ($val['varname'] == 'forum'. $val['forum_id']) ) { // !isset($maps['forums'][$val['forum_id']]) &&
							$maps['forums'][$val['forum_id']] = isset($maps['forums'][$val['forum_id']]) ? array_merge($maps['forums'][$val['forum_id']], $val) : $val;
						} else {
							$maps['forums'][$val['forum_id']][$val['varname']] = $val;
						}
					
					/* Users */
					} else if($val['user_id'] != 0) {
						if(!isset($maps['users'][$val['user_id']]) && ($val['varname'] == 'user'. $val['user_id']) ) {
							$maps['users'][$val['user_id']] = $val;
						} else {
							$maps['users'][$val['user_id']][$val['varname']] = $val;
						}
					
//					/* Categories */
//					} else if($val['category_id'] != 0) {
//						if(($val['varname'] == 'category'. $val['category_id']) ) { // !isset($maps['categories'][$val['category_id']]) && 
//							$maps['categories'][$val['category_id']] = isset($maps['categories'][$val['category_id']]) ? array_merge($maps['categories'][$val['category_id']], $val) : $val;
//						} else {
//							$maps['categories'][$val['category_id']][$val['varname']] = $val;
//						}
//					
					/* Gloabal */
					} else {
						$maps[$val['varname']] = $val;
					}			
				
				/**
				 * Subcategorize into forum permission masks
				 */
				} else {

					if(!isset($maps['groups'][$val['group_id']]))
						$maps['groups'][$val['group_id']] = array();
					
					if($val['forum_id'] != 0)
						$maps['groups'][$val['group_id']]['forums'][$val['forum_id']] = ($val['varname'] == 'forum'. $val['forum_id']) ? $val : array($val['varname'] => $val);
				}
			}
		
		} else {
			$maps['global'] = $val;
		}
	}

	return $maps;
}

?>