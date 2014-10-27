<?php
namespace TYPO3\CMS\Form\Domain\Repository;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Repository for tx_form_Domain_Model_Content
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class ContentRepository {

	/**
	 * Get the referenced record from the database
	 *
	 * Using the GET or POST variable 'P'
	 *
	 * @return bool|\TYPO3\CMS\Form\Domain\Model\Content if found, FALSE if not
	 */
	public function getRecord() {
		$record = FALSE;
		$getPostVariables = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('P');
		$table = (string) $getPostVariables['table'];
		$recordId = (int)$getPostVariables['uid'];
		$row = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($table, $recordId);
		if (is_array($row)) {
			// strip off the leading "[Translate to XY]" text after localizing the original record
			$languageField = $GLOBALS['TCA']['tt_content']['ctrl']['languageField'];
			$transOrigPointerField = $GLOBALS['TCA']['tt_content']['ctrl']['transOrigPointerField'];
			if ($row[$languageField] > 0 && $row[$transOrigPointerField] > 0) {
				$bodytext = preg_replace('/^\[.*?\] /', '', $row['bodytext'], 1);
			} else {
				$bodytext = $row['bodytext'];
			}

			/** @var $typoScriptParser \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser */
			$typoScriptParser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\Parser\\TypoScriptParser');
			$typoScriptParser->parse($bodytext);
			/** @var $record \TYPO3\CMS\Form\Domain\Model\Content */
			$record = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Form\\Domain\\Model\\Content');
			$record->setUid($row['uid']);
			$record->setPageId($row['pid']);
			$record->setTyposcript($typoScriptParser->setup);
		}
		return $record;
	}

	/**
	 * Check if the referenced record exists
	 *
	 * @return bool TRUE if record exists, FALSE if not
	 */
	public function hasRecord() {
		return $this->getRecord() !== FALSE;
	}

	/**
	 * Convert and save the incoming data of the FORM wizard
	 *
	 * @return bool TRUE if succeeded, FALSE if not
	 */
	public function save() {
		$json = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('configuration');
		$parameters = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('P');
		$success = FALSE;
		/** @var $converter \TYPO3\CMS\Form\Domain\Factory\JsonToTypoScript */
		$converter = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Form\\Domain\\Factory\\JsonToTypoScript');
		$typoscript = $converter->convert($json);
		if ($typoscript) {
			// Make TCEmain object:
			/** @var $tce \TYPO3\CMS\Core\DataHandling\DataHandler */
			$tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
			$tce->stripslashes_values = 0;
			// Put content into the data array:
			$data = array();
			$data[$parameters['table']][$parameters['uid']][$parameters['field']] = $typoscript;
			// Perform the update:
			$tce->start($data, array());
			$tce->process_datamap();
			$success = TRUE;
		}
		return $success;
	}

	/**
	 * Read and convert the content record to JSON
	 *
	 * @return The JSON object if record exists, FALSE if not
	 */
	public function getRecordAsJson() {
		$json = FALSE;
		$record = $this->getRecord();
		if ($record) {
			$typoscript = $record->getTyposcript();
			/** @var $converter \TYPO3\CMS\Form\Utility\TypoScriptToJsonConverter */
			$converter = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Form\\Utility\\TypoScriptToJsonConverter');
			$json = $converter->convert($typoscript);
		}
		return $json;
	}

}
