<?php

	/**
	 * @package toolkit
	 */
	/**
	 * The FieldManager class is responsible for managing all fields types in Symphony.
	 * Fields are stored on the file system either in the `/fields` folder of `TOOLKIT` or
	 * in a `fields` folder in an extension directory.
	 */

	require_once(TOOLKIT . '/class.field.php');

	Class FieldManager implements FileResource {

		/**
		 * An array of all the objects that the Manager is responsible for.
		 * Defaults to an empty array.
		 * @var array
		 */
		protected static $_pool = array();

		/**
		 * An array of all fields whose have been created by ID
		 * @var array
		 */
		private static $_initialiased_fields = array();

		/**
		 * Given the filename of a Field, return it's handle. This will remove
		 * the Symphony conventions of `field.*.php`
		 *
		 * @param string $filename
		 *  The filename of the Field
		 * @return string
		 */
		public static function __getHandleFromFilename($filename){
			return preg_replace(array('/^field./i', '/.php$/i'), '', $filename);
		}

		/**
		 * Given a type, returns the full class name of a Field. Fields use a
		 * 'field' prefix
		 *
		 * @param string $type
		 *  A field handle
		 * @return string
		 */
		public static function __getClassName($type){
			return 'field' . $type;
		}

		/**
		 * Finds a Field by type by searching the `TOOLKIT . /fields` folder and then
		 * any fields folders in the installed extensions. The function returns
		 * the path to the folder where the field class resides.
		 *
		 * @param string $name
		 *  The field handle, that is, `field.{$handle}.php`
		 * @return string
		 */
		public static function __getClassPath($type){
			if(is_file(TOOLKIT . "/fields/field.{$type}.php")) return TOOLKIT . '/fields';
			else{

				$extensions = Symphony::ExtensionManager()->listInstalledHandles();

				if(is_array($extensions) && !empty($extensions)){
					foreach($extensions as $e){
						if(is_file(EXTENSIONS . "/{$e}/fields/field.{$type}.php")) return EXTENSIONS . "/{$e}/fields";
					}
				}
			}

			return false;
		}

		/**
		 * Given a field type, return the path to it's class
		 *
		 * @see __getClassPath()
		 * @param string $type
		 *  The handle of the field to load (it's type)
		 * @return string
		 */
		public static function __getDriverPath($type){
			return self::__getClassPath($type) . "/field.{$type}.php";
		}

		/**
		 * This function is not implemented by the `FieldManager` class
		 */
		public static function about($name) {
			return false;
		}

		/**
		 * Given an associative array of fields, insert them into the database
		 * returning the resulting Field ID if successful, or false if there
		 * was an error. As fields are saved in order on a section, a query is
		 * made to determine the sort order of this field to be current sort order
		 * +1.
		 *
		 * @param array $fields
		 *  Associative array of field names => values for the Field object
		 * @return integer|boolean
		 *  Returns a Field ID of the created Field on success, false otherwise.
		 */
		public static function add(array $fields){

			if(!isset($fields['sortorder'])){
				$next = Symphony::Database()->fetchVar("next", 0, 'SELECT MAX(`sortorder`) + 1 AS `next` FROM tbl_fields LIMIT 1');
				$fields['sortorder'] = ($next ? $next : '1');
			}

			if(!Symphony::Database()->insert($fields, 'tbl_fields')) return false;
			$field_id = Symphony::Database()->getInsertID();

			return $field_id;
		}

		/**
		 * Given a Field ID and associative array of fields, update an existing Field
		 * row in the `tbl_fields`table. Returns boolean for success/failure
		 *
		 * @param integer $id
		 *  The ID of the Field that should be updated
		 * @param array $fields
		 *  Associative array of field names => values for the Field object
		 *  This array does need to contain every value for the field object, it
		 *  can just be the changed values.
		 * @return boolean
		 */
		public static function edit($id, array $fields){
			if(!Symphony::Database()->update($fields, "tbl_fields", " `id` = '$id'")) return false;

			return true;
		}

		/**
		 * Given a Field ID, delete a Field from Symphony. This will remove the field from
		 * the fields table, all of the data stored in this field's `tbl_entries_data_$id` any
		 * existing section associations. This function additionally call the Field's `tearDown`
		 * method so that it can cleanup any additional settings or entry tables it may of created.
		 *
		 * @param integer $id
		 *  The ID of the Field that should be deleted
		 * @return boolean
		 */
		public static function delete($id) {
			$existing = self::fetch($id);
			$existing->tearDown();

			Symphony::Database()->delete('tbl_fields', " `id` = '$id'");
			Symphony::Database()->delete('tbl_fields_'.$existing->handle(), " `field_id` = '$id'");
			Symphony::Database()->delete('tbl_sections_association', " `child_section_field_id` = '$id'");

			Symphony::Database()->query('DROP TABLE `tbl_entries_data_'.$id.'`');

			return true;
		}

		/**
		 * The fetch method returns a instance of a Field from tbl_fields. The most common
		 * use of this function is to retrieve a Field by ID, but it can be used to retrieve
		 * Fields from a Section also. There are several parameters that can be used to fetch
		 * fields by their Type, Location, by a Field Constant or with a custom WHERE query.
		 *
		 * @param integer|array $id
		 *  The ID of the field to retrieve. Defaults to null which will return multiple field
		 *  objects. Since Symphony 2.3, `$id` will accept an array of Field ID's
		 * @param integer $section_id
		 *  The ID of the section to look for the fields in. Defaults to null which will allow
		 *  all fields in the Symphony installation to be searched on.
		 * @param string $order
		 *  Available values of ASC (Ascending) or DESC (Descending), which refer to the
		 *  sort order for the query. Defaults to ASC (Ascending)
		 * @param string $sortfield
		 *  The field to sort the query by. Can be any from the tbl_fields schema. Defaults to
		 *  'sortorder'
		 * @param string $type
		 *  Filter fields by their type, ie. input, select. Defaults to null
		 * @param string $location
		 *  Filter fields by their location in the entry form. There are two possible values,
		 *  'main' or 'sidebar'. Defaults to null
		 * @param string $where
		 *  Allows a custom where query to be included. Must be valid SQL. The tbl_fields alias
		 *  is t1
		 * @param string $restrict
		 *  Only return fields if they match one of the Field Constants. Available values are
		 *  `__TOGGLEABLE_ONLY__`, `__UNTOGGLEABLE_ONLY__`, `__FILTERABLE_ONLY__`,
		 *  `__UNFILTERABLE_ONLY__` or `__FIELD_ALL__`. Defaults to `__FIELD_ALL__`
		 * @return array
		 *  An array of Field objects. If no Field are found, null is returned.
		 */
		public static function fetch($id = null, $section_id = null, $order = 'ASC', $sortfield = 'sortorder', $type = null, $location = null, $where = null, $restrict=Field::__FIELD_ALL__){
			$fields = array();
			$returnSingle = false;
			$ids = array();
			$field_contexts = array();

			if(!is_null($id)) {
				if(is_numeric($id)) {
					$returnSingle = true;
				}

				if(!is_array($id)) {
					$field_ids = array((int)$id);
				}
				else {
					$field_ids = $id;
				}

				// Loop over the `$field_ids` and check to see we have
				// instances of the request fields
				foreach($field_ids as $key => $field_id) {
					if(
						isset(self::$_initialiased_fields[$field_id])
						&& self::$_initialiased_fields[$field_id] instanceof Field
					) {
						$fields[$field_id] = self::$_initialiased_fields[$field_id];
						unset($field_ids[$key]);
					}
				}
			}

			// If there is any `$field_ids` left to be resolved lets do that, otherwise
			// if `$id` wasn't provided in the first place, we'll also continue
			if(!empty($field_ids) || is_null($id)) {
				$sql = sprintf("
						SELECT t1.*
						FROM tbl_fields AS `t1`
						WHERE 1
						%s %s %s %s
						%s
					",
					isset($type) ? " AND t1.`type` = '{$type}' " : NULL,
					isset($location) ? " AND t1.`location` = '{$location}' " : NULL,
					isset($section_id) ? " AND t1.`parent_section` = '{$section_id}' " : NULL,
					$where,
					isset($field_ids) ? " AND t1.`id` IN(" . implode(',', $field_ids) . ") " : " ORDER BY t1.`{$sortfield}` {$order}"
				);

				if(!$result = Symphony::Database()->fetch($sql)) return ($returnSingle ? null : array());

				// Loop over the resultset building an array of type, field_id
				foreach($result as $f) {
					$ids[$f['type']][] = $f['id'];
				}

				// Loop over the `ids` array, which is grouped by field type
				// and get the field context.
				foreach($ids as $type => $field_id) {
					$field_contexts[$type] = Symphony::Database()->fetch(sprintf(
						"SELECT * FROM `tbl_fields_%s` WHERE `field_id` IN (%s)",
						$type, implode(',', $field_id)
					), 'field_id');
				}

				foreach($result as $f) {
					// We already have this field in our static store
					if(
						isset(self::$_initialiased_fields[$f['id']])
						&& self::$_initialiased_fields[$f['id']] instanceof Field
					) {
						$field = self::$_initialiased_fields[$f['id']];
					}
					// We don't have an instance of this field, so let's set one up
					else {
						$field = self::create($f['type']);
						$field->setArray($f);

						// Get the context for this field from our previous queries.
						$context = $field_contexts[$f['type']][$f['id']];

                        if (is_array($context) && !empty($context)) {
    						try {
    							unset($context['id']);
    							$field->setArray($context);
    						}
    
    						catch (Exception $e) {
    							throw new Exception(__(
    								'Settings for field %s could not be found in table tbl_fields_%s.',
    								array($f['id'], $f['type'])
    							));
    						}
						}

						self::$_initialiased_fields[$f['id']] = $field;
					}

					// Check to see if there was any restricts imposed on the fields
					if (
						$restrict == Field::__FIELD_ALL__
						|| ($restrict == Field::__TOGGLEABLE_ONLY__ && $field->canToggle())
						|| ($restrict == Field::__UNTOGGLEABLE_ONLY__ && !$field->canToggle())
						|| ($restrict == Field::__FILTERABLE_ONLY__ && $field->canFilter())
						|| ($restrict == Field::__UNFILTERABLE_ONLY__ && !$field->canFilter())
					) {
						$fields[$f['id']] = $field;
					}
				}
			}

			return count($fields) <= 1 && $returnSingle ? current($fields) : $fields;
		}

		/**
		 * Given a field ID, return the type of the field by querying `tbl_fields`
		 *
		 * @param integer $id
		 * @return string
		 */
		public static function fetchFieldTypeFromID($id){
			return Symphony::Database()->fetchVar('type', 0, "SELECT `type` FROM `tbl_fields` WHERE `id` = '$id' LIMIT 1");
		}

		/**
		 * Given a field ID, return the handle of the field by querying `tbl_fields`
		 *
		 * @param integer $id
		 * @return string
		 */
		public static function fetchHandleFromID($id){
			return Symphony::Database()->fetchVar('element_name', 0, "SELECT `element_name` FROM `tbl_fields` WHERE `id` = '$id' LIMIT 1");
		}

		/**
		 * Given an element name and it's section, return it's ID. Symphony enforces
		 * a uniqueness constraint on a section where every field must have a unique
		 * label (and therefore handle) so whilst it is impossible to have two fields
		 * from the same section, it would be possible to have two fields with the same
		 * name from different sections. Passing the $section_id allows you to specify
		 * which section should be searched.
		 *
		 * @param string $element_name
		 *  The handle of the Field label
		 * @param integer $section_id
		 *  The section that this field belongs too
		 * @return integer
		 *  The field ID
		 */
		public static function fetchFieldIDFromElementName($element_name, $section_id = null){
			return Symphony::Database()->fetchVar('id', 0, sprintf("
					SELECT `id`
					FROM `tbl_fields`
					WHERE `element_name` = '%s' %s
					LIMIT 1
				",
				Symphony::Database()->cleanValue($element_name),
				(!is_null($section_id) ? " AND `parent_section` = $section_id " : "")
			));
		}

		/**
		 * Returns an array of all available field handles discovered in the
		 * `TOOLKIT . /fields` or `EXTENSIONS . /{}/fields`.
		 *
		 * @return array
		 *  A single dimensional array of field handles.
		 */
		public static function listAll() {
			$structure = General::listStructure(TOOLKIT . '/fields', '/field.[a-z0-9_-]+.php/i', false, 'asc', TOOLKIT . '/fields');

			$extensions = Symphony::ExtensionManager()->listInstalledHandles();

			if(is_array($extensions) && !empty($extensions)){
				foreach($extensions as $handle) {
					if(is_dir(EXTENSIONS . '/' . $handle . '/fields')){
						$tmp = General::listStructure(EXTENSIONS . '/' . $handle . '/fields', '/field.[a-z0-9_-]+.php/i', false, 'asc', EXTENSIONS . '/' . $handle . '/fields');
						if(is_array($tmp['filelist']) && !empty($tmp['filelist'])) {
							$structure['filelist'] = array_merge($structure['filelist'], $tmp['filelist']);
						}
					}
				}

				$structure['filelist'] = General::array_remove_duplicates($structure['filelist']);
			}

			$types = array();

			foreach($structure['filelist'] as $filename) {
				$types[] = self::__getHandleFromFilename($filename);
			}
			return $types;
		}

		/**
		 * Creates an instance of a given class and returns it. Adds the instance
		 * to the `$_pool` array with the key being the handle.
		 *
		 * @param string $type
		 *  The handle of the Field to create (which is it's handle)
		 * @return Field
		 */
		public static function create($type){
			if(!isset(self::$_pool[$type])){
				$classname = self::__getClassName($type);
				$path = self::__getDriverPath($type);

				if(!file_exists($path)){
					throw new Exception(
						__('Could not find Field %1$s at %2$s.', array('<code>' . $type . '</code>', '<code>' . $path . '</code>'))
						. ' ' . __('If it was provided by an Extension, ensure that it is installed, and enabled.')
					);
				}

				if(!class_exists($classname)){
					require_once($path);
				}

				self::$_pool[$type] = new $classname;

				if(self::$_pool[$type]->canShowTableColumn() && !self::$_pool[$type]->get('show_column')){
					self::$_pool[$type]->set('show_column', 'yes');
				}
			}

			return clone self::$_pool[$type];
		}

		/**
		 * Returns an array of all available field handles discovered in the
		 * `TOOLKIT . /fields` or `EXTENSIONS . /{}/fields`.
		 *
		 * @deprecated This function will be removed in Symphony 2.4. Use
		 * `FieldManager::listAll` instead.
		 * @return array
		 *  A single dimensional array of field handles.
		 */
		public static function fetchTypes() {
			return FieldManager::listAll();

		}
	}
