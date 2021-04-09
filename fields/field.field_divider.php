<?php

	Class fieldField_Divider extends Field {

		public function __construct(){
			parent::__construct();

			$this->_name = __('Divider');
		}

		public function createTable() {
			return Symphony::Database()->query(
				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) unsigned NOT NULL,
				  `value` double default NULL,
				  PRIMARY KEY  (`id`),
				  KEY `entry_id` (`entry_id`),
				  KEY `value` (`value`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
			);
		}

	/*-------------------------------------------------------------------------
		Settings:
	-------------------------------------------------------------------------*/

		/**
		 * Displays setting panel in section editor.
		 *
		 * @param XMLElement $wrapper - parent element wrapping the field
		 * @param array $errors - array with field errors, $errors['name-of-field-element']
		 */
		public function displaySettingsPanel(XMLElement &$wrapper, $errors = null) {
			// Initialize field settings based on class defaults (name, placement)
			parent::displaySettingsPanel($wrapper, $errors);

			$order = $this->get('sortorder');

			$group = new XMLElement('div');
			$group->setAttribute('class', 'two columns');

			// Margin
			$label = Widget::Label(__('Margin'));
			$label->setAttribute('class', 'column');
			$label->appendChild(
				new XMLElement('i', __('Optional'))
			);
			$label->appendChild(Widget::Input(
				"fields[{$order}][margin]", $this->get('margin')
			));
			$label->appendChild(
				new XMLElement('p', __('You can override the default spacing, e.g 25px 0px 35px 0px'), array('class' => 'help'))
			);

			$group->appendChild($label);
			$wrapper->appendChild($group);


			$group = new XMLElement('div');
			$group->setAttribute('class', 'two columns');

			// Show Label
			$label = Widget::Label(null, null, 'column');
			$input = Widget::Input('fields['.$order.'][show-label]', 'yes', 'checkbox');

			if ($this->get('show-label') == 'yes') $input->setAttribute('checked', 'checked');

			$label->setValue(__('%s Display the label?', array($input->generate())));

			$group->appendChild($label);
			$wrapper->appendChild($group);
		}

		public function commit(){
			if(!parent::commit()) return false;

			$id = $this->get('id');
			$order = $this->get('sortorder');

			if($id === false) return false;

			$fields = array();
			$fields['field_id'] = $id;
			$fields['margin'] = $_POST['fields'][$order]['margin'];
			$fields['show-label'] = $_POST['fields'][$order]['show-label'];

			return FieldManager::saveSettings($id, $fields);
		}

		/**
		 * Exclude field from DS output.
		 */
		public function fetchIncludableElements() {
 			return null;
 		}

	/*-------------------------------------------------------------------------
		Publish:
	-------------------------------------------------------------------------*/

		public function displayPublishPanel(XMLElement &$wrapper, $data = NULL, $flagWithError = NULL, $fieldnamePrefix = NULL, $fieldnamePostfix = NULL, $entry_id = NULL){
			if ($this->get('show-label') == 'yes') {
				$group = new XMLElement('p', $this->get('label'));
				$wrapper->appendChild($group);
			}

			if ($this->get('margin') != '') {
				$wrapper->setAttribute('style', 'margin: ' . $this->get('margin') . ';');
			}
		}

		public function processRawFieldData($data, &$status, &$message = NULL, $simulate = false, $entry_id = NULL) {
			$status = self::__OK__;

			return array(
				'value' => ''
			);
		}

		public function prepareReadableValue($data, $entry_id = NULL, $truncate = false, $defaultValue = NULL) {
			return $this->prepareTableValue($data, null, $entry_id);
		}

		public function prepareTableValue($data, XMLElement $link=NULL, $entry_id=NULL) {
			// build this entry fully
			$entries = EntryManager::fetch($entry_id);

			if ($entries === false) return parent::prepareTableValue(NULL, $link, $entry_id);

			$entry = reset(EntryManager::fetch($entry_id));

			// get the first field inside this tab
			$field_id = Symphony::Database()->fetchVar('id', 0, "SELECT `id` FROM `tbl_fields` WHERE `parent_section` = '".$this->get('parent_section')."' AND `sortorder` = ".($this->get('sortorder') + 1)." ORDER BY `sortorder` LIMIT 1");

			if ($field_id === NULL) return parent::prepareTableValue(NULL, $link, $entry_id);

			$field = FieldManager::fetch($field_id);

			// get the first field's value as a substitude for the tab's return value
			return $field->prepareTableValue($entry->getData($field_id), $link, $entry_id);
		}
	}
