<?php
namespace Scwebs\Pdflister\Controller;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2015 SCWEBS <info@scwebs.in>, SCWEBS
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

    /*
    TODO:flex_recursive

    */

/**
 * ListPdfController
 */
class ListPdfController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

    /**
     * @var \TYPO3\CMS\Core\Resource\ResourceFactory
     * @inject
     */
    protected $resourceFactory;

    protected $filesList = NULL;

    protected $folderPath = NULL;

    protected $folderCombinedId = NULL;

    protected $storageObj = NULL;

    protected $folderObj = NULL;

    /**
     * action initialize
     *
     * @return void
     */
    protected function initializeAction() {

        //print_r($this->settings);

        $this->folderPath = str_replace('1:','',$this->settings['flex_startingpoint']);
        $this->folderCombinedId = $this->settings['flex_startingpoint'];

        //$fac = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ResourceFactory'); // create instance to storage repository

        $this->storageObj = $this->resourceFactory->getStorageObjectFromCombinedIdentifier( $this->folderCombinedId );

        $this->folderObj = $this->storageObj->getFolder($this->folderPath);

        $files = $this->folderObj->getFiles();

        foreach($files as $file){
            $this->filesList[$file->getUid()]['name'] = $file->getName() ;
            $this->filesList[$file->getUid()]['identifier'] = $file->getIdentifier() ;
        }
    }

	/**
	 * action list
	 *
	 * @return void
	 */
	public function listAction() {
        $this->view->assign('filesList', $this->filesList);
	}

	/**
	 * action get
	 *
     * @param string $uid
	 * @return void
	 */
	public function getAction($uid = '') {
        $extraData = $this->getExtraData();
        if( count($extraData['errors']) ){
            $this->view->assign('errors', $extraData['errors'] );
        }else{
            $this->sendParsedFILE_forDownload($uid,$extraData);
        }
	}

    /**
     * function sendParsedFILE_forDownload
     *
     * @param string $uid
     * @param array $extraData
     * @return void
     */
    private function sendParsedFILE_forDownload($uid,$extraData){
        $fileIdentifier = $this->filesList[$uid]['identifier'];

        $file = $this->storageObj->getFile( $fileIdentifier );
        $fileSource = $file->getPublicUrl(true);

        $fileName = $this->filesList[$uid]['name'];

        $pdf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('FPDI');


        $pageCount = $pdf->setSourceFile($fileSource);

        for($i=1;$i<=$pageCount;$i++){
            $tplIdx = $pdf->importPage($i);

            $pdf->addPage();
            $pdf->useTemplate($tplIdx, null, null, 0, 0, true);

            /* Apply 'font OR font sizes' if given - ELSE Default Arial Size 10 */
            if($this->settings['flex_font'] && $this->settings['flex_font_size']){
                $pdf->SetFont($this->settings['flex_font'],'',$this->settings['flex_font_size']);
            }elseif($this->settings['flex_font']){
                $pdf->SetFont($this->settings['flex_font'],'',10);
            }elseif($this->settings['flex_font_size']){
                $pdf->SetFont('Arial','',$this->settings['flex_font_size']);
            }else{
                $pdf->SetFont('Arial','',10);
            }

            $pdf->SetTextColor(255, 255, 255);
            /* END OF - Apply 'font OR font sizes' if given */

            if($i==1){

                //IF This is the First Page

                //Apply flex_posxy_sub
                if($this->settings['flex_posxy']){
                    $posxy = explode(',',$this->settings['flex_posxy']);
                    $pdf->SetXY($posxy[0],$posxy[1]);
                }else{
                    $pdf->SetXY(10,0);
                }

                //Apply flex_cellsize
                if($this->settings['flex_cellsize']){
                    $posxy = explode(',',$this->settings['flex_cellsize']);
                    $pdf->Cell($posxy[0],$posxy[1],$extraData['licenseInfo']);
                }else{
                    $pdf->Cell(40,10,$extraData['licenseInfo']);
                }
            }else{

                //ELSE This is a sub page

                //Apply flex_posxy_sub on sub pages
                if($this->settings['flex_posxy_sub']){
                    $posxy = explode(',',$this->settings['flex_posxy_sub']);
                    $pdf->SetXY($posxy[0],$posxy[1]);
                }else{
                    $pdf->SetXY(10,0);
                }

                //Apply flex_cellsize
                if($this->settings['flex_cellsize']){
                    $posxy = explode(',',$this->settings['flex_cellsize']);
                    $pdf->Cell($posxy[0],$posxy[1],$extraData['licenseInfo']);
                }else{
                    $pdf->Cell(40,10,$extraData['licenseInfo']);
                }

            }
        }

        $pdf->Output($fileName,'D');
        die();
    }


    /**
     * function getExtraData
     *
     * @ param string $md5hash
     * @ param array $extraData
     * @return Array
     */
    private function getExtraData(){

        $llLabel = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('pdflabel', 'pdflister');

        if( $this->settings['flex_datamessage'] ){
            $llLabel = $this->settings['flex_datamessage'];
        }

        $username = '';
        $fullName = '';
        $email = '';
        $first_name = '';
        $last_name = '';
        $availableMarkers = str_getcsv('###USERNAME###,###FULLNAME###,###EMAIL###,###FIRST_NAME###,###LAST_NAME###,###DOMAIN###,###DATE###,###TIME###',',');

        if($this->settings['flex_adduserdata'] && $GLOBALS['TSFE']->fe_user->user){
            //if the user is logged in - use his data for displaying in the licence info

            $loggedUser = $GLOBALS['TSFE']->fe_user->user;

            if($loggedUser['first_name'] && $loggedUser['middle_name'] && $loggedUser['last_name']){
                $fullName = $loggedUser['first_name'] . ' ' . $loggedUser['middle_name'] . ' ' . $loggedUser['last_name'];
                $first_name = $loggedUser['first_name'];
                $last_name = $loggedUser['last_name'];
            }elseif($loggedUser['first_name'] && $loggedUser['last_name']){
                $fullName = $loggedUser['first_name'] . ' ' . $loggedUser['last_name'];
                $first_name = $loggedUser['first_name'];
                $last_name = $loggedUser['last_name'];
            }elseif($loggedUser['name']){
                $fullName = $loggedUser['name'];
            }else{
                $fullName = $loggedUser['username'];
            }

            $username = $loggedUser['username'];
            $email = $loggedUser['email'];


        }elseif($this->settings['flex_adduserdata']){
            //if user is not logged in then we must fill an error message in the extra data
            $extraData['errors'][] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('user-not-loggedin', 'pdflister');
        }else{
            $llLabel = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('pdf-label-general', 'pdflister');
        }

        $replacements['USERNAME'] = $username;
        $replacements['FULLNAME'] = $fullName;
        $replacements['EMAIL'] = $email;
        $replacements['FIRST_NAME'] = $first_name;
        $replacements['LAST_NAME'] = $last_name;

        $replacements['DOMAIN'] =  \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_HOST');

        if($this->settings['flex_dateformat']){
            $replacements['DATE'] = date($this->settings['flex_dateformat']);
        }else{
            $replacements['DATE'] = date('d/M/Y');
        }
        if($this->settings['flex_timeformat']){
            $replacements['TIME'] = date($this->settings['flex_timeformat']);
        }else{
            $replacements['TIME'] = date('H:i');
        }

        $extraData['licenseInfo'] = str_replace($availableMarkers, $replacements, $llLabel);

        return $extraData;

    }

}